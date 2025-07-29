<?php
/**
 * MigrationRunner Kullanım Örneği
 *
 * Bu dosya, MGX SlimDB ile migration işlemlerinin nasıl yönetileceğini adım adım gösterir.
 * MigrationRunner ile migration dosyalarını çalıştırabilir, geri alabilir ve durumunu görebilirsiniz.
 *
 * @package MGX\Slimdb
 * @example
 * 1. Migration dosyalarınızı bir klasörde (ör: migrations/) tutun.
 * 2. Her migration dosyası, Migration soyut sınıfından türeyen bir sınıf içermelidir.
 * 3. MigrationRunner ile migrate, rollback ve status işlemlerini kolayca yönetebilirsiniz.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MGX\Slimdb\Connect;
use Mgx\Slimdb\MigrationRunner;

// 1. Veritabanı bağlantı ayarları
$config = [
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'root',
    'password' => '',
    // 'charset' => 'utf8mb4', // opsiyonel
];

// 2. Bağlantı nesnesi oluştur
$db = Connect::getInstance($config);

// 3. Migration dosyalarının bulunduğu klasör yolu
$migrationsPath = __DIR__ . '/migrations';

// 4. MigrationRunner nesnesi oluştur
$runner = new MigrationRunner($db, $migrationsPath);

/**
 * 5. Tüm bekleyen migration'ları çalıştırmak için:
 */
echo "Tüm bekleyen migration'lar çalıştırılıyor...\n";
$runner->migrate();

/**
 * 6. Migration durumunu görmek için:
 */
echo "\nMigration durumu:\n";
$runner->status();

/**
 * 7. Son migration'ı geri almak için:
 */
echo "\nSon migration geri alınıyor...\n";
$runner->rollback();

/**
 * 8. Son 3 migration'ı geri almak için:
 */
echo "\nSon 3 migration geri alınıyor...\n";
$runner->rollback(3);

/**
 * 9. Belirli bir migration dosyasını manuel çalıştırmak için:
 */
$migrationFile = '2024_06_01_120000_create_users_table.php';
if (file_exists($migrationsPath . '/' . $migrationFile)) {
    require_once $migrationsPath . '/' . $migrationFile;
    $className = 'CreateUsersTable'; // Dosya adından sınıf adını türetin
    $migration = new $className($db);
    echo "\nBelirli migration çalıştırılıyor: $migrationFile\n";
    $migration->up();
} else {
    echo "\nBelirtilen migration dosyası bulunamadı: $migrationFile\n";
}

/**
 * 10. MigrationRunner ile migration tablosunu sıfırlamak (tüm kayıtları silmek) için:
 * (Dikkat: Bu işlem migration geçmişini siler!)
 */
// $db->query("TRUNCATE TABLE migrations");
// echo "Migration tablosu sıfırlandı.\n";