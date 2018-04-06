<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedColumn {
    
    protected $name;
    protected $nullable;
    protected $type;
    protected $length;
    protected $precision;
    protected $scale;

    public function __construct(array $columnResult) {
        $this->name = $columnResult['COLUMN_NAME'];
        $this->nullable = $columnResult['IS_NULLABLE'];
        $this->type = $columnResult['DATA_TYPE'];
        $this->length = $columnResult['CHARACTER_MAXIMUM_LENGTH'];
        $this->precision = $columnResult['NUMERIC_PRECISION'];
        $this->scale = $columnResult['NUMERIC_SCALE'];
    }

    public function getName(): String {
        return $this->name;
    }

    public function getNullable(): bool {
        return $this->nullable;
    }

    public function getType(): String {
        return $this->type;
    }

    public function getLength(): int {
        return $this->length;
    }

    public function getPrecision(): int {
        return $this->precision;
    }

    public function getScale(): int {
        return $this->scale;
    }

}