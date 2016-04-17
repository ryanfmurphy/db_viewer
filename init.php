<?php
    require_once('db-config.php');
    $db = mysqli_connect(
        $db_host, $db_user, $db_password,
        $db_name #, $db_port
    );

    class Util {

        public static function sql($query, $returnType='array') {
            global $db;
            $result = mysqli_query($db, $query);
            $rows = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
            return $rows;
        }

		public static function choose_table_and_field($field_name) {
			$suffix = substr($field_name, -3);
			if ($suffix == '_id') {
				$root = substr($field_name, 0, -3);

				$possible_tables = Util::sqlTables();

				# if it's not as simple as `contractor_id`
				# try to find a table that this id might be pointing to
				# e.g. `parent_contractor_id` also links to contractor
				if (!isset($possible_tables[$root])) {
					# loop looking for table ending in $table
					foreach (array_keys($possible_tables) as $this_table_name) {
						if (Util::endsWith($this_table_name, $root)) {
							$root = $this_table_name;
							$field_name = $this_table_name."_id";
							break;
						}
					}
				}

				#todo maybe error out if didn't match a table?
				return array($root, $field_name);
			}
			else { # this else not used yet
				$table = $field_name;
				$field_name = 'name';
				return array($table, $field_name);
			}
		}

		public static function endsWith($needle,$haystack) {
			$len = strlen($needle);
			$LEN = strlen($haystack);
			return substr($haystack, $LEN - $len) == $needle;
		}

		# result is keyed by table_name, all vals are 1
		# for fast lookup, can use e.g.:
			# $tables = Util::sqlTables();
			# if (isset($tables['contractor'])) {
			#	...
		public static function sqlTables() {
			$rows = Util::sql('show tables');
			$tables = array();
			foreach ($rows as $row) {
				$table = current($row);
				$tables[$table] = 1;
			}
			return $tables;
		}
    }

    #$jquery_url = "/js/jquery.min.js"; #todo #fixme give cdn url by default
?>
