<?php
/**
 * YzmCMS 论坛模块 - 会员中心
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2018-07-25
 */

defined('IN_YZMPHP') or exit('Access Denied'); 
yzm_base::load_controller('common', 'member', 0);
yzm_base::load_common('function/function.php', 'bbs');
yzm_base::load_sys_class('page','',0);

class member_posts extends common{

	function __construct() {
		parent::__construct();
	}

	
	/**
	 * 会员中心
	 */	
	public function init(){ 
		$memberinfo = $this->memberinfo;
		extract($memberinfo);
		$groupinfo = get_groupinfo($groupid);
		include template('member', 'index');
	}


	/**
	 * 我的帖子
	 */	
	public function post_list(){
		$memberinfo = $this->memberinfo;
		extract($memberinfo);
		
		$forum_post = D('forum_post');
		$title = isset($_GET['title']) ? trim($_GET['title']) : '';
		$where = array('userid'=>$userid);
		if(isset($_GET['dosubmit'])){
			if($title){
				$where['title'] = '%'.$title.'%';
			}
		}
		$total = $forum_post->where($where)->total();
		$page = new page($total, 10);
		$data = $forum_post->field('id,plate_id,title,inputtime,updatetime,tags,comment,status')->where($where)->order('updatetime DESC')->limit($page->limit())->select();
		$pages = '<span class="pageinfo">共'.$total.'条记录</span>'.$page->getfull(false);
		include template('member', 'post_list');
	}


	/**
	 * 我的评论
	 */
	public function post_comment_list(){
		$memberinfo = $this->memberinfo;
		extract($memberinfo);
		
		$forum_comment = D('forum_comment');
		$title = isset($_GET['title']) ? trim($_GET['title']) : '';
		$content = isset($_GET['content']) ? trim($_GET['content']) : '';
		$where = array('a.userid'=>$userid);
		if(isset($_GET['dosubmit'])){
			if($title){
				$where['b.title'] = '%'.$title.'%';
			}
			if($content){
				$where['a.content'] = '%'.$content.'%';
			}
		}
		$total = $forum_comment->alias('a')->where($where)->join('yzmcms_forum_post b ON a.forumid=b.id')->total();
		$page = new page($total, 10);
		$data = $forum_comment->alias('a')->field('a.forumid,a.inputtime,a.ip,a.content,a.status,b.title,b.plate_id,b.comment')->join('yzmcms_forum_post b ON a.forumid=b.id')->where($where)->order('forumid DESC')->limit($page->limit())->select();
		$pages = '<span class="pageinfo">共'.$total.'条记录</span>'.$page->getfull(false);
		include template('member', 'post_comment_list');
	}


	/**
	 * TA的帖子
	 */	
	public function follow_posts(){
		$memberinfo = $this->memberinfo;
		extract($memberinfo);
		
		$forum_post = D('forum_post');
		$total = $forum_post->alias('f')->join('yzmcms_member_follow m ON m.followid = f.userid', 'RIGHT')->where("m.userid=$userid AND status=1")->total();
		$page = new page($total, 10);
		$res = $forum_post->alias('f')->field('f.id,f.plate_id,f.username,f.title,f.inputtime')->join('yzmcms_member_follow m ON m.followid = f.userid', 'RIGHT')->where("m.userid=$userid AND status=1")->order('inputtime DESC')->limit($page->limit())->select();	
		$data = array();
		foreach($res as $val) {
			$val['url'] = SITE_URL.'index.php?m=bbs&c=index&a=show&id='.$val['id'];
			$val['event'] = $val['username'].' 发帖《<a href="'.$val['url'].'" target="_blank">'.$val['title'].'</a>》';
			$data[] = $val;
		}
		$pages = '<span class="pageinfo">共'.$total.'条记录</span>'.$page->getfull(false);
		include template('member', 'follow_posts');
	}
}