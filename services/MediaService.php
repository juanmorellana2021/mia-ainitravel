<?php
/**
 * mia/services/MediaService.php
 *
 * Converts WhatsApp media to text so the AI can process it normally.
 *
 *   transcribeAudio() — Groq Whisper (whisper-large-v3-turbo)
 *                       Accepts PTT voice notes and audio messages.
 *
 *   describeImage()   — Groq Vision (llama-4-scout)
 *                       Returns a one-sentence description of the image.
 *                       If the client also sent a caption it is included.
 */

declare(strict_types=1);

class MediaService
{
    private const GROQ_KEY      = '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';
    private const WHISPER_MODEL = 'whisper-large-v3-turbo';
    private const VISION_MODEL  = 'meta-llama/llama-4-scout-17b-16e-instruct';

    // ── Audio / Voice notes ───────────────────────────────────────────────────

    /**
     * Transcribe a base64-encoded audio clip via Groq Whisper.
     * Returns the transcript, or empty string on failure.
     */
    public function transcribeAudio(string $base64, string $mime): string
    {
        $ext     = $this->mimeToExt($mime);
        $tmpFile = sys_get_temp_dir() . '/' . uniqid('mia_audio_') . '.' . $ext;

        try {
            $bytes = base64_decode($base64, strict: false);
            if (!$bytes) {
                error_log('[MediaService] transcribeAudio: base64_decode returned empty');
                return '';
            }
            file_put_contents($tmpFile, $bytes);

            $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . self::GROQ_KEY,
                ],
                CURLOPT_POSTFIELDS     => [
                    'file'            => new CURLFile($tmpFile, $mime, 'audio.' . $ext),
                    'model'           => self::WHISPER_MODEL,
                    'response_format' => 'text',
                    'language'        => 'es',
                ],
            ]);

            $response = curl_exec($ch);
            $err      = curl_error($ch);
            $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                error_log("[MediaService] Whisper curl error: {$err}");
                return '';
            }
            if ($code !== 200) {
                error_log("[MediaService] Whisper HTTP {$code}: " . substr((string)$response, 0, 200));
                return '';
            }

            // Groq returns plain text when response_format=text
            return trim((string) $response);

        } finally {
            if (file_exists($tmpFile)) @unlink($tmpFile);
        }
    }

    // ── Images ────────────────────────────────────────────────────────────────

    /**
     * Describe an image via Groq Vision.
     * Returns a one-sentence description in Spanish, or the caption if vision fails.
     */
    public function describeImage(string $base64, string $mime, string $caption = ''): string
    {
        $dataUrl     = "data:{$mime};base64,{$base64}";
        $captionNote = $caption ? " El usuario también escribió: \"{$caption}\"." : '';
        $prompt      = "Describe en UNA frase breve qué hay en esta imagen.{$captionNote} Responde en español.";

        $payload = json_encode([
            'model'      => self::VISION_MODEL,
            'max_tokens' => 120,
            'messages'   => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ['type' => 'text',      'text'      => $prompt],
                ],
            ]],
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::GROQ_KEY,
            ],
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err || $code !== 200) {
            error_log("[MediaService] Vision error (HTTP {$code}): {$err}");
            return $caption; // fall back to caption alone
        }

        $data = json_decode((string) $response, true);
        $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
        return $text ?: $caption;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mimeToExt(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'ogg')  => 'ogg',
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'mp4')  => 'mp4',
            str_contains($mime, 'mpeg') => 'mp3',
            str_contains($mime, 'wav')  => 'wav',
            str_contains($mime, 'flac') => 'flac',
            str_contains($mime, 'opus') => 'ogg',
            default                     => 'ogg',
        };
    }
}
