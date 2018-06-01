<?php
include('../Grammar.php');
$g = new Grammar(
    [
        'word' => '\b\w+\b',        
        'bird' => '\b(hummingbird|canary|bluebird|pigeon|chicken|turkey|eagle|duck|penguin)\b',        
    ],
    [
        'affirmation' => '<word> to the <bird>',
        'negation' => 'aw <bad_thing> no!',
    ]
);

$txt = '<word> to the <bird>';
$regex = $g->parse_rule_text($txt);

assert(
    $regex == '(\\b\\w+\\b) to the (\\b(hummingbird|canary|bluebird|pigeon|chicken|turkey|eagle|duck|penguin)\\b)',
    'parse_rule_text swaps in terminal regexes'
) or die(1);
echo "1 PASSED!\n";

assert(
    preg_match("/^$regex$/", "sudafed to the canary"),
    'generated regex matches example sentence that conforms to rule'
) or die(1);
echo "2 PASSED!\n";

assert(
    preg_match("/^$regex$/", "umbrellas to the hummingbird"),
    'generated regex matches example sentence that conforms to rule'
) or die(1);
echo "3 PASSED!\n";

assert(
    !preg_match("/^$regex$/", "martinis with the penguin"),
    'generated regex doesn\'t match example sentence that doesn\'t conform to rule'
) or die(1);
echo "4 PASSED!\n";

assert(
    !preg_match("/^$regex$/", "props to the aardvark"),
    'generated regex doesn\'t match example sentence that doesn\'t conform to rule'
) or die(1);
echo "5 PASSED!\n";

