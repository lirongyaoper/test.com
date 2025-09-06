<?php
/**
 * 调试模板解析问题
 */

// 加载优化版本的 tree 类
require_once '/home/lirongyao0916/Projects/test.com/yzmphp/core/class/tree.class.php';

echo "<h2>模板解析调试</h2>\n";

$tree = new tree();

// 测试数据
$test_data = array(
    1 => array('id'=>'1','parentid'=>0,'name'=>'测试栏目','type'=>'category'),
);

$tree->init($test_data);

// 测试不同的模板格式
$templates = array(
    '简单模板1' => '<option value="$id">$name</option>',
    '简单模板2' => '<option value=\\$id>\\$name</option>',
    '复杂模板1' => '<tr><td>$id</td><td>$spacer$name</td></tr>',
    '复杂模板2' => '<tr><td>\\$id</td><td>\\$spacer\\$name</td></tr>',
);

foreach ($templates as $desc => $template) {
    echo "<h3>$desc</h3>\n";
    echo "<p><strong>模板:</strong> <code>" . htmlspecialchars($template) . "</code></p>\n";
    
    $tree->ret = ''; // 重置
    $result = $tree->get_tree(0, $template);
    
    echo "<p><strong>结果:</strong> <code>" . htmlspecialchars($result) . "</code></p>\n";
    echo "<hr>\n";
}

// 直接测试 parseTemplate 方法
echo "<h3>直接测试 parseTemplate 方法</h3>\n";

// 使用反射访问私有方法
$reflection = new ReflectionClass($tree);
$parseTemplate = $reflection->getMethod('parseTemplate');
$parseTemplate->setAccessible(true);

$test_vars = array(
    'id' => '123',
    'name' => '测试名称',
    'spacer' => '&nbsp;&nbsp;',
    'selected' => 'selected'
);

$test_templates = array(
    '$id',
    '$name',
    '$spacer$name',
    '\\$id',
    '\\$name',
    '<option value="$id" $selected>$spacer$name</option>',
    '<option value=\\$id \\$selected>\\$spacer\\$name</option>',
);

foreach ($test_templates as $template) {
    $result = $parseTemplate->invoke($tree, $template, $test_vars);
    echo "<p><strong>模板:</strong> <code>" . htmlspecialchars($template) . "</code></p>\n";
    echo "<p><strong>结果:</strong> <code>" . htmlspecialchars($result) . "</code></p>\n";
    echo "<br>\n";
}
?>
