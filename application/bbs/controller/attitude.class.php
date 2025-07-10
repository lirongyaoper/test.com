<?php
/**
 * YzmCMS 论坛模块 - 论坛帖子、评论点赞
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2019-05-22
 */

class attitude{

	private $userid;


	public function __construct() {
		$this->userid = get_cookie('_userid') ? intval(get_cookie('_userid')) : 0;
		if(!$this->userid) return_json(array('status'=>0, 'message'=>'请先登录！'));
	}
	

	/**
	 * 帖子评论点赞
	 */
	function init(){
		$forumid = input('get.forumid', 0, 'intval');
		$commentid = input('get.commentid', 0, 'intval');
		if(!$forumid || !$commentid) return_json(array('status'=>0, 'message'=>'参数错误！'));

		$id = D('forum_attitude')->field('id')->where(array('commentid' => $commentid,'userid' => $this->userid))->one();
		if($id) return_json(array('status'=>0, 'message'=>'您已经点过赞了！'));

		D('forum_comment')->update('`praise` = `praise`+1', array('id' => $commentid));

		D('forum_attitude')->insert(array(
			'forumid' => $forumid,
			'commentid' => $commentid,
			'userid' => $this->userid,
			'type' => 1,
			'inputtime' => SYS_TIME
		));

		return_json(array('status'=>1, 'message'=>'操作成功！'));
	}
}
