<?php

#todo #fixme - this tests functionality that is not yet committed to the repo
# namely, the "archive" special op, which will likely be added to "extras"
# ~ 2018-11-02 RFM

$cur_view='test';
include('../../../includes/init.php');

echo "Testing the 'archive' special_op\n";

$archive_url = Config::$config['hostname']
                . Config::$config['uri_trunk']
                . Config::$config['crud_api_uri'];
echo "  POST to do archive, url='$archive_url'\n";

#todo #fixme - had to use entity bc row disappears from todo
$table = 'mev';

#todo #fixme can't currently count on this row to exist
$test_todo_id = '2c9fc972-de89-11e8-918b-8f31154fc975'; #'5140bbce-de89-11e8-89fa-57713b2e4cc6';
$test_mev_id = 'fe47b354-3db3-11e7-bc90-3303542c2910';
$test_row_id = $test_mev_id;

$update_result = Db::update_row('entity', [
    'is_archived' => 'false', 
    'where_clauses' => [ 'id' => $test_row_id ],
]);
#todo #fixme get update_row to give better update_result, and test it

$row = Db::get1($table, [ 'id' => $test_row_id ]);
#echo "row before archive POST, but after initial is_archived=0 update = ".print_r($row,1);

assert( isset($row['is_archived']) && $row['is_archived'] == false,
        "before archive POST, row must not be archived (because we updated it)"
) or die(1);

$result = Curl::get($archive_url,
    [
        'action' => 'special_op',
        #todo #fixme can't currently count on 0,1 to be archive
        'col_idx' => 0,
        'op_idx' => 1,
        'table' => $table,
        'primary_key' => $test_row_id,
    ]
);

#echo "result = '$result'";

$row = Db::get1($table,
    [ 'id' => $test_row_id ]);
#echo "row after = ".print_r($row,1);

assert( isset($row['is_archived']) && $row['is_archived'] == true,
        "after archive POST, row must be archived"
) or die(1);
echo "PASSED!\n";

