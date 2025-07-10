/**
 * YZMCMS轻论坛
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 */

function yzm_follow(userid, url) {
	$.ajax({
		type: 'POST',
		url: url, 
		data: 'userid='+userid,
		success: function (msg) {
			if(msg == 1){
				$("#follow").html('已关注');
				layer.msg("关注成功！\n以后对方发布新帖时，会在您的会员中心显示哦！", {icon:1,time: 2500});
			}else if(msg == 2){
				$("#follow").html('加关注');
				layer.msg("已取消关注", {icon:2,time: 1500});
			}else if(msg == 0){
				layer.msg('请先登录！', {icon:2,time: 1500});
			}else if(msg == -3){
				layer.msg('不能关注自己哦~', {icon:2,time: 1500});
			}else if(msg == -1){
				layer.msg('该用户不存在！', {icon:2,time: 1500});
			}else{
				layer.msg('非法操作！', {icon:2,time: 1500});
			}
		}
	});
}


function yzm_sign(url) {
	$.ajax({
		type: 'POST',
		url: url, 
		data: 'dosubmit=1',
		dataType: "json", 
		success: function (msg) {
			if(msg.status == 1){
				layer.msg(msg.message, {icon:1,time: 2500});
				$("#yzm_sign").html('今日已签到');
				$(".yzm_sign").addClass('yzm_disabled');
				$("#continuity_day").html(msg.data.continuity_day);
			}else{
				layer.msg(msg.message, {icon:2,time: 2500});
			}
		}
	});
}


function yzm_open(title,url,w,h){
	if (w == undefined || w == null) {
		w = ($(window).width() * 0.8);
	}
	if (h == undefined || h == null) {
		h = ($(window).height() * 0.8);
	}
	layer.open({
		type: 2,
		area: [w+'px', h +'px'],
		fix: false, 
		// maxmin: true,
		shade:0.4,
		title: title,
		content: url
	});
} 


function yzm_reply(username){
	var str;
	var content;
	content = $("#content").val();
	str = '@'+username+' ';
	content = content.replace(str, '');
	str = str+content;
	$("#content").val(str).focus();
}

function yzm_favorite(url, title, dourl) {
	$.ajax({
		type: 'POST',
		url: dourl, 
		data: 'title='+title+'&url='+location.href,
		dataType: "json", 
		success: function (msg) {
			if(msg.status > 0){
				$("#favorite").html(msg.status==1 ? '已收藏' : '收藏');
				layer.msg(msg.message, {icon:1,time: 1500});
			}else{
				yzm_open('用户登录', site_url+'index.php?m=bbs&c=index&a=ajax_login', 490, 360); 
			}
		}
	});
}

function check_favorite() {
    $.ajax({
        type: 'POST',
        url: site_url+'api/index/check_favorite',
        data: 'url=' + location.href,
        dataType: "json",
        success: function(msg) {
            if (msg.status == 1) {
                $("#favorite").html(msg.message);
            }
        }
    });
}
    
function yzm_comment_del(url, id){
	layer.confirm('确认要删除吗？',function(index){
		$.ajax({
			type: 'POST',
			url: url, 
			data: 'id='+id,
			dataType: "json", 
			success: function (msg) {
				if(msg.status == 1){
					layer.msg(msg.message, {icon:1,time: 2000}, function(){
						window.location.reload();
					});
				}else{
					layer.msg(msg.message, {icon:2,time: 2500});
				}
			}
		})
	});
}

function yzm_submit(obj, url){
	if($("#content").val() == ''){
		layer.msg('评论内容不能为空！', {icon:2,time: 1500});
		$("#content").focus();
		return false;
	}

	var myindex = layer.msg('正在请求服务器……', {icon:16, time:0, shade:0.3});
	$.ajax({
		type: 'POST',
		url: url, 
		data: $(obj).serialize(),
		dataType: "json", 
		success: function (msg) {
			if(msg.status == 1){
				layer.msg(msg.message, {icon:1,time: 2000}, function(){
					window.location.reload();
				});
			}else if(msg.status == 2){
				layer.close(myindex);
				yzm_open('用户登录', site_url+'index.php?m=bbs&c=index&a=ajax_login', 490, 360); 
			}else{
				layer.msg(msg.message, {icon:2,time: 2500});
			}
		}
	})

	return false;
}


function yzm_praise(obj, url) {
	$.ajax({
		type: 'GET',
		url: url, 
		dataType: "json", 
		success: function (msg) {
			if(msg.status == 1){
				$(obj).parent().addClass('yzm_praise');
				var temp = $(obj).find("em").html();
				temp++;
				$(obj).find("em").html(temp);
			}else{
				layer.msg(msg.message, {icon:2,time: 2500});
			}
		}
	})
	return false;
}

function yzm_bbs_content(obj, url){
	if($("#plate_id").val() == 0){
		layer.msg('请选择板块！', {icon:2,time: 1500});
		return false;
	}
	if($("#title").val() == 0){
		layer.msg('标题不能为空！', {icon:2,time: 1500});
		$("#title").focus();
		return false;
	}
	if(UE.getEditor('content').getContent()==''){
		layer.msg('内容不能为空！', {icon:2,time: 1500});
		return false;
	}
	if($("#code").val() == 0){
		layer.msg('验证码不能为空！', {icon:2,time: 1500});
		$("#code").focus();
		return false;
	}
	var myindex = layer.msg('正在请求服务器……', {icon:16, time:0, shade:0.3});
	$.ajax({
		type: 'POST',
		url: url, 
		data: $(obj).serialize(),
		dataType: "json", 
		success: function (msg) {
			if(msg.status == 1){
				layer.close(myindex);
				layer.msg(msg.message, {icon:1,time: 2000}, function(){
					window.location.href = site_url + 'bbs';
				});
			}else if(msg.status == -1){
				$('#code+img').attr('src',$('#code+img').attr('src') + '?' + Math.random());
				layer.msg(msg.message, {icon:2,time: 1500});
			}else{
				layer.msg(msg.message, {icon:2,time: 2500});
			}
		}
	})
	return false;
}

function yzm_get_keywords(){
    var title = $("#title").val();
	if(title == ''){
		layer.msg('标题不能为空！', {icon:2,time: 2000});
		return false;
	}
    var keywords = $("#keywords").val();
    if(keywords == ''){
		$.ajax({
            type: 'POST',
            url: 'https://www.yzmcms.com/api/analysis', 
            data: {content:title},
		    dataType: "json", 
            success: function (msg) {
				if(msg.status && msg.data){
					$("#keywords").val(msg.data);
				}
            }
        })
    }
}