<?php
include('../../SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);

$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select * from table where a='hello') t
    order by time_added desc
";

echo "initially\ntxt = '$txt'";

$pattern = '/order by time_added desc\s*$/';
$matched = $s->preg_match_parse(
    $pattern, $txt, $matches,
    /*blank_out_level*/1, /*blank_out_strings*/false, /*blank_out_comments=*/false,
    /*include_subs*/true
);
echo "after preg_match_parse w pattern '$pattern', matches = '\n".var_export($matches,1)."\n\n";

assert($matched, 'matched') or die(1);
assert(
    $matches ==
        array (
          0 => "order by time_added desc\n",
        ),
    'matches are as expected'
) or die(1);

echo "PASSED!\n";

