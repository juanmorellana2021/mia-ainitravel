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

<?php if (!empty($_SESSION['mia_client_id'])): ?>
<!-- ── Mia help chat widget ──────────────────────────────────────────────────── -->
<style>
#mia-help-panel{position:fixed;bottom:60px;left:248px;z-index:1059;width:320px;max-height:460px;border-radius:16px;background:#fff;box-shadow:0 8px 40px rgba(0,0,0,.18);display:none;flex-direction:column;overflow:hidden}
@media(max-width:768px){#mia-help-panel{left:16px;bottom:16px;width:calc(100vw - 32px)}}
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

    window.miaHelpOpen = function(){
        open = true;
        document.getElementById('mia-help-panel').classList.add('open');
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
