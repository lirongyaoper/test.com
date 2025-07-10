<?php
/**
 * YzmCMS论坛下载扣除积分
 * www.yzmcms.com
 * QQ： 214243830
 */
	 
	 
class down{
	

	public function init() {

		$id = isset($_GET['id']) ? intval($_GET['id']) : showmsg('参数错误！', 'stop');
		$flag = 'bbs_'.$id;
		$data = D('forum_post')->field('userid,point,status,attachment')->where(array('id' => $id))->find();
		$downurl = $data['attachment'];
		$downpoint = $data['point'];
		if(!$downurl) showmsg('参数错误，获取下载地址失败！', 'stop');

		//如果下载积分为0，则直接跳转下载
		if($downpoint==0){
			showmsg('正在前往下载地址...', $downurl, 1);
		}
		
		$userid = intval(get_cookie('_userid'));
		if(!$userid){
			showmsg(L('need_login'), url_referer(1, HTTP_REFERER), 2);
		}
		
		// 检查是否是作者自己
		if($data['userid'] == $userid){
			showmsg('这个作品是你发布的，所以仅对你免费下载...', $downurl, 2);
		}
		
		//检查24小时内是否支付过
		$data = D('pay_spend')->field('creat_time')->where(array('userid'=>$userid,'remarks'=>$flag))->order('id DESC')->find();
		if($data && $data['creat_time']+86400 > SYS_TIME) {
			showmsg('您已24小时内支付过，正在前往下载地址...', $downurl, 1);
		}
		
		$data = D('member')->field('point,vip,overduedate')->where(array('userid'=>$userid))->find();
		
		//检查是否为vip会员
		// if($data['vip']){
			// if($data['overduedate'] > SYS_TIME)	{
				// showmsg('您是VIP会员，享受免费下载特权...', $downurl, 1);
			// }
			// D('member')->update(array('vip'=>0), array('userid'=>$userid));
		// }


		$point = $data['point'];
		if($point < $downpoint){
			showmsg("您不足 <span style='color:red'>{$downpoint}点</span> 积分，无法下载！", 'stop');
		}else{
			$text = "您目前有 <span style='color:red'>{$point}点</span> 积分，下载此文件，需扣除 <span style='color:red'>{$downpoint}点</span> 积分！";
			$parurl = 'par='.string_auth($flag.'|'.$downpoint.'|'.$downurl);
			$this->_template($text,$parurl);
		}
	}


	/**
	 * 扣除积分
	 */	
	public function spend_point(){
		if(!isset($_GET['par'])) showmsg(L('lose_parameters'), 'stop');
		$par = new_html_special_chars($_GET['par']);
		$auth = string_auth($par,'DECODE');
		if(strpos($auth,'|')===false) showmsg(L('illegal_parameters'), 'stop');
		$auth_str = explode('|', $auth);
		$flag = $auth_str[0];
		if(!preg_match('/^bbs_([0-9]+)$/', $flag)) showmsg(L('illegal_parameters'), 'stop');
		$readpoint = intval($auth_str[1]);
		$http_referer = $auth_str[2];
		yzm_base::load_model('point', 'member');
		$point = new point();
		$userid = intval(get_cookie('_userid'));
		$username = safe_replace(get_cookie('_username'));
		$point->point_spend('1',$readpoint,'8',$userid,$username,$flag);
		showmsg('支付成功，正在前往下载地址...', $http_referer, 1);
	}


	private function _template($text, $parurl){
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>	
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	    <title>YzmCMS提示信息</title>
    <style>
	  *{padding:0;margin:0;}
	  body{background:#fff;color:#000;font-family:"Microsoft Yahei","Hiragino Sans GB","Helvetica Neue",Helvetica,tahoma,arial,"WenQuanYi Micro Hei",Verdana,sans-serif;}
	  .yzm-msg{width:500px;position:absolute;top:44%;left:50%;margin:-87px 0 0 -250px;line-height:30px;text-align:center;font-size:14px;background:#fff;box-shadow: 0px 0px 25px #999;border-radius: 3px;}
	  .yzm-msg-title{height:35px;line-height:35px;color:#fff;background:#333;}
	  .yzm-msg-body{margin:20px 0;text-align:center}
	  .yzm-info{margin-bottom:10px;}
	  .yzm-msg-body p{font-size:12px;}
	  .yzm-msg-body #exp{color:#666;margin-bottom:20px;}
	  .yzm-msg-body p a{font-size:14px;background: #62a8ea;color:#fff;padding:5px 20px;text-decoration:none;margin-right:20px;transition:all 0.3s}
	  .yzm-msg-body p a:hover{background:#0a6999}
	</style>
</head>
<body>
    <div class="yzm-msg">    	
    <div class="yzm-msg-title">提示信息</div>
	<div class="yzm-msg-body">
    <div class="yzm-info">'.$text.'</div>
		<p id="exp">温馨提示：24小时重复下载不收费</p>
	 	<p><a href="'.U('spend_point',$parurl).'" title="确定支付">确定支付</a><a href="javascript:window.opener=null;window.close();" title="取消并关闭">取消并关闭</a></p>
	 </div>
    </div> 
</body>
</html>';
	}

}