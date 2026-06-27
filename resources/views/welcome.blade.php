<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Augusta Adviser | Consultoria de Imagem &amp; Estética</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
}
html{
scroll-behavior:smooth;
}
body{
font-family:'Montserrat',sans-serif;
background:#faf8f5;
color:#555;
line-height:1.7;
overflow-x:hidden;
}
img{
max-width:100%;
display:block;
}
img.zoomable{
cursor:zoom-in;
}
.login-badge{
position:fixed;
top:18px;
right:24px;
z-index:50;
display:flex;
align-items:center;
gap:6px;
background:rgba(255,255,255,.9);
backdrop-filter:blur(4px);
padding:9px 16px;
border-radius:30px;
text-decoration:none;
color:#6f5f54;
font-size:13px;
font-weight:600;
box-shadow:0 4px 14px rgba(0,0,0,.08);
}
.login-badge:hover{background:#fff;}
@media(max-width:900px){
.login-badge{
top:10px;
right:10px;
padding:7px 12px;
font-size:12px;
}
}
.container{
max-width:1100px;
margin:auto;
padding:0 32px;
}
h1,h2,h3{
font-family:'Cormorant Garamond',serif;
font-weight:600;
color:#6f5f54;
}
section{
padding:22px 0;
}
.section-alt{
background:#f6f0eb;
}
.btn{
display:inline-block;
padding:14px 30px;
background:#7a6b5d;
color:#fff;
text-decoration:none;
border-radius:40px;
transition:.3s;
}
.btn:hover{
opacity:.92;
transform:translateY(-2px);
}
.btn-primary{
background:#cdb9a9;
color:#4a3f37;
font-weight:600;
}
.hero{
background:#f8f4f0;
padding:20px 0 0;
}
.hero-grid{
display:grid;
grid-template-columns:38% 62%;
align-items:center;
gap:20px;
}
.hero-image{
position:relative;
height:360px;
}
.hero-image img{
width:100%;
height:100%;
object-fit:contain;
object-position:top center;
display:block;
}
.hero-image::before{
content:'';
position:absolute;
inset:0;
background:
linear-gradient(to right, #f8f4f0 0%, transparent 18%, transparent 52%, #f8f4f0 88%),
linear-gradient(to bottom, #f8f4f0 0%, transparent 20%),
linear-gradient(to top, #f8f4f0 0%, transparent 20%);
pointer-events:none;
z-index:1;
}
.hero-content{
max-width:560px;
margin-left:0;
}
.hero-logo{
width:346px;
margin-bottom:14px;
opacity:.95;
}
.hero h1{
font-size:24px;
line-height:1.05;
margin-bottom:8px;
white-space:nowrap;
}
.hero-subtitle{
font-size:15px;
margin-bottom:8px;
color:#7a6b5d;
}
.hero-tag{
font-size:11px;
letter-spacing:2px;
text-transform:uppercase;
color:#9b8a7c;
margin-bottom:12px;
}
.hero-slogan{
font-size:13px;
font-style:italic;
margin-bottom:20px;
color:#74675c;
}
.hero-content .btn{
width:346px;
text-align:center;
padding:12px 20px;
font-size:14px;
}
.section-title{
font-size:30px;
text-align:center;
margin-bottom:10px;
}
.divider{
width:80px;
height:2px;
background:#cdb9a9;
margin:0 auto 16px;
}
.section-intro{
max-width:900px;
margin:auto;
text-align:center;
font-size:16px;
}
.services{
display:grid;
grid-template-columns:repeat(6,1fr);
gap:18px;
margin-top:40px;
}
.service-card{
background:#fff;
border-radius:18px;
overflow:hidden;
box-shadow:0 8px 20px rgba(0,0,0,.06);
transition:.3s;
}
.service-card:hover{
transform:translateY(-4px);
}
.service-card img{
width:100%;
height:150px;
object-fit:cover;
}
.service-card-content{
padding:18px;
text-align:center;
}
.service-card-content h3{
font-size:22px;
margin-bottom:10px;
}
.service-card-content p{
font-size:14px;
}
.about{
display:grid;
grid-template-columns:42% 58%;
gap:50px;
align-items:center;
}
.about-photo{
position:relative;
}
.about-photo img{
width:100%;
max-height:520px;
object-fit:cover;
object-position:center 15%;
border-radius:25px;
}
.about-photo:after{
content:'';
position:absolute;
left:0;
right:0;
bottom:0;
height:90px;
background:linear-gradient(
to bottom,
rgba(250,248,245,0),
rgba(250,248,245,1)
);
}
.about-text h2{
font-size:37px;
margin-bottom:20px;
}
.about-text p{
margin-bottom:16px;
font-size:16px;
}
.gallery{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:16px;
margin-top:22px;
}
.gallery-card{
position:relative;
overflow:hidden;
border-radius:18px;
}
.gallery-card img{
width:100%;
height:190px;
object-fit:cover;
transition:.4s;
}
.gallery-card:hover img{
transform:scale(1.04);
}
.gallery-card:after{
content:'';
position:absolute;
left:0;
right:0;
bottom:0;
height:90px;
background:linear-gradient(
to bottom,
rgba(0,0,0,0),
rgba(0,0,0,.35)
);
}
.training-section{
background:#f6f0eb;
border-top:4px solid #cdb9a9;
border-bottom:4px solid #cdb9a9;
}
.training-wrapper{
display:grid;
grid-template-columns:40% 60%;
gap:50px;
align-items:center;
}
.training-photo{
position:relative;
}
.training-photo img{
width:100%;
max-height:420px;
object-fit:cover;
border-radius:25px;
}
.training-text ul{
padding-left:25px;
margin-top:20px;
}
.training-text li{
margin-bottom:10px;
}
.experience-grid{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin-top:40px;
}
.experience-card{
background:#fff;
padding:28px 24px;
border-radius:20px;
text-align:center;
box-shadow:0 8px 20px rgba(0,0,0,.05);
border-top:4px solid #cdb9a9;
}
.experience-card h3{
font-size:24px;
margin-bottom:10px;
}
.instagram-section{
background:#fff;
}
.contact-box{
background:#f2ebe6;
padding:38px;
border-radius:30px;
text-align:center;
max-width:1100px;
margin:auto;
}
.contact-box h2{
font-size:42px;
margin-bottom:14px;
}
.contact-box p{
margin-bottom:12px;
}
.contact-actions{
margin-top:10px;
display:flex;
flex-wrap:wrap;
justify-content:center;
gap:14px;
}
.contact-info-grid{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin:24px 0;
}
.contact-info-grid p{
margin:0;
font-size:15px;
}
@media(max-width:900px){
.contact-info-grid{
grid-template-columns:repeat(2,1fr);
}
}
.como-chegar-grid{
display:grid;
grid-template-columns:1fr 1.1fr;
gap:35px;
align-items:center;
margin-top:30px;
}
.map-container{
border-radius:25px;
overflow:hidden;
box-shadow:0 8px 25px rgba(0,0,0,.06);
}
iframe{
width:100%;
height:300px;
border:0;
}
.footer{
background:#efe5de;
padding:60px 0 35px;
text-align:center;
margin-top:50px;
}
.footer-logo{
width:260px;
margin:0 auto 30px;
display:block;
opacity:.9;
}
.footer-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:40px;
max-width:700px;
margin:0 auto 30px;
text-align:center;
}
.footer-grid h4{
font-family:'Cormorant Garamond',serif;
color:#6f5f54;
font-size:20px;
margin-bottom:10px;
}
.footer-grid p{
font-size:15px;
margin-bottom:6px;
}
.footer-bottom{
font-size:14px;
opacity:.8;
}
.inquiry-success{
background:#e8f3ea;
color:#3c6b4a;
padding:16px 20px;
border-radius:14px;
margin-bottom:24px;
text-align:center;
max-width:1100px;
margin-left:auto;
margin-right:auto;
}
.inquiry-form{
max-width:700px;
margin:30px auto 0;
display:grid;
gap:16px;
}
.inquiry-row{
display:grid;
grid-template-columns:1fr 1fr;
gap:16px;
}
.inquiry-form label{
font-size:14px;
color:#6f5f54;
margin-bottom:6px;
display:block;
font-weight:500;
}
.inquiry-form input,
.inquiry-form select,
.inquiry-form textarea{
width:100%;
padding:12px 16px;
border:1px solid #e0d8d0;
border-radius:12px;
font-family:'Montserrat',sans-serif;
font-size:15px;
color:#555;
background:#faf8f5;
}
.inquiry-form input:focus,
.inquiry-form select:focus,
.inquiry-form textarea:focus{
outline:none;
border-color:#cdb9a9;
}
.inquiry-form .field-error{
color:#b85c5c;
font-size:13px;
margin-top:4px;
}
.inquiry-form button{
border:none;
cursor:pointer;
justify-self:center;
}
.lightbox-overlay{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(20,16,14,.92);
z-index:9999;
align-items:center;
justify-content:center;
padding:30px;
cursor:zoom-out;
}
.lightbox-overlay.active{
display:flex;
}
.lightbox-overlay img{
max-width:92vw;
max-height:92vh;
width:auto;
object-fit:contain;
border-radius:14px;
box-shadow:0 20px 60px rgba(0,0,0,.5);
cursor:default;
}
.lightbox-close{
position:absolute;
top:24px;
right:32px;
color:#fff;
font-size:38px;
line-height:1;
cursor:pointer;
opacity:.85;
}
.lightbox-close:hover{
opacity:1;
}
@media(max-width:1200px){
.services{
grid-template-columns:repeat(3,1fr);
}
.experience-grid{
grid-template-columns:repeat(2,1fr);
}
}
@media(max-width:900px){
.hero-grid{
grid-template-columns:1fr;
}
.hero-image{
order:2;
height:320px;
}
.hero-image img{
width:100%;
height:100%;
object-fit:contain;
object-position:top center;
}
.hero-content{
order:1;
margin:auto;
text-align:center;
padding:16px 10px 0;
}
.hero h1{
font-size:26px;
white-space:normal;
}
.hero-logo{
width:160px;
margin:0 auto 12px;
}
.about{
grid-template-columns:1fr;
}
.about-photo img{
max-height:300px;
}
.training-wrapper{
grid-template-columns:1fr;
}
.training-photo img{
max-height:280px;
}
.gallery{
grid-template-columns:1fr;
}
.gallery-card img{
height:170px;
}
.services{
grid-template-columns:repeat(2,1fr);
}
.experience-grid{
grid-template-columns:1fr;
}
.contact-box{
padding:30px 22px;
}
.section-title{
font-size:36px;
}
.como-chegar-grid{
grid-template-columns:1fr;
}
.footer-grid{
grid-template-columns:1fr;
text-align:center;
}
}
</style>
</head>
<body>
<a href="/portal/login" class="login-badge">&#128100; Entrar / Marcar Online</a>
<a href="/admin" class="login-badge" style="top:62px;font-size:11px;padding:6px 14px;opacity:0.7;">&#9881;&#65039; Admin</a>
<section class="hero">
<div class="container">
<div class="hero-grid">
<div class="hero-image">
<img
src="/images/martahero.png"
alt="Marta Macedo"
class="zoomable"
onclick="openLightbox(this.src)">
</div>
<div class="hero-content">
<img
src="/images/logoaugusta-1a.png"
class="hero-logo"
alt="Augusta Adviser">
<div class="hero-tag">
Rosto • Corpo • Beleza Personalizada
</div>
<h1>
Marta Macedo
</h1>
<div class="hero-subtitle">
Consultoria de Imagem &amp; Estética
</div>
<div class="hero-slogan">
Evoluímos todos os dias para cuidar melhor de si.
</div>
<a href="/portal/marcar" class="btn btn-primary">
Agendar Consulta
</a>
</div>
</div>
</div>
</section>
<section>
<div class="container">
<h2 class="section-title">
Consultoria de Imagem
</h2>
<div class="divider"></div>
<div class="section-intro">
A Augusta Adviser combina consultoria de imagem,
estética e bem-estar, ajudando cada cliente a valorizar
a sua imagem, autoestima e confiança através de um
acompanhamento personalizado.
</div>
</div>
</section>
<div style="text-align:center;padding:1.2rem 1rem;background:#ede8e1;border-top:1px solid #d9d0c5;border-bottom:1px solid #d9d0c5;">
    <span style="font-family:Georgia,serif;font-size:.88rem;color:#7a6152;letter-spacing:.3px;">
        🎁 @if(auth()->check()) <a href="/portal/promotions" style="color:#5a3e2b;text-decoration:none;border-bottom:1px solid #b8a090;">Ver as nossas promoções exclusivas</a> @else <a href="/portal/login" style="color:#5a3e2b;text-decoration:none;border-bottom:1px solid #b8a090;">Clientes registados — descubra as nossas promoções exclusivas</a> @endif
    </span>
</div>

<section>
<div class="container">
<h2 class="section-title">
Serviços
</h2>
<div class="divider"></div>
<div class="services">
<div class="service-card">
<img src="/images/DB203744.jpg" class="zoomable" onclick="openLightbox(this.src)">
<div class="service-card-content">
<h3>Consultoria</h3>
<p>
Análise de imagem, valorização pessoal
e construção de confiança.
</p>
</div>
</div>
<div class="service-card">
<img src="/images/DB203881.jpg" class="zoomable" onclick="openLightbox(this.src)">
<div class="service-card-content">
<h3>Aconselhamento</h3>
<p>
Orientação personalizada para objetivos
de imagem e bem-estar.
</p>
</div>
</div>
<div class="service-card">
<img src="/images/DB204013.jpg" class="zoomable" onclick="openLightbox(this.src)">
<div class="service-card-content">
<h3>Imagem Pessoal</h3>
<p>
Valorização da identidade e estilo
de cada cliente.
</p>
</div>
</div>
<div class="service-card">
<img src="/images/IMG_1107.jpeg" class="zoomable" onclick="openLightbox(this.src)">
<div class="service-card-content">
<h3>Bem-Estar</h3>
<p>
Momentos de relaxamento e equilíbrio
num ambiente acolhedor.
</p>
</div>
</div>
<div class="service-card">
<img src="/images/pedicure.png" class="zoomable" onclick="openLightbox(this.src)">
<div class="service-card-content">
<h3>Pedicure</h3>
<p>
Cuidado, conforto e valorização
da imagem pessoal.
</p>
</div>
</div>
<div class="service-card">
<img src="/images/unhasjaponesa2.png" class="zoomable" onclick="openLightbox(this.src)">
<div class="service-card-content">
<h3>Unhas Japonesas</h3>
<p>
Brilho natural, fortalecimento e
saúde das unhas.
</p>
</div>
</div>
</div>
</div>
</section>
<section>
<div class="container">
<div class="about">
<div class="about-photo">
<img
src="/images/Marta3.png"
alt="Marta Macedo"
class="zoomable"
onclick="openLightbox(this.src)">
</div>
<div class="about-text">
<h2>
Sobre Marta
</h2>
<p>
Marta Macedo é Consultora de Imagem e Beauty Advisor,
dedicada à valorização da imagem, bem-estar e confiança
de cada cliente.
</p>
<p>
Formação em Dermocosmética, Laser Díodo,
HIFU, Microagulhamento, Aparatologia Estética,
SPA &amp; Bem-Estar e Consultoria de Imagem.
</p>
<p>
Conta ainda com experiência profissional em unidades
de referência como o Crowne Plaza Porto,
Wine &amp; Books Porto, Catalonia Porto
e Oca Douro Valley Hotel &amp; Spa.
</p>
<p>
Acredita que cada pessoa é única e que a beleza deve
ser trabalhada de forma personalizada, respeitando a
identidade e os objetivos de cada cliente.
</p>
</div>
</div>
</div>
</section>
<section class="section-alt">
<div class="container">
<h2 class="section-title">
Espaço Augusta
</h2>
<div class="divider"></div>
<div class="section-intro">
Um espaço pensado para proporcionar conforto,
tranquilidade e uma experiência personalizada,
onde cada detalhe foi preparado para o seu bem-estar.
</div>
<div class="gallery">
<div class="gallery-card">
<img src="/images/IMG_1133.jpeg" class="zoomable" onclick="openLightbox(this.src)">
</div>
<div class="gallery-card">
<img src="/images/IMG_1104.jpeg" class="zoomable" onclick="openLightbox(this.src)">
</div>
<div class="gallery-card">
<img src="/images/IMG_1123.jpeg" class="zoomable" onclick="openLightbox(this.src)">
</div>
</div>
</div>
</section>

<section class="training-section">
<div class="container">
<div class="training-wrapper">
<div class="training-photo">
<img
src="/images/IMG_1128.jpeg"
alt="Formação Augusta Adviser"
class="zoomable"
onclick="openLightbox(this.src)">
</div>
<div class="training-text">
<h2>
Formação Contínua
</h2>
<p>
A Augusta Adviser acredita na atualização
permanente de conhecimentos e técnicas,
acompanhando a evolução do setor da beleza,
estética e bem-estar.
</p>
<ul>
<li>Consultoria de Imagem</li>
<li>Dermocosmética</li>
<li>Laser Díodo</li>
<li>HIFU</li>
<li>Microagulhamento</li>
<li>Aparatologia Estética</li>
<li>SPA &amp; Bem-Estar</li>
</ul>
</div>
</div>
</div>
</section>
<section class="section-alt">
<div class="container">
<h2 class="section-title">
Experiência Profissional
</h2>
<div class="divider"></div>
<div class="section-intro">
Ao longo do seu percurso profissional, Marta Macedo
desenvolveu competências em estética, bem-estar,
atendimento premium e acompanhamento personalizado
em unidades de referência.
</div>
<div class="experience-grid">
<div class="experience-card">
<h3>
Crowne Plaza Porto
</h3>
<p>
Experiência em ambiente hoteleiro premium,
com foco na excelência de serviço.
</p>
</div>
<div class="experience-card">
<h3>
Wine &amp; Books Porto
</h3>
<p>
Bem-estar, estética e acompanhamento
personalizado ao cliente.
</p>
</div>
<div class="experience-card">
<h3>
Catalonia Porto
</h3>
<p>
Experiência em serviços de hospitalidade,
spa e bem-estar.
</p>
</div>
<div class="experience-card">
<h3>
Oca Douro Valley Hotel &amp; Spa
</h3>
<p>
Integração de técnicas de estética,
relaxamento e wellness.
</p>
</div>
</div>
</div>
</section>
<section class="instagram-section">
<div class="container">
<h2 class="section-title">
Momentos Augusta
</h2>
<div class="divider"></div>
<div class="section-intro">
Alguns momentos do espaço, tratamentos
e ambiente Augusta Adviser.
</div>
<div class="gallery">
<div class="gallery-card">
<img src="/images/Martaesteticaimagem.png" class="zoomable" onclick="openLightbox(this.src)">
</div>
<div class="gallery-card">
<img src="/images/Martaesteticaeimagem2.png" class="zoomable" onclick="openLightbox(this.src)">
</div>
<div class="gallery-card">
<img src="/images/bemestar2.png" class="zoomable" onclick="openLightbox(this.src)">
</div>
</div>
</div>
</section>
<section class="section-alt">
<div class="container">
<h2 class="section-title">
Tecnologia &amp; Tratamentos
</h2>
<div class="divider"></div>
<div class="section-intro">
A Augusta Adviser acompanha a evolução da estética
moderna através de equipamentos e técnicas
selecionadas para proporcionar conforto,
segurança e resultados personalizados.
</div>
<div class="gallery">
<div class="gallery-card">
<img src="/images/pedicure.png" class="zoomable" onclick="openLightbox(this.src)">
</div>
<div class="gallery-card">
<img src="/images/unhasjaponesa2.png" class="zoomable" onclick="openLightbox(this.src)">
</div>
<div class="gallery-card">
<img src="/images/bemestar.png" class="zoomable" onclick="openLightbox(this.src)">
</div>
</div>
</div>
</section>
<section id="localizacao">
<div class="container">
<h2 class="section-title">
Como Chegar
</h2>
<div class="divider"></div>
<div class="como-chegar-grid">
<div class="section-intro" style="text-align:left;margin:0;">
Augusta Adviser<br>
Avenida Júlio Saúl Dias nº 191<br>
4480-673 Vila do Conde
<br><br>
<a
href="https://maps.google.com/?q=Avenida+Júlio+Saúl+Dias+191+Vila+do+Conde"
target="_blank"
class="btn">
Abrir no Google Maps
</a>
</div>
<div class="map-container">
<iframe
src="https://maps.google.com/maps?q=Avenida%20Júlio%20Saúl%20Dias%20191%20Vila%20do%20Conde&t=&z=15&ie=UTF8&iwloc=&output=embed">
</iframe>
</div>
</div>
</div>
</section>
<section id="contactos">
<div class="container">
<div class="contact-box">
<h2>
Marque a Sua Consulta
</h2>
<p>
Cada pessoa é única.
A Augusta Adviser disponibiliza um acompanhamento
personalizado para ajudar a valorizar a sua imagem,
bem-estar e confiança.
</p>
<div class="contact-info-grid">
<p>
<strong>Telefone</strong><br>
+351 966 518 238
</p>
<p>
<strong>Email</strong><br>
info@augustaadviser.pt
</p>
<p>
<strong>Instagram</strong><br>
@augusta.advisor
</p>
<p>
<strong>Morada</strong><br>
Avenida Júlio Saúl Dias nº 191<br>
4480-673 Vila do Conde
</p>
</div>
<div class="contact-actions">
<a
href="/portal/marcar"
class="btn btn-primary">
Agendar Serviço
</a>
<a
href="tel:+351966518238"
class="btn">
Ligar Agora
</a>
<a
href="mailto:info@augustaadviser.pt"
class="btn">
Enviar Email
</a>
<a
href="https://instagram.com/augusta.advisor"
target="_blank"
class="btn">
Instagram
</a>
<a
href="#inquerito"
class="btn">
Enviar Inquérito
</a>
</div>
</div>
</div>
</section>
<section id="inquerito" class="section-alt">
<div class="container">
<h2 class="section-title">
Envie-nos uma Mensagem
</h2>
<div class="divider"></div>
<div class="section-intro">
Prefere escrever? Deixe aqui os seus dados e entraremos
em contacto consigo.
</div>
@if(session('inquiry_success'))
<div class="inquiry-success">
Mensagem enviada com sucesso. Entraremos em contacto em breve.
</div>
@endif
<form method="POST" action="https://augustaadviser.pt/contacto/inquerito" class="inquiry-form">
@csrf
<div class="inquiry-row">
<div>
<label for="name">Nome</label>
<input type="text" id="name" name="name" value="{{ old('name') }}" required>
@error('name')
<div class="field-error">{{ $message }}</div>
@enderror
</div>
<div>
<label for="email">Email</label>
<input type="email" id="email" name="email" value="{{ old('email') }}" required>
@error('email')
<div class="field-error">{{ $message }}</div>
@enderror
</div>
</div>
<div class="inquiry-row">
<div>
<label for="phone">Telefone (opcional)</label>
<input type="text" id="phone" name="phone" value="{{ old('phone') }}">
@error('phone')
<div class="field-error">{{ $message }}</div>
@enderror
</div>
<div>
<label for="subject">Assunto</label>
<select id="subject" name="subject" required>
<option value="" disabled {{ old('subject') ? '' : 'selected' }}>Selecione...</option>
<option value="marcacoes" {{ old('subject') == 'marcacoes' ? 'selected' : '' }}>Marcações</option>
<option value="informacoes_gerais" {{ old('subject') == 'informacoes_gerais' ? 'selected' : '' }}>Informações Gerais</option>
<option value="promocoes" {{ old('subject') == 'promocoes' ? 'selected' : '' }}>Informações sobre Promoções</option>
<option value="outros" {{ old('subject') == 'outros' ? 'selected' : '' }}>Outros</option>
</select>
@error('subject')
<div class="field-error">{{ $message }}</div>
@enderror
</div>
</div>
<div>
<label for="message">Mensagem</label>
<textarea id="message" name="message" rows="5" required>{{ old('message') }}</textarea>
@error('message')
<div class="field-error">{{ $message }}</div>
@enderror
</div>
<button type="submit" class="btn btn-primary">
Enviar Mensagem
</button>
</form>
</div>
</section>
<footer class="footer">
<div class="container">
<img
src="/images/logoaugusta-1a.png"
class="footer-logo"
alt="Augusta Adviser">
<div class="footer-grid">
<div class="footer-col">
<h4>Augusta Adviser</h4>
<p>Consultoria de Imagem &amp; Estética</p>
<p>Rosto • Corpo • Beleza Personalizada</p>
<p>Avenida Júlio Saúl Dias nº 191<br>4480-673 Vila do Conde</p>
</div>
<div class="footer-col">
<h4>Contactos</h4>
<p>Telefone: +351 966 518 238</p>
<p>Email: <a href="mailto:info@augustaadviser.pt" style="color:#7a6b5d;text-decoration:none;">info@augustaadviser.pt</a></p>
<p>Instagram:
<a
href="https://instagram.com/augusta.advisor"
target="_blank"
style="color:#7a6b5d;text-decoration:none;">
@augusta.advisor
</a>
</p>
</div>
</div>
<div class="footer-bottom">
© 2026 Augusta Adviser. Todos os direitos reservados.
</div>
</div>
</footer>
<div id="lightbox" class="lightbox-overlay" onclick="closeLightbox()">
<span class="lightbox-close" onclick="closeLightbox()">&times;</span>
<img id="lightbox-img" src="" alt="">
</div>
<script>
function openLightbox(src) {
document.getElementById('lightbox-img').src = src;
document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
document.getElementById('lightbox').classList.remove('active');
document.getElementById('lightbox-img').src = '';
}
document.addEventListener('keydown', function(e) {
if (e.key === 'Escape') {
closeLightbox();
}
});
</script>
</body>
</html>
