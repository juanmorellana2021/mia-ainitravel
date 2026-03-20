<?php
/**
 * mia/views/client/_head.php
 *
 * Opens <html>, <head>, and starts the dashboard wrapper.
 * Requires $pageTitle and $base to be set before including.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel — Mia') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= App::asset('img/favicon.svg') ?>">
    <link rel="shortcut icon" href="<?= App::asset('img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= App::asset('css/bootstrap-icons.min.css') ?>">
    <style>
        /* ── Dashboard layout ──────────────────────────────────────────────── */
        :root {
            --mc-sidebar: #1a1a2e;
            --mc-sidebar-w: 240px;
            --mc-accent: #25d366;
            --mc-accent-dark: #1da851;
            --mc-bg: #f0f4f8;
        }
        * { box-sizing: border-box; }
        body.mc-body {
            margin: 0;
            background: var(--mc-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 0.92rem;
        }
        .mc-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ────────────────────────────────────────────────────────── */
        .mc-sidebar {
            width: var(--mc-sidebar-w);
            min-width: var(--mc-sidebar-w);
            background: var(--mc-sidebar);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 200;
            overflow-y: auto;
        }
        .mc-sidebar-brand {
            padding: 20px 16px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
        }
        .mc-sidebar-brand i { color: var(--mc-accent); font-size: 1.3rem; }
        .mc-sidebar-nav { flex: 1; padding: 10px 0; }
        .mc-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.88rem;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        .mc-nav-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .mc-nav-item.active {
            background: rgba(37,211,102,0.12);
            color: var(--mc-accent);
            border-left-color: var(--mc-accent);
            font-weight: 600;
        }
        .mc-nav-item i { font-size: 1.05rem; min-width: 20px; }
        .mc-nav-divider { border-top: 1px solid rgba(255,255,255,0.08); margin: 8px 0; }
        .mc-sidebar-footer { padding: 8px 0; border-top: 1px solid rgba(255,255,255,0.08); }
        .mc-plan-badge {
            margin: 12px 14px;
            background: rgba(37,211,102,0.12);
            border: 1px solid rgba(37,211,102,0.25);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.75rem;
            color: var(--mc-accent);
        }

        /* ── Main area ──────────────────────────────────────────────────────── */
        .mc-main-area {
            margin-left: var(--mc-sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .mc-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .mc-topbar-title { font-weight: 700; color: #1a202c; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: calc(100vw - 180px); }
        .mc-topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #4a5568;
        }
        .mc-topbar-user .avatar {
            width: 32px; height: 32px;
            background: var(--mc-accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 0.8rem;
        }
        .mc-content { padding: 24px; flex: 1; }

        /* ── Cards ──────────────────────────────────────────────────────────── */
        .mc-stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }
        .mc-stat-card .stat-num { font-size: 2rem; font-weight: 800; line-height: 1; }
        .mc-stat-card .stat-label { font-size: 0.8rem; color: #718096; margin-top: 4px; }

        /* ── Tables ─────────────────────────────────────────────────────────── */
        .mc-table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .mc-table-card .card-header-bar {
            padding: 14px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .mc-table-card .table { margin: 0; font-size: 0.87rem; }
        .mc-table-card .table thead th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #718096;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
        }
        .mc-table-card .table td { padding: 10px 16px; vertical-align: middle; }

        /* ── Messages ───────────────────────────────────────────────────────── */
        .msg-bubble {
            max-width: 75%;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.87rem;
            margin-bottom: 6px;
        }
        .msg-bubble.inbound  { background: #f0f4f8; border-bottom-left-radius: 2px; }
        .msg-bubble.outbound { background: #dcf8c6; margin-left: auto; border-bottom-right-radius: 2px; }
        .msg-bubble.outbound.human { background: #e3f2fd; }
        .msg-time { font-size: 0.7rem; color: #a0aec0; margin-top: 2px; }

        /* ── Mobile ─────────────────────────────────────────────────────────── */
        .mc-mobile-toggle {
            display: none;
            background: var(--mc-sidebar);
            color: #fff;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 1.1rem;
            cursor: pointer;
        }
        .mc-sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 299;
        }
        .mc-sidebar-backdrop.show { display: block; }
        .mc-sidebar-close-btn { display: none; }
        @media (max-width: 768px) {
            .mc-mobile-toggle { display: inline-flex; align-items: center; }
            .mc-sidebar {
                display: none;
                position: fixed;
                top: 0; left: 0; bottom: 0;
                width: var(--mc-sidebar-w);
                z-index: 300;
            }
            .mc-sidebar.open { display: flex; }
            .mc-main-area { margin-left: 0; }
            .mc-content { padding: 16px; }
            .mc-sidebar-close-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: 10px; right: 10px;
                width: 30px; height: 30px;
                background: rgba(255,255,255,0.12);
                border: none;
                border-radius: 50%;
                color: #fff;
                font-size: 1rem;
                cursor: pointer;
                z-index: 10;
            }
        }
    </style>
</head>
<body class="mc-body">
<div class="mc-wrapper">
