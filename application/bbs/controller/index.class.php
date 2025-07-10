<?php
/**
 * YzmCMS 论坛模块 - 论坛前台
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2023-05-25
 */
 
defined('IN_YZMPHP') or exit('Access Denied');
yzm_base::load_common('function/function.php', 'bbs');
new_session_start();

class index{
	
	private $userid;
	private $username;
	private $groupid;
	private $fix,$pid,$forum_plate,$plate;
	
	public function __construct() {
		//设置论坛模块模板风格
		$this->fix = ismobile() ? 'm_' : '';
		set_module_theme('default');
		$this->_check_auth();
	}
	
	
	/**
	 * 首页
	 */
	public function init() {
		$site = array_merge(get_config(), get_bbs_config());
		$seo_title = '论坛社区 - '.$site['bbs_name'];
		$keywords = $site['bbs_keyword'];
		$description = $site['bbs_description'];
		$userid = $this->userid;
		$username = $this->username;
		$forum_plate = $this->forum_plate;
		//置顶
		$top_data = D('forum_post')->field('id,plate_id,title,inputtime,updatetime,userid,username,click,status,comment,praise,tread,tags')->where("FIND_IN_SET('1', tags)")->order('id DESC')->limit('6')->select();
		//最新
		$list_data = D('forum_post')->field('id,plate_id,title,inputtime,updatetime,userid,username,click,status,comment,praise,tread,tags')->where(array('status'=>1))->order('updatetime DESC')->limit(20)->select();
		//帖子排行
		$member_list = D('member')->alias('a')->join('yzmcms_member_detail AS b ON a.userid=b.userid')->field('a.userid,nickname,userpic,posts')->order('point DESC')->limit(8)->select();
		//热门推荐
		$recommend_list = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where("status=1 AND FIND_IN_SET('3',tags)")->order('id DESC')->limit('15')->select();
		include template('bbs', $this->fix.'index');
	}
	
	
	/**
	 * 列表页
	 */
	public function lists() {
		
		$pid = $this->pid;
		//SEO相关设置
		$site = array_merge(get_config(), get_bbs_config());
		$seo_title = $this->plate['plate_name'].' - '.$site['bbs_name'];
		$keywords = $this->plate['keywords'] ? $this->plate['keywords'] : $site['bbs_keyword'];
		$description = $this->plate['description'] ? $this->plate['description'] :$site['bbs_description'];

		$userid = $this->userid;
		$username = $this->username;
		$forum_plate = $this->forum_plate;
		
		//最新
		yzm_base::load_sys_class('page','',0);
		$total = D('forum_post')->where(array('plate_id'=>$this->pid, 'status'=>1))->total();
		$page = new page($total, 20);
		$list_data = D('forum_post')->field('id,plate_id,title,inputtime,updatetime,userid,username,click,status,comment,praise,tread,tags')->where(array('plate_id'=>$this->pid, 'status'=>1))->order('updatetime DESC')->limit($page->limit())->select();
		$pages = '<span class="pageinfo">共'.$total.'条记录</span>'.$page->getfull(false);
		//热门推荐
		$recommend_list = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where("status=1 AND FIND_IN_SET('3',tags)")->order('id DESC')->limit('15')->select();
		//浏览排行
		$view_list = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where("status=1")->order('click DESC')->limit('15')->select();
		include template('bbs', $this->fix.'list');
	}



	/**
	 * 搜索页
	 */
	public function search() {
		
		$pid = $this->pid;
		$q = str_replace('%', '', new_html_special_chars(strip_tags(trim($_GET['q']))));
		if(!$q) showmsg('关键字不能为空！', 'stop');

		//SEO相关设置
		$site = array_merge(get_config(), get_bbs_config());
		$seo_title = $q.'的搜索结果 - '.$site['bbs_name'];
		$keywords = $site['bbs_keyword'];
		$description = $site['bbs_description'];

		$userid = $this->userid;
		$username = $this->username;
		$forum_plate = $this->forum_plate;
		
		//搜索结果
		yzm_base::load_sys_class('page','',0);
		$where = "`status` = 1 AND (`title` LIKE '%$q%' OR `content` LIKE '%$q%') ";
		$total = D('forum_post')->where($where)->total();
		$page = new page($total, 20);
		$list_data = D('forum_post')->field('id,plate_id,title,inputtime,updatetime,userid,username,click,status,comment,praise,tread,tags')->where($where)->order('updatetime DESC')->limit($page->limit())->select();
		$pages = '<span class="pageinfo">共'.$total.'条记录</span>'.$page->getfull(false);
		//热门推荐
		$recommend_list = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where("status=1 AND FIND_IN_SET('3',tags)")->order('id DESC')->limit('15')->select();
		//浏览排行
		$view_list = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where("status=1")->order('click DESC')->limit('15')->select();
		include template('bbs', $this->fix.'search');
	}

	
	
	/**
	 * 内容页
	 */
	public function show() {
		$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if(!$id) showmsg('缺少参数！','stop');
		$data = D('forum_post')->where(array('id'=>$id))->find();
		if(!$data || $data['status'] != 1) showmsg('内容不存在或未通过审核！','stop');
		extract($data);
		
		$groupids_view = get_plateinfo($plate_id, 'groupids_view');
		//板块设置了阅读权限
		if($groupids_view){
			if($this->groupid==0) showmsg('该板块需要验证用户权限，请先登录！', url_referer(), 2);
			if($this->groupid < $groupids_view)  showmsg('无权访问该板块，请升级会员组！', 'stop');
		}
		
		// 获取版主信息
		$member_info = D('member')->alias('m')->field('m.userid,m.username,m.groupid,userpic,groupid,motto')->join('yzmcms_member_detail d ON m.userid=d.userid')->where(array('m.userid' => $userid))->find();
		if(!$member_info['userpic'])
		$member_info['userpic'] = STATIC_URL.'bbs/images/avatar.png';
		$groupinfo = get_groupinfo($member_info['groupid']);

		//SEO相关设置
		$site = array_merge(get_config(), get_bbs_config());
		$seo_title = $title.' - '.$site['bbs_name'];
		$keywords = $keywords ? $keywords : $site['bbs_keyword'];
		$description = str_cut(strip_tags($content), 250);

		$pid = $plate_id;
		$userid = $this->userid;
		$username = $this->username;
		$forum_plate = $this->forum_plate;

		//更新点击量
		D('forum_post')->update('`click` = `click`+1', array('id' => $id));

		//最近更新
		$list_data = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where(array('status'=>1))->order('id DESC')->limit(15)->select();

		//帖子评论
		$comment_list = D('forum_comment')->where(array('forumid' => $id, 'status' => 1))->order('praise DESC,id DESC')->limit(20)->select();
		
		include template('bbs', $this->fix.'show');
	}


	/**
	 * 发布帖子
	 */
	public function add(){
		if(!$this->userid) showmsg('请先登录！', url_referer(), 2);
		$memberinfo = get_memberinfo($this->userid); $is_vip = $memberinfo['vip']&&$memberinfo['overduedate']>SYS_TIME ? 1 :0;
		if(is_post()){
			if(empty($_SESSION['code']) || strtolower($_POST['code'])!=$_SESSION['code']){
				$_SESSION['code'] = '';
				return_json( array('status'=>-1, 'message'=>'验证码错误') );
			}
			$_SESSION['code'] = '';


			//判断用户是否为正常状态
			$status = D('member')->field('status')->where(array('userid' => $this->userid))->one();
			if($status != 1) return_json( array('status'=>0, 'message'=>'该用户无权发帖，详情联系管理员！') );

			$plate_id = isset($_POST['plate_id']) ? intval($_POST['plate_id']) : return_json( array('status'=>0,'message'=>'板块ID不能为空') );
			$groupids_add = D('forum_plate')->field('groupids_add')->where(array('plate_id' => $plate_id))->one();
			if($this->groupid < $groupids_add)  return_json( array('status'=>0,'message'=>'无权在此板块发帖！') );

			$title = htmlspecialchars($_POST['title']);
			// $content = strip_tags($_POST['content'], '<p><a><br><img><ul><li><strong>');
			if(strlen(strip_tags($_POST['content'])) < 50) return_json( array('status'=>-1,'message'=>'内容长度不能低于50个字符！') );
			$content = $is_vip ? $_POST['content'] : new_remove_xss($_POST['content']);  //有添加、编辑帖子两处位置需修改
			if(empty($title) || empty($content)) return_json( array('status'=>0,'message'=>'标题或内容不能为空！') );
			
			
			//新增屏蔽词处理，用户触发屏蔽词立即锁定
			$arr = explode('|', get_config('prohibit_words'));
			foreach($arr as $val){
				if(strstr($title, $val) || strstr($content, $val)){
					D('member')->update(array('status'=>2), array('userid' => $this->userid));
					return_json( array('status'=>0,'message'=>'您已被锁定，请联系管理员！') );
				}
			}			

			//限制每个用户每日发帖数量
			$max_total = get_bbs_config('posts_day_limit');
			$posts_check = get_bbs_config('posts_check');
			$date = strtotime(date('Y-m-d'));
			$total = D('forum_post')->where(array('userid' => $this->userid, 'inputtime>' => $date))->total();
			if($total >= $max_total) return_json( array('status'=>0,'message'=>'用户每日发帖数量为'.$max_total.'篇，明天再来吧~') );

			$data = array();
			$data['plate_id'] = $plate_id;
			$data['title'] = $title;
			$data['keywords'] = htmlspecialchars($_POST['keywords']);
			$data['content'] = $content;
			$data['userid'] = $this->userid;
			$data['username'] = $this->username;
			$data['inputtime'] = $data['updatetime'] = SYS_TIME;
			$data['ip'] = getip();
			$data['status'] = $posts_check ? 0 : 1;
			if($is_vip){
				$data['attachment'] = htmlspecialchars($_POST['attachment']);
			}
			$id = D('forum_post')->insert($data);
			D('member_detail')->update('`posts` = `posts`+1', array('userid' => $this->userid));
			$point = yzm_base::load_model('point', 'member');
			$point->point_add(1,get_bbs_config('posts_point'),9,$this->userid,$this->username,0,'ID:'.$id);
			!$posts_check ? return_json( array('status'=>1,'message'=>'发布成功！') ) : return_json( array('status'=>1,'message'=>'发布成功，待管理员审核后显示！') );
		}
		$site = array_merge(get_config(), get_bbs_config());
		if(!$site['posts_open']) showmsg('管理员已关闭发帖通道！', 'stop');
		$seo_title = '发布新帖 - '.$site['bbs_name'];
		$keywords = $site['bbs_keyword'];
		$description = $site['bbs_description'];
		$userid = $this->userid;
		$username = $this->username;
		$forum_plate = $this->forum_plate;
		include template('bbs', $this->fix.'add');
	}



	/**
	 * 编辑帖子
	 */
	public function edit(){
		if(!$this->userid) showmsg('请先登录！', url_referer(), 2);
		$memberinfo = get_memberinfo($this->userid); $is_vip = $memberinfo['vip']&&$memberinfo['overduedate']>SYS_TIME ? 1 :0;
		$id = input('id', 0, 'intval');
		$post_data = D('forum_post')->where(array('id' => $id))->find();
		if(!$post_data || $post_data['userid']!=$this->userid) showmsg(L('illegal_operation'), 'stop');
		if(is_post()){
			if(empty($_SESSION['code']) || strtolower($_POST['code'])!=$_SESSION['code']){
				$_SESSION['code'] = '';
				return_json( array('status'=>-1, 'message'=>'验证码错误') );
			}
			$_SESSION['code'] = '';

			$plate_id = isset($_POST['plate_id']) ? intval($_POST['plate_id']) : return_json( array('status'=>0,'message'=>'板块ID不能为空') );
			$groupids_add = D('forum_plate')->field('groupids_add')->where(array('plate_id' => $plate_id))->one();
			if($this->groupid < $groupids_add)  return_json( array('status'=>0,'message'=>'无权在此板块发帖！') );

			$title = htmlspecialchars($_POST['title']);
			// $content = strip_tags($_POST['content'], '<p><a><br><img><ul><li><strong>');
			if(strlen(strip_tags($_POST['content'])) < 50) return_json( array('status'=>-1,'message'=>'内容长度不能低于50个字符！') );
			$content = $is_vip ? $_POST['content'] : new_remove_xss($_POST['content']);    //有添加、编辑帖子两处位置需修改
			if(empty($title) || empty($content)) return_json( array('status'=>0,'message'=>'标题或内容不能为空！') );
			
			$posts_check = get_bbs_config('posts_check');			
			//新增屏蔽词处理，用户触发屏蔽词立即锁定
			$arr = explode('|', get_config('prohibit_words'));
			foreach($arr as $val){
				if(strstr($title, $val) || strstr($content, $val)){
					D('member')->update(array('status'=>2), array('userid' => $this->userid));
					return_json( array('status'=>0,'message'=>'您已被锁定，请联系管理员！') );
				}
			}			

			$data = array();
			$data['plate_id'] = $plate_id;
			$data['title'] = $title;
			$data['keywords'] = htmlspecialchars($_POST['keywords']);
			$data['content'] = $content;
			$data['updatetime'] = SYS_TIME;
			$data['ip'] = getip();
			$data['status'] = $posts_check ? 0 : 1;
			if($is_vip){
				$data['attachment'] = htmlspecialchars($_POST['attachment']);
			}
			D('forum_post')->update($data, array('id' => $id));
			!$posts_check ? return_json( array('status'=>1,'message'=>'编辑成功！') ) : return_json( array('status'=>1,'message'=>'编辑成功，待管理员审核后显示！') );
		}
		$site = array_merge(get_config(), get_bbs_config());
		$seo_title = '编辑帖子 - '.$site['bbs_name'];
		$keywords = $site['bbs_keyword'];
		$description = $site['bbs_description'];
		$userid = $this->userid;
		$username = $this->username;
		$forum_plate = $this->forum_plate;
		include template('bbs', $this->fix.'edit');
	}



	/**
	 * 删除帖子
	 */
	public function del(){
		if(!$this->userid) showmsg('请先登录！', url_referer(), 2);
		$id = isset($_GET['id']) ? intval($_GET['id']) : showmsg(L('lose_parameters'), 'stop');

		$data = D('forum_post')->field('userid,status')->where(array('id' =>$id))->find();
		//只能删除自己发布的帖子
		if($data && $data['userid'] == $this->userid){
			D('forum_post')->delete(array('id' => $id));	//删除帖子
			D('forum_comment')->delete(array('forumid' => $id));	 //删除帖子评论
			D('member_detail')->update('`posts` = `posts`-1', array('userid' => $this->userid)); //更新帖子数量
		}
		showmsg(L('operation_success'), '', 1);
	}
	
	
	
    /**
     * 处理URL地址
     */
    public function handle_url(){
        $url = isset($_GET['url']) ? urldecode($_GET['url']) : showmsg(L('lose_parameters'), 'stop');

        // 检查来源
        if(!strstr(HTTP_REFERER, HTTP_HOST)) showmsg(L('illegal_operation'), 'stop');

        // 检查目标URL状态码
        // $curl = curl_init($url);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($curl, CURLOPT_NOSIGNAL, true); 
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1500); 
        // curl_setopt($curl, CURLOPT_NOBODY, true);
        // $output = curl_exec($curl);
        // $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // curl_close($curl);
        // $message = $http_code==200 ? '' : '此链接可能打不开';

        $site = array_merge(get_config(), get_bbs_config());
        $keywords = $site['bbs_keyword'];
        $description = $site['bbs_description'];
        
        include template('bbs', 'handle_url');
    }
	
	
	
	/**
	 * ajax登录
	 */
	public function ajax_login(){
		if(is_post()){		
			if(empty($_SESSION['code']) || strtolower($_POST['code'])!=$_SESSION['code']){
				$_SESSION['code'] = '';
				return_json( array('status'=>0,'message'=>L('code_error')) );
			}
			$_SESSION['code'] = '';
			$member = D('member');
			$username = isset($_POST['username']) ? trim($_POST['username']) : return_json( array('status'=>0,'message'=>L('lose_parameters')) );
			$password = password($_POST['password']);

			//电子邮箱和用户名两种登录方式
			$where = is_email($username) ? array('email'=>$username) : array('username'=>$username);
			
			$data = $member->where($where)->find();
			if(!$data) return_json( array('status'=>0,'message'=>L('user_does_not_exist')) );
			if($data['password'] != $password) return_json( array('status'=>0,'message'=>L('password_error')) );
			if($data['status'] == '0') 
				return_json( array('status'=>0,'message'=>'用户未通过审核！') );
			else if($data['status'] == '2') 
				return_json( array('status'=>0,'message'=>'用户已锁定！') );
			else if($data['status'] == '3')
				return_json( array('status'=>0,'message'=>'用户已被管理员拒绝！') ); 
			
			$_SESSION['_userid'] = $data['userid'];
			$_SESSION['_username'] = $data['username'];
			set_cookie('_userid', $data['userid'], 3600*24*30, true);
			set_cookie('_username', $data['username'], 3600*24*30, true);
			set_cookie('_groupid', $data['groupid'], 3600*24*30, true);	
			set_cookie('_nickname', $data['username'], 3600*24*30);
			
			
			//每日登录，增加积分和经验，并更新新用户组
			$last_day = date('d', $data['lastdate']);
			if($last_day != date('d')  &&  SYS_TIME>$data['lastdate'] && get_config('login_point')>0){
			     $point = yzm_base::load_model('point', 'member');
				 $point->point_add('1',get_config('login_point'),'0',$data['userid'],$data['username'],$data['experience']);		
			}
			
			$where = '';
			if($data['vip'] && $data['overduedate']<SYS_TIME)	$where .= '`vip`=0,';   //如果用户是vip用户，检查vip是否过期
			
			$where .= '`lastip`="'.getip().'",`lastdate`="'.SYS_TIME.'",`loginnum`=`loginnum`+1';
			$member->update($where, array('userid'=>$data['userid']));
			return_json( array('status'=>1,'message'=>L('login_success')) );
		}
		include template('bbs', 'ajax_login');
	}
	
	

	private function _check_auth(){
		$this->pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
		$this->userid = get_cookie('_userid') ? intval(get_cookie('_userid')) : 0;
		$this->username = get_cookie('_username') ? safe_replace(get_cookie('_username')) : '';
		$this->groupid = get_cookie('_groupid') ? intval(get_cookie('_groupid')) : 0;
		$this->forum_plate = D('forum_plate')->field('plate_id,plate_name,groupids_view,groupids_add')->order('listorder ASC,plate_id ASC')->select();
		
		if($this->pid){
			$this->plate = get_plateinfo($this->pid);
			$groupids_view = $this->plate ? $this->plate['groupids_view'] : showmsg('板块不存在！', 'stop');
			if($groupids_view>0 && $this->groupid==0) showmsg('该板块需要验证用户权限，请先登录！', url_referer(), 2);
			if($this->groupid < $groupids_view)  showmsg('无权访问该板块，请升级会员组！', 'stop');
		}

		//验证用户名，从新登陆
		if($this->userid && $this->username){
			if(!isset($_SESSION['_userid'])){
				$data = D('member')->field('userid,username,status,password,lastdate')->where(array('userid' => $this->userid))->find();
				$user_pass = get_cookie('user_pass');
				if($data['status']==1 && $data['password']==$user_pass){
					$_SESSION['_userid'] = $data['userid'];
					$_SESSION['_username'] = $data['username'];
					
					if($data['lastdate'] < (SYS_TIME-1800)){
        				D('member')->update(array('lastdate'=>SYS_TIME, 'lastip'=>getip()), array('userid'=>$this->userid));
        			}

				}else{
					del_cookie('_userid');
					del_cookie('_username');
					del_cookie('_nickname');
					del_cookie('user_pass');
					del_cookie('_groupid');
				}
			}
		}
	}
	
	
	/**
	 * 管理员特殊操作
	 */
	public function admin(){
		if(!$this->userid) showmsg('请先登录！', url_referer(), 2);
		$id = isset($_GET['id']) ? intval($_GET['id']) : showmsg(L('lose_parameters'), 'stop');

		$data = D('forum_post')->field('userid,status')->where(array('id' =>$id))->find();
		//验证管理员
		if($this->userid == 1){
			D('forum_post')->update(array('status' => 0), array('id' => $id));	//下线处理
			D('member')->update(array('status' => 2), array('userid' => $data['userid']));	 //锁定会员
		}
		showmsg(L('operation_success'), U('init'), 1);
	}

}