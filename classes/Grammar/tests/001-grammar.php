<?php
include('../Grammar.php');
$g = new Grammar(
    [
        'word' => '\b\w+\b',        
        'bird' => '\b(hummingbird|canary|bluebird|pigeon|chicken|turkey|eagle|duck)\b',        
    ],
    [
        'affirmation' => '<word> to the <bird>',
        'negation' => 'aw <bad_thing> no!',
    ]
);

$txt = '<word> to the <bird>';
$result = $g->parse_rule_text($txt);

assert(
    $result == '(\\b\\w+\\b) to the (\\b(hummingbird|canary|bluebird|pigeon|chicken|turkey|eagle|duck)\\b)',
    'parse_rule_text swaps in terminal regexes'
) or die(1);
echo "PASSED!\n";
