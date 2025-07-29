<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * DatabaseSeeder
 *
 * Tüm veritabanı seed işlemlerini merkezi olarak yöneten ana seeder dosyasıdır.
 * Her tablo için ayrı seeder sınıfı tanımlanır ve ana seeder bunları sıralı şekilde çalıştırır.
 *
 * Kullanım:
 *   php DatabaseSeeder.php
 */

use Mgx\Slimdb\Seeder;
use Mgx\Slimdb\Connect;

/**
 * Kullanıcılar için örnek veri ekler.
 */
class UsersSeeder extends Seeder
{
    public function run()
    {
        $this->seedTable('users', [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT)
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => password_hash('password456', PASSWORD_DEFAULT)
            ]
        ]);
    }
}

/**
 * Ürünler için örnek veri ekler.
 */
class ProductsSeeder extends Seeder
{
    public function run()
    {
        $this->seedTable('products', [
            [
                'name' => 'Laptop',
                'price' => 1500.00,
                'stock' => 10
            ],
            [
                'name' => 'Smartphone',
                'price' => 800.00,
                'stock' => 25
            ]
        ]);
    }
}

/**
 * Ana seeder: tüm alt seeder'ları sıralı şekilde çalıştırır.
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seeder'lar dizi olarak tanımlanıp döngüyle çalıştırılabilir
        $seeders = [
            UsersSeeder::class,
            ProductsSeeder::class,
            // Yeni seeder eklemek için buraya ekleyin
        ];

        foreach ($seeders as $seederClass) {
            echo "Çalıştırılıyor: $seederClass\n";
            (new $seederClass($this->connection))->run();
        }
    }
}

// CLI'dan çalıştırmak için runner
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $config = [
        'host'     => 'localhost',
        'dbname'   => 'test_db',
        'username' => 'root',
        'password' => '',
    ];

    try {
        $connection = Connect::getInstance($config);
        $seeder = new DatabaseSeeder($connection);
        $seeder->run();
        echo "Seed işlemi tamamlandı.\n";
    } catch (Exception $e) {
        echo "Seed işlemi sırasında hata oluştu: " . $e->getMessage() . "\n";
    }
}