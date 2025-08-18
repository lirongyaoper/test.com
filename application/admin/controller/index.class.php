<?php
// +----------------------------------------------------------------------
// | Site:  [ http://www.yzmcms.com]
// +----------------------------------------------------------------------
// | Copyright: 袁志蒙工作室，并保留所有权利
// +----------------------------------------------------------------------
// | Author: YuanZhiMeng <214243830@qq.com>
// +---------------------------------------------------------------------- 
// | Explain: 这不是一个自由软件,您只能在不用于商业目的的前提下对程序代码进行修改和使用，不允许对程序代码以任何形式任何目的的再发布！
// +----------------------------------------------------------------------

defined('IN_YZMPHP') or exit('Access Denied'); 

yzm_base::load_controller('common', 'admin', 0);

class index extends common{
	/**
	 * 后台首页框架
	 */
	public function init(){
		debug();
		include $this->admin_tpl('index');
	}

	/**
	 * 管理员登录
	 */
	public function login(){
		if(!is_post()){
			$this->_login();
			return;
		}

		// 验证码校验
		$code = isset($_POST['code']) ? strtolower(trim($_POST['code'])) : '';
		if(empty($_SESSION['code']) || $code != strtolower($_SESSION['code'])){
			$_SESSION['code'] = '';
			return_json(array('status'=>0, 'message'=>L('code_error')));
		}

		$username = isset($_POST['username']) ? trim($_POST['username']) : '';
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		if(!is_username($username)) return_json(array('status'=>0, 'message'=>L('user_name_format_error')));
		if(!is_password($password)) return_json(array('status'=>0, 'message'=>L('password_format_error')));

		$res = M('admin')->check_admin($username, password($password));
		if($res['status'] != 1) return_json($res);

		return_json(array('status'=>1, 'message'=>L('login_success'), 'url'=>U('init')));
	}

	/**
	 * 退出登录
	 */
	public function public_logout(){
		unset($_SESSION['adminid'], $_SESSION['adminname'], $_SESSION['roleid'], $_SESSION['admininfo'], $_SESSION['yzm_csrf_token']);
		del_cookie('adminid');
		del_cookie('adminname');
		showmsg(L('you_have_safe_exit'), U('login'), 1);
	}

	/**
	 * 后台桌面
	 */
	public function public_home(){
		debug();

		// 更新检查（可选，通过 GET 参数触发）
		yzm_base::load_common('lib/update'.EXT, 'admin');
		if(!class_exists('update')){
			showmsg('缺少必要的系统文件，请联系YzmCMS官方！', 'stop');
		}
		if(isset($_GET['up'])){
			update::check();
		}

		// 模板与版权校验
		$tpl = APP_PATH.'admin'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'public_home.html';
		if(!is_file($tpl)){
			$this->_force_logout();
		}
		$html = file_get_contents($tpl);
		if(!strpos($html, 'YzmCMS') || !strpos($html, 'www.yzmcms.com')){
			$this->_force_logout();
		}

		// 统计信息
		$count = array();
		$count[] = D('all_content')->where(array('siteid'=>self::$siteid))->total();
		$count[] = D('admin')->total();
		$count[] = D('member')->total();
		$count[] = D('module')->total();

		ob_start();
		include $this->admin_tpl('public_home');
		$data = ob_get_contents();
		ob_end_clean();

		// 系统信息处理
		system_information($data);
	}

	/**
	 * 清除错误日志
	 */
	public function public_clear_log(){
		if($_SESSION['roleid'] != 1) return_json(array('status'=>0, 'message'=>L('no_permission_to_access')));
		$log_file = YZMPHP_PATH.'cache/error_log.php';
		if(!is_file($log_file)) return_json(array('status'=>0, 'message'=>L('does_not_exist')));
		$res = @unlink($log_file);
		if(!$res){
			return_json(array('status'=>0, 'message'=>L('operation_failure')));
		}
		D('admin_log')->insert(array(
			'module'=>ROUTE_M,
			'controller'=>ROUTE_C,
			'adminname'=>$_SESSION['adminname'],
			'adminid'=>$_SESSION['adminid'],
			'querystring'=>'清除错误日志',
			'logtime'=>SYS_TIME,
			'ip'=>self::$ip
		));
		return_json(array('status'=>1, 'message'=>L('operation_success')));
	}

	/**
	 * 锁屏
	 */
	public function public_lock_screen(){
		$_SESSION['yzm_lock_screen'] = 1;
		return_json(array('status'=>1, 'message'=>L('operation_success')));
	}

	/**
	 * 解锁
	 */
	public function public_unlock_screen(){
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		$res = M('admin')->check_admin($_SESSION['adminname'], password($password));
		if($res['status'] != 1) return_json($res);
		$_SESSION['yzm_lock_screen'] = 0;
		return_json(array('status'=>1, 'message'=>L('operation_success')));
	}

	/**
	 * 强制退出
	 */
	private function _force_logout(){
		$_SESSION = array();
		session_destroy();
		echo '<script type="text/javascript">window.top.location="http://www.yzmcms.com?from=' . urlencode(SITE_URL) . '"</script>';
		exit;
	}

	/**
	 * 登录页渲染
	 */
	private function _login(){
		ob_start();
		include $this->admin_tpl('login');
		$data = ob_get_contents();
		ob_end_clean();
		echo $data.base64_decode('PGRpdiBzdHlsZT0iZGlzcGxheTpub25lIj5Qb3dlcmVkIGJ5IDxhIGhyZWY9Imh0dHA6Ly93d3cueXptY21zLmNvbSIgdGFyZ2V0PSJfYmxhbmsiPll6bUNNUzwvYT48L2Rpdj4=');
	}
}
?>
