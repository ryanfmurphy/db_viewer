<?php
    $PDO = true; #todo non-PDO is #deprecated, prune

    require_once('db-config.php');
    $db = $PDO
            ? new PDO(
                "$db_type:host=$db_host;dbname=$db_name",
                $db_user, $db_password
            )
            : mysqli_connect(
                $db_host, $db_user, $db_password,
                $db_name
            );

    class Util {

        public static function sql($query, $returnType='array') {
            global $db, $PDO;
            if ($PDO) {
                return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                $result = mysqli_query($db, $query);
                $rows = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                }
                return $rows;
            }
        }

        public static function has_valid_join_field_suffix($field_name) {
            $suffix = substr($field_name, -3);
            if ($suffix === '_id') {
                return $suffix;
            }
            else {
                $suffix = substr($field_name, -4);
                if ($suffix === '_iid') {
                    return $suffix;
                }
                else {
                    return false;
                }
            }
        }

        # get table schema etc
        public static function full_tablename($tablename) {
            global $db_type;

            if ($db_type == 'pgsql') {

                $table_schemas = array( #todo get from database
                    'company' => 'market',
                    'quote' => 'market',
                );

                if (isset($table_schemas[$tablename])) {
                    $schema = $table_schemas[$tablename];
                    return "$schema.$tablename";
                }
                else {
                    return $tablename;
                }
            }
            else {
                return $tablename;
            }
        }

		public static function choose_table_and_field($field_name) {
            global $id_mode;

			$suffix = self::has_valid_join_field_suffix($field_name);
			if ($suffix) {
				$root = substr($field_name, 0, -strlen($suffix));

                #todo move into full_tablename()
				{   # if it's not as simple as `contractor_id`
                    # try to find a table that this id might be pointing to
                    # e.g. `parent_contractor_id` also links to contractor
                    $possible_tables = Util::sqlTables();

                    if (!isset($possible_tables[$root])) {
                        # loop looking for table ending in $table
                        foreach (array_keys($possible_tables) as $this_table_name) {
                            if (Util::endsWith($this_table_name, $root)) {
                                $root = $this_table_name;
                                $field_name = $this_table_name.$suffix;
                                break;
                            }
                        }
                    }
                }

                $root = self::full_tablename($root);

				#todo maybe error out if didn't match a table?
                if ($id_mode == 'id_only') {
                    $field_name = ltrim($suffix,'_');
                }
				return array($root, $field_name);
			}
			else { # this else not used yet
				$table = self::full_tablename($field_name);
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
            global $db_type;

            if ($db_type == 'mysql') {
                $rows = Util::sql('show tables');
            }
            else {
                $rows = Util::sql("select table_name from information_schema.tables where table_schema in ('public')");

                /*return array( #todo use real query
                    'food'=>1, 'ate'=>1, 'person'=>1,
                    'company'=>1, 'quote'=>1,
                );*/
            }

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
