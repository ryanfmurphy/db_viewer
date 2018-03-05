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



echo "ALL TESTS PASSED!\n";

