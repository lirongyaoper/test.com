#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
文件查找和替换工具 - 增强版
支持内容、文件名、文件夹名的查找与替换
"""

import os
import re
import sys
import json
import shutil
import argparse
import fnmatch
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
from typing import List, Dict

class FileFinderReplacer:
    def __init__(self, search_dir: str = ".", encodings: List[str] = None, follow_symlinks: bool = False):
        self.search_dir = Path(search_dir).resolve()
        self.results = []  # 文件内容匹配
        self.name_results = []  # 文件名/文件夹名匹配
        self.encodings = encodings or [
            'utf-8', 'utf-8-sig', 'gbk', 'gb2312', 'big5', 'latin-1'
        ]
        self.follow_symlinks = follow_symlinks

    # -------------- 内部工具方法 --------------
    def _compile_pattern(self, search_text: str, use_regex: bool, ignore_case: bool, whole_word: bool) -> re.Pattern:
        flags = re.MULTILINE
        if ignore_case:
            flags |= re.IGNORECASE
        pattern_text = search_text if use_regex else re.escape(search_text)
        if whole_word:
            pattern_text = rf"\b{pattern_text}\b"
        return re.compile(pattern_text, flags)

    def _is_binary(self, file_path: Path) -> bool:
        try:
            with open(file_path, 'rb') as f:
                chunk = f.read(1024)
                return b'\0' in chunk
        except Exception:
            return True

    def _read_lines_with_fallback(self, file_path: Path) -> List[str]:
        last_err = None
        for enc in self.encodings:
            try:
                with open(file_path, 'r', encoding=enc, errors='strict') as f:
                    return f.readlines()
            except Exception as e:
                last_err = e
                continue
        # 最后尝试宽松读取，避免完全失败
        try:
            with open(file_path, 'r', encoding='utf-8', errors='replace') as f:
                return f.readlines()
        except Exception as e:
            raise last_err or e

    def _file_size_ok(self, file_path: Path, max_size_bytes: int) -> bool:
        try:
            return file_path.stat().st_size <= max_size_bytes
        except Exception:
            return False

    def _match_globs(self, path_str: str, include_globs: List[str], exclude_globs: List[str]) -> bool:
        if exclude_globs:
            for g in exclude_globs:
                if fnmatch.fnmatch(path_str, g):
                    return False
        if include_globs:
            for g in include_globs:
                if fnmatch.fnmatch(path_str, g):
                    return True
            return False
        return True

    def _iter_candidate_files(
        self,
        file_extensions: List[str],
        include_globs: List[str],
        exclude_globs: List[str],
        ignore_dirs: List[str],
        max_size_bytes: int,
    ):
        for root, dirs, files in os.walk(self.search_dir, followlinks=self.follow_symlinks):
            # 过滤目录
            pruned = []
            for d in dirs:
                # 隐藏目录及忽略目录
                if d.startswith('.') or (ignore_dirs and any(fnmatch.fnmatch(d, pat) for pat in ignore_dirs)):
                    continue
                pruned.append(d)
            dirs[:] = pruned
            for file in files:
                if file.startswith('.'):  # 隐藏文件
                    continue
                file_path = Path(root) / file
                # 扩展名过滤
                if file_extensions:
                    file_ext = file_path.suffix.lower()
                    if file_ext not in file_extensions:
                        continue
                rel_str = str(file_path.relative_to(self.search_dir))
                if not self._match_globs(rel_str, include_globs, exclude_globs):
                    continue
                if not self._file_size_ok(file_path, max_size_bytes) or self._is_binary(file_path):
                    continue
                yield file_path

    def find_files_with_text(
        self,
        search_text: str,
        file_extensions: List[str] = None,
        use_regex: bool = False,
        ignore_case: bool = False,
        whole_word: bool = False,
        include_globs: List[str] = None,
        exclude_globs: List[str] = None,
        ignore_dirs: List[str] = None,
        max_size_bytes: int = 5 * 1024 * 1024,
        threads: int = max(4, (os.cpu_count() or 2) * 4),
    ) -> List[Dict]:
        self.results = []
        if not self.search_dir.exists():
            print(f"错误：目录 {self.search_dir} 不存在")
            return []
        include_globs = include_globs or []
        exclude_globs = exclude_globs or []
        ignore_dirs = ignore_dirs or []
        pattern = self._compile_pattern(search_text, use_regex, ignore_case, whole_word)

        def worker(file_path: Path):
            try:
                lines = self._read_lines_with_fallback(file_path)
            except Exception:
                return None
            matches = []
            for line_num, line in enumerate(lines, 1):
                line_no_newline = line.rstrip('\n')
                line_matches = list(pattern.finditer(line_no_newline))
                if line_matches:
                    for m in line_matches:
                        matches.append({
                            'line_num': line_num,
                            'line_content': line_no_newline,
                            'column': m.start() + 1,
                            'matched': m.group(0)
                        })
            if matches:
                return {
                    'file_path': str(file_path),
                    'relative_path': str(file_path.relative_to(self.search_dir)),
                    'matches': matches
                }
            return None

        candidates = list(self._iter_candidate_files(
            file_extensions=file_extensions,
            include_globs=include_globs,
            exclude_globs=exclude_globs,
            ignore_dirs=ignore_dirs,
            max_size_bytes=max_size_bytes,
        ))
        if not candidates:
            return []
        results: List[Dict] = []
        with ThreadPoolExecutor(max_workers=threads) as ex:
            futures = {ex.submit(worker, fp): fp for fp in candidates}
            for fut in as_completed(futures):
                res = fut.result()
                if res:
                    results.append(res)
        # 排序输出稳定
        results.sort(key=lambda r: r['relative_path'])
        self.results = results
        return results

    def find_names(
        self,
        search_text: str,
        use_regex: bool = False,
        ignore_case: bool = False,
        include_globs: List[str] = None,
        exclude_globs: List[str] = None,
        ignore_dirs: List[str] = None,
    ) -> List[Dict]:
        """
        查找文件名和文件夹名中包含/匹配 search_text 的项
        """
        self.name_results = []
        include_globs = include_globs or []
        exclude_globs = exclude_globs or []
        ignore_dirs = ignore_dirs or []

        flags = re.IGNORECASE if ignore_case else 0
        name_pattern = None
        if use_regex:
            name_pattern = re.compile(search_text, flags)

        for root, dirs, files in os.walk(self.search_dir, followlinks=self.follow_symlinks):
            # 过滤目录
            pruned = []
            for d in dirs:
                if d.startswith('.') or (ignore_dirs and any(fnmatch.fnmatch(d, pat) for pat in ignore_dirs)):
                    continue
                pruned.append(d)
            dirs[:] = pruned

            # 目录名匹配
            for d in dirs:
                rel_path = str(Path(root).joinpath(d).relative_to(self.search_dir))
                if not self._match_globs(rel_path, include_globs, exclude_globs):
                    continue
                matched = False
                if use_regex:
                    matched = bool(name_pattern.search(d))
                else:
                    matched = (search_text.lower() in d.lower()) if ignore_case else (search_text in d)
                if matched:
                    abs_path = str(Path(root) / d)
                    self.name_results.append({
                        'type': 'dir', 'name': d, 'abs_path': abs_path, 'rel_path': rel_path
                    })

            # 文件名匹配
            for f in files:
                if f.startswith('.'):
                    continue
                rel_path = str(Path(root).joinpath(f).relative_to(self.search_dir))
                if not self._match_globs(rel_path, include_globs, exclude_globs):
                    continue
                matched = False
                if use_regex:
                    matched = bool(name_pattern.search(f))
                else:
                    matched = (search_text.lower() in f.lower()) if ignore_case else (search_text in f)
                if matched:
                    abs_path = str(Path(root) / f)
                    self.name_results.append({
                        'type': 'file', 'name': f, 'abs_path': abs_path, 'rel_path': rel_path
                    })
        return self.name_results

    def save_results_to_file(self, search_text: str, context: int = 0) -> str:
        if not self.results and not self.name_results:
            print("没有找到匹配的内容/名称")
            return ""
        safe_filename = re.sub(r'[<>:"/\\|?*]', '_', search_text)
        if len(safe_filename) > 50:
            safe_filename = safe_filename[:50]
        output_file = f"{safe_filename}_search_results.txt"
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(f"搜索文本: {search_text}\n")
            f.write(f"搜索目录: {self.search_dir}\n")
            f.write(f"\n[文件内容匹配]\n")
            f.write(f"找到 {len(self.results)} 个文件内容包含匹配内容\n")
            f.write("=" * 80 + "\n\n")
            for result in self.results:
                f.write(f"文件: {result['relative_path']}\n")
                f.write(f"完整路径: {result['file_path']}\n")
                f.write("-" * 60 + "\n")
                # 为了上下文，再次读取文件
                try:
                    lines = self._read_lines_with_fallback(Path(result['file_path']))
                except Exception:
                    lines = []
                for match in result['matches']:
                    f.write(f"行号: {match.get('line_num')}, 列号: {match.get('column')}\n")
                    if context and lines:
                        start = max(0, match.get('line_num', 1) - 1 - context)
                        end = min(len(lines), match.get('line_num', 1) - 1 + context + 1)
                        for i in range(start, end):
                            prefix = '>' if (i == match.get('line_num', 1) - 1) else ' '
                            f.write(f"{prefix} {i+1:>6}: {lines[i].rstrip()}\n")
                    else:
                        f.write(f"内容: {match.get('line_content','').rstrip()}\n")
                    f.write("\n")
                f.write("=" * 80 + "\n\n")
            f.write(f"\n[文件名/文件夹名匹配]\n")
            f.write(f"找到 {len(self.name_results)} 个文件名/文件夹名包含匹配内容\n")
            f.write("=" * 80 + "\n\n")
            for item in self.name_results:
                f.write(f"类型: {'文件夹' if item['type']=='dir' else '文件'}\n")
                f.write(f"名称: {item['name']}\n")
                f.write(f"相对路径: {item['rel_path']}\n")
                f.write(f"完整路径: {item['abs_path']}\n")
                f.write("=" * 80 + "\n\n")
        print(f"搜索结果已保存到: {output_file}")
        return output_file

    def save_results_to_json(self, output_path: str) -> str:
        data = {
            'dir': str(self.search_dir),
            'content_results': self.results,
            'name_results': self.name_results,
        }
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
        print(f"JSON 搜索结果已保存到: {output_path}")
        return output_path

    def replace_text_interactive(self, search_text: str, replace_text: str, *, use_regex: bool = False, ignore_case: bool = False, whole_word: bool = False, backup: bool = False, dry_run: bool = False) -> int:
        if not self.results:
            print("没有找到要替换的文件内容")
            return 0
        total_replacements = 0
        print(f"\n开始交互式内容替换:")
        for result in self.results:
            file_path = Path(result['file_path'])
            try:
                lines = self._read_lines_with_fallback(file_path)
                file_modified = False
                new_lines = lines.copy()
                pattern = self._compile_pattern(search_text, use_regex, ignore_case, whole_word)
                last_line_num = None
                for match in result['matches']:
                    line_num = match['line_num'] - 1
                    if last_line_num == line_num:
                        # 同一行只询问一次
                        continue
                    last_line_num = line_num
                    original_line = lines[line_num]
                    if pattern.search(original_line):
                        print(f"\n文件: {result['relative_path']}")
                        print(f"行号: {match['line_num']}")
                        print(f"原内容: {original_line.rstrip()}")
                        while True:
                            response = input("是否替换这一行? (y/n/s=跳过此文件/q=退出): ").lower().strip()
                            if response in ['y', 'n', 's', 'q']:
                                break
                            print("请输入 y, n, s 或 q")
                        if response == 'y':
                            new_line, cnt = pattern.subn(replace_text, original_line)
                            new_lines[line_num] = new_line
                            print(f"新内容: {new_line.rstrip()}")
                            file_modified = True
                            total_replacements += cnt
                        elif response == 's':
                            print("跳过此文件")
                            break
                        elif response == 'q':
                            print("用户取消操作")
                            return total_replacements
                        else:
                            print("跳过这一行")
                if file_modified:
                    if backup and not dry_run:
                        try:
                            shutil.copy2(file_path, f"{file_path}.bak")
                        except Exception:
                            pass
                    if not dry_run:
                        with open(file_path, 'w', encoding='utf-8') as f:
                            f.writelines(new_lines)
                    print(f"文件已更新: {result['relative_path']}{' (dry-run)' if dry_run else ''}")
            except (UnicodeDecodeError, PermissionError, IOError):
                continue
        print(f"\n内容替换完成，共替换了 {total_replacements} 处")
        return total_replacements

    def replace_text_auto(self, search_text: str, replace_text: str, *, use_regex: bool = False, ignore_case: bool = False, whole_word: bool = False, backup: bool = False, dry_run: bool = False) -> int:
        if not self.results:
            print("没有找到要替换的文件内容")
            return 0
        total_replacements = 0
        print(f"\n开始自动内容替换:")
        for result in self.results:
            file_path = Path(result['file_path'])
            try:
                lines = self._read_lines_with_fallback(file_path)
                file_modified = False
                new_lines = lines.copy()
                pattern = self._compile_pattern(search_text, use_regex, ignore_case, whole_word)
                touched_lines = set()
                for m in result['matches']:
                    line_num = m['line_num'] - 1
                    if line_num in touched_lines:
                        continue
                    touched_lines.add(line_num)
                    original_line = lines[line_num]
                    new_line, cnt = pattern.subn(replace_text, original_line)
                    if cnt > 0:
                        new_lines[line_num] = new_line
                        file_modified = True
                        total_replacements += cnt
                        print(f"内容替换: {result['relative_path']}:{m['line_num']} (+{cnt})")
                if file_modified:
                    if backup and not dry_run:
                        try:
                            shutil.copy2(file_path, f"{file_path}.bak")
                        except Exception:
                            pass
                    if not dry_run:
                        with open(file_path, 'w', encoding='utf-8') as f:
                            f.writelines(new_lines)
            except (UnicodeDecodeError, PermissionError, IOError):
                continue
        print(f"\n自动内容替换完成，共替换了 {total_replacements} 处")
        return total_replacements

    def replace_names_interactive(self, search_text: str, replace_text: str, *, use_regex: bool = False, ignore_case: bool = False, dry_run: bool = False):
        if not self.name_results:
            print("没有找到要重命名的文件名/文件夹名")
            return 0
        count = 0
        print(f"\n开始交互式重命名:")
        for item in sorted(self.name_results, key=lambda x: -len(x['abs_path'])):
            old_path = Path(item['abs_path'])
            if use_regex:
                flags = re.IGNORECASE if ignore_case else 0
                new_name = re.sub(search_text, replace_text, item['name'], flags=flags)
            else:
                new_name = item['name'].replace(search_text, replace_text) if not ignore_case else re.sub(re.escape(search_text), replace_text, item['name'], flags=re.IGNORECASE)
            new_path = old_path.parent / new_name
            print(f"类型: {'文件夹' if item['type']=='dir' else '文件'}")
            print(f"原名称: {item['name']}")
            print(f"路径: {item['rel_path']}")
            print(f"新名称: {new_name}")
            while True:
                response = input("是否重命名? (y/n/q=退出): ").lower().strip()
                if response in ['y', 'n', 'q']:
                    break
                print("请输入 y, n 或 q")
            if response == 'y':
                try:
                    if dry_run:
                        print(f"[dry-run] 将重命名: {old_path} -> {new_path}")
                        count += 1
                    else:
                        if new_path.exists():
                            print(f"重命名跳过，目标已存在: {new_path}")
                        else:
                            old_path.rename(new_path)
                            print(f"已重命名: {old_path} -> {new_path}")
                            count += 1
                except Exception as e:
                    print(f"重命名失败: {e}")
            elif response == 'q':
                print("用户取消操作")
                return count
        print(f"\n重命名完成，共重命名了 {count} 个")
        return count

    def replace_names_auto(self, search_text: str, replace_text: str, *, use_regex: bool = False, ignore_case: bool = False, dry_run: bool = False):
        if not self.name_results:
            print("没有找到要重命名的文件名/文件夹名")
            return 0
        count = 0
        print(f"\n开始自动重命名:")
        # 先重命名文件，再重命名文件夹（从深到浅）
        for item in sorted(self.name_results, key=lambda x: (-len(x['abs_path']), x['type'] == 'dir')):
            old_path = Path(item['abs_path'])
            if use_regex:
                flags = re.IGNORECASE if ignore_case else 0
                new_name = re.sub(search_text, replace_text, item['name'], flags=flags)
            else:
                new_name = item['name'].replace(search_text, replace_text) if not ignore_case else re.sub(re.escape(search_text), replace_text, item['name'], flags=re.IGNORECASE)
            new_path = old_path.parent / new_name
            try:
                if dry_run:
                    print(f"[dry-run] 将重命名: {old_path} -> {new_path}")
                    count += 1
                else:
                    if new_path.exists():
                        print(f"重命名跳过，目标已存在: {new_path}")
                    else:
                        old_path.rename(new_path)
                        print(f"已重命名: {old_path} -> {new_path}")
                        count += 1
            except Exception as e:
                print(f"重命名失败: {e}")
        print(f"\n自动重命名完成，共重命名了 {count} 个")
        return count


def main():
    parser = argparse.ArgumentParser(
        description='文件查找和替换工具（内容+文件名+文件夹名）',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
使用示例:
  # 查找包含 'db_host' 的内容、文件名、文件夹名
  python3 file_finder_replacer.py -s db_host
  
  # 查找并保存结果
  python3 file_finder_replacer.py -s db_host -o
  
  # 查找特定类型文件内容
  python3 file_finder_replacer.py -s db_host -e .php,.py
  
  # 查找并交互式替换内容和重命名
  python3 file_finder_replacer.py -s old_text -r new_text
  
  # 查找并自动替换内容和重命名
  python3 file_finder_replacer.py -s old_text -r new_text -a
  
  # 在指定目录中查找
  python3 file_finder_replacer.py -d /path/to/dir -s db_host
        """
    )
    parser.add_argument('-d', '--dir', default='.', help='搜索目录 (默认: 当前目录)')
    parser.add_argument('-s', '--search', required=True, help='要搜索的文本（默认按字面匹配）')
    parser.add_argument('-r', '--replace', help='要替换为的文本')
    parser.add_argument('-e', '--extensions', help='文件扩展名过滤 (如: .py,.txt)')
    parser.add_argument('-o', '--output', action='store_true', help='保存搜索结果到文本文件')
    parser.add_argument('--json-out', help='将搜索结果保存为 JSON 文件到指定路径')
    parser.add_argument('-a', '--auto', action='store_true', help='自动替换/重命名模式（无需确认）')
    parser.add_argument('-i', '--interactive', action='store_true', help='交互式模式（默认）')
    parser.add_argument('--regex', action='store_true', help='将搜索串视为正则表达式')
    parser.add_argument('--ignore-case', action='store_true', help='忽略大小写')
    parser.add_argument('--word', action='store_true', help='整词匹配（与 --regex 结合时为模式两侧加 \\b）')
    parser.add_argument('--include-glob', help='仅包含匹配这些glob的路径（逗号分隔）')
    parser.add_argument('--exclude-glob', help='排除匹配这些glob的路径（逗号分隔）')
    parser.add_argument('--ignore-dirs', help='跳过这些目录名（glob，逗号分隔），例如: .git,node_modules,venv')
    parser.add_argument('--max-size', type=str, default='5M', help='最大文件大小，默认 5M，支持 K/M/G 后缀')
    parser.add_argument('--follow-symlinks', action='store_true', help='跟随符号链接')
    parser.add_argument('--encodings', help='尝试的编码列表，逗号分隔，默认: utf-8,gbk,gb2312,big5,latin-1')
    parser.add_argument('--context', type=int, default=0, help='保存文本结果到文件时，包含的上下文行数')
    parser.add_argument('--threads', type=int, help='并发线程数（默认: CPU*4）')
    parser.add_argument('--dry-run', action='store_true', help='演练模式，不对磁盘进行写入')
    parser.add_argument('--backup', action='store_true', help='在写回前生成 .bak 备份文件')
    args = parser.parse_args()
    file_extensions = None
    if args.extensions:
        file_extensions = [ext.strip().lower() for ext in args.extensions.split(',')]
        file_extensions = [ext if ext.startswith('.') else f'.{ext}' for ext in file_extensions]
    include_globs = [g.strip() for g in args.include_glob.split(',')] if args.include_glob else None
    exclude_globs = [g.strip() for g in args.exclude_glob.split(',')] if args.exclude_glob else None
    ignore_dirs = [g.strip() for g in args.ignore_dirs.split(',')] if args.ignore_dirs else None
    # 解析 max-size
    size_str = args.max_size.strip().upper()
    mult = 1
    if size_str.endswith('K'):
        mult = 1024
        size_val = size_str[:-1]
    elif size_str.endswith('M'):
        mult = 1024 * 1024
        size_val = size_str[:-1]
    elif size_str.endswith('G'):
        mult = 1024 * 1024 * 1024
        size_val = size_str[:-1]
    else:
        size_val = size_str
    try:
        max_size_bytes = int(float(size_val) * mult)
    except Exception:
        max_size_bytes = 5 * 1024 * 1024

    encodings = [e.strip() for e in args.encodings.split(',')] if args.encodings else None
    threads = args.threads if args.threads and args.threads > 0 else max(4, (os.cpu_count() or 2) * 4)

    finder = FileFinderReplacer(args.dir, encodings=encodings, follow_symlinks=args.follow_symlinks)
    # 查找内容
    results = finder.find_files_with_text(
        args.search,
        file_extensions=file_extensions,
        use_regex=args.regex,
        ignore_case=args.ignore_case,
        whole_word=args.word,
        include_globs=include_globs,
        exclude_globs=exclude_globs,
        ignore_dirs=ignore_dirs,
        max_size_bytes=max_size_bytes,
        threads=threads,
    )
    # 查找文件名/文件夹名
    name_results = finder.find_names(
        args.search,
        use_regex=args.regex,
        ignore_case=args.ignore_case,
        include_globs=include_globs,
        exclude_globs=exclude_globs,
        ignore_dirs=ignore_dirs,
    )
    if not results and not name_results:
        print("没有找到包含指定文本的内容、文件名或文件夹名")
        return
    print(f"\n找到 {len(results)} 个文件内容匹配, {len(name_results)} 个文件名/文件夹名匹配:")
    for result in results:
        print(f"  [内容] {result['relative_path']} ({len(result['matches'])} 处匹配)")
    for item in name_results:
        print(f"  [{'文件夹' if item['type']=='dir' else '文件'}名] {item['rel_path']}")
    if args.output:
        finder.save_results_to_file(args.search, context=args.context)
    if args.json_out:
        finder.save_results_to_json(args.json_out)
    if args.replace:
        if args.auto:
            finder.replace_text_auto(args.search, args.replace, use_regex=args.regex, ignore_case=args.ignore_case, whole_word=args.word, backup=args.backup, dry_run=args.dry_run)
            finder.replace_names_auto(args.search, args.replace, use_regex=args.regex, ignore_case=args.ignore_case, dry_run=args.dry_run)
        else:
            finder.replace_text_interactive(args.search, args.replace, use_regex=args.regex, ignore_case=args.ignore_case, whole_word=args.word, backup=args.backup, dry_run=args.dry_run)
            finder.replace_names_interactive(args.search, args.replace, use_regex=args.regex, ignore_case=args.ignore_case, dry_run=args.dry_run)
    print("\n操作完成！")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n用户中断操作")
    except Exception as e:
        print(f"\n发生错误: {e}")
        sys.exit(1) 