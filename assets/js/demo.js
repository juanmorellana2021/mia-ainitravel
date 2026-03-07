/**
 * mia/assets/js/demo.js
 * Interactive WhatsApp demo conversation simulator
 */
(function () {
    'use strict';

    var chat = document.getElementById('demoChat');
    var btn  = document.getElementById('btnNextStep');
    var info = document.getElementById('demoExplanation');
    if (!chat || !btn) return;

    var steps = [
        {
            who: 'user',
            text: 'Hola! Vi su hotel en Facebook y me interesa reservar una habitación para 2 personas',
            explain: '<strong>Paso 1:</strong> El huésped ve tu anuncio en Facebook y te escribe por WhatsApp. Mia responde al instante.'
        },
        {
            who: 'bot',
            text: '¡Hola! 😊 Bienvenido al Hotel Sol de Cusco. Con mucho gusto te ayudo con tu reserva.\n\n¿Para qué fechas sería?',
            explain: '<strong>Paso 2:</strong> Mia saluda y pregunta las fechas. Respuesta en menos de 5 segundos.'
        },
        {
            who: 'user',
            text: 'Del 15 al 18 de abril',
            explain: '<strong>Paso 3:</strong> El huésped da las fechas. Mia las entiende en cualquier formato.'
        },
        {
            who: 'bot',
            text: 'Perfecto — 3 noches (15-18 abril) 📅\n\nTenemos disponible:\n\n1️⃣ Habitación Doble — S/180/noche\n2️⃣ Suite Premium — S/320/noche\n\n¿Cuál te interesa?',
            explain: '<strong>Paso 4:</strong> Mia busca disponibilidad real en tu sistema y muestra opciones con precios.'
        },
        {
            who: 'user',
            text: 'La habitación doble',
            explain: '<strong>Paso 5:</strong> El huésped elige. Mia calcula el total automáticamente.'
        },
        {
            who: 'bot',
            text: 'Excelente elección ✅\n\nHab. Doble × 3 noches = S/540\n\nPara confirmar tu reserva necesito:\n📧 Tu email\n📸 Foto de tu DNI o pasaporte',
            explain: '<strong>Paso 6:</strong> Mia pide el email y documento de identidad para seguridad.'
        },
        {
            who: 'user',
            text: 'carlos.garcia@gmail.com',
            explain: '<strong>Paso 7:</strong> El huésped envía su email.'
        },
        {
            who: 'user',
            text: '📸 [Foto de DNI enviada]',
            explain: '<strong>Paso 8:</strong> El huésped envía foto de su documento — Mia la guarda.'
        },
        {
            who: 'bot',
            text: '🎉 ¡Reserva confirmada!\n\nRef: AINI-DM78A3\nHotel Sol de Cusco\n📅 15-18 abril (3 noches)\n🛏️ Habitación Doble\n💰 Total: S/540\n\nTe enviamos un email de confirmación. ¡Te esperamos! 😊',
            explain: '<strong>¡Listo!</strong> La reserva está hecha. Tú recibes una notificación instantánea con todos los datos del huésped, su foto de DNI, y el monto a cobrar. <strong>Todo automático, sin que hagas nada.</strong>'
        }
    ];

    var currentStep = 0;

    // Show initial bot greeting
    addBubble('bot', '¡Hola! 😊 Soy Mia, asistente del Hotel Sol de Cusco.\n¿En qué puedo ayudarte?');

    btn.addEventListener('click', function () {
        if (currentStep >= steps.length) {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Fin';
            return;
        }

        var step = steps[currentStep];
        addBubble(step.who, step.text);

        if (step.explain && info) {
            info.innerHTML = '<p class="mb-0">' + step.explain + '</p>';
        }

        currentStep++;

        if (currentStep >= steps.length) {
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Demo completa';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
        }
    });

    function addBubble(who, text) {
        var div = document.createElement('div');
        div.className = 'chat-bubble ' + who;
        div.innerHTML = escapeHtml(text).replace(/\n/g, '<br>');

        var time = document.createElement('div');
        time.className = 'time';
        var now = new Date();
        time.textContent = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        div.appendChild(time);

        chat.appendChild(div);
        chat.scrollTop = chat.scrollHeight;
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }
})();
