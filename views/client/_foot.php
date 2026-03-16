    </main><!-- /.mc-content -->
</div><!-- /.mc-main-area -->

</div><!-- /.mc-wrapper -->
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
<script>
function openSidebar() {
    document.getElementById('mcSidebar').classList.add('open');
    document.getElementById('mcBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('mcSidebar').classList.remove('open');
    document.getElementById('mcBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}
// Close sidebar when a nav link is tapped on mobile
document.querySelectorAll('#mcSidebar .mc-nav-item').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) closeSidebar();
    });
});
</script>

<?php
// Show Mia help chat widget only while the client hasn't finished onboarding
$_showMiaHelp = !empty($_SESSION['mia_client_id'])
             && empty($_SESSION['mia_client']['onboarding_done']);
?>
<?php if ($_showMiaHelp): ?>
<!-- ── Mia onboarding help chat widget ──────────────────────────────────────── -->
<style>
#mia-help-btn{position:fixed;bottom:24px;right:24px;z-index:1060;width:56px;height:56px;border-radius:50%;background:#25d366;border:none;box-shadow:0 4px 16px rgba(37,211,102,.45);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s}
#mia-help-btn:hover{transform:scale(1.08)}
#mia-help-btn .mia-notif{position:absolute;top:2px;right:2px;width:14px;height:14px;background:#ff4b4b;border-radius:50%;border:2px solid #fff;display:none}
#mia-help-panel{position:fixed;bottom:90px;right:24px;z-index:1059;width:320px;max-height:480px;border-radius:16px;background:#fff;box-shadow:0 8px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden}
#mia-help-panel.open{display:flex}
#mia-help-header{background:#25d366;color:#fff;padding:12px 16px;display:flex;align-items:center;gap:10px;flex-shrink:0}
#mia-help-header .mia-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center}
#mia-help-messages{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px}
.mia-msg{max-width:82%;padding:8px 11px;border-radius:12px;font-size:.83rem;line-height:1.4;word-break:break-word}
.mia-msg.bot{background:#f0f0f0;color:#1a1a1a;align-self:flex-start;border-bottom-left-radius:3px}
.mia-msg.user{background:#dcf8c6;color:#1a1a1a;align-self:flex-end;border-bottom-right-radius:3px}
#mia-help-input-wrap{display:flex;gap:6px;padding:10px;border-top:1px solid #f0f0f0;flex-shrink:0}
#mia-help-input{flex:1;border:1px solid #dee2e6;border-radius:20px;padding:7px 14px;font-size:.83rem;outline:none}
#mia-help-input:focus{border-color:#25d366;box-shadow:0 0 0 2px rgba(37,211,102,.2)}
#mia-help-send{width:36px;height:36px;border-radius:50%;background:#25d366;border:none;color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0}
#mia-help-send:disabled{opacity:.5;cursor:default}
</style>

<button id="mia-help-btn" title="Pregúntale a Mia">
    <i class="bi bi-whatsapp" style="color:#fff;font-size:1.4rem"></i>
    <span class="mia-notif" id="mia-notif-dot"></span>
</button>

<div id="mia-help-panel">
    <div id="mia-help-header">
        <div class="mia-avatar"><i class="bi bi-robot" style="font-size:1rem"></i></div>
        <div>
            <div style="font-weight:700;font-size:.9rem">Mia te ayuda</div>
            <div style="font-size:.72rem;opacity:.85">Asistente de configuración</div>
        </div>
        <button onclick="miaHelpClose()" style="margin-left:auto;background:none;border:none;color:#fff;font-size:1.1rem;line-height:1;cursor:pointer">&times;</button>
    </div>
    <div id="mia-help-messages">
        <div class="mia-msg bot">¡Hola! Soy Mia 👋 Estoy aquí para ayudarte a configurar tu bot. ¿Tienes alguna pregunta?</div>
    </div>
    <div id="mia-help-input-wrap">
        <input id="mia-help-input" type="text" placeholder="Escribe tu pregunta..." maxlength="400" autocomplete="off">
        <button id="mia-help-send" onclick="miaHelpSend()"><i class="bi bi-send-fill" style="font-size:.8rem"></i></button>
    </div>
</div>

<script>
(function(){
    var BASE     = '<?= App::basePath() ?>';
    var history  = JSON.parse(sessionStorage.getItem('mia_ob_history') || '[]');
    var open     = false;
    var notifShown = false;

    // Show notification dot after 8s if panel hasn't been opened
    setTimeout(function(){
        if (!open && !notifShown) {
            document.getElementById('mia-notif-dot').style.display = 'block';
            notifShown = true;
        }
    }, 8000);

    document.getElementById('mia-help-btn').addEventListener('click', function(){
        open ? miaHelpClose() : miaHelpOpen();
    });

    window.miaHelpOpen = function(){
        open = true;
        document.getElementById('mia-help-panel').classList.add('open');
        document.getElementById('mia-notif-dot').style.display = 'none';
        scrollBottom();
        document.getElementById('mia-help-input').focus();
    };
    window.miaHelpClose = function(){
        open = false;
        document.getElementById('mia-help-panel').classList.remove('open');
    };

    document.getElementById('mia-help-input').addEventListener('keydown', function(e){
        if (e.key === 'Enter') miaHelpSend();
    });

    window.miaHelpSend = function(){
        var inp  = document.getElementById('mia-help-input');
        var send = document.getElementById('mia-help-send');
        var text = inp.value.trim();
        if (!text) return;

        addMsg(text, 'user');
        history.push({role:'user', content:text});
        inp.value = '';
        send.disabled = true;

        var typing = addMsg('...', 'bot');

        fetch(BASE + '/api/onboarding-help', {
            method:  'POST',
            headers: {'Content-Type':'application/json'},
            body:    JSON.stringify({message: text, history: history.slice(-6)})
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            typing.textContent = d.reply || '¿En qué más puedo ayudarte?';
            history.push({role:'assistant', content: typing.textContent});
            history = history.slice(-14); // keep last 7 turns
            sessionStorage.setItem('mia_ob_history', JSON.stringify(history));
            scrollBottom();
        })
        .catch(function(){
            typing.textContent = 'Tuve un problema. Intenta de nuevo.';
        })
        .finally(function(){ send.disabled = false; });
    };

    function addMsg(text, role){
        var msgs = document.getElementById('mia-help-messages');
        var div  = document.createElement('div');
        div.className = 'mia-msg ' + role;
        div.textContent = text;
        msgs.appendChild(div);
        scrollBottom();
        return div;
    }

    function scrollBottom(){
        var msgs = document.getElementById('mia-help-messages');
        msgs.scrollTop = msgs.scrollHeight;
    }
})();
</script>
<?php endif; ?>

</body>
</html>
