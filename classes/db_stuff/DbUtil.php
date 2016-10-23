<?php
    class DbUtil {

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


        # table name manipulation functions
        #----------------------------------

        # prepend table schema etc
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


        # Language Manipulation
        #----------------------

        public static function pluralize($tablename) {
            return $tablename . 's'; #todo make pluralizing less dumb
        }

        public static function depluralize($tablename) {
            return (self::is_plural($tablename)
                        ? substr($tablename, 0, -1) #todo make depluralizing less dumb
                        : $tablename);
        }

        public static function is_plural($tablename) {
            $len = strlen($tablename);
            return ($tablename[$len-1] == 's'); #todo make less dumb
        }


        # Database Searching Functions
        #-----------------------------

		# result is keyed by table_name, all vals are 1
		# for fast lookup, can use e.g.:
			# $tables = DbViewer::sqlTables();
			# if (isset($tables['contractor'])) ...
		public static function sqlTables() {
            global $db_type; #todo #fixme - global scope won't work for CMP/SD - use static class variables

            if ($db_type == 'pgsql') {
                $rows = Db::sql("
                    select table_name
                    from information_schema.tables
                    where table_schema in ('public')
                ");
            }
			else { # probably mysql
                $rows = Db::sql('show tables');
            }

            $tables = array();
            foreach ($rows as $row) {
                $table = current($row);
                $tables[$table] = 1;
            }

            return $tables;
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

                    $possible_tables = self::sqlTables();

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
                    $tablename_root = self::pluralize($tablename_root);
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

        # get all tables with that fieldname, optionally filtering by vals
        public static function tables_with_field($fieldname, $data_type=null, $vals=null) {

			$args = print_r(get_defined_vars(),1);
			self::log("top of tables_with_field, get_defined_vars()=$args\n");
			self::log("  fieldname=$fieldname, data_type=$data_type, vals=".print_r($vals,1)."\n");

            $has_vals = (is_array($vals) && count($vals));

            # get only the tables that actually would have rows for those vals
			self::log("  has_vals?\n");
            if ($has_vals) {
                self::log("    yes\n");
                $rows = self::rows_with_field_vals(
                    $fieldname, $vals, null, $data_type
                );
                return array_keys($rows);
            }
            # get all tables with the fieldname, regardless of whether rows will actually be returned
            else {
                self::log("    no\n");

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
                self::log("$sql\n");
                $rows = Db::sql($sql);

                self::log("about to loop thru rows\n");
                $tables = array();
                foreach ($rows as $row) {
                    self::log("  row = ".print_r($row,1)."\n");
                    $schema = $row['table_schema'];
                    $table_name = $row['table_name'];
                    if ($schema != 'public') {
                        $table_name = "$schema.$table_name";
                    }
                    $tables[] = $table_name;
                }  
                self::log("  done looping, returning from tables_with_field\n");

                return $tables;

            }
        }

        #todo (front-end) depending on $id_type,
            #todo return e.g. $fieldname='inventory_id' for the 'id' field of inventory
            #todo return e.g. $fieldname='inventory' for the 'name' field of inventory
            #todo do we need this $table arg?
        public static function rows_with_field_vals($fieldname, $vals, $table=NULL, $data_type=NULL) {
            global $slow_tables;
            self::log("rows_with_field_vals(fieldname=$fieldname,vals=".json_encode($vals).",table=$table,data_type=$data_type)\n");

            $tables = ($table === null
                         ? self::tables_with_field($fieldname, $data_type)
                         : array($table));
            $val_list = self::val_list_str($vals);
            $table_rows = array();

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
                    #die();

					$rows = Db::sql($sql);
					#$delta0 = Util::timeSince($T0);
					#echo "delta0 = $delta0\n";

					$includeTable = (is_array($rows)
                                     && count($rows) > 0);
				}

                if ($includeTable) {
                    $data = self::keyRowsByFieldMulti($rows, $fieldname);
                    $table_rows[$table] = $data;
                }
            }

            return $table_rows;
        }


        # Util functions
        #---------------

        public static function log($msg) {
            error_log($msg, 3, __DIR__.'/error_log');
        }

        public static function val_list_str($vals) {
            $val_reps = array_map(
                function($val) {
					return Db::sqlLiteral($val);
					#return "'$val'";
                },
                $vals
            );
            return implode(',', $val_reps);
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

        public static function outputDbError($db) { #kill
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
            $pgArrayStrQuoted = Db::quote($pgArrayStr);
            $query = "select array_to_json($pgArrayStrQuoted::$arrayType)";
            $rows = Db::sql($query);
            $row = $rows[0];
            $val = $row['array_to_json'];
            return json_decode($val);
        }

        public static function fieldname_given_base_table($fieldname, $base_table) {
            if ($base_table) {
                if ($fieldname == 'name') {
                    return $base_table;
                }
                elseif ($fieldname == 'id') {
                    return $base_table.'_id';
                }
                elseif ($fieldname == 'iid') {
                    return $base_table.'_iid';
                }
				else {
					return $fieldname;
				}
            }
            else {
                return $fieldname;
            }
        }

        #todo improve pg_array guess, maybe user column type
        public static function seems_like_pg_array($val) {
            global $db_type;
            if ($db_type == 'pgsql'
                && is_string($val)
            ) {
                $len = strlen($val);
                if ($len >= 2
                    && $val[0] == "{"
                    && $val[$len-1] == "}"
                ) {
                    return true;
                }
                else {
                    return false;
                }
            }
            else {
                return false;
            }
        }

        public static function is_url($val) {
            if (is_string($val)) {
                $url_parts = parse_url($val);
                $is_url = (isset($url_parts['scheme']));
                return $is_url;
            }
            else {
                return false;
            }
        }

        # formal $val as HTML to put in <td>
        public static function val_html($val) { #kill
            $val = htmlentities($val);
            if (self::seems_like_pg_array($val)) {
                $vals = self::pgArray2array($val);
                { ob_start();
?>
        <ul>
<?php
                    foreach ($vals as $val) {
?>
            <li><?= $val ?></li>
<?php
                    }
?>
        </ul>
<?php
                    return ob_get_clean();
                }
            }
            elseif (self::is_url($val)) {
                { ob_start();
?>
        <a href="<?= $val ?>" target="_blank">
            <?= $val ?>
        </a>
<?php
                    return ob_get_clean();
                }
            }
            else {
                return $val;
            }
        }

        # postgres-specific setup
        public static function setDbSearchPath($search_path) {
            global $db_type;
            if ($db_type == 'pgsql') {
                if ($search_path) {
                    Db::sql("set search_path to $search_path");
                }
            }
        }

        # get comma-sep search_path as array of schemas
        public static function schemas_in_path($search_path) {
            $search_path_no_spaces = str_replace(' ', '', $search_path);
            $schemas_in_path = explode(',', $search_path_no_spaces);
            return $schemas_in_path;
        }


        # Query Manipulation / Interpretation Functions
        #----------------------------------------------

        public static function infer_table_from_query($query) {
            #todo improve inference - fix corner cases
            if (preg_match(
                    "/ \b from \s+ ((?:\w|\.)+) \b /ix",
                    $query, $matches)
            ) {
                $table = $matches[1];
                return $table;
            }
        }

        public static function infer_limit_info_from_query($query) {
            #todo improve inference - fix corner cases
            $regex = "/ ^

                        (?P<query_wo_limit>.*)

                        \s+

                        limit
                        \s+ (?P<limit>\d+)
                        (?:
                            \s+ offset
                            \s+ (?P<offset>\d+)
                        ) ?

                        $ /ix";

            $result = array(
                'limit' => null,
                'offset' => null,
                'query_wo_limit' => null,
            );

            if (preg_match($regex, $query, $matches)) {
                if (isset($matches['query_wo_limit'])) {
                    $result['query_wo_limit'] = $matches['query_wo_limit'];
                }
                if (isset($matches['limit'])) {
                    $result['limit'] = $matches['limit'];
                }
                if (isset($matches['offset'])) {
                    $result['offset'] = $matches['offset'];
                }
            }
            else {
                self::log("
infer_limit_from_query: query didn't match regex.
    query = $$$query$$
    regex = '$regex'
");
            }
            #die(print_r($result,1));
            return $result;
        }

        #todo move these to DbViewer class
        # while factoring some key sql-building part to leave here in DbUtil
        public static function link_to_prev_page($limit_info) {
            $limit = $limit_info['limit'];
            $offset = $limit_info['offset'];
            $new_offset = $offset - $limit;
            if ($new_offset < 0) {
                $new_offset = 0;
            }
            return self::link_to_query_w_limit(
                $limit_info['query_wo_limit'],
                $limit,
                $new_offset
            );
        }
        public static function link_to_next_page($limit_info) {
            $limit = $limit_info['limit'];
            $offset = $limit_info['offset'];
            $new_offset = $offset + $limit;
            if ($new_offset < 0) {
                $new_offset = 0;
            }
            return self::link_to_query_w_limit(
                $limit_info['query_wo_limit'],
                $limit,
                $new_offset
            );
        }

        public static function link_to_query_w_limit($query, $limit=null, $offset=null) {
            global $db_type;
            $maybeLimit = ($limit !== null
                                ? " limit $limit"
                                : "");
            $maybeOffset = ($offset !== null
                                ? " offset $offset"
                                : "");
            return "?sql=$query" . $maybeLimit . $maybeOffset
                   . "&db_type=$db_type";
        }

    }

