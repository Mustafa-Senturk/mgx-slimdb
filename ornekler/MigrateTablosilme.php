
<?php


require_once __DIR__ . '/../vendor/autoload.php';

use Mgx\Slimdb\Connect;
use Mgx\Slimdb\Migration\Migration;
use Mgx\Slimdb\TableBlueprint;



// Bağlantı oluştur
$config = [
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'root',
    'password' => '',
];
$connection = Connect::getInstance($config);

// Migration sınıfı tanımla
class DropAndRenameTablesMigration extends Migration
{
    public function up()
    {
        // Tablo silme
        $this->drop('old_table');

        // Tablo yeniden adlandırma
    }

    public function down()
    {
        // Geri alma işlemleri (isteğe bağlı)
    }
}

// Migration'ı çalıştır
$migration = new DropAndRenameTablesMigration($connection);
$migration->up();

