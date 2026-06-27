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
.card .meta{color:#7a6b5d;font-size:13px;margin-top:2px;}
.empty{color:#9b8a7c;font-size:14px;padding:14px;}
</style>
</head>
<body>
<div class="container">
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
<div class="meta">{{ $appointment->service?->name }} · {{ $appointment->employee?->name }}</div>
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
<div class="meta">{{ $appointment->service?->name }} · {{ $appointment->employee?->name }}</div>
</div>
</div>
@empty
<div class="empty">Ainda sem histórico.</div>
@endforelse
</div>
</body>
</html>
