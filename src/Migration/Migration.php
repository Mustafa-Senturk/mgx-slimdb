<?php
namespace Mgx\Slimdb\Migration;

/**
 * Migration sınıfı
 * Bu sınıf, veritabanı migration işlemlerini yönetmek için kullanılır.
 * Tabloların oluşturulması, değiştirilmesi ve silinmesi gibi işlemleri içerir.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan - Mustafa Şentürk-
 */

use Mgx\Slimdb\Connect;
use Mgx\Slimdb\Migration as MigrationMain;
use Mgx\Slimdb\TableBlueprint;

/**
 * Migration temel sınıfı
 */
abstract class Migration
{
    /** @var Connect */
    protected $connection;

    /** @var MigrationMain */
    protected $migration;

    public function __construct(Connect $connection)
    {
        $this->connection = $connection;
        $this->migration = new MigrationMain($connection);
    }

    /**
     * Migration'ı uygula
     */
    abstract public function up();

    /**
     * Migration'ı geri al
     */
    abstract public function down();

    /**
     * Tablo oluştur
     */
    protected function create(string $table, callable $callback): void
    {
        $this->migration->create($table, $callback);
    }

    /**
     * Tabloyu değiştir
     */
    protected function alter(string $table, callable $callback): void
    {
        $this->migration->alter($table, $callback);
    }

    /**
     * Tabloyu sil
     */
    protected function drop(string $table): void
    {
        $this->migration->drop($table);
    }

    /**
     * Tabloyu yeniden adlandır
     */
    protected function rename(string $from, string $to): void
    {
        $this->migration->rename($from, $to);
    }

    /**
     * Tabloyu değiştir (alias)
     */
    protected function table(string $table, callable $callback): void
    {
        $this->migration->alter($table, $callback);
    }

    /**
     * Tablo var mı kontrol et
     */
    protected function tableExists(string $table): bool
    {
        return $this->migration->tableExists($table);
    }
}