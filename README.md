# Augusta Adviser — Documentação Técnica

Plataforma de gestão para o centro de beleza Augusta Adviser, Vila do Conde.

---

## Stack

| Componente | Tecnologia |
|---|---|
| Framework | Laravel 13 / PHP 8.4 |
| Painel admin | Filament v4 |
| Base de dados | MariaDB |
| Infraestrutura | Docker (ASUSTOR NAS) |
| Frontend website | Blade + CSS vanilla |

---

## Ambientes

| Ambiente | Container PHP | Container BD | URL |
|---|---|---|---|
| PROD | `augusta-php` | `augusta-db` | augustaadviser.pt |
| DEV | `augusta-dev-php` | `augusta-dev-db` | augustadev.macedo |

**BD PROD:** `augusta_platform`  
**BD DEV:** `augusta_dev`  
**Credenciais BD:** user `augusta_app`, password `App-Augusta@2026`

> ⚠️ **NUNCA tocar** nos containers `tradutora-db` ou `cf-dev-db`.

---

## Fluxo de deploy

```bash
# 1. Commitar no PROD
sudo docker exec augusta-php bash -c "cd /var/www/html/laravel && git add -A && git commit -m 'mensagem' && git push"

# 2. Sincronizar DEV
sudo docker exec augusta-dev-php bash -c "cd /var/www/html/laravel && git pull"

# 3. Correr migrações em DEV (se houver)
sudo docker exec augusta-dev-php php /var/www/html/laravel/artisan migrate
```

Para scripts PHP avulsos (migrations manuais, imports):
```bash
sudo docker cp script.php augusta-php:/tmp/
sudo docker exec augusta-php php /tmp/script.php
```

---

## Estrutura de utilizadores e roles

| Role | Acesso |
|---|---|
| `admin` | Painel Filament completo |
| `profissional` | Painel Filament (vista limitada) |
| `rececionista` | Painel Filament (vista limitada) |
| *(sem role)* | Apenas portal de clientes |

Utilizadores reais em PROD: **Marta**, **Renato**, **Inglês** — nunca apagar nem sobrescrever.

---

## Base de dados — tabelas principais

| Tabela | Descrição |
|---|---|
| `users` | Contas de login (staff + admin) |
| `clients` | Clientes do centro (portal próprio) |
| `employees` | Profissionais (ligados a `users`) |
| `employee_schedules` | Horário de trabalho por profissional e dia |
| `appointments` | Marcações |
| `services` | Serviços disponíveis |
| `workstations` | Salas e postos (marquesas, manicure) |
| `equipment` | Equipamentos partilhados |
| `areas` | Áreas de atuação |
| `business_hours` | Horário de funcionamento da loja (por dia da semana) |
| `promotions` | Promoções activas |
| `inquiries` | Formulário de contacto do website |
| `employee_commissions` | Comissões por categoria de serviço |

### Coluna `clients.gender`
`ENUM('feminino', 'masculino') NULL` — opcional, filtrável no Filament.

### Tabela `business_hours`
7 linhas fixas (uma por dia da semana, 0=Dom … 6=Sab).  
Editável em **Configurações → Horário da Loja** no painel admin.  
Apresentado dinamicamente no footer do website.

### Tabela `employee_schedules`
Horário de trabalho por profissional. Gerido na ficha de cada profissional (secção "Horário de Trabalho").

---

## Painel Admin (Filament)

URL: `/admin`

| Secção | Recurso |
|---|---|
| Clientes | CRUD + campo género + filtros |
| Profissionais | CRUD + horário + comissões |
| Serviços | CRUD + equipamentos + 4 mãos |
| Marcações | CRUD + agenda + disponibilidade |
| Promoções | CRUD |
| Equipamentos | CRUD |
| Postos/Salas | CRUD (Workstations) |
| Utilizadores | CRUD + roles |
| Áreas | CRUD |
| Inquéritos | Listagem |
| Permissões | Gestão de permissões por role |
| **Configurações → Horário da Loja** | 7 dias editáveis |
| **Operações → Disponibilidade** | Vista de ocupação do dia |
| **Operações → Calendário Semanal** | Vista semanal |
| **Operações → Agenda** | Agenda geral |
| **Análise & Histórico** | Gráficos de receita e marcações |

---

## Portal de Clientes

URL: `/portal`

- Registo com verificação de email obrigatória
- Login próprio (independente do staff)
- Dashboard com marcações futuras e historial
- Marcação online de serviços
- Remarcação (até 1 dia antes)
- Cancelamento (até 1 dia antes)
- Página de promoções

---

## Website público

URL: `/`

- Apresentação de serviços
- Galeria
- Formulário de contacto (guarda em `inquiries`)
- Footer com horário dinâmico (lido da tabela `business_hours`)

---

## Importação Zappy

Scripts em `/tmp/` nos containers:

| Script | Função |
|---|---|
| `importar_clientes_zappy.php` | Importa clientes do CSV Zappy |
| `importar_marcacoes_v2.php` | Importa marcações do CSV Zappy |
| `fix_sofia_viana.php` | Insere marcações de cliente específico |
| `install_horarios_gender.php` | Instala horários + campo género |

O CSV de marcações (`Marcacoes.csv`) é exportado do Zappy e copiado para `/tmp/` antes de correr o script.

**Atenção:** Zappy guarda preços em cêntimos (2000 = €20,00) — os scripts dividem por 100.

---

## Funcionalidades pendentes (pós go-live)

- [ ] Filament v4 → v5
- [ ] Sistema de vouchers / packs de sessões
- [ ] Cliente presencial (marcação sem conta online)
- [ ] Script Fase 2 — re-importar clientes Zappy no dia do go-live
- [ ] Email e telefone opcionais na criação de clientes pelo admin
- [ ] Integração de horários no portal (filtrar slots por horário da loja + profissional)

---

## Notas técnicas

- PHP 8.4: usar `mb_strtolower($str, 'UTF-8')` para strings com caracteres portugueses
- PHP 8.4: `fgetcsv()` requer parâmetro `escape` explícito: `fgetcsv($h, 0, ',', '"', '\\')`
- MariaDB nos containers: usar `mariadb` binary no container `augusta-db`, não no `augusta-php`
- Cache do NAS: ao actualizar scripts PHP, usar sempre nome de ficheiro novo (v2, v3…)
- Filament v4: propriedade `$navigationGroup` deve ser `UnitEnum|string|null` (não `?string`)
