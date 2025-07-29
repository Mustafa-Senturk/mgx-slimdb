<?php
namespace Mgx\Slimdb;

/**
 * TableBlueprint sınıfı
 * Bu sınıf, veritabanı tablolarının yapısını tanımlamak için kullanılır.
 * Sütunlar, indexler ve foreign key'ler gibi özellikleri tanımlamak için metotlar içerir.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan -Mustafa Şentürk-
 */


class TableBlueprint
{
    /**
     * Tablo adı
     * @var string
     */
    private $tableName;

    /**
     * Sütun tanımları
     * @var array
     */
    private $columns = [];

    /**
     * Index tanımları
     * @var array
     */
    private $indexes = [];

    /**
     * Foreign key tanımları
     * @var array
     */
    private $foreignKeys = [];

    /**
     * Tablo seçenekleri
     * @var array
     */
    private $options = [];

    /**
     * Değişiklik için sütunlar
     * @var array
     */
    private $changes = [];

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * Yeni sütun ekle
     * @param string $name
     * @param string $type
     * @param array $options
     * @return ColumnDefinition
     */
    public function column(string $name, string $type, array $options = []): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $options);
        $this->columns[$name] = $column;
        return $column;
    }

    /**
     * ID sütunu ekle
     * @param string $name
     * @return ColumnDefinition
     */
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->column($name, 'bigint')
            ->unsigned()
            ->autoIncrement()
            ->primary();
    }

    /**
     * String sütunu ekle
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->column($name, "varchar($length)");
    }

    /**
     * Text sütunu ekle
     * @param string $name
     * @return ColumnDefinition
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->column($name, 'text');
    }

    /**
     * Integer sütunu ekle
     * @param string $name
     * @return ColumnDefinition
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->column($name, 'int');
    }

    /**
     * Decimal sütunu ekle
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return ColumnDefinition
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->column($name, "decimal($precision,$scale)");
    }

    /**
     * Float sütunu ekle
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return ColumnDefinition
     */
    public function float(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->column($name, "float($precision,$scale)");
    }

    /**
     * Double sütunu ekle
     * @param string $name
     * @return ColumnDefinition
     */
    public function double(string $name): ColumnDefinition
    {
        return $this->column($name, 'double');
    }

    /**
     * Big integer sütunu ekle
     * @param string $name
     * @return ColumnDefinition
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->column($name, 'bigint');
    }

    /**
     * Tarih-zaman sütunu ekle
     * @param string $name
     * @return ColumnDefinition
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->column($name, 'timestamp');
    }

    /**
     * Zaman damgası sütunları ekle (created_at, updated_at)
     */
    public function timestamps(): void
    {
        $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->defaultRaw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    /**
     * Index ekle
     * @param string|array $columns
     * @param string|null $name
     */
    public function index($columns, ?string $name = null): void
    {
        $this->indexes[] = [
            'type' => 'index',
            'columns' => (array)$columns,
            'name' => $name
        ];
    }

    /**
     * Benzersiz index ekle
     * @param string|array $columns
     * @param string|null $name
     */
    public function unique($columns, ?string $name = null): void
    {
        $this->indexes[] = [
            'type' => 'unique',
            'columns' => (array)$columns,
            'name' => $name
        ];
    }

    /**
     * Foreign key ekle
     * @param string $column
     * @param string $references
     * @param string $onTable
     * @param string $onDelete
     * @param string $onUpdate
     */
    public function foreign(
        string $column,
        string $onTable,
        string $references = 'id',
        string $onDelete = 'RESTRICT',
        string $onUpdate = 'CASCADE'
    ): void {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => $references,
            'onTable' => $onTable,
            'onDelete' => $onDelete,
            'onUpdate' => $onUpdate
        ];
    }

    /**
     * Tablo seçeneği ekle
     * @param string $key
     * @param mixed $value
     */
    public function option(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    /**
     * Tablo oluşturma SQL'i oluştur
     * @return string
     */
    public function buildCreateTable(): string
    {
        $sql = "CREATE TABLE `{$this->tableName}` (\n";

        // Sütunlar
        $columns = [];
        foreach ($this->columns as $column) {
            $columns[] = "  " . $column->build();
        }

        // Primary key
        $primaryKeys = [];
        foreach ($this->columns as $column) {
            if ($column->isPrimary()) {
                $primaryKeys[] = $column->getName();
            }
        }

        if (!empty($primaryKeys)) {
            $columns[] = "  PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }

        // Indexler
        foreach ($this->indexes as $index) {
            $type = strtoupper($index['type']);
            $name = $index['name'] ?? $this->generateIndexName($index['columns'], $type);
            if ($type === 'UNIQUE') {
                $columns[] = "  UNIQUE KEY `{$name}` (`" . implode('`, `', $index['columns']) . "`)";
            } else {
                $columns[] = "  KEY `{$name}` (`" . implode('`, `', $index['columns']) . "`)";
            }
        }

        // Foreign key'ler
        foreach ($this->foreignKeys as $fk) {
            $name = $this->generateForeignKeyName($fk['column']);
            $columns[] = "  CONSTRAINT `{$name}` FOREIGN KEY (`{$fk['column']}`) "
                . "REFERENCES `{$fk['onTable']}` (`{$fk['references']}`) "
                . "ON DELETE {$fk['onDelete']} ON UPDATE {$fk['onUpdate']}";
        }

        $sql .= implode(",\n", $columns) . "\n)";

        // Tablo seçenekleri
        if (!empty($this->options)) {
            $options = [];
            foreach ($this->options as $key => $value) {
                $options[] = strtoupper($key) . "=$value";
            }
            $sql .= " " . implode(' ', $options);
        }

        return $sql . ';';
    }

    public function boolean(string $name): ColumnDefinition
    {
        // MySQL'de boolean = tinyint(1)
        return $this->column($name, 'tinyint(1)');
    }


    /**
     * Tablo değiştirme SQL'i oluştur
     * @return string
     */
    public function buildAlterTable(): string
    {
        $sql = "ALTER TABLE `{$this->tableName}`\n";

        $changes = [];

        // Sütun ekleme
        foreach ($this->columns as $column) {
            $changes[] = "ADD COLUMN " . $column->build();
        }

        // Index ekleme
        foreach ($this->indexes as $index) {
            $type = strtoupper($index['type']);
            $name = $index['name'] ?? $this->generateIndexName($index['columns'], $type);
            $changes[] = "ADD {$type} INDEX `{$name}` (`" . implode('`, `', $index['columns']) . "`)";
        }

        // Foreign key ekleme
        foreach ($this->foreignKeys as $fk) {
            $name = $this->generateForeignKeyName($fk['column']);
            $changes[] = "ADD CONSTRAINT `{$name}` FOREIGN KEY (`{$fk['column']}`) "
                . "REFERENCES `{$fk['onTable']}` (`{$fk['references']}`) "
                . "ON DELETE {$fk['onDelete']} ON UPDATE {$fk['onUpdate']}";
        }

        // Sütun değişiklikleri
        foreach ($this->changes as $change) {
            $changes[] = $change;
        }

        return $sql . implode(",\n", $changes) . ';';
    }

    /**
     * Sütun değiştir
     * @param string $column
     * @param string $type
     * @param array $options
     * @return ColumnDefinition
     */
    public function change(string $column, string $type, array $options = []): ColumnDefinition
    {
        $col = new ColumnDefinition($column, $type, $options);
        $this->changes[] = "MODIFY COLUMN " . $col->build();
        return $col;
    }

    /**
     * Sütun sil
     * @param string $column
     */
    public function dropColumn(string $column): void
    {
        $this->changes[] = "DROP COLUMN `{$column}`";
    }

    /**
     * Index sil
     * @param string $indexName
     */
    public function dropIndex(string $indexName): void
    {
        $this->changes[] = "DROP INDEX `{$indexName}`";
    }

    /**
     * Foreign key sil
     * @param string $fkName
     */
    public function dropForeign(string $fkName): void
    {
        $this->changes[] = "DROP FOREIGN KEY `{$fkName}`";
    }

    /**
     * Index adı oluştur
     * @param array $columns
     * @param string $type
     * @return string
     */
    private function generateIndexName(array $columns, string $type = ''): string
    {
        $prefix = $type ? strtolower($type) . '_' : '';
        return $prefix . $this->tableName . '_' . implode('_', $columns) . '_idx';
    }

    /**
     * Foreign key adı oluştur
     * @param string $column
     * @return string
     */
    private function generateForeignKeyName(string $column): string
    {
        return "fk_{$this->tableName}_{$column}";
    }
}