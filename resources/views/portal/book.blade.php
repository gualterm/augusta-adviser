<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marcar Consulta | Augusta Adviser</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Montserrat',sans-serif;background:#faf8f5;color:#555;}
.container{max-width:600px;margin:auto;padding:40px 20px;}
h1{font-family:'Cormorant Garamond',serif;color:#6f5f54;font-size:30px;margin-bottom:20px;}
.back{display:inline-block;margin-bottom:20px;color:#7a6b5d;font-size:13px;text-decoration:none;}
.box{background:#fff;border-radius:20px;padding:28px;box-shadow:0 4px 14px rgba(0,0,0,.04);margin-bottom:16px;}
label{font-size:13px;color:#6f5f54;font-weight:500;display:block;margin-bottom:6px;margin-top:16px;}
label:first-child{margin-top:0;}
select,input[type=date],input[type=time]{width:100%;padding:11px 14px;border:1px solid #e0d8d0;border-radius:10px;font-size:14px;background:#faf8f5;}
select:focus,input:focus{outline:none;border-color:#cdb9a9;}
.error{color:#b85c5c;font-size:12.5px;margin-top:6px;}
.btn-primary{width:100%;padding:13px;background:#7a6b5d;color:#fff;border:none;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;margin-top:24px;display:block;}
.btn-primary:hover{opacity:.9;}
.btn-primary:disabled{opacity:.4;cursor:not-allowed;}
.btn-outline{width:100%;padding:12px;background:#fff;color:#7a6b5d;border:1.5px solid #cdb9a9;border-radius:40px;font-weight:600;font-size:14px;cursor:pointer;margin-top:10px;display:block;}
.btn-outline:hover{background:#faf8f5;}
.slots{display:flex;flex-wrap:wrap;gap:8px;}
.slot{padding:8px 15px;border:1.5px solid #cdb9a9;border-radius:30px;font-size:13px;cursor:pointer;background:#fff;color:#6f5f54;}
.slot:hover,.slot.selected{background:#7a6b5d;border-color:#7a6b5d;color:#fff;}

/* Sugestão */
.suggestion{display:none;border:1.5px solid #cdb9a9;border-radius:16px;padding:20px 24px;background:#fdf9f6;}
.suggestion.exact{border-color:#a8c5a0;background:#f5fdf4;}
.suggestion .tag{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;}
.suggestion.exact .tag{color:#5a8a52;}
.suggestion:not(.exact) .tag{color:#b8963e;}
.suggestion .time{font-family:'Cormorant Garamond',serif;font-size:32px;color:#4a3f37;margin-bottom:4px;}
.suggestion .detail{font-size:13px;color:#9b8a7c;}
.loading{font-size:13px;color:#9b8a7c;margin-top:12px;display:none;}
</style>
</head>
<body>
<div class="container">
<a href="{{ route('portal.dashboard') }}" class="back">&larr; Voltar à minha conta</a>
<h1>Marcar Consulta</h1>

<!-- Passo 1: escolha -->
<div class="box">
  <label for="service_id">Serviço</label>
  <select id="service_id">
    <option value="" disabled selected>Selecione...</option>
    @foreach($services as $category => $categoryServices)
    <optgroup label="{{ $category }}">
    @foreach($categoryServices as $service)
    <option value="{{ $service->id }}" data-duration="{{ $service->duration_minutes }}">
      {{ $service->name }} — € {{ number_format($service->price, 2, ',', '.') }} ({{ $service->duration_minutes }} min)
    </option>
    @endforeach
    </optgroup>
    @endforeach
  </select>

  <label for="pref_date">Data pretendida</label>
  <input type="date" id="pref_date" min="{{ date('Y-m-d') }}">

  <label for="pref_time">Hora preferida</label>
  <input type="time" id="pref_time" value="10:00">

  <div class="loading" id="loading">A verificar disponibilidade...</div>
  <button class="btn-primary" id="checkBtn" onclick="checkSlot()" disabled>Ver Disponibilidade</button>
</div>

<!-- Sugestão -->
<div class="suggestion" id="suggestion">
  <div class="tag" id="suggTag"></div>
  <div class="time" id="suggTime"></div>
  <div class="detail" id="suggDetail"></div>

  <form method="POST" action="{{ route('portal.book.store') }}" id="bookForm">
    @csrf
    <input type="hidden" name="service_id" id="form_service">
    <input type="hidden" name="appointment_date" id="form_date">
    <input type="hidden" name="appointment_time" id="form_time">
    <button type="submit" class="btn-primary">✓ Confirmar esta marcação</button>
  </form>
  <button class="btn-outline" id="moreBtn" onclick="loadMoreSlots()" style="display:none">Ver mais opções neste dia</button>
  <div id="more-slots" style="margin-top:14px;display:none;">
    <div style="font-size:12px;color:#9b8a7c;margin-bottom:8px;">Outros horários disponíveis:</div>
    <div class="slots" id="more-slots-list" style="display:flex;flex-wrap:wrap;gap:8px;"></div>
  </div>
  <button class="btn-outline" onclick="resetForm()" style="margin-top:10px">Escolher outra data / hora</button>
</div>
<div id="hint-msg" style="display:none;margin-top:12px;padding:10px 16px;background:#fff8ee;border:1px solid #e8d5a3;border-radius:12px;font-size:13px;color:#7a6040;">
  ☝️ Altera a data ou hora pretendida nos campos acima e clica novamente em <strong>Ver Disponibilidade</strong>.
</div>

@error('appointment_time')
<div class="box" style="border:1.5px solid #e0aaaa;">
  <div class="error" style="font-size:14px;">{{ $message }}</div>
</div>
@enderror

</div>
<script>
const serviceEl  = document.getElementById('service_id');
const dateEl     = document.getElementById('pref_date');
const timeEl     = document.getElementById('pref_time');
const checkBtn   = document.getElementById('checkBtn');
const loading    = document.getElementById('loading');
const suggestion = document.getElementById('suggestion');

function updateBtn(){
  checkBtn.disabled = !(serviceEl.value && dateEl.value && timeEl.value);
}
function hideHint(){ document.getElementById('hint-msg').style.display = 'none'; }
serviceEl.addEventListener('change', () => { updateBtn(); hideHint(); });
dateEl.addEventListener('change',    () => { updateBtn(); hideHint(); });
timeEl.addEventListener('change',    () => { updateBtn(); hideHint(); });

function checkSlot(){
  loading.style.display = 'block';
  checkBtn.disabled = true;
  suggestion.style.display = 'none';
  document.getElementById('hint-msg').style.display = 'none';

  fetch(`/portal/suggest-slot?service_id=${serviceEl.value}&date=${dateEl.value}&preferred_time=${timeEl.value}`)
    .then(r => r.json())
    .then(data => {
      loading.style.display = 'none';
      if (!data.slot) {
        suggestion.className = 'suggestion';
        suggestion.style.display = 'block';
        document.getElementById('suggTag').textContent = 'Sem disponibilidade';
        document.getElementById('suggTime').textContent = 'Não há horários disponíveis neste dia.';
        document.getElementById('suggDetail').textContent = 'Por favor escolhe outra data.';
        document.getElementById('bookForm').style.display = 'none';
        return;
      }
      suggestion.className = data.exact ? 'suggestion exact' : 'suggestion';
      suggestion.style.display = 'block';
      document.getElementById('suggTag').textContent = data.exact ? '✓ Horário disponível' : 'Sugestão mais próxima';
      document.getElementById('suggTime').textContent = data.slot;

      // Format date nicely
      const [y,m,d] = dateEl.value.split('-');
      const months = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
      document.getElementById('suggDetail').textContent = `${d} ${months[parseInt(m)-1]} ${y}`;

      document.getElementById('form_service').value = serviceEl.value;
      document.getElementById('form_date').value    = dateEl.value;
      document.getElementById('form_time').value    = data.slot;
      document.getElementById('bookForm').style.display = 'block';
      document.getElementById('moreBtn').style.display = 'block';
      document.getElementById('more-slots').style.display = 'none';
      document.getElementById('more-slots-list').innerHTML = '';
      checkBtn.disabled = false;
    })
    .catch(() => {
      loading.style.display = 'none';
      checkBtn.disabled = false;
      alert('Erro ao verificar disponibilidade. Tenta novamente.');
    });
}

function loadMoreSlots(){
  const moreList = document.getElementById('more-slots-list');
  const moreDiv  = document.getElementById('more-slots');
  moreList.innerHTML = '<span style="font-size:13px;color:#9b8a7c">A carregar...</span>';
  moreDiv.style.display = 'block';
  document.getElementById('moreBtn').style.display = 'none';

  fetch(`/portal/available-slots?service_id=${serviceEl.value}&date=${dateEl.value}`)
    .then(r => r.json())
    .then(slots => {
      moreList.innerHTML = '';
      if (!slots.length) {
        moreList.innerHTML = '<span style="font-size:13px;color:#b85c5c">Sem mais horários disponíveis.</span>';
        return;
      }
      slots.forEach(slot => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'slot';
        btn.textContent = slot;
        btn.onclick = () => {
          document.querySelectorAll('.slot').forEach(s => s.classList.remove('selected'));
          btn.classList.add('selected');
          document.getElementById('form_time').value = slot;
          document.getElementById('suggTime').textContent = slot;
          suggestion.className = 'suggestion exact';
          document.getElementById('suggTag').textContent = '✓ Horário selecionado';
        };
        moreList.appendChild(btn);
      });
    });
}

function resetForm(){
  suggestion.style.display = 'none';
  document.getElementById('more-slots').style.display = 'none';
  document.getElementById('hint-msg').style.display = 'block';
  checkBtn.disabled = false;
  window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>
</body>
</html>
