<?php
namespace Tqdev\PhpCrudApi\Data;

class HabtmValues
{
    public $pkValues;
    public $fkValues;

    public function __construct(array $pkValues, array $fkValues)
    {
        $this->pkValues = $pkValues;
        $this->fkValues = $fkValues;
    }
}
