<?php
/**
 * YzmCMS 签到会员
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2022-06-29
 */
 
defined('IN_YZMPHP') or exit('Access Denied'); 

class member_sign{

	public $userid;

	public function __construct() {

		$this->userid = intval(get_cookie('_userid'));

		//设置会员模块模板风格
		set_module_theme(get_config('member_theme'));
	}

	
	/**
	 * 用户签到
	 */	
	public function init(){ 

		if(!$this->userid) return_json(array('status'=>0,'message'=>'请先登录！'));

		$res = D('member')->field('status,username')->where(array('userid'=>$this->userid))->find();
		if($res['status'] != 1) return_json(array('status'=>0,'message'=>'你无权签到，请联系管理员！'));

		$data = D('member_sign')->field('inputtime,continuity_day')->where(array('userid' => $this->userid))->order('id DESC')->find();
		$inputtime = $data ? $data['inputtime'] : 0;
		
		//获取今天的0点的时间戳
		$starttime = strtotime(date('Y-m-d'));
		if($inputtime > $starttime)  return_json(array('status'=>0, 'message'=>'今日已签到！'));
		
		//连续签到天数
		$continuity_day = 1;
		if($inputtime && (($inputtime + 24*3600)>$starttime)){ 
			$continuity_day = $data['continuity_day']+1;
		}

		//获取积分
		$point = $this->_get_point($continuity_day);

		$data = array(
			'userid' => $this->userid,
			'inputtime' => SYS_TIME,
			'continuity_day' => $continuity_day,
			'point' => $point
		);
		
		D('member_sign')->insert($data);

		//奖励积分
		$member_point = yzm_base::load_model('point', 'member');
		$member_point->point_add(1, $point, 10, $this->userid, $res['username'], 0, '连续签到'.$continuity_day.'天', '', false);	

		return_json(array('status'=>1,'message'=>'签到成功，积分 + '.$point, 'data'=>$data));
	}


	/**
	 * 签到说明
	 */	
	public function explain(){

		include template('bbs', 'sign_explain');
	}


	/**
	 * 补签
	 */
	public function repair_sign(){

		// 需要补签的日期，格式：2022-06-29
		$repair_sign = '2022-06-28';
		$repair_sign = !isset($_GET['type']) ? '2022-06-28' : '2022-06-29';
		if(isset($_GET['type'])&&$_GET['type']==2) $repair_sign = '2022-07-01';

		if(!$this->userid) showmsg('请先登录！', 'stop');
		$res = D('member')->field('status,username')->where(array('userid'=>$this->userid))->find();
		if($res['status'] != 1) showmsg('你无权签到，请联系管理员！', 'stop');

		$repair_sign_time = strtotime($repair_sign.' '.date('H:i:s'));
		if(!$repair_sign_time || $repair_sign_time>SYS_TIME) showmsg('参数错误！', 'stop');


		if(SYS_TIME-$repair_sign_time > 3600*24*30) showmsg('只能补签30天之内的日期！', 'stop');


		// 查询比补签日期大的签到记录
		$repair_sign_time_t = strtotime($repair_sign);
		$data = D('member_sign')->field('id,inputtime,continuity_day,point')->where(array('userid' => $this->userid,'inputtime>'=>$repair_sign_time_t))->order('id ASC')->select();


		// 查询当天有没有签到
		$inputtime = $data ? $data[0]['inputtime'] : 0;
		if($inputtime && $inputtime<($repair_sign_time_t+3600*24)) showmsg('当天不需要补签！', 'stop');


		// 查询比补签当天小的记录，获取原来的连续签到次数
		$r = D('member_sign')->field('continuity_day,inputtime')->where(array('userid' => $this->userid,'inputtime<'=>$repair_sign_time_t))->order('id DESC')->find();
		$continuity_day = $r&&$r['inputtime']>($repair_sign_time_t-3600*24) ? intval($r['continuity_day'])+1 : 1;

		//获取积分
		$point = $this->_get_point($continuity_day);

		// 插入补签当天记录
		D('member_sign')->insert(array(
			'userid' => $this->userid,
			'inputtime' => $repair_sign_time,
			'continuity_day' => $continuity_day,
			'point' => $point
		));

		//奖励补签当天积分
		$member_point = yzm_base::load_model('point', 'member');
		$member_point->point_add(1, $point, 10, $this->userid, $res['username'], 0, '通过补签，得到连续签到'.$continuity_day.'天', '', false);

		// 弥补上补签之后的记录
		foreach($data as $key=>$val){
			D('member_sign')->delete(array('id'=>$val['id']));  //需要删除已经打卡过的id，因为连续签到查询时是按照id倒序排的
			D('member_sign')->insert(array(
				'userid' => $this->userid,
				'inputtime' => $val['inputtime'],
				'continuity_day' => $continuity_day+$key+1,
				'point' => $val['point'],
			));

		}

		showmsg('恭喜你补签成功！', 'stop');
	}


	/**
	 * 根据连续签到天数获取对应积分
	 */	
	private function _get_point($continuity_day){
		//如果连续签到次数小于5天，则是第一天2分，第二天4分...
		if($continuity_day < 5){
			return $continuity_day*2;
		}

		//如果连续签到次数大于等于5天，则为固定值
		return 10;
	}

}