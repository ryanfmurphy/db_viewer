<?php
# adds the option of alternation within a rule via [array]

# (not yet) # note - this uses nonterminals, and is attempting "safety" through the brace/paren mask offered by SimpleParser

include('../Grammar.php');
$rules = [
    'select_stmt' => 'select <field_name>, <field_name>, <field_name> <from_clause> <where_clause>',
    'from_clause' => [
        '\bfrom <table_name>',
        '\bfrom <table_name> <table_name>',
        '\bfrom <table_name> as <table_name>',
        #'\bfrom (<select_stmt>)', # recursive call, we'll need the insulation provided by the ()-mask
    ],
    'where_clause' => '\bwhere <field_name> = <value>',
];
$g = new Grammar(
    [
        'field_name' => '\b\w+\b',
        'table_name' => '\b\w+\b',
        'value' => '\b\d+\b',
    ],
    $rules
);
$sp = new SimpleParser();

$txt = $rules['select_stmt'];
$regex = $g->sub_regexes_into_rule($txt);
echo "regex = $regex\n";

assert(
    $sp->preg_match_parse(
        "/^$regex$/",
        "select name, ball_size, popularity from my_table where ball_size = 5",
        $matches
    ),
    "pseudo-SQL example passed example grammar (just regexes, no recursion)"    
) or die(1);
echo "1 PASSED!\n";
