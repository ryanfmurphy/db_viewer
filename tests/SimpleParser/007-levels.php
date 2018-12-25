<?php
include('../../classes/SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--']);

$txt = "select * from (select * from (select * from note) n2) n order by time_added desc";

echo "\ninitially txt = [[\n$txt\n]]\n";

$new_txt = $s->parse($txt, 1); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 1), txt = [[\n$new_txt\n]]\n";
assert(
    $new_txt == 
      "select * from (                                     ) n order by time_added desc",
    'parse($txt,1) wipes out contents of outer parens'
) or die(1);

$new_txt = $s->parse($txt, 2); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 2), txt = [[\n$new_txt\n]]\n";

assert(
    $new_txt == 
      "select * from (select * from (                  ) n2) n order by time_added desc",
    'parse($txt,2) wipes out contents of inner parens'
) or die(1);
echo "PASSED!\n";

