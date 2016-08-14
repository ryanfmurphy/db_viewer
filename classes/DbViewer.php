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


        /*
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
        */


        /*
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
        */

        /*
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
        */

        /*
        # get all tables with that fieldname, optionally filtering by vals
        public static function tables_with_field($fieldname, $data_type=null, $vals=null) {
			$args = print_r(get_defined_vars(),1);
			do_log("top of tables_with_field, get_defined_vars()=$args");
			do_log("  fieldname=$fieldname, data_type=$data_type, vals=".print_r($vals,1));

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
                $rows = Db::sql($sql);

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
        */

        /*
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
            $val_list = DbUtil::val_list_str($vals);
            $table_rows = array();

			# search through the tables one by one and
			# only include the tables that actually have matching rows
            foreach ($tables as $table) {
				if (in_array(DbUtil::just_tablename($table), $slow_tables)) {
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
                    $data = DbUtil::keyRowsByFieldMulti($rows, $fieldname);
                    $table_rows[$table] = $data;
                }
            }

            return $table_rows;
        }
        */


        # Util functions
        #---------------

        /*
        public static function log($msg) {
            error_log($msg, 3, __DIR__.'/error_log');
        }

        public static function val_list_str($vals) {
            $val_reps = array_map(
                function($val) {
					return Util::quote($val);
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
        */


        # Rendering and Type-Recognition Functions
        # ----------------------------------------

        public static function outputDbError($db) { #keep
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

        #todo maybe move to different class?
        public static function is_url($val) {
            if (is_string($val)) {
                $url_parts = parse_url($val);
                $schema = (isset($url_parts['scheme'])
                                ? $url_parts['scheme']
                                : null);

                if ($schema) {
                    # prevent false positives where after the colon we have stuff other than a link
                    # e.g. just some notes to self, but with a colon after the header

                    $colonPos = strpos($val, ':');
                    $hasStuffAfterColon = strlen($val) > $colonPos + 1;
                    if ($hasStuffAfterColon) {
                        $charAfterColon = $val[$colonPos + 1];
                        $isWhitespace = ctype_space($charAfterColon);
                        return ( !$isWhitespace
                                    ? true # probably a url
                                    : false # might be notes etc
                               );
                    }
                    else {
                        return false;
                    }
                }
                else {
                    return false;
                }
            }
            else {
                return false;
            }
        }

        # formal $val as HTML to put in <td>
        public static function val_html($val, $fieldname) { #keep
            do_log("top of val_html(val='$val', fieldname='$fieldname')\n");
            if (DbUtil::seems_like_pg_array($val)) {
                $vals = DbUtil::pgArray2array($val);
                return self::array_as_html_list($vals);
            }
            elseif (self::is_url($val)) {
                { ob_start();
                    $val = htmlentities($val);
?>
        <a href="<?= $val ?>" target="_blank">
            <?= $val ?>
        </a>
<?php
                    return ob_get_clean();
                }
            }
            elseif (self::isTableNameVal($val, $fieldname)) {
                { # vars
                    $tablename = $val;
                    $cmp = class_exists('Campaign');
                    $hasPhpExt = !$cmp;
                    $local_uri = ($hasPhpExt
                                    ? 'db_viewer.php'
                                    : 'db_viewer');
                }

                { ob_start(); # provide a link
                    $val = htmlentities($val);
?>
        <a href="<?= $local_uri ?>?sql=select * from <?= $tablename ?> limit 100"
           target="_blank"
        >
            <?= $val ?>
        </a>
<?php
                    return ob_get_clean();
                }
            }
            else {
                $val = htmlentities($val);
                $val = nl2br($val); # show newlines as <br>'s

                { # get bigger column width for longform text fields
                    #todo factor this logic in the 2 places we have it
                    # (here and in dash)
                    if ($fieldname == 'txt'
                        || $fieldname == 'src'
                    ) {
                        ob_start();
?>
                        <div class="wide_col">
                            <?= $val ?>
                        </div>
<?php
                        return ob_get_clean();
                    }
                }

                return $val;
            }
        }

        public static function array_as_html_list($array) { #keep
            { ob_start();
?>
        <ul>
<?php
                foreach ($array as $val) {
?>
            <li><?= htmlentities($val) ?></li>
<?php
                }
?>
        </ul>
<?php
                return ob_get_clean();
            }
        }

        public static function isTableNameVal($val, $fieldName) { #keep
            return ((preg_match('/Tables_in_/', $fieldName)
                     || $fieldName == "Name")
                            ? true
                            : false);
        }


        # Query Manipulation / Interpretation Functions
        #----------------------------------------------

        /*
        public static function infer_table_from_query($query) {
            #todo improve inference - fix corner cases
            if (preg_match("/\bfrom ((?:\w|\.)+)\b/i", $query, $matches)) {
                $table = $matches[1];
                return $table;
            }
        }
        */

    }

