<?php
/**
 * extention.func.php   用户自定义函数库
 *
 * @author           袁志蒙  
 * @license          http://www.yzmcms.com
 * @lastmodify       2018-03-18
 */
function Printarraylry($data, $level = 0, $isLast = true) {
    static $indent = 0; // 缩进计数器

    // HTML样式（自动内联，无需额外CSS）
    $styles = [
        'container' => 'margin-left: 15px; font-family: monospace;',
        'array' => 'color: #d63384;',
        'key' => 'color: #0066cc; font-weight: bold;',
        'string' => 'color: #28a745;',
        'number' => 'color: #fd7e14;',
        'boolean' => 'color: #6610f2;',
        'null' => 'color: #6c757d;'
    ];

    // 类型判断与颜色标记
    $type = gettype($data);
    switch ($type) {
        case 'array':
            $count = count($data);
            echo "<div style='{$styles['container']}'>";
            echo "<span style='{$styles['array']}'>Array(<span style='color:#6c757d'>$count</span>)</span> [";

            if ($count === 0) {
                echo " <span style='{$styles['null']}'>empty</span> ";
            } else {
                echo "<ul style='list-style-type: none; padding-left: 15px; margin: 0;'>";
                $i = 0;
                foreach ($data as $key => $value) {
                    $i++;
                    echo "<li>";
                    echo "<span style='{$styles['key']}'>" . htmlspecialchars($key) . "</span> => ";
                    Printarraylry($value, $level + 1, $i === $count);
                    echo "</li>";
                }
                echo "</ul>";
            }

            echo "]</div>";
            break;

        case 'string':
            echo "<span style='{$styles['string']}'>'" . htmlspecialchars($data) . "'</span>";
            break;

        case 'integer':
        case 'double':
            echo "<span style='{$styles['number']}'>$data</span>";
            break;

        case 'boolean':
            $val = $data ? 'true' : 'false';
            echo "<span style='{$styles['boolean']}'>$val</span>";
            break;

        case 'NULL':
            echo "<span style='{$styles['null']}'>null</span>";
            break;

        default:
            echo "<span>(unhandled type: $type)</span>";
    }
}

/**
 * 包裹函数：添加统一的HTML容器
 */
function Palry($data) {
    echo "<div style='background: #f8f9fa; border: 1px solid #ddd; padding: 15px; border-radius: 4px;'>";
    Printarraylry($data);
    echo "</div>";
}