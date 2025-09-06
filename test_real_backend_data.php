<?php
/**
 * 测试真实后台数据结构
 */

echo "=== 测试真实后台数据结构 ===\n\n";

require_once '/home/lirongyao0916/Projects/test.com/yzmphp/core/class/tree.class.php';

// 模拟后台category.class.php中的数据处理逻辑
echo "1. **模拟后台数据处理流程：**\n";

// 模拟从数据库查询的数据（注意：这里使用的是 catid AS id）
$db_data = array(
    array('id'=>'1','catname'=>'新闻中心','parentid'=>0,'type'=>'0','modelid'=>1,'listorder'=>10,'display'=>1,'member_publish'=>1),
    array('id'=>'2','catname'=>'公司新闻','parentid'=>1,'type'=>'0','modelid'=>1,'listorder'=>20,'display'=>1,'member_publish'=>1),
    array('id'=>'3','catname'=>'行业动态','parentid'=>1,'type'=>'0','modelid'=>1,'listorder'=>30,'display'=>1,'member_publish'=>1),
);

echo "   数据库查询结果:\n";
foreach($db_data as $v) {
    echo "   - ID: {$v['id']}, Name: {$v['catname']}, ParentID: {$v['parentid']}\n";
}

// 模拟后台的数据处理（添加额外字段）
$array = array();
foreach($db_data as $v) {
    // 模拟后台添加的额外字段
    $v['name'] = $v['catname']; // 重要：后台会将 catname 复制为 name
    $v['class'] = $v['parentid'] ? 'child' : 'top';
    $v['string'] = '操作按钮';
    $v['cattype'] = '普通栏目';
    $v['catmodel'] = '文章模型';
    $v['pclink'] = '#';
    $v['domain'] = '';
    
    $array[] = $v; // 注意：这里使用数字索引，不是 catid 作为键！
}

echo "\n   处理后的数组结构:\n";
foreach($array as $key => $v) {
    echo "   - 数组键: $key, ID字段: {$v['id']}, Name: {$v['name']}\n";
}

echo "\n2. **这里就是问题所在！**\n";
echo "   🚨 数组键（0,1,2...）与 ID字段（1,2,3...）不一致！\n";
echo "   🚨 我的实现错误地使用了数组键作为 \$id\n";
echo "   🚨 但原版中 @extract() 会用数组中的 'id' 字段覆盖 \$id\n\n";

echo "3. **测试我的错误实现：**\n";

$tree = new tree();
$tree->init($array);

// 使用后台的实际模板
$str = "<tr class='text-c \$class'>
            <td>\$id</td>
            <td>\$spacer\$name</td>
        </tr>";

echo "   使用的模板（简化版）: " . htmlspecialchars($str) . "\n";

try {
    $result = $tree->get_tree(0, $str);
    echo "   结果:\n";
    echo htmlspecialchars($result) . "\n";
} catch (Error $e) {
    echo "   ❌ 错误: " . $e->getMessage() . "\n";
}

echo "\n4. **修复方案：**\n";
echo "   🔧 不应该将 foreach 的键作为 template_vars['id']\n";
echo "   🔧 应该让 @extract() 完全控制变量覆盖\n";
echo "   🔧 只有 spacer 和 selected 是真正的局部变量\n";
?>
