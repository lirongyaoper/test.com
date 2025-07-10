<?php
defined('IN_YZMPHP') or exit('Access Denied');
defined('UNINSTALL') or exit('Access Denied');
$menu->delete(array('m'=>'bbs'));
$menu->query('ALTER TABLE `yzmcms_member_detail` DROP COLUMN `posts`');