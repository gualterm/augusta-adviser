<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>A Minha Conta | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;}
.container{max-width:900px;margin:auto;padding:40px 20px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.topbar h1{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:30px;}
.topbar form{display:inline;}
.btn{display:inline-block;padding:10px 20px;background:#7a6b5d;color:#fff;border:none;border-radius:30px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-outline{background:#fff;color:#7a6b5d;border:1px solid #cdb9a9;}
.success{background:#e8f3ea;color:#2f6b3f;padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;}
.section-title{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:22px;margin:26px 0 12px;}
.card{background:#fff;border-radius:16px;padding:16px;margin-bottom:10px;box-shadow:0 4px 14px rgba(0,0,0,.04);display:flex;justify-content:space-between;}
.card .time{font-weight:700;color:#2f2a28;}
.card .meta{color:#3d2f25;font-size:13px;margin-top:2px;font-weight:600;}
.empty{color:#9b8a7c;font-size:14px;padding:14px;}
</style>
</head>
<body>
<div class="container">
@if(session('suggest_password_change'))
<div style='background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:14px;color:#856404;'>
    A tua password tem mais de 1 ano. Recomendamos que a <a href="{{ route('portal.forgot-password') }}" style='color:#856404;font-weight:bold;'>alteres por segurança</a>.
</div>
@endif
<div class="topbar">
<h1>Olá, {{ $client->name }}</h1>
<div>
<a href="{{ route('portal.book') }}" class="btn">Marcar Consulta</a>
<form method="POST" action="{{ route('portal.logout') }}" style="display:inline-block;margin-left:8px;">
@csrf
<button type="submit" class="btn btn-outline">Sair</button>
</form>
</div>
</div>

@if(session('cancel_success'))
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;margin-bottom:16px;color:#c0392b;font-size:14px;">
  ✕ Marcação cancelada. Se mudares de ideias, podes fazer uma nova marcação.
</div>
@endif
@if(session('email_verified'))
<div style="background:#e8f3ea;border:1px solid #c3e0c9;border-radius:12px;padding:14px 18px;margin-bottom:16px;color:#2f6b3f;font-size:14px;">✓ Email confirmado! Bem-vindo(a) à Augusta Adviser.</div>
@endif
@if(session('booking_success'))
<div class="success">Marcação efetuada com sucesso! Já a podes ver abaixo.</div>
@endif


@if($promotions->isNotEmpty())
<div class="section-title">Promoções Exclusivas</div>
@foreach($promotions as $promo)
<div class="card" style="background:#fff8f0;border-left:4px solid #cdb9a9;display:block;">
    <div style="font-weight:700;color:#6f5f54;font-size:15px;margin-bottom:4px;">🎁 {{ $promo->title }}</div>
    @if($promo->description)<div style="font-size:13px;color:#7a6b5d;margin-bottom:6px;">{{ $promo->description }}</div>@endif
    <div style="font-size:12px;color:#9b8a7c;margin-bottom:12px;">{{ number_format($promo->discount_percentage,0) }}% desconto · Válida até {{ \Illuminate\Support\Carbon::parse($promo->valid_to)->format("d/m/Y") }}</div>
    <a href="{{ route('portal.book') }}?promo_id={{ $promo->id }}" class="btn" style="font-size:13px;padding:8px 18px;display:inline-block;">Reservar com desconto</a>
</div>
@endforeach
@endif
<div class="section-title">Próximas Marcações</div>
@forelse($appointments as $appointment)
<div class="card">
<div>
<div class="time">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_date)->format('d/m/Y') }} às {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('H:i') }}</div>
<div class="meta">{{ $appointment->service?->name }} · {{ $appointment->employee?->name }}{{ $appointment->secondaryEmployee ? ' & ' . $appointment->secondaryEmployee->name : '' }}</div>
@if($appointment->reschedule_count === 0 && in_array($appointment->status, ['scheduled','confirmed']))
<div style='margin-top:8px;'>
  <a href="{{ route('portal.reschedule', $appointment->id) }}" style='font-size:12px;color:#7a6b5d;border:1px solid #cdb9a9;border-radius:6px;padding:4px 12px;text-decoration:none;display:inline-block;'>&#8635; Remarcar</a>
</div>
@endif
 @php $apptDT = \Carbon\Carbon::parse($appointment->appointment_date->format('Y-m-d').' '.$appointment->appointment_time); @endphp
 @if(in_array($appointment->status, ['scheduled','confirmed']))
   @if($apptDT->diffInHours(now(), false) < -24)
   <div style='margin-top:6px;'>
     <form method='POST' action="{{ route('portal.cancel', $appointment->id) }}" onsubmit="return confirm('Tens a certeza que queres cancelar esta marcação?')">
       @csrf
       <button type='submit' style='font-size:12px;color:#c0392b;background:none;border:1px solid #e8c4c4;border-radius:6px;padding:4px 12px;cursor:pointer;'>&#10005; Cancelar</button>
     </form>
   </div>
   @else
   <div style='margin-top:6px;'>
     <span style='font-size:12px;color:#7a6b5d;'>📞 Para cancelar liga-nos diretamente</span>
   </div>
   @endif
 @endif
@if($appointment->price && $appointment->price < ($appointment->service?->price ?? PHP_INT_MAX))
<div class="meta" style="margin-top:4px;"><del style="color:#bbb;">€ {{ number_format($appointment->service->price,2,",",".") }}</del> <strong style="color:#5a8a52;">€ {{ number_format($appointment->price,2,",",".") }}</strong> <span style="font-size:11px;color:#9b8a7c;">(promoção)</span></div>
@elseif($appointment->price)
<div class="meta" style="margin-top:4px;">€ {{ number_format($appointment->price,2,",",".") }}</div>
@endif
</div>
</div>
@empty
<div class="empty">Não tens marcações futuras. <a href="{{ route('portal.book') }}">Marca já a tua próxima consulta.</a></div>
@endforelse

<div class="section-title">Histórico</div>
@forelse($history as $appointment)
<div class="card">
<div>
<div class="time">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_date)->format('d/m/Y') }}</div>
<div class="meta">{{ $appointment->service?->name }} · {{ $appointment->employee?->name }}{{ $appointment->secondaryEmployee ? ' & ' . $appointment->secondaryEmployee->name : '' }}</div>
</div>
</div>
@empty
<div class="empty">Ainda sem histórico.</div>
@endforelse
</div>
</body>
</html>
