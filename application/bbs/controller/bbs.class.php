<?php
/**
 * YzmCMS 论坛模块 - 管理员后台论坛操作类
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2018-07-26
 */
 
defined('IN_YZMPHP') or exit('Access Denied'); 
yzm_base::load_common('function/function.php', 'bbs');
yzm_base::load_controller('common', 'admin', 0);
yzm_base::load_sys_class('page','',0);

class bbs extends common{

	/**
	 * 帖子管理
	 */	
	public function init(){
		$of = input('get.of');
		$or = input('get.or');
		$of = in_array($of, array('id','userid','username','updatetime','plate_id','ip','status')) ? $of : 'updatetime';
		$or = in_array($or, array('ASC','DESC')) ? $or : 'DESC';

		$plate_id = 0;
		$plateinfo = get_plateinfo();
		$forum_post = D('forum_post');
		$total = $forum_post->total();
		$page = new page($total, 15);
		$data = $forum_post->order("$of $or")->limit($page->limit())->select();			
		include $this->admin_tpl('posts_list');
	}


	/**
	 * 帖子搜索
	 */
	public function search() {
		$of = input('get.of');
		$or = input('get.or');
		$of = in_array($of, array('id','userid','username','updatetime','plate_id','ip','status')) ? $of : 'updatetime';
		$or = in_array($or, array('ASC','DESC')) ? $or : 'DESC';

		$plate_id = isset($_GET['plate_id']) ? intval($_GET['plate_id']) : 0;
		$forum_post = D('forum_post');
		$where = '1=1';
		if(isset($_GET['dosubmit'])){	
		
			$searinfo = isset($_GET["searinfo"]) ? safe_replace($_GET["searinfo"]) : '';
			$type = isset($_GET["type"]) ? intval($_GET["type"]) : 1;

			if($plate_id){
				$where .= ' AND plate_id='.$plate_id;
			}

			if(isset($_GET["status"]) && $_GET["status"] != '99'){
				$where .= ' AND status = '.intval($_GET["status"]);
			}	

			if(isset($_GET["start"]) && $_GET["start"] != '' && $_GET["end"]){		
				$where .= ' AND updatetime BETWEEN '.strtotime($_GET["start"]).' AND '.strtotime($_GET["end"]);
			}

			if(isset($_GET["tags"]) && $_GET["tags"] != '0'){
				$where .= ' AND FIND_IN_SET('.intval($_GET["tags"]).',tags)';
			}

			if($searinfo != ''){
				if($type == '1'){
					$where .= ' AND title LIKE \'%'.$searinfo.'%\'';
				}elseif($type == '2'){
					$where .= ' AND content LIKE \'%'.$searinfo.'%\'';
				}elseif($type == '3'){
					$where .= ' AND username LIKE \'%'.$searinfo.'%\'';
				}else{
					$where .= ' AND ip = \''.$searinfo.'\'';
				}
			}
			
		}
		$plateinfo = get_plateinfo();
		$total = $forum_post->where($where)->total();
		$page = new page($total, 15);
		$data = $forum_post->where($where)->order("$of $or")->limit($page->limit())->select();		
		include $this->admin_tpl('posts_list');
	}


	/**
	 * 编辑帖子
	 */
	public function edit() {
		$id = input('id', 0, 'intval');
		if(isset($_POST['dosubmit'])) {
		    if(isset($_POST['updatetime'])) $_POST['updatetime'] = strtotime($_POST['updatetime']);
			foreach($_POST as $_k=>$_v) {
				if($_k != 'content') {
					$_POST[$_k] = !is_array($_POST[$_k]) ? htmlspecialchars($_v) : implode(',', $_v);
				}
			}
			$r = D('forum_post')->update($_POST, array('id' => $id));
			echo '<script type="text/javascript">parent.location.reload();</script>';
			exit;
		}else{
			yzm_base::load_sys_class('form','',0);
			$data = D('forum_post')->where(array('id'=>$id))->find();
			$plateinfo = get_plateinfo();
			$member_group = get_groupinfo();
			include $this->admin_tpl('post_edit');	
		}
	}
	
	
	/**
	 * 删除帖子
	 */
	public function del() {
		if($_POST && is_array($_POST['ids'])){
			foreach($_POST['ids'] as $id){
				$userid = D('forum_post')->field('userid')->where(array('id' => $id))->one();
				D('forum_post')->delete(array('id' => $id));	//删除帖子
				D('forum_comment')->delete(array('forumid' => $id));	 //删除帖子评论
				D('member_detail')->update('`posts` = `posts`-1', array('userid' => $userid)); //更新帖子数量
			}
		}
		showmsg(L('operation_success'),'',1);
	}


	/**
	 * 帖子通过审核
	 */
	public function posts_adopt(){
		if($_POST && is_array($_POST['ids'])){
			$forum_post = D('forum_post');
			foreach($_POST['ids'] as $val){
				$forum_post->update(array('status' => '1'), array('id' => $val));
			}
			showmsg(L('operation_success'),'',1);
		}
	}
	

	
	/**
	 * 移动板块
	 */
	public function remove() {
		if(isset($_POST['dosubmit'])) {
			$ids = safe_replace($_POST['ids']);
			$ids_arr = explode(',', $ids);
			$ids_arr = array_map('intval', $ids_arr);
			$ids = join(',', $ids_arr);
			$plate_id = intval($_POST['plate_id']);
			$affected = D('forum_post')->update(array('plate_id' => $plate_id), 'id IN ('.$ids.')');
			return_json(array('status' => 1, 'message' => '操作成功'));
		}else{
			$plateinfo = get_plateinfo();
			include $this->admin_tpl('posts_remove');	
		}
	}
	


	/**
	 * 增加/删除 内容属性
	 */
	public function attribute_operation(){
		if(isset($_POST['dosubmit'])) {
			$op = isset($_POST['op']) ? intval($_POST['op']) : 1;
			$ids = safe_replace($_POST['ids']);
			$ids_arr = explode(',', $ids);
			$ids_arr = array_map('intval', $ids_arr);
			$tags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : array();
			if(!$tags) return_json(array('status' => 0, 'message' => '请勾选信息！'));

			$db = D('forum_post');
			foreach($ids_arr as $id){
				$data_tags = $db->field('tags')->where(array('id' => $id))->one();
				if($op){
					$new_tags = $data_tags ? array_unique(array_merge(explode(',', $data_tags), $tags)) : $tags;
				}else{
					$new_tags = $data_tags ? array_diff(explode(',', $data_tags), $tags) : array();
				}
				sort($new_tags);
				$new_tags = join(',', $new_tags);
				$db->update(array('tags' => $new_tags), array('id' => $id));
			}
			return_json(array('status' => 1, 'message' => '操作成功'));
			
		}else{
			$op = isset($_GET['op']) ? intval($_GET['op']) : 1;
			include $this->admin_tpl('attribute_operation');	
		}
	}


	/**
	 * 帖子评论
	 */	
	public function comment_list(){
		$of = input('get.of');
		$or = input('get.or');
		$of = in_array($of, array('userid','praise','inputtime','plate_id','ip','status')) ? $of : 'id';
		$or = in_array($or, array('ASC','DESC')) ? $or : 'DESC';

		$plate_id = 0;
		$plateinfo = get_plateinfo();
		$forum_comment = D('forum_comment');
		$total = $forum_comment->total();
		$page = new page($total, 15);
		$data = $forum_comment->alias('c')->field('c.*,p.title,p.`comment`,p.plate_id')->join('yzmcms_forum_post p ON c.forumid=p.id', 'left')->order("$of $or")->limit($page->limit())->select();			
		include $this->admin_tpl('comment_list');
	}



	/**
	 * 帖子评论搜索
	 */	
	public function comment_search(){
		$of = input('get.of');
		$or = input('get.or');
		$of = in_array($of, array('userid','praise','inputtime','plate_id','ip','status')) ? $of : 'id';
		$or = in_array($or, array('ASC','DESC')) ? $or : 'DESC';

		$plate_id = isset($_GET['plate_id']) ? intval($_GET['plate_id']) : 0;
		$plateinfo = get_plateinfo();

		$where = '1=1';
		if(isset($_GET['dosubmit'])){
			$type = isset($_GET["type"]) ? $_GET["type"] : 1;
			$searinfo = isset($_GET["searinfo"]) ? safe_replace($_GET["searinfo"]) : '';
			$status = isset($_GET["status"]) ? intval($_GET["status"]) : 99 ;
			if($plate_id) $where .= ' AND p.plate_id = '.$plate_id;
			if($status != 99) $where .= ' AND c.status = '.$status;
			if(isset($_GET["start"]) && $_GET["start"] != '' && $_GET["end"]){		
				$where .= ' AND c.inputtime BETWEEN '.strtotime($_GET["start"]).' AND '.strtotime($_GET["end"]);
			}
			if($searinfo != ''){
				if($type == '1'){
					$where .= ' AND title LIKE \'%'.$searinfo.'%\'';
				}elseif($type == '2'){
					$where .= ' AND c.username = "'.$searinfo.'"';
				}elseif($type == '3'){
					$where .= ' AND c.ip = "'.$searinfo.'"';
				}else{
					$where .= ' AND c.content LIKE \'%'.$searinfo.'%\'';
				}
			}			
		}

		$forum_comment = D('forum_comment');
		$total = $forum_comment->alias('c')->where($where)->join('yzmcms_forum_post p ON c.forumid=p.id', 'left')->total();
		$page = new page($total, 15);
		$data = $forum_comment->alias('c')->field('c.*,p.title,p.`comment`,p.plate_id')->where($where)->join('yzmcms_forum_post p ON c.forumid=p.id', 'left')->order("$of $or")->limit($page->limit())->select();			
		include $this->admin_tpl('comment_list');
	}


	/**
	 * 删除帖子评论
	 */
	public function comment_del() {
		if($_POST && is_array($_POST['id'])){
			$forum_comment = D('forum_comment');
			foreach($_POST['id'] as $val){
				$comment_data = $forum_comment ->field('id,forumid,status')->where(array('id'=>$val))->find();
				if(!$comment_data) showmsg('该评论不存在，请返回检查！');
				$id = $comment_data['forumid'];
				$forum_comment->delete(array('id'=>$val));
				if($comment_data['status']) $forum_comment->query("UPDATE yzmcms_forum_post SET `comment` = `comment`-1 WHERE id='$id'");
			}
			showmsg(L('operation_success'),'',1);
		}
	}
	
	
	/**
	 * 帖子评论通过审核
	 */
	public function comment_adopt() {
		if($_POST && is_array($_POST['id'])){
			$forum_comment = D('forum_comment');
			foreach($_POST['id'] as $val){
				$forum_comment->update(array('status' => '1'), array('id' => $val));
			}
			showmsg(L('operation_success'),'',1);
		}
	}


	/**
	 * 快速锁定用户
	 */
	public function lock(){
		$userid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;
		D('member')->update(array('status' => '2'), array('userid' => $userid));
		showmsg('锁定用户成功！', '', 1);
	}


	/**
	 * 论坛设置
	 */
	public function config(){
		if(is_post()){
			$forum_config = D('forum_config');
			foreach($_POST as $key => $value){
				$value = safe_replace(trim($value));
				$forum_config->update(array('value'=>$value), array('name'=>$key));
			}
			delcache('bbs_configs');
			showmsg(L('operation_success'), '', 1);
		}
		$data = get_bbs_config();
		include $this->admin_tpl('bbs_config', 'bbs');
	}

}