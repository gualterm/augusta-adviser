<!DOCTYPE html>
<html lang="pt">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;padding:20px;">
  <div style="border-bottom:3px solid #D97706;padding-bottom:12px;margin-bottom:24px;">
    <h2 style="color:#D97706;margin:0;">Augusta Adviser</h2>
  </div>
  <p>Estimado/a {{ $inquiry->name }},</p>
  <p>Obrigado pelo seu contacto. Em resposta ao seu inquérito, informamos o seguinte:</p>
  <div style="background:#fafafa;border-left:4px solid #D97706;padding:16px 20px;margin:20px 0;border-radius:0 4px 4px 0;white-space:pre-line;">{{ $responseText }}</div>
  <p>Ficamos à disposição para qualquer esclarecimento adicional.</p>
  <p>Com os melhores cumprimentos,<br><strong>Augusta Adviser</strong></p>
  <hr style="border:none;border-top:1px solid #eee;margin:28px 0;">
  <p style="font-size:12px;color:#aaa;">Esta mensagem é uma resposta ao seu inquérito de {{ $inquiry->created_at->format('d/m/Y') }}.</p>
</body>
</html>
