<?php
    require_once('db_config.php');
    $db = new PDO(
        "$db_type:host=$db_host;dbname=$db_name",
        $db_user, $db_password
    );

    class Util {

        public static function sql($query, $returnType='array') {
            global $db;
            return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
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
			# if (isset($tables['contractor'])) ...
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

        public static function val_list_str($vals) {
            $val_reps = array_map(
                function($val) {
                    #todo #fixme doesn't work for nulls
                    return "'$val'";
                },
                $vals
            );
            return implode(',', $val_reps);
        }

        public static function tables_with_field($fieldname, $data_type=NULL) {
            {   ob_start();
?>
                select table_schema, table_name
                from information_schema.columns
                where column_name='<?= $fieldname ?>'
<?php
                if ($data_type) {
?>
                    and data_type='<?= $data_type ?>'
<?php
                }
                $sql = ob_get_clean();
            }
            $rows = Util::sql($sql);

            $tables = array();
            foreach ($rows as $row) {
                $schema = $row['table_schema'];
                $table_name = $row['table_name'];
                if ($schema != 'public') {
                    $table_name = "$schema.$table_name";
                }
                $tables[] = $table_name;
            }

            return $tables;
        }

        public static function rows_with_field_vals($fieldname, $vals, $data_type=NULL) {
            #todo depending on $id_type, send e.g. $fieldname='inventory_id' for the 'id' field of inventory
            $tables = self::tables_with_field($fieldname, $data_type);
            $val_list = self::val_list_str($vals);
            $table_rows = array();
            foreach ($tables as $table) {
                $sql = "
                    select * from $table
                    where $fieldname in ($val_list)
                ";

                $rows = Util::sql($sql);
                if (count($rows)) {
                    $table_rows[$table] = $rows;
                }
            }
            return $table_rows;
        }
    }

    #$jquery_url = "/js/jquery.min.js"; #todo #fixme give cdn url by default

    if ($pg && isset($search_path)) {
        Util::sql("set search_path to $search_path");
    }
?>
