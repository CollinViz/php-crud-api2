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
        $type = $this->typeConverter->fromJdbc($column->getType(), $column->getPk());
        if ($column->hasPrecision() && $column->hasScale()) {
            $size = '(' . $column->getPrecision() . ',' . $column->getScale() . ')';
        } else if ($column->hasPrecision()) {
            $size = '(' . $column->getPrecision() . ')';
        } else if ($column->hasLength()) {
            $size = '(' . $column->getLength() . ')';
        } else {
            $size = '';
        }
        $null = $this->getColumnNullType($column);
        $auto = $this->getColumnAutoIncrement($column);
        return $type . $size . $null . $auto;
    }

    private function canAutoIncrement(ReflectedColumn $column): bool
    {
        return in_array($column->getType(), ['integer', 'bigint']);
    }

    private function getColumnAutoIncrement(ReflectedColumn $column): String
    {
        if (!$this->canAutoIncrement($column)) {
            return '';
        }
        switch ($this->driver) {
            case 'mysql':
                return $column->getPk() ? ' AUTO_INCREMENT' : '';
            case 'pgsql':
                return '';
            case 'sqlsrv':
                return $column->getPk() ? ' IDENTITY(1,1)' : '';
        }
    }

    private function getColumnNullType(ReflectedColumn $column): String
    {
        switch ($this->driver) {
            case 'mysql':
                return $column->getNullable() ? ' NULL' : ' NOT NULL';
            case 'pgsql':
                return '';
            case 'sqlsrv':
                return $column->getNullable() ? ' NULL' : ' NOT NULL';
        }
    }

    private function getTableRenameSQL(String $tableName, String $newTableName): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($newTableName);
                return "RENAME TABLE $p1 TO $p2";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($newTableName);
                return "ALTER TABLE $p1 RENAME TO $p2";
            case 'sqlsrv':
                $p1 = $this->pdo->quote($tableName);
                $p2 = $this->pdo->quote($newColumn->getName());
                return "EXEC sp_rename $p1, $p2";
        }
    }

    private function getColumnRenameSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                $p4 = $this->getColumnType($newColumn);
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                return "ALTER TABLE $p1 RENAME COLUMN $p2 TO $p3";
            case 'sqlsrv':
                $p1 = $this->pdo->quote($tableName . '.' . $columnName);
                $p2 = $this->pdo->quote($newColumn->getName());
                return "EXEC sp_rename $p1, $p2, 'COLUMN'";
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
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->getColumnType($newColumn);
                return "ALTER TABLE $p1 ALTER COLUMN $p2 TYPE $p3";
            case 'sqlsrv':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->getColumnType($newColumn);
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p3";
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
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $newColumn->getNullable() ? 'DROP NOT NULL' : 'SET NOT NULL';
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p3";
            case 'sqlsrv':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->getColumnType($newColumn);
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p3";
        }
    }

    private function getSetColumnPkConstraintSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $newColumn->getPk() ? "ADD PRIMARY KEY ($p2)" : 'DROP PRIMARY KEY';
                return "ALTER TABLE $p1 $p3";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($tableName . '_pkey');
                $p4 = $newColumn->getPk() ? "ADD PRIMARY KEY ($p2)" : "DROP CONSTRAINT $p3";
                return "ALTER TABLE $p1 $p4";
            case 'sqlsrv':
                //TODO: implement
        }
    }

    private function getSetColumnPkSequenceSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                return "select 1";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($tableName . '_' . $columnName . '_seq');
                return $newColumn->getPk() ? "CREATE SEQUENCE $p3 OWNED BY $p1.$p2" : "DROP SEQUENCE $p3";
            case 'sqlsrv':
                //TODO: implement
        }
    }

    private function getSetColumnPkSequenceStartSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                return "select 1";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->pdo->quote($tableName . '_' . $columnName . '_seq');
                return "SELECT setval($p3, (SELECT max($p2)+1 FROM $p1));";
            case 'sqlsrv':
                //TODO: implement
        }
    }

    private function getSetColumnPkDefaultSQL(String $tableName, String $columnName, ReflectedColumn $newColumn): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                $p3 = $this->quote($newColumn->getName());
                $p4 = $this->getColumnType($newColumn);
                return "ALTER TABLE $p1 CHANGE $p2 $p3 $p4";
            case 'pgsql':
                $p1 = $this->quote($tableName);
                $p2 = $this->quote($columnName);
                if ($newColumn->getPk()) {
                    $p3 = $this->pdo->quote($tableName . '_' . $columnName . '_seq');
                    $p4 = "SET DEFAULT nextval($p3)";
                } else {
                    $p4 = 'DROP DEFAULT';
                }
                return "ALTER TABLE $p1 ALTER COLUMN $p2 $p4";
            case 'sqlsrv':
                //TODO: implement
        }
    }

    public function renameTable(String $tableName, String $newTableName)
    {
        $sql = $this->getTableRenameSQL($tableName, $newTableName);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }

    public function renameColumn(String $tableName, String $columnName, ReflectedColumn $newColumn)
    {
        $sql = $this->getColumnRenameSQL($tableName, $columnName, $newColumn);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
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

    public function addColumnPrimaryKey(String $tableName, String $columnName, ReflectedColumn $newColumn)
    {
        $sql = $this->getSetColumnPkConstraintSQL($tableName, $columnName, $newColumn);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        if ($this->canAutoIncrement($newColumn)) {
            $sql = $this->getSetColumnPkSequenceSQL($tableName, $columnName, $newColumn);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $sql = $this->getSetColumnPkSequenceStartSQL($tableName, $columnName, $newColumn);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $sql = $this->getSetColumnPkDefaultSQL($tableName, $columnName, $newColumn);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        return true;
    }

    public function removeColumnPrimaryKey(String $tableName, String $columnName, ReflectedColumn $newColumn)
    {
        if ($this->canAutoIncrement($newColumn)) {
            $sql = $this->getSetColumnPkDefaultSQL($tableName, $columnName, $newColumn);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $sql = $this->getSetColumnPkSequenceSQL($tableName, $columnName, $newColumn);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        $sql = $this->getSetColumnPkConstraintSQL($tableName, $columnName, $newColumn);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return true;
    }
}
