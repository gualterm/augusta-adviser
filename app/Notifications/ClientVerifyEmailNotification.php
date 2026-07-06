<?php
namespace App\Notifications;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
class ClientVerifyEmailNotification extends VerifyEmail {
    protected function verificationUrl($notifiable): string {
        return URL::temporarySignedRoute('portal.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]);
    }
    public function toMail($notifiable): MailMessage {
        return (new MailMessage)
            ->subject('Confirma o teu email — Augusta Adviser')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Obrigado por criares a tua conta na Augusta Adviser.')
            ->line('Clica no botão abaixo para confirmar o teu endereço de email.')
            ->action('Confirmar Email', $this->verificationUrl($notifiable))
            ->line('Este link expira em 60 minutos.')
            ->line('Se não criaste uma conta, ignora este email.')
            ->salutation(new \Illuminate\Support\HtmlString('Com os melhores cumprimentos,<br>Augusta Adviser'));
    }
}