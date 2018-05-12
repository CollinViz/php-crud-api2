<?php
namespace Com\Tqdev\CrudApi\Cache;

class RedisCache implements Cache
{
    protected $prefix;
    protected $redis;

    public function __construct(String $prefix, String $config)
    {
        $this->prefix = $prefix;
        if ($config == '') {
            $config = '127.0.0.1';
        }
        $params = explode(':', $config, 6);
        if (isset($params[3])) {
            $params[3] = null;
        }
        $this->redis = new \Redis();
        call_user_func_array(array($this->redis, 'pconnect'), $params);
        $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
    }

    public function set(String $key, $value, int $ttl = 0): bool
    {
        return $this->redis->set($this->prefix . $key, $value, $ttl);
    }

    public function get(String $key) /*: ?object*/
    {
        return $this->redis->get($this->prefix . $key) ?: null;
    }

    public function clear(): bool
    {
        return $this->redis->flushDb();
    }
}
