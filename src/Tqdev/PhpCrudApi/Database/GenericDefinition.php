<?php
namespace Tqdev\PhpCrudApi\Database;

use Tqdev\PhpCrudApi\Meta\Reflection\ReflectedColumn;

class GenericDefinition
{
    private $pdo;
    private $driver;
    private $database;
    private $typeConverter;

    public function __construct(\PDO $pdo, String $driver, String $database)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->database = $database;
        $this->typeConverter = new TypeConverter($driver);
    }

    private function quote(String $identifier): String
    {
        return '"' . str_replace('"', '', $identifier) . '"';
    }

    public function getColumnType(ReflectedColumn $column): String
    {
        $type = $this->typeConverter->fromJdbc($column->getType());
        if ($column->hasPrecision() && $column->hasScale()) {
            $type .= '(' . $column->getPrecision() . ',' . $column->getScale() . ')';
        } else if ($column->hasPrecision()) {
            $type .= '(' . $column->getPrecision() . ')';
        } else if ($column->hasLength()) {
            $type .= '(' . $column->getLength() . ')';
        }
        return $type;
    }

    private function getColumnNull(ReflectedColumn $column): String
    {
        return $column->getNullable() ? 'NULL' : 'NOT NULL';
    }

    private function getColumnRenameSQL(String $tableName, String $columnName, ReflectedColumn $newColumn, array &$parameters): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                $p4 = $this->getColumnType($newColumn);
                $p5 = $this->getColumnNull($newColumn);
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4 $p5";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                return "ALTER TABLE $p1 RENAME COLUMN $p2 TO $p3";
            case 'sqlsrv':
                $parameters[] = $tableName . '.' . $columnName;
                $parameters[] = $newColumn->getName();
                return "EXEC sp_rename ?, ?, 'COLUMN'";
        }
    }

    private function getColumnRetypeSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                $p4 = $this->getColumnType($newColumn);
                $p5 = $this->getColumnNull($newColumn);
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4 $p5";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->getColumnType($newColumn);
                return "ALTER TABLE $p1 ALTER COLUMN $p2 TYPE $p3";
            case 'sqlsrv':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->getColumnType($newColumn);
                $p4 = $this->getColumnNull($newColumn);
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p3 $p4";
        }
    }

    private function getSetColumnNullableSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                $p4 = $this->getColumnType($newColumn);
                $p5 = $this->getColumnNull($newColumn);
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4 $p5";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $newColumn->getNullable() ? 'DROP' : 'SET';
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p3 NOT NULL";
            case 'sqlsrv':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->getColumnType($newColumn);
                $p4 = $this->getColumnNull($newColumn);
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p3 $p4";
        }
    }

    public function renameColumn(String $tableName, String $columnName, ReflectedColumn $newColumn)
    {
        $parameters = [];
        $sql = $this->getColumnRenameSQL($tableName, $columnName, $newColumn, $parameters);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($parameters);
    }

    public function retypeColumn(String $tableName, String $columnName, ReflectedColumn $newColumn)
    {
        $sql = $this->getColumnRetypeSQL($tableName, $columnName, $newColumn);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    public function setColumnNullable(String $tableName, String $columnName, ReflectedColumn $newColumn)
    {
        $sql = $this->getSetColumnNullableSQL($tableName, $columnName, $newColumn);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
}
