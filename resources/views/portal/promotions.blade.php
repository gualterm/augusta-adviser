@extends('portal.layout')

@section('title', 'Promoções')

@section('content')
<div class="page-header">
    <h2>Promoções</h2>
    <p class="page-sub">Ofertas exclusivas para os nossos clientes</p>
</div>

@if($promotions->isEmpty())
    <div class="empty-state">
        <p>Não há promoções ativas de momento.<br>Volta em breve!</p>
    </div>
@else
    <div class="promotions-grid">
        @foreach($promotions as $promo)
        <div class="promo-card">
            <div class="promo-badge">
                @if($promo->serviceDiscounts->isNotEmpty())
                    até {{ number_format($promo->discount_percentage, 0) }}%
                @else
                    {{ number_format($promo->discount_percentage, 0) }}%
                @endif
            </div>
            <h3 class="promo-title">{{ $promo->title }}</h3>
            <p class="promo-service">
                @if($promo->service)
                    🎯 {{ $promo->service->name }}
                @else
                    ✨ Todos os serviços
                @endif
            </p>
            @if($promo->serviceDiscounts->isNotEmpty())
            @php
                $overrideGroups = $promo->serviceDiscounts->load('service')->groupBy('discount_percent')->sortKeysDesc();
            @endphp
            <div style="font-size:11.5px;color:#9b8a7c;line-height:1.6;">
                @foreach($overrideGroups as $pct => $items)
                <span>🏷️ {{ number_format($pct,0) }}%: {{ $items->map(fn($i)=>$i->service?->name)->filter()->implode(', ') }}</span><br>
                @endforeach
            </div>
            @endif
            <p class="promo-validity">Válida até {{ \Carbon\Carbon::parse($promo->valid_to)->format('d/m/Y') }}</p>
            <a href="{{ route('portal.book', ['promo_id' => $promo->id]) }}" class="btn-promo">Aproveitar</a>
        </div>
        @endforeach
    </div>
@endif

<style>
.page-header { margin-bottom: 32px; }
.page-header h2 { font-family: 'Cormorant Garamond', serif; font-size: 32px; color: #6f5f54; margin-bottom: 4px; }
.page-sub { color: #9b8a7c; font-size: 14px; }
.empty-state { text-align: center; padding: 60px 20px; color: #9b8a7c; font-size: 15px; line-height: 1.7; }
.promotions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
.promo-card {
    background: #fff; border-radius: 20px; padding: 28px 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    display: flex; flex-direction: column; gap: 10px;
    position: relative; overflow: hidden;
}
.promo-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(to right, #cdb9a9, #7a6b5d);
}
.promo-badge {
    display: inline-block; background: #f5ede7; color: #7a6b5d;
    font-size: 26px; font-weight: 700; font-family: 'Cormorant Garamond', serif;
    padding: 4px 16px; border-radius: 30px; align-self: flex-start;
}
.promo-title { font-family: 'Cormorant Garamond', serif; font-size: 20px; color: #6f5f54; line-height: 1.3; }
.promo-service { font-size: 13px; color: #7a6b5d; }
.promo-validity { font-size: 12px; color: #9b8a7c; margin-top: auto; }
.btn-promo {
    display: inline-block; padding: 10px 22px;
    background: #7a6b5d; color: #fff; border-radius: 30px;
    font-size: 13px; font-weight: 600; text-decoration: none; text-align: center;
}
.btn-promo:hover { opacity: .88; }
</style>
@endsection