<?php
/**
 * Class Model
 *
 * Abstract base for every PDO-backed domain class (intent 003, unit
 * 001-oop-foundation, EF-1). Before this, ten classes each declared their
 * own `private PDO $db` and an identical constructor; they now inherit
 * the connection and a few prepared-statement helpers from here.
 *
 * Subclasses that need extra setup override the constructor and call
 * parent::__construct($dbConnection) first.
 */
abstract class Model {
    protected PDO $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Prepare and execute $sql with named or positional $params.
     * Integers bind as PDO::PARAM_INT (needed for LIMIT/OFFSET), nulls
     * as PDO::PARAM_NULL, booleans as PDO::PARAM_BOOL, the rest as strings.
     */
    protected function run(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) {
            $placeholder = is_int($name) ? $name + 1 : (strpos($name, ':') === 0 ? $name : ':' . $name);
            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = PDO::PARAM_NULL;
            } else {
                $type = PDO::PARAM_STR;
            }
            $stmt->bindValue($placeholder, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    /** First row as an associative array, or null when there is none. */
    protected function fetchRow(string $sql, array $params = []): ?array {
        $row = $this->run($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** All rows as associative arrays. */
    protected function fetchRows(string $sql, array $params = []): array {
        return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** First column of the first row (e.g. a COUNT(*)), or null. */
    protected function fetchValue(string $sql, array $params = []) {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /** Retrieve a single row by primary key from $table, or null when missing. */
    protected function findById(string $table, int $id, string $idColumn = 'id'): ?array {
        if ($id <= 0) {
            return null;
        }
        return $this->fetchRow("SELECT * FROM {$table} WHERE {$idColumn} = :id LIMIT 1", [':id' => $id]);
    }
}
