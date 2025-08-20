### 概述
`file_finder_replacer.py` 是一款支持内容与名称（文件/文件夹）查找与替换的增强工具，具备正则匹配、大小写控制、整词匹配、并发扫描、glob 过滤、编码回退、二进制/大文件规避、Dry-Run、安全备份、上下文报告与 JSON 输出等能力。

### 环境要求
- Python 3.8+（建议 3.9+）
- Linux/macOS/Windows（已在 Linux 验证）

### 运行方式
- 工作目录：`/home/lirongyao0916/Projects/test.com`
- 调用：
```bash
python3 file_finder_replacer.py [参数...]
```

### 基本用法
- 仅搜索（内容+名称）：
```bash
python3 file_finder_replacer.py -s db_host
```
- 搜索并替换（交互确认）：
```bash
python3 file_finder_replacer.py -s old -r new
```
- 自动替换与重命名（无需确认）：
```bash
python3 file_finder_replacer.py -s old -r new -a
```

### 常用参数
- 路径与筛选
  - **-d/--dir**: 搜索目录（默认当前）
  - **-e/--extensions**: 扩展名过滤，例 `.py,.php`
  - **--include-glob**: 仅包含匹配的路径（逗号分隔）
  - **--exclude-glob**: 排除匹配的路径（逗号分隔）
  - **--ignore-dirs**: 跳过目录（glob，逗号分隔），例 `.git,node_modules,venv`
  - **--follow-symlinks**: 跟随符号链接
  - **--max-size**: 最大文件大小（默认 5M，支持 K/M/G）
- 匹配控制
  - **-s/--search**: 搜索文本（必填，默认字面量）
  - **--regex**: 将搜索串视为正则表达式
  - **--ignore-case**: 忽略大小写
  - **--word**: 整词匹配（正则模式下等效为两侧加边界）
- 输出与报告
  - **-o/--output**: 输出文本报告到 `<search>_search_results.txt`
  - **--context**: 文本报告中每处匹配的上下文行数
  - **--json-out**: 导出 JSON 结果到指定文件
- 替换与安全
  - **-r/--replace**: 替换为
  - **-a/--auto**: 自动替换/重命名（无需确认）
  - **-i/--interactive**: 交互模式（默认存在）
  - **--dry-run**: 演练模式，不写回磁盘
  - **--backup**: 写回前对文件创建 `.bak` 备份
- 性能与兼容
  - **--threads**: 并发线程数（默认 CPU*4）
  - **--encodings**: 尝试的编码列表（逗号分隔），默认按 utf-8/gbk/gb2312/big5/latin-1 回退

### 典型场景示例
- 忽略大小写搜索，保存文本报告与 JSON（含上下文）：
```bash
python3 file_finder_replacer.py -s db_host --ignore-case -o --context 2 --json-out out.json
```
- 在 `application/**/*.php` 内正则整词替换并备份，排除 `vendor/**`：
```bash
python3 file_finder_replacer.py -s "(?i)token" -r "auth_token" --regex --word \
  --include-glob "application/**/*.php" --exclude-glob "vendor/**" \
  --auto --backup
```
- 先看变更范围（Dry-Run 演练），再实际执行：
```bash
python3 file_finder_replacer.py -s old -r new --dry-run
python3 file_finder_replacer.py -s old -r new -a --backup
```
- 名称正则重命名（自动、忽略大小写）：
```bash
python3 file_finder_replacer.py -s "(?i)config(\\d+)" -r "cfg\\1" --regex --auto
```
- 扩展名过滤 + 大文件限制 + 指定编码回退：
```bash
python3 file_finder_replacer.py -s needle -e .php,.js --max-size 1M --encodings utf-8,gbk
```

### 输出说明
- 终端打印：
  - 内容匹配列表：每个文件的匹配次数
  - 名称匹配列表：文件/文件夹路径
- 文本报告（`-o`）：
  - 文件路径、匹配行号、列号
  - 可选上下文（`--context N`）
- JSON（`--json-out path`）：
```json
{
  "dir": "/abs/search/dir",
  "content_results": [
    {
      "file_path": "/abs/path/file.php",
      "relative_path": "sub/file.php",
      "matches": [
        { "line_num": 12, "line_content": "....", "column": 5, "matched": "token" }
      ]
    }
  ],
  "name_results": [
    { "type": "file", "name": "config1.php", "abs_path": "/abs/.../config1.php", "rel_path": "..." }
  ]
}
```

### 替换与重命名行为
- 替换
  - 同一文件的同一行仅进行一次替换动作，但会替换该行所有匹配项，计数合并显示
  - `--regex/--ignore-case/--word` 同时适用于替换
  - `--dry-run` 不写回，仅显示计划
  - `--backup` 写回前生成 `.bak`
- 重命名
  - 支持正则与忽略大小写
  - 目标已存在时跳过并提示
  - `--dry-run` 仅展示计划变更

### 安全与回滚
- 建议先 `--dry-run` 预演，再加 `--backup` 实施
- 备份文件：`原文件名.bak`
- 如需回滚，直接用备份覆盖原文件

### 性能建议
- 使用 `--include-glob/--exclude-glob/--extensions` 缩小范围
- 合理设置 `--threads`（默认 CPU*4 即可）
- 通过 `--max-size` 避免扫描超大文件
- 大量小文件时并发收益明显

### 编码与二进制处理
- 多编码回退链：utf-8/utf-8-sig/gbk/gb2312/big5/latin-1；最后 utf-8 replace 宽松读取
- 自动跳过二进制文件（检测 NUL 字节）
- 可使用 `--encodings` 自定义顺序

### 兼容与限制
- 不加新参数时，默认行为与原脚本兼容（字面匹配、打印结果）
- Windows 下 glob 分隔符请用引号包裹，注意转义正则
- 极端大仓库请结合 `--include-glob` 缩小范围

### 故障排查
- 无结果：检查大小写、正则转义、glob 是否过滤过多
- 编码错误：添加 `--encodings` 扩大回退链
- 替换未生效：确认是否使用了 `--regex/--word` 导致匹配变更
- 权限问题：使用有权限的用户或移除只读属性

如需进一步定制（如多模式同时搜索、交互多选批量确认、增量缓存、彩色输出等），可以继续扩展。