<?php defined('IN_YZMPHP') or exit('No permission resources.'); ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title><?php echo $seo_title;?></title>
    <meta name="author" content="YzmCMS内容管理系统">
    <meta name="keywords" content="<?php echo $keywords;?>" />
    <meta name="description" content="<?php echo $description;?>" />
    <link href="<?php echo STATIC_URL;?>css/yzm-common.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo STATIC_URL;?>css/yzm-style.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="<?php echo STATIC_URL;?>js/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="<?php echo STATIC_URL;?>js/yzm-front.js"></script>
    <script type="text/javascript" src="<?php echo STATIC_URL;?>js/yzm-face.js"></script>
    <script type="text/javascript" src="<?php echo STATIC_URL;?>plugin/layer/layer.js"></script>
</head>

<body>
    <?php include template("index","header"); ?>
    <div class="yzm-main-left yzm-box-left">
        <div class="yzm-content-box">
            <div class="yzm-title">
                <h2><?php echo get_catname($catid);?></h2>
                <span class="yzm-title-right">
                    当前位置：<?php echo get_location($catid);?>
                </span>
            </div>
            <div class="yzm-content-container">
                <h1><?php echo $title;?></h1>
                <div class="yzm-content-info">
                    <span>更新时间：<?php echo date('Y-m-d H:i:s',$updatetime);?></span>
                    <span>编辑：<?php echo $nickname;?> </span>
                    <span>浏览：<?php echo $click;?></span>
                </div>
                <div class="yzm-content-description">
                    <?php echo $description;?>
                </div>
                <div class="yzm-content">
                    <?php if($allow_read) { ?>
                    <?php echo $content;?>
                    <?php } else { ?>
                    <p class="yzm-content-read-tips">阅读此内容需要您支付<span><?php echo $readpoint;?></span><?php if($paytype==1) { ?>点积分<?php } else { ?>元<?php } ?>，<a href="<?php echo $pay_url;?>">点击支付</a></p>
                    <?php } ?>
                </div>
                <div class="yzm-content-tag">
                    <?php $tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'centent_tag')) {$data = $tag->centent_tag(array('modelid'=>$modelid,'id'=>$id,'limit'=>'10',));}?>
                    <?php if(is_array($data)) foreach($data as $v) { ?>
                    <a href="<?php echo tag_url($v['id']);?>" target="_blank"><?php echo $v['tag'];?></a>
                    <?php } ?>
                </div>
                <div class="yzm-operate">
                    <a href="javascript:;" onclick="add_favorite('<?php echo $title;?>');" id="favorite">收藏</a>
                    <a href="javascript:;" onClick="window.print();" class="print">打印</a>
                </div>
                <div class="yzm-pre-next">
                    <p>上一篇：<?php echo $pre;?></p>
                    <p>下一篇：<?php echo $next;?></p>
                </div>
            </div>
        </div>
        <div class="yzm-line"></div>
        <div class="yzm-content-box yzm-text-list">
            <div class="yzm-title">
                <h2>相关内容</h2>
            </div>
            <ul>
                <?php $tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'relation')) {$data = $tag->relation(array('field'=>'title,url,color,updatetime','modelid'=>$modelid,'id'=>$id,'limit'=>'5',));}?>
                <?php if(is_array($data)) foreach($data as $v) { ?>
                <li>
                    <span class="yzm-date"><?php echo date('Y-m-d', $v['updatetime']);?></span>
                    <a href="<?php echo $v['url'];?>" title="<?php echo $v['title'];?>" target="_blank"><?php echo title_color($v['title'], $v['color']);?></a>
                </li>
                <?php } ?>
            </ul>
        </div>
        <div class="yzm-line"></div>
        <!-- 评论区开始 -->
        <div class="yzm-content-box">
            <div class="yzm-title">
                <h2>文章评论</h2>
            </div>
            <div class="yzm-comment-box">
                <div class="yzm-comment-form">
                    <form action="<?php echo U('comment/index/init');?>" method="post" onsubmit="return check_comm(this)">
                        <input type="hidden" name="modelid" value="<?php echo $modelid;?>">
                        <input type="hidden" name="catid" value="<?php echo $catid;?>">
                        <input type="hidden" name="id" value="<?php echo $id;?>">
                        <input type="hidden" name="title" value="<?php echo $title;?>">
                        <input type="hidden" name="url" value="<?php echo $url;?>">
                        <textarea class="textarea" id="content" name="content" placeholder="我来说两句~"></textarea>
                        <p>
                            <input type="submit" class="yzm-comment-submit" name="dosubmit" value="提交"><span class="emotion">表情</span>
                            <?php if($site['comment_code']) { ?>
                            <span class="yzm-comment-code"><img src="<?php echo U('api/index/code');?>" onclick="this.src=this.src+'?'" title="看不清？点击更换"><input type="text" name="code" placeholder="请输入验证码" required="required"></span>
                            <?php } ?>
                        </p>
                    </form>
                </div>
            </div>
            <div class="yzm-comment-list-box">
                <?php $tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'comment_list')) {$data = $tag->comment_list(array('modelid'=>$modelid,'catid'=>$catid,'id'=>$id,'limit'=>'20',));}?>
                <div class="yzm-comment-list-top">共 <?php echo get_comment_total($id, $catid, $modelid);?> 条评论，<a href="<?php echo U('comment/index/more', array('sign'=>$modelid.'_'.$id));?>" target="_blank">查看全部</a></div>
                <div class="yzm-comment-list-body">
                    <ul>
                        <?php if(is_array($data)) foreach($data as $v) { ?>
                        <li>
                            <?php if($v['userid']) { ?>
                            <a class="user_pic" href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>" target="blank"><img src="<?php if(!empty($v['userpic'])) { ?><?php echo $v['userpic'];?><?php } else { ?><?php echo STATIC_URL;?>images/default.gif<?php } ?>" height="35" width="35"></a>
                            <?php } else { ?>
                            <a class="user_pic" href="javascript:;"><img src="<?php echo STATIC_URL;?>images/default.gif" height="35" width="35"></a>
                            <?php } ?>
                            <div class="yzm-comm-right">
                                <?php if($v['userid']) { ?>
                                <a class="user_name" href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>" target="blank"><?php echo $v['username'];?></a>
                                <?php } else { ?>
                                <a class="user_name" href="javascript:;">游客</a>
                                <?php } ?>
                                <p><?php echo nl2br($v['content']);?></p>
                                <p><span class="comm_time"><?php echo date('Y-m-d H:i:s',$v['inputtime']);?></span> <a href="javascript:toreply('<?php echo $v['id'];?>');" class="comm_a">回复</a></p>
                                <div id="rep_<?php echo $v['id'];?>" class="none">
                                    <form action="<?php echo U('comment/index/init');?>" method="post" onsubmit="return check_rep(this)">
                                        <input type="hidden" name="modelid" value="<?php echo $modelid;?>">
                                        <input type="hidden" name="catid" value="<?php echo $catid;?>">
                                        <input type="hidden" name="id" value="<?php echo $id;?>">
                                        <input type="hidden" name="title" value="<?php echo $title;?>">
                                        <input type="hidden" name="url" value="<?php echo $url;?>">
                                        <input type="hidden" name="reply" value="<?php echo $v['id'];?>">
                                        <input type="hidden" name="reply_to" value="<?php echo $v['username'];?>">
                                        <textarea name="content" class="textarea textarea2" placeholder="我来说两句~"></textarea>
                                        <?php if($site['comment_code']) { ?>
                                        <span class="yzm-comment-reply-code"><img src="<?php echo U('api/index/code');?>" onclick="this.src=this.src+'?'" title="看不清？点击更换"><input type="text" name="code" placeholder="请输入验证码" required="required"></span>
                                        <?php } ?>
                                        <input type="submit" class="yzm-comment-submit static" name="dosubmit" value="提交">
                                    </form>
                                </div>
                            </div>
                        </li>
                        <?php } ?>
                        <?php if(empty($data)) { ?><li>这篇文章还没有收到评论，赶紧来抢沙发吧~</li><?php } ?>
                    </ul>
                </div>
            </div>
        </div>
        <!-- 评论区结束 -->
    </div>
    <div class="yzm-main-right">
        <div class="yzm-content-box">
            <div class="yzm-title">
                <h2>点击排行</h2>
            </div>
            <ul class="yzm-ranking">
                <?php $tag_cache_name = 'tag_cache_'.md5(implode('&',array('field'=>'title,url,color,inputtime','catid'=>$catid,'limit'=>'10','cache'=>'3600',)));if(!$data = getcache($tag_cache_name)){$tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'hits')) {$data = $tag->hits(array('field'=>'title,url,color,inputtime','catid'=>$catid,'limit'=>'10','cache'=>'3600',));}if(!empty($data)){setcache($tag_cache_name, $data, 3600);}}?>
                <?php if(is_array($data)) foreach($data as $k=>$v) { ?>
                <?php $k=$k+1;?>
                <li><em><?php echo $k;?></em><span class="date"><?php echo date('m-d',$v['inputtime']);?></span><a href="<?php echo $v['url'];?>" title="<?php echo $v['title'];?>" target="_blank"><?php echo title_color($v['title'], $v['color']);?></a></li>
                <?php } ?>
            </ul>
        </div>
        <div class="yzm-line"></div>
        <div class="yzm-content-box">
            <div class="yzm-title">
                <h2>文章归档</h2>
            </div>
            <ul class="yzm-like-list">
                <?php $tag_cache_name = 'tag_cache_'.md5(implode('&',array('modelid'=>$modelid,'type'=>'2','limit'=>'10','cache'=>'3600',)));if(!$data = getcache($tag_cache_name)){$tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'content_archives')) {$data = $tag->content_archives(array('modelid'=>$modelid,'type'=>'2','limit'=>'10','cache'=>'3600',));}if(!empty($data)){setcache($tag_cache_name, $data, 3600);}}?>
                <?php if(is_array($data)) foreach($data as $v) { ?>
                <li><a href="<?php echo U('search/index/archives',array('pubtime'=>$v['inputtime']));?>" target="_blank"><?php echo $v['pubtime'];?>(<?php echo $v['total'];?>)</a></li>
                <?php } ?>
            </ul>
        </div>
        <div class="yzm-line"></div>
        <div class="yzm-list-advertise">
            <a href="http://www.yzmcms.com" target="_blank">
                <p>PC+WAP+微信三站合一</p>
                <p class="yzm-ad">YzmCMS开源CMS</p>
            </a>
        </div>
        <div class="yzm-line"></div>
        <div class="yzm-content-box">
            <div class="yzm-title">
                <h2>评论排行榜</h2>
            </div>
            <ul class="yzm-comment-list">
                <?php $tag_cache_name = 'tag_cache_'.md5(implode('&',array('modelid'=>'1','limit'=>'10','cache'=>'3600',)));if(!$data = getcache($tag_cache_name)){$tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'comment_ranking')) {$data = $tag->comment_ranking(array('modelid'=>'1','limit'=>'10','cache'=>'3600',));}if(!empty($data)){setcache($tag_cache_name, $data, 3600);}}?>
                <?php if(is_array($data)) foreach($data as $v) { ?>
                <li><a href="<?php echo $v['url'];?>" title="<?php echo $v['title'];?>"><?php echo $v['title'];?></a></li>
                <?php } ?>
            </ul>
        </div>
        <div class="yzm-line"></div>
        <div class="yzm-content-box">
            <div class="yzm-title">
                <h2>热门标签</h2>
            </div>
            <ul class="yzm-tag-list">
                <?php $tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'tag')) {$data = $tag->tag(array('field'=>'id,tag,total','limit'=>'30',));}?>
                <?php if(is_array($data)) foreach($data as $v) { ?>
                <li><a href="<?php echo tag_url($v['id']);?>" target="_blank"><?php echo $v['tag'];?>(<?php echo $v['total'];?>)</a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <script type="text/javascript">
    function check_favorite() {
        $.ajax({
            type: 'POST',
            url: '<?php echo U("api/index/check_favorite");?>',
            data: 'url=' + location.href,
            dataType: "json",
            success: function(msg) {
                if (msg.status == 1) {
                    $("#favorite").html(msg.message);
                }
            }
        });
    }

    function add_favorite(title) {
        $.ajax({
            type: 'POST',
            url: '<?php echo U("api/index/favorite");?>',
            data: 'title=' + title + '&url=' + location.href,
            dataType: "json",
            success: function(msg) {
                if (msg.status == 1) {
                    $("#favorite").html('已收藏');
                } else if (msg.status == 2) {
                    $("#favorite").html('收藏');
                } else {
                    alert('请先登录！');
                }
            }
        });
    }

    $(function() {
        check_favorite();
        $('.emotion').yzmface({
            path: '<?php echo STATIC_URL;?>images/face/'
        });
    });
    </script>
    <?php include template("index","footer"); ?>