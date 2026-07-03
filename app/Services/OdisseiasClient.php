<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Cliente HTTP para o portal de parceiros da Odisseias
 * (https://parceiros.odisseias.com). Não existe API pública — confirmado
 * diretamente com a Marta/diretor comercial em 2026-07-02 — mas o portal
 * (ASP.NET MVC clássico) expõe tudo o que precisamos via pedidos simples,
 * investigado ao vivo em 2026-07-03:
 *
 *  1. Login: GET /Account/Login (para ler o token __RequestVerificationToken
 *     escondido no formulário) + POST /Account/DoLogin com UserName,
 *     Password e esse token. A sessão fica guardada num cookie jar.
 *  2. Lista: GET /Appointment/ListAsync?Date_From=DD-MM-YYYY&Date_To=DD-MM-YYYY&...
 *     devolve uma tabela HTML (não JSON) com nº voucher, nº reserva,
 *     cliente, data, hora, estado, e um botão "VER DETALHES" com
 *     data-id (código da reserva) e data-type.
 *  3. Detalhe: POST /Appointment/Details com esses id/type devolve HTML
 *     com contacto telefónico, email, produto, "inclui", prazo de
 *     cancelamento e Preço NET — nenhum destes campos vem na lista.
 *
 * Não precisa de browser/headless: só pedidos HTTP com cookie jar.
 * Credenciais NUNCA hardcoded — vêm de config('odisseias'), que lê do .env
 * de cada container (mesma regra usada para SMTP e outros segredos deste
 * projeto). Ver config/odisseias.php.
 */
class OdisseiasClient
{
    private Client $http;
    private bool $loggedIn = false;

    public function __construct(?string $baseUrl = null)
    {
        $this->http = new Client([
            'base_uri' => $baseUrl ?? config('odisseias.base_url', 'https://parceiros.odisseias.com'),
            'cookies' => new CookieJar(),
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; AugustaAdviserSync/1.0)',
            ],
            'http_errors' => false,
        ]);
    }

    public function login(?string $username = null, ?string $password = null): bool
    {
        $username ??= config('odisseias.username');
        $password ??= config('odisseias.password');

        if (!$username || !$password) {
            throw new \RuntimeException('Credenciais da Odisseias não configuradas (ODISSEIAS_USERNAME / ODISSEIAS_PASSWORD no .env).');
        }

        $loginPage = $this->http->get('/Account/Login');
        $html = (string) $loginPage->getBody();

        if (!preg_match('/__RequestVerificationToken"[^>]*value="([^"]+)"/', $html, $m)) {
            throw new \RuntimeException('Não foi possível ler o __RequestVerificationToken da página de login da Odisseias — a estrutura do formulário pode ter mudado.');
        }
        $token = $m[1];

        $response = $this->http->post('/Account/DoLogin', [
            'form_params' => [
                '__RequestVerificationToken' => $token,
                'Redirect_Url' => '',
                'UserName' => $username,
                'Password' => $password,
            ],
        ]);

        // Confirma sessão autenticada tentando uma página que exige login.
        $check = $this->http->get('/');
        $body = (string) $check->getBody();
        $this->loggedIn = str_contains($body, 'As suas marcações') || str_contains($body, 'Consultar marcações');

        return $this->loggedIn;
    }

    private function ensureLoggedIn(): void
    {
        if (!$this->loggedIn) {
            $ok = $this->login();
            if (!$ok) {
                throw new \RuntimeException('Login na Odisseias falhou — confirma utilizador/palavra-passe em ODISSEIAS_USERNAME/ODISSEIAS_PASSWORD.');
            }
        }
    }

    /**
     * @return array<int, array{voucher: string, reserva: string, cliente: string, data: string, hora: string, estado: string, data_id: string, data_type: string}>
     */
    public function fetchBookings(Carbon $from, Carbon $to): array
    {
        $this->ensureLoggedIn();

        $response = $this->http->get('/Appointment/ListAsync', [
            'query' => [
                'Selected_Status' => '',
                'Selected_Unidade_Id' => '',
                'Date_From' => $from->format('d-m-Y'),
                'Date_To' => $to->format('d-m-Y'),
                'Word' => '',
                'X-Requested-With' => 'XMLHttpRequest',
                '_' => (string) now()->valueOf(),
            ],
        ]);

        return $this->parseListHtml((string) $response->getBody());
    }

    /**
     * @return array<int, array{voucher: string, reserva: string, cliente: string, data: string, hora: string, estado: string, data_id: string, data_type: string}>
     */
    private function parseListHtml(string $html): array
    {
        $rows = [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);

        $trs = $dom->getElementsByTagName('tr');
        foreach ($trs as $tr) {
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length < 6) {
                continue;
            }

            $cellText = fn (int $i) => trim($tds->item($i)?->textContent ?? '');

            $voucherCell = $cellText(0);
            $reserva = $cellText(1);
            $cliente = $cellText(2);
            $data = $cellText(3);
            $hora = $cellText(4);
            $estado = $cellText(5);

            if ($reserva === '' || $cliente === '') {
                continue;
            }

            $dataId = null;
            $dataType = null;
            foreach ($tds->item(6)?->getElementsByTagName('button') ?? [] as $btn) {
                $dataId = $btn->getAttribute('data-id') ?: null;
                $dataType = $btn->getAttribute('data-type') ?: null;
            }

            $rows[] = [
                'voucher' => $voucherCell,
                'reserva' => $reserva,
                'cliente' => $cliente,
                'data' => $data,
                'hora' => $hora,
                'estado' => $estado,
                'data_id' => $dataId ?? $reserva,
                'data_type' => $dataType ?? '2',
            ];
        }

        return $rows;
    }

    /**
     * IMPORTANTE (descoberto em 2026-07-03 ao depurar o primeiro --commit real):
     * a resposta de /Appointment/Details NÃO é HTML puro — é um JSON
     * `{"Status":0,"UserState":"<html escapado como string>"}`. O HTML lá
     * dentro tem os campos por `id` de input (Reservation_Number,
     * N_Voucher_Payment, Date — duas vezes: 1ª = data da marcação, 2ª =
     * prazo de cancelamento —, Customer, Phone, Email) e 4 `.form-control-
     * static` por ordem fixa (produto, inclui, válido para, preço NET).
     * Confirmado ao vivo no browser com a sessão real. Extrair por id/ordem
     * é muito mais robusto do que por texto de label — alguns labels
     * (Contacto telefónico/Email) vêm vazios no HTML real, ao contrário do
     * que parecia ao ler a modal já processada pelo browser.
     *
     * @return array{telefone: ?string, email: ?string, produto: ?string, inclui: ?string, prazo_cancelamento: ?string, preco_net: ?float}
     */
    public function fetchDetails(string $dataId, string $dataType): array
    {
        $this->ensureLoggedIn();

        $response = $this->http->post('/Appointment/Details', [
            'form_params' => [
                'id' => $dataId,
                'type' => $dataType,
            ],
        ]);

        $raw = (string) $response->getBody();
        $json = json_decode($raw, true);
        $html = is_array($json) ? ($json['UserState'] ?? '') : $raw;

        return $this->parseDetailsHtml((string) $html);
    }

    private function parseDetailsHtml(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_use_internal_errors(false);

        $inputValue = function (string $id, int $occurrence = 0) use ($dom): ?string {
            $matches = [];
            foreach ($dom->getElementsByTagName('input') as $input) {
                if ($input->getAttribute('id') === $id) {
                    $matches[] = trim($input->getAttribute('value'));
                }
            }
            return $matches[$occurrence] ?? null;
        };

        $statics = [];
        foreach ($dom->getElementsByTagName('div') as $div) {
            $class = $div->getAttribute('class');
            if (str_contains($class, 'form-control-static')) {
                $statics[] = trim(preg_replace('/\s+/', ' ', $div->textContent));
            }
        }

        $phone = $inputValue('Phone');
        $email = $inputValue('Email');
        $deadline = $inputValue('Date', 1); // 2º input Date = prazo de cancelamento
        $product = $statics[0] ?? null;
        $inclui = $statics[1] ?? null;
        $priceRaw = $statics[3] ?? null;
        $price = $priceRaw !== null
            ? (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,\.]/', '', $priceRaw))
            : null;

        return [
            'telefone' => $phone,
            'email' => $email,
            'produto' => $product,
            'inclui' => $inclui,
            'prazo_cancelamento' => $deadline,
            'preco_net' => $price,
        ];
    }
}
