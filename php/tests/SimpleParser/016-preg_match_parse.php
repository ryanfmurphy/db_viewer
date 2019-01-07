<?php
include('../../classes/SimpleParser.php');
$s = new SimpleParser(['line_comment'=>'//']);




$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select * from table where a='hello') t
    order by time_added desc
    limit 100
    offset 50
";

echo "initially\ntxt = '$txt'\n\n";

$matches = [];
$matched = $s->preg_match_parse(
    "/^\s*select\s+(?P<select_fields>.*)"
        ."\s+from\s+(?P<from>.*)"
        ."(?:\s+where\s+(?P<where>.*))?"
        ."\s+order by\s+(?P<order_by>.*)\s+limit(?P<limit>.*)\s+offset(?P<offset>.*)\s*$/",
    $txt,
    $matches,
    /*blank_out_level*/1, /*blank_out_strings*/true, /*blank_out_comments=*/true
);

assert($matched == true, 'preg_match_parse returns true if pattern matches')
    or die(1);

echo "PASSED 1...\n\n";

assert(
    $matches ==
        array (
          0 => 'select "a","b","c" /* I only want these fields */ // hmm
    from (select * from table where a=\'hello\') t
    order by time_added desc
    limit 100
    offset 50
',
          'select_fields' => '"a","b","c" /* I only want these fields */ // hmm',
          1 => '"a","b","c" /* I only want these fields */ // hmm',
          'from' => '(select * from table where a=\'hello\') t',
          2 => '(select * from table where a=\'hello\') t',
          'where' => '',
          3 => '',
          'order_by' => 'time_added desc',
          4 => 'time_added desc',
          'limit' => ' 100',
          5 => ' 100',
          'offset' => ' 50',
          6 => ' 50',
        ),
    'successfully found all matches in regex'
) or die(1);

echo "PASSED 2...\n\n";

echo "ALL TESTS PASSED!\n\n";
