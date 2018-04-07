<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\CrudMetaService;
use Com\Tqdev\CrudApi\Api\ColumnSelector;

class CrudApiService {

    protected $db;
    protected $tables;
    protected $columns;

    public function __construct(GenericDB $db, CrudMetaService $meta) {
        $this->db = $db;
        $this->tables = $meta->getDatabaseReflection();
        $this->columns = new ColumnSelector();
    }

    public function exists(String $table): bool {
        return $this->tables->exists($table);
    }

    public function read(String $tableName, String $id, array $params)/*: ?\stdClass*/ {
        $table = $this->tables->get($tableName);
        $columnNames = $this->columns->names($table, true, $params);
        return $this->db->selectSingle($table, $columnNames, $id);
    }

    public function list(String $tableName, array $params): array {
        $table = $this->tables->get($tableName);
        $columnNames = $this->columns->names($table, true, $params);
        return $this->db->selectAll($table, $columnNames);
    }
}