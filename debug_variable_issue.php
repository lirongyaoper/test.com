<?php
/**
 * 调试变量覆盖问题
 */

echo "=== 调试变量覆盖问题 ===\n\n";

// 模拟后台数据结构
$test_data = array(
    1 => array('id'=>'1','parentid'=>0,'name'=>'新闻中心','type'=>'0'),
    2 => array('id'=>'2','parentid'=>1,'name'=>'公司新闻','type'=>'0'),
    3 => array('id'=>'3','parentid'=>1,'name'=>'行业动态','type'=>'0'),
);

echo "测试数据:\n";
foreach($test_data as $key => $item) {
    echo "数组键: $key, 数组中的id: {$item['id']}, parentid: {$item['parentid']}, name: {$item['name']}\n";
}

echo "\n=== 模拟原版逻辑 ===\n";

foreach($test_data as $id => $value) {
    echo "\nforeach循环 - 键: $id, 值数组: ";
    print_r($value);
    
    echo "执行 @extract(\$value) 前: \$id = $id\n";
    
    // 模拟 @extract($value)
    extract($value);
    
    echo "执行 @extract(\$value) 后: \$id = $id\n";
    echo "其他变量: name=$name, parentid=$parentid\n";
    echo "---\n";
}

echo "\n=== 模拟我的优化版本逻辑 ===\n";

foreach($test_data as $foreach_id => $value) {
    echo "\nforeach循环 - 键: $foreach_id, 值数组: ";
    print_r($value);
    
    // 我的错误实现
    $template_vars = array(
        'id' => $foreach_id  // 这里我错误地使用了foreach的键
    );
    $template_vars = array_merge($template_vars, $value);
    
    echo "我的实现 - template_vars['id']: {$template_vars['id']}\n";
    echo "正确应该是: {$value['id']}\n";
    echo "是否相同: " . ($template_vars['id'] == $value['id'] ? '是' : '否') . "\n";
    echo "---\n";
}

echo "\n🚨 **发现的问题:**\n";
echo "1. 我错误地将foreach的键(\$id)作为模板变量，但原版中\$id会被@extract覆盖\n";
echo "2. 这导致递归调用时使用了错误的ID值\n";
echo "3. 缩进和层级关系因此出现混乱\n";
echo "4. 可能导致无限循环或错误的树形结构\n";
?>
