<?php
/**
 * function.php YzmCMS 论坛模块公用函数库
 *
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2018-07-25
 */

/**
 * 获取论坛配置信息
 * @param $key 键值，可为空，为空获取整个数组
 * @return array|string
 */
function get_bbs_config($key = ''){
	if(!$bbs_configs = getcache('bbs_configs')){
		$data = D('forum_config')->where(array('status'=>1))->select();
		$bbs_configs = array();
		foreach($data as $val){
			$bbs_configs[$val['name']] = $val['value'];
		}
		setcache('bbs_configs', $bbs_configs);
	}
    if(!$key){
		return $bbs_configs;
	}else{
		return array_key_exists($key, $bbs_configs) ? $bbs_configs[$key] : '';
	}	
}


/**
 * 获取用户头像
 * @param $userid userid
 * @return string
 */
function get_avatar($userid) {
	$pic = get_memberavatar($userid, 1, false);	
	return $pic ? $pic : STATIC_URL.'bbs/images/avatar.png';
}


/**
 * 用户是否关注回显
 * @param   $followid 被关注用户id
 */
function check_follow($followid){
	$userid = intval(get_cookie('_userid'));
	if(!$userid) return false;
	$id = D('member_follow')->field('id')->where(array('userid'=>$userid, 'followid'=>$followid))->one();
	return $id ? true : false;
}


/**
 * 内容收藏回显
 */
function check_collect($url){
	$userid = intval(get_cookie('_userid'));
	if(!$userid) return false;
	$id = D('favorite')->field('id')->where(array('userid'=>$userid, 'url'=>$url))->one();
	return $id ? true : false;
}


/**
 * 获取板块信息
 *
 * @param  int $plate_id
 * @param  string $parameter
 * @return array or string
 */
function get_plateinfo($plate_id = '', $parameter = ''){
    if(!$plateinfo = getcache('plateinfo')){
		$data = D('forum_plate')->order('listorder ASC, plate_id ASC')->select();
		$plateinfo = array();
		foreach($data as $val){
			$plateinfo[$val['plate_id']] = $val;
		}
		setcache('plateinfo', $plateinfo);
	}
	if($plate_id){
		if(empty($parameter))
			return array_key_exists($plate_id,$plateinfo) ? $plateinfo[$plate_id] : array();
		else
			return array_key_exists($plate_id,$plateinfo) ? $plateinfo[$plate_id][$parameter] : '';
	}else{
		return $plateinfo;	
	}
    
}


/**
 * 根据板块ID获取板块名称
 *
 * @param  int $plate_id
 * @return string
 */
function get_platename($plate_id){
	$plate_id = intval($plate_id);
    $data = get_plateinfo($plate_id);
	if(!$data) return '';
    return $data['plate_name']; 	
}


/**
 * 搜索结果高亮显示
 * @param  string $title 
 * @param  string|array $q     
 * @return string
 */
function search_title($title, $q){
	if(is_string($q)) $q = array($q);

	$replace = array();
	foreach($q as $val){
		$replace[] = '<span style="color:red;">'.$val.'</span>';
	}
	return str_ireplace($q, $replace, $title);
}


/**
 * xss过滤函数
 *
 * @param $string
 * @return string
 */
function new_remove_xss($string) { 
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

    $parm1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'base');

    $parm2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload', 'onpointerout', 'onfullscreenchange', 'onfullscreenerror', 'onhashchange', 'onanimationend', 'onanimationiteration', 'onanimationstart', 'onmessage', 'onloadstart', 'ondurationchange', 'onloadedmetadata', 'onloadeddata', 'onprogress', 'oncanplay', 'oncanplaythrough', 'onended', 'oninput', 'oninvalid', 'onoffline', 'ononline', 'onopen', 'onpagehide', 'onpageshow', 'onpause', 'onplay', 'onplaying', 'onpopstate', 'onratechange', 'onsearch', 'onseeked', 'onseeking', 'onshow', 'onstalled', 'onstorage', 'onsuspend', 'ontimeupdate', 'ontoggle', 'ontouchcancel', 'ontouchend', 'ontouchmove', 'ontouchstart', 'ontransitionend', 'onvolumechange', 'onwaiting', 'onwheel', 'onbegin');

    $parm = array_merge($parm1, $parm2); 

	for ($i = 0; $i < sizeof($parm); $i++) { 
		$pattern = '/'; 
		for ($j = 0; $j < strlen($parm[$i]); $j++) { 
			if ($j > 0) { 
				$pattern .= '('; 
				$pattern .= '(&#[x|X]0([9][a][b]);?)?'; 
				$pattern .= '|(&#0([9][10][13]);?)?'; 
				$pattern .= ')?'; 
			}
			$pattern .= $parm[$i][$j]; 
		}
		$pattern .= '/i';
		$string = preg_replace($pattern, 'xxx', $string); 
	}
	return $string;
}	


/**
 * 根据用户id获取签到信息
 *
 * @param $userid
 * @return array
 */
function get_sign_info($userid){
    if(!$userid) return array('status'=>0,'message'=>'未签到','data'=>0);
    
	$data = D('member_sign')->field('inputtime,continuity_day')->where(array('userid' => $userid))->order('id DESC')->find();
	$inputtime = $data ? $data['inputtime'] : 0;

	$starttime = strtotime(date('Y-m-d'));
	if($inputtime > $starttime)  return array('status'=>1,'message'=>'今日已签到！','data'=>$data['continuity_day']);

	$continuity_day = 0;
	if($inputtime && (($inputtime + 24*3600)>$starttime)){ 
		$continuity_day = $data['continuity_day'];
	}

	return array('status'=>0,'message'=>'未签到','data'=>$continuity_day);
}	


/**
 * 检测是否帖子评论点赞
 *
 * @param $commentid 评论ID
 * @param $praise 点赞数
 * @param $userid 用户ID
 * @return bool
 */
function check_attitude($commentid, $praise, $userid=0){
	if(!$praise || !$userid) return false;
	$id = D('forum_attitude')->field('id')->where(array('commentid' => $commentid,'userid' => $userid))->one();

	return $id ? true : false;
}	


/**
 * 处理内容中的URL
 * @param  string $content    内容
 * @param  string $handle_url 跳转URL地址
 * @return string             
 */
function handle_url($content, $handle_url = '/bbs/index/handle_url'){
    
    preg_match_all('/<a href=\"(.*?)\".*?>(.*?)<\/a>/i', $content, $matchs);
    
    $urls = [];
    foreach($matchs[1] as $key=>$url){
        if(strstr($url, HTTP_HOST) || strstr($url, 'yzmcms.com')) {
            $urls[] = '<a href="'.$url.'" target="_blank">'. $matchs[2][$key] .'</a>';
        }else{
            $urls[] = '<a href="'.$handle_url.'?url='.urlencode($url).'" target="_blank">'. $matchs[2][$key] .'</a>';
        }
    }

    return $urls ? str_replace($matchs[0], $urls, $content) : $content;
}


/**
 * 生成列表URL
 *
 * @param $pid
 * @return bool
 */
function list_url($pid){
	return SITE_URL.'list/'.$pid.'.html';
}	


/**
 * 生成内容URL
 *
 * @param $id
 * @return bool
 */
function show_url($id){
	return SITE_URL.'show/'.$id.'.html';
}


/**
 * 前端内容筛选功能 - 生成条件url
 * @param   $num   条件键的索引index
 * @param   $value 条件键的值
 * @return  生成最终的条件url
 */
function simple_url($num, $value){
	$s = array();
	$s[] = input('get.catid', 0);
	$s[] = input('get.price', 0);
	$s[] = input('get.order', 0);
	$s[] = 1; //分页标识，切换条件时分页数需设为1
	$s[$num] = $value;
	$base_url = SITE_URL.'store/list_'.join('_', $s).C('url_html_suffix');
	return $base_url;
}


/**
 * 前端内容筛选功能 - 生成分页
 * @param   $total 一共多少条数据
 * @param   $row   每页显示记录数
 * @param   $num   分页page的key,例如list_0_0_1.html, 最后的1表示第一页，也就是page，它的key就是2
 */
function simple_page($total, $row=10, $num=1){
	$page = input('page', 1, 'intval'); 
	$page = $page>0 ? $page : 1;
    $page_total = ceil($total/$row);
    $str = '';
    if($page_total<=5){
        for($i=1; $i<=$page_total; $i++){
            $class = $page==$i ? ' curpage' : '';
            $str.='<a href="'.simple_url($num, $i).'" class="listpage'.$class.'">'.$i.'</a>';
        }
    }else{  
        if($page <= 3){
            $p =5;
        }else{
            $p = ($page+2)>=$page_total ? $page_total : $page+2;
        } 
        for($i=$p-4; $i<=$p; $i++){
            $class = $page==$i ? ' curpage' : '';
            $str.='<a href="'.simple_url($num, $i).'" class="listpage'.$class.'">'.$i.'</a>';
        }
    }
    if($total > 0){
    	$str = '<a href="'.simple_url($num, 1).'" class="homepage">'.L('home_page').'</a>'.$str;
    	$str .= '<a href="'.simple_url($num, $page_total).'" class="homepage">'.L('end_page').'</a>';
    }
    return '<span class="pageinfo">共<strong>'.$page_total.'</strong>页<strong>'.$total.'</strong>条记录</span>'.$str;
}