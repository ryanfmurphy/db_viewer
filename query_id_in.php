<?php
    # query-ids-in.php
    # ----------------
    # support endpoint for the expansion "join-splice" feature
    # within db-viewer

    #todo change interface to take join_field and figure out table

	$cmp = class_exists('Util'); #todo #fixme no val_list_str function
	if (!$cmp) {
		require_once('init.php');
	}

    $ids = $_POST['ids'];
	if (isset($_POST['join_field'])) {

		list($table, $joinField) = Util::choose_table_and_field($_POST['join_field']);

		#todo error checking
		#todo accept POST

		# block injection attacks using $ids
		#todo allow non-integer expansion
		#if (preg_match('/^[0-9,]+$/', $ids)) {
        if (is_array($ids)) {
            $ids_str = Util::val_list_str($ids);

			# do query
			$query = "
				select *
				from $table
				where $joinField in ($ids_str)
			";
            #die($query);
			$rows = Util::sql($query, 'array');

			$data = array();
			foreach ($rows as $row) {
				$idVal = $row[$joinField];
				$data[$idVal] = $row;
			}

			die(json_encode($data));
		}
		else { die('Invalid ids'); }
	}
	else { die('No join field'); }
?>
