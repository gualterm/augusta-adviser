<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagamentosDiariosController extends Controller
{
    public function print(Request $request)
    {
        abort_unless(Auth::user()?->role === 'admin', 403);

        $date      = $request->get('date', today()->toDateString());
        $employees = Employee::where('active', true)->with('commissions')->orderBy('name')->get();
        $result    = [];

        foreach ($employees as $emp) {
            $appts = Appointment::where('appointment_date', $date)
                ->where('employee_id', $emp->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->with(['service', 'client'])
                ->orderBy('appointment_time')
                ->get();

            if ($appts->isEmpty()) continue;

            $totalValue = $totalCommission = 0;
            $breakdown  = [];

            foreach ($appts as $appt) {
                $category   = $appt->service->category ?? null;
                $pct        = $emp->commissionPercentageFor($category);
                $commission = round((float) $appt->price * $pct / 100, 2);
                $totalValue      += (float) $appt->price;
                $totalCommission += $commission;
                $breakdown[] = [
                    'time'       => $appt->appointment_time,
                    'client'     => $appt->client->name ?? '—',
                    'service'    => $appt->service->name ?? 'desconhecido',
                    'price'      => (float) $appt->price,
                    'pct'        => $pct,
                    'commission' => $commission,
                ];
            }

            $result[] = [
                'name'             => $emp->name,
                'count'            => $appts->count(),
                'total_value'      => $totalValue,
                'total_commission' => $totalCommission,
                'breakdown'        => $breakdown,
            ];
        }

        $grandTotal      = array_sum(array_column($result, 'total_value'));
        $grandCommission = array_sum(array_column($result, 'total_commission'));
        $formattedDate   = Carbon::parse($date)->translatedFormat('l, d \de F \de Y');

        return view('pagamentos-diarios-print', compact(
            'result', 'date', 'formattedDate', 'grandTotal', 'grandCommission'
        ));
    }
}