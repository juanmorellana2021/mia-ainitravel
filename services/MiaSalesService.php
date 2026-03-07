<?php
/**
 * mia/services/MiaSalesService.php
 *
 * Stateful WhatsApp sales assistant — "Mia"
 *
 * Manages the full B2B sales conversation flow:
 *   intro → qualifying (size, method, pain) → ROI pitch → demo → benefits → close → captured
 *
 * Each phone number gets a session row tracking their position in the funnel.
 * Returns either a direct reply or AI instructions for the LLM.
 */

declare(strict_types=1);

class MiaSalesService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mia_sales_sessions (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                phone           VARCHAR(50) NOT NULL,
                state           VARCHAR(30) DEFAULT 'intro',
                business_name   VARCHAR(255) NULL,
                contact_name    VARCHAR(255) NULL,
                email           VARCHAR(255) NULL,
                business_type   VARCHAR(50)  NULL,
                room_count      INT          NULL,
                current_method  VARCHAR(50)  NULL,
                pain_point      VARCHAR(100) NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Process an incoming WhatsApp message from a prospect.
     * Returns ['reply' => string] for a direct reply, or
     *         ['sofiaInstructions' => string, 'context' => string] for AI.
     */
    public function process(string $phone, string $message): array
    {
        $phone   = $this->normalizePhone($phone);
        $session = $this->getOrCreateSession($phone);
        $msg     = mb_strtolower(trim($message));

        // Reset command
        if (in_array($msg, ['reset', 'reiniciar', 'empezar de nuevo'], true)) {
            $this->resetSession($phone);
            return $this->introReply();
        }

        return match ($session['state']) {
            'intro'             => $this->handleIntro($phone, $msg),
            'qualifying_size'   => $this->handleQualifySize($phone, $msg),
            'qualifying_method' => $this->handleQualifyMethod($phone, $msg),
            'qualifying_pain'   => $this->handleQualifyPain($phone, $msg),
            'roi_pitch'         => $this->handleRoiPitch($phone, $msg, $session),
            'demo'              => $this->handleDemo($phone, $msg),
            'benefits'          => $this->handleBenefits($phone, $msg),
            'closing'           => $this->handleClosing($phone, $msg, $session),
            'collecting_name'   => $this->handleCollectName($phone, $message),
            'collecting_email'  => $this->handleCollectEmail($phone, $msg),
            'captured'          => $this->handleCaptured($phone, $msg),
            default             => $this->introReply(),
        };
    }

    // ════════════════════════════════════════════════════════════════════════
    //  STATE HANDLERS
    // ════════════════════════════════════════════════════════════════════════

    private function handleIntro(string $phone, string $msg): array
    {
        // Any reply to intro → move to qualifying size
        $this->updateSession($phone, ['state' => 'qualifying_size']);

        return ['reply' =>
            "¡Genial! Solo 3 preguntas rápidas para entender tu negocio 😊\n\n" .
            "📊 *Pregunta 1:* ¿Cuántas habitaciones tiene tu hotel?\n" .
            "(Si eres agencia, ¿cuántos clientes manejas al mes?)\n\n" .
            "1️⃣ Menos de 20 habitaciones / hasta 50 clientes\n" .
            "2️⃣ 20-50 hab. / 50-200 clientes\n" .
            "3️⃣ Más de 50 hab. / más de 200 clientes"
        ];
    }

    private function handleQualifySize(string $phone, string $msg): array
    {
        $roomCount = 0;
        $bizType = 'hotel';

        if (str_contains($msg, 'agencia') || str_contains($msg, 'agency')) {
            $bizType = 'agency';
        } elseif (str_contains($msg, 'hostal') || str_contains($msg, 'hostel')) {
            $bizType = 'hostel';
        } elseif (str_contains($msg, 'restaurante') || str_contains($msg, 'restaurant')) {
            $bizType = 'restaurant';
        }

        if (str_contains($msg, '1') || str_contains($msg, 'menos') || str_contains($msg, 'less')) {
            $roomCount = 15;
        } elseif (str_contains($msg, '2') || str_contains($msg, 'medio') || str_contains($msg, 'medium')) {
            $roomCount = 35;
        } elseif (str_contains($msg, '3') || str_contains($msg, 'mas') || str_contains($msg, 'más') || str_contains($msg, 'more')) {
            $roomCount = 75;
        } else {
            // Try to extract a number
            if (preg_match('/(\d+)/', $msg, $m)) {
                $roomCount = (int) $m[1];
            }
        }

        $this->updateSession($phone, [
            'state'         => 'qualifying_method',
            'room_count'    => $roomCount,
            'business_type' => $bizType,
        ]);

        return ['reply' =>
            "Perfecto 👍\n\n" .
            "📋 *Pregunta 2:* ¿Cómo reciben reservas actualmente?\n\n" .
            "1️⃣ Por llamadas / WhatsApp manual\n" .
            "2️⃣ Por formulario en la web\n" .
            "3️⃣ Por OTAs (Booking.com, Airbnb, Expedia...)\n" .
            "4️⃣ Una mezcla de todo"
        ];
    }

    private function handleQualifyMethod(string $phone, string $msg): array
    {
        $method = 'mixed';
        if (str_contains($msg, '1') || str_contains($msg, 'manual') || str_contains($msg, 'llamada') || str_contains($msg, 'whatsapp')) {
            $method = 'manual';
        } elseif (str_contains($msg, '2') || str_contains($msg, 'formulario') || str_contains($msg, 'web')) {
            $method = 'web_form';
        } elseif (str_contains($msg, '3') || str_contains($msg, 'booking') || str_contains($msg, 'airbnb') || str_contains($msg, 'ota')) {
            $method = 'otas';
        }

        $this->updateSession($phone, [
            'state'          => 'qualifying_pain',
            'current_method' => $method,
        ]);

        return ['reply' =>
            "Entendido ✅\n\n" .
            "🎯 *Última pregunta:* ¿Qué es lo que más te frustra con las reservas?\n\n" .
            "1️⃣ Pierdo clientes fuera del horario de oficina 🌙\n" .
            "2️⃣ Me toma mucho tiempo responder mensajes ⏰\n" .
            "3️⃣ Los clientes preguntan pero nunca confirman 😤\n" .
            "4️⃣ Pago comisiones altas a Booking/Airbnb 💸"
        ];
    }

    private function handleQualifyPain(string $phone, string $msg): array
    {
        $pain = 'general';
        if (str_contains($msg, '1') || str_contains($msg, 'horario') || str_contains($msg, 'noche') || str_contains($msg, 'after')) {
            $pain = 'after_hours';
        } elseif (str_contains($msg, '2') || str_contains($msg, 'tiempo') || str_contains($msg, 'slow') || str_contains($msg, 'lento')) {
            $pain = 'slow_replies';
        } elseif (str_contains($msg, '3') || str_contains($msg, 'confirman') || str_contains($msg, 'nunca') || str_contains($msg, 'no confirm')) {
            $pain = 'no_confirm';
        } elseif (str_contains($msg, '4') || str_contains($msg, 'comision') || str_contains($msg, 'comisión') || str_contains($msg, 'commission') || str_contains($msg, 'booking')) {
            $pain = 'high_commissions';
        }

        $this->updateSession($phone, [
            'state'      => 'roi_pitch',
            'pain_point' => $pain,
        ]);

        // Build ROI message based on their pain + size
        $session = $this->getSession($phone);
        return $this->buildRoiPitch($session);
    }

    private function buildRoiPitch(array $s): array
    {
        $rooms = (int) ($s['room_count'] ?? 20);
        $pain  = $s['pain_point'] ?? 'general';

        // Calculate personalized ROI
        $lostPerNight  = max(2, (int) ($rooms * 0.15));  // ~15% of rooms as missed inquiries
        $avgNightPrice = 180; // PEN average
        $monthlyLost   = $lostPerNight * $avgNightPrice * 30;
        $captured      = (int) ($monthlyLost * 0.30);  // Conservative 30% capture rate

        $painMessages = [
            'after_hours'      => "📊 Tu hotel recibe aproximadamente *$lostPerNight consultas por noche* fuera de horario. Sin respuesta automática, se van a la competencia.",
            'slow_replies'     => "📊 Cada minuto que tardas en responder, la probabilidad de cerrar la reserva baja un 10%. Con Mia, la respuesta es *instantánea*.",
            'no_confirm'       => "📊 El 70% de consultas por WhatsApp no se convierten en reserva porque el seguimiento es manual. Mia guía al cliente hasta el pago.",
            'high_commissions' => "📊 Booking.com cobra entre 15-25% de comisión. Mia te trae reservas directas a *costo fijo* — sin comisiones por reserva.",
            'general'          => "📊 En promedio, un hotel pierde *30% de sus reservas potenciales* por no responder mensajes a tiempo.",
        ];

        $painMsg = $painMessages[$pain] ?? $painMessages['general'];

        return ['reply' =>
            "Gracias por tus respuestas 🙏\n\n" .
            "Déjame mostrarte los números reales de tu negocio:\n\n" .
            $painMsg . "\n\n" .
            "💰 *Estimación para tu hotel:*\n" .
            "• Reservas potenciales perdidas/mes: ~S/" . number_format($monthlyLost) . "\n" .
            "• Con Mia podrías recuperar: ~*S/" . number_format($captured) . "/mes*\n" .
            "• Mia cuesta: *S/399/mes*\n\n" .
            "📈 *Retorno: por cada S/1 que inviertes, recuperas S/" . max(2, (int) ($captured / 399)) . "*\n\n" .
            "¿Quieres ver una demo en vivo de cómo funciona? Te muestro en 2 minutos 🎬\n\n" .
            "Escribe *demo* para verlo en acción"
        ];
    }

    private function handleRoiPitch(string $phone, string $msg, array $session): array
    {
        if (str_contains($msg, 'demo') || str_contains($msg, 'si') || str_contains($msg, 'sí') || str_contains($msg, 'yes') || str_contains($msg, 'ver') || str_contains($msg, 'muestra')) {
            $this->updateSession($phone, ['state' => 'demo']);
            return $this->showDemo();
        }

        // Not interested yet — re-pitch
        return ['reply' =>
            "Entiendo que quieras pensarlo. Pero mira — aquí hay un dato:\n\n" .
            "🏨 El *85% de viajeros* contactan hoteles por WhatsApp antes de reservar.\n" .
            "Si no respondes en *5 minutos*, se van al siguiente hotel.\n\n" .
            "¿Te muestro cómo Mia resuelve esto? Solo escribe *demo* 😊"
        ];
    }

    private function showDemo(): array
    {
        return ['reply' =>
            "🎬 *DEMO EN VIVO — Así ve tu huésped el bot:*\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
            "🤖 *Mia (tu asistente):*\n" .
            "¡Hola! Bienvenido al Hotel Sol de Cusco ☀️\n" .
            "¿En qué puedo ayudarte?\n\n" .
            "👤 *Huésped:*\n" .
            "Hola quiero reservar una habitación para 2 personas\n\n" .
            "🤖 *Mia:*\n" .
            "¡Con gusto! ¿Para qué fechas sería la reserva?\n\n" .
            "👤 *Huésped:*\n" .
            "Del 15 al 18 de abril\n\n" .
            "🤖 *Mia:*\n" .
            "Perfecto — 3 noches (15-18 abril). Tenemos disponible:\n\n" .
            "1️⃣ Habitación Doble — S/180/noche\n" .
            "2️⃣ Suite Premium — S/320/noche\n\n" .
            "👤 *Huésped:*\n" .
            "La doble\n\n" .
            "🤖 *Mia:*\n" .
            "Excelente. Hab. Doble × 3 noches = *S/540*\n" .
            "Para confirmar necesito tu email y una foto de tu DNI/pasaporte 📸\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
            "✅ Al final, *tú recibes* una notificación con:\n" .
            "• Nombre del huésped\n" .
            "• Fechas exactas y habitación\n" .
            "• Total a cobrar\n" .
            "• Foto del documento de identidad\n" .
            "• Email del huésped\n\n" .
            "Todo *automático*, 24/7, sin que hagas nada 🚀\n\n" .
            "¿Qué te pareció? Escribe *beneficios* para ver todo lo incluido,\n" .
            "o *empezar* si ya quieres activar tu prueba gratis 🎁"
        ];
    }

    private function handleDemo(string $phone, string $msg): array
    {
        if (str_contains($msg, 'beneficio') || str_contains($msg, 'incluido') || str_contains($msg, 'incluye') || str_contains($msg, 'feature')) {
            $this->updateSession($phone, ['state' => 'benefits']);
            return $this->showBenefits();
        }

        if (str_contains($msg, 'empezar') || str_contains($msg, 'activar') || str_contains($msg, 'prueba') || str_contains($msg, 'start') || str_contains($msg, 'trial') || str_contains($msg, 'si') || str_contains($msg, 'sí') || str_contains($msg, 'yes')) {
            $this->updateSession($phone, ['state' => 'closing']);
            return $this->showPricing();
        }

        // Default — show benefits
        $this->updateSession($phone, ['state' => 'benefits']);
        return $this->showBenefits();
    }

    private function showBenefits(): array
    {
        return ['reply' =>
            "✨ *Todo lo que incluye Mia:*\n\n" .
            "📱 *Reservas Automáticas 24/7*\n" .
            "Tu WhatsApp toma reservas incluso a las 3am\n\n" .
            "🗣️ *Bilingüe (Español + Inglés)*\n" .
            "Detecta el idioma del huésped automáticamente\n\n" .
            "👤 *Traspaso Humano Inteligente*\n" .
            "Si tú respondes, el bot se pausa — tú tomas el control\n\n" .
            "🔔 *Notificaciones Instantáneas*\n" .
            "Recibes un WhatsApp + email con cada reserva nueva\n\n" .
            "📧 *Email de Confirmación al Huésped*\n" .
            "Automático con logo de tu hotel\n\n" .
            "🆔 *Verificación de Identidad*\n" .
            "Pide foto de DNI/pasaporte antes de confirmar\n\n" .
            "📊 *Panel Web de Control*\n" .
            "Ve todas tus reservas, ingresos y estadísticas online\n" .
            "Consulta datos de tu negocio directamente por WhatsApp\n\n" .
            "💬 *Historial de Conversaciones*\n" .
            "Todas las charlas guardadas y accesibles\n\n" .
            "🚫 *Sin Comisiones por Reserva*\n" .
            "Tarifa fija mensual — no importa cuántas reservas hagas\n\n" .
            "⚡ *Configuración en 48 horas*\n" .
            "No necesitas nada técnico — nosotros lo hacemos todo\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
            "🎁 *7 días GRATIS* para probarlo sin compromiso\n\n" .
            "¿Listo para empezar? Escribe *empezar* 🚀"
        ];
    }

    private function handleBenefits(string $phone, string $msg): array
    {
        if (str_contains($msg, 'empezar') || str_contains($msg, 'activar') || str_contains($msg, 'start') || str_contains($msg, 'precio') || str_contains($msg, 'cuanto') || str_contains($msg, 'cuánto') || str_contains($msg, 'precio') || str_contains($msg, 'cost') || str_contains($msg, 'si') || str_contains($msg, 'sí') || str_contains($msg, 'yes')) {
            $this->updateSession($phone, ['state' => 'closing']);
            return $this->showPricing();
        }

        if (str_contains($msg, 'demo')) {
            $this->updateSession($phone, ['state' => 'demo']);
            return $this->showDemo();
        }

        // Default — show pricing
        $this->updateSession($phone, ['state' => 'closing']);
        return $this->showPricing();
    }

    private function showPricing(): array
    {
        return ['reply' =>
            "💳 *Planes Mia — WhatsApp AI para tu Negocio:*\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
            "1️⃣ *BÁSICO — S/399/mes*\n" .
            "• Reservas automáticas 24/7\n" .
            "• Notificaciones WhatsApp + email\n" .
            "• Traspaso humano\n" .
            "• Hasta 200 conversaciones/mes\n" .
            "• Ideal para hostales y hoteles pequeños\n\n" .
            "2️⃣ *PRO — S/699/mes*\n" .
            "• Todo lo del Básico +\n" .
            "• Panel web de control completo\n" .
            "• Consulta datos de tu negocio por WhatsApp\n" .
            "• Conversaciones ilimitadas\n" .
            "• Reportes mensuales automáticos\n" .
            "• Ideal para hoteles medianos y agencias\n\n" .
            "3️⃣ *ENTERPRISE — S/1,199/mes*\n" .
            "• Todo lo del Pro +\n" .
            "• Múltiples números WhatsApp\n" .
            "• Integración con tu sistema de gestión\n" .
            "• Soporte prioritario\n" .
            "• Personalización del asistente\n" .
            "• Ideal para cadenas y agencias grandes\n\n" .
            "━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
            "⚙️ Configuración única: *S/500*\n" .
            "🎁 *Primeros 7 días GRATIS*\n\n" .
            "¿Cuál plan te interesa? Escribe *1*, *2*, o *3*\n" .
            "O escribe *prueba* para activar los 7 días gratis del plan Básico"
        ];
    }

    private function handleClosing(string $phone, string $msg, array $session): array
    {
        $plan = 'basic';
        if (str_contains($msg, '2') || str_contains($msg, 'pro')) {
            $plan = 'pro';
        } elseif (str_contains($msg, '3') || str_contains($msg, 'enterprise') || str_contains($msg, 'empresa')) {
            $plan = 'enterprise';
        }

        $this->updateSession($phone, ['state' => 'collecting_name']);
        $this->pdo->prepare("UPDATE mia_sales_sessions SET business_type = COALESCE(business_type, 'hotel') WHERE phone = ?")
            ->execute([$phone]);

        $planNames = ['basic' => 'Básico', 'pro' => 'Pro', 'enterprise' => 'Enterprise'];
        return ['reply' =>
            "¡Excelente elección! 🎉 Plan *" . ($planNames[$plan] ?? 'Básico') . "*\n\n" .
            "Para activar tu prueba gratuita de 7 días, necesito algunos datos.\n\n" .
            "📝 *¿Cuál es el nombre de tu hotel o agencia?*"
        ];
    }

    private function handleCollectName(string $phone, string $message): array
    {
        $name = trim($message);
        if (strlen($name) < 2) {
            return ['reply' => "Necesito el nombre de tu negocio para continuar. ¿Cómo se llama? 🏨"];
        }

        $this->updateSession($phone, [
            'state'         => 'collecting_email',
            'business_name' => $name,
        ]);

        return ['reply' =>
            "Perfecto — *$name* ✅\n\n" .
            "📧 ¿Y tu email de contacto? (para enviarte los accesos)"
        ];
    }

    private function handleCollectEmail(string $phone, string $msg): array
    {
        if (!preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $msg, $m)) {
            return ['reply' => "Hmm, no detecté un email válido. ¿Puedes escribirlo de nuevo? 📧"];
        }

        $email = $m[0];
        $this->updateSession($phone, [
            'state' => 'captured',
            'email' => $email,
        ]);

        // Convert session to lead
        $session = $this->getSession($phone);
        $leadService = new LeadService();
        $s = SalesSession::fromRow($session);
        $leadService->createFromSession($s);

        return ['reply' =>
            "🎉 *¡Listo! Tu prueba gratuita de 7 días está activada!*\n\n" .
            "📋 *Resumen:*\n" .
            "• Negocio: *" . ($session['business_name'] ?? '-') . "*\n" .
            "• Email: *$email*\n" .
            "• WhatsApp: *$phone*\n\n" .
            "📞 Nuestro equipo te contactará en las próximas *24 horas* para:\n" .
            "1. Configurar tu número de WhatsApp\n" .
            "2. Personalizar tu asistente con tus habitaciones y precios\n" .
            "3. Dejarlo funcionando\n\n" .
            "¿Tienes alguna pregunta mientras tanto? Estoy aquí para ayudarte 😊\n\n" .
            "También puedes visitar: *mia.ainitravel.com* para más info"
        ];
    }

    private function handleCaptured(string $phone, string $msg): array
    {
        return ['reply' =>
            "¡Ya tenemos tus datos registrados! 🎉\n\n" .
            "Nuestro equipo se pondrá en contacto contigo pronto para la configuración.\n\n" .
            "Si tienes alguna pregunta adicional, escríbeme con toda confianza 😊\n\n" .
            "📞 O si prefieres hablar con una persona, escribe *humano* y te conecto con nuestro equipo."
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  INTRO (first message / cold open)
    // ════════════════════════════════════════════════════════════════════════

    private function introReply(): array
    {
        return ['reply' =>
            "¡Hola! 👋 Soy *Mia*, asistente virtual de *AiniDesk*\n\n" .
            "Ayudamos a hoteles y agencias de viajes a tomar *reservas automáticas por WhatsApp* — 24/7, sin perder un solo cliente. 🚀\n\n" .
            "🏨 Uno de nuestros hoteles aumentó sus reservas directas un *40%* en el primer mes.\n\n" .
            "¿Puedo hacerte 3 preguntas rápidas para ver si podemos ayudarte?\n" .
            "Solo toma 2 minutos 😊\n\n" .
            "Escribe *sí* para empezar"
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SESSION MANAGEMENT
    // ════════════════════════════════════════════════════════════════════════

    private function getOrCreateSession(string $phone): array
    {
        $session = $this->getSession($phone);
        if ($session) {
            return $session;
        }

        $this->pdo->prepare("INSERT INTO mia_sales_sessions (phone, state) VALUES (?, 'intro')")
            ->execute([$phone]);

        return $this->getSession($phone);
    }

    private function getSession(string $phone): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch() ?: null;
    }

    private function updateSession(string $phone, array $fields): void
    {
        $sets   = [];
        $values = [];
        foreach ($fields as $col => $val) {
            $sets[]   = "$col = ?";
            $values[] = $val;
        }
        $values[] = $phone;
        $sql = "UPDATE mia_sales_sessions SET " . implode(', ', $sets) . " WHERE phone = ?";
        $this->pdo->prepare($sql)->execute($values);
    }

    private function resetSession(string $phone): void
    {
        $this->pdo->prepare("DELETE FROM mia_sales_sessions WHERE phone = ?")->execute([$phone]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
