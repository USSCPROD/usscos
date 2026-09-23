<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Storing a file uploaded against a job.
 *
 * Shared by artwork and job documents rather than copied, because this is the security
 * boundary for uploads: the stored name is generated here, the extension whitelist is
 * enforced here, and nothing else decides where the bytes land. Two copies of that logic
 * would drift, and the copy that drifts is the one with the hole in it.
 *
 * Files go under public/uploads/jobs/<sales order>/, where public/uploads/.htaccess
 * refuses to execute anything. The database stores the web path, never the filesystem
 * path.
 */
class JobFileStore
{
    /**
     * Validate and store one uploaded file.
     *
     * @param  ?array   $file       one entry from $_FILES
     * @param  int      $salesOrderId
     * @param  string[] $allowedExt lowercase, without dots
     * @param  int      $maxBytes
     * @param  string   $fallbackName used when the original name has nothing usable in it
     * @return array{path:string,name:string,size:int,mime:?string}
     * @throws \RuntimeException with a message intended for the user
     */
    public function store(
        ?array $file,
        int $salesOrderId,
        array $allowedExt,
        int $maxBytes,
        string $fallbackName = 'file'
    ): array {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Choose a file to upload.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ((int)$file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is too large for the server to accept.',
                UPLOAD_ERR_PARTIAL                        => 'The upload was interrupted — please try again.',
                default                                   => 'Upload failed (error ' . (int)$file['error'] . ').',
            });
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Invalid upload.');
        }

        if ((int)$file['size'] > $maxBytes) {
            throw new \RuntimeException('File is too large. Maximum is ' . round($maxBytes / 1048576) . ' MB.');
        }

        $original = (string)$file['name'];
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            throw new \RuntimeException(
                'File type ".' . $ext . '" is not allowed. Accepted: ' . implode(', ', $allowedExt) . '.'
            );
        }

        $dir = BASE_PATH . '/public/uploads/jobs/' . $salesOrderId;

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the upload folder.');
        }

        $base = pathinfo($original, PATHINFO_FILENAME);
        $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $base) ?? ''), '-');
        $slug = substr($slug !== '' ? $slug : $fallbackName, 0, 60);

        $filename = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }

        @chmod($dir . '/' . $filename, 0644);

        return [
            'path' => '/uploads/jobs/' . $salesOrderId . '/' . $filename,
            'name' => $original,
            'size' => (int)$file['size'],
            'mime' => $file['type'] ?? null,
        ];
    }
}
