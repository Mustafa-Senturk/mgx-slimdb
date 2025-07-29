<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MGX\Slimdb\Connect;
use MGX\Slimdb\QueryBuilder;

$config = [
    'host'     => 'localhost',
    'dbname'   => 'test_db',
    'username' => 'root',
    'password' => '',
];

try {
    $db = Connect::getInstance($config);
    $qb = new QueryBuilder($db);

    // --- 1. BETWEEN ile filtreleme ---
    $between = $qb->table('products')
        ->select()
        ->where('price', '>=', 1000)
        ->where('price', '<=', 10000)
        ->get();
    echo "Fiyatı 1000-10000₺ arası ürünler:\n";
    foreach ($between as $row) {
        echo "- {$row['name']} ({$row['price']}₺)\n";
    }

    // --- 2. NOT IN ile filtreleme ---
    $notIn = $qb->table('products')
        ->select()
        ->whereNotNull('name')
        ->where('stock', '>', 0)
        ->get();
    echo "Stokta olan ürünler:\n";
    foreach ($notIn as $row) {
        echo "- {$row['name']} (stok: {$row['stock']})\n";
    }

    // --- 3. NULL ve NOT NULL filtreleme ---
    $nulls = $qb->table('products')
        ->select()
        ->whereNull('name')
        ->get();
    echo "İsmi NULL olan ürünler (varsa):\n";
    foreach ($nulls as $row) {
        echo "- ID: {$row['id']}\n";
    }

    $notNulls = $qb->table('products')
        ->select()
        ->whereNotNull('name')
        ->get();
    echo "İsmi NULL olmayan ürünler:\n";
    foreach ($notNulls as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 4. JOIN örneği ---
    // Örnek için yeni bir tablo oluşturalım
    $db->query(
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100)
        )"
    );
    $db->query("INSERT INTO categories (name) VALUES ('Elektronik'), ('Aksesuar')");

    // Ürünlere kategori ekleyelim
    $db->query("ALTER TABLE products ADD COLUMN category_id INT NULL");
    $db->query("UPDATE products SET category_id = 1 WHERE name IN ('Laptop','Telefon','Tablet','Monitör')");
    $db->query("UPDATE products SET category_id = 2 WHERE name IN ('Klavye','Mouse','Webcam')");

    $joined = $qb->table('products')
        ->select(['products.name as product', 'categories.name as category'])
        ->join('categories', 'products.category_id', '=', 'categories.id')
        ->get();
    echo "Ürünler ve kategorileri:\n";
    foreach ($joined as $row) {
        echo "- {$row['product']} ({$row['category']})\n";
    }

    // --- 5. DISTINCT kullanımı ---
    $distinctStocks = $qb->table('products')
        ->select(['stock'])
        ->distinct()
        ->get();
    echo "Farklı stok değerleri:\n";
    foreach ($distinctStocks as $row) {
        echo "- {$row['stock']}\n";
    }

    // --- 6. LIMIT ve OFFSET kullanımı ---
    $limited = $qb->table('products')->select()->orderBy('id')->limit(2)->offset(1)->get();
    echo "2 kayıt, 1. kayıttan sonra:\n";
    foreach ($limited as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 7. LIKE ile arama ---
    $like = $qb->table('products')->select()->where('name', 'LIKE', '%top%')->get();
    echo "İsminde 'top' geçen ürünler:\n";
    foreach ($like as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 8. IS NULL/IS NOT NULL ile arama ---
    $nullCategory = $qb->table('products')->select()->whereNull('category_id')->get();
    echo "Kategorisi olmayan ürünler:\n";
    foreach ($nullCategory as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 9. Çoklu güncelleme ---
    $affected = $qb->table('products')
        ->where('stock', '<', 20)
        ->update(['stock' => 20]);
    echo "Stok < 20 olan ürünler güncellendi ($affected kayıt).\n";

    // --- 10. Çoklu silme ---
    $deleted = $qb->table('products')
        ->where('price', '<', 1000)
        ->delete();
    echo "Fiyatı 1000₺ altı ürünler silindi ($deleted kayıt).\n";

    // --- 11. COUNT ile toplam kayıt sayısı ---
    $count = $qb->table('products')->count();
    echo "Toplam ürün sayısı: $count\n";

    // --- 12. Son eklenen ID ---
    $qb->table('products')->insert(['name' => 'Kamera', 'price' => 2000, 'stock' => 5]);
    $lastId = $qb->lastInsertId();
    echo "Son eklenen ürün ID: $lastId\n";

    // --- 13. SQL çıktısı (toSql) ---
    $sql = $qb->table('products')->where('stock', '>', 10)->orderBy('price')->toSql();
    echo "Oluşan SQL: $sql\n";

    // --- 14. GROUP BY ve HAVING kullanımı ---
    $grouped = $qb->table('products')
        ->select(['stock', 'COUNT(*) as adet'])
        ->groupBy('stock')
        ->having('adet', '>', 1)
        ->get();
    echo "Stok adedine göre gruplama (adet > 1):\n";
    foreach ($grouped as $row) {
        echo "- Stok: {$row['stock']} => {$row['adet']} ürün\n";
    }

    // --- 15. Çoklu GROUP BY ve HAVING ---
    $multiGroup = $qb->table('products')
        ->select(['stock', 'price', 'COUNT(*) as adet'])
        ->groupBy('stock', 'price')
        ->having('adet', '>=', 1)
        ->orderBy('adet', 'DESC')
        ->get();
    echo "Stok ve fiyat bazında gruplama:\n";
    foreach ($multiGroup as $row) {
        echo "- Stok: {$row['stock']}, Fiyat: {$row['price']} => {$row['adet']} ürün\n";
    }

    // --- 16. LEFT JOIN örneği ---
    $leftJoin = $qb->table('products')
        ->select(['products.name as product', 'categories.name as category'])
        ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
        ->get();
    echo "LEFT JOIN ile ürünler ve kategorileri:\n";
    foreach ($leftJoin as $row) {
        echo "- {$row['product']} (" . ($row['category'] ?? 'Kategori Yok') . ")\n";
    }

    // --- 17. RIGHT JOIN örneği ---
    $rightJoin = $qb->table('products')
        ->select(['products.name as product', 'categories.name as category'])
        ->rightJoin('categories', 'products.category_id', '=', 'categories.id')
        ->get();
    echo "RIGHT JOIN ile ürünler ve kategorileri:\n";
    foreach ($rightJoin as $row) {
        echo "- {$row['product']} (" . ($row['category'] ?? 'Kategori Yok') . ")\n";
    }

    // --- 18. orWhere, orWhereNull, orWhereNotNull kullanımı ---
    $orWhere = $qb->table('products')
        ->select()
        ->where('stock', '<', 10)
        ->orWhere('price', '>', 10000)
        ->get();
    echo "Stok < 10 veya fiyatı 10000₺ üstü ürünler:\n";
    foreach ($orWhere as $row) {
        echo "- {$row['name']} ({$row['price']}₺, stok: {$row['stock']})\n";
    }

    $orWhereNull = $qb->table('products')
        ->select()
        ->where('price', '<', 1000)
        ->orWhereNull('category_id')
        ->get();
    echo "Fiyatı 1000₺ altı veya kategorisi olmayan ürünler:\n";
    foreach ($orWhereNull as $row) {
        echo "- {$row['name']}\n";
    }

    $orWhereNotNull = $qb->table('products')
        ->select()
        ->where('stock', '>', 0)
        ->orWhereNotNull('category_id')
        ->get();
    echo "Stok > 0 veya kategorisi olan ürünler:\n";
    foreach ($orWhereNotNull as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 19. Çoklu insert (toplu ekleme) ---
    $bulk = [
        ['name' => 'Hoparlör', 'price' => 700, 'stock' => 40],
        ['name' => 'Mikrofon', 'price' => 1200, 'stock' => 15],
    ];
    foreach ($bulk as $item) {
        $qb->table('products')->insert($item);
    }
    echo "Toplu ürün ekleme tamamlandı (Hoparlör, Mikrofon).\n";

    // --- 20. first() ile tek kayıt çekme ---
    $firstProduct = $qb->table('products')->select()->orderBy('id')->first();
    echo "İlk ürün: {$firstProduct['name']} ({$firstProduct['price']}₺)\n";

    // --- 21. rightJoin ile kategori olmayan ürünler (RIGHT JOIN + IS NULL) ---
    $rightJoinNull = $qb->table('categories')
        ->select(['categories.name as category', 'products.name as product'])
        ->rightJoin('products', 'categories.id', '=', 'products.category_id')
        ->whereNull('categories.id')
        ->get();
    echo "Kategorisi olmayan ürünler (RIGHT JOIN + IS NULL):\n";
    foreach ($rightJoinNull as $row) {
        echo "- {$row['product']} (Kategori Yok)\n";
    }

    // --- 22. groupBy ile birden fazla alan ve aggregate fonksiyonları ---
    $multiAgg = $qb->table('products')
        ->select(['category_id', 'AVG(price) as avg_price', 'SUM(stock) as total_stock'])
        ->groupBy('category_id')
        ->get();
    echo "Kategoriye göre ortalama fiyat ve toplam stok:\n";
    foreach ($multiAgg as $row) {
        echo "- Kategori ID: {$row['category_id']} | Ortalama Fiyat: {$row['avg_price']} | Toplam Stok: {$row['total_stock']}\n";
    }

    // --- 23. whereIn ve orWhereIn kullanımı ---
    $whereIn = $qb->table('products')
        ->select()
        ->whereIn('name', ['Laptop', 'Tablet'])
        ->get();
    echo "Laptop veya Tablet olan ürünler:\n";
    foreach ($whereIn as $row) {
        echo "- {$row['name']}\n";
    }

    $orWhereIn = $qb->table('products')
        ->select()
        ->where('stock', '<', 50)
        ->orWhereIn('name', ['Hoparlör', 'Mikrofon'])
        ->get();
    echo "Stok < 50 veya adı Hoparlör/Mikrofon olan ürünler:\n";
    foreach ($orWhereIn as $row) {
        echo "- {$row['name']} (stok: {$row['stock']})\n";
    }

    // --- 24. whereNotIn ve orWhereNotIn kullanımı ---
    $whereNotIn = $qb->table('products')
        ->select()
        ->whereNotIn('name', ['Laptop', 'Tablet'])
        ->get();
    echo "Laptop ve Tablet HARİÇ ürünler:\n";
    foreach ($whereNotIn as $row) {
        echo "- {$row['name']}\n";
    }

    $orWhereNotIn = $qb->table('products')
        ->select()
        ->where('stock', '>', 10)
        ->orWhereNotIn('name', ['Kamera', 'Webcam'])
        ->get();
    echo "Stok > 10 veya adı Kamera/Webcam olmayan ürünler:\n";
    foreach ($orWhereNotIn as $row) {
        echo "- {$row['name']} (stok: {$row['stock']})\n";
    }

    // --- 25. Sadece belirli kolonları çekme (select) ---
    $onlyNames = $qb->table('products')->select(['name'])->get();
    echo "Tüm ürün isimleri:\n";
    foreach ($onlyNames as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 26. Sıralama birden fazla kolon ile (orderBy) ---
    $multiOrder = $qb->table('products')
        ->select()
        ->orderBy('stock', 'DESC')
        ->orderBy('price', 'ASC')
        ->get();
    echo "Stok azalan, fiyat artan sıralama:\n";
    foreach ($multiOrder as $row) {
        echo "- {$row['name']} (stok: {$row['stock']}, fiyat: {$row['price']})\n";
    }

    // --- 27. Paginate ile sayfalama ---
    $page = 2;
    $perPage = 3;
    $pagination = $qb->table('products')->select()->orderBy('id')->paginate($perPage, $page);
    echo "Sayfa $page/{$pagination['total_pages']} (Her sayfa $perPage ürün):\n";
    foreach ($pagination['data'] as $row) {
        echo "- {$row['name']} ({$row['price']}₺)\n";
    }
    echo "Toplam ürün: {$pagination['total']}\n";

    // --- 28. reset() ile yeni sorguya başlama ---
    $qb->table('products')->select(['name'])->where('stock', '>', 10);
    echo "Reset öncesi SQL: " . $qb->toSql() . "\n";
    $qb->reset();
    $qb->table('products')->select(['name'])->where('price', '<', 5000);
    echo "Reset sonrası yeni SQL: " . $qb->toSql() . "\n";

    // --- 29. toSql() ile parametreli sorgu çıktısı ---
    $qb->table('products')->select(['name', 'price'])->where('name', 'LIKE', '%a%')->orderBy('price', 'DESC');
    echo "Parametreli SQL: " . $qb->toSql() . "\n";

    // --- 30. Birden fazla HAVING ile gruplama ---
    $multiHaving = $qb->table('products')
        ->select(['category_id', 'AVG(price) as avg_price', 'SUM(stock) as total_stock'])
        ->groupBy('category_id')
        ->having('avg_price', '>', 1000)
        ->having('total_stock', '>', 10, 'AND')
        ->get();
    echo "Kategoriye göre ortalama fiyatı 1000₺ ve toplam stoğu 10'dan fazla olanlar:\n";
    foreach ($multiHaving as $row) {
        echo "- Kategori ID: {$row['category_id']} | Ortalama Fiyat: {$row['avg_price']} | Toplam Stok: {$row['total_stock']}\n";
    }

    // --- 31. Tek bir ürünün varlığını kontrol etme (first + null kontrolü) ---
    $product = $qb->table('products')->where('name', '=', 'Laptop')->first();
    if ($product) {
        echo "Laptop ürünü mevcut, fiyatı: {$product['price']}\n";
    } else {
        echo "Laptop ürünü bulunamadı.\n";
    }

    // --- 32. Sadece belirli bir sayıda kayıt çekme (limit) ---
    $limitedProducts = $qb->table('products')->select(['name'])->limit(2)->get();
    echo "Sadece 2 ürün:\n";
    foreach ($limitedProducts as $row) {
        echo "- {$row['name']}\n";
    }

    // --- 33. Farklı bir tablo ile join ve aggregate ---
    $categoryCounts = $qb->table('categories')
        ->select(['categories.name as category', 'COUNT(products.id) as product_count'])
        ->leftJoin('products', 'categories.id', '=', 'products.category_id')
        ->groupBy('categories.id')
        ->get();
    echo "Kategorilere göre ürün sayısı:\n";
    foreach ($categoryCounts as $row) {
        echo "- {$row['category']}: {$row['product_count']}\n";
    }

    // --- 34. Sıfırlanmış QueryBuilder ile yeni bir sorgu (reset sonrası) ---
    $qb->reset();
    $single = $qb->table('products')->select(['name'])->where('id', '=', 1)->first();
    echo "ID=1 olan ürün: " . ($single['name'] ?? 'Bulunamadı') . "\n";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}