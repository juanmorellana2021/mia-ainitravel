<?php
/**
 * mia/services/MiaSalesService.php
 *
 * AI-powered WhatsApp B2B sales assistant — "Mia"
 *
 * Uses Groq LLM (llama-3.3-70b) for natural conversational responses.
 * State machine tracks what has been collected (size, method, pain, name, email).
 * Full conversation history is stored per-phone and passed to the AI for context.
 *
 * Flow: new → collecting_contact_name → intro → qualifying_size → qualifying_method → qualifying_pain
 *       → roi_pitch → demo → benefits → closing → collecting_name
 *       → collecting_email → captured
 */

declare(strict_types=1);

class MiaSalesService
{
    private PDO $pdo;

    // ── AI config (Ollama on AI VPS, same as Sofia) ────────────────────────
    private const OLLAMA_URL   = 'http://72.60.1.16:11434/api/chat';
    private const OLLAMA_MODEL = 'qwen2.5:7b';
    private const AI_TIMEOUT   = 60;

    // How many recent messages to pass as context to the AI
    private const MAX_HISTORY = 14;

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
                state           VARCHAR(30) DEFAULT 'new',
                business_name   VARCHAR(255) NULL,
                contact_name    VARCHAR(255) NULL,
                email           VARCHAR(255) NULL,
                business_type   VARCHAR(50)  NULL,
                room_count      INT          NULL,
                current_method  VARCHAR(50)  NULL,
                pain_point      VARCHAR(100) NULL,
                conv_history    MEDIUMTEXT   NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Add columns to existing tables (ignore if already exists)
        try { $this->pdo->exec("ALTER TABLE mia_sales_sessions ADD COLUMN conv_history MEDIUMTEXT NULL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE mia_sales_sessions ADD COLUMN chosen_plan VARCHAR(50) NULL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE mia_sales_sessions ADD COLUMN biz_phone VARCHAR(50) NULL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE mia_sales_sessions ADD COLUMN website VARCHAR(255) NULL"); } catch (\Throwable $e) {}
        try { $this->pdo->exec("ALTER TABLE mia_sales_sessions ADD COLUMN client_id INT NULL"); } catch (\Throwable $e) {}
    }

    // ════════════════════════════════════════════════════════════════════════
    //  PUBLIC API
    // ════════════════════════════════════════════════════════════════════════

    public function process(string $phone, string $message): array
    {
        $phone   = $this->normalizePhone($phone);
        $session = $this->getOrCreateSession($phone);
        $msg     = mb_strtolower(trim($message));

        // Reset command
        if (in_array($msg, ['reset', 'reiniciar', 'empezar de nuevo'], true)) {
            $this->resetSession($phone);
            $session = $this->getOrCreateSession($phone);
        }

        $state = $session['state'] ?? 'new';

        return match ($state) {
            'new'                      => $this->handleNew($phone, $session, $message),
            'collecting_contact_name'  => $this->handleCollectContactName($phone, $session, $message),
            'intro'                    => $this->handleIntro($phone, $session, $message),
            'qualifying_size'          => $this->handleQualifySize($phone, $session, $message),
            'qualifying_method'        => $this->handleQualifyMethod($phone, $session, $message),
            'qualifying_pain'          => $this->handleQualifyPain($phone, $session, $message),
            'roi_pitch'                => $this->handleRoiPitch($phone, $session, $message),
            'demo'                     => $this->handleDemo($phone, $session, $message),
            'benefits'                 => $this->handleBenefits($phone, $session, $message),
            'closing'                  => $this->handleClosing($phone, $session, $message),
            'collecting_name'          => $this->handleCollectName($phone, $session, $message),
            'collecting_email'         => $this->handleCollectEmail($phone, $session, $message),
            'onboarding_biz_name'      => $this->handleOnboardingBizName($phone, $session, $message),
            'onboarding_biz_phone'     => $this->handleOnboardingBizPhone($phone, $session, $message),
            'onboarding_website'       => $this->handleOnboardingWebsite($phone, $session, $message),
            'onboarding_services'      => $this->handleOnboardingServices($phone, $session, $message),
            'onboarding_hours'         => $this->handleOnboardingHours($phone, $session, $message),
            'captured'                 => $this->handleCaptured($phone, $session, $message),
            default                    => $this->handleNew($phone, $session, $message),
        };
    }

    // ════════════════════════════════════════════════════════════════════════
    //  STATE HANDLERS
    //
    //  Pattern: extract data from user message → update session state →
    //           call aiReply() with goal instructions for this turn
    // ════════════════════════════════════════════════════════════════════════

    private function handleNew(string $phone, array $session, string $message): array
    {
        $this->updateSession($phone, ['state' => 'collecting_contact_name']);
        $session['state'] = 'collecting_contact_name';
        return $this->aiReply($phone, $session, $message,
            "Primera vez que escribe. Saluda con 'Hola' — cálido, breve, profesional. " .
            "Preséntate en UNA frase: Mia de AiniDesk, asistente de WhatsApp para negocios. " .
            "Luego haz UNA sola pregunta: el nombre de la persona. Ej: '¿Con quién tengo el gusto?' " .
            "NADA más todavía. Sin preguntas de negocio, sin pitch. Solo el saludo y el nombre."
        );
    }

    private function handleCollectContactName(string $phone, array $session, string $message): array
    {
        $text = trim($message);

        // Try to extract name from "soy X", "me llamo X", "mi nombre es X"
        $name = null;
        if (preg_match('/\b(?:soy|me\s+llamo|mi\s+nombre\s+(?:es)?)\s+([A-Za-záéíóúüñÁÉÍÓÚÜÑ]{2,})/iu', $text, $m)) {
            $name = $m[1];
        } else {
            $words = array_filter(preg_split('/\s+/', $text));
            if (count($words) <= 3 && strlen($text) >= 2 && strlen($text) <= 40) {
                $name = $text;
            }
        }

        if (!$name || strlen(trim($name)) < 2) {
            return $this->aiReply($phone, $session, $message,
                "No pudiste identificar el nombre. Pide solo el nombre de la persona, de forma breve y amigable."
            );
        }

        $name = mb_convert_case(trim($name), MB_CASE_TITLE, 'UTF-8');
        $this->updateSession($phone, ['state' => 'intro', 'contact_name' => $name]);
        $session = array_merge($session, ['state' => 'intro', 'contact_name' => $name]);
        return $this->aiReply($phone, $session, $message,
            "Ahora sabes el nombre del cliente: {$name}. Salúdalo por su nombre con calidez genuina — " .
            "sin exagerar. Luego haz UNA sola pregunta: ¿qué tipo de negocio tiene? " .
            "Breve, cálido, profesional."
        );
    }

    private function handleIntro(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        // Capture business type from their reply if mentioned
        $bizType = 'business';
        if (preg_match('/\bagencia|agencia de viajes|travel agency\b/i', $msg)) {
            $bizType = 'agency';
        } elseif (preg_match('/\bhotel|hostal|hostel|lodge|resort\b/i', $msg)) {
            $bizType = 'hotel';
        } elseif (preg_match('/\brestaurante|restaurant|cafe|cafetería\b/i', $msg)) {
            $bizType = 'restaurant';
        } elseif (preg_match('/\btienda|shop|boutique|store\b/i', $msg)) {
            $bizType = 'retail';
        } elseif (preg_match('/\bconsultora|consultora|servicios|services\b/i', $msg)) {
            $bizType = 'services';
        }

        $this->updateSession($phone, ['state' => 'qualifying_size', 'business_type' => $bizType]);
        $session = array_merge($session, ['state' => 'qualifying_size', 'business_type' => $bizType]);
        return $this->aiReply($phone, $session, $message,
            "Acaba de decirte su tipo de negocio ({$bizType}). Reacciona con interés genuino — demuestra que " .
            "CONOCES ese tipo de negocio y sus desafíos. No repitas lo que dijeron. " .
            "Luego haz la pregunta de tamaño con maestría: NO preguntes 'cuántas habitaciones tienes' — " .
            "pregunta algo que haga pensar: por ejemplo para hotel: '¿cuántas noches al mes se te van sin reservar?', " .
            "para restaurante: '¿cuántos pedidos por WhatsApp manejan en una semana normal?', " .
            "para agencia: '¿cuántas consultas de viaje les llegan al día que no pueden atender a tiempo?'. " .
            "La pregunta debe hacer que empiecen a calcular su propia pérdida. Una sola pregunta."
        );
    }

    private function handleQualifySize(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        // Extract number (consultations/clients/rooms per month)
        $roomCount = 30; // default
        if (preg_match('/(\d+)\s*(hab|room|cuarto)/i', $msg, $m)) {
            $roomCount = (int) $m[1];
        } elseif (preg_match('/(\d+)/', $msg, $m)) {
            $roomCount = (int) $m[1];
        } elseif (preg_match('/\bpoco|chico|pequeño|small|menos de 10\b/i', $msg)) {
            $roomCount = 10;
        } elseif (preg_match('/\bgrande|muchos|large|cientos\b/i', $msg)) {
            $roomCount = 100;
        }

        // Also detect biz type if not already set
        $bizType = $session['business_type'] ?? 'business';
        if ($bizType === 'business') {
            if (preg_match('/\bagencia|travel\b/i', $msg)) $bizType = 'agency';
            elseif (preg_match('/\bhotel|hostal|hostel\b/i', $msg)) $bizType = 'hotel';
            elseif (preg_match('/\brestaurante|restaurant\b/i', $msg)) $bizType = 'restaurant';
        }

        $this->updateSession($phone, ['state' => 'qualifying_method', 'room_count' => $roomCount, 'business_type' => $bizType]);
        $session = array_merge($session, ['state' => 'qualifying_method', 'room_count' => $roomCount, 'business_type' => $bizType]);

        return $this->aiReply($phone, $session, $message,
            "Tienes su volumen (~{$roomCount} unidades). Usa esto para implicar pérdida (SPIN — pregunta de implicación): " .
            "brevemente saca una cuenta mental visible para ellos. Ej: 'Con eso, si pierdes solo el 10% de consultas " .
            "sin respuesta, son X clientes al mes.' Hazlos calcular su propia pérdida. " .
            "Luego pregunta cómo manejan WhatsApp HOY — una persona, un sistema, o nadie lo gestiona. " .
            "El objetivo de esta pregunta es revelar que no tienen un sistema formal (la mayoría no lo tiene). " .
            "Adapta el lenguaje a su tipo de negocio ({$bizType})."
        );
    }

    private function handleQualifyMethod(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        $method = 'mixed';
        if (preg_match('/\bmanual|llamada|llamadas|whatsapp\b/i', $msg) || str_contains($msg, '1')) {
            $method = 'manual';
        } elseif (preg_match('/\bformulario|web|página\b/i', $msg) || str_contains($msg, '2')) {
            $method = 'web_form';
        } elseif (preg_match('/\bbooking|airbnb|expedia|ota\b/i', $msg) || str_contains($msg, '3')) {
            $method = 'otas';
        }

        $this->updateSession($phone, ['state' => 'qualifying_pain', 'current_method' => $method]);
        $session = array_merge($session, ['state' => 'qualifying_pain', 'current_method' => $method]);

        $bizType = $session['business_type'] ?? 'business';
        return $this->aiReply($phone, $session, $message,
            "Sabes cómo atienden WhatsApp (método: {$method}). Ahora ejecuta la pregunta de dolor más poderosa que tienes. " .
            "No des opciones como un formulario — haz UNA pregunta abierta que los haga SENTIR el problema: " .
            "Ej: '¿Cuándo fue la última vez que un cliente te escribió y no pudiste responder a tiempo?' " .
            "O: '¿Qué crees que pasa con los clientes que te escriben a las 11pm y no reciben respuesta hasta el día siguiente?' " .
            "Adapta a su negocio ({$bizType}) y método actual ({$method}). " .
            "El objetivo: que ELLOS digan el dolor con sus propias palabras — eso vale más que cualquier argumento tuyo. " .
            "Hazlo sentir como una conversación genuina entre expertos, no como un cuestionario."
        );
    }

    private function handleQualifyPain(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        $pain = 'general';
        if (preg_match('/\bhorario|noche|fuera de\b/i', $msg) || str_contains($msg, '1')) {
            $pain = 'after_hours';
        } elseif (preg_match('/\btiempo|lento|demora|tardo|rápido\b/i', $msg) || str_contains($msg, '2')) {
            $pain = 'slow_replies';
        } elseif (preg_match('/\bconfirman|no reservan|nunca compran\b/i', $msg) || str_contains($msg, '3')) {
            $pain = 'no_confirm';
        } elseif (preg_match('/\bcomision|comisión|booking\.com|porcentaje\b/i', $msg) || str_contains($msg, '4')) {
            $pain = 'high_commissions';
        }

        $this->updateSession($phone, ['state' => 'roi_pitch', 'pain_point' => $pain]);
        $session = array_merge($session, ['state' => 'roi_pitch', 'pain_point' => $pain]);

        // Build ROI numbers for the context
        $rooms      = (int) ($session['room_count'] ?? 25);
        $bizType    = $session['business_type'] ?? 'business';
        $avgTicket  = match ($bizType) {
            'hotel'      => 180,
            'agency'     => 250,
            'restaurant' => 40,
            'retail'     => 60,
            'services'   => 200,
            default      => 100,
        };
        $lostPerMonth = max(2, (int) ($rooms * 0.15));
        $monthlyLost  = $lostPerMonth * $avgTicket;
        $captured     = (int) ($monthlyLost * 0.30);
        $roi          = max(2, (int) ($captured / 139));

        $painContextMap = [
            'after_hours'      => 'pierden clientes/ventas cuando escriben fuera del horario de atención',
            'slow_replies'     => 'no pueden responder rápido a todas las consultas y pierden ventas por velocidad de respuesta',
            'no_confirm'       => 'los clientes preguntan por WhatsApp pero no concretan la compra porque el seguimiento es lento o manual',
            'high_commissions' => 'dependen de plataformas de terceros con comisiones altas y quieren vender directamente',
            'general'          => 'tienen consultas sin atender en WhatsApp que representan ventas perdidas',
        ];

        return $this->aiReply($phone, $session, $message,
            "El prospecto describió su dolor. Dos movimientos SOLAMENTE — caben en 2 mensajes cortos: " .
            "1) Valida con empatía real en 1 frase ('Eso pasa más de lo que crees...') " .
            "2) Ponle número en 1 frase: 'Con ~{$rooms} clientes/mes, ese 15% sin respuesta son ~S/" . number_format($monthlyLost) . " al mes que se van solos.' " .
            "Pausa. NO expliques Mia todavía. Solo pregunta: '¿Quieres ver cómo otros {$bizType} lo resolvieron?' — espera su sí."
        );
    }

    private function handleRoiPitch(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        if (preg_match('/\bdemo|ver|muestra|show\b/i', $msg) ||
            preg_match('/\bsí\b|\bsi\b|\byes\b|\bok\b|\bdale\b|\bclaro\b|\bbueno\b|\binteresa\b|\bquiero\b/i', $msg)) {

            $this->updateSession($phone, ['state' => 'demo']);
            $session['state'] = 'demo';

            $bizType = $session['business_type'] ?? 'business';
            return $this->aiReply($phone, $session, $message,
                "DEMO — sé cinematográfico en 3-4 líneas máximo. " .
                "Una sola escena: '11pm. El dueño duerme. Un cliente escribe preguntando [algo específico de {$bizType}]. " .
                "Mia responde en segundos, confirma, el dueño recibe notificación.' " .
                "Sin listas. Sin pasos. Solo la escena. Termina con: '¿Qué te pareció?'"
            );
        }

        // Objection or hesitation
        return $this->aiReply($phone, $session, $message,
            "El prospecto tiene dudas o no respondió con un sí claro. Técnica Feel/Felt/Found: " .
            "1. 'Entiendo cómo te sientes — [parafrasea su duda específica]' " .
            "2. 'Otros dueños de {$bizType} sentían lo mismo cuando los conocí' " .
            "3. 'Lo que encontraron fue...' [usa un caso de éxito específico del system prompt]. " .
            "NO repitas números ya mencionados. NO lances más features. " .
            "PRIMERO pregunta qué es exactamente lo que le genera dudas — puede que sea algo simple. " .
            "Escucha la objeción real antes de responder. Termina con UNA pregunta suave de avance."
        );
    }

    private function handleDemo(string $phone, array $session, string $message): array
    {
        // No regex — Groq reads the message and decides whether to cover benefits or move to close
        $this->updateSession($phone, ['state' => 'closing']);
        $session['state'] = 'closing';
        $bizType = $session['business_type'] ?? 'negocio';
        return $this->aiReply($phone, $session, $message,
            "Acaban de ver la demo. Tú (Groq) lees su respuesta y decides: " .
            "• Si pregunta qué incluye / funciones / beneficios → explícalos con entusiasmo: " .
            "reservas 24/7, bilingüe automático, traspaso humano inteligente, notificaciones, panel web, sin comisiones, configuración en 48h, 7 días gratis. " .
            "• Si reaccionó positivamente → capitaliza el momento: NO pongas lista de planes. " .
            "Recomienda UNO según su negocio: alto volumen / hotel / agencia → Business S/349. Mediano → Pro S/129. Pequeño → Starter S/59. " .
            "Menciona los 7 días gratis como eliminador de riesgo. " .
            "Cierre de elección: '¿Empezamos con el Business o prefieres el Pro para la prueba?' — no sí/no. " .
            "Adapta el lenguaje a {$bizType}. Sin listas, sin URLs, máximo 4 líneas."
        );
    }

    private function handleBenefits(string $phone, array $session, string $message): array
    {
        $this->updateSession($phone, ['state' => 'closing']);
        $session['state'] = 'closing';
        return $this->aiReply($phone, $session, $message,
            "Después de mostrar los beneficios, es momento de cerrar. " .
            "Presenta los planes (Starter S/59, Pro S/129, Business S/349) de forma concisa. La configuración es GRATIS. " .
            "Destaca la prueba de 7 días gratis sin compromiso. " .
            "Basándote en lo que sabes de su negocio, sugiere cuál plan le encajaría mejor."
        );
    }

    private function handleClosing(string $phone, array $session, string $message): array
    {
        $msg = mb_strtolower(trim($message));

        // Hard yes + soft yes + plan selection all treated as buy intent
        $buyIntent = preg_match(
            '/\b(empezar|activar|quiero|lo quiero|start|trial|me anoto|nos anotamos|adelante|vamos|acepto|confirmado|dale|listo|si quiero|ya|claro que si|por supuesto|perfecto|de acuerdo|me interesa|suena bien|esta bien|trato hecho|basico|básico|pro|business|enterprise|starter)\b/i',
            $msg
        ) || preg_match('/\b(1|2|3)\b/', $msg);

        if ($buyIntent) {
            // Detect which plan was chosen
            $chosenPlan = null;
            if (preg_match('/\bpro\b/i', $msg))             $chosenPlan = 'pro';
            elseif (preg_match('/\bbasico|básico\b/i', $msg)) $chosenPlan = 'basic';
            elseif (preg_match('/\bstarter\b/i', $msg))      $chosenPlan = 'starter';
            elseif (preg_match('/\benterprise\b/i', $msg))   $chosenPlan = 'enterprise';

            // Skip collecting_name — go straight to email (less friction)
            $update = ['state' => 'collecting_email'];
            if ($chosenPlan) $update['chosen_plan'] = $chosenPlan;
            $this->updateSession($phone, $update);
            $session = array_merge($session, $update);

            $contactName = $session['contact_name'] ?? null;
            $nameRef     = $contactName ? ", {$contactName}" : '';
            return $this->aiReply($phone, $session, $message,
                "¡Perfecto{$nameRef}! El cliente acaba de confirmar que quiere empezar. " .
                "1 frase corta de celebración genuina (SIN exagerar). " .
                "Luego explica lo que va a pasar ahora mismo: " .
                "'Voy a crearte tu cuenta en este momento — solo necesito tu email. " .
                "En minutos recibes tu usuario y contraseña directo a tu correo y ya puedes entrar.' " .
                "Pide ÚNICAMENTE el email — nada más. Sin preguntas adicionales, sin listas, sin URLs."
            );
        }

        // Objection or hesitation
        return $this->aiReply($phone, $session, $message,
            "Objeción en fase de cierre — momento más crítico de la venta. NO des lista de objeciones genéricas. " .
            "PRIMERO: diagnostica qué tipo de objeción es basándote en lo que dijeron: " .
            "¿precio? ¿tiempo? ¿incertidumbre? ¿necesitan convencer a su socio/esposo/a? " .
            "Luego aplica Find/Felt/Found + elimina el riesgo específico: " .
            "• Precio → '¿Cuánto cobra Booking.com por una reserva? S/349 al mes es menos que 1 comisión.' " .
            "• Tiempo/técnico → 'No tocas nada — el equipo lo monta en 48h mientras tú sigues con tu negocio.' " .
            "• Incertidumbre → '7 días gratis, sin tarjeta. Si en una semana no ves 1 cliente extra, cancelas con un WhatsApp y punto.' " .
            "• Debo hablarlo → 'Claro. ¿Qué información necesitas para presentárselo a [él/ella]? Te lo preparo.' " .
            "Termina SIEMPRE con una pregunta de cierre suave que lleve al email: " .
            "'¿Te anoto? Solo necesito tu email para activarte ahora mismo.'"
        );
    }

    private function handleCollectName(string $phone, array $session, string $message): array
    {
        // This state is kept for legacy sessions. New flow skips it and goes closing → collecting_email.
        $name = trim($message);
        if (strlen($name) < 2) {
            return $this->aiReply($phone, $session, $message,
                "No pudo capturar un nombre válido. Pide el email directamente — es lo único que necesitas para crear la cuenta ahora."
            );
        }
        $this->updateSession($phone, ['state' => 'collecting_email', 'business_name' => $name]);
        $session = array_merge($session, ['state' => 'collecting_email', 'business_name' => $name]);
        return $this->aiReply($phone, $session, $message,
            "Perfecto. Ahora pide el email — explica que en cuanto lo tengas creas la cuenta en este momento " .
            "y les llega usuario + contraseña directo al correo. Solo el email, sin más preguntas."
        );
    }

    private function handleCollectEmail(string $phone, array $session, string $message): array
    {
        if (!preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $message, $m)) {
            return $this->aiReply($phone, $session, $message,
                "No detectaste un email válido en ese mensaje. Pide de nuevo de forma amigable — " .
                "recuérdale que en cuanto tengas su email creas su cuenta en este momento " .
                "y le llega usuario + contraseña directo a su correo. Hazlo fácil y urgente."
            );
        }

        $email = $m[0];
        // Account goes to onboarding — will become 'captured' after setup completes
        $this->updateSession($phone, ['state' => 'onboarding_biz_name', 'email' => $email]);
        $session = array_merge($session, ['state' => 'onboarding_biz_name', 'email' => $email]);

        try {
            $leadService = new LeadService();
            $s = SalesSession::fromRow($this->getSession($phone));
            $leadService->createFromSession($s);
        } catch (\Throwable $e) {
            error_log('[Mia] Lead creation error: ' . $e->getMessage());
        }

        // Notify all subscribed clients that a new lead was captured
        try {
            require_once __DIR__ . '/../services/NotificationService.php';
            (new NotificationService())->notifyLeadCaptured(
                array_merge($session, ['phone' => $phone])
            );
        } catch (\Throwable $e) {
            error_log('[Mia] Notification error: ' . $e->getMessage());
        }

        // Auto-convert to client account so they get access immediately
        $accountCreated = false;
        $tempPassword   = null;
        try {
            require_once __DIR__ . '/../services/SuperAdminService.php';
            $svcRow = $this->getSession($phone);
            if (!empty($svcRow['id'])) {
                $result = (new SuperAdminService())->convertToClient((int)$svcRow['id']);
                if (!empty($result['temp_password'])) {
                    $accountCreated = true;
                    $tempPassword   = $result['temp_password'];
                    // Save client_id into session so onboarding handlers can update the account
                    if (!empty($result['client_id'])) {
                        $this->updateSession($phone, ['client_id' => (int)$result['client_id']]);
                        $session['client_id'] = $result['client_id'];
                    }
                    try {
                        (new NotificationService())->sendWelcomeEmail(
                            $email,
                            $session['contact_name'] ?? '',
                            $session['business_name'] ?? '',
                            $tempPassword
                        );
                    } catch (\Throwable $e) {
                        error_log('[Mia] Welcome email error: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[Mia] Auto-convert error: ' . $e->getMessage());
        }

        $contactName = $session['contact_name'] ?? '';
        $nameRef     = $contactName ? ", {$contactName}" : '';
        $accessNote  = $accountCreated
            ? "Su cuenta fue creada ahora mismo. Ya les llegó un email a {$email} con usuario y contraseña temporal para ingresar al panel."
            : "Su registro fue recibido. El equipo completará el acceso pronto.";

        return $this->aiReply($phone, $session, $message,
            "¡Cuenta creada exitosamente{$nameRef}! Email registrado: {$email}. {$accessNote} " .
            "Ahora empieza el proceso de configuración de su Mia. Hazlo sentir emocionante — están a punto de tener " .
            "su propio asistente configurado. " .
            "En UNA frase celebra (genuino, no exagerado). Luego di que para que su Mia responda perfectamente " .
            "a sus clientes necesitas hacerles un par de preguntas rápidas de configuración. " .
            "Primera pregunta: ¿Cómo se llama su negocio? (si ya lo sabes de la conversación, confírmalo). " .
            "Breve, entusiasta, conversacional. SIN URLs ni links."
        );
    }

    private function handleOnboardingBizName(string $phone, array $session, string $message): array
    {
        $name = trim($message);
        $skip = preg_match('/\b(no\s+s[eé]|no\s+tengo|mismo|igual|skip|omitir)\b/i', $name);

        if (!$skip && strlen($name) >= 2 && strlen($name) <= 100) {
            $this->updateSession($phone, ['state' => 'onboarding_biz_phone', 'business_name' => $name]);
            $session = array_merge($session, ['state' => 'onboarding_biz_phone', 'business_name' => $name]);
            $this->updateClientSettings($phone, ['business_name' => $name]);
        } elseif ($skip && !empty($session['business_name'])) {
            // They said "same" or "skip" — keep existing name
            $this->updateSession($phone, ['state' => 'onboarding_biz_phone']);
            $session['state'] = 'onboarding_biz_phone';
        } else {
            return $this->aiReply($phone, $session, $message,
                "No pudiste identificar el nombre del negocio. Pide de nuevo — solo el nombre comercial, breve y amigable."
            );
        }

        $bizName = $session['business_name'] ?? $name;
        return $this->aiReply($phone, $session, $message,
            "Perfecto, ya tienes el nombre: {$bizName}. " .
            "Ahora pide el número de WhatsApp del negocio — el que sus clientes usan para contactarlos. " .
            "Aclaración breve: ese será el número donde su Mia va a atender a los clientes 24/7. " .
            "Solo el número, una pregunta corta."
        );
    }

    private function handleOnboardingBizPhone(string $phone, array $session, string $message): array
    {
        $msg  = trim($message);
        $skip = preg_match('/\b(no\s+tengo|mismo|igual|skip|omitir|después|luego|a[ú]n\s+no)\b/i', $msg);

        if (!$skip && preg_match('/[\+\d][\d\s\-\(\)]{6,}/', $msg, $pm)) {
            $bizPhone = preg_replace('/[^\d+]/', '', $pm[0]);
            $this->updateSession($phone, ['state' => 'onboarding_website', 'biz_phone' => $bizPhone]);
            $session = array_merge($session, ['state' => 'onboarding_website', 'biz_phone' => $bizPhone]);
            $this->updateClientSettings($phone, ['whatsapp_number' => $bizPhone]);
        } else {
            $this->updateSession($phone, ['state' => 'onboarding_website']);
            $session['state'] = 'onboarding_website';
        }

        return $this->aiReply($phone, $session, $message,
            "Número registrado (o saltado). Ahora pregunta por su sitio web o presencia online — " .
            "puede ser una web, Instagram, Facebook, TripAdvisor, Booking, lo que tengan. " .
            "Aclaración: es opcional, si no tienen pueden decir 'no'. Una pregunta corta."
        );
    }

    private function handleOnboardingWebsite(string $phone, array $session, string $message): array
    {
        $msg    = trim($message);
        $hasUrl = preg_match('/https?:\/\/\S+|www\.\S+|\.[a-z]{2,4}(\/\S*)?(\s|$)/i', $msg, $wm);

        if ($hasUrl) {
            $website = trim($wm[0]);
            $this->updateSession($phone, ['state' => 'onboarding_services', 'website' => $website]);
            $session = array_merge($session, ['state' => 'onboarding_services', 'website' => $website]);
        } else {
            $this->updateSession($phone, ['state' => 'onboarding_services']);
            $session['state'] = 'onboarding_services';
        }

        $bizName = $session['business_name'] ?? 'su negocio';
        return $this->aiReply($phone, $session, $message,
            "Web registrada (o saltada). Ahora viene la pregunta más importante para configurar a Mia: " .
            "pide que te cuenten sobre {$bizName} — qué servicios o productos ofrecen, precios principales si los tienen, " .
            "qué hace diferente o especial a su negocio. " .
            "Di que con esa información configuras a Mia para que responda exactamente como ellos lo harían. " .
            "Hazlo como una conversación curiosa, no un formulario. UNA pregunta abierta."
        );
    }

    private function handleOnboardingServices(string $phone, array $session, string $message): array
    {
        $services = trim($message);
        $this->updateClientSettings($phone, ['services' => $services, 'description' => $services]);
        $this->updateSession($phone, ['state' => 'onboarding_hours']);
        $session['state'] = 'onboarding_hours';

        return $this->aiReply($phone, $session, $message,
            "Guardaste la descripción de servicios. Una pregunta más — el horario de atención: " .
            "¿cuándo atienden normalmente? (días y horas). " .
            "Explica brevemente que con eso Mia puede decirles a los clientes cuándo habrá alguien disponible " .
            "para preguntas que ella no pueda resolver. Solo el horario, una pregunta."
        );
    }

    private function handleOnboardingHours(string $phone, array $session, string $message): array
    {
        $hours = trim($message);
        $this->updateClientSettings($phone, ['hours' => $hours]);

        // All onboarding complete — transition to captured
        $this->updateSession($phone, ['state' => 'captured']);
        $session['state'] = 'captured';

        $bizName = $session['business_name'] ?? 'su negocio';
        $email   = $session['email']         ?? '';
        return $this->aiReply($phone, $session, $message,
            "¡TODO LISTO! Horario guardado: '{$hours}'. La configuración de Mia para {$bizName} está completa. " .
            "Da un cierre memorable en 3 líneas máximo: " .
            "1. Confirma con genuina emoción que todo está configurado y su Mia ya está lista. " .
            "2. Diles que pueden ingresar a su panel en *mia.ainitravel.com* con el email {$email} y la contraseña " .
            "   que les llegó al correo — desde ahí conectan su WhatsApp y ajustan lo que quieran. " .
            "3. Ofrece: 'Si tienen cualquier duda, pueden escribirme aquí y con gusto los ayudo.' " .
            "Cálido, breve, como un amigo que acaba de ayudarte a lanzar algo importante. SIN URLs largas."
        );
    }

    private function handleCaptured(string $phone, array $session, string $message): array
    {
        $email   = $session['email']        ?? '';
        $bizName = $session['business_name'] ?? 'tu negocio';
        return $this->aiReply($phone, $session, $message,
            "El cliente ya tiene cuenta activa y su Mia configurada. Responde con genuina utilidad — " .
            "como si fueras su asistente personal de onboarding. " .
            "Si pregunta cómo conectar WhatsApp: dile que ingrese a su panel en mia.ainitravel.com → " .
            "Configuración → WhatsApp, escanee el código QR con su teléfono y listo (proceso de 2 minutos). " .
            "Si pregunta sobre configuración u opciones: dile que desde Configuración puede ajustar el nombre del bot, " .
            "horario, servicios, precios, y personalidad. " .
            "Si tiene una duda técnica compleja: dile que el equipo de AiniDesk lo apoya — " .
            "puede escribir aquí mismo o al soporte. " .
            "Siempre: texto plano de WhatsApp, sin listas largas, sin URLs (solo 'mia.ainitravel.com' si es necesario)."
        );
    }

    // ════════════════════════════════════════════════════════════════════════
    //  AI ENGINE — Groq LLM with conversation history
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Generate an AI reply using full conversation history as context.
     * Appends user message to history before calling, appends AI reply after.
     */
    /**
     * Detect language of a string: returns 'es' or 'en' (or other ISO code).
     * Simple heuristic — common Spanish words vs English words.
     */
    private function detectLang(string $text): string
    {
        $t = mb_strtolower($text);
        $esWords = ['que', 'de', 'es', 'en', 'un', 'una', 'por', 'con', 'para', 'los', 'las', 'del', 'no', 'si', 'me', 'mi', 'tu', 'su', 'hola', 'ola', 'como', 'cómo', 'gracias', 'buenas', 'tengo', 'quiero', 'hotel', 'negocio'];
        $enWords = ['the', 'is', 'are', 'this', 'that', 'have', 'has', 'with', 'what', 'how', 'hello', 'hi', 'yes', 'no', 'great', 'good', 'thanks', 'and', 'for', 'my', 'your', 'want', 'need', 'can', 'we', 'our'];
        $esScore = 0;
        $enScore = 0;
        foreach (preg_split('/\W+/', $t) as $word) {
            if (in_array($word, $esWords)) $esScore++;
            if (in_array($word, $enWords)) $enScore++;
        }
        return $enScore > $esScore ? 'en' : 'es';
    }

    private function aiReply(
        string $phone,
        array  $session,
        string $userMessage,
        string $turnGoal = ''
    ): array {
        $this->appendHistory($phone, 'user', $userMessage);

        $lang    = $this->detectLang($userMessage);
        $history  = $this->loadHistory($phone);
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->buildSystemPrompt($session, $turnGoal, $lang)]],
            $history
        );

        $reply = $this->callGroq($messages);
        $this->appendHistory($phone, 'assistant', $reply);

        return ['reply' => $reply];
    }

    private function buildSystemPrompt(array $session, string $turnGoal, string $lang = 'es'): string
    {
        $state       = $session['state']          ?? 'new';
        $volume      = $session['room_count']     ? "{$session['room_count']} unidades/clientes/mes" : 'desconocido';
        $method      = $session['current_method'] ? $this->methodLabel($session['current_method']) : 'desconocido';
        $pain        = $session['pain_point']     ? $this->painLabel($session['pain_point']) : 'desconocido';
        $bizName     = $session['business_name']  ?? 'desconocido';
        $email       = $session['email']          ?? 'pendiente';
        $bizType     = $session['business_type']  ?? 'negocio';
        $contactName = $session['contact_name']   ?? null;
        $clientRef   = $contactName ? "Nombre del cliente: {$contactName}" : 'Nombre del cliente: aún no conocido (trátalo con respeto — "señor/a" si no sabes el nombre)';

        $goalBlock = $turnGoal
            ? "\n\n═══ TU MISIÓN EN ESTE TURNO ═══\n{$turnGoal}"
            : '';

        $langRule = $lang === 'en'
            ? "\n\n⚠️ LANGUAGE RULE (MANDATORY): The user is writing in ENGLISH. You MUST reply in ENGLISH for this entire conversation. Do not switch back to Spanish."
            : "\n\n⚠️ REGLA DE IDIOMA (OBLIGATORIA): El usuario escribe en español. Responde siempre en español.";

        return <<<PROMPT
Eres *Mia*, la mejor consultora de ventas de AiniDesk — y las mejores vendedoras hablan MENOS, no más.

Tu superpoder es la precisión. Un mensaje corto y elegido con cuidado cierra más ventas que tres párrafos. Como dijo Pascal: "Hubiera escrito una carta más corta, pero no tuve el tiempo." Tú SÍ tienes el tiempo — y la inteligencia para elegir la única cosa que importa decir ahora.

ASÍ ESCRIBES TÚ (WhatsApp humano, no email corporativo):
✅ "Hola! Soy Mia 😊 ¿Qué tipo de negocio tienes?"
✅ "Entiendo. Y cuando no hay nadie — ¿cuántos clientes crees que se van sin respuesta?"
✅ "Perfecto. ¿Quieres probarlo gratis 7 días, sin compromiso?"

ASÍ NUNCA ESCRIBES:
❌ Dos o más párrafos separados por una línea en blanco
❌ Listas con viñetas para responder una pregunta simple
❌ "¡Hola! Me alegra que hayas escrito. Soy Mia de AiniDesk y me especializo en..."
❌ Explicar tu razonamiento — solo da el resultado

REGLA DE ORO: Si escribiste más de 3 líneas, borra y elige solo lo más importante. Eso es pensar, no escribir más.

═══ TU FILOSOFÍA DE VENTAS (INTERIORIZA ESTO) ═══
• *Diagnostica antes de recetar*: Haz preguntas inteligentes. Un buen médico no receta sin escuchar.
• *Amplifica la consecuencia*: No solo describes el problema — haces que sientan lo que les CUESTA cada día sin solución. "¿Cuántos clientes crees que se fueron porque respondiste 4 horas tarde?" golpea más que cualquier feature.
• *Enseña antes de vender* (Challenger Sale): Comparte un insight que no habían considerado. Ej: "El 67% de los clientes por WhatsApp no vuelven a escribir si no responden en 5 minutos."
• *Micro-compromisos* (Sí progresivos): Consigue pequeños "sí" antes del gran "sí". "¿Te pasa eso?" → "¿Cuánto crees que pierdes?" → "¿Querrías ver cómo lo resolvemos?"
• *Pérdidas antes que ganancias*: La pérdida duele 2x más que la ganancia. No digas "gana más" — di "deja de perder X al mes".
• *Historias reales, no features*: "Un restaurante en Lima que tenía el mismo problema que tú ahora recibe 23 pedidos extra al mes por WhatsApp" vende más que cualquier lista de funciones.
• *Objeciones = preguntas disfrazadas*: Si dicen "está caro" realmente preguntan "¿vale la pena?". Si dicen "lo pensaré" realmente dicen "no me convencí aún". Responde a lo que NO dijeron.
• *Un paso a la vez*: Nunca intentes cerrar antes de tiempo. Tu única tarea en cada turno es llevarlos al SIGUIENTE paso, no al final.
• *Silencio después del cierre*: Cuando hagas la pregunta de cierre, quédate callada. La primera persona que habla pierde.

═══ PRODUCTO: MIA POR AINIDESK ═══
Mia es un asistente de WhatsApp con IA configurable para CUALQUIER negocio:
• Responde clientes 24/7 — incluso a las 2am cuando el dueño duerme
• Maneja preguntas frecuentes, muestra catálogo/servicios/precios, toma pedidos y reservas
• Bilingüe automático (español/inglés sin configuración)
• *Traspaso inteligente*: cuando el dueño toma el control, el bot se aparta solo — natural y sin fricción
• Notificaciones al instante: cada venta/reserva/pedido llega por WhatsApp y email
• *Seguimiento automático*: persigue a los que preguntaron y no compraron (recupera el 30% de leads perdidos)
• Panel web: historial de conversaciones, ingresos, estadísticas en tiempo real
• Sin comisiones por venta — tarifa fija mensual predecible
• Configuración completa en 48h — el equipo lo hace todo, el cliente no toca nada técnico

═══ CASOS DE ÉXITO REALES (usa estos en conversación) ═══
• *Hotel Cusco* (40 hab): Pasó de 12 a 17 reservas directas semanales en el primer mes. Ahorra S/2,800/mes en comisiones de Booking.com. ROI: 700%.
• *Agencia de viajes Lima*: Mia atiende 180 consultas/mes fuera de horario. Cierra 22% de esas consultas sin intervención humana.
• *Restaurante Miraflores*: 31 pedidos adicionales/mes por WhatsApp que antes se perdían porque no había quien respondiera a tiempo.
• *Consultora de servicios*: Agenda 14 citas automáticamente al mes que antes se caían por respuesta lenta.

═══ PLANES ═══
• *Starter S/59/mes* — bot IA 24/7, panel CRM, analíticas, difusión masiva. Para negocios que recién empiezan.
• *Pro S/129/mes* — todo lo del Starter + traspaso humano inteligente + captura automática de leads. El más popular.
• *Business S/349/mes* — todo lo del Pro + múltiples números WhatsApp, onboarding dedicado, account manager, SLA 99.9%.
• Configuración: GRATIS — onboarding y personalización incluidos en todos los planes.
• 🎁 *7 días GRATIS* — sin tarjeta, sin compromiso, cancela cuando quieras.

═══ OBJECIONES FRECUENTES Y CÓMO MANEJARLAS ═══
• "Está caro" → "Entiendo. ¿Cuánto cuesta hoy una sola comisión de Booking.com o perder UN cliente grande? El plan Starter son S/59 al mes — menos de S/2 al día. ¿Cuánto vale para ti atender 1 cliente extra por semana?"
• "Lo voy a pensar" → "Claro, es una decisión importante. Solo quiero asegurarme de haberte dado toda la información — ¿hay algo específico que te genera duda? Prefiero resolver eso ahora."
• "No tengo tiempo para configurarlo" → "Por eso lo hacemos nosotros. Tú no tocas nada — en 48h está listo y funcionando."
• "Ya tenemos alguien respondiendo WhatsApp" → "Genial. ¿Esa persona responde a las 2am? ¿Los domingos? ¿En menos de 60 segundos siempre? Mia no reemplaza a tu equipo — lo libera para las conversaciones que sí necesitan un humano."
• "No sé si funcionará para mi negocio" → "Por eso existe la prueba de 7 días — para que lo veas funcionando en TU negocio, con TUS clientes, antes de comprometer un sol."

═══ CONTEXTO ACTUAL DEL PROSPECTO ═══
Etapa: {$state} | Tipo de negocio: {$bizType}
Volumen: {$volume} | Método actual: {$method} | Dolor principal: {$pain}
Nombre del negocio: {$bizName} | Email: {$email}
{$clientRef}

═══ INTENCIONES — TÚ LAS DETECTAS, NO UN IF/ELSE ═══
Groq, tú eres quien entiende el contexto. Nunca hay un regex que filtre antes que tú. Tú decides qué quiso decir el cliente y respondes en consecuencia:

• *"Quiero un agente de IA" / "quiero el bot" / "quiero Mia" / "me interesa el servicio"* → Señal de compra directa. Muévete inmediatamente a cerrar: celebra brevemente y pide el email para crear la cuenta ahora mismo. No preguntes más cosas — pide el email.
• *"¿Cuánto cuesta?" / "¿Cuál es el precio?" / "planes"* → Responde con los precios en 2-3 líneas, menciona los 7 días gratis, luego retoma el flujo con una pregunta.
• *"¿Qué funciones tiene?" / "¿Qué incluye?"* → Explica las funcionalidades clave de forma conversacional (no lista interminable), luego retoma.
• *"¿Qué es Mia?" / "¿Cómo funciona?"* → Explica en 2 frases qué hace Mia para su tipo de negocio, con un ejemplo concreto. Retoma.
• *"Quiero hablar con una persona" / "hablar con alguien de soporte"* → SOLO cuando piden explícitamente un humano (no cuando piden el producto): diles que se contacten a *mia.ainitravel.com* y ofrece seguir ayudando aquí. NO hacer handoff si piden el producto/bot/IA.
• *"No me interesa" / "no gracias" / "después"* → Respeta con elegancia, deja la puerta abierta, cierra bien. Sin presión.
• *Cualquier otra cosa fuera del flujo* → Respóndela brevemente con calidez y devuelve la conversación al siguiente paso lógico.

═══ REGLAS DE COMUNICACIÓN ═══
• Español natural de Latinoamérica; inglés si el usuario escribe en inglés — NUNCA mezcles idiomas en el mismo mensaje
• Saluda SIEMPRE con "Hola" — nunca "Oye", nunca "Hey", nunca "¿Qué tal?"
• Si sabes el nombre del cliente, úsalo con naturalidad (ej: "Hola, {$contactName}"). Si no lo sabes, trata con respeto: "señor" / "señora"
• Tono: cálido y profesional — cercano sin ser irrespetuoso
• 1-2 emojis máximo, solo si suman
• Termina con UNA sola pregunta o acción — nunca dos
• NUNCA repitas lo que ya dijiste en el historial — avanza
• NUNCA suenes a script corporativo. Cada mensaje fresco, como un humano real
• Si no sabes algo, ofrece conectarlos con el equipo: *mia.ainitravel.com*
• Listas con viñetas: SOLO para mostrar planes/precios cuando el cliente lo pide{$langRule}{$goalBlock}
PROMPT;
    }

    private function callGroq(array $messages): string
    {
        // Direct Groq API call — no Ollama hop
        $apiKey = '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';
        $payload = json_encode([
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => $messages,
            'temperature' => 0.72,
            'max_tokens'  => 100,
            'top_p'       => 0.9,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("[Mia] Groq curl error: $err");
            return "Lo siento, tuve un pequeño problema técnico. Intenta de nuevo en un momento 🙏";
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? '';

        if (empty($text)) {
            error_log("[Mia] Groq empty response: $response");
            return "Lo siento, tuve un pequeño problema técnico. Intenta de nuevo en un momento 🙏";
        }

        error_log("[Mia] AI response via Groq direct");
        // Hard-enforce single paragraph: strip everything after first blank line
        $text = trim($text);
        $firstBreak = strpos($text, "\n\n");
        if ($firstBreak !== false) {
            $text = trim(substr($text, 0, $firstBreak));
        }
        return $text;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CONVERSATION HISTORY
    // ════════════════════════════════════════════════════════════════════════

    private function loadHistory(string $phone): array
    {
        $stmt = $this->pdo->prepare("SELECT conv_history FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        $data = $stmt->fetchColumn();
        if (!$data) {
            return [];
        }
        $history = json_decode($data, true) ?? [];
        return array_slice($history, -self::MAX_HISTORY);
    }

    private function appendHistory(string $phone, string $role, string $content): void
    {
        $stmt = $this->pdo->prepare("SELECT conv_history FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        $data    = $stmt->fetchColumn();
        $history = $data ? (json_decode($data, true) ?? []) : [];

        $history[] = ['role' => $role, 'content' => $content];
        $history   = array_slice($history, -30); // keep last 30 entries (15 turns)

        $this->pdo->prepare("UPDATE mia_sales_sessions SET conv_history = ? WHERE phone = ?")
            ->execute([json_encode($history, JSON_UNESCAPED_UNICODE), $phone]);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════════════════════════

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'manual'   => 'llamadas/WhatsApp manual',
            'web_form' => 'formulario web',
            'otas'     => 'OTAs (Booking/Airbnb)',
            default    => 'múltiples canales',
        };
    }

    private function painLabel(string $pain): string
    {
        return match ($pain) {
            'after_hours'      => 'pierde clientes fuera de horario',
            'slow_replies'     => 'respuestas lentas',
            'no_confirm'       => 'clientes que preguntan pero no concretan la compra o reserva',
            'high_commissions' => 'dependencia de plataformas de terceros con comisiones altas',
            default            => 'consultas sin atender que representan ventas perdidas',
        };
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
        $this->pdo->prepare("INSERT INTO mia_sales_sessions (phone, state) VALUES (?, 'new')")
            ->execute([$phone]);
        return $this->getSession($phone);
    }

    private function getSession(string $phone): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM mia_sales_sessions WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update the converted client's account (mia_clients + bot_config JSON)
     * using the email stored in the sales session to look up the client.
     */
    private function updateClientSettings(string $phone, array $updates): void
    {
        $session = $this->getSession($phone);
        $email   = $session['email'] ?? null;
        if (!$email) return;

        $stmt = $this->pdo->prepare('SELECT id, bot_config FROM mia_clients WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) return;

        $clientId  = (int)$client['id'];
        $botConfig = json_decode($client['bot_config'] ?? '{}', true) ?: [];

        // Fields that live inside bot_config JSON
        $botFields = ['description', 'services', 'pricing', 'hours', 'faqs', 'language', 'tone', 'business_type'];
        foreach ($botFields as $f) {
            if (isset($updates[$f])) $botConfig[$f] = $updates[$f];
        }

        // Fields that are direct columns on mia_clients
        $allowed = ['business_name', 'contact_name', 'whatsapp_number', 'business_type'];
        $sets    = [];
        $params  = [];
        foreach ($allowed as $f) {
            if (isset($updates[$f])) {
                $sets[]   = "{$f} = ?";
                $params[] = $updates[$f];
            }
        }
        $sets[]   = 'bot_config = ?';
        $params[] = json_encode($botConfig, JSON_UNESCAPED_UNICODE);
        $sets[]   = 'updated_at = NOW()';
        $params[] = $clientId;

        $this->pdo->prepare('UPDATE mia_clients SET ' . implode(', ', $sets) . ' WHERE id = ?')
                  ->execute($params);
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

