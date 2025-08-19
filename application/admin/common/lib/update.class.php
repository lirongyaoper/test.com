<?php
if(!defined('IN_YZMPHP')) define('IN_YZMPHP', true);
defined('IN_YZMPHP') || exit('Access Denied');

yzm_base::load_common('function/function.php');
define('CLOSE_WRITE_LOG', true);

class update {
    public static function mysql_varsion() {
        return D('admin')->version();
    }
    
    public static function notice_url($action = "notice") {
        $pars = array(
            'action' => $action,
            'siteurl' => urlencode(SITE_URL),
            'sitename' => urlencode(get_config('site_name')),
            'version' => YZMCMS_VERSION,
            'software' => urlencode($_SERVER['SERVER_SOFTWARE']),
            'os' => PHP_OS,
            'php' => phpversion(),
            'mysql' => self::mysql_varsion(),
            'browser' => urlencode($_SERVER['HTTP_USER_AGENT']),
            'username' => urlencode($_SESSION['adminname'])
        );
        $data = http_build_query($pars);
        return base64_decode('aHR0cDovL3VwZGF0ZS55em1jbXMuY29tLw==') . $data;
    }
    
    public static function check() {
        if(!($official_info = getcache('official_info'))) {
            $curl = curl_init(self::notice_url());
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 2500);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $official_info = curl_exec($curl);
            curl_close($curl);
            setcache('official_info', $official_info, 86400);
        }
        header('Content-Type: application/javascript');
        echo $official_info;
        exit;
    }
    
    public static function check_update() {
        $update_url = base64_decode('aHR0cDovL3VwZGF0ZS55em1jbXMuY29tLw==') . 'ver=' . YZMCMS_VERSION . '&ver_time=' . YZMCMS_UPDATE . '&key=' . C('auth_key') . '&host=' . urlencode(HTTP_HOST) . '&php=' . phpversion() . '&mysql=' . self::mysql_varsion() . '&ip=' . gethostbyname($_SERVER['SERVER_NAME']) . '&server=' . urlencode($_SERVER['SERVER_SOFTWARE']);
        
        if(!function_exists('curl_init')) {
            if(!function_exists('stream_context_set_default')) {
                return array('status' => 0, 'message' => '缺少指定PHP扩展，无法连接到升级服务器！');
            }
            $context = stream_context_set_default(array('http' => array('timeout' => 5, 'method' => 'GET')));
            $service_data = @file_get_contents($update_url, false, $context);
        } else {
            $curl = curl_init($update_url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_NOSIGNAL, true);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $service_data = curl_exec($curl);
            curl_close($curl);
        }
        
        if(!$service_data) {
            return array('status' => 0, 'message' => '无法连接到升级服务器，请刷新重试！');
        }
        
        $service_data = json_decode($service_data, true);
        if(!is_array($service_data)) {
            return array('status' => 0, 'message' => '升级服务器错误，请稍后重试！');
        }
        
        if(!$service_data['status']) {
            return array('status' => 0, 'message' => $service_data['message']);
        }
        
        if(empty($service_data['data'])) {
            delcache('yzm_update_ignore');
            return array('status' => 1, 'message' => '当前已是最新版本！');
        }
        
        return array('status' => 1, 'message' => '发现新版本！', 'data' => $service_data['data']);
    }
    
    public static function system_update() {
        $result = self::check_update();
        if($result['status'] != 1) {
            return_json($result);
        }
        
        $service_data = $result['data'];
        
        if(!extension_loaded('zip')) {
            return_json(array('status' => 0, 'message' => '请开启 php.ini 中的【php_zip】扩展！'));
        }
        
        if($_SESSION['roleid'] > 1) {
            return_json(array('status' => 0, 'message' => '系统升级仅限超级管理员操作！'));
        }
        
        if(!is_writeable(YZMPHP_PATH . 'cache/')) {
            return_json(array('status' => 0, 'message' => '文件权限不足，请检查文件权限！'));
        }
        
        $down_package = YZMPHP_PATH . 'cache/down_package/';
        if(!is_dir($down_package)) {
            @mkdir($down_package, 0777, true);
        }
        
        $result = downfile($service_data['downfile'], $service_data['file_md5']);
        if(!$result['status']) {
            return_json($result);
        }
        
        $unzip_folder = str_replace('.zip', '', $result['file_path']);
        $result = unzips($result['file_path'], $down_package);
        
        if(!$result['status']) {
            return_json($result);
        }
        
        $web_pack = YZMPHP_PATH . 'cache/' . strtolower(YZMCMS_VERSION) . '_bak/';
        
        $copy_message = '';
        if(is_dir($unzip_folder . '/files/')) {
            $copy_fail = copy_file($unzip_folder . '/files/', YZMPHP_PATH);
            if($copy_fail) {
                $copy_message = '但有<span class="c-red"> ' . $copy_fail . ' </span>个文件复制失败<span class="c-red">（请检查目录权限）</span>！<br>请从目录【/cache/down_package/' . $service_data['version'] . '/files】中取出全部文件覆盖到根目录，<br>完成手工升级！';
            }
        }
        
        del_dir($unzip_folder);
        
        $ziplist = glob(YZMPHP_PATH . 'cache/down_package/*.zip');
        @array_map('unlink', $ziplist);
        delcache('official_info');
        delcache('yzm_update_info');
        
        self::update_log($service_data);
        
        return_json(array('status' => $copy_message ? 2 : 1, 'message' => '升级成功！' . $copy_message));
    }
    
    public static function update_log($service_data) {
        $parameter = array(
            'ver' => $service_data['version'],
            'ver_time' => date('Ymd', $service_data['ver_time']),
            'key' => C('auth_key'),
            'host' => HTTP_HOST,
            'php' => phpversion(),
            'mysql' => self::mysql_varsion(),
            'ip' => gethostbyname($_SERVER['SERVER_NAME']),
            'server' => $_SERVER['SERVER_SOFTWARE']
        );
        
        $update_url = base64_decode('aHR0cHM6Ly91cGRhdGUueXptY21zLmNvbS91cGRhdGVfbG9nLnBocA==') . http_build_query($parameter);
        https_request($update_url);
    }

    public static function system_information($data) {
        $notice_url = U("public_home", "up=1");
        $string = base64_decode('PHNjcmlwdD5zZXRUaW1lb3V0KGZ1bmN0aW9uKCl7JCgiI25vdGljZSIpLmxvYWQoIjw='); // 注意：这里需要完整的base64字符串
        echo $data . str_replace('NOTICE_URL', $notice_url, $string);
    }
}
?>