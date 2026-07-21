<x-filament-panels::page>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
.qr-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
.qr-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; text-align: center; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.qr-card h3 { font-size: .95em; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
.qr-card .qr-url { font-size: .7em; font-family: monospace; color: #6b7280; background: #f9fafb; padding: 3px 8px; border-radius: 5px; margin-bottom: 14px; display: inline-block; word-break: break-all; }
.qr-wrap { display: flex; justify-content: center; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 14px; min-height: 220px; align-items: center; }
.qr-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
.qr-btn { padding: 7px 14px; border: none; border-radius: 7px; cursor: pointer; font-size: .78em; font-weight: 600; transition: opacity .15s; }
.qr-btn:hover { opacity: .82; }
.qr-btn-dl  { background: #d97706; color: #fff; }
.qr-btn-dl2 { background: #374151; color: #fff; }
.qr-btn-cp  { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
.qr-btn-cp.ok { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
</style>

<div class="qr-grid" id="qr-grid"></div>

<script>
const QRS = [
  { id:"website", icon:"🌐", title:"Website Principal",   url:"https://augustaadviser.pt" },
  { id:"registo", icon:"📝", title:"Registo de Cliente",  url:"https://augustaadviser.pt/portal/registo" },
  { id:"rgpd",    icon:"🔒", title:"Consentimento RGPD",  url:"https://augustaadviser.pt/consentimento/" },
  { id:"login",   icon:"🔑", title:"Login Portal Cliente",url:"https://augustaadviser.pt/portal/login" },
];
const grid = document.getElementById("qr-grid");
QRS.forEach(q => {
  const d = document.createElement("div");
  d.className = "qr-card";
  d.innerHTML = `<div style="font-size:1.5em;margin-bottom:6px">${q.icon}</div>
    <h3>${q.title}</h3>
    <div class="qr-url">${q.url}</div>
    <div class="qr-wrap"><div id="qr-${q.id}"></div></div>
    <div class="qr-actions">
      <button class="qr-btn qr-btn-dl"  onclick="dl('${q.id}','${q.url}',512)">⬇ PNG 512</button>
      <button class="qr-btn qr-btn-dl2" onclick="dl('${q.id}','${q.url}',1024)">⬇ PNG 1024</button>
      <button class="qr-btn qr-btn-cp"  id="cp-${q.id}" onclick="cp('${q.url}','${q.id}')">📋 Copiar link</button>
    </div>`;
  grid.appendChild(d);
  new QRCode(document.getElementById("qr-"+q.id),{text:q.url,width:200,height:200,colorDark:"#1f2937",colorLight:"#ffffff",correctLevel:QRCode.CorrectLevel.H});
});
function dl(id,url,sz){
  const t=document.createElement("div");t.style.display="none";document.body.appendChild(t);
  new QRCode(t,{text:url,width:sz,height:sz,colorDark:"#1f2937",colorLight:"#ffffff",correctLevel:QRCode.CorrectLevel.H});
  setTimeout(()=>{const c=t.querySelector("canvas");if(c){const a=document.createElement("a");a.download="augusta_qr_"+id+"_"+sz+".png";a.href=c.toDataURL("image/png");a.click();}document.body.removeChild(t);},300);
}
function cp(url,id){
  navigator.clipboard.writeText(url).then(()=>{
    const b=document.getElementById("cp-"+id);b.textContent="✓ Copiado!";b.classList.add("ok");
    setTimeout(()=>{b.textContent="📋 Copiar link";b.classList.remove("ok");},2000);
  });
}
</script>
</x-filament-panels::page>
