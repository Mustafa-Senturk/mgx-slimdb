<?php
namespace Mgx\Slimdb;
/**
 * Seeder sınıfı
 * Bu sınıf veritabanı tablolarını seed etmek için temel işlevselliği sağlar.
 * Türetilmiş sınıflar bu sınıfı kullanarak veritabanı tablolarını doldurabilir.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan -Mustafa Şentürk-
 */


class Seeder
{
    protected $connection;
    protected $queryBuilder;

    public function __construct(Connect $connection)
    {
        $this->connection = $connection;
        $this->queryBuilder = new QueryBuilder($connection);
    }

    /**
     * Seed çalıştır
     */
    public function run(): void
    {
        // Türetilmiş sınıflar tarafından implemente edilecek
    }

    /**
     * Tabloyu temizle ve seed et
     * @param string $table
     * @param array $data
     */
    protected function seedTable(string $table, array $data): void
    {
        $this->queryBuilder->table($table)->delete();
        
        foreach ($data as $row) {
            $this->queryBuilder->table($table)->insert($row);
        }
    }
}