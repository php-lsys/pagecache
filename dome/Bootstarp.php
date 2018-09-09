<?php
use LSYS\PageCache;
use LSYS\PageCache\Storage\Redis;
use LSYS\PageCache\Output\DefaultOutput;
include_once __DIR__."/../vendor/autoload.php";
LSYS\Config\File::dirs(array(
    __DIR__."/config",
));
PageCache::init(new Redis(),new DefaultOutput());
//注册运行完成后,输出内容缓存处理函数
register_shutdown_function(function(){
	if ($error = error_get_last() AND in_array($error['type'], array(E_PARSE, E_ERROR, E_USER_ERROR))){
		PageCache::shutdown(false);//出错,对当前输出不进行缓存
	}else{
		PageCache::shutdown(true);//对当前输出进行缓存
	}
});