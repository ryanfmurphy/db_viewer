<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\",'/* my harmless string */' as gotcha_field /* What comments will be removed? */
    from (select * from table where a='hello') t
";

echo "\ninitially txt = [[
$txt
]]\n";

$new_txt = $s->parse($txt);
echo "\nafter parse(), new_txt = [[
'$new_txt'
]]\n";

assert(
    $new_txt ==
'select "a","b","c",\'/* my harmless string */\' as gotcha_field                                     
    from (                                   ) t
',
    'erases comments and blanks out paren contents'
) or die(1);
echo "PASSED!\n";

