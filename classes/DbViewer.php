<?php
    class DbViewer {

        # ends with _id or _iid
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

		# peel off schema/database
		public static function just_tablename($full_tablename) {
			$dotPos = strpos($full_tablename, '.');
			return ($dotPos !== false
						? substr($full_tablename, $dotPos+1)
						: $full_tablename);
		}

        public static function pluralize($tablename) {
            return $tablename . 's'; #todo make pluralizing less dumb
        }

        # used in joins to determine which table to join to from a field_name
		public static function choose_table_and_field($field_name) {
            global $id_mode, $pluralize_table_names;

			$suffix = self::has_valid_join_field_suffix($field_name);
			if ($suffix) {
				$tablename_root = substr($field_name, 0, -strlen($suffix));

				{ # allow prefixes in id field

                    # if it's not as simple as `contractor_id`
                    # try to find a table that this id might be pointing to
                    # e.g. `parent_contractor_id` also links to contractor

					#todo make sure that this is a FALLBACK:
					#     if there's a full field_name match, use that
					#     otherwise try this loop

                    $possible_tables = DbViewer::sqlTables();

                    if (!isset($possible_tables[$tablename_root])) {
                        # loop looking for table ending in $table
                        foreach (array_keys($possible_tables) as $this_table_name) {
                            if (Util::endsWith($this_table_name, $tablename_root)) {
                                #todo use longest match as suffix / and_or require "_" before suffix
                                #	  because for example, lead_id has ad_id at the end!
                                #     so instead of breaking, only replace if it's longer

                                $tablename_root = $this_table_name;
                                $field_name = $this_table_name.$suffix;
                                break;
                            }
                        }
                    }
                }

                if ($pluralize_table_names) {
                    $tablename_root = DbViewer::pluralize($tablename_root);
                }

                $tablename_root = self::full_tablename($tablename_root);

				#todo maybe error out if didn't match a table?
                if ($id_mode == 'id_only') {
                    $field_name = ltrim($suffix,'_');
                }
				return array($tablename_root, $field_name);
			}
			else { # this else not used yet
				$table = self::full_tablename($field_name);
				$field_name = 'name';
				return array($table, $field_name);
			}
		}

		# result is keyed by table_name, all vals are 1
		# for fast lookup, can use e.g.:
			# $tables = DbViewer::sqlTables();
			# if (isset($tables['contractor'])) ...
		public static function sqlTables() {
            global $db_type; #todo #fixme - global scope won't work for CMP/SD - use static class variables

            if ($db_type == 'pgsql') {
                $rows = Util::sql("
                    select table_name
                    from information_schema.tables
                    where table_schema in ('public')
                ");
            }
			else { # probably mysql
                $rows = Util::sql('show tables');
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
					return "'$val'";
                    #global $db; #todo will this global work in all cases?
                    #todo #fixme might not work for nulls?
                    #return $db->quote($val); #todo maybe make a fn for quote?
                },
                $vals
            );
            return implode(',', $val_reps);
        }

        public static function tables_with_field($fieldname, $data_type=null, $vals=null) {

            $has_vals = (is_array($vals) && count($vals));

            # get only the tables that actually would have rows for those vals
            if ($has_vals) {
                $rows = self::rows_with_field_vals($fieldname, $vals, null, $data_type);
                return array_keys($rows);
            }
            # get all tables with the fieldname, regardless of whether rows will actually be returned
            else {

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
        }

        #todo (front-end) depending on $id_type,
            # send e.g. $fieldname='inventory_id' for the 'id' field of inventory
        public static function rows_with_field_vals($fieldname, $vals, $table=NULL, $data_type=NULL) {

            $tables = ($table === null
                         ? self::tables_with_field($fieldname, $data_type)
                         : array($table));
            $val_list = self::val_list_str($vals);
            $table_rows = array();

			$slow_tables = array( #todo put in config
				'lead','note2campaign','lead_call','lead_contact_email2campaign',
				# 'ad_cost2campaign'
			);

			# search through the tables one by one and
			# only include the tables that actually have matching rows
            foreach ($tables as $table) {
				if (in_array(self::just_tablename($table), $slow_tables)) {
					$includeTable = true; # just assume it's included, skip slow query
				}
				else {
					#$T0 = microtime();
					#echo "T0 = $T0\n";
					$sql = "
						select * from $table
						where $fieldname in ($val_list)
					";
					#echo "sql = $sql\n";

					$rows = Util::sql($sql);
					#$delta0 = Util::timeSince($T0);
					#echo "delta0 = $delta0\n";

					$includeTable = (count($rows) > 0);
				}

                if (count($rows)) {
                    $data = self::keyRowsByFieldMulti($rows, $fieldname);
                    $table_rows[$table] = $data;
                }
            }

            return $table_rows;
        }

        public static function keyRowsByField($rows, $keyField) {
			$data = array();
			foreach ($rows as $row) {
				$idVal = $row[$keyField];
				$data[$idVal] = $row;
			}
            return $data;
        }

        # same thing but allow multiple rows per key
        public static function keyRowsByFieldMulti($rows, $keyField) {
			$data = array();
			foreach ($rows as $row) {
				$idVal = $row[$keyField];
				$data[$idVal][] = $row;
			}
            return $data;
        }

        public static function outputDbError($db) {
?>
<div>
    <p>
        <b>Oops! Could not get a valid result.</b>
    </p>
    <p>
        PDO::errorCode(): <?= $db->errorCode() ?>
    </p>
    <div>
        PDO::errorInfo():
        <pre><?php print_r($db->errorInfo()) ?></pre>
    </div>
</div>
<?php
        }

        # convert postgres array str into php array
        public static function pgArray2array($pgArrayStr, $itemType='text') {
            $arrayType = $itemType.'[]';
            $query = "select array_to_json('$pgArrayStr'::$arrayType)";
            $rows = Util::sql($query);
            $row = $rows[0];
            $val = $row['array_to_json'];
            return json_decode($val);
        }

    }

