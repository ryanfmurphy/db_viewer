<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--']);

$txt =
"select \"a\",\"b\",\"c\",'/* my harmless string */' as gotcha_field /* What comments will be removed? */
    from (select * from table where a='hello') t -- here is a subquery
    where a='hello' -- but hopefully this doesn't cause an issue /*
    /* this is separate from the line above */
";

echo "\ninitially txt = [[\n$txt\n]]\n";

#echo "\n";
#echo "the seperate_strings() gave:\n";
#print_r($s->separate_strings($txt));

$new_txt = $s->parse($txt);
echo "\nafter parse(), txt = \n'$new_txt'\n\n";

assert(
    $new_txt ==
'select "a","b","c",\'/* my harmless string */\' as gotcha_field                                     
    from (                                   ) t                      
    where a=\'hello\'                                                
                                              
',
    'blanks out comments and paren contents'
) or die(1);
echo "PASSED!\n";

