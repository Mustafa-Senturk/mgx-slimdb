# MGX SlimDB

Hafif, sade ve modern bir PDO tabanlı PHP veritabanı, migration ve seeder kütüphanesi.

---

## Özellikler

- PDO ile kolay ve güvenli veritabanı bağlantısı
- Migration desteği (tablo oluşturma, değiştirme, silme)
- Seeder desteği (örnek veri ekleme)
- QueryBuilder ile kolay SQL sorguları
- CLI ile migration ve seeder yönetimi
- PSR-4 uyumlu modern yapı

---

## Kurulum

### Composer ile Kurulum

```bash
composer require mgx/slimdb
```

> **Not:** Henüz Packagist’e yüklemediyseniz, kendi projenizde local olarak kullanmak için:
>
> 1. Ana projenizin `composer.json` dosyasına aşağıdaki satırı ekleyin:
>    ```json
>    "repositories": [
>        {
>            "type": "path",
>            "url": "../mgx-slimdb"
>        }
>    ]
>    ```
> 2. Sonra:
>    ```bash
>    composer require mgx/slimdb:dev-main
>    ```

---

## Temel Kullanım

### 1. Veritabanı Bağlantısı

```php
use Mgx\Slimdb\Connect;

$config = [
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'root',
    'password' => '',
];

$db = Connect::getInstance($config);
```

---

### 2. Migration Kullanımı

#### Migration Oluşturma

```php
use Mgx\Slimdb\Migration\Migration;
use Mgx\Slimdb\TableBlueprint;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->create('users', function (TableBlueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password', 60);
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->drop('users');
    }
}
```

#### Migration Çalıştırma (CLI)

```bash
php ornekler/Migration_Runner_ornek.php
```

---

### 3. Seeder Kullanımı

#### Seeder Sınıfı

```php
use Mgx\Slimdb\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTable('users', [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT)
            ]
        ]);
    }
}
```

#### Tüm Seeder'ları Çalıştırmak

```bash
php ornekler/DatabaseSeeder.php
```

---

### 4. QueryBuilder Kullanımı

```php
use Mgx\Slimdb\QueryBuilder;

$qb = new QueryBuilder($db);

// Tüm kullanıcıları çek
$users = $qb->table('users')->select()->get();

// Filtreli sorgu
$admins = $qb->table('users')->where('email', 'LIKE', '%admin%')->get();
```

---

## CLI ile Migration ve Seeder Yönetimi

Hazır CLI dosyası ile migration ve seeder işlemlerini kolayca yönetebilirsiniz:

```bash
php ornekler/ornekcli.php migrate      # Tüm migration'ları çalıştırır
php ornekler/ornekcli.php rollback     # Son migration'ı geri alır
php ornekler/ornekcli.php status       # Migration durumunu gösterir
php ornekler/ornekcli.php new <name>   # Yeni migration dosyası oluşturur
```

---

## Klasör Yapısı

```
mgx-slimdb/
├── src/                # Ana kütüphane kodları
├── ornekler/           # Kullanım örnekleri ve CLI dosyaları
├── test/               # Otomatik testler
├── migrations/         # Migration dosyaları (kendi projenizde)
├── seeds/              # Seeder dosyaları (kendi projenizde)
├── composer.json
└── README.md
```

---

## Test

```bash
composer install
vendor/bin/phpunit
```

---

## Katkı ve Lisans

- Katkı yapmak için PR gönderebilirsiniz.
- MIT Lisansı ile dağıtılmaktadır.

---

**Daha fazla örnek ve detay