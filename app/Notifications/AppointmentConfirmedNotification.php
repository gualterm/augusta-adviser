<?php
namespace App\Notifications;

use App\Models\Appointment;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email de confirmação enviado ao cliente sempre que faz uma marcação no
 * portal (App\Http\Controllers\Portal\ClientPortalController::book).
 * Mostra os detalhes da marcação, promoção/desconto (se aplicável) e
 * inclui um ficheiro .ics para adicionar ao calendário.
 */
class AppointmentConfirmedNotification extends Notification
{
    public function __construct(
        protected Appointment $appointment,
        protected ?Promotion  $promotion = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing(['service', 'employee']);
        $date  = Carbon::parse($appointment->appointment_date)->format('d/m/Y');
        $time  = substr($appointment->appointment_time, 0, 5);
        $isPendingLunch = str_contains((string) $appointment->notes, 'horário de almoço');

        $message = (new MailMessage)
            ->subject('Confirmação da tua marcação — Augusta Adviser')
            ->greeting('Olá, ' . $notifiable->name . '!');

        if ($isPendingLunch) {
            $message->line('Recebemos o teu pedido de marcação — como pediste um horário durante o intervalo de almoço, esta marcação fica sujeita a confirmação da clínica. Vamos contactar-te para confirmar ou sugerir outra hora.');
        } else {
            $message->line('A tua marcação ficou confirmada. Aqui ficam os detalhes:');
        }

        $message
            ->line('**Serviço:** ' . ($appointment->service->name ?? '—'))
            ->line('**Data:** ' . $date . ' às ' . $time)
            ->line('**Profissional:** ' . ($appointment->employee->name ?? '—'));

        // Promoção e preço
        if ($this->promotion) {
            $originalPrice   = (float) ($appointment->service->price   ?? 0);
            $discountedPrice = (float) ($appointment->price            ?? $originalPrice);
            $discount        = (int)   $this->promotion->discount_percentage;

            $message
                ->line('**Promoção:** ' . $this->promotion->title . ' — ' . $discount . '% desconto')
                ->line('**Preço:** €' . number_format($discountedPrice, 2, ',', '.')
                    . ' *(preço original: €' . number_format($originalPrice, 2, ',', '.') . ')*');
        } elseif ($appointment->price) {
            $message->line('**Preço:** €' . number_format((float) $appointment->price, 2, ',', '.'));
        }

        $message
            ->line('Em anexo enviamos um ficheiro de calendário para adicionares esta marcação ao Outlook, telemóvel ou computador.')
            ->line('Se precisares de remarcar ou cancelar, podes fazê-lo na tua área de cliente.')
            ->salutation(new \Illuminate\Support\HtmlString('Com os melhores cumprimentos,<br>Augusta Adviser'));

        $message->attachData(
            $this->buildIcs($appointment),
            'marcacao-augusta-adviser.ics',
            ['mime' => 'text/calendar; charset=utf-8; method=PUBLISH']
        );

        return $message;
    }

    private function buildIcs(Appointment $appointment): string
    {
        $start = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time);
        $end   = $appointment->end_time
            ? Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->end_time)
            : $start->copy()->addMinutes($appointment->service->duration_minutes ?? 30);

        $summary     = 'Augusta Adviser — ' . ($appointment->service->name ?? 'Marcação');
        $description = 'Marcação na Augusta Adviser'
            . ($appointment->employee?->name ? ' com ' . $appointment->employee->name : '');

        if ($this->promotion) {
            $description .= ' | Promoção: ' . $this->promotion->title
                . ' (' . (int) $this->promotion->discount_percentage . '% desconto)';
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Augusta Adviser//PT',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:appointment-' . $appointment->id . '@augustaadviser.pt',
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $start->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $end->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:' . $this->escapeIcsText($summary),
            'DESCRIPTION:' . $this->escapeIcsText($description),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escapeIcsText(string $text): string
    {
        return str_replace([',', ';', "\n"], ['\,', '\;', '\n'], $text);
    }
}