<?php
    # query-ids-in.php
    # ----------------
    # support endpoint for the expansion "join-splice" feature in db_viewer

	{
		require_once('init.php');
		do_log(date('c') . " - db_viewer.query_ids_in received a request\n");
        do_log(print_r($_POST,1));
		$ids = $_POST['ids'];
	}

	# take join_field, figure out table
	if (isset($_POST['join_field'])) {
		list($table, $joinField) = DbViewer::choose_table_and_field($_POST['join_field']);

        do_log("  table = $table\n");
        do_log("  joinField = $joinField\n");

		#todo error checking
		#todo accept POST

		# block injection attacks using $ids
		#todo allow non-integer expansion
		#if (preg_match('/^[0-9,]+$/', $ids)) {
        if (is_array($ids)) {
            $ids_str = DbViewer::val_list_str($ids);

			# do query
			$query = "
				select *
				from $table
				where $joinField in ($ids_str)
			";
            #die($query);
			$rows = Util::sql($query, 'array');
            do_log("\n  query = $query\n");

            if (is_array($rows)) {
                $data = DbViewer::keyRowsByField($rows, $joinField);
                die(json_encode($data));
            }
            else {
                DbViewer::outputDbError($db);
                echo "\n\nquery = $query\n";
                die();
            }
		}
		else { die('Invalid ids'); }
	}
	else { die('No join field'); }
?>
