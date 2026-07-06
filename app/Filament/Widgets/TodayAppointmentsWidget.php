<?php
namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Employee;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TodayAppointmentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Marcações de Hoje';

    private static ?array $colorMap = null;

    private static function buildColorMap(): array
    {
        if (self::$colorMap !== null) return self::$colorMap;
        $colors = ['success', 'info', 'danger', 'warning', 'primary', 'gray'];
        $icons  = [
            'heroicon-m-hand-raised',
            'heroicon-m-sparkles',
            'heroicon-m-star',
            'heroicon-m-scissors',
            'heroicon-m-heart',
            'heroicon-m-face-smile',
        ];
        $ids = Employee::where('active', true)->orderBy('id')->pluck('id')->toArray();
        self::$colorMap = [];
        foreach ($ids as $i => $id) {
            self::$colorMap[$id] = [
                'color' => $colors[$i % count($colors)],
                'icon'  => $icons[$i % count($icons)],
            ];
        }
        return self::$colorMap;
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $role = $user?->role ?? 'profissional';

        $query = Appointment::query()
            ->where('appointment_date', Carbon::today()->toDateString())
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->orderBy('appointment_time')
            ->with(['client', 'employee', 'service']);

        // Profissional: só as suas marcações + Marta (id=2)
        if ($role === 'profissional') {
            $employee = Employee::where('user_id', $user->id)->first();
            $empIds   = [2];
            if ($employee && $employee->id !== 2) {
                $empIds[] = $employee->id;
            }
            $query->where(fn ($q) => $q->whereIn('employee_id', $empIds)
                                       ->orWhereIn('secondary_employee_id', $empIds));
        }

        $showPrice = ($role === 'admin' || $role === 'rececionista');

        $columns = [
            Tables\Columns\TextColumn::make('appointment_time')
                ->label('Hora')
                ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('H:i'))
                ->sortable(),
            Tables\Columns\TextColumn::make('client.name')
                ->label('Cliente')
                ->searchable(),
            Tables\Columns\TextColumn::make('service.name')
                ->label('Serviço')
                ->searchable(),
            Tables\Columns\TextColumn::make('employee.name')
                ->label('Profissional')
                ->badge()
                ->color(fn ($record) => (self::buildColorMap()[$record->employee_id ?? 0] ?? ['color' => 'gray'])['color'])
                ->icon(fn ($record)  => (self::buildColorMap()[$record->employee_id ?? 0] ?? ['icon' => 'heroicon-m-user'])['icon']),
        ];

        if ($showPrice) {
            $columns[] = Tables\Columns\TextColumn::make('price')
                ->label('Valor')
                ->formatStateUsing(fn ($state) => '€ ' . number_format((float) $state, 2, ',', '.'));
        }

        $columns[] = Tables\Columns\TextColumn::make('status')
            ->label('Estado')
            ->badge()
            ->color(fn ($state) => match ($state) {
                'scheduled' => 'warning',
                'confirmed' => 'info',
                'completed' => 'success',
                'cancelled' => 'danger',
                default     => 'gray',
            })
            ->icon(fn ($state) => match ($state) {
                'scheduled' => 'heroicon-m-clock',
                'confirmed' => 'heroicon-m-check-circle',
                'completed' => 'heroicon-m-check-badge',
                'cancelled' => 'heroicon-m-x-circle',
                default     => 'heroicon-m-question-mark-circle',
            })
            ->formatStateUsing(fn ($state) => match ($state) {
                'scheduled' => 'Agendada',
                'confirmed' => 'Confirmada',
                'completed' => 'Concluída',
                'cancelled' => 'Cancelada',
                default     => $state,
            });

        return $table
            ->query($query)
            ->columns($columns)
            ->emptyStateHeading('Sem marcações para hoje')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}