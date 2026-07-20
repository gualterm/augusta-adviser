<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmação de Consentimento — Augusta</title>
</head>
<body style="font-family: Georgia, serif; background: #F5EFE8; margin: 0; padding: 32px 16px;">
<table width="100%" cellpadding="0" cellspacing="0">
  <tr><td bgcolor="#FDFAF6" style="padding:20px 32px; text-align:center; border-bottom:2px solid #E8D5B7;">
    <img src="https://augustaadviser.pt/images/logoaugusta-1a.png" alt="Augusta Beauty Advisor" style="height:90px; width:auto; display:block; margin:0 auto;">
  </td></tr>

  {{-- Body --}}
  <tr><td style="padding:32px;">
    <h2 style="color:#2C1810; margin:0 0 20px; font-size:1.15em;">
      ✓ Consentimento Registado
    </h2>

    <p style="color:#5C3D2E; line-height:1.7; margin:0 0 12px;">
      Ol&aacute; <strong>{{ $consent->name }}</strong>,
    </p>
    <p style="color:#5C3D2E; line-height:1.7; margin:0 0 24px;">
      O seu consentimento de tratamento de dados pessoais e autorização de tratamento est&eacute;tico
      foi registado com sucesso na Augusta Beauty Advisor.
    </p>

    {{-- Details box --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF8F0; border:1px solid #E8D5B7; border-radius:8px; margin-bottom:24px;">
    <tr><td style="padding:18px 20px;">
      <p style="margin:0 0 8px; color:#2C1810; font-size:0.9em;">
        <strong>Data:</strong> {{ $consent->consented_at->format('d/m/Y \à\s H:i') }}
      </p>
      <p style="margin:0 0 8px; color:#2C1810; font-size:0.9em;">
        <strong>Email registado:</strong> {{ $consent->email }}
      </p>
      <p style="margin:0; color:#2C1810; font-size:0.9em;">
        <strong>Refer&ecirc;ncia:</strong> #{{ $consent->id }}
      </p>
    </td></tr>
    </table>

    <p style="color:#5C3D2E; line-height:1.7; font-size:0.88em; margin:0 0 12px;">
      Os seus dados ser&atilde;o utilizados exclusivamente para a gest&atilde;o dos seus agendamentos e servi&ccedil;os.
      Pode exercer os seus direitos (acesso, rectifica&ccedil;&atilde;o, apagamento) atrav&eacute;s de
      <a href="mailto:info@augustaadviser.pt" style="color:#C4975A; text-decoration:none;">info@augustaadviser.pt</a>.
    </p>

    @if($consent->marketing_consent)
    <p style="color:#5C3D2E; line-height:1.7; font-size:0.88em; margin:0;">
      Aceitou tamb&eacute;m receber comunica&ccedil;&otilde;es de marketing com novidades e promo&ccedil;&otilde;es.
      Pode cancelar esta subscri&ccedil;&atilde;o a qualquer momento.
    </p>
    @endif
  </td></tr>

  {{-- Footer --}}
  <tr><td bgcolor="#F5EFE8" style="padding:16px 32px; text-align:center;">
    <p style="color:#8B6914; font-size:0.78em; margin:0;">
      Augusta Beauty Advisor &middot; Vila do Conde &middot;
      <a href="https://augustaadviser.pt" style="color:#8B6914;">augustaadviser.pt</a>
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>