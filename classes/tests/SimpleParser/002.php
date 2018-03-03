<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--']);

$txt =
"select \"a\",\"b\",\"c\", 'tricky /* dang */ string' as whoa -- line comment 1
    from (select * from table where a='hello') t -- line comment 2
    group by \"c\" -- line comment 3";

echo "initially\ntxt = '$txt'\n";

$txt = $s->blank_out_comments($txt);
echo "after blank_out_comments(),\ntxt = '$txt'\n";

