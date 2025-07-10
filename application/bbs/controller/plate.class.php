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

class plate extends common{

	/**
	 * 板块列表
	 */	
	public function init(){ 
		$forum_plate = D('forum_plate');
		$data = $forum_plate->order('listorder ASC, plate_id ASC')->select();			
		include $this->admin_tpl('forum_plate_list');
	}
	

	
	/**
	 * 添加板块
	 */	
	public function add(){ 
 		if(isset($_POST['dosubmit'])) {
			D('forum_plate')->insert($_POST, true);
			delcache('plateinfo');
			return_json(array('status'=>1,'message'=>L('operation_success')));
		}
		$member_group = get_groupinfo();
		include $this->admin_tpl('forum_plate_add');
	}

	

	
	/**
	 * 修改板块
	 */	
	public function edit(){ 
 		if(isset($_POST['dosubmit'])) {
			$plate_id = isset($_POST['plate_id']) ? intval($_POST['plate_id']) : 0;
			if(D('forum_plate')->update($_POST, array('plate_id' => $plate_id), true)){
				delcache('plateinfo');
				return_json(array('status'=>1,'message'=>L('operation_success')));
			}else{
				return_json();
			}
		}
		$plate_id = isset($_GET['plate_id']) ? intval($_GET['plate_id']) : 0;
		$data = D('forum_plate')->where(array('plate_id' => $plate_id))->find();
		$member_group = get_groupinfo();
		include $this->admin_tpl('forum_plate_edit');
	}
	
	
	
	/**
	 * 删除板块
	 */
	public function del() {
		if($_POST && is_array($_POST['id'])){
			if(D('forum_plate')->delete($_POST['id'], true)){
				delcache('plateinfo');
				showmsg(L('operation_success'));
			}else{
				showmsg(L('operation_failure'));
			}
		}
	}

}