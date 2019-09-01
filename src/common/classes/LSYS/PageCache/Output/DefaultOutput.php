<?php
/**
 * lsys pagecache
 * 默认缓存结果输出处理,可自定义
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\PageCache\Output;
use LSYS\PageCache\Output;
class DefaultOutput implements Output{
	public function output($not_modified,$headers,$cache_data){
		foreach ($headers as $k=>$v)header($k.": ".$v);
		if ($not_modified){
			http_response_code(304);
			die();
		}
		die($cache_data);
	}
}