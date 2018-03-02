<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\",'/* my harmless string */' as gotcha_field /* What comments will be removed? */
    from (select * from table where a='hello') t
";

echo "\ninitially txt = [[$txt]]";

$new_txt = $s->blank_out_comments($txt);
echo "\nafter blank_out_comments(), txt = [[$new_txt]]";

echo "\n";
echo "It didn't trip over the /* */ in the string because\n";
echo "first it temporarily blanked out the strings:\n\n";

echo $s->separate_strings($txt)[0]."\n";

echo "\nso therefore it gets [[$new_txt]]\n";

