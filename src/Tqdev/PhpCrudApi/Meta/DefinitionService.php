<?php
namespace Tqdev\PhpCrudApi\Meta;

use Tqdev\PhpCrudApi\Database\GenericDB;
use Tqdev\PhpCrudApi\Meta\Reflection\ReflectedColumn;

class DefinitionService
{
    private $db;
    private $reflection;

    public function __construct(GenericDB $db, ReflectionService $reflection)
    {
        $this->db = $db;
        $this->reflection = $reflection;
    }

    public function updateColumn(String $tableName, String $columnName, /* object */ $changes): bool
    {
        $table = $this->reflection->getTable($tableName);
        $column = $table->get($columnName);
        $newColumn = ReflectedColumn::fromJson((object) array_merge((array) $column->jsonSerialize(), (array) $changes));
        if ($newColumn->getName() != $column->getName()) {
            if (!$this->db->definition()->renameColumn($table->getName(), $column->getName(), $newColumn)) {
                return false;
            }
        }
        if (!$column->hasSameType($newColumn)) {
            if (!$this->db->definition()->retypeColumn($table->getName(), $newColumn->getName(), $newColumn)) {
                return false;
            }
        }
        return true;
    }

}
