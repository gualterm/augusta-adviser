<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Politica de Cookies - Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;line-height:1.7;}
.top-bar{background:#1a1a1a;padding:16px 24px;}
.top-bar a{color:#C9A96E;text-decoration:none;font-size:14px;font-weight:500;}
.top-bar a:hover{text-decoration:underline;}
.content{max-width:760px;margin:0 auto;padding:60px 24px 80px;}
h1{font-family:'Cormorant Garamond',serif;font-size:2.2rem;font-weight:600;color:#1a1a1a;margin-bottom:1.5rem;}
h2{font-family:'Cormorant Garamond',serif;font-size:1.35rem;font-weight:600;color:#1a1a1a;margin:2rem 0 .75rem;}
p{margin-bottom:1rem;font-size:15px;}
a{color:#C9A96E;}
table{width:100%;border-collapse:collapse;margin-bottom:1.5rem;font-size:14px;}
th{padding:10px 12px;text-align:left;border:1px solid #ddd;background:#f0ece6;font-weight:600;color:#1a1a1a;}
td{padding:10px 12px;border:1px solid #ddd;}
tr:nth-child(even) td{background:#f7f4f0;}
.btn-area{display:flex;gap:12px;flex-wrap:wrap;margin:1rem 0 2rem;}
.btn-aceitar{background:#C9A96E;color:#fff;border:none;padding:10px 22px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:600;font-family:'Montserrat',sans-serif;}
.btn-rejeitar{background:#fff;color:#333;border:1px solid #ccc;padding:10px 22px;border-radius:4px;cursor:pointer;font-size:14px;font-family:'Montserrat',sans-serif;}
.note{font-size:13px;color:#888;margin-top:2rem;}
</style>
</head>
<body>
<div class="top-bar"><a href="/">&#8592; Voltar ao site</a></div>
<div class="content">
  <h1>Politica de Cookies</h1>
  <p>Este website utiliza cookies para melhorar a sua experiencia de navegacao e analisar o trafego de forma anonima.</p>
  <h2>O que sao cookies?</h2>
  <p>Cookies sao pequenos ficheiros de texto guardados no seu dispositivo. Permitem que o site se lembre das suas preferencias e melhore a sua experiencia.</p>
  <h2>Que cookies utilizamos?</h2>
  <table>
    <thead><tr><th>Cookie</th><th>Finalidade</th><th>Duracao</th></tr></thead>
    <tbody>
      <tr><td>cookie_consent</td><td>Guardar a sua preferencia de consentimento</td><td>1 ano</td></tr>
      <tr><td>_ga, _ga_*</td><td>Google Analytics - analise de trafego anonimo (apenas se aceitar)</td><td>2 anos</td></tr>
    </tbody>
  </table>
  <h2>Google Analytics</h2>
  <p>Utilizamos o Google Analytics 4 para compreender como os visitantes utilizam o nosso site. Os dados sao anonimos e processados pela Google LLC nos termos do <a href="https://policies.google.com/privacy" target="_blank">RGPD</a>.</p>
  <h2>Gerir as suas preferencias</h2>
  <p>Pode alterar a sua preferencia a qualquer momento:</p>
  <div class="btn-area">
    <button class="btn-aceitar" onclick="aceitarCookies(); alert('Preferencia guardada: Cookies aceites.')">Aceitar cookies</button>
    <button class="btn-rejeitar" onclick="rejeitarCookies(); alert('Preferencia guardada: Cookies rejeitados.')">Rejeitar cookies</button>
  </div>
  <p class="note">Ultima atualizacao: Agosto de 2026</p>
</div>
@include('partials.cookie-consent')
</body>
</html>