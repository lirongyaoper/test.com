<?php
/**
 * YzmCMS Admin Index Controller - 学习参考实现
 * 
 * 这是一个基于功能分析的学习参考实现，不是原始代码的精确还原
 * 仅供学习研究使用，请遵守相关法律法规
 * 
 * @author   学习研究版本
 * @license  仅供学习使用
 */

defined('IN_YZMPHP') or exit('Access Denied'); 
yzm_base::load_controller('common', 'admin', 0);

class index extends common {

    /**
     * 后台首页
     */
    public function init() {
        debug();
        
        // 统计留言本数量
        $guestbook_total = D('guestbook')->where(array(
            'replyid' => 0,
            'siteid' => self::$siteid,
            'isread' => 0
        ))->total();
        
        // 加载首页模板
        include $this->admin_tpl('index');
    }

    /**
     * 管理员登录
     */
    public function login() {
        if (!is_post()) {
            // 显示登录页面
            $this->_login();
            return;
        }

        // 验证验证码
        if (empty($_SESSION['code']) || strtolower($_POST['code']) != $_SESSION['code']) {
            $_SESSION['code'] = '';
            return_json(array('status' => 0, 'message' => L('code_error')));
        }

        // 验证用户名格式
        $_POST['username'] = trim($_POST['username']);
        if (!is_username($_POST['username'])) {
            return_json(array('status' => 0, 'message' => L('user_name_format_error')));
        }

        // 验证密码格式
        if (!is_password($_POST['password'])) {
            return_json(array('status' => 0, 'message' => L('password_format_error')));
        }

        // 验证管理员账号
        $res = M('admin')->check_admin($_POST['username'], password($_POST['password']));
        if (!$res['status']) {
            return_json($res);
        }

        // 登录成功，设置 Session
        $_SESSION['adminid'] = $res['adminid'];
        $_SESSION['adminname'] = $res['adminname'];
        $_SESSION['roleid'] = $res['roleid'];
        $_SESSION['admininfo'] = $res;

        return_json(array(
            'status' => 1, 
            'message' => L('login_success'),
            'url' => U('admin/index/init')
        ));
    }

    /**
     * 管理员退出
     */
    public function public_logout() {
        // 清除 Session
        unset($_SESSION['adminid'], $_SESSION['adminname'], $_SESSION['roleid'], $_SESSION['admininfo']);
        
        // 清除 Cookie
        del_cookie('adminid');
        del_cookie('adminname');
        
        showmsg(L('you_have_safe_exit'), U('admin/index/login'), 1);
    }

    /**
     * 后台桌面
     */
    public function public_home() {
        debug();
        
        // 检查更新
        if (isset($_GET['up'])) {
            // 更新检查逻辑
            // 这里需要加载更新类
            yzm_base::load_common('lib/update'.EXT, 'admin');
            if (!class_exists('update')) {
                showmsg('缺少必要的系统文件，请联系YzmCMS官方！', 'stop');
            }
            update::check();
            return;
        }

        // 获取统计信息
        $template_file = APP_PATH . 'admin' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'public_home.html';
        if (!is_file($template_file)) {
            $this->_force_logout();
            return;
        }

        $html = file_get_contents($template_file);
        
        // 检查版权信息
        if (!strpos($html, 'YzmCMS') || !strpos($html, 'www.yzmcms.com')) {
            $this->_force_logout();
            return;
        }

        // 统计数据
        $count = array();
        $count[] = D('content')->where(array('siteid' => self::$siteid))->total();
        $count[] = D('admin')->total();
        $count[] = D('member')->total();

        ob_start();
        include $this->admin_tpl('public_home');
        $data = ob_get_contents();
        ob_end_clean();

        // 系统信息处理
        system_information($data);
    }

    /**
     * 清理错误日志
     */
    public function public_clear_log() {
        // 检查权限（仅超级管理员）
        if ($_SESSION['roleid'] != 1) {
            return_json(array('status' => 0, 'message' => '此操作仅限于超级管理员！'));
        }

        // 清理日志文件
        if (is_file(YZMPHP_PATH . 'cache/error_log.php')) {
            $res = @unlink(YZMPHP_PATH . 'cache/error_log.php');
            if (!$res) {
                return_json(array('status' => 0, 'message' => L('operation_failure')));
            }

            // 记录操作日志
            D('admin_log')->insert(array(
                'module' => ROUTE_M,
                'action' => ROUTE_C,
                'adminname' => $_SESSION['adminname'],
                'adminid' => $_SESSION['adminid'],
                'querystring' => '清除错误日志',
                'logtime' => SYS_TIME,
                'ip' => self::$ip
            ));
        }

        return_json(array('status' => 1, 'message' => L('operation_success')));
    }

    /**
     * 锁定屏幕
     */
    public function public_lock_screen() {
        $_SESSION['yzm_lock_screen'] = 1;
        return_json(array('status' => 1, 'message' => L('operation_success')));
    }

    /**
     * 解锁屏幕
     */
    public function public_unlock_screen() {
        $res = M('admin')->check_admin($_SESSION['adminname'], password($_POST['password']));
        if (!$res['status']) {
            return_json($res);
        }

        $_SESSION['yzm_lock_screen'] = 0;
        return_json(array('status' => 1, 'message' => L('login_success')));
    }

    /**
     * 强制退出（私有方法）
     */
    private function _force_logout() {
        $_SESSION = array();
        session_destroy();
        echo '<script type="text/javascript">window.top.location="http://www.yzmcms.com?from=' . urlencode(SITE_URL) . '"</script>';
        exit;
    }

    /**
     * 显示登录页面（私有方法）
     */
    private function _login() {
        ob_start();
        include $this->admin_tpl('login');
        $data = ob_get_contents();
        ob_end_clean();
        
        // 输出页面内容和版权信息
        echo $data . base64_decode('PGRpdiBzdHlsZT0iZGlzcGxheTpub25lIj5Qb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHA6Ly93d3cueXptY21zLmNvbSIgdGFyZ2V0PSJfYmxhbmsiPll6bUNNUzwvYT48L2Rpdj4=');
    }
}
?>
