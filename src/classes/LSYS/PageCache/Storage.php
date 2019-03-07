<?php
/**
 * lsys pagecache
 * 页面内容缓存存储接口
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
namespace LSYS\PageCache;
interface Storage{
	/**
	 * 保存缓存结果
	 * @param string $key 缓存KEY
	 * @param string $meta 缓存时的相关信息
	 * @param string $val 缓存的内容
	 * @param int $time 缓存有效期
	 */
	public function set($key,$meta,$data,$time);
	/**
	 * 获取缓存的相关信息
	 * @param string $key
	 */
	public function getMeta($key);
	/**
	 * 获取缓存的内容
	 * @param string $key
	 */
	public function getData($key);
	/**
	 * 得到一个缓存进行时的锁[保证原子性,目的:多个访客同时访问时,都进入缓存处理,可能导致缓存错乱]
	 * @param string $key
	 */
	public function lock($key);
	/**
	 * 释放一个缓存进行时的锁
	 * @param string $key
	 */
	public function unlock($key);
}