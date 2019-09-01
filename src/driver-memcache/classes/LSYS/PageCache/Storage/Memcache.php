<?php
/**
 * lsys pagecache
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\PageCache\Storage;
use LSYS\PageCache\Storage;
class Memcache implements Storage{
	/**
	 * @var \LSYS\Memcache
	 */
	protected $_memcache;
	protected $_prefix;

	public function __construct($prefix=null,\LSYS\Memcache $memcache=NULL){
		$this->_prefix=$prefix;
		$this->_memcache=$memcache?$memcache:\LSYS\Memcache\DI::get()->memcache();
	}
	public function set($key,$meta,$data,$time){
	    $this->_memcache->configServers();
	    $memcache=$this->_memcache;
	    return $memcache->set($this->_prefix.$key,$data,false, $time)
	    &&$memcache->set($this->_prefix.$key."_meta",$meta,false, $time);
	}
	/**
	 * get data from cache
	 * @param string $key
	 */
	public function getMeta($key){
	    $this->_memcache->configServers();
	    return $this->_memcache->get($this->_prefix.$key."_meta");
	}
	/**
	 * get data from cache
	 * @param string $key
	 */
	public function getData($key){
	    $this->_memcache->configServers();
	    return $this->_memcache->get($this->_prefix.$key);
	}
	public function lock($key){
	    $this->_memcache->configServers();
	    $incr=$this->_memcache->increment($this->_prefix.$key.'_lock',5);
		if($incr>1)return false;
		return true;
	}
	public function unlock($key){
	    $this->_memcache->configServers();
	    $this->_memcache->del($this->_prefix.$key.'_lock');
		return true;
	}
}