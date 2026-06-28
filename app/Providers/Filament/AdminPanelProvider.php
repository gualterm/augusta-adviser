<?php
namespace App\Providers\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\AugustaDashboardStats;
use App\Filament\Widgets\TodayAppointmentsWidget;
use App\Filament\Widgets\WeeklyByProfessionalWidget;
use App\Filament\Pages\Analytics;
use App\Filament\Widgets\AppointmentsByMonthChart;
use App\Filament\Widgets\ProfessionalHistoryChart;
use App\Filament\Widgets\RevenueByMonthChart;
use App\Filament\Widgets\RevenueByWeekChart;
use App\Filament\Widgets\ServiceBreakdownChart;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LoginResponse::class,
            \App\Filament\Auth\Responses\LoginResponse::class,
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName('Augusta Adviser')
            ->brandLogo(asset('images/logoaugusta-1a.png'))
            ->brandLogoHeight('7rem')
            ->colors(['primary' => Color::Amber])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Analytics::class, Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([AccountWidget::class, AugustaDashboardStats::class, TodayAppointmentsWidget::class, WeeklyByProfessionalWidget::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => '<style>
/* ---- chart hover zoom ---- */
.fi-wi-chart {
    transition: transform 0.25s cubic-bezier(.25,.8,.25,1),
                box-shadow 0.25s cubic-bezier(.25,.8,.25,1);
    border-radius: 12px;
}
.fi-wi-chart:hover {
    transform: scale(1.04);
    box-shadow: 0 16px 48px rgba(0,0,0,0.18);
    z-index: 20;
    position: relative;
}
/* dashboard grid: forçar 2 colunas nos charts */
.fi-dashboard-widgets-container {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
}
</style>'
            )
            ->authMiddleware([Authenticate::class])
            // Barra ambiente (dinâmica por APP_URL)
            ->renderHook(
                'panels::body.start',
                function () {
                    $isProd = str_contains(config('app.url'), 'augustaadviser.pt');
                    $text = $isProd ? '✦ PRODUÇÃO ✦' : '✦ FORMAÇÃO ✦';
                    $color = $isProd ? '#2d6a4f' : '#9b1c1c';
                    return new \Illuminate\Support\HtmlString(
                        '<div style="background:' . $color . ';color:#fff;text-align:center;padding:5px 0;font-size:12px;font-weight:600;letter-spacing:2px;text-transform:uppercase;opacity:0.85;">' . $text . '</div>'
                    );
                }
            )
            // Nome do utilizador ao lado do logo
            ->renderHook(
                'panels::sidebar.header',
                fn () => auth()->check()
                    ? new \Illuminate\Support\HtmlString(
                        '<div style="
                            display:flex;align-items:center;gap:8px;
                            padding:6px 16px 10px;
                            border-bottom:1px solid rgba(0,0,0,0.07);
                            margin-bottom:4px;
                        ">
                            <div style="
                                width:32px;height:32px;border-radius:50%;
                                background:#f59e0b;color:#fff;
                                display:flex;align-items:center;justify-content:center;
                                font-size:14px;font-weight:700;flex-shrink:0;
                            ">' . e(mb_strtoupper(mb_substr(auth()->user()->name, 0, 1))) . '</div>
                            <div style="min-width:0;">
                                <div style="font-size:13px;font-weight:600;color:#1f2937;
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                                    . e(auth()->user()->name) .
                                '</div>
                                <div style="font-size:11px;color:#6b7280;">'
                                    . e(auth()->user()->getRoleLabelAttribute()) .
                                '</div>
                            </div>
                        </div>'
                    )
                    : ''
            );
    }
}