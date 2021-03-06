<?php
/**
 * lsys pagecache
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\PageCache\Storage;
use LSYS\PageCache\Storage;
class Redis implements Storage{
	protected $_prefix;
	/**
	 * @var \LSYS\Redis
	 */
	protected $_redis;
	public function __construct(string $prefix=null,\LSYS\Redis $redis=null){
		$this->_prefix=$prefix;
		$this->_redis=$redis?$redis:\LSYS\Redis\DI::get()->redis();
	}
	public function set(string $key,string $meta,string $data,int $time):bool{
        $redis=$this->_redis->configConnect();
		$redis->multi();
		$redis->setex($this->_prefix.$key,$time,$data);
		$redis->setex($this->_prefix.$key."_meta",$time,$meta);
		return (bool)$redis->exec();
	}
	/**
	 * get data from cache
	 * @param string $key
	 */
	public function getMeta(string $key){
	    $redis=$this->_redis->configConnect();
	    return $redis->get($this->_prefix.$key."_meta");
	}
	/**
	 * get data from cache
	 * @param string $key
	 */
	public function getData(string $key){
	    $redis=$this->_redis->configConnect();
	    if(!$redis->exists($this->_prefix.$key))return false;
	    return $redis->get($this->_prefix.$key);
	}
	public function lock(string $key):bool{
	    $redis=$this->_redis->configConnect();
		$incr=$redis->incr($this->_prefix.$key.'_lock');
		$redis->expire($this->_prefix.$key.'_lock',5);
		if($incr>1)return false;
		return true;
	}
	public function unlock(string $key):bool{
	    $redis=$this->_redis->configConnect();
		$redis->del($this->_prefix.$key.'_lock');
		return true;
	}
}