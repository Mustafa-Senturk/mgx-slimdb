<?php
namespace Mgx\Slimdb;

/**
 * ColumnDefinition sınıfı
 * Bu sınıf, veritabanı sütunlarının tanımını yapar.
 * Sütun adları, türleri ve seçenekleri gibi özellikleri içerir.
 * @package Mgx\SlimDB
 * @version 1.0.0
 * @author MicroMan -Mustafa Şentürk-
 */


class ColumnDefinition
{
    private $name;
    private $type;
    private $options = [
        'nullable' => false,
        'default' => null,
        'primary' => false,
        'unique' => false,
        'autoIncrement' => false,
        'unsigned' => false,
        'after' => null,
        'comment' => null
    ];

    public function __construct(string $name, string $type, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Sütun adını döndür
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * NULL olabilir
     * @return $this
     */
    public function nullable(): self
    {
        $this->options['nullable'] = true;
        return $this;
    }

    /**
     * NULL olamaz
     * @return $this
     */
    public function notNull(): self
    {
        $this->options['nullable'] = false;
        return $this;
    }

    /**
     * Varsayılan değer ata
     * @param mixed $value
     * @return $this
     */
    public function default($value): self
    {
        // Boolean için 0/1'e çevir
        if (is_bool($value)) {
            $value = $value ? 1 : 0;
        }
        // Fonksiyonel değerler için tırnak ekleme!
        if (is_string($value) && preg_match('/^(CURRENT_TIMESTAMP|NOW\(\))$/i', $value)) {
            $this->options['default'] = $value;
        } else {
            $this->options['default'] = $value;
        }
        return $this;
    }

    /**
     * Birincil anahtar yap
     * @return $this
     */
    public function primary(): self
    {
        $this->options['primary'] = true;
        return $this;
    }

    /**
     * Benzersiz yap
     * @return $this
     */
    public function unique(): self
    {
        $this->options['unique'] = true;
        return $this;
    }

    /**
     * Otomatik artan yap
     * @return $this
     */
    public function autoIncrement(): self
    {
        $this->options['autoIncrement'] = true;
        return $this;
    }

    /**
     * İşaretsiz yap
     * @return $this
     */
    public function unsigned(): self
    {
        $this->options['unsigned'] = true;
        return $this;
    }

    /**
     * Açıklama ekle
     * @param string $comment
     * @return $this
     */
    public function comment(string $comment): self
    {
        $this->options['comment'] = $comment;
        return $this;
    }

    /**
     * Birincil anahtar mı?
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->options['primary'];
    }

    /**
     * Sütunu başka bir sütundan sonra ekle
     * @param string $column
     * @return $this
     */
    public function after(string $column): self
    {
        $this->options['after'] = $column;
        return $this;
    }

    /**
     * Ham varsayılan değer ata
     * @param string $expression
     * @return $this
     */
    public function defaultRaw(string $expression): self
    {
        $this->options['default_raw'] = $expression;
        return $this;
    }

    /**
     * Sütun tanımını oluştur
     * @return string
     */
    public function build(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        // Unsigned
        if ($this->options['unsigned']) {
            $sql .= " UNSIGNED";
        }

        // Nullable
        if ($this->options['nullable']) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }

        // Default value
        if (isset($this->options['default_raw'])) {
            $sql .= " DEFAULT {$this->options['default_raw']}";
        } elseif ($this->options['default'] !== null) {
            // Fonksiyonel değerler için tırnak ekleme!
            if (is_string($this->options['default']) && preg_match('/^(CURRENT_TIMESTAMP|NOW\(\))$/i', $this->options['default'])) {
                $sql .= " DEFAULT {$this->options['default']}";
            } else {
                $sql .= " DEFAULT '{$this->options['default']}'";
            }
        }

        // Auto increment
        if ($this->options['autoIncrement']) {
            $sql .= " AUTO_INCREMENT";
        }

        // Comment
        if ($this->options['comment'] !== null) {
            $sql .= " COMMENT '{$this->options['comment']}'";
        }

        // Unique
        if ($this->options['unique']) {
            $sql .= " UNIQUE";
        }

        if ($this->options['after']) {
            $sql .= " AFTER `{$this->options['after']}`";
        }
        return $sql;
    }
}