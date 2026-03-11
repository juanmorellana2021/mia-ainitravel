<?php
/**
 * mia/views/superadmin/_head.php
 * Opens html/head and the main wrapper for the superadmin layout.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Superadmin — Mia') ?></title>
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <style>
        :root {
            --sa-sidebar: #0f172a;
            --sa-sidebar-w: 240px;
            --sa-accent: #6366f1;
            --sa-accent-dark: #4f46e5;
            --sa-bg: #f1f5f9;
        }
        * { box-sizing: border-box; }
        body.sa-body {
            margin: 0;
            background: var(--sa-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 0.92rem;
        }
        .sa-wrapper { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sa-sidebar {
            width: var(--sa-sidebar-w);
            min-width: var(--sa-sidebar-w);
            background: var(--sa-sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 200;
            overflow-y: auto;
        }
        .sa-brand {
            padding: 20px 16px 18px;
            display: flex; align-items: center; gap: 10px;
            font-size: 1.05rem; font-weight: 700; color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            text-decoration: none;
        }
        .sa-brand-badge {
            font-size: 0.65rem; background: var(--sa-accent);
            color: #fff; border-radius: 4px; padding: 2px 6px;
            font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;
        }
        .sa-nav { flex: 1; padding: 10px 0; }
        .sa-nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 18px;
            color: rgba(255,255,255,0.55);
            text-decoration: none; font-size: 0.88rem;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        .sa-nav-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sa-nav-item.active {
            background: rgba(99,102,241,0.15);
            color: #a5b4fc;
            border-left-color: var(--sa-accent);
            font-weight: 600;
        }
        .sa-nav-item i { font-size: 1.05rem; min-width: 20px; }
        .sa-nav-divider { border-top: 1px solid rgba(255,255,255,0.07); margin: 8px 0; }
        .sa-sidebar-footer { padding: 8px 0; border-top: 1px solid rgba(255,255,255,0.07); }

        /* Main area */
        .sa-main-area {
            margin-left: var(--sa-sidebar-w);
            flex: 1; display: flex; flex-direction: column; min-width: 0;
        }
        .sa-topbar {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 14px 28px; display: flex;
            align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .sa-topbar-title { font-weight: 700; font-size: 1.08rem; color: #0f172a; }
        .sa-content { flex: 1; padding: 28px; }

        /* Cards */
        .sa-kpi-card {
            background: #fff; border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px 22px;
        }
        .sa-kpi-card .kpi-label { font-size: 0.78rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .sa-kpi-card .kpi-value { font-size: 1.9rem; font-weight: 700; color: #0f172a; line-height: 1; }
        .sa-kpi-card .kpi-sub   { font-size: 0.78rem; color: #94a3b8; margin-top: 4px; }

        .sa-table-card {
            background: #fff; border-radius: 12px;
            border: 1px solid #e2e8f0; overflow: hidden;
        }
        .sa-table-card .card-header-bar {
            padding: 14px 20px; border-bottom: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between;
            font-weight: 600; color: #334155;
        }
        .sa-table-card .table { margin: 0; font-size: 0.875rem; }
        .sa-table-card .table th { background: #f8fafc; color: #64748b; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid #e2e8f0; padding: 10px 16px; }
        .sa-table-card .table td { padding: 11px 16px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .sa-table-card .table tbody tr:last-child td { border-bottom: 0; }
        .sa-table-card .table tbody tr:hover td { background: #f8fafc; }

        .sa-detail-card {
            background: #fff; border-radius: 12px;
            border: 1px solid #e2e8f0; padding: 24px;
        }
        .sa-detail-card h6 {
            font-size: 0.78rem; text-transform: uppercase;
            letter-spacing: 0.5px; color: #64748b; margin-bottom: 14px;
        }

        /* Status badges */
        .badge-trial      { background: rgba(234,179,8,0.12);  color: #854d0e; border: 1px solid rgba(234,179,8,0.3); }
        .badge-active     { background: rgba(34,197,94,0.12);  color: #166534; border: 1px solid rgba(34,197,94,0.3); }
        .badge-expired    { background: rgba(239,68,68,0.12);  color: #991b1b; border: 1px solid rgba(239,68,68,0.3); }
        .badge-cancelled  { background: rgba(148,163,184,0.15); color: #475569; border: 1px solid rgba(148,163,184,0.3); }
    </style>
</head>
<body class="sa-body">
<div class="sa-wrapper">
