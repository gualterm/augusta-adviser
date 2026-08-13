{{-- Cookie Consent Banner RGPD --}}
<div id="cookie-banner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:9999; background:#1a1a1a; color:#fff; padding:16px 24px; box-shadow:0 -2px 12px rgba(0,0,0,0.3);">
  <div style="max-width:900px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
    <p style="margin:0; font-size:14px; line-height:1.5; flex:1; min-width:220px;">
      Utilizamos cookies para melhorar a sua experiencia e analisar o trafego do site.
      Consulte a nossa <a href="/politica-de-cookies" style="color:#C9A96E; text-decoration:underline;">Politica de Cookies</a>.
    </p>
    <div style="display:flex; gap:10px; flex-shrink:0;">
      <button onclick="aceitarCookies()" style="background:#C9A96E; color:#fff; border:none; padding:9px 20px; border-radius:4px; cursor:pointer; font-size:14px; font-weight:600;">Aceitar</button>
      <button onclick="rejeitarCookies()" style="background:transparent; color:#ccc; border:1px solid #555; padding:9px 20px; border-radius:4px; cursor:pointer; font-size:14px;">Rejeitar</button>
    </div>
  </div>
</div>
<script>
(function() {
  function getCookie(n){var m=document.cookie.match(new RegExp('(^| )'+n+'=([^;]+)'));return m?m[2]:null;}
  function setCookie(n,v,d){var e=new Date();e.setTime(e.getTime()+d*86400000);document.cookie=n+'='+v+';expires='+e.toUTCString()+';path=/;SameSite=Lax';}
  window.aceitarCookies=function(){setCookie('cookie_consent','accepted',365);document.getElementById('cookie-banner').style.display='none';loadGA4();};
  window.rejeitarCookies=function(){setCookie('cookie_consent','rejected',365);document.getElementById('cookie-banner').style.display='none';};
  window.loadGA4=function(){if(window._ga4Loaded)return;window._ga4Loaded=true;var s=document.createElement('script');s.async=true;s.src='https://www.googletagmanager.com/gtag/js?id=G-WEPTTK7NM6';document.head.appendChild(s);window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}window.gtag=gtag;gtag('js',new Date());gtag('config','G-WEPTTK7NM6');};
  document.addEventListener('DOMContentLoaded',function(){var c=getCookie('cookie_consent');if(!c){document.getElementById('cookie-banner').style.display='block';}else if(c==='accepted'){loadGA4();}});
})();
</script>