<?php
include('../../classes/SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select * from table where a='hello') t
";

echo "initially\ntxt = '$txt'";

list($new_txt,$subs) = $s->parse($txt,
    /*blank_out_level*/1, /*blank_out_strings*/false, /*blank_out_comments*/true,
    /*include_subs*/true
);
echo "after parse(blank_out_level=1, blank_out_strings=false, include_subs=true),\nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
"select \"a\",\"b\",\"c\"                                      
    from (                                   ) t
",
    '$txt contains query with paren content blanked out'
) or die(1);

echo "and subs = '".var_export($subs,1)."'";
assert(
    $subs ==
        array (
          19 => '/* I only want these fields */',
          50 => '// hmm',
          67 => 'select * from table where a=\'hello\'',
        ),
    '$subs contains a map of offset => txt for each piece taken out'
) or die(1);




# now blank_out_strings=true
list($new_txt,$subs) = $s->parse($txt,
    /*blank_out_level*/1, /*blank_out_strings*/true, /*blank_out_comments*/true,
    /*include_subs*/true
);
echo "\n\nafter parse(blank_out_level=1, blank_out_strings=true, include_subs=true),\nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
'select " "," "," "                                      
    from (                                   ) t
',
    '$new_txt contains query with paren content blanked out'
) or die(1);

echo "and subs = '".var_export($subs,1)."'\n\n";
assert(
    $subs ==
        array (
          8 => 'a',
          12 => 'b',
          16 => 'c',
          19 => '/* I only want these fields */',
          50 => '// hmm',
          67 => 'select * from table where a=\'hello\'',
        ),
    '$subs contains a map of offset => txt for each piece taken out'
) or die(1);

$new_txt = $s->put_subs_back_in_txt($new_txt, $subs);
echo "after put_subs_back_in_txt, new_txt = \n'$new_txt'\n\ncompare to original txt = \n'$txt'\n\n";
assert(
    $new_txt == $txt,
    'put_subs_back_in_txt restores new_txt to how txt was before'
) or die(1);

echo "PASSED!\n";

