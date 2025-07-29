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
class CreateTablesMigration extends Migration
{
    public function up()
    {
        // Kullanıcılar tablosu
        $this->create('users', function (TableBlueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        // Gönderiler tablosu
        $this->create('posts', function (TableBlueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('content');
            $table->bigInteger('user_id')->unsigned();
            $table->timestamps();
            $table->foreign('user_id', 'id', 'users');
        });
    }

    public function down()
    {
        $this->drop('posts');
        $this->drop('users');
    }
}

// Migration'ı çalıştır
$migration = new CreateTablesMigration($connection);
$migration->up(); // Tablo oluşturur
// $migration->down(); // Geri almak için