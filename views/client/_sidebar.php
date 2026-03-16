<?php
/**
 * mia/views/client/_sidebar.php
 *
 * Sidebar nav + opens the main content area.
 * Requires $base, $activeNav, $client to be set.
 */
$sessionClient = $_SESSION['mia_client'] ?? [];
$clientName    = $sessionClient['business_name'] ?? 'Mi Panel';
$plan          = $sessionClient['plan'] ?? 'trial';
$planStatus    = $sessionClient['plan_status'] ?? 'trial';
$initials      = strtoupper(substr($clientName, 0, 2));
?>
<!-- Backdrop overlay (mobile) -->
<div class="mc-sidebar-backdrop" id="mcBackdrop" onclick="closeSidebar()"></div>

<!-- ── Sidebar ── -->
<aside class="mc-sidebar" id="mcSidebar">
    <button class="mc-sidebar-close-btn" onclick="closeSidebar()" aria-label="Cerrar menú">
        <i class="bi bi-x-lg"></i>
    </button>
    <a class="mc-sidebar-brand" href="<?= $base ?>/">
        <i class="bi bi-whatsapp"></i>
        <span>Mia</span>
    </a>

    <!-- Plan badge -->
    <div class="mc-plan-badge">
        <i class="bi bi-lightning-fill me-1"></i>
        <?php if ($planStatus === 'trial'): ?>
            Prueba gratuita — <?= max(0, (int) ceil((strtotime($_SESSION['mia_client']['trial_ends_at'] ?? 'now') - time()) / 86400)) ?> días
        <?php elseif ($planStatus === 'active'): ?>
            Plan <?= htmlspecialchars(ucfirst($plan)) ?> activo
        <?php else: ?>
            Plan suspendido
        <?php endif; ?>
    </div>

    <nav class="mc-sidebar-nav">
        <a href="<?= $base ?>/dashboard"
           class="mc-nav-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="<?= $base ?>/dashboard/leads"
           class="mc-nav-item <?= $activeNav === 'leads' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Leads
        </a>
        <a href="<?= $base ?>/dashboard/messages"
           class="mc-nav-item <?= $activeNav === 'messages' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots"></i> Mensajes
        </a>
        <a href="<?= $base ?>/dashboard/analytics"
           class="mc-nav-item <?= $activeNav === 'analytics' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart"></i> Analíticas
        </a>
        <?php
        $_proPlans = ['trial', 'pro', 'enterprise', 'enterprise_duo', 'enterprise_chain', 'enterprise_corp'];
        $_hasBroadcast = in_array($plan, $_proPlans);
        $_hasSequences = in_array($plan, $_proPlans);
        ?>
        <?php if ($_hasBroadcast): ?>
        <a href="<?= $base ?>/dashboard/broadcast"
           class="mc-nav-item <?= $activeNav === 'broadcast' ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i> Difusión
        </a>
        <?php else: ?>
        <a href="<?= $base ?>/dashboard/billing?upgrade=broadcast"
           class="mc-nav-item" style="opacity:.5" title="Disponible desde el plan Business">
            <i class="bi bi-megaphone"></i> Difusión
            <span class="badge bg-warning text-dark ms-auto" style="font-size:.6rem;padding:2px 5px">Pro</span>
        </a>
        <?php endif; ?>
        <?php if ($_hasSequences): ?>
        <a href="<?= $base ?>/dashboard/sequences"
           class="mc-nav-item <?= $activeNav === 'sequences' ? 'active' : '' ?>">
            <i class="bi bi-send-check"></i> Automatizaciones
        </a>
        <?php else: ?>
        <a href="<?= $base ?>/dashboard/billing?upgrade=sequences"
           class="mc-nav-item" style="opacity:.5" title="Disponible desde el plan Business">
            <i class="bi bi-send-check"></i> Automatizaciones
            <span class="badge bg-warning text-dark ms-auto" style="font-size:.6rem;padding:2px 5px">Pro</span>
        </a>
        <?php endif; ?>
        <a href="<?= $base ?>/dashboard/billing"
           class="mc-nav-item <?= $activeNav === 'billing' ? 'active' : '' ?>">
            <i class="bi bi-credit-card"></i> Suscripción
        </a>
        <a href="<?= $base ?>/dashboard/settings"
           class="mc-nav-item <?= $activeNav === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> Configuración
        </a>
    </nav>

    <div class="mc-sidebar-footer">
        <div class="mc-nav-divider"></div>
        <a href="<?= $base ?>/" class="mc-nav-item" target="_blank">
            <i class="bi bi-globe"></i> Ver sitio
        </a>
        <a href="<?= $base ?>/logout" class="mc-nav-item" style="color:rgba(255,100,100,0.8)">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
    </div>
</aside>

<!-- ── Main area ── -->
<div class="mc-main-area">
    <header class="mc-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="mc-mobile-toggle" onclick="openSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <span class="mc-topbar-title"><?= htmlspecialchars($pageTopTitle ?? $pageTitle ?? '') ?></span>
        </div>
        <div class="mc-topbar-user">
            <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            <span class="d-none d-md-inline"><?= htmlspecialchars($clientName) ?></span>
        </div>
    </header>
    <main class="mc-content">
