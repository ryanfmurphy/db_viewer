<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select * from table where a='hello') t
";

echo "initially\ntxt = '$txt'";

list($txt,$subs) = $s->parse($txt, /*blank_out_level*/1, /*blank_out_strings*/false, /*include_subs*/true);
echo "after parse(blank_out_level=1, blank_out_strings=false, include_subs=true),\ntxt = '$txt'\n\n";

assert(
    $txt ==
"select \"a\",\"b\",\"c\"                                      
    from (                                   ) t
",
    '$txt contains query with paren content blanked out'
) or die(1);

echo "and subs = '".var_export($subs,1)."'";
assert(
    $subs ==
array (
  67 => 'select * from table where a=\'hello\'',
),
    '$subs contains a map of offset => txt for each piece taken out'
) or die(1);




# now blank_out_strings=true
list($txt,$subs) = $s->parse($txt, /*blank_out_level*/1, /*blank_out_strings*/true, /*include_subs*/true);
echo "\n\nafter parse(blank_out_level=1, blank_out_strings=true, include_subs=true),\ntxt = '$txt'\n\n";

assert(
    $txt ==
'select " "," "," "                                      
    from (                                   ) t
',
    '$txt contains query with paren content blanked out'
) or die(1);

echo "and subs = '".var_export($subs,1)."'\n\n";
assert(
    $subs ==
array (
  8 => 'a',
  12 => 'b',
  16 => 'c',
  67 => '                                   ',
),
    '$subs contains a map of offset => txt for each piece taken out'
) or die(1);



echo "PASSED!\n";

