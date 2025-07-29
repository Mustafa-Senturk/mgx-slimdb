<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MGX\Slimdb\Connect;

// Bağlantı ayarları
$config = [
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'root',
    'password' => '',
    // 'charset' => 'utf8mb4', // opsiyonel
];

try {
    // Singleton ile bağlantı nesnesi oluşturma
    $db = Connect::getInstance($config);

    // PDO nesnesine erişim
    $pdo = $db->getPdo();
    echo "PDO bağlantısı başarılı!\n";

    // Basit bir sorgu çalıştırma
    $result = $db->query("SELECT NOW() as current_time")->fetch();
    echo "Veritabanı saati: " . $result['current_time'] . "\n";

    // Hazırlanmış sorgu ile veri ekleme
    $db->query(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );
    echo "Tablo oluşturuldu veya zaten mevcut.\n";

    $insert = $db->query(
        "INSERT INTO users (name, email) VALUES (?, ?)",
        ['Ali Veli', 'ali@example.com']
    );
    echo "Kullanıcı eklendi, ID: " . $db->lastInsertId() . "\n";

    // Veri çekme
    $users = $db->query("SELECT * FROM users")->fetchAll();
    echo "Kullanıcılar:\n";
    foreach ($users as $user) {
        echo "- {$user['id']}: {$user['name']} ({$user['email']})\n";
    }

    // Transaction örneği
    $db->beginTransaction();
    try {
        $db->query("INSERT INTO users (name, email) VALUES (?, ?)", ['Mehmet', 'mehmet@example.com']);
        $db->query("INSERT INTO users (name, email) VALUES (?, ?)", ['Ayşe', 'ayse@example.com']);
        $db->commit();
        echo "Transaction başarılı şekilde tamamlandı.\n";
    } catch (Exception $e) {
        $db->rollBack();
        echo "Transaction geri alındı: " . $e->getMessage() . "\n";
    }

    // Hatalı bağlantı örneği
    /*
    $wrongConfig = [
        'host' => 'localhost',
        'dbname' => 'yanlis_db',
        'username' => 'root',
        'password' => 'yanlis'
    ];
    $wrongDb = Connect::getInstance($wrongConfig); // Hata fırlatır
    */

} catch (Exception $e) {
    echo "Bağlantı veya sorgu hatası: " . $e->getMessage() . "\n";
}