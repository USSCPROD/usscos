<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Repository;

class DocumentRepository extends Repository
{
    protected function getTable(): string
    {
        return 'documents';
    }

    public function getAllCategories(): array
    {
        return Database::select("
            SELECT * FROM document_categories
            WHERE is_active = 1
            ORDER BY sort_order ASC, name ASC
        ");
    }

    public function getCategoriesForAudiences(array $audiences): array
    {
        return Database::select("SELECT * FROM document_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    }

    public function getDocumentsForAudiences(array $audiences, string $search = ''): array
    {
        // Build FIND_IN_SET conditions for each audience the user can see
        $audienceClauses = array_map(fn($a) => "FIND_IN_SET(?, d.audience)", $audiences);
        $audienceWhere   = '(' . implode(' OR ', $audienceClauses) . ')';
        $params          = $audiences;

        $searchClause = '';
        if ($search !== '') {
            $searchClause = "AND (d.title LIKE ? OR d.description LIKE ?)";
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
        }

        return Database::select("
            SELECT d.*,
                   dc.name      AS category_name,
                   dc.audience  AS category_audience,
                   u.first_name AS uploader_first,
                   u.last_name  AS uploader_last
            FROM documents d
            JOIN document_categories dc ON dc.id = d.document_category_id
            JOIN users u ON u.id = d.uploaded_by
            WHERE d.is_active = 1
              AND $audienceWhere
              $searchClause
            ORDER BY dc.sort_order ASC, dc.name ASC, d.title ASC
        ", $params);
    }

    public function findById(int|string $id): array|false
    {
        return Database::selectOne("
            SELECT d.*, dc.name AS category_name, dc.audience AS category_audience
            FROM documents d
            JOIN document_categories dc ON dc.id = d.document_category_id
            WHERE d.id = ?
            LIMIT 1
        ", [$id]);
    }

    public function insert(array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            INSERT INTO documents
                (document_category_id, audience, title, original_filename, stored_filename,
                 file_size, mime_type, description, uploaded_by)
            VALUES
                (:category_id, :audience, :title, :original_filename, :stored_filename,
                 :file_size, :mime_type, :description, :uploaded_by)
        ");
        $stmt->execute([
            ':category_id'       => $data['document_category_id'],
            ':audience'          => $data['audience'],
            ':title'             => $data['title'],
            ':original_filename' => $data['original_filename'],
            ':stored_filename'   => $data['stored_filename'],
            ':file_size'         => $data['file_size'],
            ':mime_type'         => $data['mime_type'],
            ':description'       => $data['description'] ?: null,
            ':uploaded_by'       => $data['uploaded_by'],
        ]);
        return (int)Database::connection()->lastInsertId();
    }

    public function deactivate(int $id): void
    {
        Database::connection()->prepare("UPDATE documents SET is_active = 0 WHERE id = ?")->execute([$id]);
    }

    public function update(int|string $id, array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            UPDATE documents SET
                title                = :title,
                document_category_id = :category_id,
                audience             = :audience,
                description          = :description
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'          => $id,
            ':title'       => $data['title'],
            ':category_id' => $data['document_category_id'],
            ':audience'    => $data['audience'],
            ':description' => $data['description'] ?: null,
        ]);
        return $stmt->rowCount();
    }
}
