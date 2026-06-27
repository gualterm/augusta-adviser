<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Promoções | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;}
.container{max-width:900px;margin:auto;padding:40px 20px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}
.topbar h1{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:30px;}
.btn{display:inline-block;padding:10px 20px;background:#7a6b5d;color:#fff;border:none;border-radius:30px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;}
.btn-outline{background:#fff;color:#7a6b5d;border:1px solid #cdb9a9;}
.section-title{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:22px;margin:0 0 20px;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;}
.promo-card{background:#fff;border-radius:16px;padding:24px 20px;box-shadow:0 4px 14px rgba(0,0,0,.05);display:flex;flex-direction:column;gap:10px;position:relative;overflow:hidden;}
.promo-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(to right,#cdb9a9,#7a6b5d);}
.badge{display:inline-block;background:#f5ede7;color:#7a6b5d;font-size:20px;font-weight:700;font-family:'Cormorant Garamond',serif;padding:4px 14px;border-radius:30px;align-self:flex-start;}
.promo-title{font-family:'Cormorant Garamond',serif;font-size:18px;color:#6f5f54;line-height:1.3;}
.promo-desc{font-size:13px;color:#7a6b5d;line-height:1.6;}
.promo-valid{font-size:12px;color:#9b8a7c;margin-top:auto;}
.promo-btn{margin-top:6px;display:inline-block;padding:9px 20px;background:#7a6b5d;color:#fff;border-radius:30px;font-size:13px;font-weight:600;text-decoration:none;text-align:center;}
.promo-btn:hover{opacity:.88;}
.empty{color:#9b8a7c;font-size:14px;padding:14px;}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>Promoções</h1>
        <div>
            <a href="{{ route('portal.dashboard') }}" class="btn btn-outline">← Conta</a>
            <a href="{{ route('portal.book') }}" class="btn" style="margin-left:8px;">Marcar Consulta</a>
        </div>
    </div>
    <div class="section-title">Ofertas Exclusivas</div>
    @if($promotions->isEmpty())
        <div class="empty">Não há promoções ativas neste momento. Volta em breve!</div>
    @else
        <div class="grid">
            @foreach($promotions as $promo)
            <div class="promo-card">
                <div class="badge">{{ $promo->formatted_discount }}</div>
                <div class="promo-title">{{ $promo->title }}</div>
                @if($promo->description)
                    <div class="promo-desc">{{ $promo->description }}</div>
                @endif
                @if($promo->valid_until)
                    <div class="promo-valid">Válida até {{ $promo->valid_until->format('d/m/Y') }}</div>
                @endif
                <a href="{{ route('portal.book') . ($promo->service_id ? '?service_id=' . $promo->service_id : '') }}" class="promo-btn">Aproveitar</a>
            </div>
            @endforeach
        </div>
    @endif
</div>
</body>
</html>
