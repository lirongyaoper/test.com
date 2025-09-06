<?php
/**
 * 修复 parseTemplate 方法的脚本
 */

$file = '/home/lirongyao0916/Projects/test.com/yzmphp/core/class/tree.class.php';
$content = file_get_contents($file);

// 要替换的旧代码
$old_code = '        // 动态替换所有变量，完全模拟 @extract() 的行为
        foreach($vars as $key => $value) {
            if(is_scalar($value) || is_null($value)) {
                // 替换 $key 格式的变量
                $result = str_replace(\'$\' . $key, (string)$value, $result);
            }
        }';

// 新的修复代码
$new_code = '        // 动态替换所有变量，正确处理转义字符
        // 先处理转义：将 \$ 替换为临时占位符
        $placeholder = \'___ESCAPED_DOLLAR___\';
        $result = str_replace(\'\\\\$\', $placeholder, $result);
        
        // 然后替换变量
        foreach($vars as $key => $value) {
            if(is_scalar($value) || is_null($value)) {
                // 替换 $key 格式的变量
                $result = str_replace(\'$\' . $key, (string)$value, $result);
            }
        }
        
        // 最后恢复转义字符
        $result = str_replace($placeholder, \'$\', $result);';

// 执行替换
$new_content = str_replace($old_code, $new_code, $content);

if ($new_content !== $content) {
    file_put_contents($file, $new_content);
    echo "✅ parseTemplate 方法已成功修复！\n";
} else {
    echo "❌ 未找到需要替换的代码\n";
}

// 验证修复结果
echo "\n验证修复结果:\n";
echo "文件大小: " . filesize($file) . " 字节\n";
echo "文件行数: " . substr_count(file_get_contents($file), "\n") . " 行\n";
?>
