<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'--','str_escape_mode'=>'both']);

$txt = "
    select *,\"abra_cadabra\",'testing''s great fun' as \"test_field\" from
        (
            select 'here\\'s the thing'
            from (select * from note) n2
            where txt like '%you don''t know what you don\\'t know%'
        ) n
        order by time_added desc
";

echo "\ninitially txt = [[\n'$txt'\n]]\n";

$new_txt = $s->parse($txt, 1, true); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 1, true), txt = [[\n'$new_txt'\n]]\n";
assert(
    $new_txt == 
'
    select *,"            ",\'                    \' as "          " from
        (                                                                                                                                                             ) n
        order by time_added desc
',
    'parse($txt,1,true) correctly blanks out the strings, even w escaped quotes'
) or die(1);

$new_txt = $s->parse($txt, 2, true); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 2), txt = [[\n'$new_txt'\n]]\n";

assert(
    $new_txt == 
'
    select *,"            ",\'                    \' as "          " from
        (
            select \'                 \'
            from (                  ) n2
            where txt like \'                                      \'
        ) n
        order by time_added desc
',
    'parse($txt,2,true) correctly blanks out the strings that use both "backslash" and "double" escape modes'
) or die(1);




$txt2 = "
    select  *, \"abra_cadabra\",\"\"\"horrible\"\"_field_name\",
            'testing''s great fun' as \"they_say\",
            \"another \"\"stupid\"\" \\\"barely legal\\\" field name\"
        from
        (
            select 'here\\'s the thing', 'I''s just testing the string''s', '\"escape\" \\'modes'' \\'that\\' ''is'''
            from (
                select * from note
            ) n2
            where txt like '%you don''t know''s what you don\\'t know''s%'
        ) n
        order by time_added desc
";

echo "\ninitially txt2 = [[\n'$txt2'\n]]\n";

$new_txt = $s->parse($txt2, 2, true); # blank out level 1 and deeper
echo "\nafter parse(\$txt2, 2, true), new_txt = [[\n'$new_txt'\n]]\n";
assert(
    $new_txt == 
'
    select  *, "            ","                       ",
            \'                    \' as "        ",
            "                                              "
        from
        (
            select \'                 \', \'                               \', \'                                  \'
            from (                                                ) n2
            where txt like \'                                            \'
        ) n
        order by time_added desc
',
    'parse($txt2,2,true) correctly blanks out the strings that use both "backslash" and "double" escape modes'
) or die(1);





$txt = "
    select *,\"abra_(cadabra\",'testing''s great) fun' as \"test_field)\" from
        (
            select ')here\\'s the (thing'
            from (
                select * from note where name = ')'
            ) n2
            where txt like '%you don''t (know what you don\\'t know%'
        ) n
        order by time_added desc
";

echo "\ninitially txt = [[\n'$txt'\n]]\n";

$new_txt = $s->parse($txt, 3, true); # blank out level 1 and deeper
echo "\nafter parse(\$txt, 3, true), txt = [[\n'$new_txt'\n]]\n";
assert(
    $new_txt == 
'
    select *,"             ",\'                     \' as "           " from
        (
            select \'                   \'
            from (
                select * from note where name = \' \'
            ) n2
            where txt like \'                                       \'
        ) n
        order by time_added desc
',
    'parse($txt,3,true) correctly blanks out the strings, even w escaped quotes'
) or die(1);

echo "PASSED!\n";

