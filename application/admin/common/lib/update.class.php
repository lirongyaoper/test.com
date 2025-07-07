<?php
defined('IN_YZMPHP') or exit('Access Denied');

class update {

    public static function mysql_version() {
        return D('mysql')->version();
    }

    public static function notice_url($action = "notice") {
        $pars = array(
            'action' => $action,
            'siteurl' => urlencode(SITE_URL),
            'sitename' => urlencode(get_config('site_name')),
            'version' => YZMCMS_VERSION,
            'server_software' => urlencode($_SERVER['SERVER_SOFTWARE']),
            'os' => PHP_OS,
            'php' => phpversion(),
            'mysql' => self::mysql_version(),
            'ip' => urlencode(gethostbyname($_SERVER['SERVER_NAME'])),
            'adminname' => urlencode($_SESSION['adminname'])
        );
        return "http://lryper.com/api.php?".http_build_query($pars);
    }

    public static function check() {
        $official_info = getcache('official_info');

        if (!$official_info) {
            $curl = curl_init(self::notice_url());
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 2500);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $official_info = curl_exec($curl);
            curl_close($curl);

            if ($official_info) {
                setcache('official_info', $official_info, 3600);
            }
        }

        header('Content-Type: application/json');
        echo $official_info ?: '{"status":0,"message":""}';
        exit;
    }

    public static function check_update() {
        $update_url = "http://lryper.com/api.php?action=update_check"
            . "&version=".YZMCMS_VERSION
            . "&ver_time=".YZMCMS_UPDATE_TIME
            . "&auth_key=".C('auth_key')
            . "&domain=".urlencode(HTTP_HOST)
            . "&php=".phpversion()
            . "&mysql=".self::mysql_version()
            . "&ip=".gethostbyname($_SERVER['SERVER_NAME'])
            . "&server=".urlencode($_SERVER['SERVER_SOFTWARE']);

        if (function_exists('curl_init')) {
            $curl = curl_init($update_url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_NOSIGNAL, true);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 2000);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $service_data = curl_exec($curl);
            curl_close($curl);
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'timeout' => 2,
                    'method' => 'GET'
                )
            ));
            $service_data = @file_get_contents($update_url, false, $context);
        }

        if (!$service_data) {
            delcache('official_info');
            return array(
                'status' => 0,
                'message' => ''
            );
        }

        $service_data = json_decode($service_data, true);

        if (!is_array($service_data)) {
            return array(
                'status' => 0,
                'message' => ''
            );
        }

        return $service_data;
    }

    public static function system_update() {
        if ($_SESSION['roleid'] != 1) {
            return_json(array(
                'status' => 0,
                'message' => ''
            ));
        }

        $result = self::check_update();
        if ($result['status'] != 1) {
            return_json($result);
        }

        $service_data = $result['data'];
        $down_package = YZMPHP_PATH.'cache/down_package/';

        if (!is_dir($down_package)) {
            if (!mkdir($down_package, 0755, true)) {
                return_json(array(
                    'status' => 0,
                    'message' => 'cache'
                ));
            }
        }

        $result = downfile($service_data['url'], $service_data['md5']);
        if (!$result['status']) {
            return_json($result);
        }

        $unzip_folder = str_replace('.zip', '', $result['file_path']);
        $result = unzips($result['file_path'], $down_package);
        if (!$result['status']) {
            return_json($result);
        }

        $web_pack = $down_package.strtolower(YZMCMS_VERSION).'/';
        $copy_message = '';

        foreach ($service_data['files'] as $val) {
            $source_file = YZMPHP_PATH.$val;
            if (is_file($source_file)) {
                $dirname = dirname($web_pack.$val);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0755, true);
                }
                $r = @copy($source_file, $web_pack.$val);
                if (!$r) {
                    $copy_message .= $val.' ';
                }
            }
        }

        if (is_file($unzip_folder.'/sqls/upgrade.sql')) {
            $res = exec_sql(file_get_contents($unzip_folder.'/sqls/upgrade.sql'));
            if (!$res) {
                return_json(array(
                    'status' => 0,
                    'message' => ''
                ));
            }
        }

        $ziplist = glob($down_package.'*.zip');
        @array_map('unlink', $ziplist);
        delcache('official_info');
        delcache('yzm_update_info');

        return_json(array(
            'status' => empty($copy_message) ? 1 : 0,
            'message' => empty($copy_message)
                ? ''
                : ''.$copy_message
        ));
    }
}

function system_information($data) {
    $notice_url = U("public_home", "up=1");
    $script = '<script>var yzm_notice_url="'.$notice_url.'";</script>';
    echo $data.$script;
}