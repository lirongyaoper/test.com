DROP TABLE IF EXISTS `yzmcms_forum_config`;
CREATE TABLE `yzmcms_forum_config` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '' COMMENT '配置名称',
  `title` varchar(60) NOT NULL DEFAULT '' COMMENT '配置说明',
  `value` text NOT NULL COMMENT '配置值',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='论坛配置表';

INSERT INTO `yzmcms_forum_config` VALUES ('1', 'bbs_name', '论坛名称', 'YzmCMS论坛', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('2', 'bbs_keyword', '论坛关键字', '', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('3', 'bbs_description', '论坛描述', '', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('4', 'posts_open', '帖子开关', '1', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('5', 'posts_day_limit', '每个用户每日发帖数量', '10', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('6', 'posts_point', '发帖奖励积分', '3', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('7', 'posts_check', '发帖是否审核', '0', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('8', 'posts_comment_check', '帖子评论是否审核', '0', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('9', 'posts_comment_limit', '帖子评论间隔时间限制', '10', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('10', 'posts_comment_point', '帖子评论奖励积分', '1', '1');
INSERT INTO `yzmcms_forum_config` VALUES ('11', 'posts_comment_point_limit', '帖子评论每日奖励次数', '5', '1');

DROP TABLE IF EXISTS `yzmcms_forum_comment`;
CREATE TABLE `yzmcms_forum_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forumid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '帖子id',
  `userid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `username` varchar(30) NOT NULL DEFAULT '',
  `userpic` varchar(100) NOT NULL DEFAULT '',
  `inputtime` int(10) unsigned NOT NULL DEFAULT '0',
  `ip` char(15) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '评论状态{0:未审核,1:通过审核}',
  `reply` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为回复',
  `praise` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '点赞数',
  PRIMARY KEY (`id`),
  KEY `forumid` (`forumid`,`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='论坛帖子评论';

DROP TABLE IF EXISTS `yzmcms_forum_attitude`;
CREATE TABLE `yzmcms_forum_attitude` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forumid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '帖子id',
  `commentid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '评论id',
  `userid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1顶0踩',
  `inputtime` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `commentid` (`commentid`,`userid`),
  KEY `forumid` (`forumid`,`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='论坛顶踩表';

DROP TABLE IF EXISTS `yzmcms_forum_plate`;
CREATE TABLE `yzmcms_forum_plate` (
  `plate_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `plate_name` varchar(60) NOT NULL DEFAULT '' COMMENT '板块名称',
  `listorder` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `groupids_view` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '阅读权限',
  `groupids_add` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '发布权限',
  `keywords` varchar(120) NOT NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '描述',
  PRIMARY KEY (`plate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='论坛板块表';

DROP TABLE IF EXISTS `yzmcms_forum_post`;
CREATE TABLE `yzmcms_forum_post` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `plate_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '板块ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `userid` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `username` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `inputtime` int(10) unsigned NOT NULL DEFAULT '0',
  `updatetime` int(10) unsigned DEFAULT '0',
  `keywords` varchar(100) NOT NULL DEFAULT '',
  `ip` varchar(15) NOT NULL DEFAULT '',
  `click` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1正常0隐藏',
  `attachment` varchar(150) NOT NULL DEFAULT '' COMMENT '附件',
  `point` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '下载收费',
  `paytype` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1积分,2金钱',
  `tags` varchar(15) NOT NULL DEFAULT '' COMMENT '1置顶2精帖3推荐',
  `is_comment` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否允许评论',
  `comment` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '评论次数',
  `praise` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '赞次数',
  `tread` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '踩次数',
  `groupids_view` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '阅读权限',
  `content` text COMMENT '内容',
  PRIMARY KEY (`id`),
  KEY `plate_id` (`plate_id`),
  KEY `userid` (`userid`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='论坛帖子表';

ALTER TABLE `yzmcms_member_detail`
ADD COLUMN `posts`  mediumint(8) UNSIGNED NOT NULL DEFAULT 0 COMMENT '帖子数' AFTER `fans`;

DROP TABLE IF EXISTS `yzmcms_member_sign`;
CREATE TABLE `yzmcms_member_sign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `inputtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '签到时间',
  `continuity_day` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '连续签到天数',
  `point` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '赠送积分',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='会员签到表';