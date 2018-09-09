<?php
/**
 * lsys pagecache
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS;
use LSYS\PageCache\Storage;
use LSYS\PageCache\Output\DefaultOutput;
use LSYS\PageCache\Output;

class PageCache{
	/**
	 * @var PageCache
	 */
	protected static $_instance;
	/**
	 * 初始化缓存处理
	 * @param Storage $cache
	 * @param Output $output
	 */
	public static function init(Storage $cache,Output $output=null){
		if ($output==null)$output=new DefaultOutput();
		self::$_instance=new static($cache,$output);
	}
	/**
	 * 检查是否缓存并替换动态内如
	 * @param string $key 缓存KEY
	 * @param number $time 缓存不可用时,缓存存储时间
	 * @param array $replace 动态内容替换
	 * @param int $level 缓存方式 
	 */
	public static function cache($key,$time=10860,$replace=array(),$level=NULL){
		if (!self::$_instance)return ;
		self::$_instance->param($key,$replace,$level)->check()->start($time);
	}
	protected static $headers=array();
	/**
	 * 缓存时,添加需要进行缓存的消息头 
	 * @param string $key
	 * @param string $value
	 */
	public static function cache_header($key,$value=null){
		if (is_array($key)) foreach ($key as $k=>$v)self::$headers[$k]=$v;
		else self::$headers[$key]=$value;
		foreach (self::$headers as $k=>$v){
			if ($v==null)unset(self::$headers[$k]);
		}
	}
	/**
	 * init page cache
	 * @param static $cache
	 */
	/**
	 * 页面操作完成时,是否进行缓存的处理函数
	 * @param string $status 是否对输出进行缓存
	 */
	public static function shutdown($status=null){
		if (!self::$_instance)return;
		if ($status===null){
			$error = error_get_last();
			$status=(!isset($error['type'])||!in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR)))&&http_response_code()==200;
		}
		
		if ($status) self::$_instance->end(self::$headers);
		else self::$_instance->cancel();
	}
	/**
	 * @var Storage
	 */
	protected $_cache;
	/**
	 * @var Output
	 */
	protected $_output;
	protected $_param;
	protected $_time=0;
	protected $_is_lock=false;
	protected $_replace=array();
	protected $_is_check=false;
	protected $_level;
	/**
	 * 是否在服务器缓存
	 * @var integer
	 */
	const LEVEL_SERVER=1<<0;
	/**
	 * 是否在客户端缓存
	 * @var int
	 */
	const LEVEL_CLIENT=1<<1;
	
	/**
	 * page cache
	 * @param Storage $cache
	 * @param array $param
	 */
	public function __construct(Storage $cache,Output $output){
		$this->_cache=$cache;
		$this->_output=$output;
	}
	/**
	 * 缓存KEY参数
	 * @param array $param
	 * @return \LSYS\PageCache
	 */
	public function param($key,$replace=array(),$level=NULL){
		if (is_array($key)){
			$this->_filter($key);
			$key=json_encode($key);
		}
		if ($level===null)$level=self::LEVEL_CLIENT|self::LEVEL_SERVER;
		$this->_param=$key;
		$this->_replace=$replace;
		$this->_level=$level;
		return $this;
	}
	/**
	 * filter and sort param
	 * @param array $param
	 */
	private function _filter(array &$param){
		ksort($param);
		foreach ($param as $k=>$v){
			$_k=strval($k);
			if (!isset($param[$_k])){
				unset($param[$k]);
			}
			if (is_array($v)){
				$this->_filter($v);
			}else $param[$_k]=strval($v);
		}
	}
	/**
	 * @return string
	 */
	private function _param(){
		return $this->_param;
	}
	/**
	 * create etag from body
	 * @param string $body
	 * @return string
	 */
	protected function _etag($body){
		return sha1($body);
	}
	/**
	 * create replaces from reg
	 * @param string $preg
	 * @return NULL|string
	 */
	protected function _replace($preg){
		if (empty($preg))return null;
		return '<!--page_cache_'.md5(trim($preg)).'-->';
	}
	/**
	 * check is flush
	 * @return boolean
	 */
	protected function _client_flush(){
		return isset($_SERVER['HTTP_PRAGMA'])&&$_SERVER['HTTP_PRAGMA']=='no-cache';
	}
	/**
	 * start page cache
	 * @param number $time
	 * @return boolean|\LSYS\PageCache
	 */
	public function start($time=10860){
		if ($this->_is_check)return false;
		if ($time<=0||!$this->_cache->lock($this->_param()))return false;
		$this->_is_lock=true;
		$this->_time=$time;
		ob_start();
		return $this;
	}
	/**
	 * 检查是否已经缓存
	 * @param array $replace 替换内容
	 * @return \LSYS\PageCache
	 */
	public function check(){
		if($this->_client_flush()||!self::LEVEL_SERVER&$this->_level) return $this;//强制刷新或服务器不缓存
		$cache_key=$this->_param();
		$data=$this->_cache->get_data($cache_key);
		
		if ($data===false) return $this;//缓存未发现
		
		foreach ($this->_replace as $k=>$v){
			$k=$this->_replace($k);
			if ($k==null)continue;
			$data=str_replace($k, $v, $data);
		}
		
		ob_end_clean();
		if (headers_sent()||!($this->_level&self::LEVEL_CLIENT)){
			$this->_is_check=true;
			return $this->_output->output(false, array(), $data);
		}
		
		$meta=$this->_cache->get_meta($cache_key);
		
		$create_time=0;
		$cache_time=0;
		$headers=array();
		if ($meta!==false) @list($create_time,$cache_time,$headers)=unserialize($meta);
		
		$etag = $this->_etag($data);
		
		$not_mod=false;
		
		if (isset($_SERVER['HTTP_IF_NONE_MATCH'])){
			if ($_SERVER['HTTP_IF_NONE_MATCH']==$etag){
				// 				$header[]='HTTP/1.1 304 Not Modified!';
				//header('HTTP/1.1 304 Not Modified!');
				$not_mod=true;
			}
		}
		$age=$create_time+$cache_time-time();
		if ($cache_time>0) $headers["Cache-Control"]="private,no-cache,max-age={$age}";
		if ($create_time>0) $headers['Last-Modified']=gmdate('D, d M Y H:i:s', $create_time).' GMT';//304时无用.可不发
		//send etag
		$headers['Etag']=$etag;
		
		$this->_is_check=true;
		//not modified not out put data
		return $this->_output->output($not_mod, $headers, $data);
	}
	/**
	 * cancel page cache
	 */
	public function cancel(){
		if ($this->_time<=0)return;
		$this->_cache->unlock($this->_param());
		$headers=array();
		if (!headers_sent()){
			$headers=array(
				"Cache-Control"=>"no-store",
				"Pragma"=>"no-store",
				"Expires"=>"-1"
			);
		}
		$data=ob_get_contents();
		ob_end_clean();
		return $this->_output->output(false, $headers,$data);
	}
	/**
	 * end page cache
	 */
	public function end($cache_header=array()){
		
		if ($this->_is_check||$this->_time<=0)return;
		$data=$_data=ob_get_contents();
		ob_end_clean();
		
		$etag = $this->_etag($data);
		$not_mod=false;
		if (!headers_sent()&&isset($_SERVER['HTTP_IF_NONE_MATCH'])){
			if ($_SERVER['HTTP_IF_NONE_MATCH']==$etag){//服务器缓存失效情况
				//$header[]='HTTP/1.1 304 Not Modified!';
				$not_mod=true;
			}
		}
		$headers=array();
		if ($this->_level&self::LEVEL_CLIENT){
			if (!headers_sent()){
				//send etag
				$headers['Etag']=$etag;
				$headers['Cache-Control']="private,no-cache,max-age={$this->_time}";
				$headers['Last-Modified']=gmdate('D, d M Y H:i:s', time()).' GMT';
			}
		}
		$cache_key=$this->_param();
		if ($this->_level&self::LEVEL_SERVER){
			foreach ($this->_replace as $k=>$v){
				$_data=preg_replace($k, $this->_replace($k), $_data);
			}
			$this->_cache->set($cache_key,serialize(array(
					time(),
					$this->_time,
					$cache_header,
			)),$_data,$this->_time+1);
		}
		$this->_cache->unlock($cache_key);
		return $this->_output->output($not_mod, $headers, $data);
	}
	/**
	 * destruct free cache lock
	 */
	public function __destruct(){
		if(!$this->_is_lock)return ;
		$this->_cache->unlock($this->_param());
	}
}

