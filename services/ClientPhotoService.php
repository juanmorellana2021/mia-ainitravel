<?php
/**
 * mia/services/ClientPhotoService.php
 *
 * Manages business photo uploads for each Mia client.
 * Each client can store up to MAX_PHOTOS images, fully isolated by client_id.
 *
 * Files are stored at:  assets/uploads/photos/{clientId}/{filename}
 * Allowed types:        JPEG, PNG, WebP
 * Max file size:        5 MB per photo
 */

declare(strict_types=1);

class ClientPhotoService
{
    private const MAX_PHOTOS   = 30;
    private const MAX_BYTES    = 5 * 1024 * 1024; // 5 MB
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_EXT  = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Returns all photos for a client, ordered by sort_order then id.
     */
    public function list(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, filename, caption, photo_name, description, price, sort_order, created_at
             FROM mia_client_photos
             WHERE client_id = ?
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Upload a new photo for a client.
     * $file = one entry from $_FILES (e.g. $_FILES['photo']).
     * Returns ['ok' => true, 'photo' => [...]] or ['error' => '...'].
     */
    public function upload(int $clientId, array $file, string $caption = ''): array
    {
        // Count existing photos
        $count = (int)$this->pdo->prepare(
            'SELECT COUNT(*) FROM mia_client_photos WHERE client_id = ?'
        )->execute([$clientId]) && ($stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM mia_client_photos WHERE client_id = ?'
        )) && $stmt->execute([$clientId]) ? $stmt->fetchColumn() : 0;

        // Re-query cleanly
        $s = $this->pdo->prepare('SELECT COUNT(*) FROM mia_client_photos WHERE client_id = ?');
        $s->execute([$clientId]);
        $count = (int)$s->fetchColumn();

        if ($count >= self::MAX_PHOTOS) {
            return ['error' => 'Límite de ' . self::MAX_PHOTOS . ' fotos alcanzado.'];
        }

        // Basic upload error check
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Error al subir el archivo.'];
        }

        // Size check
        if ($file['size'] > self::MAX_BYTES) {
            return ['error' => 'La imagen no puede superar los 5 MB.'];
        }

        // MIME validation via finfo (not trusting user-supplied type)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, self::ALLOWED_MIME, true)) {
            return ['error' => 'Solo se permiten imágenes JPEG, PNG o WebP.'];
        }

        // Derive extension from real mime type
        $ext = array_search($mimeType, self::ALLOWED_EXT, true);
        if ($ext === 'jpeg') $ext = 'jpg';

        // Build safe filename: clientId_uniqid.ext — no user input in filename
        $filename = $clientId . '_' . uniqid('', true) . '.' . $ext;
        $dir      = $this->uploadDir($clientId);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return ['error' => 'No se pudo crear el directorio de subida.'];
        }

        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['error' => 'No se pudo guardar la imagen.'];
        }

        // Persist record
        $caption = substr(trim($caption), 0, 200);
        $ins = $this->pdo->prepare(
            'INSERT INTO mia_client_photos (client_id, filename, caption, photo_name, description, price) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$clientId, $filename, $caption, '', '', '']);
        $id = (int)$this->pdo->lastInsertId();

        return [
            'ok'    => true,
            'photo' => [
                'id'       => $id,
                'filename' => $filename,
                'caption'  => $caption,
                'url'      => $this->publicUrl($clientId, $filename),
            ],
        ];
    }

    /**
     * Delete a photo — verifies ownership by client_id before deleting.
     * Returns true on success, false if photo not found / not owned.
     */
    public function delete(int $photoId, int $clientId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT filename FROM mia_client_photos WHERE id = ? AND client_id = ?'
        );
        $stmt->execute([$photoId, $clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;

        // Delete file
        $path = $this->uploadDir($clientId) . '/' . $row['filename'];
        if (file_exists($path)) @unlink($path);

        // Delete DB record
        $this->pdo->prepare('DELETE FROM mia_client_photos WHERE id = ? AND client_id = ?')
                  ->execute([$photoId, $clientId]);

        return true;
    }

    /**
     * Returns all photos with public URLs (for JSON responses).
     */
    public function listWithUrls(int $clientId): array
    {
        $photos = $this->list($clientId);
        foreach ($photos as &$p) {
            $p['url'] = $this->publicUrl($clientId, $p['filename']);
        }
        return $photos;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function uploadDir(int $clientId): string
    {
        return __DIR__ . '/../assets/uploads/photos/' . $clientId;
    }

    public function publicUrl(int $clientId, string $filename): string
    {
        return App::basePath() . '/assets/uploads/photos/' . $clientId . '/' . rawurlencode($filename);
    }

    public function maxPhotos(): int { return self::MAX_PHOTOS; }

    /**
     * Update photo attributes (name, description, price).
     * Returns true on success.
     */
    public function updatePhoto(int $photoId, int $clientId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mia_client_photos
             SET photo_name = ?, description = ?, price = ?, caption = ?
             WHERE id = ? AND client_id = ?'
        );
        return $stmt->execute([
            substr(trim($data['photo_name']  ?? ''), 0, 150),
            substr(trim($data['description'] ?? ''), 0, 500),
            substr(trim($data['price']       ?? ''), 0, 50),
            substr(trim($data['caption']     ?? ''), 0, 200),
            $photoId,
            $clientId,
        ]);
    }
}
