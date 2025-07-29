<?php
#!/usr/bin/env php
/**
 * MGX SlimDB Migration CLI
 *
 * Komut satırından migration işlemlerini yönetmek için gelişmiş CLI aracı.
 * 
 * Kullanım:
 *   php ornekcli.php migrate           # Tüm bekleyen migration'ları çalıştırır
 *   php ornekcli.php rollback [n]      # Son n migration'ı geri alır (varsayılan: 1)
 *   php ornekcli.php status            # Migration durumunu gösterir
 *   php ornekcli.php new <name>        # Yeni migration dosyası oluşturur
 *   php ornekcli.php help              # Yardım mesajı
 */

require __DIR__ . '/../vendor/autoload.php';

use Mgx\Slimdb\Connect;
use Mgx\Slimdb\MigrationRunner;

// Konfigürasyon
$config = [
    'host' => 'localhost',
    'dbname' => 'my_database',
    'username' => 'root',
    'password' => 'secret',
    'charset' => 'utf8mb4'
];

$migrationsPath = __DIR__ . '/../migrations';

// Bağlantı oluştur
$db = Connect::getInstance($config);
$runner = new MigrationRunner($db, $migrationsPath);

// Komutları işle
$command = $argv[1] ?? 'help';

switch ($command) {
    case 'migrate':
        echo "Tüm bekleyen migration'lar çalıştırılıyor...\n";
        $runner->migrate();
        break;

    case 'rollback':
        $steps = isset($argv[2]) ? (int)$argv[2] : 1;
        echo "Son $steps migration geri alınıyor...\n";
        $runner->rollback($steps);
        break;

    case 'status':
        echo "Migration durumu:\n";
        $runner->status();
        break;

    case 'new':
        $name = $argv[2] ?? null;
        if (!$name) {
            echo "Kullanım: php ornekcli.php new <migration_name>\n";
            exit(1);
        }
        createMigrationFile($name, $migrationsPath);
        break;

    case 'reset':
        echo "Tüm migration kayıtları siliniyor (migration tablosu sıfırlanıyor)...\n";
        $db->query("TRUNCATE TABLE migrations");
        echo "Migration tablosu sıfırlandı.\n";
        break;

    case 'help':
    default:
        echo <<<EOT
MGX SlimDB Migration CLI

Kullanım:
  php ornekcli.php migrate           Tüm bekleyen migration'ları çalıştırır
  php ornekcli.php rollback [n]      Son n migration'ı geri alır (varsayılan: 1)
  php ornekcli.php status            Migration durumunu gösterir
  php ornekcli.php new <name>        Yeni migration dosyası oluşturur
  php ornekcli.php reset             Migration tablosunu sıfırlar (tüm geçmiş silinir)
  php ornekcli.php help              Bu yardım mesajını gösterir

EOT;
        break;
}

/**
 * Yeni migration dosyası oluşturur.
 *
 * @param string $name Migration sınıf adı (ör: create_users_table)
 * @param string $migrationsPath Migration dosyalarının bulunduğu klasör
 */
function createMigrationFile(string $name, string $migrationsPath): void
{
    $filename = date('Y_m_d_His') . '_' . $name . '.php';
    $filepath = rtrim($migrationsPath, '/') . '/' . $filename;

    // Sınıf adını oluştur (ör: CreateUsersTable)
    $className = str_replace('_', ' ', $name);
    $className = ucwords($className);
    $className = str_replace(' ', '', $className);

    $content = <<<PHP
<?php

use Mgx\Slimdb\Migration\Migration;
use Mgx\Slimdb\TableBlueprint;

class $className extends Migration
{
    public function up()
    {
        // Migration kodunuz buraya
    }

    public function down()
    {
        // Rollback kodunuz buraya
    }
}
PHP;

    if (!is_dir($migrationsPath)) {
        mkdir($migrationsPath, 0777, true);
    }

    file_put_contents($filepath, $content);
    echo "Yeni migration dosyası oluşturuldu: $filename\n";
}