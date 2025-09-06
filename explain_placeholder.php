<?php
/**
 * 解释临时占位符的必要性
 */

echo "=== 为什么需要临时占位符？===\n\n";

echo "1. **问题场景演示：**\n\n";

// 测试数据
$vars = array(
    'id' => '123',
    'name' => '测试名称',
    'spacer' => '&nbsp;&nbsp;'
);

echo "变量数据:\n";
foreach($vars as $key => $value) {
    echo "  \$key = '$key', \$value = '$value'\n";
}

echo "\n2. **测试不同的模板：**\n\n";

$templates = array(
    '正常变量' => '$id and $name',
    '转义变量' => '\\$id and \\$name', 
    '混合情况' => '$id and \\$name and $spacer',
    '复杂情况' => 'value="$id" class="\\$name" data="$spacer"'
);

foreach($templates as $desc => $template) {
    echo "🔸 **$desc:**\n";
    echo "   原始模板: " . $template . "\n";
    
    // 方法1: 不使用占位符（错误的方法）
    echo "   方法1 - 不使用占位符:\n";
    $result1 = $template;
    foreach($vars as $key => $value) {
        $result1 = str_replace('$' . $key, $value, $result1);
    }
    echo "     结果: " . $result1 . "\n";
    
    // 方法2: 使用占位符（正确的方法）
    echo "   方法2 - 使用占位符:\n";
    $result2 = $template;
    
    // 步骤1: 保护转义字符
    $placeholder = '___ESCAPED_DOLLAR___';
    $result2 = str_replace('\\$', $placeholder, $result2);
    echo "     步骤1 (保护转义): " . $result2 . "\n";
    
    // 步骤2: 替换变量
    foreach($vars as $key => $value) {
        $result2 = str_replace('$' . $key, $value, $result2);
    }
    echo "     步骤2 (替换变量): " . $result2 . "\n";
    
    // 步骤3: 恢复转义字符
    $result2 = str_replace($placeholder, '$', $result2);
    echo "     步骤3 (恢复转义): " . $result2 . "\n";
    
    echo "   ✅ 期望结果: ";
    if($desc == '正常变量') {
        echo "123 and 测试名称\n";
    } elseif($desc == '转义变量') {
        echo "\$id and \$name\n";
    } elseif($desc == '混合情况') {
        echo "123 and \$name and &nbsp;&nbsp;\n";
    } elseif($desc == '复杂情况') {
        echo 'value="123" class="$name" data="&nbsp;&nbsp;"' . "\n";
    }
    
    echo "   🎯 正确方法: " . ($result2 == ($desc == '正常变量' ? '123 and 测试名称' : 
        ($desc == '转义变量' ? '$id and $name' : 
        ($desc == '混合情况' ? '123 and $name and &nbsp;&nbsp;' : 
        'value="123" class="$name" data="&nbsp;&nbsp;"'))) ? '方法2 ✅' : '都不对 ❌') . "\n\n";
}

echo "3. **关键问题说明：**\n\n";

echo "🚨 **不使用占位符的问题：**\n";
$problem_template = '\\$id and $id';
echo "   模板: $problem_template\n";
echo "   期望: \$id and 123\n";

$result_wrong = $problem_template;
foreach($vars as $key => $value) {
    $result_wrong = str_replace('$' . $key, $value, $result_wrong);
}
echo "   错误结果: $result_wrong\n";
echo "   问题: 转义的 \\$id 也被替换了！\n\n";

echo "✅ **使用占位符的解决方案：**\n";
$result_right = $problem_template;
$placeholder = '___ESCAPED_DOLLAR___';
$result_right = str_replace('\\$', $placeholder, $result_right); // 保护转义
echo "   步骤1: " . $result_right . "\n";
foreach($vars as $key => $value) {
    $result_right = str_replace('$' . $key, $value, $result_right); // 替换变量
}
echo "   步骤2: " . $result_right . "\n";
$result_right = str_replace($placeholder, '$', $result_right); // 恢复转义
echo "   步骤3: " . $result_right . "\n";
echo "   ✅ 正确！转义的 \\$id 被保留为 \$id\n\n";

echo "4. **在原版PHP中的对应关系：**\n\n";
echo "   PHP原版逻辑:\n";
echo "   ```php\n";
echo "   @extract(\$value);  // 创建变量 \$id, \$name 等\n";
echo "   eval(\"\\\$nstr = \\\"\$str\\\";\");  // 在字符串中，\\$id 表示字面量 \$id\n";
echo "   ```\n\n";
echo "   我们的安全替代:\n";
echo "   ```php\n";
echo "   // 1. 保护转义字符 \\$ \n";
echo "   // 2. 替换变量 \$var\n";  
echo "   // 3. 恢复转义为字面量 \$\n";
echo "   ```\n\n";

echo "5. **总结：**\n";
echo "   📌 临时占位符是为了正确区分 '\$var'(要替换) 和 '\\$var'(要保留)\n";
echo "   📌 这完全模拟了PHP中 eval() 对转义字符的处理\n";
echo "   📌 确保与原版行为完全一致\n";
echo "   📌 这是安全替换 eval() 的必要技术手段\n";
?>
