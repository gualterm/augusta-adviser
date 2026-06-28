<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Promotion;
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
        $promotions = Promotion::active()->orderBy('valid_to')->get();

        return view('portal.dashboard', [
            'client'       => $client,
            'appointments' => $appointments,
            'history'      => $history,
            'promotions'   => $promotions,
        ]);
    }

    public function showBook(Request $request)
    {
        $services = Service::query()
            ->where('active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $promo     = null;
        $promoSlot = null;

        if ($request->filled('promo_id')) {
            $promo = Promotion::active()->find($request->promo_id);
            if ($promo?->service_id) {
                $promoSlot = $this->findFirstSlotForPromo($promo);
            }
        }

        return view('portal.book', [
            'services'  => $services,
            'promo'     => $promo,
            'promoSlot' => $promoSlot,
        ]);
    }

    
    private function getEmployeesForService($service): \Illuminate\Support\Collection
    {
        $areaIds = \Illuminate\Support\Facades\DB::table('service_area')->where('service_id', $service->id)->pluck('area_id');
        if ($areaIds->isNotEmpty()) {
            return \App\Models\Employee::where('active', true)
                ->join('employee_area', 'employees.id', '=', 'employee_area.employee_id')
                ->whereIn('employee_area.area_id', $areaIds)
                ->orderBy('employee_area.priority')
                ->select('employees.*')
                ->get();
        }
        $eligibleIds = \App\Models\EmployeeCommission::where('category', $service->category)->pluck('employee_id');
        $q = \App\Models\Employee::where('active', true);
        if ($eligibleIds->isNotEmpty()) $q->whereIn('id', $eligibleIds);
        return $q->orderBy('name')->get();
    }
    private function findFirstSlotForPromo(Promotion $promo): ?array
    {
        $service = Service::find($promo->service_id);
        if (!$service) return null;

        $start    = Carbon::today()->max(Carbon::parse($promo->valid_from));
        $end      = Carbon::parse($promo->valid_to)->endOfDay();
        $duration = $service->duration_minutes;
        $now      = Carbon::now()->addMinutes(30);

        $eligibleIds  = EmployeeCommission::where('category', $service->category)->pluck('employee_id');
        $employees    = Employee::where('active', true)
            ->when($eligibleIds->isNotEmpty(), fn($q) => $q->whereIn('id', $eligibleIds))
            ->get();
        $equipmentIds = $service->equipment->pluck('id')->all();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $cursor  = Carbon::createFromFormat('Y-m-d H:i', "$dateStr 09:00");
            $limit   = Carbon::createFromFormat('Y-m-d H:i', "$dateStr 19:30");
            while ($cursor->copy()->addMinutes($duration)->lte($limit)) {
                if ($cursor->gte($now)) {
                    $s = $cursor->format('H:i');
                    $e = $cursor->copy()->addMinutes($duration)->format('H:i');
                    foreach ($employees as $emp) {
                        if (AppointmentConflictService::employeeHasConflict($emp->id, $dateStr, $s, $e)) continue;
                        if (!empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict($equipmentIds, $dateStr, $s, $e)) continue;
                        $ws = AppointmentConflictService::findAlternativeWorkstation($service->workstation_type, $dateStr, $s, $e);
                        if (!$ws) continue;
                        return ['date' => $dateStr, 'time' => $s];
                    }
                }
                $cursor->addMinutes(30);
            }
        }
        return null;
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

        $employees = $this->getEmployeesForService($service);
        $equipmentIds = $service->equipment->pluck('id')->all();

        foreach ($slots as $startTime) {
            $endTime = Carbon::createFromFormat('H:i', $startTime)->addMinutes($duration)->format('H:i');
            foreach ($employees as $employee) {
                if (AppointmentConflictService::employeeHasConflict($employee->id, $date, $startTime, $endTime)) continue;
                if (!empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict($equipmentIds, $date, $startTime, $endTime)) continue;
                $workstation = AppointmentConflictService::findAlternativeWorkstation($service->workstation_type, $date, $startTime, $endTime);
                if (!$workstation) continue;

                $exact = ($startTime === $preferred->format('H:i'));
                // Recolher todos os profissionais disponíveis para este slot
                $available = $employees->filter(function($emp) use ($date, $startTime, $endTime) {
                    return !\App\Services\AppointmentConflictService::employeeHasConflict($emp->id, $date, $startTime, $endTime);
                })->map(fn($e) => ['id' => $e->id, 'name' => $e->name])->values();
                // Para servicos a 4 maos, precisamos de 2 profissionais livres
                if ($service->two_employees && $available->count() < 2) continue;
                $secondary = $service->two_employees ? $available->skip(1)->first() : null;
                return response()->json([
                    'slot'                    => $startTime,
                    'exact'                   => $exact,
                    'employee_id'             => $employee->id,
                    'employee_name'           => $employee->name,
                    'secondary_employee_id'   => $secondary ? $secondary['id'] : null,
                    'secondary_employee_name' => $secondary ? $secondary['name'] : null,
                    'available_employees'     => $available,
                    'two_employees'           => (bool) $service->two_employees,
                ]);
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

        // Usar sistema de áreas (prioridade) para obter profissionais elegíveis
        $employees = $this->getEmployeesForService($service);
        // Se o cliente escolheu um profissional específico, coloca-o à frente
        if ($request->filled('employee_id')) {
            $preferred = (int) $request->employee_id;
            $employees = $employees->sortBy(fn($e) => $e->id === $preferred ? 0 : 1)->values();
        }
        $equipmentIds = $service->equipment->pluck('id')->all();

        foreach ($employees as $employee) {
            if (AppointmentConflictService::employeeHasConflict($employee->id, $data['appointment_date'], $startTime, $endTime)) continue;
            if (!empty($equipmentIds) && AppointmentConflictService::equipmentHasConflict($equipmentIds, $data['appointment_date'], $startTime, $endTime)) continue;
            $workstation = AppointmentConflictService::findAlternativeWorkstation($service->workstation_type, $data['appointment_date'], $startTime, $endTime);
            if (!$workstation) continue;

            // Para serviços a 4 mãos: encontrar 2º terapeuta disponível
            $secondaryEmployeeId = null;
            if ($service->two_employees) {
                $preferredSecondary = $request->filled('secondary_employee_id')
                    ? (int) $request->secondary_employee_id
                    : null;
                $secondaryEmployee = $employees
                    ->filter(fn($e) => $e->id !== $employee->id
                        && !AppointmentConflictService::employeeHasConflict($e->id, $data['appointment_date'], $startTime, $endTime))
                    ->sortBy(fn($e) => $e->id === $preferredSecondary ? 0 : 1)
                    ->first();
                if (!$secondaryEmployee) continue; // sem 2º terapeuta, tenta próximo slot
                $secondaryEmployeeId = $secondaryEmployee->id;
            }
            Appointment::create([
                'client_id'              => $client->id,
                'employee_id'            => $employee->id,
                'secondary_employee_id'  => $secondaryEmployeeId,
                'workstation_id'   => $workstation->id,
                'service_id'       => $service->id,
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $startTime,
                'end_time'         => $endTime,
                'status'           => 'scheduled',
                'price'            => (function() use ($request, $service) {
                    $price = $service->price;
                    if ($request->filled('promo_id')) {
                        $pr = Promotion::active()->find($request->promo_id);
                        if ($pr && $pr->service_id == $service->id) {
                            $price = round($price * (1 - $pr->discount_percentage / 100), 2);
                        }
                    }
                    return $price;
                })(),
            ]);
            return redirect()->route('portal.dashboard')->with('booking_success', true);
        }
        return back()
            ->withErrors(['appointment_time' => 'Não há disponibilidade nesse horário. Por favor escolhe outra data ou hora.'])
            ->withInput();
    }
}
