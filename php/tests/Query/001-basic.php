<?php
    include('../../classes/Query.php');


    $q = new Query('select * from note');
    assert($q->sql_from_parts() == 'select * from note',
        'simple Query returns same sql txt from sql_from_parts()'
    ) or die(1);
    echo "PASSED TEST 1\n\n";

    $q = new Query('select * from note limit 100 offset 50');
    assert($q->sql_from_parts() == 'select * from note limit 100 offset 50',
        'Query with limit and offset returns same sql txt from sql_from_parts()'
    ) or die(1);
    echo "PASSED TEST 2a\n\n";
    assert($q->limit == 100,
        'Inferred limit from query correctly'
    ) or die(1);
    echo "PASSED TEST 2b\n\n";
    assert($q->offset == 50,
        'Inferred offset from query correctly'
    ) or die(1);
    echo "PASSED TEST 2c\n\n";
    assert($q->body == 'select * from note',
        'Body has limit and offset truncated'
    ) or die(1);
    echo "PASSED TEST 2d\n\n";

    echo "ALL TESTS PASSED!\n\n";
