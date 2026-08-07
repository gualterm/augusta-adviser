<?php
namespace App\Filament\Resources\Inquiries\Pages;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Mail\InquiryReplyMail;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;
class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Responder')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->modalHeading('Responder ao Inquérito')
                ->modalSubmitActionLabel('Enviar resposta')
                ->visible(fn () => $this->record->status !== 'respondido')
                ->form([
                    FormTextarea::make('response')
                        ->label('Resposta')
                        ->required()
                        ->rows(10)
                        ->placeholder('Escreva a sua resposta aqui...'),
                ])
                ->action(function (array $data): void {
                    $inquiry = $this->record;
                    Mail::to($inquiry->email)
                        ->send(new InquiryReplyMail($inquiry, $data['response']));
                    $inquiry->update([
                        'response'     => $data['response'],
                        'responded_at' => now(),
                        'status'       => 'respondido',
                    ]);
                    $this->refreshFormData(['status', 'response', 'responded_at']);
                    Notification::make()
                        ->title('Resposta enviada com sucesso!')
                        ->success()
                        ->send();
                }),
        ];
    }
}
