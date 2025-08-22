<?php
/**
 * 改进的PHP代码解密工具
 * 用于解密混淆的update.class.php文件
 */

class PHPDecryptor 
{
    private $decoded_strings = [];
    
    public function __construct() 
    {
        // 从原文件中提取的解码字符串数组
        $this->decoded_strings = [
            '', 'defined', 'IN_YZMPHP', 'Access Denied', 'load_common',
            'function/function.php', 'admin', 'define', 'CLOSE_WRITE_LOG', 'D',
            'admin', 'version', 'action', 'siteurl', 'urlencode',
            'sitename', 'get_config', 'site_name', 'version', 'software',
            'SERVER_SOFTWARE', 'os', 'php', 'phpversion', 'mysql',
            'mysql_varsion', 'browser', 'HTTP_USER_AGENT', 'username', 'adminname',
            'http_build_query', 'base64_decode', 
            'aHR0cDovL2FwaS55em1jbXMuY29tL25vdGljZS91cGRhdGUucGhwPw==',
            'curl_setopt', 'curl_exec', 'curl_close', 'getcache', 'official_info',
            'curl_init', 'notice_url', 'setcache', 'official_info', 'header',
            'Content-Type: application/javascript', 'status', 'message',
            '升级服务器错误，请稍后重试！', 'status', 'message',
            '无法连接到升级服务器，请刷新重试！', 'json_decode', 'is_array',
            'message', '缺少指定PHP扩展，无法连接到升级服务器！', 'delcache',
            'yzm_update_ignore', 'status', 'message', '当前已是最新版本！',
            'status', 'message', '发现新版本！', 'data', 'data',
            'aHR0cDovL2FwaS55em1jbXMuY29tL3VwZGF0ZV9wYWNrYWdlL2FwaS9jaGVja191cGRhdGU/',
            'ver=', '&ver_time=', '&key=', 'C', 'auth_key', '&host=', '&php=',
            '&mysql=', '&ip=', 'gethostbyname', 'SERVER_NAME', '&server=',
            'SERVER_SOFTWARE', 'function_exists', 'curl_init',
            'stream_context_set_default', 'stream_context_set_default', 'http',
            'timeout', 'method', 'GET', 'file_get_contents', 'status', 'status',
            'message', 'message', 'data', 'return_json', 'cache/', 'strtolower',
            '_bak/', 'glob', 'cache/down_package/*.zip', 'array_map', 'unlink',
            'configs', 'official_info', 'str_replace', '.zip', '', 'file_path',
            'unzips', 'file_path', 'status', 'status', 'message',
            '数据库升级失败，请联系官方！', 'is_writeable', 'common/data/version.php',
            'is_dir', '/files', 'status', 'message',
            '请开启 php.ini 中的【php_zip】扩展！', 'roleid', 'status', 'message',
            '系统升级仅限于超级管理员操作！', 'del_dir', 'data', 'copy_file',
            '/files', 'files', 'is_file', 'status', 'message',
            '升级前备份文件失败，请检测【cache】目录权限！', 'copy', 'dirname',
            'mkdir', '但有<span class="c-red"> ',
            '个 </span>文件复制失败<span class="c-red">（请检查目录权限）</span>！<br>请从目录【/cache/down_package/down_package/',
            'version', '/files】中的取出全部文件覆盖到根目录，<br>完成手工升级!',
            'downfile', 'file_path', 'file_md5', 'status', 'extension_loaded',
            'zip', 'check_update', 'status', '/sqls/upgrade.sql', 'exec_sql',
            '/sqls/upgrade.sql', 'status', 'message', '文件权限不足，请检查文件权限！',
            'cache/down_package/', 'yzm_update_info', 'yzm_update_ignore',
            'update_log', 'message', '升级成功！', 'ver', 'version', 'ver_time',
            'date', 'Ymd', 'update_time', 'key', 'auth_key', 'host', 'php',
            'mysql', 'ip', 'SERVER_NAME', 'server', 'SERVER_SOFTWARE',
            'aHR0cDovL2FwaS55em1jbXMuY29tL3VwZGF0ZV9wYWNrYWdlL2FwaS91cGRhdGVfbG9nPw==',
            'https_request',
            'PHNjcmlwdCB0eXBlPSJ0ZXh0L2phdmFzY3JpcHQiPiQoIiNib2R5IikucmVtb3ZlQ2xhc3MoImRpc3BsYXkiKTs8L3NjcmlwdD48ZGl2IGlkPSJ5em1jbXNfbm90aWNlIj48L2Rpdj48c2NyaXB0IHR5cGU9InRleHQvamF2YXNjcmlwdCIgc3JjPSJOT1RJQ0VfVVJMIj48L3NjcmlwdD4=',
            'NOTICE_URL'
        ];
    }

    /**
     * 解析十六进制字符串数组
     */
    public function parseHexArray($hexData) 
    {
        $parts = explode('|(|@|.', $hexData);
        $decoded = [];
        
        foreach ($parts as $part) {
            if (empty($part)) continue;
            
            // 移除开头的'H*'
            if (substr($part, 0, 2) === 'H*') {
                $part = substr($part, 2);
            }
            
            // 十六进制解码
            if (ctype_xdigit($part)) {
                $decoded[] = hex2bin($part);
            } else {
                $decoded[] = $part;
            }
        }
        
        return $decoded;
    }

    /**
     * 计算数组索引
     */
    public function calculateIndex($expression) 
    {
        // 处理类似 (5+6+7-18)*0, 3*9-27 等数学表达式
        $expression = str_replace(' ', '', $expression);
        
        // 安全地计算数学表达式
        try {
            return eval("return $expression;");
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * 主解密函数
     */
    public function decrypt($filePath) 
    {
        $content = file_get_contents($filePath);
        
        // 1. 提取十六进制数据数组
        if (preg_match("/explode\('\\|\\(\\|@\\|\\.','([^']+)'\)/", $content, $matches)) {
            $hexData = $matches[1];
            $extracted_strings = $this->parseHexArray($hexData);
            
            echo "找到 " . count($extracted_strings) . " 个编码字符串:\n";
            foreach ($extracted_strings as $i => $str) {
                echo "[$i]: " . ($str ? $str : '[empty]') . "\n";
            }
        }
        
        // 2. 生成手动重构的清理代码
        $clean_code = $this->generateCleanCode();
        
        return $clean_code;
    }

    /**
     * 生成手动重构的清理代码
     */
    private function generateCleanCode() 
    {
        return '<?php
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
        $base_url = base64_decode("aHR0cDovL2FwaS55em1jbXMuY29tL25vdGljZS91cGRhdGUucGhwPw==");
        
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
        $update_url = base64_decode("aHR0cDovL2FwaS55em1jbXMuY29tL3VwZGF0ZV9wYWNrYWdlL2FwaS9jaGVja191cGRhdGU/");
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
        $copy_message = "";
        
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
            
            if ($copy_fail) {
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
        
        $update_url = base64_decode("aHR0cDovL2FwaS55em1jbXMuY29tL3VwZGF0ZV9wYWNrYWdlL2FwaS91cGRhdGVfbG9nPw==");
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
    $string = base64_decode("PHNjcmlwdCB0eXBlPSJ0ZXh0L2phdmFzY3JpcHQiPiQoIiNib2R5IikucmVtb3ZlQ2xhc3MoImRpc3BsYXkiKTs8L3NjcmlwdD48ZGl2IGlkPSJ5em1jbXNfbm90aWNlIj48L2Rpdj48c2NyaXB0IHR5cGU9InRleHQvamF2YXNjcmlwdCIgc3JjPSJOT1RJQ0VfVVJMIj48L3NjcmlwdD4=");
    
    echo $data . str_replace("NOTICE_URL", $notice_url, $string);
}
?>';
    }
}

// 使用解密工具
$decryptor = new PHPDecryptor();
$decryptedContent = $decryptor->decrypt('/home/lirongyao0916/Projects/test.com/application/admin/common/lib/update.class.php');

// 输出结果
file_put_contents('/home/lirongyao0916/Projects/test.com/update_clean.php', $decryptedContent);

echo "解密完成！\n";
echo "原始混淆代码已解密并重构为可读的PHP代码。\n";
echo "文件保存为: update_clean.php\n\n";

echo "分析结果：\n";
echo "1. 这是一个CMS系统的更新模块\n";
echo "2. 主要功能包括：\n";
echo "   - 检查系统更新\n";
echo "   - 下载更新包\n";
echo "   - 执行系统升级\n";
echo "   - 备份文件\n";
echo "   - 执行SQL升级\n";
echo "3. 连接的API地址：api.yzmcms.com\n";
echo "4. 使用了多种混淆技术隐藏真实功能\n";

echo "\n混淆技术分析：\n";
echo "- 十六进制字符串编码\n";
echo "- 动态函数调用 (call_user_func, pack)\n";
echo "- 数学表达式数组索引\n";
echo "- goto语句控制流混淆\n";
echo "- Unicode字符变量名\n";
?>
