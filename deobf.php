<?php
// Usage: php deobf.php /absolute/path/to/jiemi.php > /absolute/path/to/jiemi_dec.php
if ($argc < 2) {fwrite(STDERR, "Usage: php deobf.php <obf_file>\n"); exit(1);} 
$code = file_get_contents($argv[1]);

// 1) 提取并构建字典表（稳健解析，不依赖脆弱正则）
// 形如：$GLOBALS[_A_AA_AA_]=explode('| |@|4','H*| |@|....');
if (!preg_match('/\$GLOBALS\[_A_AA_AA_\]\s*=\s*explode\(/s', $code, $m, PREG_OFFSET_CAPTURE)) {
  fwrite(STDERR, "Dictionary not found.\n"); exit(2);
}
$pos = $m[0][1] + strlen($m[0][0]);
while ($pos < strlen($code) && ctype_space($code[$pos])) $pos++;
if ($pos >= strlen($code) || ($code[$pos] !== "'" && $code[$pos] !== '"')) { fwrite(STDERR, "Dictionary not found.\n"); exit(2); }
$q1 = $code[$pos++];
$start = $pos; $sep = '';
while ($pos < strlen($code)) {
  if ($code[$pos] === '\\') { $pos += 2; continue; }
  if ($code[$pos] === $q1) { $sep = substr($code, $start, $pos - $start); $pos++; break; }
  $pos++;
}
if ($sep === '') { fwrite(STDERR, "Dictionary not found.\n"); exit(2); }
while ($pos < strlen($code) && ctype_space($code[$pos])) $pos++;
if ($pos >= strlen($code) || $code[$pos] !== ',') { fwrite(STDERR, "Dictionary not found.\n"); exit(2); }
$pos++;
while ($pos < strlen($code) && ctype_space($code[$pos])) $pos++;
if ($pos >= strlen($code) || ($code[$pos] !== "'" && $code[$pos] !== '"')) { fwrite(STDERR, "Dictionary not found.\n"); exit(2); }
$q2 = $code[$pos++];
$start = $pos; $blob = '';
while ($pos < strlen($code)) {
  if ($code[$pos] === '\\') { $pos += 2; continue; }
  if ($code[$pos] === $q2) { $blob = substr($code, $start, $pos - $start); $pos++; break; }
  $pos++;
}
if ($blob === '') { fwrite(STDERR, "Dictionary not found.\n"); exit(2); }

$rawTokens = explode($sep, $blob);

// 0 号是 'H*'，其余尽量按十六进制解码；若解码失败则原值保留
$dict = [];
foreach ($rawTokens as $i => $t) {
  if ($i === 0) { $dict[$i] = $t; continue; }
  if (preg_match('/^[0-9A-Fa-f]+$/', $t)) {
    $decoded = @pack('H*', $t);
    $dict[$i] = ($decoded !== false ? $decoded : $t);
  } else {
    $dict[$i] = $t;
  }
}

// 安全计算纯算术下标表达式
function eval_index($expr) {
  $expr = trim($expr);
  if ($expr === '') return 0;
  if (preg_match('/^[0-9\s+\-\*\/\(\)]+$/', $expr) !== 1) {
    throw new Exception("Unsafe index expr: ".$expr);
  }
  $val = @eval('return (int)('.$expr.');');
  if ($val === null) throw new Exception("Eval failed: ".$expr);
  return (int)$val;
}

// 从字典取明文（已在构建阶段解码）
function decode_from_dict($idx) {
  global $dict;
  if (!array_key_exists($idx, $dict)) return '';
  return $dict[$idx];
}

// 2) 先解出所有 pack/调用形式为字面量
$patterns = [
  // pack($GLOBALS[..][a], $GLOBALS[..][b])
  '/pack\(\s*\$GLOBALS\[_A_AA_AA_\]\[(.*?)\]\s*,\s*\$GLOBALS\[_A_AA_AA_\]\[(.*?)\]\s*\)/s',
  // call_user_func('pack', $GLOBALS[..][a], $GLOBALS[..][b])
  '/call_user_func\(\s*[\'\"]pack[\'\"]\s*,\s*\$GLOBALS\[_A_AA_AA_\]\[(.*?)\]\s*,\s*\$GLOBALS\[_A_AA_AA_\]\[(.*?)\]\s*\)/s',
  // call_user_func_array('pack', array($GLOBALS[..][a], $GLOBALS[..][b]))
  '/call_user_func_array\(\s*[\'\"]pack[\'\"]\s*,\s*array\(\s*\$GLOBALS\[_A_AA_AA_\]\[(.*?)\]\s*,\s*\$GLOBALS\[_A_AA_AA_\]\[(.*?)\]\s*\)\s*\)/s',
];

$replacer = function($matches) {
  $a = eval_index($matches[1]);
  $b = eval_index($matches[2]);
  $str = decode_from_dict($b);
  return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $str) . "'";
};

foreach ($patterns as $pat) {
  for ($i=0; $i<8; $i++) {
    $new = preg_replace_callback($pat, $replacer, $code);
    if ($new === null || $new === $code) break;
    $code = $new;
  }
}

// 2.5) 将 ::{'method'} 与 ->{'method'} 还原为合法调用
$code = preg_replace("/::\s*\{\s*'([A-Za-z_][A-Za-z0-9_]*)'\s*\}/", '::$1', $code);
$code = preg_replace("/->\s*\{\s*'([A-Za-z_][A-Za-z0-9_]*)'\s*\}/", '->$1', $code);
$code = preg_replace("/::\s*'([A-Za-z_][A-Za-z0-9_]*)'/", '::$1', $code);
$code = preg_replace("/->\s*'([A-Za-z_][A-Za-z0-9_]*)'/", '->$1', $code);

// 3) 删除 goto 与标签、删除无意义 unset
$code = preg_replace('/\bgoto\s+\w+\s*;/', '', $code);
$code = preg_replace('/^\s*(?:[A-Za-z_][A-Za-z0-9_]*:)+/m', '', $code); // 行首标签
// 行内标签（避免匹配作用域运算符 ::）：包含 '}', ';', 空白作为前导，且单冒号，不跟第二个冒号
for ($i=0; $i<5; $i++) {
  $new = preg_replace('/([\}\{;\s])([A-Za-z_][A-Za-z0-9_]*)\s*:(?!:)/', '$1', $code);
  if ($new === null || $new === $code) break;
  $code = $new;
}
// 兜底：去除任意剩余的单冒号标签（不在 :: 场景）
$code = preg_replace('/\b([A-Za-z_][A-Za-z0-9_]*)\s*:(?!:)/', '', $code);

$code = preg_replace('/unset\([^;]*\);\s*/', '', $code);

// 3.3) 折叠空 if 紧接单语句（多轮，unicode）
for ($i=0; $i<8; $i++) {
  $before = $code;
  $code = preg_replace('/if\s*\(([^)]*)\)\s*\{\s*\}\s*(return_json\([^;]+\);)/us', 'if($1) $2', $code);
  $code = preg_replace('/if\s*\(([^)]*)\)\s*\{\s*\}\s*(showmsg\([^;]+\);)/us', 'if($1) $2', $code);
  $code = preg_replace('/if\s*\(([^)]*)\)\s*\{\s*\}\s*(\$this->_login\([^;]*\);)/us', 'if($1) $2', $code);
  $code = preg_replace('/if\s*\(([^)]*)\)\s*\{\s*\}\s*(\$this->_force_logout\([^;]*\);)/us', 'if($1) $2', $code);
  $code = preg_replace('/if\s*\(([^)]*)\)\s*\{\s*\}\s*(update::check\([^;]*\);)/us', 'if($1) $2', $code);
  // 通用折叠：空 if 后跟一条非分支语句
  $code = preg_replace('/if\s*\(([^)]*)\)\s*\{\s*\}\s*((?!(?:if|for|while|switch)\b)[A-Za-z_\$][^;]*;)/us', 'if($1) $2', $code);
  if ($before === $code) break;
}

// 3.35) 全局清理非 ASCII 变量语句（无论前面是什么字符）
for ($i=0; $i<5; $i++) {
  $before = $code;
  $code = preg_replace('/\$[^\x00-\x7F\$][^;]*;\s*/mu', '', $code);
  if ($before === $code) break;
}

// 3.4) 去除重复的连续相同调用（简单化，同一语句连在一起时去重）
$code = preg_replace('/(\$this->_force_logout\([^;]*\);)\s*\1/', '$1', $code);

// 3.45) 修正 URL 协议前缀（避免变成 //www）
$code = str_replace('window.top.location="//www.yzmcms.com', 'window.top.location="http://www.yzmcms.com', $code);

// 3.5) 删除顶层的字典与常量定义
$code = preg_replace('/if\s*\(!defined\(\'_A_AA_AA_\'\)\)\s*define\([^;]*\);/s', '', $code);
$code = preg_replace('/\$GLOBALS\[_A_AA_AA_\]\s*=\s*explode\([^;]*\);/s', '', $code);

// 3.6) 规整分号与空白
$code = preg_replace('/;\s*;+/m', ';', $code);
$code = preg_replace("/\n{3,}/", "\n\n", $code);
$code = preg_replace("/\{\s*\}/", "{}", $code);

// 4) 可选：把 base64_decode('...') 直接内联（如需）
// $code = preg_replace_callback('/base64_decode\(\s*\'([A-Za-z0-9+\/=]+)\'\s*\)/', function($m){return "'".base64_decode($m[1])."'";}, $code);

// 5) 输出解密后的代码
echo $code;