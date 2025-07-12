<?php defined('IN_YZMPHP') or exit('No permission resources.'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php echo $seo_title;?></title>
	<meta name="keywords" content="<?php echo $keywords;?>" />
	<meta name="description" content="<?php echo $description;?>" />
	<link rel="stylesheet" href="<?php echo STATIC_URL;?>bbs/css/yzm_bbs_common.css?v=yzmcms20200221">
	<link rel="stylesheet" href="<?php echo STATIC_URL;?>bbs/css/yzm_bbs_index.css?v=yzmcms20200221">
	<link rel="stylesheet" href="<?php echo STATIC_URL;?>bbs/iconfont/iconfont.css">
</head>
<body>
	<div class="yzm_header">
		<div class="container">
		    <a class="yzm_logo" href="<?php echo SITE_URL;?>">
		      <img src="<?php echo STATIC_URL;?>bbs/images/bbs_logo.png" alt="YZMCMS轻论坛" title="YZMCMS轻论坛">
		    </a>
		    <ul class="yzm_nav">

		    	<li><a href="<?php echo SITE_URL;?>" target="_blank">站点首页</a></li>
				<?php $tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'nav')) {$nav_data = $tag->nav(array('field'=>'catid,catname,arrchildid,pclink,target','where'=>"parentid=0",'limit'=>'6','return'=>'nav_data',));}?>
				<?php if(is_array($nav_data)) foreach($nav_data as $v) { ?>
				      <li>
				      <a<?php if(isset($catid) && $v['catid']==$catid) { ?> class="current" <?php } ?> href="<?php echo $v['pclink'];?>" target="<?php echo $v['target'];?>" ><?php echo $v['catname'];?></a>
				    </li>  
				<?php } ?> 

				<!-- <li><a href="https://www.yzmcms.com/" target="_blank">官网首页</a></li> -->
			</ul>
		    
		    <div class="yzm_nav_right">
		    <?php if($userid==0) { ?>
		      <span class="yzm_user_avatar">
		        <a href="<?php echo U('member/index/login');?>"><img src="<?php echo STATIC_URL;?>bbs/images/avatar.png"/></a>
		      </span>
		      <span>
		        <a href="<?php echo url_referer(1);?>" target="_blank">登录</a>
		      </span>
		      <span>
		        <a href="<?php echo U('member/index/register');?>" target="_blank">注册</a>
		      </span>
		    <?php } else { ?>
		    	<div class="yzm_login_status">
		        <a href="<?php echo U('member/index/init');?>" target="_blank">
		        	<span><?php echo $username;?></span>
		        	<img src="<?php echo get_avatar($userid);?>" title="<?php echo $username;?>" />
		        </a>
		        <ul class="yzm_user_menu">
		        	<li><a href="<?php echo U('member/index/init');?>" target="_blank">用户中心</a></li>
		        	<li><a href="<?php echo U('member/index/account');?>" target="_blank">修改资料</a></li>
		        	<li><a href="<?php echo U('member/myhome/init', array('userid'=>$userid));?>" target="_blank">个人主页</a></li>
		        	<li><a href="<?php echo U('member_posts/post_list');?>" target="_blank">我的帖子</a></li>
		        	<li><a href="<?php echo url_referer(0);?>" target="_blank">退出</a></li>
		        </ul>
		      </div>
		    <?php } ?>
	       </div>		
		</div>
	</div>
	<div class="yzm_header_children">
		<div class="container">
		    <ul class="yzm_nav yzm_nav_children">
		       <li><a href="<?php echo SITE_URL;?>bbs" <?php if(!isset($pid)) { ?> class="yzm_nav_current"<?php } ?>>首页</a></li>
		    	<?php if(is_array($forum_plate)) foreach($forum_plate as $v) { ?>
		       <li><a href="<?php echo U('index/lists', array('pid' => $v['plate_id']));?>" <?php if(isset($pid) && $v['plate_id']==$pid) { ?> class="yzm_nav_current"<?php } ?>><?php echo $v['plate_name'];?></a></li>
		        <?php } ?>
		    </ul>
		    
		    <ul class="yzm_nav_right yzm_search">
		      <form method="get" action="<?php echo SITE_URL;?>index.php" target="_blank">
		      	<input type="hidden" name="m" value="<?php echo ROUTE_M;?>" />
		      	<input type="hidden" name="c" value="<?php echo ROUTE_C;?>" />
		      	<input type="hidden" name="a" value="search" />
		      	<input type="text"  name="q" required placeholder="搜一下又不会怀孕~"/>
		      	<input type="submit" value="搜索" class="yzm_button yzm_font" />
		      </form>
	       </ul>		
		</div>
	</div>