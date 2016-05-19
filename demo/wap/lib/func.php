<?php
use koala\Koala;

/**
 * 全局通用函数存放
 */


/**
 * 上传文件完整的链接地址
 * 如果是空则返回空,否则返回可访问的地址
 * @param  string $upload_url 上传文件路径
 * @return string
 */
function real_upload_url($upload_url){
	if(empty($upload_url)){
		return '';
	}else if(preg_match("/^((http|https)\:\/\/).*$/i", $upload_url)){
		return $upload_url;
	} else {
	return $upload_url;
	}
}


/**
 * 获取客户端ip地址
 */
function client_ip_address(){
	foreach (array(
				'HTTP_X_FORWARDED_FOR',
				'HTTP_CLIENT_IP',
				'REMOTE_ADDR') as $key) {
		if (array_key_exists($key, $_SERVER)) {
			//X-Forwarded-For的格式有可能是ip1,ip2,ip3...返回第一个格式正确的IP就行了
			//其它都是单个IIP，用explode也没问题
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				$ip = trim($ip);
				//过滤IP地址
				if ((bool) filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}
	}
	return null;
}


/**
 * 获取字符串的长度，无论是中文还是英文都可以
 * @param string $str 字符串
 * @return  int 
 */

function chinese_strlen($str){
	preg_match_all('/./us', $str, $match);
	return count($match[0]);
}

/**
 * 时间距离计算
 * @param int $now 当前时间
 * @return  int 
 */
function thetime($timestamp){

	$now_time = time();
	$show_time = $timestamp;
	$dur = $now_time - $show_time;

	if($dur < 0){
		return $timestamp; 
	}else{
		if($dur < 60){
			return $dur.'秒前'; 
		}else{
			if($dur < 3600){
				return floor($dur/60).'分钟前'; 
			}else{
			   if($dur < 86400){
					return floor($dur/3600).'小时前'; 
			   }else{
				   if($dur < 259200){//3天内
					   return floor($dur/86400).'天前';
				   }else{
					   return date("Y-m-d h:i:s",$timestamp); 
				   }
			   }
			}
		}
	}
}

// 判断客户端系统
function user_agent_os_type() {
	$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ?strtolower($_SERVER['HTTP_USER_AGENT']) : '';
	$os_types = array(
		'win'     => 'windows nt',
		'android' => 'android',
		'iphone'  => 'iphone',
		'ipad'    => 'ipad',
	);
	foreach ($os_types as $os => $os_tag) {
		if (strpos($user_agent, $os_tag) !== false) {
			return $os;
		}
	}
	return false;
}

/**
 * 一千为计量单位返回多少k
 * @param  int $num 数量
 * @return [type]      [description]
 */
function thousand($num){
	if($num < 1000){
		return $num;
	}else{
		return floor($num/100)/10 .'K';
	}
}

/**
 * 一万为计量单位返回多少万
 * @param  int $num 数量
 * @return [type]      [description]
 */
function tenthousand($num){
	if($num < 10000){
		return $num;
	}else{
		return floor($num/10000) .'万';
	}
}

/**
 * 分页
 */
function __P__($p) {
	$page_record_max = Koala::$app->Config->get('common','page_record_max');
	$_p = max($p - 1,0);
	$start = $_p * $page_record_max;
	$end = $page_record_max;
	return [$start,$end];
}