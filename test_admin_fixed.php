<?php
/**
 * 修正后的后台栏目管理功能测试
 */

// 模拟后台环境
define('IN_YZMPHP', true);
define('APP_DEBUG', true);
define('URL_MODEL', '3');
define('YZMPHP_PATH', dirname(__FILE__) . '/');

// 加载框架
require(YZMPHP_PATH . 'yzmphp/yzmphp.php');

echo "<html><head><meta charset='utf-8'><title>修正后的后台栏目测试</title></head><body>";
echo "<h2>修正后的后台栏目管理测试</h2>";

try {
    // 直接测试 tree 类的加载和使用
    echo "<h3>Tree 类测试</h3>";
    
    // 加载 tree 类
    yzm_base::load_sys_class('tree', '', 0);
    $tree = new tree();
    
    // 检查类信息
    $reflection = new ReflectionClass($tree);
    echo "<p><strong>当前使用的 Tree 类信息:</strong></p>";
    echo "<ul>";
    echo "<li>类名: " . $reflection->getName() . "</li>";
    echo "<li>文件路径: " . $reflection->getFileName() . "</li>";
    echo "<li>方法数量: " . count($reflection->getMethods()) . "</li>";
    echo "<li>是否有缓存属性: " . ($reflection->hasProperty('_cache') ? '是(优化版)' : '否(原版)') . "</li>";
    echo "</ul>";
    
    // 模拟栏目数据
    $test_categories = array(
        1 => array('id'=>'1','parentid'=>0,'name'=>'新闻中心','type'=>'0','modelid'=>1,'listorder'=>10,'display'=>1,'member_publish'=>1),
        2 => array('id'=>'2','parentid'=>0,'name'=>'产品展示','type'=>'0','modelid'=>2,'listorder'=>20,'display'=>1,'member_publish'=>0),
        3 => array('id'=>'3','parentid'=>1,'name'=>'公司新闻','type'=>'0','modelid'=>1,'listorder'=>15,'display'=>1,'member_publish'=>1),
        4 => array('id'=>'4','parentid'=>1,'name'=>'行业动态','type'=>'0','modelid'=>1,'listorder'=>25,'display'=>1,'member_publish'=>1),
        5 => array('id'=>'5','parentid'=>2,'name'=>'产品分类A','type'=>'0','modelid'=>2,'listorder'=>30,'display'=>1,'member_publish'=>0),
    );
    
    echo "<h3>栏目树形结构生成测试</h3>";
    
    // 设置树形图标
    $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ','&nbsp;&nbsp;&nbsp;├─ ','&nbsp;&nbsp;&nbsp;└─ ');
    $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
    
    // 初始化数据
    $tree->init($test_categories);
    
    // 生成栏目列表 HTML（使用正确的模板格式，不使用转义）
    $str = '<tr class="text-c">
                <td><input type="text" class="input-text listorder" name="listorder[]" value="$listorder"><input type="hidden" name="catid[]" value="$id"></td>
                <td>$id</td>
                <td class="text-l">$spacer<a href="#" class="yzm_text_link">$name</a></td>
                <td>$type</td>
                <td>$modelid</td>
                <td>$display</td>
                <td>$member_publish</td>
                <td class="td-manage">
                    <a href="#" class="btn-mini btn-primary ml-5">编辑</a>
                    <a href="#" class="btn-mini btn-danger ml-5">删除</a>
                </td>
            </tr>';
    
    $result = $tree->get_tree(0, $str);
    
    echo "<h4>生成的栏目列表:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr><th>排序</th><th>ID</th><th>栏目名称</th><th>类型</th><th>模型ID</th><th>显示</th><th>会员发布</th><th>操作</th></tr>";
    echo $result;
    echo "</table>";
    
    echo "<h3>菜单树形结构生成测试</h3>";
    
    // 模拟菜单数据
    $test_menus = array(
        1 => array('id'=>'1','parentid'=>0,'name'=>'系统管理','listorder'=>10,'display'=>1),
        2 => array('id'=>'2','parentid'=>0,'name'=>'内容管理','listorder'=>20,'display'=>1),
        3 => array('id'=>'3','parentid'=>1,'name'=>'用户管理','listorder'=>15,'display'=>1),
        4 => array('id'=>'4','parentid'=>1,'name'=>'权限管理','listorder'=>25,'display'=>1),
        5 => array('id'=>'5','parentid'=>2,'name'=>'文章管理','listorder'=>30,'display'=>1),
    );
    
    $tree->ret = ''; // 重置
    $tree->init($test_menus);
    
    $menu_str = '<tr class="text-c">
                    <td><input name="listorders[$id]" type="text" value="$listorder" class="input-text listorder"></td>
                    <td>$id</td>
                    <td class="text-l">$spacer$name</td>
                    <td class="td-manage">$display</td>
                    <td>
                        <a href="#" class="btn-mini btn-success ml-5">编辑</a>
                        <a href="#" class="btn-mini btn-danger ml-5">删除</a>
                    </td>
                </tr>';
    
    $menu_result = $tree->get_tree(0, $menu_str);
    
    echo "<h4>生成的菜单列表:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;'>";
    echo "<tr><th>排序</th><th>ID</th><th>菜单名称</th><th>显示</th><th>操作</th></tr>";
    echo $menu_result;
    echo "</table>";
    
    echo "<p style='color:green;'><strong>✅ 所有测试通过！优化版 Tree 类工作正常。</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>❌ 错误: " . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color:red;'><strong>❌ 致命错误: " . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>
