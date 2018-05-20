<?php
namespace Tqdev\PhpCrudApi\Cache;

class MemcacheCached extends MemcacheCache
{
    protected function create(): object
    {
        return new \Memcached();
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, $ttl);
    }
}
