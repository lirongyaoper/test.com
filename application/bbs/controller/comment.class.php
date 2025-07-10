<?php
/**
 * YzmCMS 论坛模块 - 论坛前台评论
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2018-07-25
 */

defined('IN_YZMPHP') or exit('Access Denied');
yzm_base::load_common('function/function.php', 'bbs');
yzm_base::load_sys_class('page','',0);

class comment{

	public function init(){
		
	}


	/**
	 * 发布评论
	 */	
	public function post(){
	    if(!is_post())  return_json( array('status'=>0,'message'=>L('illegal_operation')) );

	    $userid = get_cookie('_userid') ? intval(get_cookie('_userid')) : 0;
	    $username = get_cookie('_username') ? safe_replace(get_cookie('_username')) : '';
	    if(!$userid || !$username) return_json( array('status'=>2,'message'=>'请先登录！') );

	    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	    $content = isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
	    if(!$id || !$content) return_json( array('status'=>0,'message'=>L('lose_parameters')) );

	    $is_comment = D('forum_post')->field('is_comment')->where(array('id' => $id))->one();
	    if(!$is_comment) return_json( array('status'=>0,'message'=>'该帖不允许评论！') );

	    //检查用户是否锁定
	    $status = D('member')->field('status')->where(array('userid' => $userid))->one();
	    if($status != 1) return_json( array('status'=>0, 'message'=>'用户已锁定,无权发表评论！') );

	    //限制用户发布频率
	    $data = D('forum_comment')->field('forumid, inputtime')->where(array('userid' => $userid))->order('id DESC')->find();
	    if($data && (($data['inputtime']+get_bbs_config('posts_comment_limit')) > SYS_TIME)) return_json( array('status'=>0,'message'=>'发表过快，请稍后再试！') );


	    $posts_comment_check = get_bbs_config('posts_comment_check');
	    // 发布评论@用户，短消息提醒
	    if(!$posts_comment_check){
	        preg_match_all("/\@([a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)/", $content, $user_arr);
	        $forum_title = D('forum_post')->field('title')->where(array('id'=>$id))->one();
	        foreach($user_arr[1] as $user){
	            $target_userid = D('member')->field('userid')->where(array('username'=>$user))->one();
	            if(!$target_userid) continue;

	            $content = str_replace('@'.$user, '<a href="'.U('member/myhome/init', array('userid'=>$target_userid)).'" target="_blank">@'.$user.'</a>', $content);

	            if($target_userid == $userid) continue;
	            D('message')->insert(array(
	                'send_from' => '系统',
	                'send_to' => $user,
	                'message_time' => SYS_TIME,
	                'subject' => $username.' 在【'.$forum_title.'】中提到了你',
	                'content' => '<a href="'.U('member/myhome/init', array('userid'=>$target_userid)).'" target="_blank">'.$username.'</a> 在【'.$forum_title.'】中提到了你，<a href="'.show_url($id).'" target="_blank">点击查看原文</a>。',
	                'replyid' => 0,
	                'issystem' => 1,
	            ));
	        }
	    }

	    
	    $data = array(
	        'forumid' => $id,
	        'userid' => $userid,
	        'username' => $username,
	        'userpic' => D('member_detail')->field('userpic')->where(array('userid' => $userid))->one(),
	        'inputtime' => SYS_TIME,
	        'ip' => getip(),
	        'content' => $content,
	        'status' => $posts_comment_check ? 0 : 1
	    );
	    D('forum_comment')->insert($data);
	    D('forum_post')->update('`comment` = `comment`+1', array('id' => $id));

	    //每日评论超过限制时，不奖励积分
	    $max_total = get_bbs_config('posts_comment_point_limit');
	    $date = strtotime(date('Y-m-d'));
	    $total = D('forum_comment')->where(array('userid' => $userid, 'inputtime>' => $date))->total();
	    if($total <= $max_total){
	        $point = yzm_base::load_model('point', 'member');
	        $point->point_add(1,get_bbs_config('posts_comment_point'),1,$userid,$username,0,'论坛评论ID:'.$id);
	    }

	    $posts_comment_check ? return_json( array('status'=>1,'message'=>'评论成功，待管理审核后显示！') ) : return_json( array('status'=>1,'message'=>'评论成功！') );
	}
	
	
	
	/**
	 * 删除评论
	 */
	public function del(){
		$userid = get_cookie('_userid') ? intval(get_cookie('_userid')) : 0;
		if(!$userid) return_json(array('status'=>0,'message'=>'请先登录！'));
		$id = isset($_POST['id']) ? intval($_POST['id']) : return_json(array('status'=>0,'message'=>L('lose_parameters')));
		$data = D('forum_comment')->field('userid,forumid')->where(array('id' => $id))->find();
		if($data['userid'] != $userid) return_json( array('status'=>0,'message'=>'非法操作') );
		D('forum_comment')->delete(array('id' => $id));  //删除评论记录
		D('forum_attitude')->delete(array('commentid' => $id));  //删除评论点赞记录
		D('forum_post')->update('`comment` = `comment`-1', array('id' => $data['forumid']));  //更新帖子评论数
		return_json( array('status'=>1,'message'=>'操作成功！') );
	}
	
	
	/**
	 * 更多评论
	 */
	public function more(){
		$id = isset($_GET['id']) ? intval($_GET['id']) : showmsg(L('lose_parameters'), 'stop');

		$data = D('forum_post')->where(array('id'=>$id))->find();
		if(!$data || $data['status'] != 1) showmsg('内容不存在或未通过审核！','stop');
		extract($data);

		//SEO相关
		$site = array_merge(get_config(), get_bbs_config());
		$seo_title = $title.'的评论内容_'.$site['bbs_name'];
		$keywords = $keywords ? $keywords : $title.'的评论内容';
		$description = $site['bbs_description'];

		//获取全部评论
		$forum_comment = D('forum_comment');
		$total = $forum_comment->where(array('forumid'=>$id, 'status'=>1))->total();
		$page = new page($total, 20);
		$comment_list = $forum_comment->field('id,forumid,userid,username,userpic,inputtime,content,reply,praise')->where(array('forumid'=>$id, 'status'=>1))->order('praise DESC,id DESC')->limit($page->limit())->select();
		$pages = $page->getfull(false);
		
		//获取板块
        $forum_plate = D('forum_plate')->field('plate_id,plate_name,groupids_view,groupids_add')->order('listorder ASC,plate_id ASC')->select();

		//最近更新
		$list_data = D('forum_post')->field('id,plate_id,title,inputtime,updatetime')->where(array('status'=>1))->order('id DESC')->limit(20)->select();

		$userid = get_cookie('_userid') ? intval(get_cookie('_userid')) : 0;
		$username = get_cookie('_username') ? safe_replace(get_cookie('_username')) : '';

		//设置模板风格
        set_module_theme('default');

		include template('bbs', 'comment_more');
	}
	
	
}