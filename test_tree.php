<?php
/**
 * 测试当前使用的 tree 类版本
 */

// 设置基本路径
define('YZMPHP_PATH', dirname(__FILE__) . '/');
define('DIRECTORY_SEPARATOR', '/');
define('EXT', '.class.php');

// 模拟 yzm_base::load_sys_class 的行为
function test_load_tree() {
    $classname = 'tree';
    $path = YZMPHP_PATH . 'yzmphp/core/class';
    
    echo "<h2>Tree 类加载测试</h2>\n";
    echo "<p>尝试加载路径: {$path}/{$classname}.class.php</p>\n";
    
    // 检查原始文件
    $original_file = $path . '/' . $classname . '.class.php';
    echo "<p>原始文件存在: " . (is_file($original_file) ? '是' : '否') . "</p>\n";
    
    // 检查优化文件
    $opti_file = $path . '/tree_opti.class.php';
    echo "<p>优化文件存在: " . (is_file($opti_file) ? '是' : '否') . "</p>\n";
    
    // 尝试加载原始版本
    if (is_file($original_file)) {
        include_once $original_file;
        $tree_original = new tree();
        echo "<h3>原始版本信息:</h3>\n";
        echo "<pre>";
        $reflection = new ReflectionClass($tree_original);
        echo "类名: " . $reflection->getName() . "\n";
        echo "文件路径: " . $reflection->getFileName() . "\n";
        echo "方法数量: " . count($reflection->getMethods()) . "\n";
        echo "是否有缓存属性: " . ($reflection->hasProperty('_cache') ? '是' : '否') . "\n";
        echo "</pre>\n";
    }
    
    return true;
}

// 测试数据
$test_data = array(
    1 => array('id'=>'1','parentid'=>0,'name'=>'测试栏目一','type'=>'category'),
    2 => array('id'=>'2','parentid'=>0,'name'=>'测试栏目二','type'=>'category'),
    3 => array('id'=>'3','parentid'=>1,'name'=>'子栏目一','type'=>'list'),
);

echo "<html><head><meta charset='utf-8'><title>Tree类测试</title></head><body>";

test_load_tree();

// 测试原始版本
if (class_exists('tree')) {
    echo "<h3>功能测试:</h3>\n";
    $tree = new tree();
    $tree->init($test_data);
    
    $template = '<option value="\\$id">\\$spacer\\$name (\\$type)</option>';
    $result = $tree->get_tree(0, $template);
    
    echo "<h4>生成结果:</h4>\n";
    echo "<pre>" . htmlspecialchars($result) . "</pre>\n";
}

echo "</body></html>";
?>
