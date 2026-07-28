<?php
namespace App\Filament\Pages;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
class RelatorioFaturacao extends Page
{
    protected string $view = 'filament.pages.relatorio-faturacao';
    protected static ?string $navigationLabel = 'Relatório Faturação';
    protected static bool $shouldRegisterNavigation = false;
    public string $tipo   = 'mes';
    public string $mes    = '';
    public string $origem = 'todas';
    protected $queryString = ['tipo', 'mes', 'origem'];
    public function mount(): void
    {
        abort_unless(Auth::user()?->role === 'admin', 403);
        if (empty($this->mes)) {
            $this->mes = Carbon::now()->format('Y-m');
        }
        // compatibilidade com links antigos do dashboard
        if ($this->tipo === 'odisseias') {
            $this->tipo   = 'mes';
            $this->origem = 'Odisseias';
        } elseif ($this->tipo === 'direta') {
            $this->tipo   = 'mes';
            $this->origem = 'Direto';
        }
    }
    public function getMeses(): array
    {
        $meses = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $meses[$m->format('Y-m')] = ucfirst($m->translatedFormat('F Y'));
        }
        return $meses;
    }
    public function getTitulo(): string
    {
        $origemLabel = match($this->origem) {
            'Odisseias' => ' · Odisseias',
            'Direto'    => ' · Direta',
            default     => '',
        };
        if ($this->tipo === 'hoje') {
            return 'Faturação de Hoje' . $origemLabel . ' — ' . Carbon::today()->translatedFormat('l, d \de F \de Y');
        }
        if ($this->tipo === 'semana') {
            return 'Faturação Semanal' . $origemLabel . ' — '
                . Carbon::now()->startOfWeek()->format('d/m') . ' a '
                . Carbon::now()->endOfWeek()->format('d/m');
        }
        $label = ucfirst(Carbon::createFromFormat('Y-m', $this->mes)->translatedFormat('F Y'));
        return 'Faturação' . $origemLabel . ' — ' . $label;
    }
    public function getData(): array
    {
        $paid  = ['confirmed', 'completed'];
        $query = Appointment::with(['client', 'employee', 'service'])
            ->whereIn('status', $paid)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time');
        if ($this->tipo === 'hoje') {
            $query->where('appointment_date', Carbon::today()->toDateString());
        } elseif ($this->tipo === 'semana') {
            $query->whereBetween('appointment_date', [
                Carbon::now()->startOfWeek()->toDateString(),
                Carbon::now()->endOfWeek()->toDateString(),
            ]);
        } else {
            $date = Carbon::createFromFormat('Y-m', $this->mes);
            $query->whereBetween('appointment_date', [
                $date->copy()->startOfMonth()->toDateString(),
                $date->copy()->endOfMonth()->toDateString(),
            ]);
        }
        if ($this->origem === 'Odisseias') {
            $query->where('source', 'Odisseias');
        } elseif ($this->origem === 'Direto') {
            $query->where('source', '<>', 'Odisseias');
        }
        $appointments = $query->get();
        return [
            'appointments' => $appointments,
            'total'        => (float) $appointments->sum('price'),
            'count'        => $appointments->count(),
        ];
    }
}
