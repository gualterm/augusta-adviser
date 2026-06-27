<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrada Staff | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:30px;}
.logo{height:100px;width:auto;margin-bottom:32px;}
h1{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:26px;margin-bottom:6px;text-align:center;}
.sub{font-size:13px;color:#9b8a7c;margin-bottom:36px;text-align:center;}
.cards{display:flex;flex-wrap:wrap;gap:16px;justify-content:center;max-width:700px;}
.card{background:#fff;border-radius:20px;padding:28px 32px;min-width:190px;flex:1;max-width:210px;text-align:center;text-decoration:none;box-shadow:0 4px 18px rgba(0,0,0,.06);transition:.2s;border:2px solid transparent;}
.card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,0,0,.1);}
.card .icon{font-size:36px;margin-bottom:12px;}
.card .label{font-size:15px;font-weight:600;color:#4a3f37;margin-bottom:6px;}
.card .desc{font-size:12px;color:#9b8a7c;line-height:1.5;}
.card.prod{border-color:#cdb9a9;}
.card.prod:hover{border-color:#7a6b5d;}
.card.train{border-color:#b8c9a8;}
.card.train:hover{border-color:#6a8c5a;}
.card.client{border-color:#a8c5d4;}
.card.client:hover{border-color:#4a8ca8;}
.back{margin-top:32px;font-size:12px;color:#b0a090;text-decoration:none;}
.back:hover{color:#7a6b5d;}
</style>
</head>
<body>
<img src="/images/logoaugusta-1a.png" alt="Augusta Adviser" class="logo">
<h1>Área de Acesso</h1>
<p class="sub">Seleciona o ambiente pretendido</p>

<div class="cards">
  <a href="https://augustaadviser.pt/admin" class="card prod">
    <div class="icon">🏢</div>
    <div class="label">Produção</div>
    <div class="desc">Dados reais.<br>Uso diário da clínica.</div>
  </a>

  <a href="http://augusta.macedo/admin" class="card train">
    <div class="icon">🎓</div>
    <div class="label">Formação Staff</div>
    <div class="desc">Ambiente de testes.<br>Treino sem riscos.</div>
  </a>

  <a href="https://augustaadviser.pt/portal" class="card client">
    <div class="icon">👤</div>
    <div class="label">Testes Cliente</div>
    <div class="desc">Simular a experiência<br>do cliente no portal.</div>
  </a>
</div>

<a href="/" class="back">← Voltar ao site</a>
</body>
</html>
