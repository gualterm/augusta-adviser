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

### Tabela `areas` — controlo de postos e concorrência

Cada área define dois campos adicionais além do nome:

| Campo | Tipo | Descrição |
|---|---|---|
| `workstation_type` | VARCHAR(50) | Tipo de sala/posto que os serviços desta área ocupam |
| `max_concurrent` | TINYINT | Nº máximo de marcações simultâneas por profissional desta área |

**Mapeamento actual:**

| Área | workstation_type | max_concurrent | Motivo |
|---|---|---|---|
| Estéticista | marquesa | 1 | Tratamentos na marquesa, um cliente de cada vez |
| Massagista | marquesa | 1 | Massagem na marquesa, um cliente de cada vez |
| Manicure | manicure | 2 | Openspace com 2 mesas — a Inês pode ter 2 clientes em simultâneo |
| Recepcionista | receção | 1 | Trabalho administrativo na recepção |

**Porque nas Áreas e não nos profissionais?**
Se `max_concurrent` estivesse no `Employee`, conflituaria com o sistema de 4 mãos (`secondary_employee_id`), onde o profissional está fisicamente ocupado numa sala e não pode atender mais ninguém. Nas Áreas, a regra fica na categoria de trabalho: uma nova manicurista herda `max_concurrent=2` automaticamente.

### `employees.preferred_workstation_id`

Posto preferencial de cada profissional (nullable, não rígido). Se o posto estiver ocupado, `AppointmentConflictService::findAlternativeWorkstation()` sugere alternativa do mesmo tipo.

| Profissional | preferred_workstation_id | Posto |
|---|---|---|
| Marta (id=2) | 3 | Sala Tratamentos 1 |
| Renato (id=10) | 4 | Sala Tratamentos 2 |
| Inês (id=9) | 1 | Posto Manicure 1 (spill para Posto 2) |

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
| `patch_areas_workstations.php` | Adiciona workstation_type/max_concurrent às áreas; atribui salas às marcações |
| `fix_area_resource.php` | Reescreve AreaResource.php com campos de posto no form |

O CSV de marcações (`Marcacoes.csv`) é exportado do Zappy e copiado para `/tmp/` antes de correr o script.

**Atenção:** Zappy guarda preços em cêntimos (2000 = €20,00) — os scripts dividem por 100.

As marcações importadas do Zappy não tinham `workstation_id`. O `patch_areas_workstations.php` atribuiu salas retroactivamente: Marta→Sala 1, Renato→Sala 2, Inês distribuída entre Posto Manicure 1 e 2.

---

## Próximas prioridades

### Pré go-live (fazer antes de abrir ao público)

**1. Email e telefone opcionais na criação de clientes pelo admin** (`#44`)
A recepcionista precisa de criar clientes presenciais sem email. Actualmente o form exige email. Mudança rápida em `ClientForm.php` — remover `->required()` de email e telefone quando criado pelo admin.

**2. Cliente Presencial** (`#39`)
Marcações feitas na loja sem conta online — o cliente existe mas não tem acesso ao portal. Proposta: flag `is_walkin` no cliente ou simplesmente deixar o email em branco (depende do #44 acima).

**3. Script Fase 2 — re-importar clientes Zappy no go-live** (`#47`)
No dia do go-live, exportar CSV final do Zappy e re-importar para garantir que os últimos clientes estão na plataforma. O script `importar_clientes_zappy.php` já existe; confirmar que está actualizado.

**4. Verificar página Disponibilidade do Dia**
Confirmar visualmente que a página agora mostra as salas ocupadas correctamente com as marcações reais (Marta na Sala 1, Renato na Sala 2, Inês nos Postos Manicure).

### Pós go-live (melhorias)

**5. Duplicar registo no Filament** (discutido hoje)
Adicionar `ReplicateAction` às tabelas de Clientes, Profissionais e Utilizadores. Útil para criar novo profissional com horário semelhante.

**6. Integração de horários no portal**
Filtrar slots de marcação online pelo horário da loja (`business_hours`) e pelo horário do profissional (`employee_schedules`). Actualmente o portal não respeita as horas de almoço nem os dias de folga.

**7. Horário de almoço nos `employee_schedules`**
A tabela `employee_schedules` não tem `lunch_start`/`lunch_end` (só `business_hours` tem). Adicionar se os profissionais tiverem pausas diferentes da loja.

**8. Filament v4 → v5** (`#33`)
Actualização de framework — deixar para depois do go-live estar estável.

---

## Versão 2 — roadmap pós go-live

Funcionalidades identificadas para aproximar o painel do Filament ao Zappy (sem faturação).
Ordenadas do mais simples para o mais complexo.

### Rápido (1–2 dias cada)

**Colunas de stats na tabela de Clientes**
Inspirado no Zappy. Adicionar ao `ClientsTable.php`:
- "Última Marcação" — dias desde o último appointment (computed via `withCount`/subquery)
- "Próxima Marcação" — dias até à próxima marcação futura
- "Segmento" — label automática: Activo (< 90 dias), Em risco (90–180), Inactivo (> 180)

**Duplicar registo no Filament**
`ReplicateAction` nas tabelas de Clientes, Profissionais e Utilizadores. Útil para criar novo profissional com configuração semelhante.

### Médio (3–5 dias cada)

**Relatórios tabulares com filtros e exportação**
Páginas `Filament\Pages\Page` para substituir/complementar os gráficos actuais:
- Relatório de Marcações (filtros: período, profissional, serviço, estado; exportar CSV)
- Relatório de Comissões (por profissional e período, baseado em `employee_commissions`)
- Relatório de Recebimentos (marcações pagas vs pendentes)

**Packs de sessões & Vouchers**
Tabelas `packs` e `client_packs`. O cliente compra X sessões de um serviço a preço reduzido; o sistema desconta automaticamente a cada marcação. Estrutura simples mas requer lógica no portal e no admin.

### Maior esforço (1–2 semanas)

**Mensagens de Aniversário**
Job agendado que corre diariamente, detecta clientes com aniversário nos próximos N dias e envia email personalizado. Depende de integração com serviço de email (já temos Laravel Mail) e de ter data de nascimento no perfil do cliente.

**Lembretes automáticos de marcação**
Email/SMS automático X horas antes da marcação. Requer integração SMS (ex: Twilio, ClickSend) para o canal SMS; email já funciona. Alto valor para reduzir no-shows.

**Campanhas**
Envio de email/SMS para segmentos de clientes (ex: "clientes inactivos há 3 meses"). Depende das colunas de segmento (rápido acima) e de integração de envio em massa.

---

## Notas técnicas

- PHP 8.4: usar `mb_strtolower($str, 'UTF-8')` para strings com caracteres portugueses
- PHP 8.4: `fgetcsv()` requer parâmetro `escape` explícito: `fgetcsv($h, 0, ',', '"', '\\')`
- MariaDB nos containers: usar `mariadb` binary no container `augusta-db`, não no `augusta-php`
- Cache do NAS: ao actualizar scripts PHP, usar sempre nome de ficheiro novo (v2, v3…)
- Filament v4: propriedade `$navigationGroup` deve ser `UnitEnum|string|null` (não `?string`)

### AppointmentConflictService — como funciona o controlo de concorrência

`employeeHasConflict()` conta marcações sobrepostas (em vez de apenas verificar se existe alguma) e compara com `max_concurrent` das Áreas do profissional:

```php
$maxConcurrent = DB::table('employee_area')
    ->join('areas', 'areas.id', '=', 'employee_area.area_id')
    ->where('employee_area.employee_id', $employeeId)
    ->max('areas.max_concurrent') ?? 1;

return $count >= $maxConcurrent; // true = conflito
```

Para a Inês (Manicure, max_concurrent=2): só retorna conflito quando já existem 2 ou mais marcações sobrepostas — permite a segunda mesa em openspace. Para todos os outros (max_concurrent=1): qualquer sobreposição é conflito. Serviços a 4 mãos (`secondary_employee_id`) são contabilizados pela mesma query — o profissional fica completamente bloqueado durante esse serviço.
