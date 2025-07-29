<?php
namespace Mgx\Slimdb;

/**
 * Connect sınıfı
 * Bu sınıf, veritabanı bağlantısını yönetir.
 * PDO kullanarak veritabanına bağlanır ve sorguları çalıştırır.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan -Mustafa Şentürk-
 */

use PDO;
use PDOException;




class Connect {
    private static $instance = null;
    private $pdo;
    private $connectionParams = [];

   /* Singleton deseni ile bağlantıyı koruma yapma */



   private function __construct(array $config)
   {
       $this->connectionParams = $this->validateConfig($config);

       $this->connect();
   }

    
   
   public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    public function getDatabaseName(): string
    {
        return $this->connectionParams['dbname'];
    }
        
    
    
    private function validateConfig(array $config): array
    {
        $required = ['host', 'dbname', 'username', 'password'];
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("$key ayarı eksik!");
            }
        }
        return $config;
    }
    /**
     * Veritabanı bağlantısını döndürür.
     *
     * @return PDO
     */
    private function connect(): void
    {
        $dsn = "mysql:host={$this->connectionParams['host']};" .
               "dbname={$this->connectionParams['dbname']};" .
               "charset=" . ($this->connectionParams['charset'] ?? 'utf8') . ";";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->connectionParams['username'],
                $this->connectionParams['password'],
                $options
            );
        } catch (PDOException $e) {
            throw new \RuntimeException("Bağlantı hatası: " . $e->getMessage());
        }
    }

   
   
   
    public function getPdo(): PDO
    {
        return $this->pdo;
    }


    // Doğrudan sorgu çalıştırma
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }


   // Transaction işlemleri
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function rollBack(): bool
    {
        if ($this->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return false;
    }

    // Son eklenen ID
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
  
}