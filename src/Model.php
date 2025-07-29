<?php
namespace Mgx\Slimdb;

use RuntimeException;
use InvalidArgumentException;

/***
 * Model sınıfı
 * 
 * Bu sınıf, veritabanı tablolarıyla etkileşimde bulunmak için temel bir model sağlar.
 * Model sınıfı, veritabanı işlemlerini kolaylaştırmak için temel CRUD (Create, Read, Update, Delete) işlevselliğini içerir.
 * Ayrıca, otomatik zaman damgaları, toplu atama ve gizleme gibi özellikler sunar.
 * @package MGX\SlimDB
 * @version 1.0.0
 * @author MGX
 */

abstract class Model
{
    /**
     * Tablo adı (model sınıfından türetilen sınıf tarafından tanımlanmalıdır)
     * @var string
     */
    protected static $table;

    /**
     * Birincil anahtar (varsayılan: 'id')
     * @var string
     */
    protected static $primaryKey = 'id';

    /**
     * Otomatik zaman damgaları (created_at, updated_at) kullanılsın mı?
     * @var bool
     */
    protected static $timestamps = true;

    /**
     * Toplu atama (mass assignment) için izin verilen alanlar
     * @var array
     */
    protected static $fillable = [];

    /**
     * JSON çıktısında gizlenecek alanlar
     * @var array
     */
    protected static $hidden = [];

    /**
     * Model öznitelikleri
     * @var array
     */
    protected $attributes = [];

    /**
     * Orijinal öznitelikler (değişiklik takibi için)
     * @var array
     */
    protected $original = [];

    /**
     * QueryBuilder örneği
     * @var QueryBuilder
     */
    protected static $queryBuilder;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    /**
     * QueryBuilder örneğini ayarlar
     * @param QueryBuilder $queryBuilder
     */
    public static function setQueryBuilder(QueryBuilder $queryBuilder): void
    {
        static::$queryBuilder = $queryBuilder;
    }

    /**
     * QueryBuilder örneği döndürür
     * @return QueryBuilder
     */
    protected static function builder(): QueryBuilder
    {
        if (!static::$queryBuilder) {
            throw new RuntimeException('QueryBuilder instance not set');
        }
        
        return static::$queryBuilder->table(static::getTable());
    }

    /**
     * Tablo adını döndürür
     * @return string
     */
    public static function getTable(): string
    {
        if (!static::$table) {
            // Model sınıf adından tablo adını türet (ör: User -> users)
            $className = (new \ReflectionClass(static::class))->getShortName();
            static::$table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
        }

        return static::$table;
    }

    /**
     * Öznitelikleri doldur (toplu atama)
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            // Sadece fillable tanımlıysa ve key fillable içinde ise veya fillable boşsa
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Öznitelik değeri atar
     * @param string $key
     * @param mixed $value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Öznitelik değeri döndürür
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }

        return $this->attributes[$key];
    }

    /**
     * Tüm öznitelikleri döndürür (hidden hariç)
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        foreach (static::$hidden as $hidden) {
            unset($attributes[$hidden]);
        }
        return $attributes;
    }

    /**
     * Modeli veritabanına kaydet (create veya update)
     * @return bool
     */
    public function save(): bool
    {
        if (static::$timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!$this->getAttribute(static::$primaryKey)) {
                $this->setAttribute('created_at', $now);
            }
            $this->setAttribute('updated_at', $now);
        }

        // Eğer primaryKey değeri varsa update, yoksa insert
        if ($this->getAttribute(static::$primaryKey)) {
            return $this->updateModel();
        }

        return $this->insertModel();
    }

    /**
     * Yeni model oluştur
     * @return bool
     */
    private function insertModel(): bool
    {
        $attributes = $this->toArray();
        unset($attributes[static::$primaryKey]); // ID'yi kaldır

        $success = static::builder()->insert($attributes);

        if ($success) {
            $this->setAttribute(static::$primaryKey, static::builder()->lastInsertId());
            $this->syncOriginal();
        }

        return $success;
    }

    /**
     * Modeli güncelle
     * @return bool
     */
    private function updateModel(): bool
    {
        $attributes = $this->getDirty(); // Sadece değişen alanlar

        if (empty($attributes)) {
            return true; // Hiçbir değişiklik yok
        }

        $primaryKeyValue = $this->getAttribute(static::$primaryKey);
        $updated = static::builder()
            ->where(static::$primaryKey, '=', $primaryKeyValue)
            ->update($attributes);

        if ($updated) {
            $this->syncOriginal();
        }

        return $updated > 0;
    }

    /**
     * Modeli sil
     * @return bool
     */
    public function delete(): bool
    {
        $primaryKeyValue = $this->getAttribute(static::$primaryKey);
        if (!$primaryKeyValue) {
            return false;
        }

        $deleted = static::builder()
            ->where(static::$primaryKey, '=', $primaryKeyValue)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Değişen öznitelikleri döndür
     * @return array
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Orijinal öznitelikleri güncelle
     */
    public function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // --- Statik Sorgu Metodları ---

    /**
     * Tüm kayıtları döndürür
     * @return static[]
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Belirtilen birincil anahtar değerine sahip modeli bul
     * @param int|string $id
     * @return static|null
     */
    public static function find($id): ?self
    {
        $result = static::query()->where(static::$primaryKey, '=', $id)->first();
        
        if (!$result) {
            return null;
        }
        
        return static::newFromBuilder($result);
    }

    /**
     * Sonuç kümesinden model oluştur
     * @param array $attributes
     * @return static
     */
    public static function newFromBuilder(array $attributes): self
    {
        $model = new static();
        $model->fill($attributes);
        $model->syncOriginal();
        return $model;
    }

    /**
     * Sorgu oluşturucu başlat
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return static::builder();
    }

    /**
     * Yeni model örneği oluştur ve kaydet
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes): self
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    // --- Sihirli Metodlar ---

    public function __get(string $name)
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, $value)
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name)
    {
        return isset($this->attributes[$name]);
    }

    public function __unset(string $name)
    {
        unset($this->attributes[$name]);
    }
}