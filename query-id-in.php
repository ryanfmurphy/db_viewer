<?php
    # query-ids-in.php
    # ----------------
    # support endpoint for the expansion feature
    # within db-viewer

    require_once('init.php');

    $table = $_GET['table'];
    $ids = $_GET['ids'];

    #todo error checking
    #todo accept POST

    # block injection attacks using $table
    if (preg_match('/^[A-Za-z_]+$/', $table)) {

        # block injection attacks using $ids
        #todo allow non-integer expansion
        if (preg_match('/^[0-9,]+$/', $ids)) {

            # do query
            $data = Util::sql("
                select *
                from $table
                where ".$table."_id in ($ids)
            ", 'array');

            die(json_encode($data));
        }
        else { die('Invalid ids'); }
    }
    else { die('Invalid table name'); }
?>
