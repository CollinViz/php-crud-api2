<?php
namespace Tqdev\PhpCrudApi\Database;

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

    private function getColumnRenameSQL(ReflectedTable $table, ReflectedColumn $column, String $name, array &$parameters): String
    {
        switch ($this->driver) {
            case 'mysql':
                $p1 = $this->quote($table->getName());
                $p2 = $this->quote($column->getName());
                $p3 = $this->quote($name);
                return "ALTER TABLE $p1 CHANGE $p2 $p3 INTEGER NOT NULL";
            case 'pgsql':
                $p1 = $this->quote($table->getName());
                $p2 = $this->quote($column->getName());
                $p3 = $this->quote($name);
                return "ALTER TABLE $p1 RENAME COLUMN $p2 TO $p3";
            case 'sqlsrv':
                $parameters[] = $table->getName() . '.' . $column->getName();
                $parameters[] = $name;
                return "EXEC sp_rename ?, ?, 'COLUMN'";
        }
    }

    public function renameColumn(ReflectedTable $table, ReflectedColumn $column, String $newColumnName)
    {
        $parameters = [];
        $sql = $this->getColumnRenameSQL($table, $column, $newColumnName, $parameters);
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($parameters);
    }
}
