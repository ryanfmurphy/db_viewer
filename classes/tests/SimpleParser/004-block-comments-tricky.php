<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\",'/* my harmless string */' as gotcha_field /* What comments will be removed? */
    from (select * from table where a='hello') t
";

echo "\ninitially txt = [[$txt]]";

$new_txt = $s->parse($txt);
echo "\nafter parse(), txt = [[$new_txt]]";

