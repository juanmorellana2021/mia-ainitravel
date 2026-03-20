<?php
/**
 * mia/views/layouts/main.php
 *
 * Master layout template — Bootstrap 5 (local files, zero CDN).
 * Pages set $pageTitle and $pageContent before including this.
 */

$base = App::basePath();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Mia by AiniTravel') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? App::TAGLINE) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($pageCanonical ?? (App::URL . ($_SERVER['REQUEST_URI'] === '/' ? '/' : rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')))) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars(App::NAME) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Mia by AiniTravel') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription ?? App::TAGLINE) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($pageCanonical ?? (App::URL . ($_SERVER['REQUEST_URI'] === '/' ? '/' : rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')))) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageOgImage ?? App::URL . App::asset('img/og-cover.png')) ?>">
    <meta property="og:locale" content="es_PE">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle ?? 'Mia by AiniTravel') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription ?? App::TAGLINE) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($pageOgImage ?? App::URL . App::asset('img/og-cover.png')) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= App::asset('img/favicon.svg') ?>">
    <link rel="shortcut icon" href="<?= App::asset('img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/mia.css') ?>">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= $base ?>/">
            <i class="bi bi-whatsapp text-success me-2"></i>Mia
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/features">Funciones</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/pricing">Precios</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $base ?>/demo">Demo</a></li>
                <?php if (!empty($_SESSION['mia_client_id'])): ?>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm ms-lg-2 mt-2 mt-lg-0" href="<?= $base ?>/dashboard">
                        <i class="bi bi-grid me-1"></i>Mi Panel
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm ms-lg-2 mt-2 mt-lg-0" href="<?= $base ?>/login">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Ingresar
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="btn btn-success btn-sm ms-2 mt-2 mt-lg-0" href="<?= $base ?>/register">
                        <i class="bi bi-rocket me-1"></i>Prueba Gratis
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page content -->
<main>
<?php if (isset($pageContent)) echo $pageContent; ?>
</main>

<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold"><i class="bi bi-whatsapp text-success me-2"></i>Mia by AiniTravel</h5>
                <p class="text-secondary"><?= htmlspecialchars(App::TAGLINE) ?></p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Enlaces</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= $base ?>/features" class="text-secondary text-decoration-none">Funciones</a></li>
                    <li><a href="<?= $base ?>/pricing" class="text-secondary text-decoration-none">Precios</a></li>
                    <li><a href="<?= $base ?>/demo" class="text-secondary text-decoration-none">Demo en Vivo</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Contacto</h6>
                <p class="text-secondary mb-1"><i class="bi bi-whatsapp me-2"></i><?= App::WHATSAPP ?></p>
                <p class="text-secondary mb-1"><i class="bi bi-envelope me-2"></i>hola@ainitravel.com</p>
                <p class="text-secondary"><i class="bi bi-globe me-2"></i>mia-whatsapp.com</p>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-secondary mb-0">&copy; <?= date('Y') ?> AiniTravel. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
<?php if (!empty($pageScripts)) echo $pageScripts; ?>
<script>
(function(){
  var BASE='<?= App::basePath() ?>';
  var sid=localStorage.getItem('_mia_sid');if(!sid){sid=Math.random().toString(36).slice(2)+Date.now().toString(36);localStorage.setItem('_mia_sid',sid);}
  var t0=Date.now();
  var ua=navigator.userAgent;
  var dev=(/Mobi|Android/i.test(ua)?'mobile':(/iPad|Tablet/i.test(ua)?'tablet':'desktop'));
  var qs=new URLSearchParams(location.search);
  function send(ev,extra){
    var p={event:ev,page:location.pathname,referrer:document.referrer,
      utm_source:qs.get('utm_source')||'',utm_medium:qs.get('utm_medium')||'',
      utm_campaign:qs.get('utm_campaign')||'',device:dev,sid:sid,
      duration_ms:(ev==='pageleave'?Date.now()-t0:0)};
    if(extra)Object.assign(p,extra);
    navigator.sendBeacon(BASE+'/api/track',JSON.stringify(p));
  }
  send('pageview');
  var sent=false;
  window.addEventListener('pagehide',function(){if(!sent){sent=true;send('pageleave');}});
    document.querySelectorAll('a,button').forEach(function(el){
        var txt=(el.textContent||'').trim();
        var title=(el.getAttribute('title')||'').trim();
        var aria=(el.getAttribute('aria-label')||'').trim();
        var href=(el.getAttribute('href')||'').trim();
        var label=(txt||title||aria||href||'cta').slice(0,60);

        var isKeywordCta=/prueba|empezar|gratis|demo|register|start|whatsapp|mensaje|contactar|ventas/i.test(txt+' '+title+' '+aria);
        var isWhatsAppLink=/wa\.me|api\.whatsapp\.com/i.test(href) || el.id==='wa-bubble';
        var isRegisterLink=/\/register|register/i.test(href);

        if(isKeywordCta || isWhatsAppLink || isRegisterLink){
            el.addEventListener('click',function(){
                send('cta_click',{label:label,href:href.slice(0,120)});
            },true);
        }
    });
})();
</script>
<!-- WhatsApp floating chat bubble -->
<a href="https://wa.me/<?= ltrim(App::WHATSAPP, '+') ?>?text=Hola%21" target="_blank" rel="noopener"
   id="wa-bubble"
   style="position:fixed;bottom:24px;right:24px;z-index:9999;
          width:60px;height:60px;border-radius:50%;
          background:#25d366;display:flex;align-items:center;justify-content:center;
          box-shadow:0 4px 16px rgba(37,211,102,.5);text-decoration:none;
          animation:wa-pulse 2.4s ease-in-out infinite;"
   title="Habla con Mia">
  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#fff" viewBox="0 0 16 16">
    <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
  </svg>
</a>
<style>
@keyframes wa-pulse {
  0%,100%{box-shadow:0 4px 16px rgba(37,211,102,.5)}
  50%{box-shadow:0 4px 28px rgba(37,211,102,.85),0 0 0 8px rgba(37,211,102,.15)}
}
@media (max-width: 575.98px){
    #wa-bubble{
        width:54px !important;
        height:54px !important;
        right:14px !important;
        bottom:14px !important;
    }
    #wa-bubble svg{
        width:26px !important;
        height:26px !important;
    }
}
</style>
</body>
</html>
