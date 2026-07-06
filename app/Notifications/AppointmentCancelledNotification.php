<?php

namespace App\Notifications;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email enviado ao cliente quando a Marta recusa/cancela um pedido de
 * marcação (ex.: pedido de horário de almoço não aceite) através do botão
 * "Recusar" na Agenda. Inclui o motivo escrito pela Marta e um link direto
 * para o cliente escolher outra hora.
 *
 * Pedido do Gualter (2026-07-06): antes disto, o cliente não era avisado
 * nenhuma forma quando a marcação era cancelada pela clínica.
 */
class AppointmentCancelledNotification extends Notification
{
    public function __construct(protected Appointment $appointment, protected string $reason)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appointment = $this->appointment->loadMissing('service');
        $date = Carbon::parse($appointment->appointment_date)->format('d/m/Y');
        $time = substr($appointment->appointment_time, 0, 5);

        return (new MailMessage)
            ->subject('A tua marcação foi cancelada — Augusta Adviser')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('A tua marcação não pôde ser confirmada e foi cancelada pela clínica:')
            ->line('**Serviço:** ' . ($appointment->service->name ?? '—'))
            ->line('**Data:** ' . $date . ' às ' . $time)
            ->line('**Motivo:** ' . $this->reason)
            ->line('Pedimos desculpa pelo incómodo. Podes escolher outra data e hora diretamente na tua área de cliente.')
            ->action('Marcar Nova Consulta', route('portal.book'))
            ->salutation(new \Illuminate\Support\HtmlString('Com os melhores cumprimentos,<br>Augusta Adviser'));
    }
}
