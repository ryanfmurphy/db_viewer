<?php
    # query-ids-in.php
    # ----------------
    # support endpoint for the expansion "join-splice" feature in db_viewer

    #todo change interface to take join_field and figure out table

    {
        require_once('init.php');
        do_log(date('c') . " - db_viewer.query_ids_in received a request\n");
        $ids = $_POST['ids'];
    }

    do_log("  do we have a join_field?\n");
    if (isset($_POST['join_field'])) {
        do_log("    yes: ".$_POST['join_field']."\n");

        do_log("    doing choose_table_and_field\n");
        # take join_field, figure out table
        list($table, $joinField) = DbUtil::choose_table_and_field($_POST['join_field']);
        do_log("      now joinField = $joinField\n");
        do_log("      table = $table\n");

        #todo error checking
        #todo accept POST

        # block injection attacks using $ids
        #todo allow non-integer expansion
        #if (preg_match('/^[0-9,]+$/', $ids))
        do_log("      is ids an array?\n");
        if (is_array($ids)) {
            do_log("        yes: ".print_r($ids,1)."\n");
            $ids_str = DbUtil::val_list_str($ids);
            do_log("        ids_str = $ids_str\n");

            # do query
            $query = "
                select *
                from $table
                where $joinField in ($ids_str)
            ";
            #die($query);
            do_log("        query = $query\n");
            $rows = Util::sql($query, 'array');

            do_log("        got array of rows?\n");
            if (is_array($rows)) {
                do_log("          got array of rows. doing keyRowsByField\n");
                $data = DbUtil::keyRowsByField($rows, $joinField);
                die(json_encode($data));
            }
            else {
                do_log("          nope. outputDbError and echo query\n");
                DbViewer::outputDbError($db);
                echo "\n\nquery = $query\n";
                die();
            }
        }
        else {
            do_log("    nope. dying\n");
            die('Invalid ids');
        }
    }
    else {
        do_log("    nope. dying\n");
        die('No join field');
    }
?>
