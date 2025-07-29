<?php
namespace Mgx\Slimdb;
/**
 * MigrationRunner sınıfı
 * Bu sınıf, veritabanı migration'larını çalıştırmak ve geri almak için kullanılır.
 * Migration dosyalarını okuyarak veritabanı yapısını günceller.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan -Mustafa Şentürk-
 */



use PDO;
use RuntimeException;
use InvalidArgumentException;

class MigrationRunner
{
    private $connection;
    private $migrationsPath;
    private $migrationTable = 'migrations';

    public function __construct(Connect $connection, string $migrationsPath)
    {
        $this->connection = $connection;
        $this->migrationsPath = rtrim($migrationsPath, '/') . '/';
        
        // Migration tablosunu oluştur
        $this->createMigrationsTable();
    }

    /**
     * Migration tablosunu oluştur
     */
    private function createMigrationsTable(): void
    {
        $migration = new Migration($this->connection);
        
        if (!$migration->tableExists($this->migrationTable)) {
            $migration->create($this->migrationTable, function (TableBlueprint $table) {
                $table->id();
                $table->string('migration', 255);
                $table->timestamp('run_at')->default('CURRENT_TIMESTAMP');
            });
        }
    }

    /**
     * Tüm bekleyen migration'ları çalıştır
     */
    public function migrate(): void
    {
        $runMigrations = $this->getRunMigrations();
        $allMigrations = $this->getAllMigrations();
        $pendingMigrations = array_diff($allMigrations, $runMigrations);

        if (empty($pendingMigrations)) {
            echo "Bekleyen migration yok.\n";
            return;
        }

        sort($pendingMigrations);
        
        try {
            $this->connection->beginTransaction();

            foreach ($pendingMigrations as $migrationFile) {
                $this->runMigration($migrationFile);
            }

            $this->connection->commit();
            echo "Successfully ran " . count($pendingMigrations) . " migration(s).\n";
        } catch (\Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new RuntimeException("Migration failed: " . $e->getMessage());
        }
    }

    /**
     * Migration'ı geri al
     * @param int $steps Geri alınacak migration sayısı
     */
    public function rollback(int $steps = 1): void
    {
        $runMigrations = $this->getRunMigrations();
        $rollbackMigrations = array_slice($runMigrations, -$steps, $steps, true);

        if (empty($rollbackMigrations)) {
            echo "No migrations to rollback.\n";
            return;
        }

        try {
            $this->connection->beginTransaction();
            
            foreach (array_reverse($rollbackMigrations) as $migrationFile) {
                $this->rollbackMigration($migrationFile);
            }
            
            $this->connection->commit();
            echo "Successfully rolled back " . count($rollbackMigrations) . " migration(s).\n";
        } catch (\Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new RuntimeException("Rollback failed: " . $e->getMessage());
        }
    }

    /**
     * Migration dosyasını çalıştır
     * @param string $migrationFile
     */
    private function runMigration(string $migrationFile): void
    {
        require_once $this->migrationsPath . $migrationFile;
        
        $className = $this->getMigrationClassName($migrationFile);
        $migration = new $className($this->connection);
        
        echo "Running migration: $migrationFile\n";
        $migration->up();
        
        // Migration'ı kaydet
        $this->recordMigration($migrationFile);
    }

    /**
     * Migration'ı geri al
     * @param string $migrationFile
     */
    private function rollbackMigration(string $migrationFile): void
    {
        require_once $this->migrationsPath . $migrationFile;
        
        $className = $this->getMigrationClassName($migrationFile);
        $migration = new $className($this->connection);
        
        echo "Rolling back migration: $migrationFile\n";
        $migration->down();
        
        // Migration kaydını sil
        $this->removeMigration($migrationFile);
    }

    /**
     * Migration sınıf adını al
     * @param string $migrationFile
     * @return string
     */
    private function getMigrationClassName(string $migrationFile): string
    {
        $name = str_replace('.php', '', $migrationFile);
        // Sadece ilk tarih kısmını (YYYY_MM_DD_HHMMSS_) kaldır
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $name);
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);

        return $name;
    }

    /**
     * Çalıştırılan migration'ları al
     * @return array
     */
    private function getRunMigrations(): array
    {
        $stmt = $this->connection->getPdo()->prepare(
            "SELECT migration FROM {$this->migrationTable} ORDER BY id ASC"
        );
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Tüm migration dosyalarını al
     * @return array
     */
    private function getAllMigrations(): array
    {
        $files = glob($this->migrationsPath . '*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file);
        }
        
        sort($migrations);
        return $migrations;
    }

    /**
     * Migration'ı kaydet
     * @param string $migrationFile
     */
    private function recordMigration(string $migrationFile): void
    {
        $this->connection->query(
            "INSERT INTO {$this->migrationTable} (migration) VALUES (?)", 
            [$migrationFile]
        );
    }

    /**
     * Migration kaydını sil
     * @param string $migrationFile
     */
    private function removeMigration(string $migrationFile): void
    {
        $this->connection->query(
            "DELETE FROM {$this->migrationTable} WHERE migration = ?", 
            [$migrationFile]
        );
    }

    /**
     * Migration durumunu göster
     */
    public function status(): void
    {
        $runMigrations = $this->getRunMigrations();
        $allMigrations = $this->getAllMigrations();
        
        echo "Migration Status\n";
        echo "================\n";
        
        foreach ($allMigrations as $migration) {
            $status = in_array($migration, $runMigrations) ? 'Ran' : 'Pending';
            $date = '';
            
            if ($status === 'Ran') {
                $stmt = $this->connection->getPdo()->prepare(
                    "SELECT run_at FROM {$this->migrationTable} WHERE migration = ?"
                );
                $stmt->execute([$migration]);
                $date = $stmt->fetchColumn();
            }
            
            printf("%-50s %-10s %s\n", $migration, $status, $date);
        }
        
        echo "\nTotal: " . count($allMigrations) . " migration(s)\n";
        echo "Ran: " . count($runMigrations) . ", Pending: " . 
             (count($allMigrations) - count($runMigrations)) . "\n";
    }
}