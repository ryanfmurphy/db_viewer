<?php
include('../Grammar.php');
$rules = [
    'select_stmt' => 'select <field_name>, <field_name>, <field_name> <from_clause> <where_clause>',
    #'field_list' => '<field_name>, <field>+',
    #'field_def' => Any('"a"','"b"','"c"'),
];
$g = new Grammar(
    [
        'field_name' => '\b\w+\b',
        'from_clause' => '\bfrom \w+\b',
        'where_clause' => '\bwhere \b\w+\b = \b\w+\b',
    ],
    $rules
);

$txt = $rules['select_stmt'];
$regex = $g->sub_regexes_into_rule($txt);

assert(
    preg_match(
        "/^$regex$/",
        "select name, ball_size, popularity from game where ball_size = small"
    ),
    "pseudo-SQL example passed example grammar (just regexes, no recursion)"    
) or die(1);
echo "1 PASSED!\n";
