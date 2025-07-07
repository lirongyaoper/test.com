<?php

defined('IN_YZMPHP') or exit('Access Denied'); 
yzm_base::load_controller('common', ROUTE_M, 0);

class index extends common {

	/**
	 * 管理员后台
	 */
	public function init() {
		debug();
		$total = D('guestbook')->field('id')->where(array('replyid'=>'0','siteid'=>self::$siteid,'isread'=>'0'))->total();
		include $this->admin_tpl('index');
	}
	

	/**
	 * 管理员登录
	 */	
	public function login() {
		if(is_post()) {
			if(empty($_SESSION['code']) || strtolower($_POST['code'])!=$_SESSION['code']){
				$_SESSION['code'] = '';
				return_json(array('status'=>0,'message'=>L('code_error')));
			}
			$_SESSION['code'] = '';
			$_POST['username'] = trim($_POST['username']);
			if(!is_username($_POST['username'])) return_json(array('status'=>0,'message'=>L('user_name_format_error')));
			if(!is_password($_POST['password'])) return_json(array('status'=>0,'message'=>L('password_format_error')));
			$res = M('admin')->check_admin($_POST['username'], password($_POST['password']));
			if($res['status']){
				return_json(array('status'=>1,'message'=>L('login_success'),'url'=>U('init')));
			}else{
				return_json($res);
			}
		}else{
			$this->_login();
		}
	}
	

	/**
	 * 管理员退出
	 */		
	public function public_logout() {
		unset($_SESSION['adminid'], $_SESSION['adminname'], $_SESSION['roleid'], $_SESSION['admininfo']);
		del_cookie('adminid');
		del_cookie('adminname');
		showmsg(L('you_have_safe_exit'), U('login'), 1);
	}
	

	/**
	 * 管理员公共桌面
	 */		
	public function public_home() {
		yzm_base::load_common('lib/update'.EXT, 'lry_admin_center');
		if(!class_exists('update')) showmsg('缺少必要的系统文件，请联系YzmCMS官方！', 'stop');
		if(isset($_GET['up'])){update::check();}
		ob_start();	
		// 统计信息
        $count = array();
        $count[] = D('all_content')->where(array('siteid'=>self::$siteid))->total();
        $count[] = D('module')->total();
        $count[] = D('member')->total();
        $count[] = D('admin')->total();
		include $this->admin_tpl('public_home');
		$data = ob_get_contents();
		ob_end_clean();
		system_information($data);		
	}


	/**
	 * 清除错误日志
	 */		
	public function public_clear_log() {
		if($_SESSION['roleid'] != 1) return_json(array('status'=>0,'message'=>'此操作仅限于超级管理员！'));
		if(is_file(YZMPHP_PATH.'cache/error_log.php')){
			$res = @unlink(YZMPHP_PATH.'cache/error_log.php');
			if(!$res) return_json(array('status'=>0,'message'=>L('operation_failure')));
			D('admin_log')->insert(array('module'=>ROUTE_M,'action'=>ROUTE_C,'adminname'=>$_SESSION['adminname'],'adminid'=>$_SESSION['adminid'],'querystring'=>'清除错误日志','logtime'=>SYS_TIME,'ip'=>self::$ip));
		}
		return_json(array('status'=>1,'message'=>L('operation_success')));
	}
	
	
	/**
	 * 锁屏
	 */		
	public function public_lock_screen() {
		$_SESSION['yzm_lock_screen'] = 1;
		return_json(array('status'=>1,'message'=>L('operation_success')));
	}


	/**
	 * 解锁
	 */		
	public function public_unlock_screen() {
		$res = M('admin')->check_admin($_SESSION['adminname'], password($_POST['password']));
		if(!$res['status']) return_json($res);
		$_SESSION['yzm_lock_screen'] = 0;
		return_json(array('status'=>1,'message'=>L('login_success')));
	}
	

	private function _login(){
		ob_start();	
		include $this->admin_tpl('login');
		$data = ob_get_contents();
		ob_end_clean();
		echo $data.base64_decode('PCEtLSBQb3dlcmVkIEJ5IFl6bUNNU+WGheWuueeuoeeQhuezu+e7nyAgLS0+');
	}
}