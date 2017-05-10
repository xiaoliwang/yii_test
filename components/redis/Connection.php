<?php
/**
 * Created by PhpStorm.
 * User: TomCao
 * Date: 2017/5/6
 * Time: 下午11:12
 */

namespace app\components\redis;

use Yii;
use Redis;
use RedisException;
use yii\base\Configurable;
use yii\base\InvalidParamException;

class Connection extends Redis implements Configurable
{
    public $hostname = 'localhost';
    public $port = 6379;
    public $unixSocket;
    public $password;
    public $database = 0;
    public $connectionTimeout = 0.0;

    private $part_len = 1000;
    private $parts = 10;

    const LINER = 1;
    const HASH = 2;

    public static function className()
    {
        return get_called_class();
    }

    public function __construct(array $config = [])
    {
        if ($config) {
            Yii::configure($this, $config);
        }
        $this->init();
    }

    public function init()
    {
        $this->_connect();
    }

    private function _connect()
    {
        if ($this->unixSocket) {
            $isConnected = $this->connect($this->unixSocket);
        } else {
            $isConnected = $this->pconnect($this->hostname, $this->port, $this->connectionTimeout);
        }

        if ($isConnected === false) throw new RedisException('Connection refused');

        if ($this->password) {
            $this->auth($this->password);
        }

        if ($this->database !== null) {
            $this->select($this->database);
        }

        if ($this->ping() !== '+PONG') throw new RedisException('NOAUTH Authentication Required.');
    }

    public function generateKey($pattern, ...$args): string
    {
        $str_count = substr_count($pattern, '*');
        $arg_count = count($args);
        if ($str_count !== $arg_count)
            throw new InvalidParamException("pattern expects $str_count arguments $arg_count given");
        $pattern = str_replace('*', '%s', $pattern);
        $key = sprintf($pattern, ...$args);
        return $key;
    }

    public function delPatternKeys($pattern)
    {
        $cursor = Null;
        do {
            $keys = $this->scan($cursor, $pattern, 1000);
            $this->del($keys);
        } while($cursor);
        return true;
    }

    // 分布式锁
    public function lock($lock_key, int $timeout)
    {
        $now = time();
        $timeout = $now + $timeout + 1;
        if ($this->setnx($lock_key, $timeout) || 
            ($now > $this->get($lock_key) && $now > $this->getSet($lock_key, $timeout))
        ) {
            return true;
        } 
        return false;
    }

    public function unlock($lock_key)
    {
        $now = time();
        if ($now < $this->get($lock_key)) {
            $this->del($lock_key);
            return true;
        }
        return false;
    }

    // hash 计数器
    public function incrCount($pattern, int $id, $type = self::LINER, int $increment = 1)
    {
        [$key, $sub_id] = $this->segmentKey($pattern, $id, $type);
        $this->hIncrBy($key, $sub_id, $increment);
    }

    public function getCount($pattern, $id, $type = self::LINER)
    {
        [$key, $sub_id] = $this->segmentKey($pattern, $id, $type);
        return $this->hGet($key, $sub_id);
    }

    public function resetCount($pattern, $id, $type = self::LINER)
    {
        [$key, $sub_id] = $this->segmentKey($pattern, $id, $type);
        while (true) {
            $this->watch($key);
            $ret = $this->multi()
                ->hGet($key, $sub_id)
                ->hDel($key, $sub_id)
                ->exec();
            if (false === $ret) continue;
            $this->watch($key);
            if (0 === $this->hlen($key)) {
                $this->multi()
                    ->del($key)
                    ->exec();
            } else {
                $this->unWatch();
            }
            return $ret[0];
        }
    }

    // 分段键
    private function segmentKey($pattern, $id, $type = self::LINER)
    {
        switch ($type) {
            case self::LINER:
                $seg = intdiv($id, $this->part_len);
                $sub_id = $id % $this->part_len;
                $key = $this->generateKey($pattern, $type, $seg);
                return [$key, $sub_id];
            case self::HASH:
                $sub_id = intdiv($id, $this->parts);
                $seg = $id % $this->parts;
                $key = $this->generateKey($pattern, $type, $seg);
                return [$key, $sub_id];
        }
    }
}