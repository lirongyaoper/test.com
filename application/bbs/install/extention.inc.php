<?php
defined('IN_YZMPHP') or exit('Access Denied');
defined('INSTALL') or exit('Access Denied');

$parentid = $menu->insert(array('name'=>'论坛管理', 'parentid'=>0, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'init', 'data'=>'yzm-iconluntan', 'listorder'=>2, 'display'=>'1'));
$menu->insert(array('name'=>'论坛配置', 'parentid'=>$parentid, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'config', 'data'=>'', 'listorder'=>201, 'display'=>'1'));
$p = $menu->insert(array('name'=>'板块管理', 'parentid'=>$parentid, 'm'=>'bbs', 'c'=>'plate', 'a'=>'init', 'data'=>'', 'listorder'=>202, 'display'=>'1'));
$menu->insert(array('name'=>'添加板块', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'plate', 'a'=>'add', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'编辑板块', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'plate', 'a'=>'edit', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'删除板块', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'plate', 'a'=>'del', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$p = $menu->insert(array('name'=>'帖子管理', 'parentid'=>$parentid, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'init', 'data'=>'', 'listorder'=>203, 'display'=>'1'));
$menu->insert(array('name'=>'帖子管理', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'init', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'帖子搜索', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'search', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'帖子编辑', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'edit', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'帖子删除', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'del', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'帖子审核', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'posts_adopt', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'移动板块', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'remove', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'新增/删除属性', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'attribute_operation', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$p = $menu->insert(array('name'=>'帖子评论', 'parentid'=>$parentid, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'comment_list', 'data'=>'', 'listorder'=>204, 'display'=>'1'));
$menu->insert(array('name'=>'帖子评论', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'comment_list', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'帖子评论搜索', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'comment_search', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'删除帖子评论', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'comment_del', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'帖子评论通过审核', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'comment_adopt', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
$menu->insert(array('name'=>'快速锁定用户', 'parentid'=>$p, 'm'=>'bbs', 'c'=>'bbs', 'a'=>'lock', 'data'=>'', 'listorder'=>0, 'display'=>'0'));
