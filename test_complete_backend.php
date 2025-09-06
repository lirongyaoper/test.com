<?php
/**
 * 完整的后台功能测试
 */

echo "<html><head><meta charset='utf-8'><title>完整后台测试</title></head><body>";
echo "<h1>🎯 完整后台功能测试</h1>";

require_once '/home/lirongyao0916/Projects/test.com/yzmphp/core/class/tree.class.php';

// 模拟真实的后台数据结构
$db_data = array(
    array('id'=>'1','catname'=>'新闻中心','parentid'=>0,'type'=>'0','modelid'=>1,'listorder'=>10,'display'=>1,'member_publish'=>1),
    array('id'=>'2','catname'=>'公司新闻','parentid'=>1,'type'=>'0','modelid'=>1,'listorder'=>20,'display'=>1,'member_publish'=>1),
    array('id'=>'3','catname'=>'行业动态','parentid'=>1,'type'=>'0','modelid'=>1,'listorder'=>30,'display'=>1,'member_publish'=>1),
    array('id'=>'4','catname'=>'产品展示','parentid'=>0,'type'=>'0','modelid'=>2,'listorder'=>40,'display'=>1,'member_publish'=>0),
    array('id'=>'5','catname'=>'产品分类A','parentid'=>4,'type'=>'0','modelid'=>2,'listorder'=>50,'display'=>1,'member_publish'=>0),
    array('id'=>'6','catname'=>'产品分类B','parentid'=>4,'type'=>'0','modelid'=>2,'listorder'=>60,'display'=>1,'member_publish'=>0),
);

// 模拟后台的数据处理
$array = array();
foreach($db_data as $v) {
    $v['name'] = $v['catname']; // 重要：后台会将 catname 复制为 name
    $v['class'] = $v['parentid'] ? 'child' : 'top';
    $v['cattype'] = $v['type']=="0" ? '普通栏目' : '单页面';
    $v['catmodel'] = '模型' . $v['modelid'];
    $v['pclink'] = '#';
    $v['domain'] = '';
    $v['display'] = $v['display'] ? '是' : '否';
    $v['member_publish'] = $v['member_publish'] ? '是' : '否';
    $v['string'] = '<a href="#">编辑</a> <a href="#">删除</a>';
    $v['parentoff'] = $v['parentid'] ? '' : '📁 ';
    
    $array[] = $v; // 使用数字索引
}

echo "<h2>📊 测试数据结构</h2>";
echo "<table border='1' cellpadding='3' cellspacing='0'>";
echo "<tr><th>数组键</th><th>ID字段</th><th>ParentID</th><th>Name</th></tr>";
foreach($array as $key => $v) {
    echo "<tr><td>$key</td><td>{$v['id']}</td><td>{$v['parentid']}</td><td>{$v['name']}</td></tr>";
}
echo "</table>";

echo "<h2>🌲 树形结构生成测试</h2>";

$tree = new tree();
$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
$tree->init($array);

// 使用后台的实际模板（简化版）
$str = "<tr class='text-c \$class'>
            <td>\$listorder</td>
            <td>\$id</td>
            <td class='text-l'>\$parentoff\$spacer\$name</td>
            <td>\$cattype</td>
            <td>\$catmodel</td>
            <td>\$display</td>
            <td>\$member_publish</td>
            <td>\$string</td>
        </tr>";

try {
    $result = $tree->get_tree(0, $str);
    
    echo "<h3>✅ 生成结果：</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr><th>排序</th><th>ID</th><th>栏目名称</th><th>类型</th><th>模型</th><th>显示</th><th>会员发布</th><th>操作</th></tr>";
    echo $result;
    echo "</table>";
    
    echo "<h3>🔍 关键验证点：</h3>";
    echo "<ul>";
    echo "<li>✅ ID是否正确显示（1,2,3,4,5,6）</li>";
    echo "<li>✅ 名称是否正确显示（不同的栏目名）</li>";
    echo "<li>✅ 层级关系是否正确（子栏目有正确缩进）</li>";
    echo "<li>✅ 没有无限重复</li>";
    echo "<li>✅ 没有内存溢出</li>";
    echo "</ul>";
    
} catch (Error $e) {
    echo "<p style='color:red;'><strong>❌ 仍然有错误: " . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>🎉 修复总结</h2>";
echo "<p><strong>修复的关键问题：</strong></p>";
echo "<ol>";
echo "<li><strong>变量覆盖逻辑修正</strong> - 不再错误地预设 foreach 键为 \$id</li>";
echo "<li><strong>递归ID修正</strong> - 使用数组中的真实 ID 进行递归调用</li>";
echo "<li><strong>转义字符处理</strong> - 正确处理 \\\$variable 格式</li>";
echo "<li><strong>递归保护机制</strong> - 防止无限递归导致内存溢出</li>";
echo "</ol>";

echo "</body></html>";
?>
