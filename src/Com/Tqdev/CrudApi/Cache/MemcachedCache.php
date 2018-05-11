<?php
namespace Com\Tqdev\CrudApi\Cache;

class MemcachedCache implements Cache
{
    const PREFIX = 'phpcrudapi-';

    protected $prefix;
    protected $memcache;

    public function __construct(String $config)
    {
        $this->init($config);
    }

    protected function create(): object
    {
        return new \Memcached();
    }

    private function init(String $config): void
    {
        if ($config == '') {
            $address = 'localhost';
            $port = 11211;
        } elseif (strpos($config, ':') === false) {
            $address = $config;
            $port = 11211;
        } else {
            list($address, $port) = explode(':', $config);
        }
        $id = substr(md5(__FILE__), 0, 8);
        $this->prefix = self::PREFIX . $id . '-';
        $this->memcache = $this->create();
        $this->memcache->addServer($address, $port);
    }

    public function set(String $key, $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, $ttl);
    }

    public function get(String $key) /*: ?object*/
    {
        return $this->memcache->get($this->prefix . $key) ?: null;
    }

    public function clear(): bool
    {
        return $this->memcache->flush();
    }
}
