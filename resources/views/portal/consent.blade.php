<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consentimento RGPD | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.box{background:#fff;border-radius:25px;padding:40px;max-width:540px;width:100%;box-shadow:0 8px 30px rgba(0,0,0,.06);}
.logo-wrap{text-align:center;margin-bottom:16px;}
.logo-wrap img{height:64px;object-fit:contain;}
.logo-text{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:30px;text-align:center;display:none;}
.sub{text-align:center;color:#9b8a7c;font-size:13px;margin-bottom:8px;}
.steps{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:22px;font-size:12px;}
.st-d{color:#7a6b5d;font-weight:600;}
.st-a{background:#7a6b5d;color:#fff;border-radius:50px;padding:3px 12px;font-weight:600;}
label{font-size:13px;color:#6f5f54;font-weight:500;display:block;margin-bottom:5px;}
.fval{background:#faf8f5;border:1px solid #e0d8d0;border-radius:10px;padding:10px 14px;font-size:14px;margin-bottom:14px;color:#555;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.legal{background:#fdf6ee;border:1px solid #e8d5b7;border-radius:10px;padding:14px;font-size:12px;color:#6f5f54;line-height:1.6;margin-bottom:18px;}
.ck{display:flex;align-items:flex-start;gap:10px;margin-bottom:16px;font-size:12.5px;color:#6f5f54;line-height:1.5;}
.ck input{width:16px;min-width:16px;height:16px;margin-top:2px;accent-color:#7a6b5d;flex-shrink:0;}
.sig-label{font-size:13px;color:#6f5f54;font-weight:500;margin-bottom:8px;}
.sig-wrap{border:1.5px solid #cdb9a9;border-radius:10px;overflow:hidden;touch-action:none;margin-bottom:6px;background:#fff;}
#sig-canvas{display:block;width:100%;height:140px;cursor:crosshair;touch-action:none;-webkit-user-select:none;user-select:none;}
.sig-actions{display:flex;gap:8px;align-items:center;margin-bottom:16px;}
.btn-clear{padding:7px 16px;border:1px solid #cdb9a9;border-radius:20px;background:#fff;font-size:12px;color:#7a6b5d;cursor:pointer;font-family:'Montserrat',sans-serif;}
.sig-hint{font-size:11px;color:#9b8a7c;}
.error-msg{color:#b85c5c;font-size:12px;margin-bottom:10px;}
button[type=submit]{width:100%;padding:13px;background:#7a6b5d;color:#fff;border:none;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;}
button[type=submit]:hover{opacity:.92;}
</style>
</head>
<body>
<div class="box">
  <div class="logo-wrap">
    <img src="{{ asset('images/logo-augusta.png') }}" alt="Augusta Adviser"
         onerror="this.style.display='none';document.getElementById('lt').style.display='block'">
    <div class="logo-text" id="lt">Augusta Adviser</div>
  </div>
  <div class="sub">Consentimento de Tratamento de Dados &amp; Autorização Estética</div>
  <div class="steps">
    <span class="st-d">✓ 1 Os teus dados</span>
    <span style="color:#ccc">›</span>
    <span class="st-a">2 Consentimento RGPD</span>
  </div>

  <form method="POST" action="{{ route('portal.consent.save') }}" id="consentForm">
    @csrf

    <div class="row2">
      <div><label>Nome</label><div class="fval">{{ $client->name }}</div></div>
      <div><label>Telemóvel</label><div class="fval">{{ $client->phone ?? '—' }}</div></div>
    </div>
    <div class="row2">
      <div><label>Email</label><div class="fval" style="font-size:12px;word-break:break-all;">{{ $client->email }}</div></div>
      <div><label>NIF</label><div class="fval">{{ $client->nif ?? '—' }}</div></div>
    </div>
    @if($client->address || $client->morada)
    <label>Morada</label>
    <div class="fval">{{ $client->address ?? $client->morada }}</div>
    @endif

    <div class="legal">
      A signatária/o signatário declara ter lido e aceite a política de tratamento de dados da Augusta Beauty Adviser, nos termos do Regulamento Geral sobre a Proteção de Dados (RGPD / Reg. EU 2016/679). Autoriza a recolha e tratamento dos dados pessoais acima indicados para gestão de agendamentos e comunicação de serviços. Autoriza ainda a realização dos tratamentos estéticos agendados, declarando ter sido informado(a) das suas características e possíveis contra-indicações.
    </div>

    <div class="ck">
      <input type="checkbox" name="marketing_consent" value="1" id="mkt" {{ old('marketing_consent') ? 'checked' : '' }}>
      <label for="mkt" style="font-weight:400;">Aceito receber promoções exclusivas e novidades por email. <em>(opcional)</em></label>
    </div>

    <div class="sig-label">Assinatura digital *</div>
    <div class="sig-wrap"><canvas id="sig-canvas"></canvas></div>
    <div class="sig-actions">
      <button type="button" class="btn-clear" onclick="clearSig()">✕ Limpar</button>
      <span class="sig-hint" id="sig-hint">Assine com o rato ou o dedo</span>
    </div>
    @error('signature_data')<div class="error-msg">{{ $message }}</div>@enderror

    <input type="hidden" name="signature_data" id="signature_data">

    <button type="submit">Confirmar Consentimento RGPD ✓</button>
  </form>
</div>

<script>
var canvas = document.getElementById('sig-canvas');
var ctx, drawing = false, signed = false;

function initCanvas() {
  ctx = canvas.getContext('2d');
  var dpr = window.devicePixelRatio || 1;
  var rect = canvas.getBoundingClientRect();
  if (!rect.width) return;
  canvas.width = rect.width * dpr;
  canvas.height = rect.height * dpr;
  ctx.scale(dpr, dpr);
  ctx.strokeStyle = '#2C1810';
  ctx.lineWidth = 1.8;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
}
document.addEventListener('DOMContentLoaded', function() { initCanvas(); });
window.addEventListener('resize', function() {
  var img = new Image(); img.src = canvas.toDataURL();
  initCanvas();
  img.onload = function() { var d = window.devicePixelRatio||1; ctx.drawImage(img, 0, 0, canvas.width/d, canvas.height/d); };
});
function pos(e) {
  var r = canvas.getBoundingClientRect();
  return e.touches ? {x:e.touches[0].clientX-r.left, y:e.touches[0].clientY-r.top} : {x:e.clientX-r.left, y:e.clientY-r.top};
}
canvas.addEventListener('mousedown', function(e){drawing=true;ctx.beginPath();var p=pos(e);ctx.moveTo(p.x,p.y);});
canvas.addEventListener('mousemove', function(e){if(!drawing)return;var p=pos(e);ctx.lineTo(p.x,p.y);ctx.stroke();signed=true;});
canvas.addEventListener('mouseup', function(){drawing=false;});
canvas.addEventListener('mouseleave', function(){drawing=false;});
canvas.addEventListener('touchstart', function(e){e.preventDefault();drawing=true;ctx.beginPath();var p=pos(e);ctx.moveTo(p.x,p.y);},{passive:false});
canvas.addEventListener('touchmove', function(e){e.preventDefault();if(!drawing)return;var p=pos(e);ctx.lineTo(p.x,p.y);ctx.stroke();signed=true;},{passive:false});
canvas.addEventListener('touchend', function(){drawing=false;});
function clearSig(){ctx.clearRect(0,0,canvas.width,canvas.height);signed=false;document.getElementById('signature_data').value='';}
document.getElementById('consentForm').addEventListener('submit', function(e) {
  if (!signed) {
    e.preventDefault();
    var h = document.getElementById('sig-hint');
    h.textContent = '⚠ Por favor assine antes de continuar.';
    h.style.color = '#b85c5c';
    return;
  }
  document.getElementById('signature_data').value = canvas.toDataURL('image/png');
});
</script>
</body>
</html>