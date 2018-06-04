<?php
namespace Tqdev\PhpCrudApi\Meta;

use Tqdev\PhpCrudApi\Cache\Cache;
use Tqdev\PhpCrudApi\Database\GenericDB;
use Tqdev\PhpCrudApi\Meta\Reflection\ReflectedDatabase;
use Tqdev\PhpCrudApi\Meta\Reflection\ReflectedTable;

class ReflectionService
{
    private $db;
    private $cache;
    private $tables;

    public function __construct(GenericDB $db, Cache $cache, int $ttl)
    {
        $this->db = $db;
        $this->cache = $cache;
        $data = $this->cache->get('ReflectedDatabase');
        if ($data != '') {
            $this->tables = ReflectedDatabase::fromJson(json_decode(gzuncompress($data)));
        } else {
            $this->tables = ReflectedDatabase::fromReflection($db->reflection());
            $data = gzcompress(json_encode($this->tables, JSON_UNESCAPED_UNICODE));
            $this->cache->set('ReflectedDatabase', $data, $ttl);
        }
    }

    public function hasTable(String $table): bool
    {
        return $this->tables->exists($table);
    }

    public function getTable(String $table): ReflectedTable
    {
        return $this->tables->get($table);
    }

    public function getDatabase(): ReflectedDatabase
    {
        return $this->tables;
    }
}
