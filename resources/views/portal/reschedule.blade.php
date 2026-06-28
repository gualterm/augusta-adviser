<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Remarcar Consulta — Augusta Adviser</title>
<style>
  body{font-family:'Georgia',serif;background:#f5f0eb;margin:0;padding:20px;}
  .container{max-width:480px;margin:0 auto;}
  h1{font-size:24px;color:#3d2f25;margin-bottom:6px;}
  .back{font-size:13px;color:#7a6b5d;text-decoration:none;display:block;margin-bottom:20px;}
  .card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-bottom:16px;}
  label{display:block;font-size:13px;color:#6f5f54;margin-bottom:6px;font-weight:500;}
  input[type=date],input[type=time]{width:100%;padding:12px 14px;border:1px solid #e0d8d0;border-radius:10px;font-size:15px;background:#faf8f5;box-sizing:border-box;font-family:inherit;}
  .btn-primary{width:100%;padding:15px;background:#5c4a3a;color:#fff;border:none;border-radius:12px;font-size:16px;cursor:pointer;margin-top:16px;font-family:inherit;}
  .btn-outline{width:100%;padding:13px;background:#fff;color:#7a6b5d;border:1px solid #cdb9a9;border-radius:12px;font-size:15px;cursor:pointer;margin-top:10px;font-family:inherit;text-align:center;display:block;text-decoration:none;}
  .info-box{background:#f5f0eb;border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:14px;color:#3d2f25;}
  .info-box strong{display:block;margin-bottom:4px;}
  .slot-box{background:#f0faf0;border:1px solid #b8ddb8;border-radius:12px;padding:16px;margin-top:16px;display:none;}
  .slot-time{font-size:32px;font-weight:bold;color:#3d2f25;}
  .slot-detail{font-size:14px;color:#5a8a52;margin-top:2px;}
  .error{color:#c0392b;font-size:13px;margin-top:8px;}
  .notice{background:#fff3cd;border-radius:8px;padding:10px 14px;font-size:13px;color:#856404;margin-bottom:16px;}
</style>
</head>
<body>
<div class="container">
  <a href="{{ route('portal.dashboard') }}" class="back">← Voltar à minha conta</a>
  <h1>Remarcar Consulta</h1>

  <div class="notice">⚠️ Só é possível remarcar uma vez. Após confirmar, esta opção deixa de estar disponível.</div>

  <div class="info-box">
    <strong>Serviço atual</strong>
    {{ $appointment->service?->name }} — {{ $appointment->appointment_date->format('d/m/Y') }} às {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('H:i') }}
  </div>

  @if($errors->any())
    <div class="error">{{ $errors->first() }}</div>
  @endif

  <div class="card">
    <div style="margin-bottom:16px;">
      <label>Nova data pretendida</label>
      <input type="date" id="date" min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ old('date', date('Y-m-d', strtotime('+1 day'))) }}">
    </div>
    <div>
      <label>Hora preferida</label>
      <input type="time" id="time" value="{{ old('preferred_time', '10:00') }}" step="1800">
    </div>
    <button class="btn-primary" onclick="checkSlot()">Ver Disponibilidade</button>
  </div>

  <div class="slot-box" id="slotBox">
    <div class="slot-time" id="slotTime"></div>
    <div class="slot-detail" id="slotDetail"></div>
    <form method="POST" action="{{ route('portal.reschedule.save', $appointment->id) }}">
      @csrf
      <input type="hidden" name="appointment_date" id="form_date">
      <input type="hidden" name="appointment_time" id="form_time">
      <button type="submit" class="btn-primary">✓ Confirmar remarcação</button>
    </form>
    <a href="#" class="btn-outline" onclick="document.getElementById('slotBox').style.display='none';return false;">Escolher outra data / hora</a>
  </div>
</div>

<script>
function checkSlot() {
  var date = document.getElementById('date').value;
  var time = document.getElementById('time').value;
  if (!date || !time) { alert('Preenche a data e a hora.'); return; }
  var params = new URLSearchParams({service_id: {{ $appointment->service_id }}, date: date, preferred_time: time});
  fetch('/portal/suggest-slot?' + params, {method: 'GET'})
  .then(r => r.json())
  .then(data => {
    if (!data.slot) { alert('Sem disponibilidade nessa data. Tenta outra.'); return; }
    var parts = data.slot.split(':');
    var d = date.split('-');
    var months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
    var empName = data.employee_name ? ' · ' + data.employee_name : '';
    if (data.secondary_employee_name) empName += ' & ' + data.secondary_employee_name;
    document.getElementById('slotTime').textContent = parts[0] + ':' + parts[1];
    document.getElementById('slotDetail').textContent = parseInt(d[2]) + ' ' + months[parseInt(d[1])-1] + ' ' + d[0] + empName;
    document.getElementById('form_date').value = date;
    document.getElementById('form_time').value = data.slot;
    document.getElementById('slotBox').style.display = 'block';
  });
}
</script>
</body>
</html>