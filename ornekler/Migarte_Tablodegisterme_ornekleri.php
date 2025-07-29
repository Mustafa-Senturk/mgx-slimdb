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
class AlterTablesMigration extends Migration
{
    public function up()
    {
        // users tablosunu değiştir
        $this->alter('users', function (TableBlueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->change('name', 'varchar(150)');
            $table->dropColumn('email_verified_at');
            $table->index('created_at');
        });

        // posts tablosunu değiştir
        $this->alter('posts', function (TableBlueprint $table) {
            $table->foreign('category_id', 'id', 'categories');
        });
    }

    public function down()
    {
        // Geri alma işlemleri (isteğe bağlı)
    }
}

// Migration'ı çalıştır
$migration = new AlterTablesMigration($connection);
$migration->up();