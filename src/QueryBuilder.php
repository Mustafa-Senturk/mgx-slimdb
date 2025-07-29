<?php
namespace Mgx\Slimdb;
use PDO;
use PDOException;
use PDOStatement;
use InvalidArgumentException;
use RuntimeException;
use Mgx\Slimdb\Connect;

/***
     * Sorgu oluşturma sınıfı QueryBuilder
     * 
     * Bu sınıf, SQL sorgularını programatik olarak oluşturmak için kullanılacaktır.
     * @package Mgx\SlimDB
     * @version 1.0.0
     * @author MicroMan -Mustafa Şentürk- 
     */



class QueryBuilder
{
    private $connection;
    private $query;
    private $bindings = [];
    private $type = 'select';
    
    // SELECT için bileşenler
    private $columns = [];
    private $table;
    private $wheres = [];
    private $joins = [];
    private $orders = [];
    private $limit;
    private $offset;
    private $distinct = false;
    private $groupBy = [];
    private $having = [];
    
    // INSERT/UPDATE için
    private $data = [];

    public function __construct(Connect $connection)
    {
        $this->connection = $connection;
    }

    // Tablo ayarlama ve otomatik reset
    public function table(string $table): self
    {
        $this->reset();
        $this->table = $table;
        return $this;
    }

    // SELECT metodları
    public function select(array $columns = ['*']): self
    {
        $this->type = 'select';
        $this->columns = $columns;
        return $this;
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    // WHERE koşulları
    public function where(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        if (!in_array($operator, ['=', '<>', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IS', 'IS NOT'])) {
            throw new InvalidArgumentException("Geçersiz operatör: {$operator}");
        }
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        $this->addBinding($value);
        return $this;
    }

    public function orWhere(string $column, string $operator, $value): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        $this->addBindings($values);
        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean
        ];
        return $this;
    }

    public function orWhereNull(string $column): self
    {
        return $this->whereNull($column, 'OR');
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => $boolean
        ];
        return $this;
    }

    public function orWhereNotNull(string $column): self
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        $this->addBindings($values);
        return $this;
    }

    public function orWhereNotIn(string $column, array $values): self
    {
        return $this->whereNotIn($column, $values, 'OR');
    }

    // JOIN işlemleri
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    // Sıralama ve limit
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new InvalidArgumentException("Geçersiz sıralama yönü: {$direction}");
        }

        $this->orders[] = compact('column', 'direction');
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    public function having(string $column, string $operator, $value, string $boolean = 'AND'): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        $this->addBinding($value);
        return $this;
    }
    /***Limit belirleme işlemleri */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    /*** Offset  işlemleri */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // INSERT işlemleri
    public function insert(array $data): bool
    {
        $this->type = 'insert';
        $this->data = $data;
        return $this->execute();
    }

    // UPDATE işlemleri
    public function update(array $data): int
    {
        $this->type = 'update';
        $this->data = $data;
        return $this->execute();
    }

    // DELETE işlemi
    public function delete(): int
    {
        $this->type = 'delete';
        return $this->execute();
    }

    // Sorguyu çalıştırma
    public function get(): array
    {
        $this->type = 'select';
        $stmt = $this->executeStatement();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $this->select(['COUNT(*) as count']);
        $result = $this->first();
        return (int)($result['count'] ?? 0);
    }

    public function paginate(int $perPage = 15, int $currentPage = 1): array
    {
        // Orijinal sorguyu klonla
        $totalBuilder = clone $this;
        $totalBuilder->columns = ['COUNT(*) as count'];
        $totalBuilder->orders = [];
        $totalBuilder->limit = null;
        $totalBuilder->offset = null;

        $totalResult = $totalBuilder->first();
        $total = (int)($totalResult['count'] ?? 0);
        
        // Toplam sayfa sayısını hesapla
        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;
        
        // Geçerli sayfayı sınırla
        $currentPage = max(1, min($currentPage, $totalPages));
        
        // OFFSET hesapla
        $offset = ($currentPage - 1) * $perPage;
        
        // Verileri çek
        $results = $this->limit($perPage)
                        ->offset($offset)
                        ->get();
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => $total > 0 ? min($offset + $perPage, $total) : 0,
            'data' => $results
        ];
    }

    // Sorgu oluşturma
    private function buildQuery(): string
    {
        return match ($this->type) {
            'select' => $this->buildSelectQuery(),
            'insert' => $this->buildInsertQuery(),
            'update' => $this->buildUpdateQuery(),
            'delete' => $this->buildDeleteQuery(),
            default => throw new InvalidArgumentException("Geçersiz sorgu tipi: {$this->type}")
        };
    }

    private function buildSelectQuery(): string
    {
        $distinct = $this->distinct ? 'DISTINCT ' : '';
        $columns = empty($this->columns) ? '*' : implode(', ', $this->columns);
        
        $sql = "SELECT {$distinct}{$columns} FROM {$this->table}";

        // JOIN'ler
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // WHERE koşulları
        $sql .= $this->buildWhereClause();

        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // HAVING
        if (!empty($this->having)) {
            $sql .= ' HAVING ';
            $havingClauses = [];
            
            foreach ($this->having as $index => $having) {
                $clause = $index > 0 ? " {$having['boolean']} " : '';
                $clause .= "{$having['column']} {$having['operator']} ?";
                $havingClauses[] = $clause;
            }
            
            $sql .= implode('', $havingClauses);
        }

        // Sıralama
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ';
            $orderClauses = [];
            
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order['column']} {$order['direction']}";
            }
            
            $sql .= implode(', ', $orderClauses);
        }

        // Limit ve offset
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            
            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }

    private function buildWhereClause(): string
    {
        if (empty($this->wheres)) return '';

        $sql = ' WHERE ';
        $whereClauses = [];
        
        foreach ($this->wheres as $where) {
            $clause = '';
            
            if (!empty($whereClauses)) {
                $clause .= " {$where['boolean']} ";
            }
            
            switch ($where['type']) {
                case 'basic':
                    $clause .= "{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(',', array_fill(0, count($where['values']), '?'));
                    $clause .= "{$where['column']} IN ({$placeholders})";
                    break;
                case 'null':
                    $clause .= "{$where['column']} IS NULL";
                    break;
                case 'not_null':
                    $clause .= "{$where['column']} IS NOT NULL";
                    break;
                case 'not_in':
                    $placeholders = implode(',', array_fill(0, count($where['values']), '?'));
                    $clause .= "{$where['column']} NOT IN ({$placeholders})";
                    break;
            }
            
            $whereClauses[] = $clause;
        }
        
        return $sql . implode('', $whereClauses);
    }

    private function buildInsertQuery(): string
    {
        $columns = implode(', ', array_keys($this->data));
        $placeholders = implode(', ', array_fill(0, count($this->data), '?'));
        
        return "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
    }

    private function buildUpdateQuery(): string
    {
        $sql = "UPDATE {$this->table} SET ";
        $sets = [];
        
        foreach (array_keys($this->data) as $column) {
            $sets[] = "{$column} = ?";
        }
        
        $sql .= implode(', ', $sets);
        $sql .= $this->buildWhereClause();
        
        return $sql;
    }

    private function buildDeleteQuery(): string
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->buildWhereClause();
        return $sql;
    }

    // Bağlama işlemleri
    private function addBinding($value): void
    {
        $this->bindings[] = $value;
    }

    private function addBindings(array $values): void
    {
        $this->bindings = array_merge($this->bindings, $values);
    }

    private function prepareBindings(): array
    {
        $bindings = [];
        
        // INSERT için veriler
        if ($this->type === 'insert') {
            $bindings = array_values($this->data);
        }
        
        // UPDATE için veriler + where koşulları
        elseif ($this->type === 'update') {
            $bindings = array_values($this->data);
            
            foreach ($this->wheres as $where) {
                if ($where['type'] === 'basic') {
                    $bindings[] = $where['value'];
                } elseif ($where['type'] === 'in') {
                    $bindings = array_merge($bindings, $where['values']);
                }
            }
        }
        
        // DELETE ve SELECT için sadece where koşulları
        else {
            foreach ($this->wheres as $where) {
                if ($where['type'] === 'basic') {
                    $bindings[] = $where['value'];
                } elseif ($where['type'] === 'in') {
                    $bindings = array_merge($bindings, $where['values']);
                }
            }
            
            // HAVING koşulları
            foreach ($this->having as $having) {
                $bindings[] = $having['value'];
            }
        }
        
        return $bindings;
    }

    // Sorguyu çalıştır
    private function executeStatement(): PDOStatement
    {
        $sql = $this->buildQuery();
        $bindings = $this->prepareBindings();
        
        return $this->connection->query($sql, $bindings);
    }

    private function execute()
    {
        $stmt = $this->executeStatement();
        
        return match ($this->type) {
            'insert' => $stmt->rowCount() > 0,
            'update', 'delete' => $stmt->rowCount(),
            default => true
        };
    }

    // Debug için SQL sorgusunu göster
    public function toSql(): string
    {
        return $this->buildQuery();
    }

    // Sorguyu sıfırla
    public function reset(): void
    {
        $this->type = 'select';
        $this->columns = [];
        $this->table = null;
        $this->wheres = [];
        $this->joins = [];
        $this->orders = [];
        $this->groupBy = [];
        $this->having = [];
        $this->limit = null;
        $this->offset = null;
        $this->distinct = false;
        $this->data = [];
        $this->bindings = [];
    }

    // Son eklenen ID
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }
}