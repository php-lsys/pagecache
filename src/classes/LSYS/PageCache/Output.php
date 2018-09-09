<?php
/**
 * lsys pagecache
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\PageCache;
interface Output{
	/**
	 * 缓存的内容输出函数
	 * @param bool $not_modified 客户端缓存是否有效,如客户端内容有效,可直接发送304 HTTP头
	 * @param string $headers 缓存结果需要发送的消息头,包含ETAG信息,需发送给浏览器
	 * @param string $cache_data 缓存页面内容
	 */
	public function output($not_modified,$headers,$cache_data);
}