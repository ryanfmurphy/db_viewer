<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--']);

$txt = "select * from note order by time_added desc";

echo "\ninitially txt = [[\n$txt\n]]\n";

$new_txt = $s->parse($txt).'a';
echo "\nafter parse(), txt = [[\n$new_txt\n]]\n";

assert("$new_txt == $txt");

