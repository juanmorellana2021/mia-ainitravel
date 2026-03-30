<?php
/**
 * mia/services/ClientDocService.php
 *
 * Manages document uploads for each Mia client.
 * Clients can store up to MAX_DOCS files, fully isolated by client_id.
 *
 * Supported types: PDF, DOCX, XLSX, PPTX
 * Files are stored at: assets/uploads/docs/{clientId}/{filename}
 * Max file size: 20 MB per document
 */

declare(strict_types=1);

class ClientDocService
{
    private const MAX_DOCS   = 50;
    private const MAX_BYTES  = 20 * 1024 * 1024; // 20 MB

    // Real MIME types (validated via finfo, NOT user-supplied Content-Type)
    private const ALLOWED_MIME = [
        'application/pdf'                                                                => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'       => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'             => 'xlsx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'     => 'pptx',
        // Some servers detect these legacy MIME types for Office files
        'application/msword'                                                             => 'docx',
        'application/vnd.ms-excel'                                                      => 'xlsx',
        'application/vnd.ms-powerpoint'                                                 => 'pptx',
        // zip-based detection (OOXML files are ZIP archives)
        'application/zip'                                                                => null, // handled specially below
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::get();
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public function list(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, filename, original_name, doc_name, description, file_type, file_size, sort_order, created_at
             FROM mia_client_docs
             WHERE client_id = ?
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listWithUrls(int $clientId): array
    {
        $docs = $this->list($clientId);
        foreach ($docs as &$d) {
            $d['url']             = $this->publicUrl($clientId, $d['filename']);
            $d['file_size_label'] = self::formatSize((int)$d['file_size']);
        }
        return $docs;
    }

    /**
     * Upload a document for a client.
     * $file = one entry from $_FILES (e.g. $_FILES['doc']).
     * Returns ['ok' => true, 'doc' => [...]] or ['error' => '...'].
     */
    public function upload(int $clientId, array $file): array
    {
        // Count existing docs
        $s = $this->pdo->prepare('SELECT COUNT(*) FROM mia_client_docs WHERE client_id = ?');
        $s->execute([$clientId]);
        if ((int)$s->fetchColumn() >= self::MAX_DOCS) {
            return ['error' => 'Límite de ' . self::MAX_DOCS . ' documentos alcanzado.'];
        }

        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Error al subir el archivo.'];
        }

        if ($file['size'] > self::MAX_BYTES) {
            return ['error' => 'El archivo no puede superar los 20 MB.'];
        }

        // Validate MIME type via finfo
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $fileType = self::ALLOWED_MIME[$mimeType] ?? null;

        // OOXML files (.docx/.xlsx/.pptx) are ZIP archives — detect by extension
        if ($fileType === null) {
            $origExt  = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
            $zipTypes = ['docx' => 'docx', 'xlsx' => 'xlsx', 'pptx' => 'pptx'];
            if (($mimeType === 'application/zip' || $mimeType === 'application/octet-stream')
                && isset($zipTypes[$origExt])) {
                $fileType = $zipTypes[$origExt];
            }
        }

        if ($fileType === null) {
            return ['error' => 'Solo se permiten archivos PDF, Word (DOCX), Excel (XLSX) o PowerPoint (PPTX).'];
        }

        // Build safe filename: clientId_uniqid.ext — zero user input in filename
        $filename     = $clientId . '_' . uniqid('', true) . '.' . $fileType;
        $originalName = substr(basename($file['name'] ?? 'archivo'), 0, 255);
        $dir          = $this->uploadDir($clientId);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return ['error' => 'No se pudo crear el directorio de subida.'];
        }

        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['error' => 'No se pudo guardar el archivo.'];
        }

        $ins = $this->pdo->prepare(
            'INSERT INTO mia_client_docs (client_id, filename, original_name, doc_name, description, file_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $clientId,
            $filename,
            $originalName,
            pathinfo($originalName, PATHINFO_FILENAME), // default doc_name = filename without ext
            '',
            $fileType,
            (int)$file['size'],
        ]);
        $id = (int)$this->pdo->lastInsertId();

        return [
            'ok'  => true,
            'doc' => [
                'id'           => $id,
                'filename'     => $filename,
                'original_name'=> $originalName,
                'doc_name'     => pathinfo($originalName, PATHINFO_FILENAME),
                'description'  => '',
                'file_type'    => $fileType,
                'file_size'    => (int)$file['size'],
                'url'          => $this->publicUrl($clientId, $filename),
            ],
        ];
    }

    /**
     * Delete a document — verifies ownership before deleting.
     */
    public function delete(int $docId, int $clientId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT filename FROM mia_client_docs WHERE id = ? AND client_id = ?'
        );
        $stmt->execute([$docId, $clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;

        $path = $this->uploadDir($clientId) . '/' . $row['filename'];
        if (file_exists($path)) @unlink($path);

        $this->pdo->prepare('DELETE FROM mia_client_docs WHERE id = ? AND client_id = ?')
                  ->execute([$docId, $clientId]);

        return true;
    }

    /**
     * Update doc display name and description.
     */
    public function updateDoc(int $docId, int $clientId, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mia_client_docs
             SET doc_name = ?, description = ?
             WHERE id = ? AND client_id = ?'
        );
        return $stmt->execute([
            substr(trim($data['doc_name']    ?? ''), 0, 150),
            substr(trim($data['description'] ?? ''), 0, 500),
            $docId,
            $clientId,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function uploadDir(int $clientId): string
    {
        return __DIR__ . '/../assets/uploads/docs/' . $clientId;
    }

    public function publicUrl(int $clientId, string $filename): string
    {
        return App::URL . '/assets/uploads/docs/' . $clientId . '/' . rawurlencode($filename);
    }

    public function maxDocs(): int { return self::MAX_DOCS; }

    public static function fileIcon(string $fileType): string
    {
        return match($fileType) {
            'pdf'  => 'bi-file-earmark-pdf',
            'docx' => 'bi-file-earmark-word',
            'xlsx' => 'bi-file-earmark-excel',
            'pptx' => 'bi-file-earmark-ppt',
            default => 'bi-file-earmark',
        };
    }

    public static function iconColor(string $fileType): string
    {
        return match($fileType) {
            'pdf'  => '#e53e3e',
            'docx' => '#2b6cb0',
            'xlsx' => '#276749',
            'pptx' => '#c05621',
            default => '#718096',
        };
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}
