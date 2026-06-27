<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recuperação de Password — Augusta Adviser')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Recebemos um pedido para recuperar a password da tua conta Augusta Adviser.')
            ->action('Recuperar Password', $this->url)
            ->line('Este link expira em 60 minutos.')
            ->line('Se não fizeste este pedido, podes ignorar este email.')
            ->salutation('Augusta Adviser');
    }
}
