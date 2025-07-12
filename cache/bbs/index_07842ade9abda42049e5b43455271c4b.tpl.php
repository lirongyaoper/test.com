<?php defined('IN_YZMPHP') or exit('No permission resources.'); ?><?php include template("bbs","header"); ?> 
	<div class="yzm_main container">
		<div class="yzm_main_left">
			<div class="yzm_list_section">
				<strong>置顶</strong>
				<ul class="yzm_list">
					<?php if(is_array($top_data)) foreach($top_data as $v) { ?>
					<li>
						<a href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>" target="_blank" class="yzm_avatar" title="<?php echo $v['username'];?>">
							<img src="<?php echo get_avatar($v['userid']);?>" alt="<?php echo $v['username'];?>">
						</a>

			            <h3>
			              <span class="yzm_badge"><?php echo get_platename($v['plate_id']);?></span>
			              <a href="<?php echo U('show', array('id'=>$v['id']));?>" target="_blank"><?php echo $v['title'];?></a>
			            </h3>
			            <div class="yzm_list_info">
			              <a href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>" target="_blank">
			                <cite><?php echo $v['username'];?></cite>
			                <?php if($v['username']=='yzmcms') { ?>
			                <img class="yzm_v" src="<?php echo STATIC_URL;?>bbs/images/v.png" alt="官网认证"  title="官网认证"/>
			                <?php } ?>
			              </a>
			              <span class="yzm_time"><?php echo date('Y-m-d H:i', $v['updatetime']);?></span>
			              <span class="yzm_list_nums"> 
			              	<span class="yzm_browse"  title="浏览次数" ><img src="<?php echo STATIC_URL;?>bbs/images/browse.png" alt="浏览次数" /><?php echo $v['click'];?>次</span>
			              	<span class="yzm_comment" title="评论次数" ><img src="<?php echo STATIC_URL;?>bbs/images/comment.png" alt="评论次数" /><?php echo $v['comment'];?></span>
			              </span>
			            </div>
			            <div class="yzm_list_badge">
			            	<?php if(strpos($v['tags'], '1') !== false) { ?>
	        	            <span class="yzm_badge yzm_stick">置顶</span>
	        	            <?php } ?>
	        	            <?php if(strpos($v['tags'], '2') !== false) { ?>
	        	            <span class="yzm_badge yzm_essence">精帖</span>    
	        	            <?php } ?>  
	        	            <?php if(strpos($v['tags'], '3') !== false) { ?>
	        	            <span class="yzm_badge yzm_recomm">推荐</span>
	        	            <?php } ?>      
				        </div>	
					</li>
					<?php } ?>
				</ul>
			</div>

			<div class="yzm_list_section">
				<strong>最新</strong>
				<ul class="yzm_list">
					<?php if(is_array($list_data)) foreach($list_data as $v) { ?>
					<li>
						<a href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>" target="_blank" class="yzm_avatar" title="<?php echo $v['username'];?>">
							<img src="<?php echo get_avatar($v['userid']);?>" alt="<?php echo $v['username'];?>">
						</a>

			            <h3>
			              <span class="yzm_badge"><?php echo get_platename($v['plate_id']);?></span>
			              <a href="<?php echo U('show', array('id'=>$v['id']));?>" target="_blank"><?php echo $v['title'];?></a>
			            </h3>
			            <div class="yzm_list_info">
			              <a href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>" target="_blank">
			                <cite><?php echo $v['username'];?></cite>
			                <?php if($v['username']=='yzmcms') { ?>
			                <img class="yzm_v" src="<?php echo STATIC_URL;?>bbs/images/v.png" alt="官网认证"  title="官网认证"/>
			                <?php } ?>
			              </a>
			              <span class="yzm_time"><?php echo date('Y-m-d H:i', $v['updatetime']);?></span>
			              <span class="yzm_list_nums"> 
			              	<span class="yzm_browse"  title="浏览次数" ><img src="<?php echo STATIC_URL;?>bbs/images/browse.png" alt="浏览次数" /><?php echo $v['click'];?>次</span>
			              	<span class="yzm_comment" title="评论次数" ><img src="<?php echo STATIC_URL;?>bbs/images/comment.png" alt="评论次数" /><?php echo $v['comment'];?></span>
			              </span>
			            </div>
			            <div class="yzm_list_badge">
			            	<?php if(strpos($v['tags'], '1') !== false) { ?>
	        	            <span class="yzm_badge yzm_stick">置顶</span>
	        	            <?php } ?>
	        	            <?php if(strpos($v['tags'], '2') !== false) { ?>
	        	            <span class="yzm_badge yzm_essence">精帖</span>    
	        	            <?php } ?>  
	        	            <?php if(strpos($v['tags'], '3') !== false) { ?>
	        	            <span class="yzm_badge yzm_recomm">推荐</span>
	        	            <?php } ?>      
				        </div>	
					</li>
					<?php } ?>
				</ul>
			</div>		
		</div>
		<div class="yzm_main_right">
			<div class="yzm_list_section">
				<strong>每日签到</strong>
				<?php $get_sign_info = get_sign_info($userid); $is_sign = $get_sign_info['status']?>
				<div class="yzm_release yzm_sign <?php if($is_sign) { ?>yzm_disabled<?php } ?>">
					<a href="javascript:;" onclick="yzm_sign('<?php echo U('member/member_sign/init');?>')" id="yzm_sign"><?php if($is_sign) { ?>今日已签到<?php } else { ?>每日签到<?php } ?></a>
					<p>已连续签到<span id="continuity_day"><?php if(isset($get_sign_info['data'])) { ?><?php echo $get_sign_info['data'];?><?php } else { ?>0<?php } ?></span>天 <img src="<?php echo STATIC_URL;?>bbs/images/yzm_doubt.png" alt="?" title="规则说明" onclick="yzm_open('签到规则说明', '<?php echo U('member/member_sign/explain');?>', 600, 370)"></p>
				</div>
			</div>	
			<div class="yzm_list_section">
				<strong>发布新帖</strong>
				<div class="yzm_release">
					<a href="<?php echo U('add');?>" target="_blank">发布新帖</a>
				</div>
			</div>
			<div class="yzm_list_section">
				<strong>活跃用户</strong>
				<dl class="yzm_lately">
					<?php if(is_array($member_list)) foreach($member_list as $v) { ?>
					<dd>
			            <a href="<?php echo U('member/myhome/init', array('userid'=>$v['userid']));?>">
			            <img src="<?php if($v['userpic']) { ?><?php echo $v['userpic'];?><?php } else { ?><?php echo STATIC_URL;?>bbs/images/avatar.png<?php } ?>"><cite><?php echo $v['nickname'];?></cite><i>贴子数<?php echo $v['posts'];?></i>
			            </a>
			        </dd>
			        <?php } ?>

				</dl>
			</div>
			<div class="yzm_list_section">
				<strong>热门推荐</strong>
				<ul class="yzm_recommend">
					<?php if(is_array($recommend_list)) foreach($recommend_list as $v) { ?>
					<li><a href="<?php echo U('show', array('id'=>$v['id']));?>" target="_blank"><?php echo $v['title'];?></a></li>
					<?php } ?>				
				</ul>
			</div>
			<div class="yzm_list_section">
				<strong>友情链接</strong>
				<ul class="yzm_link">
					<?php $tag = yzm_base::load_sys_class('yzm_tag');if(method_exists($tag, 'link')) {$data = $tag->link(array('field'=>'url,logo,name','limit'=>'20',));}?>
					<?php if(is_array($data)) foreach($data as $v) { ?>
					   <li><a href="<?php echo $v['url'];?>" target="_blank"><?php echo $v['name'];?></a></li>	
					<?php } ?>	
				</ul>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="<?php echo STATIC_URL;?>js/jquery-1.8.2.min.js"></script>
	<script type="text/javascript" src="<?php echo STATIC_URL;?>plugin/layer/layer.js"></script>	  
	<script type="text/javascript" src="<?php echo STATIC_URL;?>bbs/js/yzm_bbs_js.js"></script>		
<?php include template("bbs","footer"); ?> 