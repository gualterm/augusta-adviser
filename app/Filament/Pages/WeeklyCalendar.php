<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class WeeklyCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Calendário Semanal';

    protected static ?string $title = 'Calendário Semanal';

    protected static string|UnitEnum|null $navigationGroup = 'Operações';

    protected string $view = 'filament.pages.weekly-calendar';

    public ?string $weekStart = null;

    protected const DAY_COLORS = [
        '#cf6b6b',
        '#6bb0a8',
        '#c4a13f',
        '#7c8fd1',
        '#a86bc4',
        '#5fa86b',
        '#c4824a',
    ];

    public function mount(): void
    {
        $this->weekStart = $this->weekStart
            ?: Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->subWeek()
            ->format('Y-m-d');
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->addWeek()
            ->format('Y-m-d');
    }

    public function thisWeek(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
    }

    public function getWeekRangeLabel(): string
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->addDays(6);

        return $start->format('d/m/Y') . ' — ' . $end->format('d/m/Y');
    }

    public function getDaysWithAppointments(): Collection
    {
        $start = Carbon::parse($this->weekStart);

        return collect(range(0, 6))->map(function (int $offset) use ($start) {
            $day = $start->copy()->addDays($offset);
            $dateString = $day->format('Y-m-d');

            $appointments = Appointment::query()
                ->where('appointment_date', $dateString)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('appointment_time')
                ->whereNotNull('end_time')
                ->with(['client', 'employee', 'service', 'workstation'])
                ->orderBy('appointment_time')
                ->get()
                ->map(function (Appointment $appointment) {
                    $appointment->setAttribute(
                        'editUrl',
                        AppointmentResource::getUrl('edit', ['record' => $appointment])
                    );

                    return $appointment;
                });

            return (object) [
                'date' => $day,
                'dateString' => $dateString,
                'isToday' => $day->isToday(),
                'label' => ucfirst($day->translatedFormat('D, d/m')),
                'color' => self::DAY_COLORS[$offset % count(self::DAY_COLORS)],
                'appointments' => $appointments,
                'createUrl' => AppointmentResource::getUrl('create') . '?' . http_build_query([
                    'appointment_date' => $dateString,
                ]),
            ];
        });
    }
}
