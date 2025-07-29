<?php
namespace Mgx\Slimdb;


/**
 * Migration sınıfı
 * Bu sınıf, veritabanı migration işlemlerini yönetmek için kullanılır.
 * Tabloların oluşturulması, değiştirilmesi ve silinmesi gibi işlemleri içerir.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan -Mustafa Şentürk-
 */


use RuntimeException;
use InvalidArgumentException;

class Migration
{
    /**
     * Veritabanı bağlantısı
     * @var Connection
     */
    private $connection;

    /**
     * Sorgu oluşturucu
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * Mevcut tabloların önbelleği
     * @var array
     */
    private static $tablesCache = [];

    public function __construct(Connect $connection)
    {
        $this->connection = $connection;
        $this->queryBuilder = new QueryBuilder($connection);
    }

    /**
     * Tablo oluştur
     * @param string $tableName
     * @param callable $callback
     */
    public function create(string $tableName, callable $callback): void
    {
        if ($this->tableExists($tableName)) {
            echo "Tablo zaten mevcut, atlanıyor: {$tableName}\n";
            return;
        }

        $blueprint = new TableBlueprint($tableName);
        $callback($blueprint);

        $sql = $blueprint->buildCreateTable();
        $this->execute($sql);
        
        // Cache'i temizle
        self::$tablesCache = [];
    }

    /**
     * Tabloyu değiştir
     * @param string $tableName
     * @param callable $callback
     */
    public function alter(string $tableName, callable $callback): void
    {
        if (!$this->tableExists($tableName)) {
            throw new RuntimeException("Tablo bulunamadı: {$tableName}");
        }

        $blueprint = new TableBlueprint($tableName);
        $callback($blueprint);

        $sql = $blueprint->buildAlterTable();
        $this->execute($sql);
    }

    /**
     * Tabloyu yeniden adlandır
     * @param string $from
     * @param string $to
     */
    public function rename(string $from, string $to): void
    {
        if (!$this->tableExists($from)) {
            throw new RuntimeException("Tablo bulunamadı: {$from}");
        }

        if ($this->tableExists($to)) {
            throw new RuntimeException("Hedef tablo zaten mevcut: {$to}");
        }

        $sql = "RENAME TABLE `{$from}` TO `{$to}`";
        $this->execute($sql);
    }

    /**
     * Tabloyu sil
     * @param string $tableName
     */
    public function drop(string $tableName): void
    {
        if (!$this->tableExists($tableName)) {
            echo "Tablo zaten mevcut değil, atlanıyor: {$tableName}\n";
            return;
        }

        $sql = "DROP TABLE `{$tableName}`";
        $this->execute($sql);
        
        // Cache'i temizle
        self::$tablesCache = [];
    }

    /**
     * Tabloyu sil (varsa)
     * @param string $tableName
     */
    public function dropIfExists(string $tableName): void
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        $this->execute($sql);
    }

    /**
     * Tablo var mı kontrol et
     * @param string $tableName
     * @return bool
     */
    public function tableExists(string $tableName): bool
    {
        if (empty(self::$tablesCache)) {
            $tables = $this->queryBuilder->table('information_schema.tables')
                ->select(['TABLE_NAME'])
                ->where('TABLE_SCHEMA', '=', $this->connection->getDatabaseName())
                ->get();
            
            self::$tablesCache = array_column($tables, 'TABLE_NAME');
        }

        return in_array($tableName, self::$tablesCache);
    }

    /**
     * SQL sorgusunu çalıştır
     * @param string $sql
     */
    private function execute(string $sql): void
    {
        try {
            echo "Executing SQL: " . substr($sql, 0, 100) . "...\n";
            $this->connection->query($sql);
            echo "SQL executed successfully.\n";
        } catch (\Exception $e) {
            echo "SQL execution failed: " . $e->getMessage() . "\n";
            echo "SQL: " . $sql . "\n";
            throw $e;
        }
    }
}