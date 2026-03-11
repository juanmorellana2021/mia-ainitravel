-- ═══════════════════════════════════════════════════════════════════
-- Mia demo seed data + password reset table
-- Run on prod VPS: mysql mia_db < _seed_data.sql
-- ═══════════════════════════════════════════════════════════════════

-- ── 1. Password reset tokens table ─────────────────────────────────
CREATE TABLE IF NOT EXISTS mia_password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    token_hash VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_hash),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. Demo leads for Samay Wasi (client_id = 1) ───────────────────
-- Clear any existing demo data first (safe re-run)
DELETE FROM mia_client_messages WHERE client_id = 1;
DELETE FROM mia_client_leads    WHERE client_id = 1;

INSERT INTO mia_client_leads
    (client_id, contact_name, phone, source, status, value_estimate, notes, created_at)
VALUES
-- 28-29 days ago (closed)
(1,'Carlos Mamani',   '51987651001','whatsapp','closed_won', 699.00,'Hostal Arcoiris Cusco, 12 hab. Pago mensual confirmado.',   DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1,'Rosa Condori',    '51976543210','whatsapp','closed_won', 399.00,'Albergue Valle Verde, 8 hab. Plan Basic.',                  DATE_SUB(NOW(), INTERVAL 27 DAY)),

-- 24-25 days ago
(1,'Miguel Torres',   '51965432109','whatsapp','interested',   0.00,'Hotel Plaza Puno, 25 hab. Pidio demo para el viernes.',     DATE_SUB(NOW(), INTERVAL 24 DAY)),
(1,'Lucia Apaza',     '51954321098','whatsapp','closed_won',  699.00,'Hotel Inti Paccha, 18 hab. En plan Pro.',                  DATE_SUB(NOW(), INTERVAL 23 DAY)),

-- 20-22 days ago
(1,'Jorge Quispe',    '51943210987','whatsapp','closed_lost',   0.00,'Hostal Sol Andino, presupuesto muy bajo.',                 DATE_SUB(NOW(), INTERVAL 21 DAY)),
(1,'Ana Huanca',      '51932109876','whatsapp','interested',    0.00,'Boutique hotel Ollantaytambo, 10 hab. Interesada.',        DATE_SUB(NOW(), INTERVAL 20 DAY)),

-- 14-18 days ago
(1,'Pedro Ccallo',    '51921098765','whatsapp','new',           0.00, NULL,                                                      DATE_SUB(NOW(), INTERVAL 17 DAY)),
(1,'Maria Caceres',   '51910987654','whatsapp','interested',    0.00,'Hostal Familiar Titicaca, pregunto por integraciones PMS.',DATE_SUB(NOW(), INTERVAL 15 DAY)),
(1,'Roberto Flores',  '51909876543','whatsapp','closed_won',  399.00,'Alojamiento Rio Urubamba, 7 hab. Plan Starter.',           DATE_SUB(NOW(), INTERVAL 14 DAY)),

-- 10-13 days ago
(1,'Elena Villanueva','51898765432','whatsapp','closed_lost',   0.00,'No tiene WhatsApp Business activo aun.',                  DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1,'David Mamani',    '51887654321','whatsapp','interested',    0.00,'Hotel Mirador Cusco, 30 hab. Pidio plan Enterprise.',      DATE_SUB(NOW(), INTERVAL 11 DAY)),
(1,'Carmen Ramos',    '51876543210','whatsapp','new',           0.00, NULL,                                                      DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- 6-9 days ago
(1,'Francisco Leon',  '51865432109','whatsapp','interested',    0.00,'Eco Lodge Manu, temporada alta se acerca.',               DATE_SUB(NOW(), INTERVAL 8 DAY)),
(1,'Sandra Gutierrez','51854321098','whatsapp','closed_won',  1199.00,'Hotel Apu Salkantay, 45 hab, 3 sedes. Plan Enterprise.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1,'Julio Salas',     '51843210987','whatsapp','interested',    0.00,'Hostal Las Orquideas, duda sobre idiomas soportados.',    DATE_SUB(NOW(), INTERVAL 6 DAY)),

-- 3-5 days ago
(1,'Patricia Chavez', '51832109876','whatsapp','new',           0.00, NULL,                                                      DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1,'Hernan Rodriguez','51821098765','whatsapp','interested',    0.00,'Hotel Valle del Colca, muy interesado. Cotizacion enviada.',DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1,'Isabel Torres',   '51810987654','whatsapp','closed_lost',   0.00,'Cancelo por contrato con otro proveedor.',                DATE_SUB(NOW(), INTERVAL 3 DAY)),

-- 1-2 days ago (fresh leads)
(1,'Andres Morales',  '51809876543','whatsapp','new',           0.00, NULL,                                                      DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1,'Valentina Ruiz',  '51798765432','whatsapp','new',           0.00, NULL,                                                      DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ── 3. Demo messages ────────────────────────────────────────────────
-- Messages for Carlos Mamani (closed_won)
INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51987651001', 'inbound',
    'Hola! Vi su anuncio. Tengo un hostal en Cusco y quiero automatizar mis respuestas de WhatsApp.',
    'mia', DATE_SUB(NOW(), INTERVAL 28 DAY)
FROM mia_client_leads WHERE phone='51987651001' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51987651001', 'outbound',
    'Hola Carlos! Soy Mia, el asistente virtual de Samay Wasi. Con gusto te cuento sobre nuestro sistema.',
    'mia', DATE_SUB(NOW(), INTERVAL 28 DAY)
FROM mia_client_leads WHERE phone='51987651001' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51987651001', 'inbound',
    'Cuantas habitaciones maneja el sistema? Yo tengo 12.',
    'mia', DATE_SUB(NOW(), INTERVAL 28 DAY)
FROM mia_client_leads WHERE phone='51987651001' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51987651001', 'outbound',
    'No hay limite de habitaciones! El plan Pro cubre hasta 3,000 conversaciones/mes. Para 12 hab recomendaria el plan Basic a S/399/mes.',
    'mia', DATE_SUB(NOW(), INTERVAL 27 DAY)
FROM mia_client_leads WHERE phone='51987651001' AND client_id=1 LIMIT 1;

-- Messages for Sandra Gutierrez (enterprise closed_won, 7 days ago)
INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51854321098', 'inbound',
    'Buenos dias. Tenemos 3 hoteles en Cusco, Puno y Lima. Necesitamos atencion 24/7 en todos.',
    'mia', DATE_SUB(NOW(), INTERVAL 7 DAY)
FROM mia_client_leads WHERE phone='51854321098' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51854321098', 'outbound',
    'Hola Sandra! Para 3 sedes tenemos el plan Enterprise a S/1,199/mes que incluye hasta 5,000 conversaciones y soporte prioritario.',
    'mia', DATE_SUB(NOW(), INTERVAL 7 DAY)
FROM mia_client_leads WHERE phone='51854321098' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51854321098', 'inbound',
    'Perfecto, como procedemos para activar el servicio?',
    'mia', DATE_SUB(NOW(), INTERVAL 7 DAY)
FROM mia_client_leads WHERE phone='51854321098' AND client_id=1 LIMIT 1;

-- Messages for Hernan Rodriguez (interested, 4 days ago)
INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51821098765', 'inbound',
    'Hola, me interesa el sistema pero necesito saber si funciona en espanol e ingles para los turistas.',
    'mia', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM mia_client_leads WHERE phone='51821098765' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51821098765', 'outbound',
    'Hola Hernan! Si, Mia responde en mas de 50 idiomas incluyendo espanol, ingles, portugues, frances y mas. Detecto el idioma automaticamente.',
    'mia', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM mia_client_leads WHERE phone='51821098765' AND client_id=1 LIMIT 1;

-- Today messages (so "today's activity" counter works)
INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51809876543', 'inbound',
    'Buenas tardes! Vi que ofrecen un bot de WhatsApp para hoteles. Tenemos un lodge en el Valle Sagrado.',
    'mia', DATE_SUB(NOW(), INTERVAL 2 HOUR)
FROM mia_client_leads WHERE phone='51809876543' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51809876543', 'outbound',
    'Hola Andres! Soy Mia. Que tipo de consultas reciben mas frecuentemente de sus huespedes?',
    'mia', DATE_SUB(NOW(), INTERVAL 2 HOUR)
FROM mia_client_leads WHERE phone='51809876543' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51798765432', 'inbound',
    'Hola! Tengo una posada en Aguas Calientes y me recomendaron su servicio.',
    'mia', DATE_SUB(NOW(), INTERVAL 1 HOUR)
FROM mia_client_leads WHERE phone='51798765432' AND client_id=1 LIMIT 1;

INSERT INTO mia_client_messages
    (client_id, lead_id, phone, direction, message, handled_by, created_at)
SELECT 1, id, '51798765432', 'outbound',
    'Bienvenida Valentina! Cuantas habitaciones tiene su posada y actualmente como maneja las reservas?',
    'mia', DATE_SUB(NOW(), INTERVAL 1 HOUR)
FROM mia_client_leads WHERE phone='51798765432' AND client_id=1 LIMIT 1;

-- ── Done ─────────────────────────────────────────────────────────────
SELECT 'Seed complete!' AS status;
SELECT COUNT(*) AS total_leads    FROM mia_client_leads    WHERE client_id=1;
SELECT COUNT(*) AS total_messages FROM mia_client_messages WHERE client_id=1;
SELECT COUNT(*) AS today_messages FROM mia_client_messages WHERE client_id=1 AND DATE(created_at)=CURDATE();
