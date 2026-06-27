<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nova Password | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:30px;}
.box{background:#fff;border-radius:25px;padding:40px;max-width:420px;width:100%;box-shadow:0 8px 30px rgba(0,0,0,.06);}
h1{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:34px;margin-bottom:6px;text-align:center;}
.sub{text-align:center;color:#9b8a7c;font-size:14px;margin-bottom:26px;}
label{font-size:13px;color:#6f5f54;font-weight:500;display:block;margin-bottom:6px;}
input{width:100%;padding:11px 14px;border:1px solid #e0d8d0;border-radius:10px;font-size:14px;margin-bottom:16px;background:#faf8f5;}
input:focus{outline:none;border-color:#cdb9a9;}
.error{color:#b85c5c;font-size:12.5px;margin:-10px 0 14px;}
button{width:100%;padding:13px;background:#7a6b5d;color:#fff;border:none;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;}
button:hover{opacity:.92;}
.foot{text-align:center;margin-top:18px;font-size:13px;}
.foot a{color:#7a6b5d;font-weight:600;}
</style>
</head>
<body>
<div class="box">
    <h1>Augusta Adviser</h1>
    <div class="sub">Define a tua nova password.</div>
    <form method="POST" action="{{ route('portal.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <label for="password">Nova Password</label>
        <input type="password" id="password" name="password" required>
        @error('password')<div class="error">{{ $message }}</div>@enderror
        <label for="password_confirmation">Confirmar Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>
        <button type="submit">Guardar Nova Password</button>
    </form>
    <div class="foot"><a href="{{ route('portal.login') }}">← Voltar ao login</a></div>
</div>
</body>
</html>
