<?php
namespace Com\Tqdev\CrudApi\Api;

class PathTree {

	protected $values = array();

    protected $branches = array();

    public function getValues(): array {
        return $this->values;
    }

    public function put(array $path, $value) {
        if (count($path)==0) {
            $this->values[] = $value;
            return;
        }
        $key = array_shift($path);
        if (!isset($this->branches[$key])) {
            $this->branches[$key] = new PathTree();
        }
        $tree = $this->branches[$key];
        $tree->put($path, $value);
    }

    public function getKeys(): array {
        return array_keys($this->branches);
    }

    public function get($key): PathTree {
        return $this->branches[$key];
    }
}
