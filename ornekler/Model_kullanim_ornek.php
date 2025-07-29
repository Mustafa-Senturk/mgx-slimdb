
<?php
use Mgx\Slimdb\Connect;
use Mgx\Slimdb\Model;
use Mgx\Slimdb\QueryBuilder;

// Örnek User modeli
class User extends \Mgx\Slimdb\Model
{
    protected static $table = 'users';
    protected static $primaryKey = 'user_id';
    protected static $fillable = ['name', 'email', 'password'];
    protected static $hidden = ['password'];
    protected static $timestamps = true;
}

// QueryBuilder'ı modellere bağlama
$db = new Connect($config);
$queryBuilder = new QueryBuilder($db);
Model::setQueryBuilder($queryBuilder);

// Yeni kullanıcı oluştur
$user = new User();
$user->name = 'Ahmet';
$user->email = 'ahmet@example.com';
$user->password = password_hash('sifre123', PASSWORD_DEFAULT);
$user->save();

// Veya
$user = User::create([
    'name' => 'Mehmet',
    'email' => 'mehmet@example.com',
    'password' => password_hash('sifre456', PASSWORD_DEFAULT)
]);

// Kullanıcıyı güncelle
$user = User::find(1);
$user->email = 'yeni@example.com';
$user->save();

// Kullanıcıyı sil
$user->delete();

// Tüm aktif kullanıcılar
$activeUsers = User::query()
    ->where('status', '=', 'active')
    ->orderBy('name', 'ASC')
    ->get();

// İlk 10 kullanıcı
$users = User::query()
    ->limit(10)
    ->get();