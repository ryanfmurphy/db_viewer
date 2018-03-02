<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ -- hmm
    from (select * from table where a='hello') t
";

echo "initially txt = '$txt'";

$txt = $s->blank_out_block_comments($txt);
echo "now txt = '$txt'";

