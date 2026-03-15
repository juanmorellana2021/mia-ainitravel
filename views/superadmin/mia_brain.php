<?php
/**
 * mia/views/superadmin/mia_brain.php
 * Live reference screen showing every behavior, state, trigger and rule Mia uses.
 */
require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<style>
.brain-section {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 22px 26px;
    margin-bottom: 20px;
}
.brain-section h5 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.brain-section h5 .badge-section {
    font-size: 0.65rem;
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 2px 10px;
    font-weight: 600;
}

/* State machine flow */
.state-flow {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.state-node {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    min-width: 130px;
    position: relative;
    cursor: default;
    transition: border-color .15s, box-shadow .15s;
}
.state-node:hover {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.state-node .sn-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: monospace;
}
.state-node .sn-title {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1e293b;
    margin-top: 2px;
    text-align: center;
}
.state-node .sn-desc {
    font-size: 0.73rem;
    color: #64748b;
    margin-top: 4px;
    text-align: center;
    line-height: 1.35;
}
.state-node.sn-capture {
    border-color: #22c55e;
    background: #f0fdf4;
}
.state-node.sn-capture .sn-label { color: #16a34a; }
.state-node.sn-intercept {
    border-color: #f59e0b;
    background: #fffbeb;
}
.state-node.sn-intercept .sn-label { color: #d97706; }
.state-arrow {
    color: #94a3b8;
    font-size: 1.1rem;
    flex-shrink: 0;
}

/* Rule pills */
.rule-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 0.78rem;
    color: #334155;
    margin: 3px;
}
.rule-pill.danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.rule-pill.success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
.rule-pill.warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
.rule-pill.accent { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }

/* Trigger keyword */
.kw-chip {
    display: inline-block;
    background: #1e293b;
    color: #a5f3fc;
    border-radius: 4px;
    padding: 2px 8px;
    font-size: 0.73rem;
    font-family: monospace;
    margin: 2px;
}
.kw-chip.removed {
    background: #fee2e2;
    color: #b91c1c;
    text-decoration: line-through;
}

/* Objection cards */
.obj-card {
    background: #f8fafc;
    border-radius: 8px;
    border-left: 3px solid #6366f1;
    padding: 10px 14px;
    margin-bottom: 10px;
}
.obj-card .obj-trigger {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1e293b;
}
.obj-card .obj-response {
    font-size: 0.8rem;
    color: #475569;
    margin-top: 4px;
    line-height: 1.5;
}

/* Config table */
.cfg-row {
    display: flex;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    gap: 12px;
}
.cfg-row:last-child { border-bottom: none; }
.cfg-key {
    font-size: 0.78rem;
    font-family: monospace;
    color: #6366f1;
    min-width: 180px;
    font-weight: 600;
}
.cfg-val {
    font-size: 0.82rem;
    color: #1e293b;
}
.cfg-note {
    font-size: 0.74rem;
    color: #94a3b8;
    margin-top: 2px;
}

/* Plan cards */
.plan-card {
    border-radius: 10px;
    padding: 16px;
    border: 1.5px solid #e2e8f0;
    height: 100%;
}
.plan-card.popular {
    border-color: #6366f1;
    background: #f5f3ff;
}
.plan-price {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
}
.plan-currency {
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
    vertical-align: super;
}

/* Philosophy cards */
.phi-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px 14px;
    border-left: 3px solid #a5b4fc;
    margin-bottom: 8px;
}
.phi-card strong { color: #3730a3; font-size: 0.82rem; }
.phi-card p { font-size: 0.8rem; color: #475569; margin: 3px 0 0; }

/* Bot status badge */
.bot-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.bot-badge.online { background: #dcfce7; color: #15803d; }
.bot-badge.offline { background: #fee2e2; color: #b91c1c; }

/* Live status section */
.live-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}
.ls-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px 16px;
    border: 1px solid #e2e8f0;
}
.ls-card .ls-label { font-size: 0.72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.ls-card .ls-val { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-top: 3px; }
.ls-card .ls-sub { font-size: 0.72rem; color: #94a3b8; margin-top: 2px; }

/* Section tab bar */
.brain-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.brain-tab {
    padding: 10px 18px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
    white-space: nowrap;
}
.brain-tab.active {
    color: #6366f1;
    border-bottom-color: #6366f1;
}
.brain-panel { display: none; }
.brain-panel.active { display: block; }
</style>

<div style="max-width:1200px">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">🧠 Cerebro de Mia</h4>
            <p class="text-muted small mb-0 mt-1">Todo lo que Mia sabe, cómo piensa, qué hace en cada estado. Vista de solo lectura.</p>
        </div>
        <div id="botStatusBadge" class="bot-badge online">
            <span style="width:8px;height:8px;background:#16a34a;border-radius:50%;display:inline-block"></span>
            Cargando…
        </div>
    </div>

    <!-- Tabs -->
    <div class="brain-tabs">
        <div class="brain-tab active" onclick="switchTab('flow')"><i class="bi bi-diagram-3 me-1"></i>Flujo de ventas</div>
        <div class="brain-tab" onclick="switchTab('triggers')"><i class="bi bi-lightning me-1"></i>Triggers & Regex</div>
        <div class="brain-tab" onclick="switchTab('philosophy')"><i class="bi bi-lightbulb me-1"></i>Filosofía</div>
        <div class="brain-tab" onclick="switchTab('objections')"><i class="bi bi-shield-exclamation me-1"></i>Objeciones</div>
        <div class="brain-tab" onclick="switchTab('plans')"><i class="bi bi-credit-card me-1"></i>Planes</div>
        <div class="brain-tab" onclick="switchTab('rules')"><i class="bi bi-card-checklist me-1"></i>Reglas de comunicación</div>
        <div class="brain-tab" onclick="switchTab('engine')"><i class="bi bi-cpu me-1"></i>Motor IA</div>
    </div>

    <!-- ════ TAB: FLOW ════ -->
    <div id="tab-flow" class="brain-panel active">

        <!-- Interceptors first -->
        <div class="brain-section">
            <h5><i class="bi bi-lightning-charge text-warning"></i> Interceptores globales <span class="badge-section">se ejecutan ANTES del state machine</span></h5>
            <div class="state-flow">
                <div class="state-node sn-intercept">
                    <span class="sn-label">interceptor</span>
                    <span class="sn-title">Precio / Uso</span>
                    <span class="sn-desc">detectsPriceOrUsageQuestion()<br>dispara en estados early/mid</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node sn-intercept">
                    <span class="sn-label">handler</span>
                    <span class="sn-title">handleDirectPriceQuestion</span>
                    <span class="sn-desc">Responde precio + uso, avanza a <code>closing</code></span>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                Activo en estados: <code>intro</code> · <code>qualifying_size</code> · <code>qualifying_method</code> · <code>qualifying_pain</code> · <code>roi_pitch</code> · <code>demo</code> · <code>benefits</code>
            </div>
        </div>

        <!-- Main funnel -->
        <div class="brain-section">
            <h5><i class="bi bi-diagram-3 text-indigo-600" style="color:#6366f1"></i> Flujo principal del sales funnel</h5>

            <div class="state-flow mb-4">
                <!-- new -->
                <div class="state-node">
                    <span class="sn-label">new</span>
                    <span class="sn-title">Nuevo contacto</span>
                    <span class="sn-desc">Saludo + pide nombre</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">collecting_contact_name</span>
                    <span class="sn-title">Captura nombre</span>
                    <span class="sn-desc">Extrae nombre con regex o primeras palabras</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">intro</span>
                    <span class="sn-title">Tipo de negocio</span>
                    <span class="sn-desc">Saluda por nombre, pregunta qué negocio tiene</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">qualifying_size</span>
                    <span class="sn-title">Tamaño / volumen</span>
                    <span class="sn-desc">Pregunta SPIN de implicación (cálculo de pérdida)</span>
                </div>
            </div>

            <div class="state-flow mb-4">
                <div class="state-node">
                    <span class="sn-label">qualifying_method</span>
                    <span class="sn-title">Método actual</span>
                    <span class="sn-desc">¿Cómo manejan WhatsApp hoy?</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">qualifying_pain</span>
                    <span class="sn-title">Dolor real</span>
                    <span class="sn-desc">Pregunta abierta — que ELLOS nombren el dolor</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">roi_pitch</span>
                    <span class="sn-title">ROI Pitch</span>
                    <span class="sn-desc">Valida + pone número a la pérdida. Espera "sí"</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">demo</span>
                    <span class="sn-title">Demo cinematográfica</span>
                    <span class="sn-desc">1 escena de 3-4 líneas. Sin listas.</span>
                </div>
            </div>

            <div class="state-flow">
                <div class="state-node">
                    <span class="sn-label">closing</span>
                    <span class="sn-title">Cierre</span>
                    <span class="sn-desc">Buy intent → pide email. Objeción → Feel/Felt/Found</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node sn-capture">
                    <span class="sn-label">collecting_email</span>
                    <span class="sn-title">Captura email</span>
                    <span class="sn-desc">Regex valida email. Crea cuenta automáticamente.</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node">
                    <span class="sn-label">onboarding_*</span>
                    <span class="sn-title">Onboarding</span>
                    <span class="sn-desc">biz_name → biz_phone → website → services → hours</span>
                </div>
                <span class="state-arrow">→</span>
                <div class="state-node sn-capture">
                    <span class="sn-label">captured</span>
                    <span class="sn-title">Cliente activo</span>
                    <span class="sn-desc">Soporte post-venta. Guía de acceso al panel.</span>
                </div>
            </div>
        </div>

        <!-- Biz type detection -->
        <div class="brain-section">
            <h5><i class="bi bi-building me-2" style="color:#6366f1"></i>Detección de tipo de negocio</h5>
            <div class="row g-3">
                <?php
                $bizTypes = [
                    ['key' => 'agency',     'label' => 'Agencia de viajes', 'regex' => '/agencia|agencia de viajes|travel agency/i',   'ticket' => 250],
                    ['key' => 'hotel',      'label' => 'Hotel / hostal',    'regex' => '/hotel|hostal|hostel|lodge|resort/i',           'ticket' => 180],
                    ['key' => 'restaurant', 'label' => 'Restaurante',       'regex' => '/restaurante|restaurant|cafe|cafetería/i',      'ticket' => 40],
                    ['key' => 'retail',     'label' => 'Tienda / retail',   'regex' => '/tienda|shop|boutique|store/i',                 'ticket' => 60],
                    ['key' => 'services',   'label' => 'Servicios / consultoría','regex' => '/consultora|servicios|services/i',        'ticket' => 200],
                    ['key' => 'business',   'label' => 'Genérico (default)','regex' => '(fallback si ninguno coincide)',               'ticket' => 100],
                ];
                foreach ($bizTypes as $bz): ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px">
                        <div style="font-size:.78rem;font-weight:700;color:#1e293b"><?= $bz['label'] ?></div>
                        <div class="kw-chip mt-1"><?= $bz['key'] ?></div>
                        <div style="font-size:.72rem;color:#64748b;margin-top:6px"><?= htmlspecialchars($bz['regex']) ?></div>
                        <div style="font-size:.72rem;color:#475569;margin-top:4px">Ticket promedio: S/<?= $bz['ticket'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pain point map -->
        <div class="brain-section">
            <h5><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Mapa de puntos de dolor detectados</h5>
            <div class="row g-2">
                <?php
                $pains = [
                    ['key' => 'after_hours',       'label' => 'Fuera de horario',         'desc' => 'Clientes escriben de noche y no reciben respuesta'],
                    ['key' => 'slow_replies',       'label' => 'Respuestas lentas',         'desc' => 'Pierden ventas por velocidad de respuesta'],
                    ['key' => 'no_confirm',         'label' => 'Sin confirmación',          'desc' => 'Preguntan pero no concretan la compra'],
                    ['key' => 'high_commissions',   'label' => 'Comisiones altas',          'desc' => 'Dependen de Booking.com / OTAs'],
                    ['key' => 'general',            'label' => 'General (default)',         'desc' => 'Consultas sin atender en WhatsApp'],
                ];
                foreach ($pains as $p): ?>
                <div class="col-12 col-md-6">
                    <div class="obj-card" style="border-color:#f59e0b">
                        <div class="obj-trigger"><code><?= $p['key'] ?></code> — <?= $p['label'] ?></div>
                        <div class="obj-response"><?= $p['desc'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div><!-- /tab-flow -->

    <!-- ════ TAB: TRIGGERS ════ -->
    <div id="tab-triggers" class="brain-panel">
        <div class="brain-section">
            <h5><i class="bi bi-lightning-charge text-warning"></i> Price / Usage interceptor <span class="badge-section">detectsPriceOrUsageQuestion()</span></h5>
            <p class="small text-muted mb-3">Keywords que disparan la respuesta directa de precios. <span class="text-success fw-semibold">Verde = activo</span>. <span class="text-danger fw-semibold">Tachado = eliminado (era demasiado general)</span>.</p>
            <div>
                <?php
                $activeKw  = ['costo','cuesta','precio','plan','planes','cuánto','cuanto','vale','cobran','tarifa','mensual','suscripción','cómo funciona','cómo se usa','cómo se utiliza','cómo trabaja','qué incluye','cómo se instala','cómo se configura','how much','how does','pricing','price'];
                $removedKw = ['información','más información','detalles','qué ofrece','explain'];
                foreach ($activeKw as $kw): ?>
                    <span class="kw-chip"><?= htmlspecialchars($kw) ?></span>
                <?php endforeach; ?>
                <?php foreach ($removedKw as $kw): ?>
                    <span class="kw-chip removed"><?= htmlspecialchars($kw) ?></span>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 p-3" style="background:#f8fafc;border-radius:8px;font-family:monospace;font-size:.78rem;color:#334155;border:1px solid #e2e8f0">
                /\b(costo|cuesta|precio|plan|planes|cuánto|cuanto|vale|cobran|tarifa|mensual|suscripci[oó]n|<br>
                c[oó]mo funciona|c[oó]mo se usa|c[oó]mo se utiliza|c[oó]mo trabaja|qu[eé] incluye|<br>
                c[oó]mo se instala|c[oó]mo se configura|how much|how does|pricing|price)\b/iu
            </div>
            <div class="mt-2 small" style="color:#d97706"><i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Por qué se eliminaron "información" y "más información":</strong> La frase "Quiero más información" es demasiado genérica. No indica que el cliente quiera saber el precio — solo que quiere seguir conociendo el producto. Disparar el interceptor en ese punto interrumpe el funnel antes de tiempo y envía precios al primer contacto.
            </div>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-person-bounding-box me-2" style="color:#6366f1"></i>Buy intent detector <span class="badge-section">handleClosing() — regex</span></h5>
            <p class="small text-muted mb-3">Cuando el cliente dice algo de esta lista en estado <code>closing</code>, Mia avanza directamente a pedir el email.</p>
            <div>
                <?php
                $buyKw = ['empezar','activar','quiero','lo quiero','start','trial','me anoto','nos anotamos','adelante','vamos','acepto','confirmado','dale','listo','si quiero','ya','claro que si','por supuesto','perfecto','de acuerdo','me interesa','suena bien','esta bien','trato hecho','basico','pro','business','enterprise','starter','1','2','3'];
                foreach ($buyKw as $kw): ?>
                    <span class="rule-pill success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($kw) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-robot me-2" style="color:#6366f1"></i>Detección de idioma <span class="badge-section">detectLang()</span></h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px">
                        <div style="font-size:.78rem;font-weight:700;color:#15803d;margin-bottom:8px">🇪🇸 Palabras clave español (esScore)</div>
                        <div>
                            <?php $esWords = ['que','de','es','en','un','una','por','con','para','los','las','del','no','si','me','mi','tu','su','hola','ola','como','cómo','gracias','buenas','tengo','quiero','hotel','negocio'];
                            foreach ($esWords as $w) echo '<span class="kw-chip" style="background:#15803d">' . $w . '</span>'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px">
                        <div style="font-size:.78rem;font-weight:700;color:#1d4ed8;margin-bottom:8px">🇺🇸 Palabras clave inglés (enScore)</div>
                        <div>
                            <?php $enWords = ['the','is','are','this','that','have','has','with','what','how','hello','hi','yes','no','great','good','thanks','and','for','my','your','want','need','can','we','our'];
                            foreach ($enWords as $w) echo '<span class="kw-chip" style="background:#1d4ed8">' . $w . '</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 small text-muted">Si <code>enScore &gt; esScore</code> → modo inglés. La regla de idioma se inyecta en el system prompt de esa conversación y es OBLIGATORIA.</div>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-shield-check me-2" style="color:#6366f1"></i>Bot.js — Protecciones activas</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="obj-card" style="border-color:#6366f1">
                        <div class="obj-trigger">Dedup de mensajes duplicados</div>
                        <div class="obj-response">Se usa <code>msg.id._serialized</code> como clave única. Si el mismo msgId llega dos veces (ad-click duplicado, reconexión), el segundo se descarta. TTL: 5 minutos.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="obj-card" style="border-color:#6366f1">
                        <div class="obj-trigger">Skip de backlog offline</div>
                        <div class="obj-response">Al reconectar, el bot guarda <code>miaBotReadyAt</code>. Cualquier mensaje con <code>timestamp &lt; readyAt</code> se descarta para evitar tormentas de respuesta.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="obj-card" style="border-color:#6366f1">
                        <div class="obj-trigger">Ad-click (notification_template)</div>
                        <div class="obj-response">Facebook/Instagram ad-clicks llegan sin <code>body</code>. Se extrae <code>msg._data.body</code> o se usa "Hola" como fallback. El @lid se resuelve a número real via <code>getContact()</code>.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="obj-card" style="border-color:#6366f1">
                        <div class="obj-trigger">Reset de sesión</div>
                        <div class="obj-response">Si el usuario escribe <code>reset</code>, <code>reiniciar</code> o <code>empezar de nuevo</code>, toda la sesión se borra y vuelve a <code>new</code>.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="obj-card" style="border-color:#6366f1">
                        <div class="obj-trigger">Grupos ignorados</div>
                        <div class="obj-response">Mensajes de grupos (@g.us) y <code>status@broadcast</code> se filtran antes de cualquier procesamiento.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="obj-card" style="border-color:#6366f1">
                        <div class="obj-trigger">Trabajadores aislados por cliente</div>
                        <div class="obj-response">Cada cliente corre en un proceso fork separado. Un crash en el bot de un cliente nunca afecta a Mia ni a otros clientes.</div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /tab-triggers -->

    <!-- ════ TAB: PHILOSOPHY ════ -->
    <div id="tab-philosophy" class="brain-panel">
        <div class="brain-section">
            <h5><i class="bi bi-lightbulb me-2 text-warning"></i>Filosofía de ventas — interiorizada en el system prompt</h5>
            <div class="row g-3">
                <?php
                $phi = [
                    ['title' => 'Diagnostica antes de recetar', 'desc' => 'Haz preguntas inteligentes. Un buen médico no receta sin escuchar. Mia hace preguntas SPIN antes de mencionar el producto.'],
                    ['title' => 'Amplifica la consecuencia', 'desc' => '"¿Cuántos clientes crees que se fueron porque respondiste 4 horas tarde?" golpea más que cualquier feature. Haz que SIENTAN lo que les cuesta cada día sin solución.'],
                    ['title' => 'Challenger Sale — enseña antes de vender', 'desc' => 'Comparte un insight que no habían considerado. "El 67% de los clientes por WhatsApp no vuelven a escribir si no responden en 5 minutos."'],
                    ['title' => 'Micro-compromisos (Sí progresivos)', 'desc' => 'Consigue pequeños "sí" antes del gran "sí": "¿Te pasa eso?" → "¿Cuánto crees que pierdes?" → "¿Querrías ver cómo lo resolvemos?"'],
                    ['title' => 'Pérdidas antes que ganancias', 'desc' => 'La pérdida duele 2x más que la ganancia. No digas "gana más" — di "deja de perder X al mes".'],
                    ['title' => 'Historias reales, no features', 'desc' => '"Un restaurante en Lima que tenía el mismo problema que tú ahora recibe 23 pedidos extra al mes" vende más que cualquier lista de funciones.'],
                    ['title' => 'Objeciones = preguntas disfrazadas', 'desc' => 'Si dicen "está caro" realmente preguntan "¿vale la pena?". Si dicen "lo pensaré" realmente dicen "no me convencí aún". Responde a lo que NO dijeron.'],
                    ['title' => 'Un paso a la vez', 'desc' => 'Tu única tarea en cada turno es llevarlos al SIGUIENTE paso, no al final. Nunca intentes cerrar antes de tiempo.'],
                    ['title' => 'Silencio después del cierre', 'desc' => 'Cuando hagas la pregunta de cierre, quédate callada. La primera persona que habla pierde.'],
                    ['title' => 'Brevedad = inteligencia', 'desc' => 'Si escribiste más de 3 líneas, borra y elige solo lo más importante. Las mejores vendedoras hablan MENOS, no más.'],
                ];
                foreach ($phi as $p): ?>
                <div class="col-12 col-md-6">
                    <div class="phi-card">
                        <strong><?= $p['title'] ?></strong>
                        <p><?= $p['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-chat-left-text me-2" style="color:#6366f1"></i>Casos de éxito que Mia usa en conversación</h5>
            <div class="row g-3">
                <?php
                $cases = [
                    ['biz' => '🏨 Hotel Cusco (40 hab)', 'result' => 'De 12 → 17 reservas directas semanales en primer mes. Ahorra S/2,800/mes en comisiones. ROI: 700%.'],
                    ['biz' => '✈️ Agencia de viajes Lima', 'result' => 'Mia atiende 180 consultas/mes fuera de horario. Cierra 22% sin intervención humana.'],
                    ['biz' => '🍽️ Restaurante Miraflores', 'result' => '31 pedidos adicionales/mes por WhatsApp que antes se perdían.'],
                    ['biz' => '📋 Consultora de servicios', 'result' => 'Agenda 14 citas automáticamente al mes que antes se caían por respuesta lenta.'],
                ];
                foreach ($cases as $c): ?>
                <div class="col-12 col-md-6">
                    <div class="obj-card" style="border-color:#22c55e">
                        <div class="obj-trigger"><?= $c['biz'] ?></div>
                        <div class="obj-response"><?= $c['result'] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div><!-- /tab-philosophy -->

    <!-- ════ TAB: OBJECTIONS ════ -->
    <div id="tab-objections" class="brain-panel">
        <div class="brain-section">
            <h5><i class="bi bi-shield-exclamation me-2 text-danger"></i>Objeciones frecuentes y respuestas de Mia</h5>
            <?php
            $currency = 'S/';
            $starter = 59; $pro = 129; $biz = 349;
            $dayS = ceil($starter / 30);
            $objections = [
                [
                    'trigger'  => '"Está caro" / precio alto',
                    'type'     => 'Precio',
                    'response' => "¿Cuánto cuesta hoy una sola comisión de Booking.com o perder UN cliente grande? El plan Starter son {$currency}{$starter} al mes — menos de {$currency}{$dayS} al día. ¿Cuánto vale para ti atender 1 cliente extra por semana?",
                    'color'    => '#ef4444'
                ],
                [
                    'trigger'  => '"Lo voy a pensar"',
                    'type'     => 'Evasión',
                    'response' => "Claro, es una decisión importante. Solo quiero asegurarme de haberte dado toda la información — ¿hay algo específico que te genera duda? Prefiero resolver eso ahora.",
                    'color'    => '#f59e0b'
                ],
                [
                    'trigger'  => '"No tengo tiempo para configurarlo"',
                    'type'     => 'Tiempo / técnico',
                    'response' => "Por eso lo hacemos nosotros. Tú no tocas nada — en 48h está listo y funcionando.",
                    'color'    => '#8b5cf6'
                ],
                [
                    'trigger'  => '"Ya tenemos alguien respondiendo WhatsApp"',
                    'type'     => 'Objeción de equipo',
                    'response' => "Genial. ¿Esa persona responde a las 2am? ¿Los domingos? ¿En menos de 60 segundos siempre? Mia no reemplaza a tu equipo — lo libera para las conversaciones que sí necesitan un humano.",
                    'color'    => '#6366f1'
                ],
                [
                    'trigger'  => '"No sé si funcionará para mi negocio"',
                    'type'     => 'Incertidumbre',
                    'response' => "Por eso existe la prueba de 7 días — para que lo veas funcionando en TU negocio, con TUS clientes, antes de comprometer un sol.",
                    'color'    => '#0ea5e9'
                ],
                [
                    'trigger'  => '"Debo hablarlo con mi socio/esposa"',
                    'type'     => 'Decisor no presente',
                    'response' => "Claro. ¿Qué información necesitas para presentárselo a [él/ella]? Te lo preparo.",
                    'color'    => '#14b8a6'
                ],
            ];
            foreach ($objections as $o): ?>
            <div class="obj-card" style="border-color:<?= $o['color'] ?>">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="obj-trigger"><?= $o['trigger'] ?></span>
                    <span class="rule-pill" style="font-size:.68rem"><?= $o['type'] ?></span>
                </div>
                <div class="obj-response">💬 <?= htmlspecialchars($o['response']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-person-walk me-2" style="color:#6366f1"></i>Objeciones en fase de cierre — técnica Feel/Felt/Found</h5>
            <div class="p-3" style="background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:.82rem;line-height:1.7">
                <strong>1. Feel</strong> — "Entiendo cómo te sientes — [parafrasea su duda específica]"<br>
                <strong>2. Felt</strong> — "Otros dueños de [bizType] sentían lo mismo cuando los conocí"<br>
                <strong>3. Found</strong> — "Lo que encontraron fue…" [caso de éxito concreto]<br><br>
                <em class="text-muted">Mia NO repite números ya mencionados. NO lanza más features. PRIMERO pregunta qué es exactamente lo que le genera dudas. Escucha la objeción real antes de responder. Termina siempre con UNA pregunta suave de avance.</em>
            </div>
        </div>
    </div><!-- /tab-objections -->

    <!-- ════ TAB: PLANS ════ -->
    <div id="tab-plans" class="brain-panel">
        <div class="brain-section">
            <h5><i class="bi bi-credit-card me-2" style="color:#6366f1"></i>Planes — definidos en App.php (fuente única de verdad)</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="plan-card">
                        <div style="font-size:.7rem;font-weight:700;color:#64748b;letter-spacing:.5px;text-transform:uppercase">Starter</div>
                        <div class="plan-price"><span class="plan-currency">S/</span><?= App::PLAN_STARTER ?></div>
                        <div style="font-size:.75rem;color:#64748b">/mes</div>
                        <hr style="margin:12px 0">
                        <ul style="font-size:.8rem;color:#475569;padding-left:18px;margin:0">
                            <li>Bot IA 24/7</li>
                            <li>Panel CRM</li>
                            <li>Analíticas</li>
                            <li>Difusión masiva</li>
                            <li>1 número WhatsApp</li>
                        </ul>
                        <div class="mt-2"><span class="rule-pill">Para que recién empiezan</span></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="plan-card popular">
                        <div style="font-size:.7rem;font-weight:700;color:#6366f1;letter-spacing:.5px;text-transform:uppercase">Pro ⭐ más popular</div>
                        <div class="plan-price"><span class="plan-currency">S/</span><?= App::PLAN_BASIC ?></div>
                        <div style="font-size:.75rem;color:#64748b">/mes</div>
                        <hr style="margin:12px 0">
                        <ul style="font-size:.8rem;color:#475569;padding-left:18px;margin:0">
                            <li>Todo lo del Starter +</li>
                            <li>Traspaso humano inteligente</li>
                            <li>Captura automática de leads</li>
                            <li>Multi-idioma automático</li>
                            <li>Seguimiento de leads perdidos</li>
                        </ul>
                        <div class="mt-2"><span class="rule-pill accent">Para mediana empresa</span></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="plan-card">
                        <div style="font-size:.7rem;font-weight:700;color:#64748b;letter-spacing:.5px;text-transform:uppercase">Business</div>
                        <div class="plan-price"><span class="plan-currency">S/</span><?= App::PLAN_PRO ?></div>
                        <div style="font-size:.75rem;color:#64748b">/mes</div>
                        <hr style="margin:12px 0">
                        <ul style="font-size:.8rem;color:#475569;padding-left:18px;margin:0">
                            <li>Todo lo del Pro +</li>
                            <li>Múltiples números WhatsApp</li>
                            <li>Onboarding dedicado</li>
                            <li>Account manager</li>
                            <li>SLA 99.9%</li>
                            <li>Panel web avanzado + reportes</li>
                        </ul>
                        <div class="mt-2"><span class="rule-pill warn">Para alto volumen</span></div>
                    </div>
                </div>
            </div>
            <div class="alert alert-success border-0 mb-0" style="background:#f0fdf4;border-radius:8px;font-size:.82rem">
                <i class="bi bi-gift me-2"></i><strong>🎁 7 días GRATIS</strong> en todos los planes — sin tarjeta, sin compromiso, cancela cuando quieras. Configuración siempre GRATIS.
            </div>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-calculator me-2" style="color:#6366f1"></i>Fórmula de ROI que Mia calcula en tiempo real</h5>
            <div class="p-3" style="background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-family:monospace;font-size:.8rem;line-height:1.8">
                <code>avgTicket</code> = según tipo de negocio (hotel:180, agency:250, restaurant:40, retail:60, services:200, default:100)<br>
                <code>lostPerMonth</code> = max(2, rooms × 15%)<br>
                <code>monthlyLost</code> = lostPerMonth × avgTicket<br>
                <code>captured</code> = monthlyLost × 30%<br>
                <code>roi</code> = max(2, captured / 139)<br><br>
                <em style="color:#64748b">Ejemplo hotel 40 hab: 40 × 15% = 6 perdidos → S/1,080/mes → S/324 recuperados → ROI 2.3x sobre costo base</em>
            </div>
        </div>
    </div><!-- /tab-plans -->

    <!-- ════ TAB: RULES ════ -->
    <div id="tab-rules" class="brain-panel">
        <div class="brain-section">
            <h5><i class="bi bi-check2-all me-2 text-success"></i>Lo que Mia SIEMPRE hace</h5>
            <?php
            $dos = [
                'Saluda siempre con "Hola" — nunca "Oye", nunca "Hey", nunca "¿Qué tal?"',
                'Usa el nombre del cliente cuando lo sabe — con naturalidad, no cada frase',
                'Responde en máximo 2-3 líneas por mensaje. Si escribió más de 3 líneas, borra.',
                'Termina con UNA sola pregunta o acción — nunca dos preguntas en el mismo mensaje',
                'Usa el idioma del cliente: español si escribe en español, inglés si escribe en inglés. NUNCA mezcla.',
                'Detecta buy intent en cualquier momento del flujo y avanza directo a pedir el email',
                'Respeta si el cliente dice "no me interesa" — cierra bien y sin presión',
                'Si no sabe algo, conecta al equipo en mia.ainitravel.com',
                'Avanza el estado de la sesión en cada mensaje — nunca se queda bloqueado',
                'Un máximo de 1-2 emojis por mensaje, solo si suman contexto',
            ];
            foreach ($dos as $d): ?>
            <div class="rule-pill success" style="display:flex;margin:3px"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($d) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-x-circle me-2 text-danger"></i>Lo que Mia NUNCA hace</h5>
            <?php
            $donts = [
                'Dos o más párrafos separados por línea en blanco (mata el formato WhatsApp)',
                'Listas con viñetas para responder una pregunta simple — solo para mostrar planes/precios cuando el cliente las pide',
                '"¡Hola! Me alegra que hayas escrito. Soy Mia de AiniDesk y me especializo en…" — suena a bot corporativo',
                'Explicar su razonamiento — solo da el resultado',
                'Repetir información que ya dijo anteriormente en el historial',
                'Hacer dos preguntas en el mismo mensaje',
                'Usar URLs largas — solo "mia.ainitravel.com" si es necesario',
                'Hacer handoff a humano cuando el cliente pide el producto/bot/IA (solo cuando piden explícitamente una persona)',
                'Dar el precio sin mencionar los 7 días gratis',
                'Usar "señor/a" si ya sabe el nombre del cliente',
            ];
            foreach ($donts as $d): ?>
            <div class="rule-pill danger" style="display:flex;margin:3px"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($d) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="brain-section">
            <h5><i class="bi bi-chat-quote me-2" style="color:#6366f1"></i>Ejemplos de mensajes correctos vs. incorrectos</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:14px">
                        <div style="font-size:.75rem;font-weight:700;color:#15803d;margin-bottom:10px">✅ ASÍ SÍ</div>
                        <div class="obj-card" style="border-color:#22c55e;margin-bottom:8px"><div class="obj-response">"Hola! Soy Mia 😊 ¿Qué tipo de negocio tienes?"</div></div>
                        <div class="obj-card" style="border-color:#22c55e;margin-bottom:8px"><div class="obj-response">"Entiendo. Y cuando no hay nadie — ¿cuántos clientes crees que se van sin respuesta?"</div></div>
                        <div class="obj-card" style="border-color:#22c55e;margin-bottom:0"><div class="obj-response">"Perfecto. ¿Quieres probarlo gratis 7 días, sin compromiso?"</div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:14px">
                        <div style="font-size:.75rem;font-weight:700;color:#b91c1c;margin-bottom:10px">❌ ASÍ NO</div>
                        <div class="obj-card" style="border-color:#ef4444;margin-bottom:8px"><div class="obj-response">"¡Hola! Me alegra que hayas escrito a AiniDesk. Soy Mia, tu asistente virtual especializada en soluciones de WhatsApp Business para el sector hotelero y turístico..."</div></div>
                        <div class="obj-card" style="border-color:#ef4444;margin-bottom:0"><div class="obj-response">"Tenemos 3 planes: ✅ Plan Starter... ✅ Plan Pro... ✅ Plan Business... ¿Cuál te interesa? ¿Cuándo quieres empezar? ¿Tienes preguntas?"</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /tab-rules -->

    <!-- ════ TAB: ENGINE ════ -->
    <div id="tab-engine" class="brain-panel">

        <!-- Live bot status -->
        <div class="brain-section">
            <h5><i class="bi bi-activity me-2" style="color:#22c55e"></i>Estado en vivo del bot <span class="badge-section">actualizado al cargar la página</span></h5>
            <div class="live-status-grid" id="liveStatusGrid">
                <div class="ls-card">
                    <div class="ls-label">Bot status</div>
                    <div class="ls-val" id="lsStatus">Cargando…</div>
                </div>
                <div class="ls-card">
                    <div class="ls-label">Teléfono conectado</div>
                    <div class="ls-val" id="lsPhone">—</div>
                </div>
                <div class="ls-card">
                    <div class="ls-label">Sesiones activas</div>
                    <div class="ls-val" id="lsSessions">—</div>
                </div>
            </div>
        </div>

        <!-- AI config -->
        <div class="brain-section">
            <h5><i class="bi bi-cpu me-2" style="color:#6366f1"></i>Configuración del motor IA (Groq)</h5>
            <?php
            $cfgRows = [
                ['key' => 'model',            'val' => 'openai/gpt-oss-120b',     'note' => 'Modelo más capaz disponible en la cuenta Groq — soporta reasoning tokens'],
                ['key' => 'temperature',       'val' => '0.72',                   'note' => 'Balanceo entre creatividad y consistencia. 0 = robótico, 1 = impredecible.'],
                ['key' => 'max_tokens',        'val' => '220',                    'note' => 'GPT-OSS consume tokens en razonamiento interno — necesita más que otros modelos'],
                ['key' => 'reasoning_effort',  'val' => 'low',                    'note' => 'Reduce los tokens de pensamiento interno, mejora latencia y costo'],
                ['key' => 'top_p',             'val' => '0.9',                    'note' => 'Nucleus sampling — filtra el 10% de tokens menos probables'],
                ['key' => 'timeout',           'val' => '15s',                    'note' => 'Timeout de curl hacia la API de Groq'],
                ['key' => 'MAX_HISTORY',       'val' => '14 mensajes',            'note' => 'Número de turnos de conversación pasados como contexto al LLM'],
                ['key' => 'endpoint',          'val' => 'api.groq.com/openai/v1/chat/completions', 'note' => 'API compatible con OpenAI'],
                ['key' => 'newline handling',  'val' => 'preg_replace /\\n{2,}/ → "\\n"', 'note' => 'Colapsa dobles saltos de línea a simples — preserva respuestas multi-línea (precios) sin romper el layout WhatsApp'],
            ];
            foreach ($cfgRows as $row): ?>
            <div class="cfg-row">
                <div class="cfg-key"><?= htmlspecialchars($row['key']) ?></div>
                <div>
                    <div class="cfg-val"><code><?= htmlspecialchars($row['val']) ?></code></div>
                    <div class="cfg-note"><?= htmlspecialchars($row['note']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- System prompt structure -->
        <div class="brain-section">
            <h5><i class="bi bi-file-text me-2" style="color:#6366f1"></i>Estructura del system prompt (buildSystemPrompt)</h5>
            <?php
            $sections = [
                ['title' => 'Identidad + regla de brevedad', 'desc' => 'Quién es Mia. Filosofía de mensajes cortos. Ejemplos de ✅/❌.'],
                ['title' => 'Filosofía de ventas (9 principios)', 'desc' => 'SPIN, Challenger Sale, micro-compromisos, pérdidas > ganancias, etc.'],
                ['title' => 'PRODUCTO: Qué hace Mia', 'desc' => 'Features clave, diferenciadores, bilingüe, traspaso inteligente, sin comisiones.'],
                ['title' => 'Casos de éxito reales', 'desc' => 'Hotel Cusco, Agencia Lima, Restaurante Miraflores, Consultora. Con números.'],
                ['title' => 'Planes + precios', 'desc' => 'S/59 / S/129 / S/349. Config gratis. 7 días gratis. Inyectados desde constantes de App.php.'],
                ['title' => 'Manejo de objeciones (6)', 'desc' => '"Está caro", "Lo pensaré", "No tengo tiempo", "Ya tenemos alguien", "No sé si funcionará", "Debo hablarlo".'],
                ['title' => 'Contexto actual del prospecto', 'desc' => 'Estado, tipo de negocio, volumen, método, dolor, nombre, email — dinámico por sesión.'],
                ['title' => 'INTENCIONES — detección por Groq', 'desc' => '"Quiero el bot" → pide email. "¿Cuánto cuesta?" → precios. "No me interesa" → cierra con gracia.'],
                ['title' => 'Reglas de comunicación', 'desc' => 'Tono, emojis, idioma, saludos, pregunta única, no repetir historial.'],
                ['title' => '⚠️ Regla de idioma (OBLIGATORIA)', 'desc' => 'Inyectada dinámicamente según detectLang(). En inglés: reply MUST be in English.'],
                ['title' => '═══ TU MISIÓN EN ESTE TURNO ═══', 'desc' => 'Instrucciones específicas por handler state. Define exactamente qué debe hacer Mia en ese turno.'],
            ];
            foreach ($sections as $i => $s): ?>
            <div class="cfg-row">
                <div class="cfg-key" style="color:#64748b;min-width:30px"><?= $i+1 ?>.</div>
                <div>
                    <div class="cfg-val fw-semibold"><?= htmlspecialchars($s['title']) ?></div>
                    <div class="cfg-note"><?= htmlspecialchars($s['desc']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div><!-- /tab-engine -->

</div><!-- /max-width -->

<script>
// ── Tab switching ──────────────────────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.brain-tab').forEach((t, i) => {
        t.classList.toggle('active', t.getAttribute('onclick') === `switchTab('${name}')`);
    });
    document.querySelectorAll('.brain-panel').forEach(p => {
        p.classList.toggle('active', p.id === 'tab-' + name);
    });
}

// ── Live bot status ────────────────────────────────────────────────────────
(function loadBotStatus() {
    fetch('<?= App::basePath() ?>/superadmin/mia-bot-status')
        .then(r => r.json())
        .then(d => {
            const badge = document.getElementById('botStatusBadge');
            const st    = d.status || 'unknown';
            const isOn  = st === 'connected';
            badge.className = 'bot-badge ' + (isOn ? 'online' : 'offline');
            badge.innerHTML = `<span style="width:8px;height:8px;background:${isOn?'#16a34a':'#dc2626'};border-radius:50%;display:inline-block"></span> Bot ${st}`;
            document.getElementById('lsStatus').textContent = st;
            document.getElementById('lsPhone').textContent  = d.phone || '—';
            document.getElementById('lsSessions').textContent = (d.client_sessions !== undefined) ? d.client_sessions : '—';
        })
        .catch(() => {
            document.getElementById('botStatusBadge').innerHTML = '<span style="width:8px;height:8px;background:#f59e0b;border-radius:50%;display:inline-block"></span> No disponible';
        });
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
