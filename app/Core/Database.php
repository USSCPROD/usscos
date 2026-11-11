<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $connection = null;
    private static int  $queryCount = 0;

    public static function init(): void
    {
        if (self::$connection !== null) {
            return;
        }

        $cfg = Config::get('database.connections.mysql');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );

        try {
            self::$connection = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
        } catch (PDOException $e) {
            Logger::critical('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed. Please check your configuration.');
        }

        self::alignTimezone();
    }

    /**
     * Put MySQL on the same clock as PHP.
     *
     * The server runs UTC, so NOW() and CURDATE() were five hours ahead of Alpharetta:
     * an invoice tipped into overdue in the early evening of the day before, and
     * anything stamped by the database disagreed with everything stamped by PHP.
     *
     * Set as a numeric offset, not a name. MySQL's named-timezone tables are not
     * loaded on this server, so 'America/New_York' is rejected outright. The offset is
     * worked out in PHP for the moment the connection opens, which means it is the
     * right one either side of a daylight-saving change — connections here live for a
     * single request, so there is no chance of one going stale across the boundary.
     *
     * Failure is logged and swallowed. A wrong clock is a bug; refusing to serve the
     * application because the clock could not be set would be a worse one.
     */
    private static function alignTimezone(): void
    {
        $zone = (string)(Config::get('app.timezone') ?: 'America/New_York');

        try {
            $offset = (new \DateTime('now', new \DateTimeZone($zone)))->format('P');

            self::$connection->exec("SET time_zone = '{$offset}'");
        } catch (\Throwable $e) {
            Logger::error('Could not set the database timezone: ' . $e->getMessage());
        }
    }

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::init();
        }
        return self::$connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        self::$queryCount++;

        try {
            $stmt = self::connection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error('Query failed: ' . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    public static function select(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function selectOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::connection()->lastInsertId();
    }

    public static function update(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function delete(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function statement(string $sql, array $params = []): bool
    {
        return self::query($sql, $params)->rowCount() >= 0;
    }

    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollback(): bool
    {
        return self::connection()->rollBack();
    }

    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();

        try {
            $result = $callback(self::connection());
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }

    public static function lastInsertId(): string
    {
        return self::connection()->lastInsertId();
    }

    public static function getQueryCount(): int
    {
        return self::$queryCount;
    }

    public static function escape(string $value): string
    {
        return self::connection()->quote($value);
    }
}
