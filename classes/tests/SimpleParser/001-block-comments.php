<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select * from table where a='hello') t
";

echo "initially\ntxt = '$txt'";

$txt = $s->parse($txt);
echo "after parse(),\ntxt = '$txt'";

