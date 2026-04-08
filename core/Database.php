<?php
/**
 * Database Handler
 * 
 * @package CurlyCMS\Core
 */

declare(strict_types=1);

namespace CurlyCMS\Core;

use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;
    private array $config;
    private int $queryCount = 0;
    private array $queryLog = [];
    private bool $logging = false;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        $driver = $this->config['driver'] ?? getenv('DB_CONNECTION') ?? 'sqlite';
        
        try {
            switch ($driver) {
                case 'sqlite':
                    $dbPath = $this->config['path'] ?? CMS_STORAGE . '/database/cms.db';
                    $this->connection = new PDO("sqlite:{$dbPath}");
                    break;
                    
                case 'mysql':
                    $host = $this->config['host'] ?? getenv('DB_HOST') ?? 'localhost';
                    $port = $this->config['port'] ?? getenv('DB_PORT') ?? '3306';
                    $dbname = $this->config['database'] ?? getenv('DB_DATABASE') ?? 'curlycms';
                    $charset = $this->config['charset'] ?? 'utf8mb4';
                    
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    $username = $this->config['username'] ?? getenv('DB_USERNAME') ?? 'root';
                    $password = $this->config['password'] ?? getenv('DB_PASSWORD') ?? '';
                    
                    $this->connection = new PDO($dsn, $username, $password);
                    break;
                    
                case 'pgsql':
                    $host = $this->config['host'] ?? getenv('DB_HOST') ?? 'localhost';
                    $port = $this->config['port'] ?? getenv('DB_PORT') ?? '5432';
                    $dbname = $this->config['database'] ?? getenv('DB_DATABASE') ?? 'curlycms';
                    
                    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                    $username = $this->config['username'] ?? getenv('DB_USERNAME') ?? 'postgres';
                    $password = $this->config['password'] ?? getenv('DB_PASSWORD') ?? '';
                    
                    $this->connection = new PDO($dsn, $username, $password);
                    break;
                    
                default:
                    throw new \RuntimeException("Unsupported database driver: {$driver}");
            }
            
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $this->queryCount++;
        
        if ($this->logging) {
            $start = microtime(true);
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        
        if ($this->logging) {
            $this->queryLog[] = [
                'sql' => $sql,
                'params' => $params,
                'time' => microtime(true) - $start
            ];
        }
        
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return (int) $this->connection->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "{$key} = :where_{$key}";
            $data["where_{$key}"] = $value;
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        $stmt = $this->query($sql, $data);
        
        return $stmt->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        $params = [];
        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        $whereClause = implode(' AND ', $whereParts);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getLastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function enableQueryLog(): void
    {
        $this->logging = true;
    }

    public function disableQueryLog(): void
    {
        $this->logging = false;
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function tableExists(string $table): bool
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        switch ($driver) {
            case 'sqlite':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
                break;
            case 'mysql':
                $sql = "SHOW TABLES LIKE ?";
                break;
            case 'pgsql':
                $sql = "SELECT to_regclass(?)";
                break;
            default:
                return false;
        }
        
        $result = $this->fetch($sql, [$table]);
        return $result !== null;
    }

    public function createTable(string $table, array $columns, array $options = []): void
    {
        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        $columnDefs = [];
        
        foreach ($columns as $name => $definition) {
            $columnDefs[] = "{$name} {$definition}";
        }
        
        if (!empty($options['primary'])) {
            $primary = is_array($options['primary']) ? implode(', ', $options['primary']) : $options['primary'];
            $columnDefs[] = "PRIMARY KEY ({$primary})";
        }
        
        $columnSql = implode(', ', $columnDefs);
        
        $sql = "CREATE TABLE {$table} ({$columnSql})";
        
        if ($driver === 'mysql') {
            if (!empty($options['engine'])) {
                $sql .= " ENGINE={$options['engine']}";
            }
            if (!empty($options['charset'])) {
                $sql .= " DEFAULT CHARSET={$options['charset']}";
            }
        }
        
        $this->query($sql);
    }
}
