<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selecionar Ambiente — Augusta Adviser</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'Georgia',interrefamily:serif;background:#f5f0eb;min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .wrapper{text-align:center;padding:2rem;max-width:800px;width:100%;}
    .logo{margin-bottom:2rem;}
    .logo img{height:140px;}
    h1{font-size:1.8rem;color:#3d2b1f;margin-bottom:.5rem;}
    .subtitle{color:#7a6152;margin-bottom:3rem;font-size:1rem;}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:2rem;}
    .card{background:white;border-radius:16px;padding:2rem;box-shadow:0 2px 16px rgba(0,0,0,.08);transition:transform .2s,box-shadow .2s;text-decoration:none;display:block;}
    .card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.14);}
    .card-icon{font-size:2.5rem;margin-bottom:1rem;}
    .card-title{font-size:1.2rem;font-weight:bold;color:#3d2b1f;margin-bottom:.5rem;}
    .card-desc{color:#7a6152;font-size:.9rem;line-height:1.6;margin-bottom:1.2rem;}
    .badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:.75rem;font-weight:bold;letter-spacing:.5px;}
    .badge-prod{background:#d4edda;color:#155724;}
    .badge-form{background:#cce5ff;color:#004085;}
    .logout{margin-top:2rem;}
    .logout button{background:none;border:none;cursor:pointer;color:#7a6152;font-size:.85rem;text-decoration:underline;}
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo">
      <img src="/images/logoaugusta-1a.png" alt="Augusta Adviser">
    </div>
    <h1>Selecionar Ambiente</h1>
    <p class="subtitle">Bem-vindo, {{ auth()->user()->name ?? 'Administrador' }}. Escolhe o ambiente de trabalho.</p>
    <div class="grid">
      <a href="https://augustaadviser.pt/admin" class="card">
        <div class="card-icon">🏢</div>
        <div class="card-title">Produção</div>
        <div class="card-desc">Sistema em produção com dados reais de clientes e marcações.</div>
        <span class="badge badge-prod">● LIVE</span>
      </a>
      <a href="http://augustadev.macedo/admin" class="card">
        <div class="card-icon">🎓</div>
        <div class="card-title">Formação & Demonstraçóo</div>
        <div class="card-desc">Ambiente para formação de staff e demonstração a clientes com dados fictícios.</div>
        <span class="badge badge-form">● DEMO</span>
      </a>
    </div>
    <div class="logout">
      <form method="POST" action="{{ route('filament.admin.auth.logout') }}" style="display:inline;">
        @csrf
        <button type="submit">Terminar sessão</button>
      </form>
    </div>
  </div>
</body>
</html>
