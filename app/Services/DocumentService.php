<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\DocumentRepository;

class DocumentService
{
    private DocumentRepository $repo;

    private const ROLE_AUDIENCES = [
        'owner'       => ['internal', 'reps', 'distributors', 'customers'],
        'admin'       => ['internal', 'reps', 'distributors', 'customers'],
        'manager'     => ['internal'],
        'staff'       => ['internal'],
        'readonly'    => ['internal'],
        'rep'         => ['reps'],
        'distributor' => ['distributors'],
        'customer'    => ['customers'],
    ];

    public const ALL_AUDIENCES = [
        'internal'     => 'Internal (Employees)',
        'reps'         => 'Reps',
        'distributors' => 'Distributors',
        'customers'    => 'Customers',
    ];

    public function __construct()
    {
        $this->repo = new DocumentRepository();
    }

    public function audiencesForRole(string $role): array
    {
        return self::ROLE_AUDIENCES[$role] ?? ['internal'];
    }

    public function canUpload(string $role): bool
    {
        return in_array($role, ['owner', 'admin']);
    }

    public function index(string $role, string $search = ''): array
    {
        $audiences     = $this->audiencesForRole($role);
        $documents     = $this->repo->getDocumentsForAudiences($audiences, $search);
        $allCategories = $this->repo->getAllCategories();
        $canUpload     = $this->canUpload($role);
        $allAudiences  = self::ALL_AUDIENCES;

        $grouped = [];
        foreach ($documents as $doc) {
            $grouped[$doc['category_name']][] = $doc;
        }

        return compact('grouped', 'allCategories', 'canUpload', 'search', 'allAudiences');
    }

    public function getForEdit(int $id): array
    {
        $doc = $this->repo->findById($id);
        if (!$doc) {
            throw new \RuntimeException('Document not found.');
        }
        $allCategories = $this->repo->getAllCategories();
        $allAudiences  = self::ALL_AUDIENCES;
        return compact('doc', 'allCategories', 'allAudiences');
    }

    public function store(array $post, array $file, int $userId): int
    {
        if (empty($_FILES)) {
            $postMax = ini_get('post_max_size');
            throw new \RuntimeException("No file data received. File may exceed server limit ({$postMax}).");
        }

        if (empty($file['name'])) {
            throw new \RuntimeException('No file was selected.');
        }

        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp directory missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
        ];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($uploadErrors[$file['error']] ?? 'Upload error ' . $file['error']);
        }

        $allowedMime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'text/plain', 'text/csv',
        ];

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowedMime)) {
            throw new \RuntimeException('File type not allowed.');
        }

        $ext            = pathinfo($file['name'], PATHINFO_EXTENSION);
        $storedFilename = uniqid('doc_', true) . '.' . strtolower($ext);
        $uploadDir      = BASE_PATH . '/uploads/documents/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new \RuntimeException('Could not create upload directory.');
            }
        }

        if (!is_writable($uploadDir)) {
            throw new \RuntimeException('Upload directory is not writable.');
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedFilename)) {
            throw new \RuntimeException('Could not save file. Check folder permissions.');
        }

        return $this->repo->insert([
            'document_category_id' => (int)$post['document_category_id'],
            'audience'             => $this->parseAudience($post['audience'] ?? []),
            'title'                => trim($post['title']),
            'original_filename'    => $file['name'],
            'stored_filename'      => $storedFilename,
            'file_size'            => $file['size'],
            'mime_type'            => $mime,
            'description'          => trim($post['description'] ?? ''),
            'uploaded_by'          => $userId,
        ]);
    }

    public function update(int $id, array $post, string $role): void
    {
        if (!$this->canUpload($role)) {
            throw new \RuntimeException('Not authorized.');
        }
        $doc = $this->repo->findById($id);
        if (!$doc) {
            throw new \RuntimeException('Document not found.');
        }
        $this->repo->update($id, [
            'title'                => trim($post['title']),
            'document_category_id' => (int)$post['document_category_id'],
            'audience'             => $this->parseAudience($post['audience'] ?? []),
            'description'          => trim($post['description'] ?? ''),
        ]);
    }

    public function delete(int $id, string $role): void
    {
        if (!$this->canUpload($role)) {
            throw new \RuntimeException('Not authorized.');
        }
        if (!$this->repo->findById($id)) {
            throw new \RuntimeException('Document not found.');
        }
        $this->repo->deactivate($id);
    }

    public function getForDownload(int $id, string $role): array
    {
        $doc = $this->repo->findById($id);
        if (!$doc) {
            throw new \RuntimeException('Document not found.');
        }
        $audiences   = $this->audiencesForRole($role);
        $docAudiences = explode(',', $doc['audience']);
        if (!array_intersect($audiences, $docAudiences)) {
            throw new \RuntimeException('Not authorized.');
        }
        return $doc;
    }

    private function parseAudience(array|string $input): string
    {
        $valid = array_keys(self::ALL_AUDIENCES);
        if (is_string($input)) {
            $input = [$input];
        }
        $filtered = array_filter($input, fn($a) => in_array($a, $valid));
        return implode(',', $filtered ?: ['internal']);
    }
}
