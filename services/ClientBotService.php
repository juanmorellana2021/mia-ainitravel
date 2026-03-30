<?php
/**
 * mia/services/ClientBotService.php
 *
 * AI bot service for a Mia client's WhatsApp number.
 * Reads the client's bot_config to build a custom system prompt,
 * then calls Groq to answer the guest's message.
 *
 * This is SEPARATE from MiaSalesService (which sells Mia to new businesses).
 * This service IS Mia running on behalf of a subscribed client's business.
 */

declare(strict_types=1);

class ClientBotService
{
    private PDO    $pdo;
    private Client $client;
    private array  $cfg;

    // Plan-based feature flags
    private bool $canCaptureLead;
    private bool $canHandoff;
    private bool $canBroadcast;
    private bool $canSequences;
    private bool $canAppointments;

    private const GROQ_KEY          = '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';
    private const GROQ_MODEL        = 'llama-3.3-70b-versatile';
    private const GROQ_MODEL_FALLBACK = 'llama-3.1-8b-instant'; // 500K TPD — used when 70B is rate-limited
    private const MAX_HISTORY = 10; // message pairs

    private const PLAN_CAPS = [
        'trial'           => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments'],
        'starter'         => [],
        'basic'           => ['handoff', 'leads', 'appointments'],   // Mia Ventas: closes sales + captures leads
        'pro'             => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments'],
        'enterprise'      => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments'],
        'enterprise_duo'  => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments'],
        'enterprise_chain'=> ['handoff', 'leads', 'broadcast', 'sequences', 'appointments'],
        'enterprise_corp' => ['handoff', 'leads', 'broadcast', 'sequences', 'appointments'],
    ];

    public static function planHasCap(string $plan, string $cap): bool
    {
        return in_array($cap, self::PLAN_CAPS[$plan] ?? [], true);
    }

    // Monthly conversation limits per plan (0 = unlimited)
    public const CONV_LIMITS = [
        'trial'           => 0,    // trial = full Pro experience, unlimited
        'starter'         => 500,
        'basic'           => 1000,
        'pro'             => 0,
        'enterprise'      => 0,
        'enterprise_duo'  => 0,
        'enterprise_chain'=> 0,
        'enterprise_corp' => 0,
    ];

    public function __construct(Client $client)
    {
        $this->pdo    = Database::get();
        $this->client = $client;
        $this->cfg    = json_decode($client->bot_config ?? '{}', true) ?: [];

        $caps = self::PLAN_CAPS[$client->plan] ?? [];
        $this->canHandoff     = in_array('handoff',      $caps);
        $this->canCaptureLead = in_array('leads',        $caps);
        $this->canBroadcast   = in_array('broadcast',    $caps);
        $this->canSequences   = in_array('sequences',    $caps);
        $this->canAppointments = in_array('appointments', $caps);

        $this->ensureTable();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Process an incoming WhatsApp message from a guest.
     * Returns ['reply' => string].
     */
    public function process(string $guestPhone, string $message, string $fromLid = '', string $contactName = '', string $profilePicUrl = ''): array
    {
        $guestPhone = $this->normalizePhone($guestPhone);
        $msg        = trim($message);

        if (empty($msg)) {
            return ['reply' => ''];
        }

        // ── Upsert lead record ────────────────────────────────────────────────
        // Every phone that messages the bot becomes a lead automatically.
        $leadService = new ClientLeadService();
        $stmt = $this->pdo->prepare(
            "SELECT id, contact_type FROM mia_client_leads WHERE client_id=? AND phone=? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$this->client->id, $guestPhone]);
        $leadRow = $stmt->fetch();

        // ── Fallback: phone without country-code prefix ───────────────────────
        // WA sometimes sends just local digits (e.g. 930293197) while the DB stored
        // the full E.164 number (e.g. 51930293197). Try stripping a 2-digit prefix.
        if (!$leadRow && strlen($guestPhone) > 9) {
            $shortPhone = substr($guestPhone, 2); // strip 2-digit country code
            $stmtShort  = $this->pdo->prepare(
                "SELECT id, contact_type FROM mia_client_leads WHERE client_id=? AND phone=? ORDER BY id DESC LIMIT 1"
            );
            $stmtShort->execute([$this->client->id, $shortPhone]);
            $leadRow = $stmtShort->fetch();
            if ($leadRow) {
                // Canonicalize to full number going forward
                $this->pdo->prepare("UPDATE mia_client_leads SET phone=? WHERE id=?")->execute([$guestPhone, $leadRow['id']]);
                $this->pdo->prepare("UPDATE mia_client_messages SET phone=? WHERE client_id=? AND phone=?")->execute([$guestPhone, $this->client->id, $shortPhone]);
                error_log("[ClientBot:{$this->client->id}] Canonical phone {$shortPhone} → {$guestPhone} for lead {$leadRow['id']}");
            }
        }

        // ── Direct LID lookup: match lead where lid column = fromLid ─────────
        // This is the fastest path — if we've seen this contact before and stored
        // their LID, we find them instantly without any phone matching.
        if (!$leadRow && $fromLid !== '') {
            $stmtLidCol = $this->pdo->prepare(
                "SELECT id, contact_type FROM mia_client_leads WHERE client_id=? AND lid=? ORDER BY id ASC LIMIT 1"
            );
            $stmtLidCol->execute([$this->client->id, $fromLid]);
            $leadRow = $stmtLidCol->fetch();
            if ($leadRow) {
                error_log("[ClientBot:{$this->client->id}] Matched lead {$leadRow['id']} by LID column '{$fromLid}'");
            }
        }

        // ── LID migration: if not found by real phone, check old LID-derived number ──
        // Only run when $guestPhone is a real phone number (not empty/unresolved LID)
        if (!$leadRow && $fromLid !== '' && $guestPhone !== '') {
            $lidPhone = $this->normalizePhone($fromLid);
            if ($lidPhone !== $guestPhone) {
                $stmtLid = $this->pdo->prepare(
                    "SELECT id, contact_type FROM mia_client_leads WHERE client_id=? AND phone=? ORDER BY id ASC LIMIT 1"
                );
                $stmtLid->execute([$this->client->id, $lidPhone]);
                $lidLeadRow = $stmtLid->fetch();
                if ($lidLeadRow) {
                    // Check if a lead with the real phone already exists
                    $stmtReal = $this->pdo->prepare(
                        "SELECT id, contact_type FROM mia_client_leads WHERE client_id=? AND phone=? ORDER BY id ASC LIMIT 1"
                    );
                    $stmtReal->execute([$this->client->id, $guestPhone]);
                    $realLeadRow = $stmtReal->fetch();

                    if ($realLeadRow && $realLeadRow['id'] !== $lidLeadRow['id']) {
                        // Real-phone lead exists — merge LID lead into it, delete LID lead
                        $this->pdo->prepare("UPDATE mia_client_messages SET lead_id=?, phone=? WHERE lead_id=?")
                                  ->execute([$realLeadRow['id'], $guestPhone, $lidLeadRow['id']]);
                        $this->pdo->prepare("DELETE FROM mia_client_leads WHERE id=?")
                                  ->execute([$lidLeadRow['id']]);
                        $leadRow = $realLeadRow;
                        error_log("[ClientBot:{$this->client->id}] Merged LID lead {$lidLeadRow['id']} ({$lidPhone}) into real lead {$realLeadRow['id']} ({$guestPhone})");
                    } else {
                        // Only LID lead exists — update its phone to real phone
                        $this->pdo->prepare("UPDATE mia_client_leads SET phone=? WHERE id=?")
                                  ->execute([$guestPhone, $lidLeadRow['id']]);
                        $this->pdo->prepare("UPDATE mia_client_messages SET phone=? WHERE client_id=? AND phone=?")
                                  ->execute([$guestPhone, $this->client->id, $lidPhone]);
                        $leadRow = $lidLeadRow;
                        error_log("[ClientBot:{$this->client->id}] Migrated phone {$lidPhone} → {$guestPhone} for lead {$lidLeadRow['id']}");
                    }
                }
            }
        }

        // ── Fallback: match by contact_name when phone lookup fails ──────────
        if (!$leadRow && $contactName !== '') {
            $stmtName = $this->pdo->prepare(
                "SELECT id, contact_type, phone FROM mia_client_leads WHERE client_id=? AND contact_name=? ORDER BY id DESC LIMIT 1"
            );
            $stmtName->execute([$this->client->id, $contactName]);
            $leadRow = $stmtName->fetch();
            if ($leadRow) {
                error_log("[ClientBot:{$this->client->id}] Matched lead {$leadRow['id']} by contact_name '{$contactName}' (phone {$guestPhone} didn't match)");
                // If the stored phone is a LID and we now have a real phone, upgrade it
                $storedPhone = $leadRow['phone'] ?? '';
                if (strlen($storedPhone) >= 14 && $guestPhone !== '' && strlen($guestPhone) < 14) {
                    $this->pdo->prepare("UPDATE mia_client_leads SET phone=? WHERE id=?")
                              ->execute([$guestPhone, $leadRow['id']]);
                    $this->pdo->prepare("UPDATE mia_client_messages SET phone=? WHERE client_id=? AND phone=?")
                              ->execute([$guestPhone, $this->client->id, $storedPhone]);
                    error_log("[ClientBot:{$this->client->id}] Upgraded phone {$storedPhone} → {$guestPhone} for lead {$leadRow['id']} (name match)");
                }
            }
        }

        // ── Fallback: match by profile pic URL hash ───────────────────────────
        // WhatsApp profile pic URLs contain a stable hash even when LIDs change.
        // Extract the hash from the URL (the long alphanumeric segment) and compare
        // against hashes stored in profile_pic_url column.
        if (!$leadRow && $profilePicUrl !== '') {
            // Extract hash: the long token after the last / and before ?
            if (preg_match('/\/([a-zA-Z0-9_\-]{20,})\b/', $profilePicUrl, $picMatch)) {
                $picHash = $picMatch[1];
                $stmtPic = $this->pdo->prepare(
                    "SELECT id, contact_type FROM mia_client_leads
                     WHERE client_id=? AND profile_pic_url LIKE ?
                     ORDER BY id DESC LIMIT 1"
                );
                $stmtPic->execute([$this->client->id, '%' . $picHash . '%']);
                $leadRow = $stmtPic->fetch();
                if ($leadRow) {
                    error_log("[ClientBot:{$this->client->id}] Matched lead {$leadRow['id']} by profile pic hash '{$picHash}' (phone {$guestPhone})");
                    // If the stored phone is a LID and we now have a real phone, upgrade it
                    $storedPhone = $leadRow['phone'] ?? '';
                    if (strlen($storedPhone) >= 14 && $guestPhone !== '' && strlen($guestPhone) < 14) {
                        $this->pdo->prepare("UPDATE mia_client_leads SET phone=? WHERE id=?")
                                  ->execute([$guestPhone, $leadRow['id']]);
                        $this->pdo->prepare("UPDATE mia_client_messages SET phone=? WHERE client_id=? AND phone=?")
                                  ->execute([$guestPhone, $this->client->id, $storedPhone]);
                        error_log("[ClientBot:{$this->client->id}] Upgraded phone {$storedPhone} → {$guestPhone} for lead {$leadRow['id']} (pic hash match)");
                    }
                }
            }
        }

        if ($leadRow) {
            $leadId = (int)$leadRow['id'];
            // Save LID on the lead if we have one and it isn't stored yet
            if ($fromLid !== '') {
                $this->pdo->prepare(
                    "UPDATE mia_client_leads SET lid=? WHERE id=? AND (lid IS NULL OR lid='')"
                )->execute([$fromLid, $leadId]);
            }
        } else {
            // New contact — create lead, trigger auto-enroll sequences
            $lead   = $leadService->create($this->client->id, [
                'contact_name' => $contactName,  // WA pushname from bot worker (may be empty)
                'phone'        => $guestPhone,
                'source'       => 'whatsapp',
                'status'       => 'new',
            ]);
            $leadId = $lead->id;
            // Save LID immediately so the next message from this contact resolves directly
            if ($fromLid !== '') {
                $this->pdo->prepare("UPDATE mia_client_leads SET lid=? WHERE id=?")
                          ->execute([$fromLid, $leadId]);
            }
        }

        // ── Routing verification: verify the resolved lead matches incoming data ─
        // Re-fetch the full lead record to verify identity consistency.
        $verifyStmt = $this->pdo->prepare(
            "SELECT id, phone, lid, contact_name FROM mia_client_leads WHERE id = ? LIMIT 1"
        );
        $verifyStmt->execute([$leadId]);
        $verifiedLead = $verifyStmt->fetch();
        if ($verifiedLead) {
            $vPhone = $verifiedLead['phone'] ?? '';
            $vLid   = $verifiedLead['lid']   ?? '';
            $vName  = $verifiedLead['contact_name'] ?? '';

            // 1) If we have a real phone and lead phone is set but different → possible mismatch
            if ($guestPhone !== '' && $vPhone !== '' && $guestPhone !== $vPhone) {
                error_log("[RoutingCheck:{$this->client->id}] WARN Lead {$leadId} phone mismatch: incoming={$guestPhone} stored={$vPhone}");
            }
            // 2) If we have a LID and lead LID is set but different → identity may have changed
            if ($fromLid !== '' && $vLid !== '' && $fromLid !== $vLid) {
                error_log("[RoutingCheck:{$this->client->id}] WARN Lead {$leadId} LID mismatch: incoming={$fromLid} stored={$vLid}");
            }
            // 3) Log-only: no auto-writes here — phone upgrades are handled by
            //    the dedicated migration/name-match/pic-match blocks above.
            //    Auto-writing caused LID digits to bleed into phone columns.
            if ($guestPhone !== '' && $vPhone !== '' && $guestPhone !== $vPhone) {
                error_log("[RoutingCheck:{$this->client->id}] INFO Lead {$leadId} phone differs: incoming={$guestPhone} stored={$vPhone}");
            }
            // 5) Log the connection used for this message
            $routeType = $fromLid !== '' ? 'LID' : 'Phone';
            $routeAddr = $fromLid !== '' ? $fromLid : $guestPhone;
            error_log("[RoutingCheck:{$this->client->id}] Routed to lead {$leadId} via {$routeType}={$routeAddr} (stored phone={$vPhone}, lid={$vLid})");
        }

        // ── Save profile pic if we have a URL and no pic yet ─────────────────
        if ($profilePicUrl) {
            $hasPic = $this->pdo->prepare('SELECT profile_pic, profile_pic_url FROM mia_client_leads WHERE id = ? LIMIT 1');
            $hasPic->execute([$leadId]);
            $picRow = $hasPic->fetch();
            $currentPic    = $picRow ? $picRow['profile_pic']     : null;
            $currentPicUrl = $picRow ? $picRow['profile_pic_url'] : null;

            // Always persist the latest WA URL (used as fingerprint for future hash matching)
            if ($currentPicUrl !== $profilePicUrl) {
                $this->pdo->prepare('UPDATE mia_client_leads SET profile_pic_url = ? WHERE id = ?')
                          ->execute([$profilePicUrl, $leadId]);
            }

            if (!$currentPic) {
                $savedPath = $this->downloadProfilePic($profilePicUrl, $this->client->id, $leadId);
                if ($savedPath) {
                    $this->pdo->prepare('UPDATE mia_client_leads SET profile_pic = ? WHERE id = ?')
                              ->execute([$savedPath, $leadId]);
                }
            }
        }

        // ── Contact type routing ──────────────────────────────────────────────
        $contactType = $leadRow ? ($leadRow['contact_type'] ?? 'lead') : 'lead';

        if ($contactType === 'ignored') {
            // Silent: save message so owner can see it in the dashboard, but Mia sends no reply
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $msg, 'inbound', 'bot');
            error_log("[ClientBot:{$this->client->id}] Ignored msg from {$guestPhone} (contact_type=ignored)");
            return ['reply' => ''];
        }

        if ($contactType === 'staff') {
            // Staff: Mia answers internal business questions using configured knowledge
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $msg, 'inbound', 'bot');
            $history   = $this->loadHistory($guestPhone);
            $chatMsgs  = array_merge(
                [['role' => 'system', 'content' => $this->buildStaffPrompt()]],
                $history,
                [['role' => 'user', 'content' => $msg]]
            );
            $reply = $this->callGroq($chatMsgs, 200);
            $this->log($guestPhone, 'user', $msg);
            $this->log($guestPhone, 'assistant', $reply);
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $reply, 'outbound', 'bot');
            return ['reply' => $reply];
        }

        if ($contactType === 'friend' || $contactType === 'proveedor') {
            // Save inbound but skip sales flow entirely
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $msg, 'inbound', 'bot');
            $history  = $this->loadHistory($guestPhone);
            $sysPrompt = $contactType === 'proveedor'
                ? $this->buildProveedorPrompt()
                : $this->buildFriendlyPrompt();
            $chatMsgs = array_merge(
                [['role' => 'system', 'content' => $sysPrompt]],
                $history,
                [['role' => 'user', 'content' => $msg]]
            );
            $reply = $this->callGroq($chatMsgs);
            $this->log($guestPhone, 'user', $msg);
            $this->log($guestPhone, 'assistant', $reply);
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $reply, 'outbound', 'bot');
            return ['reply' => $reply];
        }
        // ─────────────────────────────────────────────────────────────────────

        // ── Monthly conversation limit check ─────────────────────────────────
        $limit = self::CONV_LIMITS[$this->client->plan] ?? 0;
        if ($limit > 0) {
            $addonSvc = new AddonService();

            // If the client bought an unlimited-month add-on, skip the cap entirely
            if (!$addonSvc->hasUnlimitedThisMonth($this->client->id)) {

                $stmtExist = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM mia_client_messages
                     WHERE client_id = ? AND phone = ? AND direction = 'inbound'
                       AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
                );
                $stmtExist->execute([$this->client->id, $guestPhone]);
                $isNewConvo = ((int)$stmtExist->fetchColumn() === 0);

                if ($isNewConvo) {
                    $stmtCount = $this->pdo->prepare(
                        "SELECT COUNT(DISTINCT phone) FROM mia_client_messages
                         WHERE client_id = ? AND direction = 'inbound'
                           AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
                    );
                    $stmtCount->execute([$this->client->id]);
                    $used = (int)$stmtCount->fetchColumn();

                    // Add any purchased extra conversation slots
                    $effectiveLimit = $limit + $addonSvc->getExtraConvosThisMonth($this->client->id);

                    if ($used >= $effectiveLimit) {
                        error_log("[ClientBot:{$this->client->id}] Conv limit reached ({$used}/{$effectiveLimit}) — blocking {$guestPhone}");

                        // Notify the owner once per month (not on every blocked message)
                        if (!$addonSvc->limitNoticeAlreadySentThisMonth($this->client->id)) {
                            (new NotificationService())->notifyConvLimitHit($this->client);
                            $addonSvc->markLimitNoticeSent($this->client->id);
                        }

                        // Save the inbound message so it appears in the CRM
                        $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $msg, 'inbound', 'bot');

                        // Polite reply to the guest instead of silent drop
                        $limitReply = '¡Hola! En este momento estamos atendiendo alta demanda. Te responderemos muy pronto. ¡Gracias por tu paciencia! 🙏';
                        $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $limitReply, 'outbound', 'bot');
                        return ['reply' => $limitReply];
                    }
                }
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        // ── Business-hours check ──────────────────────────────────────────────
        if (!$this->isWithinHours()) {
            $closedMsg = $this->cfg['hours_config']['closed_message']
                ?? 'Estamos cerrados por el momento. Te responderemos en cuanto abramos. ¡Gracias por escribirnos! 🕐';
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $msg,       'inbound',  'bot');
            $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $closedMsg, 'outbound', 'bot');
            return ['reply' => $closedMsg];
        }
        // ─────────────────────────────────────────────────────────────────────

        // Save inbound message to CRM
        $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $msg, 'inbound', 'bot');

        // Pause any active follow-up sequences — lead replied, they're engaged
        (new SequenceService())->pauseForLead($leadId, $this->client->id);

        // No regex intent interception — Groq detects handoff requests from context
        // (see INTENCIONES block in buildSystemPrompt)

        $history  = $this->loadHistory($guestPhone);
        $systemPrompt = $this->buildSystemPrompt($guestPhone);
        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user',   'content' => $msg]]
        );

        // Allow more tokens when photos or memories are in the prompt
        $hasPhotos  = str_contains($systemPrompt, '[FOTO:') || str_contains($systemPrompt, 'FOTOS DEL NEGOCIO');
        $hasMemory  = str_contains($systemPrompt, 'MEMORIA DEL CONTACTO');
        $hasAppts   = str_contains($systemPrompt, 'AGENDA DE CITAS:');
        $maxTokens  = $hasPhotos ? 600 : ($hasMemory ? 120 : ($hasAppts ? 120 : 80));
        $reply = $this->callGroq($messages, $maxTokens);

        // ── Appointment booking detection ────────────────────────────────────
        if ($this->canAppointments && preg_match('/\[BOOK:(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})\]/i', $reply, $bMatch)) {
            $bookDate = $bMatch[1];
            $bookTime = $bMatch[2];
            $reply    = trim(preg_replace('/\s*\[BOOK:[^\]]+\]/i', '', $reply));

            $stmtLN = $this->pdo->prepare('SELECT contact_name FROM mia_client_leads WHERE id = ? LIMIT 1');
            $stmtLN->execute([$leadId]);
            $leadName = trim((string)$stmtLN->fetchColumn()) ?: $guestPhone;

            try {
                (new AppointmentService())->book($this->client->id, [
                    'date'         => $bookDate,
                    'time'         => $bookTime,
                    'contact_name' => $leadName,
                    'phone'        => $guestPhone,
                    'lead_id'      => $leadId,
                ]);
                error_log("[ClientBot:{$this->client->id}] Cita booked {$bookDate} {$bookTime} for {$guestPhone}");
            } catch (\RuntimeException $e) {
                $reply .= ' (Ese horario ya no está disponible, ¿puedes elegir otro?)';
                error_log("[ClientBot:{$this->client->id}] Booking failed: " . $e->getMessage());
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        $this->log($guestPhone, 'user', $msg);
        $this->log($guestPhone, 'assistant', $reply);

        // Save outbound reply to CRM
        $leadService->saveMessage($this->client->id, $leadId, $guestPhone, $reply, 'outbound', 'bot');

        // ── Auto-classify lead status based on conversation ──────────────────
        $this->autoClassifyLead($leadId, $guestPhone, $msg, $reply);

        // Extract and store new facts about this lead (async-safe, non-blocking)
        try {
            (new LeadMemoryService())->extractAndStore($this->client->id, $guestPhone, $msg, $reply);
        } catch (\Throwable $e) {
            error_log("[ClientBot:{$this->client->id}] Memory extraction failed: " . $e->getMessage());
        }

        return ['reply' => $reply];
    }

    // ── Auto-classify lead status from conversation signals ──────────────────

    private function autoClassifyLead(int $leadId, string $phone, string $inbound, string $outbound): void
    {
        try {
            $stmt = $this->pdo->prepare('SELECT status FROM mia_client_leads WHERE id = ? AND client_id = ? LIMIT 1');
            $stmt->execute([$leadId, $this->client->id]);
            $current = (string)$stmt->fetchColumn();

            // Only auto-promote: new → interested. Never demote or override manual changes.
            if ($current !== 'new') return;

            $combo = mb_strtolower($inbound . ' ' . $outbound);

            // Signals that the person is interested (asking prices, availability, booking, wanting info)
            $interested = false;
            $patterns = [
                '/\bpreci(o|os)\b/',
                '/\bcuánto|cuanto\b/',
                '/\bcost(o|a|ar)\b/',
                '/\bdisponib(le|ilidad)\b/',
                '/\breserv(a|ar|ación)\b/',
                '/\bhabitaci(ón|ones)\b/',
                '/\bnoches?\b/',
                '/\bpaquete/',
                '/\bpromoción|promocion\b/',
                '/\bdescuento/',
                '/\bquiero\b/',
                '/\bme\s+interesa/',
                '/\bcotiza(r|ción|cion)\b/',
                '/\btarifa/',
                '/\bpara\s+\d+\s+persona/',
                '/\bcita\b/',
                '/\bhora(rio|s)?\b/',
                '/\bagendar\b/',
            ];

            foreach ($patterns as $p) {
                if (preg_match($p, $combo)) {
                    $interested = true;
                    break;
                }
            }

            // Also check message count — 3+ exchanges from this lead signals engagement
            if (!$interested) {
                $stmtCnt = $this->pdo->prepare(
                    'SELECT COUNT(*) FROM mia_client_messages WHERE client_id = ? AND lead_id = ? AND direction = ?'
                );
                $stmtCnt->execute([$this->client->id, $leadId, 'inbound']);
                if ((int)$stmtCnt->fetchColumn() >= 3) {
                    $interested = true;
                }
            }

            if ($interested) {
                $this->pdo->prepare(
                    'UPDATE mia_client_leads SET status = ?, updated_at = NOW() WHERE id = ? AND client_id = ? AND status = ?'
                )->execute(['interested', $leadId, $this->client->id, 'new']);

                // Trigger auto-enroll sequences for interested leads
                (new SequenceService())->autoEnroll($leadId, $this->client->id, 'on_interested');

                error_log("[ClientBot:{$this->client->id}] Lead {$leadId} auto-classified: new → interested");
            }
        } catch (\Throwable $e) {
            error_log("[ClientBot:{$this->client->id}] autoClassify error: " . $e->getMessage());
        }
    }

    // ── Alternative prompts (non-lead contact types) ─────────────────────────

    private function buildFriendlyPrompt(): string
    {
        $bizName = $this->client->business_name;
        return "Eres el asistente personal de {$bizName}. Esta persona es amigo/a o familiar del dueño del negocio. "
             . "Sé casual, amigable y natural — como un asistente de confianza. "
             . "NO hagas ventas, NO captures datos de lead, NO pidas nombre ni teléfono con fines comerciales. "
             . "Simplemente responde lo que pregunten de forma cálida y directa. Máximo 3 oraciones.";
    }

    private function buildProveedorPrompt(): string
    {
        $bizName = $this->client->business_name;
        return "Eres el asistente administrativo de {$bizName}. Esta persona es un proveedor del negocio. "
             . "Sé formal, profesional y eficiente. Ayuda con consultas sobre pedidos, pagos, entregas o coordinación logística. "
             . "NO hagas ventas ni trates de capturar datos de cliente. "
             . "Si no tienes la información exacta, indica que transmitirás la consulta al equipo responsable. Máximo 4 oraciones.";
    }

    private function buildStaffPrompt(): string
    {
        $bizName  = $this->client->business_name;
        $desc     = $this->cfg['description']    ?? '';
        $services = $this->cfg['services']       ?? '';
        $pricing  = $this->cfg['pricing']        ?? '';
        $hours    = $this->cfg['hours']          ?? '';
        $faqs     = $this->cfg['faqs']           ?? '';
        $custom   = $this->cfg['custom_instructions'] ?? '';

        $knowledge = implode("\n", array_filter([
            $desc     ? "DESCRIPCIÓN DEL NEGOCIO: {$desc}"     : '',
            $services ? "SERVICIOS/PRODUCTOS: {$services}"     : '',
            $pricing  ? "PRECIOS: {$pricing}"                  : '',
            $hours    ? "HORARIOS: {$hours}"                   : '',
            $faqs     ? "PREGUNTAS FRECUENTES: {$faqs}"        : '',
            $custom   ? "INSTRUCCIONES ADICIONALES: {$custom}" : '',
        ]));

        return "Eres el asistente interno de {$bizName}. Esta persona es un miembro del staff o equipo del negocio. "
             . "Tu función es responder preguntas sobre el funcionamiento interno del negocio: procesos, horarios, servicios, precios, políticas y cualquier información operativa que el dueño haya configurado. "
             . "Sé claro, conciso y útil — como un manual de negocio interactivo. "
             . "NO hagas ventas externas. "
             . ($knowledge ? "\n\nCONOCIMIENTO DEL NEGOCIO:\n{$knowledge}" : '');
    }

    // ── System prompt ─────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $phone = ''): string
    {
        $bizName    = $this->client->business_name;
        $rawType    = $this->cfg['business_type']  ?? $this->client->business_type ?? 'negocio';
        $customType = $this->cfg['custom_type']    ?? '';
        $bizType    = ($rawType === 'other' && $customType !== '') ? $customType : $rawType;
        $desc       = $this->cfg['description']    ?? '';
        $services   = $this->cfg['services']       ?? '';
        $pricing    = $this->cfg['pricing']        ?? '';
        $hours      = $this->cfg['hours']          ?? '';
        $faqs       = $this->cfg['faqs']           ?? '';
        $website    = $this->cfg['website']        ?? '';
        $location   = $this->cfg['location']       ?? '';
        $googleMaps = $this->cfg['google_maps']    ?? '';
        $tone       = $this->cfg['tone']        ?? 'friendly';
        $language   = $this->cfg['language']     ?? 'auto';
        $charSkills = (array)($this->cfg['char_skills'] ?? []);

        // Build skill-specific prompt injections
        $skillMap = [
            'humor'    => 'Uso de humor ligero y apropiado: incluye una broma corta o comentario ingenioso cuando el momento lo permita de forma natural.',
            'empathy'  => 'Empatía activa: reconoce el sentimiento del cliente antes de dar información ("Entiendo que puede ser frustrante...", "Me alegra que preguntes eso").',
            'stories'  => 'Cuenta micro-historias de éxito: cuando sea relevante menciona brevemente un cliente similar que tuvo un buen resultado con el negocio.',
            'direct'   => 'Estilo ultra-directo: sin frases de relleno, sin saludos largos — la primera frase ya da la respuesta.',
            'scarcity' => 'Usa escasez y urgencia cuando sea apropiado: disponibilidad limitada, temporada alta, oferta por tiempo limitado.',
            'patient'  => 'Nunca presiones al cliente. Si no está listo, respeta su ritmo. Solo una pregunta de seguimiento por turno, nunca dos.',
            'usted'    => 'Usa SIEMPRE "usted" y nunca "tú". Trato formal en todo momento.',
            'tips'     => 'Proactivo con valor extra: cuando sea natural, da 1 consejo útil adicional relacionado con lo que preguntaron.',
            'premium'  => 'Voz de marca premium: usa vocabulario refinado — "inversión" no "costo", "exclusivo" no "barato", "seleccionado" no "disponible".',
            'proactive'=> 'Siempre ofrece un próximo paso claro o alternativa sin que te lo pidan. No dejes la conversación sin dirección.',
        ];
        $activeSkillLines = [];
        foreach ($charSkills as $sk) {
            if (isset($skillMap[$sk])) $activeSkillLines[] = '- ' . $skillMap[$sk];
        }
        $skillsBlock = !empty($activeSkillLines)
            ? "\nHABILIDADES DE PERSONALIDAD ACTIVAS:\n" . implode("\n", $activeSkillLines)
            : '';

        $toneDesc = match ($tone) {
            'professional' => 'Formal y profesional. Respuestas precisas y bien estructuradas.',
            'casual'       => 'Casual y relajado. Como hablar con un amigo que conoce el negocio.',
            'luxury'       => 'Exclusivo y refinado. Al nivel de un servicio de lujo de cinco estrellas.',
            default        => 'Amigable y cercano. Cálido, directo y genuinamente útil.',
        };

        $languageRule = match ($language) {
            'en'   => 'Always respond in English.',
            'auto' => 'Detect the language of the customer\'s message and respond in the same language.',
            default => 'Responde siempre en español.',
        };

        $handoffInstruction = $this->canHandoff
            ? "Si el cliente claramente pide hablar con una persona real (ej: 'quiero hablar con alguien', 'necesito un humano', 'comunícame con el equipo'), responde: 'Entendido, aviso al equipo de {$bizName} ahora mismo. Alguien te contactará en breve 👋'. NO actives esto si el cliente pregunta sobre servicios, agentes de viaje, o cualquier otra cosa que incluya las palabras humano/agente/persona en otro contexto."
            : "Si el cliente pide hablar con una persona, dile que puede comunicarse directamente con el negocio.";

        // Mia Ventas (basic) = closes sales herself. Pro/Enterprise = capture for team follow-up.
        $leadBlock = '';
        if ($this->canCaptureLead) {
            $leadBlock = $this->client->plan === 'basic'
                ? "- Tu objetivo es CERRAR LA VENTA en esta conversación. Guía al cliente activamente hacia la decisión de comprar/reservar. Pregunta lo que necesites (nombre, preferencias, fecha, presupuesto) de forma natural para confirmar. Registra sus datos en el CRM. No esperes a que pidan hablar con el equipo — intenta cerrar tú mismo hasta el final."
                : "- Si el cliente muestra interés en comprar/reservar algo específico, pide su nombre y número/email de forma natural para que el equipo lo contacte.";
        }

        $servicesBlock = $services ? "SERVICIOS Y PRODUCTOS:\n{$services}" : '';
        $pricingBlock  = $pricing  ? "PRECIOS:\n{$pricing}"               : '';
        $hoursBlock    = $hours    ? "HORARIOS:\n{$hours}"                 : '';
        $faqsBlock     = $faqs     ? "PREGUNTAS FRECUENTES:\n{$faqs}"      : '';
        $descBlock     = $desc     ? "SOBRE EL NEGOCIO:\n{$desc}"          : '';
        $websiteBlock  = $website  ? "SITIO WEB / REDES SOCIALES: {$website}" : '';
        $locationBlock = '';
        if ($location || $googleMaps) {
            $locationBlock = 'UBICACIÓN / DIRECCIÓN:';
            if ($location)   $locationBlock .= " {$location}";
            if ($googleMaps) $locationBlock .= "\nLINK GOOGLE MAPS: {$googleMaps} — Comparte este link cuando el cliente pregunte cómo llegar, dónde queda, o pida ubicación.";
        }

        // Lead memory: load remembered facts about this contact
        $memoryBlock = '';
        if ($phone !== '') {
            try {
                $memoryBlock = (new LeadMemoryService())->getMemoryBlock($this->client->id, $phone);
            } catch (\Throwable $e) {
                error_log("[ClientBot:{$this->client->id}] Memory load failed: " . $e->getMessage());
            }
        }

        // Photos: fetch public URLs for this client
        $photosBlock = '';
        try {
            $stmt = $this->pdo->prepare(
                "SELECT filename, caption, photo_name, description, price FROM mia_client_photos WHERE client_id = ? ORDER BY sort_order ASC, id ASC LIMIT 30"
            );
            $stmt->execute([$this->client->id]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $baseUrl = \App::URL;
                $lines = [];
                foreach ($rows as $row) {
                    $url = $baseUrl . '/assets/uploads/photos/' . $this->client->id . '/' . $row['filename'];
                    $parts = [];
                    if (!empty($row['photo_name']))  $parts[] = $row['photo_name'];
                    if (!empty($row['description'])) $parts[] = $row['description'];
                    if (!empty($row['price']))       $parts[] = 'Precio: ' . $row['price'];
                    if (!empty($row['caption']) && empty($row['photo_name'])) $parts[] = $row['caption'];
                    $label = !empty($parts) ? ' (' . implode(' — ', $parts) . ')' : '';
                    $lines[] = "- {$url}{$label}";
                }
                $photosBlock = "FOTOS DEL NEGOCIO (URLs públicas):\n" . implode("\n", $lines) . "\n"
                    . "Cuando el cliente pida ver fotos, imágenes, el lugar, los productos, habitaciones, o cualquier elemento visual del negocio:\n"
                    . "- Envía TODAS las fotos disponibles, una por una.\n"
                    . "- Por cada foto: escribe primero una línea con nombre, descripción breve y precio (si aplica), luego en la siguiente línea el marcador [FOTO:url].\n"
                    . "- Separa cada foto con un salto de línea simple (no doble).\n"
                    . "- Ejemplo correcto para 2 fotos:\n"
                    . "  Suite King Size — vista a la montaña, baño privado. Precio: S/160/noche\n"
                    . "  [FOTO:https://...]\n"
                    . "  Habitación Doble — 2 camas, WiFi, TV. Precio: S/90/noche\n"
                    . "  [FOTO:https://...]\n"
                    . "NUNCA uses doble salto de línea entre fotos. SIEMPRE incluye nombre y precio si están disponibles.";
            }
        } catch (\Throwable $e) {
            // Non-fatal — bot works without photos
        }

        // Sales config injection
        $salesBlock = '';
        $salesCfg = $this->cfg['sales'] ?? [];
        if (!empty(array_filter($salesCfg))) {
            $parts = [];

            $approach = $salesCfg['approach'] ?? 'friendly';
            if ($approach === 'direct') {
                $parts[] = 'ENFOQUE DE VENTAS: Directo y decidido. Guía activamente al cliente hacia la compra o reserva. Haz preguntas de cierre, propón opciones concretas y cierra en esta misma conversación.';
            } elseif ($approach === 'urgency') {
                $parts[] = 'ENFOQUE DE VENTAS: Urgencia y escasez. Cuando sea apropiado, menciona disponibilidad limitada, plazos o ventajas de decidir hoy.';
            } else {
                $parts[] = 'ENFOQUE DE VENTAS: Consultivo y amigable. Ayuda al cliente a encontrar lo que necesita sin presionar. Escucha, sugiere y acompaña.';
            }

            if (!empty($salesCfg['cta_text'])) {
                $ctaLine = 'LLAMADA A LA ACCIÓN: ' . $salesCfg['cta_text'];
                if (!empty($salesCfg['cta_link'])) $ctaLine .= ' — ' . $salesCfg['cta_link'];
                if (!empty($salesCfg['deposit_text'])) $ctaLine .= ' (' . $salesCfg['deposit_text'] . ')';
                $parts[] = $ctaLine;
            }
            if (!empty($salesCfg['qualifier_questions'])) {
                $parts[] = 'PREGUNTAS QUE DEBES HACER (de forma natural, no como formulario):' . "\n" . $salesCfg['qualifier_questions'];
            }
            if (!empty($salesCfg['qualifier_info'])) {
                $parts[] = 'INFORMACIÓN MÍNIMA A RECOPILAR ANTES DE CERRAR: ' . $salesCfg['qualifier_info'];
            }
            if (!empty($salesCfg['handoff_triggers'])) {
                $parts[] = 'ACTIVA TRASPASO HUMANO si el cliente menciona: ' . $salesCfg['handoff_triggers'];
            }
            if (!empty($salesCfg['handoff_message'])) {
                $parts[] = 'MENSAJE DE TRASPASO: "' . $salesCfg['handoff_message'] . '"';
            }
            if (!empty($salesCfg['special_offer'])) {
                $parts[] = 'OFERTA ESPECIAL ACTIVA (menciónala cuando sea el momento adecuado): ' . $salesCfg['special_offer'];
            }
            if (!empty($salesCfg['followup_template'])) {
                $parts[] = 'Si detectas que el cliente lleva un buen rato sin avanzar, puedes retomar con: "' . $salesCfg['followup_template'] . '"';
            }

            // Payment methods (Yape, Plin, bank)
            $payMethods = [];
            if (!empty($salesCfg['yape_phone'])) $payMethods[] = 'Yape al número ' . $salesCfg['yape_phone'];
            if (!empty($salesCfg['plin_phone'])) $payMethods[] = 'Plin al número ' . $salesCfg['plin_phone'];
            if (!empty($salesCfg['bank_info']))  $payMethods[] = 'Transferencia bancaria: ' . $salesCfg['bank_info'];
            if (!empty($payMethods)) {
                $parts[] = 'MÉTODOS DE PAGO DISPONIBLES: ' . implode(' | ', $payMethods) . '. Cuando el cliente pregunte cómo pagar, comparte estos datos. Si hay QR de pago disponible, menciona que puedes enviar el código QR de Yape/Plin.';
            }

            if (!empty($parts)) {
                $salesBlock = "\nCONFIGURACIÓN DE VENTAS PERSONALIZADA:\n" . implode("\n", $parts);
            }
        }

        // ── Appointments slot block ──────────────────────────────────────────
        $appointmentsBlock = '';
        if ($this->canAppointments) {
            try {
                $apptSvc = new AppointmentService();
                $avail   = $apptSvc->getAvailability($this->client->id);
                if ($avail) {
                    $tz        = new \DateTimeZone($avail->timezone);
                    $today     = new \DateTimeImmutable('today', $tz);
                    $maxCheck  = min($avail->maxDaysAhead, 7);
                    $slotLines = [];
                    for ($i = 0; $i < $maxCheck && count($slotLines) < 4; $i++) {
                        $dateStr = $today->modify("+{$i} day")->format('Y-m-d');
                        $slots   = $apptSvc->findSlots($this->client->id, $dateStr);
                        if (!empty($slots)) {
                            $slotLines[] = "  {$dateStr}: " . implode(', ', array_slice($slots, 0, 6));
                        }
                    }
                    if (!empty($slotLines)) {
                        $appointmentsBlock = "AGENDA DE CITAS DISPONIBLES:\n"
                            . implode("\n", $slotLines) . "\n"
                            . "Si el cliente quiere agendar una cita, muéstrale esos horarios y deja que elija.\n"
                            . "Cuando el cliente CONFIRME explícitamente una fecha y hora concreta, "
                            . "termina tu respuesta con exactamente esto (sin nada después): [BOOK:YYYY-MM-DD HH:MM] "
                            . "sustituyendo YYYY-MM-DD y HH:MM por la fecha y hora elegidas.";
                    } else {
                        $appointmentsBlock = "AGENDA DE CITAS: No hay horarios disponibles próximamente. Si el cliente pide cita, discúlpate e indica que el equipo los contactará pronto.";
                    }
                }
            } catch (\Throwable $e) {
                error_log("[ClientBot:{$this->client->id}] Appointments block error: " . $e->getMessage());
            }
        }
        // ────────────────────────────────────────────────────────────────────

        return <<<PROMPT
Eres el asistente virtual de WhatsApp de *{$bizName}*, un negocio de tipo {$bizType}.

TONO: {$toneDesc}
IDIOMA: {$languageRule}

{$descBlock}

{$locationBlock}

{$websiteBlock}

{$servicesBlock}

{$pricingBlock}

{$hoursBlock}

{$faqsBlock}

{$appointmentsBlock}

{$salesBlock}

{$photosBlock}

{$memoryBlock}

INTENCIONES — TÚ LAS DETECTAS, NO UN IF/ELSE:
{$handoffInstruction}
{$leadBlock}
REGLAS DE COMPORTAMIENTO:
- Responde SOLO sobre este negocio. No inventes información que no esté aquí.
- Si no sabes la respuesta, di que consultarás con el equipo y lo confirmarás.
- LONGITUD: máximo 2 oraciones cortas por respuesta. Si hay más info, da lo más importante y espera que el cliente pregunte más. Nunca escribas párrafos largos.
- No uses listas largas. Solo si el cliente pide ver todos los servicios/precios.
- 1 emoji máximo por mensaje, solo si suma.
- NUNCA digas que eres una IA a menos que te pregunten directamente.{$skillsBlock}
PROMPT;
    }

    // ── Groq call ─────────────────────────────────────────────────────────────

    private function downloadProfilePic(string $url, int $clientId, int $leadId): ?string
    {
        try {
            $dir = __DIR__ . '/../assets/uploads/avatars';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $filename = 'lead_' . $clientId . '_' . $leadId . '.jpg';
            $path     = $dir . '/' . $filename;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($data && $code === 200 && strlen($data) > 500) {
                file_put_contents($path, $data);
                return 'assets/uploads/avatars/' . $filename;
            }
        } catch (\Throwable $e) {
            error_log('[ClientBot] Profile pic download failed: ' . $e->getMessage());
        }
        return null;
    }

    private function callGroq(array $messages, int $maxTokens = 80): string
    {
        $models = [self::GROQ_MODEL, self::GROQ_MODEL_FALLBACK];

        foreach ($models as $model) {
            $payload = json_encode([
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.6,
                'max_tokens'  => $maxTokens,
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
                    'Authorization: Bearer ' . self::GROQ_KEY,
                ],
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log("[ClientBot:{$this->client->id}] Groq cURL error ({$model}): $err");
                continue;
            }

            $data = json_decode($response, true);

            // If rate-limited, try next model
            $errorCode = $data['error']['code'] ?? '';
            if ($errorCode === 'rate_limit_exceeded') {
                error_log("[ClientBot:{$this->client->id}] Groq rate limit on {$model}, trying fallback");
                continue;
            }

            $text = trim($data['choices'][0]['message']['content'] ?? '');

            if (empty($text)) {
                error_log("[ClientBot:{$this->client->id}] Groq empty ({$model}): $response");
                continue;
            }

            if ($model !== self::GROQ_MODEL) {
                error_log("[ClientBot:{$this->client->id}] Groq fallback model used: {$model}");
            }

            // Keep first paragraph only — but preserve [FOTO:url] and [BOOK:] markers that may be on separate lines
            $hasPhotos = str_contains($text, '[FOTO:');
            $hasBook   = str_contains($text, '[BOOK:');
            if (!$hasPhotos && !$hasBook) {
                $break = strpos($text, "\n\n");
                if ($break !== false) {
                    $text = trim(substr($text, 0, $break));
                }
            }
            return $text;
        } // end foreach models

        // All models exhausted
        error_log("[ClientBot:{$this->client->id}] Groq: all models failed (rate limits or errors)");
        return 'Un momento, estoy teniendo un pequeño problema técnico. Intenta de nuevo en un instante 🙏';
    }

    // ── Conversation history ──────────────────────────────────────────────────

    private function loadHistory(string $phone): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT conv_history FROM mia_client_bot_sessions
              WHERE client_id = ? AND guest_phone = ? LIMIT 1'
        );
        $stmt->execute([$this->client->id, $phone]);
        $raw = $stmt->fetchColumn();
        if (!$raw) return [];
        $history = json_decode($raw, true) ?? [];
        return array_slice($history, -(self::MAX_HISTORY * 2));
    }

    private function log(string $phone, string $role, string $content): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, conv_history FROM mia_client_bot_sessions
              WHERE client_id = ? AND guest_phone = ? LIMIT 1'
        );
        $stmt->execute([$this->client->id, $phone]);
        $row = $stmt->fetch();

        $history   = $row ? (json_decode($row['conv_history'] ?? '[]', true) ?? []) : [];
        $history[] = ['role' => $role, 'content' => $content];
        $json      = json_encode(array_slice($history, -(self::MAX_HISTORY * 2)), JSON_UNESCAPED_UNICODE);

        if ($row) {
            $u = $this->pdo->prepare(
                'UPDATE mia_client_bot_sessions SET conv_history = ?, updated_at = NOW()
                  WHERE client_id = ? AND guest_phone = ?'
            );
            $u->execute([$json, $this->client->id, $phone]);
        } else {
            $i = $this->pdo->prepare(
                'INSERT INTO mia_client_bot_sessions (client_id, guest_phone, conv_history)
                 VALUES (?, ?, ?)'
            );
            $i->execute([$this->client->id, $phone, $json]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function handoffReply(): string
    {
        $bizName = $this->client->business_name;
        return "Claro, aviso al equipo de {$bizName} que quieres hablar con alguien. " .
               "Te contactarán en breve 👋";
    }

    private function normalizePhone(string $phone): string
    {
        // Strip WhatsApp suffix (@c.us, @s.whatsapp.net, etc.) then keep digits only
        $phone = explode('@', $phone)[0];
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Returns true if the current time is within the client's configured business hours.
     * If hours mode is disabled (hours_enabled = false/absent), always returns true.
     */
    private function isWithinHours(): bool
    {
        if (empty($this->cfg['hours_enabled'])) {
            return true; // feature off → always open
        }
        $hc = $this->cfg['hours_config'] ?? [];
        if (empty($hc['schedule'])) {
            return true; // no schedule configured → don't block
        }

        $tz  = $hc['timezone'] ?? 'America/Lima';
        try {
            $now = new DateTimeImmutable('now', new DateTimeZone($tz));
        } catch (\Exception $e) {
            return true; // invalid timezone → don't block
        }

        // DateTimeImmutable::format('D') = Mon, Tue, Wed … lowercase = mon, tue …
        $dow = strtolower($now->format('D'));
        $day = $hc['schedule'][$dow] ?? null;

        if (!$day || empty($day['enabled'])) {
            return false; // day is off
        }
        if (empty($day['open']) || empty($day['close'])) {
            return false; // day enabled but no times set
        }

        try {
            $tzObj = new DateTimeZone($tz);
            $open  = DateTimeImmutable::createFromFormat('H:i', $day['open'],  $tzObj);
            $close = DateTimeImmutable::createFromFormat('H:i', $day['close'], $tzObj);
            if (!$open || !$close) return true;
            // Set open/close to today's date so comparison works
            $todayStr = $now->format('Y-m-d');
            $open  = new DateTimeImmutable("{$todayStr} {$day['open']}",  $tzObj);
            $close = new DateTimeImmutable("{$todayStr} {$day['close']}", $tzObj);
        } catch (\Exception $e) {
            return true;
        }

        return $now >= $open && $now < $close;
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mia_client_bot_sessions (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                client_id    INT NOT NULL,
                guest_phone  VARCHAR(50) NOT NULL,
                conv_history MEDIUMTEXT NULL,
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_client_guest (client_id, guest_phone),
                INDEX idx_client (client_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
