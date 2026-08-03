<?php

declare(strict_types=1);

namespace App\Core;

abstract class Repository
{
    protected string $table      = '';
    protected string $primaryKey = 'id';
    protected bool   $softDeletes = false;

    abstract protected function getTable(): string;

    // -------------------------------------------------------------------------
    // Basic CRUD
    // -------------------------------------------------------------------------

    public function findById(int|string $id): array|false
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$this->primaryKey} = ?";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return Database::selectOne($sql . ' LIMIT 1', [$id]);
    }

    public function findByIdOrFail(int|string $id): array
    {
        $record = $this->findById($id);
        if (!$record) {
            throw new \RuntimeException("Record not found in [{$this->getTable()}] with id [{$id}].");
        }
        return $record;
    }

    public function findBy(string $column, mixed $value): array|false
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$column} = ?";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return Database::selectOne($sql . ' LIMIT 1', [$value]);
    }

    public function findAllBy(string $column, mixed $value, string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$column} = ?";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= " ORDER BY {$orderBy} {$direction}";
        return Database::select($sql, [$value]);
    }

    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM {$this->getTable()}";
        if ($this->softDeletes) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        return Database::select($sql . " ORDER BY {$orderBy} {$direction}");
    }

    public function create(array $data): string
    {
        $data = $this->withTimestamps($data, 'create');

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->getTable()} ({$columns}) VALUES ({$placeholders})";
        return Database::insert($sql, array_values($data));
    }

    public function update(int|string $id, array $data): int
    {
        $data = $this->withTimestamps($data, 'update');

        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$this->getTable()} SET {$set} WHERE {$this->primaryKey} = ?";

        return Database::update($sql, [...array_values($data), $id]);
    }

    public function delete(int|string $id): int
    {
        if ($this->softDeletes) {
            return Database::update(
                "UPDATE {$this->getTable()} SET deleted_at = NOW() WHERE {$this->primaryKey} = ?",
                [$id]
            );
        }

        return Database::delete(
            "DELETE FROM {$this->getTable()} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function forceDelete(int|string $id): int
    {
        return Database::delete(
            "DELETE FROM {$this->getTable()} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function restore(int|string $id): int
    {
        return Database::update(
            "UPDATE {$this->getTable()} SET deleted_at = NULL WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function count(array $where = []): int
    {
        [$conditions, $params] = $this->buildWhere($where);
        $sql = "SELECT COUNT(*) as total FROM {$this->getTable()}" . $conditions;
        $row = Database::selectOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    public function exists(int|string $id): bool
    {
        $sql = "SELECT 1 FROM {$this->getTable()} WHERE {$this->primaryKey} = ?";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return (bool) Database::selectOne($sql . ' LIMIT 1', [$id]);
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------

    public function paginate(int $page = 1, int $perPage = 20, array $where = [], string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $offset    = ($page - 1) * $perPage;
        $total     = $this->count($where);

        [$conditions, $params] = $this->buildWhere($where);

        $sql  = "SELECT * FROM {$this->getTable()}" . $conditions;
        $sql .= " ORDER BY {$orderBy} {$direction} LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data'         => Database::select($sql, $params),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    // -------------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------------

    public function search(string $term, array $columns, int $limit = 50): array
    {
        $conditions = implode(' OR ', array_map(fn($col) => "{$col} LIKE ?", $columns));
        $params     = array_fill(0, count($columns), "%{$term}%");

        $sql = "SELECT * FROM {$this->getTable()} WHERE ({$conditions})";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= " LIMIT {$limit}";

        return Database::select($sql, $params);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function buildWhere(array $where): array
    {
        if (empty($where)) {
            $suffix = $this->softDeletes ? ' WHERE deleted_at IS NULL' : '';
            return [$suffix, []];
        }

        $conditions = [];
        $params     = [];

        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $placeholders  = implode(', ', array_fill(0, count($value), '?'));
                $conditions[]  = "{$column} IN ({$placeholders})";
                $params        = array_merge($params, $value);
            } else {
                $conditions[] = "{$column} = ?";
                $params[]     = $value;
            }
        }

        if ($this->softDeletes) {
            $conditions[] = 'deleted_at IS NULL';
        }

        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function withTimestamps(array $data, string $operation): array
    {
        $now = date('Y-m-d H:i:s');

        if ($operation === 'create') {
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        } elseif ($operation === 'update') {
            $data['updated_at'] = $now;
        }

        return $data;
    }
}
