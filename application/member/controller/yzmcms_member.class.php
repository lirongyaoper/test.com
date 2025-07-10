<?php
/**
 * YzmCMS新版会员注册
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2019-08-23
 */
 
defined('IN_YZMPHP') or exit('Access Denied'); 
new_session_start();

class yzmcms_member{

	public function __construct() {
		//设置会员模块模板风格
		set_module_theme(get_config('member_theme'));
	}

	
	/**
	 * 会员注册
	 */	
	public function init(){ 
		
		include template('member', 'register');
	}


	/**
	 * 发送邮箱验证码
	 */	
	public function send_code(){ 
		$email = isset($_POST['email']) ? trim($_POST['email']) : return_json(array('status'=>0,'message'=>'电子邮箱不能为空！'));
		if(!is_email($email))  return_json(array('status'=>0,'message'=>'邮箱格式不正确！'));
		$arr = explode('@', $email); 
		if(!checkdnsrr($arr[1], 'MX')) return_json(array('status'=>0,'message'=>'不要填写不存在的邮箱！'));
 
		$code = isset($_POST['code']) ? trim($_POST['code']) : return_json(array('status'=>0,'message'=>'验证码不能为空！'));
		if(empty($_SESSION['code']) || strtolower($code)!=$_SESSION['code']){
			$_SESSION['code'] = '';
			return_json(array('status'=>0,'message'=>'验证码不正确！'));
		}
		$_SESSION['code'] = '';

		//判断邮箱是否存在
		$userid = D('member')->field('userid')->where(array('email' => $email ))->one();
		if($userid) return_json(array('status'=>0,'message'=>'邮箱已注册！'));

		//发送验证码
		if(!isset($_SESSION['code_time']) || ($_SESSION['code_time']+60)<SYS_TIME){
			$email_code = create_randomstr();
			$_SESSION['email_code'] = $email_code;
			$message = '您正在注册'.get_config('site_name').'会员，本次验证码是：【'.$email_code.'】，如非本人操作，请忽略！';
			$res = sendmail($email, '会员邮箱验证', $message);
			if(!$res) return_json(array('status'=>0,'message'=>'发送验证码失败，请联系管理员！'));
			$_SESSION['code_time'] = SYS_TIME;
			$_SESSION['email'] = $email;
			return_json(array('status'=>1,'message'=>'发送成功！'));			
		}else{
			return_json(array('status'=>0,'message'=>'验证码已发送，请稍后再试！'));
		}
		
	}

	/**
	 * 会员注册检查所有字段
	 */	
	public function checkall(){ 
		$username = isset($_POST['username']) ? trim($_POST['username']) : return_json(array('status'=>0,'message'=>'用户名不能为空！'));
		if(!is_username($username))  return_json(array('status'=>0,'message'=>'用户名格式不正确！'));

		$password = isset($_POST['password']) ? trim($_POST['password']) : return_json(array('status'=>0,'message'=>'密码不能为空！'));
		if(!is_password($password)) return_json(array('status'=>0,'message'=>'密码格式不正确！'));

		//如果开启了会员邮箱注册验证，验证邮箱验证码，否则验证普通验证码
		if(get_config('member_email')){
			$email_code = isset($_POST['email_code']) ? trim($_POST['email_code']) : return_json(array('status'=>0,'message'=>'邮箱验证码不能为空！'));
			if(empty($_SESSION['email_code']) || $email_code != $_SESSION['email_code'])  return_json(array('status'=>0,'message'=>'邮箱验证码不正确！'));
			$email = isset($_SESSION['email']) ? $_SESSION['email'] : return_json(array('status'=>0,'message'=>'邮箱地址不能为空！'));
		}else{
			$code = isset($_POST['code']) ? trim($_POST['code']) : return_json(array('status'=>0,'message'=>'验证码不能为空！'));
			if(empty($_SESSION['code']) || strtolower($code)!=$_SESSION['code']){
				$_SESSION['code'] = '';
				return_json(array('status'=>-1,'message'=>'验证码不正确！'));
			}
			$_SESSION['code'] = '';
			
			$email = isset($_POST['email']) ? trim($_POST['email']) : return_json(array('status'=>0,'message'=>'电子邮箱不能为空！'));
			if(!is_email($email))  return_json(array('status'=>0,'message'=>'邮箱格式不正确！'));	
		}

		$userid = D('member')->field('userid')->where(array('username' => $username ))->one();
		if($userid) return_json(array('status'=>0,'message'=>'用户名已注册！'));

		$userid = D('member')->field('userid')->where(array('email' => $email ))->one();
		if($userid) return_json(array('status'=>0,'message'=>'邮箱已注册！'));

		unset($_SESSION['email_code'], $_SESSION['code_time']);
		$_SESSION['member_info'] = array('username' => $username, 'email' => $email, 'password' => $password);
		if(get_config('member_email')){
			$_SESSION['member_info']['email_status'] = 1;
		}
		$_SESSION['is_check'] = 1;
		return_json(array('status'=>1,'message'=>'通过检查！'));
	}


	/**
	 * 会员注册处理
	 */	
	public function register(){ 
		$config = get_config();
		if(!isset($_SESSION['is_check'])) showmsg('请勿非法操作！', 'stop');

		$data = $_SESSION['member_info'];
		$data['nickname'] = $data['username'];
		$data["password"] = password($data['password']);
		$data['regdate'] = $data['lastdate'] = SYS_TIME;
		$data['regip'] = $data['lastip'] = getip();
		$data['groupid'] = '1';
		$data['amount'] = '0.00';
		$data['point'] = $data['experience'] = $config['member_point'];	 //经验和积分
		$data['status'] = 1;		
		$data['userid'] = D('member')->insert($data, true);		
		if(!$data['userid']) showmsg('注册失败，请联系管理员！', 'stop');
		
		D('member_detail')->insert($data, true, false); //插入附表

		unset($_SESSION['member_info'], $_SESSION['is_check']);
		showmsg('注册成功！', U('member/index/login'), 1);		

	}
	

}