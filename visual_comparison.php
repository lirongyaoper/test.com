<?php
/**
 * 直观的对比演示
 */

echo "=== 临时占位符的作用：直观对比 ===\n\n";

echo "🔍 **关键概念：**\n";
echo "在PHP的字符串中：\n";
echo "- \$var  → 要替换为变量值\n";
echo "- \\\$var → 要保留为字面量 \$var\n\n";

$vars = array('name' => 'John');

echo "📝 **示例模板：**\n";
$template = 'Hello $name, your username is \\$name';
echo "模板: '$template'\n";
echo "期望: 'Hello John, your username is \$name'\n\n";

echo "❌ **错误方法（简单替换）：**\n";
$wrong_result = str_replace('$name', $vars['name'], $template);
echo "str_replace('\$name', 'John', '$template')\n";
echo "结果: '$wrong_result'\n";
echo "问题: 转义的 \\\$name 也被替换了！应该保留为 \$name\n\n";

echo "✅ **正确方法（使用占位符）：**\n";

echo "步骤1: 保护转义字符\n";
$step1 = str_replace('\\$', '【PLACEHOLDER】', $template);
echo "  str_replace('\\\$', '【PLACEHOLDER】', '$template')\n";
echo "  结果: '$step1'\n";
echo "  说明: 将 \\\$ 替换为占位符，保护它不被后续替换影响\n\n";

echo "步骤2: 安全替换变量\n";
$step2 = str_replace('$name', $vars['name'], $step1);
echo "  str_replace('\$name', 'John', '$step1')\n";
echo "  结果: '$step2'\n";
echo "  说明: 现在只有真正的 \$name 被替换，占位符保护的部分不受影响\n\n";

echo "步骤3: 恢复转义字符\n";
$step3 = str_replace('【PLACEHOLDER】', '$', $step2);
echo "  str_replace('【PLACEHOLDER】', '\$', '$step2')\n";
echo "  结果: '$step3'\n";
echo "  说明: 将占位符恢复为 \$，完成转义字符的保护\n\n";

echo "🎯 **对比结果：**\n";
echo "错误方法: '$wrong_result' ❌\n";
echo "正确方法: '$step3' ✅\n";
echo "期望结果: 'Hello John, your username is \$name' ✅\n\n";

echo "🔧 **在 tree.class.php 中的应用：**\n\n";
echo "后台模板经常使用这种格式：\n";
echo "```\n";
echo "<input name='catid[]' value='\\\$id'>\\\$spacer\\\$name\n";
echo "```\n\n";
echo "期望输出：\n";
echo "```\n";
echo "<input name='catid[]' value='\$id'>\$spacer\$name\n";
echo "```\n\n";
echo "如果不使用占位符，会错误地输出：\n";
echo "```\n";
echo "<input name='catid[]' value='\\123'>\\&nbsp;&nbsp;\\测试名称\n";
echo "```\n\n";

echo "📌 **总结：**\n";
echo "临时占位符是安全替换 eval() 的关键技术，确保：\n";
echo "1. 🎯 正确区分变量和字面量\n";
echo "2. 🔒 避免转义字符被误替换\n";
echo "3. ✅ 与原版 eval() 行为完全一致\n";
echo "4. 🛡️ 提供安全的模板解析功能\n";
?>
