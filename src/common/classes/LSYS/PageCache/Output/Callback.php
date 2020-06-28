<?php
/**
 * lsys pagecache
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\PageCache\Output;
use LSYS\PageCache\Output;
class Callback implements Output{
	protected $_callable;
	public function __construct(callable $callable){
		$this->_callable=$callable;
	}
	public function output(bool $not_modified,array $headers,string $cache_data):void{
		call_user_func_array($this->_callable, func_get_args());
	}
}