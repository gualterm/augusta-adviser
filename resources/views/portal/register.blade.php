<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar Conta | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.box{background:#fff;border-radius:25px;padding:40px;max-width:440px;width:100%;box-shadow:0 8px 30px rgba(0,0,0,.06);}
h1{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:34px;margin-bottom:6px;text-align:center;}
.sub{text-align:center;color:#9b8a7c;font-size:14px;margin-bottom:26px;}
label{font-size:13px;color:#6f5f54;font-weight:500;display:block;margin-bottom:6px;}
input{width:100%;padding:11px 14px;border:1px solid #e0d8d0;border-radius:10px;font-size:14px;margin-bottom:16px;background:#faf8f5;}
input:focus{outline:none;border-color:#cdb9a9;}
.error{color:#b85c5c;font-size:12.5px;margin:-10px 0 14px;}
button{width:100%;padding:13px;background:#7a6b5d;color:#fff;border:none;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;}
button:hover{opacity:.92;}
.foot{text-align:center;margin-top:18px;font-size:13px;color:#7a6b5d;}
.foot a{color:#7a6b5d;font-weight:600;}
.consent{display:flex;align-items:flex-start;gap:10px;margin-bottom:14px;font-size:12.5px;color:#6f5f54;line-height:1.5;}
.consent input[type=checkbox]{width:16px;min-width:16px;height:16px;margin-top:2px;accent-color:#7a6b5d;flex-shrink:0;}
</style>
</head>
<body>
<div class="box">
<h1>Augusta Adviser</h1>
<div class="sub">Cria a tua conta para marcares consultas online</div>

<form method="POST" action="{{ route('portal.register') }}">
@csrf
<label for="name">Nome</label>
<input type="text" id="name" name="name" value="{{ old('name') }}" required>
@error('name')<div class="error">{{ $message }}</div>@enderror

<label for="email">Email</label>
<input type="email" id="email" name="email" value="{{ old('email') }}" required>
@error('email')<div class="error">{{ $message }}</div>@enderror

<label for="phone">Telefone (opcional)</label>
<input type="text" id="phone" name="phone" value="{{ old('phone') }}">

<label for="password">Password</label>
<input type="password" id="password" name="password" required>
@error('password')<div class="error">{{ $message }}</div>@enderror

<label for="password_confirmation">Confirmar Password</label>
<input type="password" id="password_confirmation" name="password_confirmation" required>

<div class="consent">
<input type="checkbox" name="data_consent" value="1" required oninvalid="this.setCustomValidity('Por favor aceita os termos para poderes continuar.')" oninput="this.setCustomValidity('')">
<label>Aceito que a Augusta Adviser guarde e processe os meus dados pessoais para gestão das marcações (RGPD). <em>(obrigatório)</em></label>
</div>
@error('data_consent')<div class="error" style="margin-top:-8px;margin-bottom:12px;">{{ $message }}</div>@enderror
<div class="consent">
<input type="checkbox" name="marketing_consent" value="1">
<label>Aceito receber promoções exclusivas e novidades por email. <em>(opcional)</em></label>
</div>
<button type="submit">Criar Conta</button>
</form>

<div class="foot">Já tens conta? <a href="{{ route('portal.login') }}">Iniciar Sessão</a></div>
</div>
</body>
</html>
