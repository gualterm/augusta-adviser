<?php

namespace App\Filament\Pages;

use App\Services\ZappyImportService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

/**
 * Importação manual do export do Zappy, feita diretamente pela Marta no
 * backoffice — sem terminal, sem depender do Gualter.
 *
 * Porque existe: o Zappy vai ser abandonado no go-live, mas até lá a Marta
 * continua a registar aí as marcações diretas (não-Odisseias), porque não
 * temos acesso automático ao Zappy (login com 2FA, sem a password). A
 * Odisseias já entra sozinha via App\Services\OdisseiasClient — esta página
 * cobre a outra metade: o que só existe no Zappy.
 *
 * Fluxo: 1) upload do ficheiro "reports" exportado do Zappy, 2) "Analisar
 * ficheiro" mostra uma pré-visualização (nada é gravado), 3) "Confirmar
 * importação" grava as marcações novas. A deduplicação é por
 * cliente+data+hora contra QUALQUER marcação já existente — por isso é
 * seguro correr isto repetidamente com exports cada vez maiores: o que já
 * está na agenda (incluindo o que veio da Odisseias) nunca duplica.
 *
 * Ver App\Services\ZappyImportService para a lógica de parsing/matching.
 */
class ImportZappy extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.import-zappy';

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin', 'recepcionista'], true);
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationLabel(): string
    {
        return 'Importar Zappy';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Operações';
    }

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Importar Marcações do Zappy';
    }

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $file = null;

    public ?array $previewRows = null;

    public ?array $summary = null;

    /** Caminho absoluto do ficheiro já validado, pronto para o commit. */
    public ?string $analyzedPath = null;

    public function updatedFile(): void
    {
        // novo ficheiro escolhido — limpa qualquer pré-visualização anterior
        $this->previewRows = null;
        $this->summary = null;
        $this->analyzedPath = null;
    }

    public function analisar(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [], ['file' => 'ficheiro']);

        $path = $this->file->getRealPath();

        try {
            $result = app(ZappyImportService::class)->preview($path);
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Não foi possível analisar o ficheiro')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            return;
        }

        $this->previewRows = $result['rows'];
        $this->summary = $result['summary'];
        // guarda uma cópia estável do ficheiro para o passo de confirmação
        $stored = $this->file->store('zappy-imports');
        $this->analyzedPath = storage_path('app/private/' . $stored);
        if (!is_file($this->analyzedPath)) {
            $this->analyzedPath = storage_path('app/' . $stored);
        }

        Notification::make()
            ->success()
            ->title('Ficheiro analisado')
            ->body("Novas: {$this->summary['novas']} · Já existentes: {$this->summary['duplicadas']} · Com problemas: " . ($this->summary['sem_servico'] + $this->summary['sem_profissional'] + $this->summary['sem_posto'] + $this->summary['erros']))
            ->persistent()
            ->send();
    }

    public function confirmarImportacao(): void
    {
        if (!$this->analyzedPath || !is_file($this->analyzedPath)) {
            Notification::make()
                ->danger()
                ->title('Analisa o ficheiro primeiro')
                ->body('Carrega novamente o ficheiro e clica em "Analisar ficheiro" antes de confirmar.')
                ->persistent()
                ->send();
            return;
        }

        try {
            $result = app(ZappyImportService::class)->commit($this->analyzedPath);
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Erro ao importar')
                ->body($e->getMessage())
                ->persistent()
                ->send();
            return;
        }

        $this->previewRows = $result['rows'];
        $this->summary = $result['summary'];

        Notification::make()
            ->success()
            ->title('Importação concluída')
            ->body("Marcações criadas: {$this->summary['novas']} · Clientes novos criados: {$this->summary['clientes_novos']} · Já existentes (ignoradas): {$this->summary['duplicadas']}")
            ->persistent()
            ->send();

        // limpa o estado para não permitir gravar duas vezes o mesmo ficheiro sem querer
        $this->file = null;
        $this->analyzedPath = null;
    }
}
