<?php

defined('IN_YZMPHP') or exit('Access Denied');

class update {

	/**
	 * 获取 MySQL 版本
	 */
	public static function mysql_varsion() {
		return D('admin')->version();
	}

	/**
	 * 生成上报/通知 URL
	 */
	public static function notice_url($action = 'notice') {
		$params = array(
			'action'     => $action,
			'siteurl'    => SITE_URL,
			'sitename'   => urlencode(get_config('site_name')),
			'version'    => YZMCMS_VERSION,
			'agent'      => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'os'         => PHP_OS,
			'php'        => phpversion(),
			'mysql'      => self::mysql_varsion(),
			'ip'         => gethostbyname(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'),
			'host'       => HTTP_HOST,
			'auth_key'   => C('auth_key'),
		);
		return 'https://api.yzmcms.com/api/notice?'.http_build_query($params);
	}

	/**
	 * 输出官方 JS（用于后台首页授权/提示）
	 */
	public static function check() {
		$cache_key = 'yzm_official_notice';
		$official = getcache($cache_key);
		if(!$official){
			$url = self::notice_url('notice');
			$official = https_request($url, '', false, 3000);
			$official && setcache($cache_key, $official, 86400);
		}
		header('Content-Type: application/javascript');
		echo $official ? $official : '';
		exit;
	}

	/**
	 * 检测是否有新版本
	 * 返回：['status'=>0|1|2, 'message'=>'...', 'data'=>[]]
	 */
	public static function check_update() {
		$api = 'https://api.yzmcms.com/api/update';
		$params = array(
			'ver'       => YZMCMS_VERSION,
			'ver_time'  => YZMCMS_UPDATE,
			'key'       => C('auth_key'),
			'host'      => HTTP_HOST,
			'php'       => phpversion(),
			'mysql'     => self::mysql_varsion(),
			'ip'        => gethostbyname(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'),
			'server'    => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '',
		);

		$res = https_request($api.'?'.http_build_query($params), '', true, 4000);
		if(!$res || !is_array($res)) {
			return array('status'=>0, 'message'=>'检查更新时出错，请确定是否为官方完整版本！');
		}
		if(isset($res['status'])) return $res;
		return array('status'=>0, 'message'=>'服务返回数据异常！');
	}

	/**
	 * 一键更新
	 */
	public static function system_update() {
		$result = self::check_update();
		if(!isset($result['status']) || $result['status'] != 2) return_json($result);
		$service = isset($result['data']) ? $result['data'] : array();

		if(!extension_loaded('zip')) return_json(array('status'=>0, 'message'=>'请开启 php.ini 中的【php_zip】扩展！'));

		$down_dir = YZMPHP_PATH.'cache/down_package/';
		if(!is_dir($down_dir)) @mkdir($down_dir, 0777, true);

		if(empty($service['down_url']) || empty($service['down_md5'])) {
			return_json(array('status'=>0, 'message'=>'更新包信息不完整！'));
		}

		$down = downfile($service['down_url'], $service['down_md5']);
		if(!$down || empty($down['status'])) return_json($down);

		$zipfile = $down['file_path'];
		$unzip = unzips($zipfile, $down_dir);
		if(!$unzip || empty($unzip['status'])) return_json($unzip);

		$folder = $down_dir.pathinfo($zipfile, PATHINFO_FILENAME).'/';
		$copy_message = '';

		// 覆盖文件
		if(is_dir($folder.'files')){
			$fail = copy_file($folder.'files', YZMPHP_PATH);
			if($fail){
				$copy_message = ' 文件复制失败数：'.$fail.'。';
			}
		}

		// 执行SQL
		if(is_file($folder.'update.sql')){
			$sql_ok = exec_sql(@file_get_contents($folder.'update.sql'));
			if(!$sql_ok) return_json(array('status'=>0, 'message'=>'SQL执行失败！'));
		}

		// 清理
		del_dir($folder);
		@unlink($zipfile);
		delcache('yzm_update_info');
		delcache('yzm_update_ignore');

		self::update_log($service);

		return_json(array('status'=>1, 'message'=>'升级成功！'.$copy_message));
	}

	/**
	 * 上报更新日志
	 */
	public static function update_log($service_data) {
		$params = array(
			'version'    => isset($service_data['version']) ? $service_data['version'] : YZMCMS_VERSION,
			'update_at'  => isset($service_data['update_time']) ? date('Y-m-d H:i:s', intval($service_data['update_time'])) : date('Y-m-d H:i:s'),
			'auth_key'   => C('auth_key'),
			'host'       => HTTP_HOST,
			'php'        => phpversion(),
			'mysql'      => self::mysql_varsion(),
			'ip'         => gethostbyname(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'),
		);
		$log_url = 'https://api.yzmcms.com/api/update/log?'.http_build_query($params);
		@https_request($log_url, '', false, 3000);
	}
}

/**
 * 后台首页系统信息输出（保持原函数名以兼容调用）
 */
function system_information($data){
	// 保持原样输出
	echo $data;
}


