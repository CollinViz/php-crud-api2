<?php
namespace Tqdev\PhpCrudApi\Meta;

use Tqdev\PhpCrudApi\Database\GenericDB;
use Tqdev\PhpCrudApi\Meta\Reflection\ReflectedColumn;
use Tqdev\PhpCrudApi\Meta\Reflection\ReflectedTable;

class DefinitionService
{
    private $db;
    private $reflection;

    public function __construct(GenericDB $db, ReflectionService $reflection)
    {
        $this->db = $db;
        $this->reflection = $reflection;
    }

    public function updateColumn(ReflectedTable $table, ReflectedColumn $column, /* object */ $columnChanges): void
    {

    }

}
