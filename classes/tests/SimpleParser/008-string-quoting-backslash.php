<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--','str_escape_mode'=>'backslash']);

$str0 = "'simple single-quoted string'";
$str1 = "\"simple double-quoted string\"";
$str2 = "'this is db_viewer\'s string'";
$str3 = "\"this \\\"here\\\" is another string\"";
$txt = "$str0 $str1 $str2 $str3";

echo "\ninitially txt = [[\n$txt\n]]\n";

$new_txt = $s->parse($str0, 1, true);
echo "\nafter parse(\$str0, 1, true), txt = [[\n$new_txt\n]]\n";
assert($new_txt == "'".str_repeat(' ',strlen($str0)-2)."'", "blanks out str0") or die(1);

$new_txt = $s->parse($str1, 1, true);
echo "\nafter parse(\$str1, 1, true), txt = [[\n$new_txt\n]]\n";
assert($new_txt == '"'.str_repeat(' ',strlen($str1)-2).'"', "blanks out str1") or die(1);

$new_txt = $s->parse($str2, 1, true);
echo "\nafter parse(\$str2, 1, true), txt = [[\n$new_txt\n]]\n";
assert($new_txt == "'".str_repeat(' ',strlen($str2)-2)."'", "blanks out str2") or die(1);

$new_txt = $s->parse($str3, 1, true);
echo "\nafter parse(\$str3, 1, true), txt = [[\n$new_txt\n]]\n";
assert($new_txt == '"'.str_repeat(' ',strlen($str3)-2).'"', "blanks out str3") or die(1);

$new_txt = $s->parse($txt); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 1), txt = [[\n$new_txt\n]]\n";
assert($new_txt == $txt, "doesn't do anything if blank_out_strings=false") or die(1);

# option to blank out the strings so we can actually confirm that it's working
$expected_txt = "'                           ' \"                           \" '                           ' \"                               \"";
$new_txt = $s->parse($txt, 1, true); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 1, true), txt = [[\n$new_txt\n]]\n";
echo "\n                    expected_txt = [[\n$expected_txt\n]]\n";
assert(
    $new_txt == $expected_txt,
    "blanks out all strings properly"
) or die(1);
echo "PASSED!\n";

