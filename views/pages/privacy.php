<?php
/**
 * mia/views/pages/privacy.php — Privacy Policy page
 */

$base = App::basePath();
$pageTitle = 'Política de Privacidad — Mia by AiniTravel';
$pageDescription = 'Política de privacidad de Mia by AiniTravel. Conoce cómo recopilamos, usamos y protegemos tu información.';

ob_start();
?>

<div class="container py-5" style="max-width:800px;">
    <h1 class="fw-bold mb-4">Política de Privacidad</h1>
    <p class="text-muted">Última actualización: 24 de marzo de 2026</p>

    <h4 class="mt-4">1. Información que recopilamos</h4>
    <p>Cuando usas <?= App::NAME ?>, recopilamos la siguiente información:</p>
    <ul>
        <li><strong>Datos de cuenta:</strong> nombre, correo electrónico, número de WhatsApp y nombre de negocio.</li>
        <li><strong>Datos de conversación:</strong> mensajes entre tu asistente virtual y tus clientes/leads, almacenados para que puedas gestionar tus leads.</li>
        <li><strong>Datos de uso:</strong> información sobre cómo interactúas con la plataforma (páginas visitadas, funciones usadas).</li>
    </ul>

    <h4 class="mt-4">2. Cómo usamos tu información</h4>
    <ul>
        <li>Proveer y mantener el servicio de asistente virtual de WhatsApp.</li>
        <li>Gestionar tu cuenta y suscripción.</li>
        <li>Enviar notificaciones relacionadas al servicio (alertas de leads, recordatorios de citas).</li>
        <li>Mejorar la calidad del servicio y la inteligencia artificial del asistente.</li>
    </ul>

    <h4 class="mt-4">3. Almacenamiento y seguridad</h4>
    <p>Tus datos se almacenan en servidores seguros con conexión cifrada (HTTPS/TLS). Las contraseñas se almacenan con hash seguro y nunca en texto plano. Implementamos medidas de seguridad estándar de la industria para proteger tu información.</p>

    <h4 class="mt-4">4. Compartir información</h4>
    <p><strong>No vendemos ni compartimos tu información personal con terceros.</strong> Solo compartimos datos con:</p>
    <ul>
        <li>Proveedores de API de inteligencia artificial (para procesar conversaciones).</li>
        <li>Procesadores de pagos (MercadoPago) para gestionar suscripciones.</li>
        <li>WhatsApp/Meta como plataforma de mensajería.</li>
    </ul>

    <h4 class="mt-4">5. Tus derechos</h4>
    <p>Tienes derecho a:</p>
    <ul>
        <li>Acceder a tus datos personales.</li>
        <li>Solicitar la corrección de datos incorrectos.</li>
        <li>Solicitar la eliminación de tu cuenta y datos asociados.</li>
        <li>Exportar tus datos en formato estándar.</li>
    </ul>

    <h4 class="mt-4">6. Cookies</h4>
    <p>Usamos cookies de sesión esenciales para mantener tu inicio de sesión activo. No usamos cookies de seguimiento ni publicidad de terceros.</p>

    <h4 class="mt-4">7. Cambios a esta política</h4>
    <p>Podemos actualizar esta política ocasionalmente. Notificaremos cambios significativos por correo electrónico o mediante un aviso en la plataforma.</p>

    <h4 class="mt-4">8. Contacto</h4>
    <p>Si tienes preguntas sobre esta política de privacidad, contáctanos:</p>
    <ul>
        <li><i class="bi bi-envelope me-2"></i>support@mia-whatsapp.com</li>
        <li><i class="bi bi-whatsapp me-2"></i><?= App::WHATSAPP ?></li>
    </ul>
</div>

<?php
$pageContent = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
