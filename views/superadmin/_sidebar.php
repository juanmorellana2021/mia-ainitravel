<?php
/**
 * mia/views/superadmin/_sidebar.php
 * Sidebar + opens main content area.
 */
$base = App::basePath();
?>
<aside class="sa-sidebar">
    <a class="sa-brand" href="<?= $base ?>/superadmin/dashboard">
        <i class="bi bi-whatsapp" style="color:#6366f1;font-size:1.3rem;"></i>
        <span>Mia</span>
        <span class="sa-brand-badge">Super</span>
    </a>

    <nav class="sa-nav">
        <a href="<?= $base ?>/superadmin/dashboard"
           class="sa-nav-item <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="<?= $base ?>/superadmin/mia-bot"
           class="sa-nav-item <?= ($activeNav ?? '') === 'mia_bot' ? 'active' : '' ?>">
            <i class="bi bi-whatsapp"></i> Conectar Bot
        </a>
        <a href="<?= $base ?>/superadmin/prospects"
           class="sa-nav-item <?= ($activeNav ?? '') === 'prospects' ? 'active' : '' ?>">
            <i class="bi bi-chat-square-text"></i> Prospectos
        </a>
        <a href="<?= $base ?>/superadmin/clients"
           class="sa-nav-item <?= ($activeNav ?? '') === 'clients' ? 'active' : '' ?>">
            <i class="bi bi-buildings"></i> Clientes
        </a>
        <div class="sa-nav-divider"></div>
        <a href="<?= $base ?>/dashboard" class="sa-nav-item" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> App cliente
        </a>
    </nav>

    <div class="sa-sidebar-footer">
        <a href="<?= $base ?>/superadmin/logout" class="sa-nav-item" style="color:rgba(239,68,68,0.7)">
            <i class="bi bi-box-arrow-left"></i> Salir
        </a>
    </div>
</aside>

<div class="sa-main-area">
    <div class="sa-topbar">
        <span class="sa-topbar-title"><?= htmlspecialchars($pageTopTitle ?? '') ?></span>
        <span class="text-muted small"><i class="bi bi-person-lock me-1"></i><?= htmlspecialchars($_SESSION['mia_superadmin'] ?? '') ?></span>
    </div>
    <main class="sa-content">
