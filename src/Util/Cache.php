<?php

declare(strict_types=1);
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2024 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */
namespace Tuoluojiang\Baidubce\Util;

use Psr\SimpleCache\CacheInterface;
use Tuoluojiang\BaiduBce\Exception\BaiduBceException;

class Cache implements CacheInterface
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var array
     */
    protected $config;

    /**
     * Cache constructor.
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        try {
            $this->redis = new \Redis();

            $this->redis->connect($config['host'], (int) $config['port'], (int) $config['timeout']);

            if (! empty($config['password'])) {
                $this->redis->auth($config['password']);
            }

            if (isset($this->config['select']) && $this->config['select'] != 0) {
                $this->redis->select($this->config['select']);
            }
        } catch (\Throwable $e) {
            throw new BaiduBceException($e->getMessage());
        }
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->redis, $method], $args);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key   = $this->getCacheKey($key);
        $value = $this->unserialize($this->redis->get($key));

        return $value !== null ? $value : $default;
    }

    /**
     * @param null|\DateInterval|int $ttl
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $key   = $this->getCacheKey($key);
        $value = $this->serialize($value);

        return $this->redis->set($key, $value, $ttl);
    }

    /**
     *  删除单个.
     */
    public function delete(string $key): bool
    {
        return (bool) $this->redis->del($this->getCacheKey($key));
    }

    public function clear(): bool
    {
        $this->redis->flushDB();
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param null|\DateInterval|int $ttl
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $val) {
            $result = $this->set($key, $val, $ttl);

            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $result = $this->delete($key);

            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($this->getCacheKey($key));
    }

    /**
     * 序列化数据.
     * @param mixed $data 缓存数据
     */
    protected function serialize($data): string
    {
        if (is_numeric($data)) {
            return (string) $data;
        }

        $serialize = $this->config['redis']['serialize'][0] ?? 'serialize';

        return $serialize($data);
    }

    /**
     * 反序列化数据.
     * @param string $data 缓存数据
     * @return mixed
     */
    protected function unserialize(string $data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->config['redis']['serialize'][1] ?? 'unserialize';

        return $unserialize($data);
    }

    /**
     * @return string
     */
    protected function getCacheKey(string $key)
    {
        return $this->config['redis']['prefix'] . $key;
    }
}
