<?php
/**
 * 清晰演示临时占位符的必要性
 */

echo "=== 临时占位符的必要性详解 ===\n\n";

echo "🎯 **核心问题：如何区分要替换的变量和要保留的字面量？**\n\n";

// 测试变量
$vars = array('id' => '123', 'name' => '测试');

echo "📋 **场景1: 简单情况**\n";
$template1 = 'Hello $name';
echo "模板: '$template1'\n";
echo "期望: 'Hello 测试'\n";

$result1 = str_replace('$name', $vars['name'], $template1);
echo "结果: '$result1' ✅\n\n";

echo "📋 **场景2: 转义情况**\n";
$template2 = 'Hello \\$name';  // 注意：\\$ 应该保留为字面量 $name
echo "模板: '$template2'\n";
echo "期望: 'Hello \$name' (保留字面量)\n";

$result2_wrong = str_replace('$name', $vars['name'], $template2);
echo "错误做法: '$result2_wrong' ❌ (转义被破坏了)\n";

// 正确做法
$result2_right = $template2;
$result2_right = str_replace('\\$', '___TEMP___', $result2_right);  // 保护
$result2_right = str_replace('$name', $vars['name'], $result2_right);  // 替换
$result2_right = str_replace('___TEMP___', '$', $result2_right);  // 恢复
echo "正确做法: '$result2_right' ✅\n\n";

echo "📋 **场景3: 混合情况（最复杂）**\n";
$template3 = 'value="$id" title="\\$name says: $name"';
echo "模板: '$template3'\n";
echo "期望: 'value=\"123\" title=\"\$name says: 测试\"'\n";
echo "说明: \$id要替换为123, \\$name要保留为\$name, \$name要替换为测试\n\n";

// 错误做法：直接替换
echo "❌ **错误做法（不使用占位符）：**\n";
$wrong = $template3;
foreach($vars as $key => $value) {
    $wrong = str_replace('$' . $key, $value, $wrong);
    echo "替换 \$key: '$wrong'\n";
}
echo "最终错误结果: '$wrong'\n";
echo "问题: 转义的 \\$name 也被替换了！\n\n";

// 正确做法：使用占位符
echo "✅ **正确做法（使用占位符）：**\n";
$right = $template3;

echo "步骤1 - 保护转义字符:\n";
$right = str_replace('\\$', '___ESCAPED_DOLLAR___', $right);
echo "  '$right'\n";

echo "步骤2 - 安全替换变量:\n";
foreach($vars as $key => $value) {
    $old_right = $right;
    $right = str_replace('$' . $key, $value, $right);
    echo "  替换 \$key: '$old_right' → '$right'\n";
}

echo "步骤3 - 恢复转义字符:\n";
$right = str_replace('___ESCAPED_DOLLAR___', '$', $right);
echo "  '$right'\n";

echo "\n🎉 **最终对比：**\n";
echo "错误结果: '$wrong'\n";
echo "正确结果: '$right'\n";
echo "期望结果: 'value=\"123\" title=\"\$name says: 测试\"'\n\n";

echo "📌 **总结：**\n";
echo "1. 临时占位符用于保护转义字符 \\$ 不被误替换\n";
echo "2. 这样可以正确区分 '\$var'(要替换) 和 '\\$var'(要保留)\n";
echo "3. 完全模拟了 PHP eval() 中对转义字符的处理\n";
echo "4. 确保与原版 tree.class.php 的 eval() 行为完全一致\n";
echo "5. 这是安全替换 eval() 的关键技术\n";
?>
