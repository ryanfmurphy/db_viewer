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
$new_txt = $s->preg_replace_callback_parse(
    $pattern,
    function($match){
        return "order by lower(name)";
    },
    $txt,
    /*blank_out_level*/1, /*blank_out_strings*/false, /*blank_out_comments=*/false
);
echo "after preg_replace_callback_parse w pattern '$pattern', \nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select * from table where a='hello') t
    order by lower(name)",
    'replace successfully completed'
) or die(1);

echo "PASSED 1...\n\n";





# test 2
$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select \"a\",\"b\",\"c\" from table where a='hello') t
    order by time_added desc
";

echo "initially\ntxt = '$txt'";

$pattern = '/\\"([abc])\\"/';
$new_txt = $s->preg_replace_callback_parse(
    $pattern,
    function($match){
        $new_letter = strtoupper($match[1]);
        return "\"$new_letter\"";
    },
    $txt,
    /*blank_out_level*/1, /*blank_out_strings*/false, /*blank_out_comments=*/false
);
echo "after preg_replace_callback_parse w pattern '$pattern', \nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
'select "A","B","C" /* I only want these fields */ // hmm
    from (select "a","b","c" from table where a=\'hello\') t
    order by time_added desc
',
    'replace successfully completed'
) or die(1);
echo "PASSED 2...\n\n";






# test 3 - displacing characters and subbing back properly
$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select \"a\",\"b\",\"c\" from table where a='hello') t
    order by time_added desc
";

echo "initially\ntxt = '$txt'";

$pattern = '/\\"([abc])\\"/';
$new_txt = $s->preg_replace_callback_parse(
    $pattern,
    function($match){
        $new_letter = strtoupper($match[1]);
        return "\"field_$new_letter\"";
    },
    $txt,
    /*blank_out_level*/1, /*blank_out_strings*/false, /*blank_out_comments=*/false
);
echo "after preg_replace_callback_parse w pattern '$pattern', \nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
'select "field_A","field_B","field_C" /* I only want these fields */ // hmm
    from (select "a","b","c" from table where a=\'hello\') t
    order by time_added desc
',
    'replace successfully completed, subbed back in taking into account displacement'
) or die(1);
echo "PASSED 3...\n\n";




# test 4 - add a new table to the FROM list
$txt =
"select \"a\",\"b\",\"c\" /* I only want these fields */ // hmm
    from (select \"a\",\"b\",\"c\" from table where a='hello') t
    order by time_added desc
";

echo "initially\ntxt = '$txt'";

$pattern = '/from \(\s*\) \w+\b/';
$new_txt = $s->preg_replace_callback_parse(
    $pattern,
    function($match){
        return "$match[0], new_table";
    },
    $txt,
    /*blank_out_level*/1, /*blank_out_strings*/true, /*blank_out_comments=*/true
);
echo "after preg_replace_callback_parse w pattern '$pattern', \nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
'select "a","b","c" /* I only want these fields */ // hmm
    from (select "a","b","c" from table where a=\'hello\') t, new_table
    order by time_added desc
',
    'replace successfully completed, subbed back in taking into account displacement'
) or die(1);
echo "PASSED 4...\n\n";


# test 5 - add a where clause to the last one
$pattern = '/from (?:\(\s*\)\s*)?\w*(?:,\s*(?:\(\s*\)\s*)?\w*)*/';
$new_txt = $s->preg_replace_callback_parse(
    $pattern,
    function($match){
        return "$match[0] where new_table.t_id = t.id";
    },
    $new_txt,
    /*blank_out_level*/1, /*blank_out_strings*/true, /*blank_out_comments=*/true
);
echo "after preg_replace_callback_parse w pattern '$pattern' (on new_txt!) \nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
'select "a","b","c" /* I only want these fields */ // hmm
    from (select "a","b","c" from table where a=\'hello\') t, new_table where new_table.t_id = t.id
    order by time_added desc
',
    'replace successfully completed, subbed back in taking into account displacement'
) or die(1);
echo "PASSED 5...\n\n";



# test 6 - add a table BEFORE the (), using a pattern that needs it
$from_table = '(?:\(\s*\)\s*)?\w*\b';
$pattern = "/from ($from_table)/";
$new_txt = $s->preg_replace_callback_parse(
    $pattern,
    function($match){
        return "from new_first_table, $match[1]";
    },
    $new_txt,
    /*blank_out_level*/1, /*blank_out_strings*/true, /*blank_out_comments=*/true,
    /*shift_overlapping_sub*/true
);
echo "after preg_replace_callback_parse w pattern '$pattern' (on new_txt!) \nnew_txt = '$new_txt'\n\n";

assert(
    $new_txt ==
'select "a","b","c" /* I only want these fields */ // hmm
    from new_first_table, (select "a","b","c" from table where a=\'hello\') t, new_table where new_table.t_id = t.id
    order by time_added desc
',
    'replace successfully completed, displacing the overlapping sub (...) over'
) or die(1);
echo "PASSED 6...\n\n";




echo "ALL TESTS PASSED!\n";

