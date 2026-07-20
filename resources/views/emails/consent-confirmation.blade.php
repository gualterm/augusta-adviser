<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmação de Consentimento — Augusta Beauty Adviser</title>
</head>
<body style="font-family: Georgia, serif; background: #F5EFE8; margin: 0; padding: 32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="margin:0 auto; background:#FDFAF6; border-radius:8px; overflow:hidden;">
  <tr><td bgcolor="#FDFAF6" style="padding:20px 32px; text-align:center; border-bottom:2px solid #E8D5B7;">
    <img src="https://augustaadviser.pt/images/logoaugusta-1a.png" alt="Augusta Beauty Adviser" style="height:90px; width:auto; display:block; margin:0 auto;">
  </td></tr>
  <tr><td style="padding:32px;">
    <h2 style="color:#2C1810; margin:0 0 20px; font-size:1.15em;">&#10003; Consentimento Registado</h2>
    <p style="color:#5C3D2E; line-height:1.7; margin:0 0 12px;">
      Ol&aacute; <strong>{{ $consent->name }}</strong>,
    </p>
    <p style="color:#5C3D2E; line-height:1.7; margin:0 0 24px;">
      O seu consentimento de tratamento de dados pessoais e autoriza&ccedil;&atilde;o de tratamento est&eacute;tico
      foi registado com sucesso na Augusta Beauty Adviser.
    </p>
    {{-- Caixa de detalhes --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF8F0; border:1px solid #E8D5B7; border-radius:8px; margin-bottom:24px;">
    <tr><td style="padding:18px 20px; font-size:0.9em; color:#2C1810; line-height:2;">
      <p style="margin:0 0 4px;"><strong>Refer&ecirc;ncia:</strong> #{{ $consent->id }}</p>
      <p style="margin:0 0 12px;"><strong>Data:</strong> {{ ($consent->consented_at ?? now())->format('d/m/Y \à\s H:i') }}</p>
      <hr style="border:none;border-top:1px solid #E8D5B7;margin:4px 0 12px;">
      <p style="margin:0 0 4px;"><strong>Nome:</strong> {{ $consent->name }}</p>
      <p style="margin:0 0 4px;"><strong>Email:</strong> {{ $consent->email }}</p>
      @if($consent->phone)
      <p style="margin:0 0 4px;"><strong>Telem&oacute;vel:</strong> {{ $consent->phone }}</p>
      @endif
      @if($consent->birth_date)
      <p style="margin:0 0 4px;"><strong>Data de Nascimento:</strong> {{ \Carbon\Carbon::parse($consent->birth_date)->format('d/m/Y') }}</p>
      @endif
      @if($consent->nif)
      <p style="margin:0 0 4px;"><strong>NIF:</strong> {{ $consent->nif }}</p>
      @endif
      @if($consent->morada)
      <p style="margin:0 0 4px;"><strong>Morada:</strong> {{ $consent->morada }}@if($consent->codigo_postal), {{ $consent->codigo_postal }}@endif@if($consent->localidade) {{ $consent->localidade }}@endif</p>
      @endif
      <hr style="border:none;border-top:1px solid #E8D5B7;margin:12px 0 4px;">
      <p style="margin:0;"><strong>Marketing:</strong> {{ $consent->marketing_consent ? '&#10003; Aceite' : '&#10007; N&atilde;o aceite' }}</p>
    </td></tr>
    </table>
    <p style="color:#5C3D2E; line-height:1.7; font-size:0.88em; margin:0 0 12px;">
      Os seus dados ser&atilde;o utilizados exclusivamente para a gest&atilde;o dos seus agendamentos e servi&ccedil;os.
      Pode exercer os seus direitos (acesso, rectifica&ccedil;&atilde;o, apagamento) atrav&eacute;s de
      <a href="mailto:info@augustaadviser.pt" style="color:#C4975A; text-decoration:none;">info@augustaadviser.pt</a>.
    </p>
  </td></tr>
  <tr><td bgcolor="#F5EFE8" style="padding:16px 32px; text-align:center;">
    <p style="color:#8B6914; font-size:0.78em; margin:0;">
      Augusta Beauty Adviser &middot; Vila do Conde &middot;
      <a href="https://augustaadviser.pt" style="color:#8B6914;">augustaadviser.pt</a>
    </p>
  </td></tr>
</table>
</body>
</html>
