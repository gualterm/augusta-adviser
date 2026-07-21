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
.box{background:#fff;border-radius:25px;padding:40px;max-width:480px;width:100%;box-shadow:0 8px 30px rgba(0,0,0,.06);}
.logo-wrap{text-align:center;margin-bottom:16px;}
.logo-wrap img{height:64px;object-fit:contain;}
.logo-text{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:30px;text-align:center;display:none;}
.sub{text-align:center;color:#9b8a7c;font-size:13px;margin-bottom:8px;}
.steps{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:22px;font-size:12px;}
.st-a{background:#7a6b5d;color:#fff;border-radius:50px;padding:3px 12px;font-weight:600;}
.st-n{color:#b0a090;}
label{font-size:13px;color:#6f5f54;font-weight:500;display:block;margin-bottom:5px;}
input,select{width:100%;padding:11px 14px;border:1px solid #e0d8d0;border-radius:10px;font-size:14px;margin-bottom:14px;background:#faf8f5;font-family:'Montserrat',sans-serif;color:#555;}
input:focus,select:focus{outline:none;border-color:#cdb9a9;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.error{color:#b85c5c;font-size:12px;margin:-8px 0 10px;}
button{width:100%;padding:13px;background:#7a6b5d;color:#fff;border:none;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;margin-top:4px;}
button:hover{opacity:.92;}
.foot{text-align:center;margin-top:18px;font-size:13px;color:#7a6b5d;}
.foot a{color:#7a6b5d;font-weight:600;}
</style>
</head>
<body>
<div class="box">
  <div class="logo-wrap">
    <img src="{{ asset('images/logo-augusta.png') }}" alt="Augusta Adviser"
         onerror="this.style.display='none';document.getElementById('lt').style.display='block'">
    <div class="logo-text" id="lt">Augusta Adviser</div>
  </div>
  <div class="sub">Cria a tua conta de cliente</div>
  <div class="steps">
    <span class="st-a">1 Os teus dados</span>
    <span class="st-n">›</span>
    <span class="st-n">2 Consentimento RGPD</span>
  </div>

  <form method="POST" action="{{ route('portal.register') }}">
    @csrf

    <label for="name">Nome completo *</label>
    <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
    @error('name')<div class="error">{{ $message }}</div>@enderror

    <div class="row2">
      <div>
        <label for="phone">Telemóvel *</label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required>
        @error('phone')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label for="gender">Género *</label>
        <select id="gender" name="gender" required>
          <option value="">Selecionar</option>
          <option value="F" {{ old('gender')=='F'?'selected':'' }}>Feminino</option>
          <option value="M" {{ old('gender')=='M'?'selected':'' }}>Masculino</option>
        </select>
        @error('gender')<div class="error">{{ $message }}</div>@enderror
      </div>
    </div>

    <label for="email">Email *</label>
    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    @error('email')<div class="error">{{ $message }}</div>@enderror

    <div class="row2">
      <div>
        <label for="nif">NIF *</label>
        <input type="text" id="nif" name="nif" value="{{ old('nif') }}" required maxlength="20" placeholder="999999999">
        @error('nif')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label for="birth_date">Data de nascimento</label>
        <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}">
      </div>
    </div>

    <label for="morada">Morada <small style="color:#9b8a7c;font-weight:400;">(opcional)</small></label>
    <input type="text" id="morada" name="morada" value="{{ old('morada') }}" placeholder="Rua, número, andar">

    <label for="password">Password *</label>
    <input type="password" id="password" name="password" required autocomplete="new-password">
    @error('password')<div class="error">{{ $message }}</div>@enderror

    <label for="password_confirmation">Confirmar Password *</label>
    <input type="password" id="password_confirmation" name="password_confirmation" required>

    <button type="submit">Continuar para Consentimento RGPD →</button>
  </form>

  <div class="foot">Já tens conta? <a href="{{ route('portal.login') }}">Iniciar Sessão</a></div>
</div>
</body>
</html>