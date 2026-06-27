<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\AppointmentConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class ClientPortalController extends Controller
{
    public function dashboard()
    {
        $client = Auth::guard('client')->user();
        $appointments = Appointment::query()
            ->where('client_id', $client->id)
            ->where('status', '!=', 'cancelled')
            ->where('appointment_date', '>=', now()->format('Y-m-d'))
            ->with(['employee', 'service'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();
        $history = Appointment::query()
            ->where('client_id', $client->id)
            ->where('appointment_date', '<', now()->format('Y-m-d'))
            ->with(['employee', 'service'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(20)
            ->get();
        return view('portal.dashboard', [
            'client'       => $client,
            'appointments' => $appointments,
            'history'      => $history,
        ]);
    }

    public function showBook()
    {
        $services = Service::query()
            ->where('active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
        return view('portal.book', ['services' => $services]);
    }

    /**
     * Dado um serviço, data e hora preferida, devolve o slot disponível mais próximo.
     * Resposta JSON: { slot: "HH:MM"|null, exact: bool }
     */
    public function suggestSlot(Request $request)
    {
        $request->validate([
            'service_id'     => ['required', 'exists:services,id'],
            'date'           => ['required', 'date', 'after_or_equal:today'],
            'preferred_time' => ['required'],
        ]);

        $service  = Service::find($request->service_id);
        $date     = $request->date;
        $duration = $service->duration_minutes;
        $preferred = Carbon::createFromFormat('H:i', substr($request->preferred_time, 0, 5));

        $openHour  = 9;
        $closeTime = '19:30';
        $interval  = 30; // minutos entre slots

        // Candidatos: todos os slots do dia ordenados por distância à hora preferida
        $slots = [];
        $cursor = Carbon::createFromFormat('Y-m-d H:i', "$date 09:00");
        $limit  = Carbon::createFromFormat('Y-m-d H:i', "$date $closeTime");
        $now    = Carbon::now()->addMinutes(30);

        while ($cursor->copy()->addMinutes($duration)->lte($limit)) {
            if ($cursor->gte($now)) {
                $slots[] = $cursor->format('H:i');
            }
            $cursor->addMinutes($interval);
        }

        if (empty($slots)) {
            return response()->json(['slot' => null, 'exact' => false]);
        }

        // Ordena por distância à hora preferida
        usort($slots, function ($a, $b) use ($preferred) {
            $diffA = abs(Carbon::createFromFormat('H:i', $a)->diffInMinutes($preferred, false));
            $diffB = abs(Carbon::createFromFormat('H:i', $b)->diffInMinutes($preferred, false));
            return $diffA <=> $diffB;
        });

        // Encontra employees elegíveis
        $eligibleIds = EmployeeCommission::where('category', $service->category)->pluck('employee_id');
        $employeesQ  = Employee::where('active', true);
        if ($eligibleIds->isNotEmpty()) $employeesQ->whereIn('id', $eligibleIds);
        $employees    = $employeesQ->get();
        $equipmentIds = $service->equipment->pluck('id')->all();

        foreach ($slots as $startTime) {
            $endTime = Carbon::createFromFormat('H:i', $startTime)->addMinutes($duration)->format('H:i');
            foreach ($employees as $employee) {
                if (AppointmentConflictService::employeeHasConflict($employee->id, $date, $startTime, $endTime)) continue;
                if (!empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict($equipmentIds, $date, $startTime, $endTime)) continue;
                $workstation = AppointmentConflictService::findAlternativeWorkstation($service->workstation_type, $date, $startTime, $endTime);
                if (!$workstation) continue;

                $exact = ($startTime === $preferred->format('H:i'));
                return response()->json(['slot' => $startTime, 'exact' => $exact]);
            }
        }

        return response()->json(['slot' => null, 'exact' => false]);
    }

    /**
     * Devolve todos os slots disponíveis para um serviço numa data.
     * Resposta JSON: ["09:00","09:30",...]
     */
    public function availableSlots(Request $request)
    {
        $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'date'       => ['required', 'date', 'after_or_equal:today'],
        ]);

        $service  = Service::find($request->service_id);
        $date     = $request->date;
        $duration = $service->duration_minutes;
        $closeTime = '19:30';
        $interval  = 30;
        $now = Carbon::now()->addMinutes(30);

        $eligibleIds = EmployeeCommission::where('category', $service->category)->pluck('employee_id');
        $employeesQ  = Employee::where('active', true);
        if ($eligibleIds->isNotEmpty()) $employeesQ->whereIn('id', $eligibleIds);
        $employees    = $employeesQ->get();
        $equipmentIds = $service->equipment->pluck('id')->all();

        $available = [];
        $cursor = Carbon::createFromFormat('Y-m-d H:i', "$date 09:00");
        $limit  = Carbon::createFromFormat('Y-m-d H:i', "$date $closeTime");

        while ($cursor->copy()->addMinutes($duration)->lte($limit)) {
            if ($cursor->gte($now)) {
                $startTime = $cursor->format('H:i');
                $endTime   = $cursor->copy()->addMinutes($duration)->format('H:i');
                foreach ($employees as $employee) {
                    if (AppointmentConflictService::employeeHasConflict($employee->id, $date, $startTime, $endTime)) continue;
                    if (!empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict($equipmentIds, $date, $startTime, $endTime)) continue;
                    $workstation = AppointmentConflictService::findAlternativeWorkstation($service->workstation_type, $date, $startTime, $endTime);
                    if (!$workstation) continue;
                    $available[] = $startTime;
                    break;
                }
            }
            $cursor->addMinutes($interval);
        }

        return response()->json($available);
    }

    public function book(Request $request)
    {
        $data = $request->validate([
            'service_id'       => ['required', 'exists:services,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required'],
        ]);
        $client    = Auth::guard('client')->user();
        $service   = Service::find($data['service_id']);
        $startTime = Carbon::parse($data['appointment_time'])->format('H:i');
        $endTime   = Carbon::parse($startTime)->addMinutes($service->duration_minutes)->format('H:i');

        $eligibleIds = EmployeeCommission::where('category', $service->category)->pluck('employee_id');
        $employeesQ  = Employee::where('active', true);
        if ($eligibleIds->isNotEmpty()) $employeesQ->whereIn('id', $eligibleIds);
        $employees    = $employeesQ->orderBy('name')->get();
        $equipmentIds = $service->equipment->pluck('id')->all();

        foreach ($employees as $employee) {
            if (AppointmentConflictService::employeeHasConflict($employee->id, $data['appointment_date'], $startTime, $endTime)) continue;
            if (!empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict($equipmentIds, $data['appointment_date'], $startTime, $endTime)) continue;
            $workstation = AppointmentConflictService::findAlternativeWorkstation($service->workstation_type, $data['appointment_date'], $startTime, $endTime);
            if (!$workstation) continue;

            Appointment::create([
                'client_id'        => $client->id,
                'employee_id'      => $employee->id,
                'workstation_id'   => $workstation->id,
                'service_id'       => $service->id,
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $startTime,
                'end_time'         => $endTime,
                'status'           => 'scheduled',
                'price'            => $service->price,
            ]);
            return redirect()->route('portal.dashboard')->with('booking_success', true);
        }
        return back()
            ->withErrors(['appointment_time' => 'Não há disponibilidade nesse horário. Por favor escolhe outra data ou hora.'])
            ->withInput();
    }
}
