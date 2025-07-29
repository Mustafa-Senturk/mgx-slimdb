<?php
/**
 * Tüm Seeder'ları Çalıştırma Örneği
 *
 * Bu dosya, belirli bir dizindeki tüm seeder dosyalarını otomatik olarak yükler ve çalıştırır.
 * Her seeder dosyası, Seeder sınıfından türeyen bir sınıf içermelidir.
 *
 * Kullanım:
 *   php TumSedeerlaritetikleme.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Mgx\Slimdb\Connect;
use Mgx\Slimdb\Seeder;

// Veritabanı bağlantı ayarları
$config = [
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'root',
    'password' => '',
];

// Bağlantı oluştur
$db = Connect::getInstance($config);

// Seeder dosyalarının bulunduğu klasör (ör: ornekler/seeds/)
$seedsPath = __DIR__ . '/seeds';

// Eğer seeds klasörü yoksa hata ver
if (!is_dir($seedsPath)) {
    echo "Seeder klasörü bulunamadı: $seedsPath\n";
    exit(1);
}

// Tüm seeder dosyalarını bul
$seedFiles = glob($seedsPath . '/*.php');

if (empty($seedFiles)) {
    echo "Hiçbir seeder dosyası bulunamadı.\n";
    exit(0);
}

// Her seeder dosyasını sırayla yükle ve çalıştır
foreach ($seedFiles as $seedFile) {
    require_once $seedFile;
    $className = pathinfo($seedFile, PATHINFO_FILENAME);

    if (!class_exists($className)) {
        echo "Seeder sınıfı bulunamadı: $className\n";
        continue;
    }

    $seeder = new $className($db);
    echo "Çalıştırılıyor: $className\n";
    $seeder->run();
}

echo "Tüm seed işlemleri tamamlandı.\n";


/****
 * Ornek seeder dosyası yapısı:
 *
 class UsersSeeder extends Seeder
    {
        public function run()
        {
            $this->seedTable('users', [
                ['name' => 'Demo', 'email' => 'demo@example.com', 'password' => password_hash('demo', PASSWORD_DEFAULT)],
            ]);
        }
    }
 */