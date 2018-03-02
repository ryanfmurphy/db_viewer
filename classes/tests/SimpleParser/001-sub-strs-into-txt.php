<?php
# run in /classes dir
include('SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

list($txt,$strs) = $s->separate(
"select \"a\",\"b\",\"c\" /* I only want these fields */ -- hmm
    from (select * from table where a='hello') t
"
);
echo $txt;
print_r($strs);

$y = $s->sub_strs_into_txt($txt,$strs);
echo "$y";

