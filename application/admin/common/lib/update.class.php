<?php
/**
 * YzmCMS 系统更新类
 * 解密后的代码
 */

if (!defined("IN_YZMPHP")) {
    exit("Access Denied");
}

yzm_base::load_common("function/function.php", "admin");
define("CLOSE_WRITE_LOG", true);

class update 
{
    /**
     * 获取MySQL版本
     */
    public static function mysql_varsion() 
    {
        return D("admin")->version();
    }

    /**
     * 生成通知URL
     */
    public static function notice_url($action = "notice") 
    {
        $pars = array(
            "action" => $action,
            "siteurl" => urlencode(SITE_URL),
            "sitename" => urlencode(get_config("site_name")),
            "version" => YZMCMS_VERSION,
            "software" => urlencode($_SERVER["SERVER_SOFTWARE"]),
            "os" => PHP_OS,
            "php" => phpversion(),
            "mysql" => self::mysql_varsion(),
            "browser" => urlencode($_SERVER["HTTP_USER_AGENT"]),
            "username" => urlencode($_SESSION["adminname"])
        );
        
        $data = http_build_query($pars);
        $base_url = "http://api.yzmcms.com/notice/update.php?";
        
        return $base_url . $data;
    }

    /**
     * 检查官方通知
     */
    public static function check() 
    {
        // 尝试从缓存获取官方信息
        if (!($official_info = getcache("official_info"))) {
            $curl = curl_init(self::notice_url());
            curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 2500);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            
            $official_info = curl_exec($curl);
            curl_close($curl);
            
            // 缓存官方信息
            setcache("official_info", $official_info, 691200);
        }
        
        header("Content-Type: application/javascript");
        echo $official_info;
        exit;
    }

    /**
     * 检查更新
     */
    public static function check_update() 
    {
        // 构建更新检查URL
        $update_url = "http://api.yzmcms.com/update_package/api/check_update?";
        $update_url .= "ver=" . YZMCMS_VERSION;
        $update_url .= "&ver_time=" . YZMCMS_UPDATE;
        $update_url .= "&key=" . C("auth_key");
        $update_url .= "&host=" . urlencode(HTTP_HOST);
        $update_url .= "&php=" . phpversion();
        $update_url .= "&mysql=" . self::mysql_varsion();
        $update_url .= "&ip=" . gethostbyname($_SERVER["SERVER_NAME"]);
        $update_url .= "&server=" . urlencode($_SERVER["SERVER_SOFTWARE"]);

        $service_data = null;
        
        // 优先使用cURL
        if (function_exists("curl_init")) {
            $curl = curl_init($update_url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_NOSIGNAL, true);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 5000);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            
            $service_data = curl_exec($curl);
            curl_close($curl);
        } 
        // 回退到file_get_contents
        else if (function_exists("stream_context_set_default")) {
            $context = stream_context_set_default(array(
                "http" => array(
                    "timeout" => 5,
                    "method" => "GET"
                )
            ));
            $service_data = @file_get_contents($update_url, false, $context);
        } else {
            return array(
                "status" => 0,
                "message" => "缺少指定PHP扩展，无法连接到升级服务器！"
            );
        }

        if (!$service_data) {
            delcache("yzm_update_ignore");
            return array(
                "status" => 1,
                "message" => "发现新版本！"
            );
        }

        $service_data = json_decode($service_data, true);
        
        if (!is_array($service_data)) {
            return array(
                "status" => 0,
                "message" => "升级服务器错误，请稍后重试！"
            );
        }

        if (!$service_data["status"]) {
            return array(
                "status" => 0,
                "message" => $service_data["message"]
            );
        }

        if (empty($service_data["data"])) {
            delcache("yzm_update_ignore");
            return array(
                "status" => 1,
                "message" => "当前已是最新版本！"
            );
        }

        return array(
            "status" => 2,
            "message" => "发现新版本！",
            "data" => $service_data["data"]
        );
    }

    /**
     * 系统更新
     */
    public static function system_update() 
    {
        $result = self::check_update();
        
        if ($result["status"] != 2) {
            return_json($result);
        }
        
        $service_data = $result["data"];
        
        // 检查ZIP扩展
        if (!extension_loaded("zip")) {
            return_json(array(
                "status" => 0,
                "message" => "请开启 php.ini 中的【php_zip】扩展！"
            ));
        }

        // 检查用户权限
        if ($_SESSION["roleid"] > 1) {
            return_json(array(
                "status" => 0,
                "message" => "系统升级仅限于超级管理员操作！"
            ));
        }

        // 检查目录权限
        if (!is_writeable(YZMPHP_PATH . "common/data/version.php")) {
            return_json(array(
                "status" => 0,
                "message" => "文件权限不足，请检查文件权限！"
            ));
        }

        $down_package = YZMPHP_PATH . "cache/down_package/";
        
        if (!is_dir($down_package)) {
            @mkdir($down_package, 0777, true);
        }

        // 下载更新包
        $result = downfile($service_data["file_path"], $service_data["file_md5"]);
        
        if (!$result["status"]) {
            return_json($result);
        }

        // 解压更新包
        $unzip_folder = str_replace(".zip", "", $result["file_path"]);
        $result = unzips($result["file_path"], $down_package);
        
        if (!$result["status"]) {
            return_json($result);
        }

        // 备份和复制文件
        $web_pack = YZMPHP_PATH . "cache/" . strtolower(YZMCMS_VERSION) . "_bak/";
        
        $copy_fail = 0;
        foreach ($service_data["files"] as $val) {
            $source_file = YZMPHP_PATH . $val;
            
            if (is_file($source_file)) {
                $dirname = dirname($web_pack . $val);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                
                $r = @copy($source_file, $web_pack . $val);
                if (!$r) {
                    return_json(array(
                        "status" => 0,
                        "message" => "升级前备份文件失败，请检测【cache】目录权限！"
                    ));
                }
            }
        }

        // 复制新文件
        if (is_dir($unzip_folder . "/files")) {
            $copy_fail = copy_file($unzip_folder . "/files", YZMPHP_PATH);
            
            if (!$copy_fail) {
                $copy_message = "但有<span class=\"c-red\"> " . $copy_fail . 
                               " 个 </span>文件复制失败<span class=\"c-red\">（请检查目录权限）</span>！<br>" .
                               "请从目录【/cache/down_package/down_package/" . 
                               strtolower($service_data["version"]) . 
                               "/files】中的取出全部文件覆盖到根目录，<br>完成手工升级!";
            }
        }

        // 执行SQL升级脚本
        if (is_file($unzip_folder . "/sqls/upgrade.sql")) {
            $res = exec_sql(file_get_contents($unzip_folder . "/sqls/upgrade.sql"));
            if (!$res) {
                return_json(array(
                    "status" => 0,
                    "message" => "数据库升级失败，请联系官方！"
                ));
            }
        }

        // 清理缓存
        $ziplist = glob(YZMPHP_PATH . "cache/down_package/*.zip");
        @array_map("unlink", $ziplist);
        delcache("configs");
        delcache("official_info");
        delcache("yzm_update_info");
        delcache("yzm_update_ignore");
        
        // 记录更新日志
        self::update_log($service_data);
        
        // 清理临时目录
        del_dir($unzip_folder);
        
        return_json(array(
            "status" => $copy_message ? 0 : 1,
            "message" => "升级成功！" . $copy_message
        ));
    }

    /**
     * 记录更新日志
     */
    public static function update_log($service_data) 
    {
        $parameter = array(
            "ver" => $service_data["version"],
            "ver_time" => date("Ymd", $service_data["update_time"]),
            "key" => C("auth_key"),
            "host" => HTTP_HOST,
            "php" => phpversion(),
            "mysql" => self::mysql_varsion(),
            "ip" => gethostbyname($_SERVER["SERVER_NAME"]),
            "server" => $_SERVER["SERVER_SOFTWARE"]
        );
        
        $update_url =  "http://api.yzmcms.com/update_package/api/update_log?";
        $update_url .= http_build_query($parameter);
        
        https_request($update_url);
    }
}

/**
 * 系统信息函数
 */
function system_information($data) 
{
    $notice_url = U("public_home", "up=1");
    $string = '<script type="text/javascript">$("#body").removeClass("display");</script><div id="yzmcms_notice"></div><script type="text/javascript" src="NOTICE_URL"></script>';
    echo $data . str_replace("NOTICE_URL", $notice_url, $string);
}
?>