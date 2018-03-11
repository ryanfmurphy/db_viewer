<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--']);

$txt =
"select \"a\",\"b\",\"c\" -- line comment 1
    from (select * from table where a='hello') t -- line comment 2
    -- line comment 3";

echo "initially\ntxt = '$txt'\n";

$txt = $s->parse($txt);
echo "after parse(),\ntxt = '$txt'\n";

assert(
    $txt ==
'select "a","b","c"                  
    from (                                   ) t                  
                     ',
    'erases comments and clears out paren contents'
) or die(1);
echo "PASSED!\n";

