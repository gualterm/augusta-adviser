<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;

class QrCodes extends Page
{
    public static function canAccess(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role === 'admin';
    }

    protected string $view = 'filament.pages.qr-codes';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-qr-code';
    }

    public static function getNavigationLabel(): string
    {
        return 'QR Codes & Links';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Administração';
    }

    public static function getNavigationSort(): ?int
    {
        return 99;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'QR Codes & Links';
    }
}
