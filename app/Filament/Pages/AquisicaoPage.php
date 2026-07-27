<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AquisicaoPage extends Page
{
    protected string $view = 'filament.pages.aquisicao';

    public static function getNavigationIcon(): string { return 'heroicon-o-funnel'; }

    public static function getNavigationLabel(): string { return 'Análise de Aquisição'; }
    public function getTitle(): string { return 'Análise de Aquisição'; }
    public static function getNavigationGroup(): ?string { return 'Sistema'; }
    public static function getNavigationSort(): ?int    { return 91; }

    public array  $stats       = [];
    public array  $topClientes = [];
    public string $periodo     = 'all';

    public function mount(): void { $this->loadStats(); }

    public function setPeriodo(string $p): void
    {
        $this->periodo = $p;
        $this->loadStats();
    }

    private function loadStats(): void
    {
        $dateFilter = match ($this->periodo) {
            'today' => now()->startOfDay(),
            'week'  => now()->startOfWeek(\Carbon\Carbon::MONDAY),
            'month' => now()->startOfMonth(),
            default => null,
        };

        $query = DB::table('clients as c')
            ->leftJoin('appointments as a', 'a.client_id', '=', 'c.id')
            ->select(
                DB::raw("COALESCE(c.referral_source, 'desconhecido') as canal"),
                DB::raw('COUNT(DISTINCT c.id) as total_clientes'),
                DB::raw('COUNT(a.id) as total_marcacoes'),
                DB::raw("SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as canceladas"),
                DB::raw("SUM(CASE WHEN a.status IN ('scheduled','confirmed','completed') THEN 1 ELSE 0 END) as activas"),
                DB::raw("SUM(CASE WHEN a.status IN ('scheduled','confirmed','completed') AND a.price IS NOT NULL THEN a.price ELSE 0 END) as valor_total"),
            )
            ->groupBy('canal')
            ->orderByDesc('total_marcacoes');

        if ($dateFilter) $query->where('c.created_at', '>=', $dateFilter);

        $labels = ['facebook'=>'Facebook','instagram'=>'Instagram','odisseias'=>'Odisseias','outro'=>'Outro','desconhecido'=>'(sem registo)'];
        $icons  = ['Facebook'=>'📘','Instagram'=>'📸','Odisseias'=>'🏖️','Outro'=>'🔍','(sem registo)'=>'❓'];

        $this->stats = $query->get()->map(function ($row) use ($labels, $icons) {
            $row->canal_label = $labels[$row->canal] ?? ucfirst($row->canal);
            $row->canal_icon  = $icons[$row->canal_label] ?? '🔹';
            $row->valor_total = '€ ' . number_format((float)$row->valor_total, 2, ',', '.');
            return $row;
        })->toArray();

        $topQ = DB::table('clients as c')
            ->leftJoin('appointments as a', 'a.client_id', '=', 'c.id')
            ->select(
                'c.id', 'c.name',
                DB::raw("COALESCE(c.referral_source, '—') as canal"),
                DB::raw('COUNT(a.id) as total_marcacoes'),
                DB::raw("SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as canceladas"),
                DB::raw('MIN(a.appointment_date) as primeira_marcacao'),
                DB::raw('MAX(a.appointment_date) as ultima_marcacao'),
            )
            ->groupBy('c.id', 'c.name', 'c.referral_source')
            ->orderByDesc('total_marcacoes')
            ->limit(15);

        if ($dateFilter) $topQ->where('c.created_at', '>=', $dateFilter);

        $this->topClientes = $topQ->get()->toArray();
    }
}