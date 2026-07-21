<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activar Portal | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.box{background:#fff;border-radius:25px;padding:40px;max-width:460px;width:100%;box-shadow:0 8px 30px rgba(0,0,0,.06);}
.logo-wrap{text-align:center;margin-bottom:16px;}
.logo-wrap img{height:64px;object-fit:contain;}
.logo-text{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:30px;text-align:center;display:none;}
.sub{text-align:center;color:#9b8a7c;font-size:13px;margin-bottom:24px;}
.section{background:#faf8f5;border-radius:12px;padding:16px;margin-bottom:20px;}
.section h3{font-size:11px;color:#9b8a7c;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.field{margin-bottom:10px;}
label{font-size:12px;color:#6f5f54;font-weight:500;display:block;margin-bottom:4px;}
.fval{font-size:14px;color:#555;padding:4px 0;}
.divider{border:none;border-top:1px solid #e0d8d0;margin:20px 0;}
input{width:100%;padding:11px 14px;border:1px solid #e0d8d0;border-radius:10px;font-size:14px;background:#fff;font-family:'Montserrat',sans-serif;}
input:focus{outline:none;border-color:#cdb9a9;}
.error{color:#b85c5c;font-size:12px;margin:4px 0 8px;}
button{width:100%;padding:13px;background:#7a6b5d;color:#fff;border:none;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;margin-top:8px;}
button:hover{opacity:.92;}
.welcome{background:#e8f5e9;border-radius:10px;padding:14px;text-align:center;font-size:13px;color:#2e7d32;margin-bottom:20px;}
</style>
</head>
<body>
<div class="box">
  <div class="logo-wrap">
    <img src="{{ asset('images/logoaugusta-1a.png') }}" alt="Augusta Adviser"
         onerror="this.style.display='none';document.getElementById('lt').style.display='block'">
    <div class="logo-text" id="lt">Augusta Adviser</div>
  </div>
  <div class="sub">Activar o seu acesso ao portal de cliente</div>

  <div class="welcome">✓ O seu consentimento RGPD está registado. Defina a sua password para aceder ao portal.</div>

  <div class="section">
    <h3>Os seus dados</h3>
    <div class="row2">
      <div class="field"><label>Nome</label><div class="fval">{{ $client->name }}</div></div>
      <div class="field"><label>Telemóvel</label><div class="fval">{{ $client->phone ?? '—' }}</div></div>
    </div>
    <div class="row2">
      <div class="field"><label>Email</label><div class="fval" style="font-size:12px;word-break:break-all;">{{ $client->email }}</div></div>
      <div class="field"><label>NIF</label><div class="fval">{{ $client->nif ?? '—' }}</div></div>
    </div>
    @if($client->address || $client->morada)
    <div class="field"><label>Morada</label><div class="fval">{{ $client->address ?? $client->morada }}</div></div>
    @endif
  </div>

  <form method="POST" action="{{ route('portal.activate.save', $token) }}">
    @csrf
    <label for="password">Definir Password *</label>
    <input type="password" id="password" name="password" required autocomplete="new-password" style="margin-bottom:6px;">
    @error('password')<div class="error">{{ $message }}</div>@enderror

    <label for="password_confirmation" style="margin-top:8px;display:block;margin-bottom:4px;">Confirmar Password *</label>
    <input type="password" id="password_confirmation" name="password_confirmation" required>

    <button type="submit">Activar e Entrar no Portal</button>
  </form>
</div>
</body>
</html>