<?php
include('../../classes/SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--']);

$txt = "select * from note order by time_added desc";

echo "\ninitially txt = [[\n$txt\n]]\n";

$new_txt = $s->parse($txt);
echo "\nafter parse(), txt = [[\n$new_txt\n]]\n";

assert('$new_txt == $txt') or die(1);
echo "PASSED!\n";

