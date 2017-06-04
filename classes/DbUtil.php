<?php
#todo move this function elsewhere and rename it
if (!class_exists('Utility')) {

    class Utility {

        public static function ends_with($needle,$haystack) {
            $len = strlen($needle);
            $LEN = strlen($haystack);
            return substr($haystack, $LEN - $len) == $needle;
        }

        /*
        public static function quote($val) {
            global $db; #todo do we even need this fn anymore?
            #todo #fixme might not work for nulls?
            return $db->quote($val);
        }
        */
    }

}

if (!class_exists('DbUtil')) {

    class DbUtil {

        # ends with _id or _iid: returns that suffix
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

        # returns e.g. 'id' or 'iid' or false if not an id field
        public static function is_id_field($field_name) {
            if ($field_name == 'id') {
                return 'id';
            }
            elseif ($field_name == 'iid') {
                return 'iid';
            }
            else {
                $suffix = self::has_valid_join_field_suffix($field_name);
                if ($suffix) {
                    return substr($suffix,1);
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
            $db_type = Config::$config['db_type'];

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

        # unquote tablename
        public static function strip_quotes($tablename) {
            $db_type = Config::$config['db_type'];
            return str_replace(self::quote_char(), '', $tablename);
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
			# $tables = TableView::sql_tables();
			# if (isset($tables['contractor'])) ...
		public static function sql_tables() {
            $db_type = Config::$config['db_type'];

            if ($db_type == 'pgsql') {
                $rows = Db::sql("
                    select table_name
                    from information_schema.tables
                    where table_schema in ('public')
                ");
            }
            elseif ($db_type == 'sqlite') {
                $rows = Db::sql("
                    select name from sqlite_master where type='table'
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


        # look through all tables to find if $tablename_root has some table name at the end of it
        # allows prefixes in join field, e.g. "parent_topic" can link to topic table
        # return an array of [$tablename_root, $field_name], modified if a match was found
        public static function find_table_prefix(
            $tablename_root, $field_name=null, $suffix=null, $possible_tables=null
        ) {
            self::log("    looking for prefix in field\n",3);

            # if it's not as simple as `contractor_id`
            # try to find a table that this id might be pointing to
            # e.g. `parent_contractor_id` also links to contractor

            #todo make sure that this is a FALLBACK:
            #     if there's a full field_name match, use that
            #     otherwise try this loop

            if ($possible_tables === null) {
                $possible_tables = self::sql_tables();
            }

            if (!isset($possible_tables[$tablename_root])) {
                # loop looking for table ending in $table
                self::log("      loop looking for table ending in '$tablename_root'\n        ",3);
                foreach (array_keys($possible_tables) as $this_table_name) {
                    self::log(" $this_table_name",5);
                    if (Utility::ends_with($this_table_name, $tablename_root)) {
                        #todo use longest match as suffix / and_or require "_" before suffix
                        #     because for example, lead_id has ad_id at the end!
                        #     so instead of returning, only replace if it's longer

                        $tablename_root = $this_table_name;
                        # overwrite field_name
                        $field_name = ($suffix
                                          ? $this_table_name.$suffix #todo does this work for id_only mode too?
                                          : 'name');
                        self::log("
    found it!  table '$this_table_name' is the suffix of tablename_root '$tablename_root'
    done looking for prefix in field:
        tablename_root = $tablename_root,
        field_name = $field_name
",3);
                        return array(
                            $tablename_root,
                            $field_name
                        );
                    }
                }
            }

            return array(
                $tablename_root,
                $field_name
            );
        }

        # used in joins to determine which table to join to from a field_name
        public static function choose_table_and_field($field_name) {
            self::log("\ntop of choose_table_and_field(field_name = '$field_name')\n");
            $id_mode = Config::$config['id_mode'];
            $pluralize_table_names = Config::$config['pluralize_table_names'];

            $suffix = self::has_valid_join_field_suffix($field_name);
            self::log(" has_valid_join_field_suffix(field_name=$field_name)?\n");

            if ($suffix) {
                self::log("  yes, suffix\n");

                { # chop off suffix
                    $tablename_root = substr($field_name, 0, -strlen($suffix));
                    self::log("  did substr to shave suffix: tablename_root = $tablename_root\n");
                }
            }
            else { # no suffix - maybe a name field
                self::log("  no suffix - maybe a name field\n");
                $tablename_root = $field_name;
                # field_name set to 'name' later
            }

            { # find table prefix if any
                self::log("    about to find_table_prefix\n");
                list($tablename_root, $field_name) =
                    self::find_table_prefix($tablename_root, $field_name, $suffix);
            }

            { # doctor tablename
                if ($pluralize_table_names) {
                    $tablename_root = self::pluralize($tablename_root);
                }

                $tablename_root = self::full_tablename($tablename_root);
            }

            { # doctor field_name
                if ($suffix) {
                    self::log("  id_only mode?\n");
                    if ($id_mode == 'id_only') {
                        self::log("    yes.  trimming before suffix (#todo do this earlier in find_table_prefix fn)\n");
                        $field_name = ltrim($suffix,'_'); #todo this seems dangerous! will this really work in all cases?
                        self::log("    now trimmed field_name = '$field_name'\n");
                    }
                    #todo maybe error out if didn't match a table?
                }
                else { # no-suffix, probably a name field
                    $field_name = 'name';
                }
            }

            return array($tablename_root, $field_name);
        }

        # get all tables with that fieldname, optionally filtering by vals
        public static function tables_with_field(
            $fieldname, $data_type=null, $vals=null
        ) {

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
            $slow_tables = Config::$config['slow_tables'];
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
					#$delta0 = Utility::time_since($T0);
					#echo "delta0 = $delta0\n";

					$includeTable = (is_array($rows)
                                     && count($rows) > 0);
				}

                if ($includeTable) {
                    $data = self::key_rows_by_field_multi($rows, $fieldname);
                    $table_rows[$table] = $data;
                }
            }

            return $table_rows;
        }


        # Util functions
        #---------------

        public static function log($msg, $level=0) {
            if ($level < 5) {
                # use error_log for now because this class is now used by
                # both obj_editor and table_view - and obj_editor doesn't have do_log() defined
                error_log($msg, 3, __DIR__.'/error_log');
                #do_log($msg);
            }
        }

        public static function val_list_str($vals) {
            $val_reps = array_map(
                function($val) {
					return Db::sql_literal($val);
					#return "'$val'";
                },
                $vals
            );
            return implode(',', $val_reps);
        }

        public static function key_rows_by_field($rows, $keyField) {
			$data = array();
			foreach ($rows as $row) {
				$idVal = $row[$keyField];
				$data[$idVal] = $row;
			}
            return $data;
        }

        # same thing but allow multiple rows per key
        public static function key_rows_by_field_multi($rows, $keyField) {
			$data = array();
			foreach ($rows as $row) {
				$idVal = $row[$keyField];
				$data[$idVal][] = $row;
			}
            return $data;
        }

        # convert postgres array str into php array
        public static function pg_array2array($pgArrayStr, $itemType='text') {
            $arrayType = $itemType.'[]';
            $pgArrayStrQuoted = Db::quote($pgArrayStr);
            $query = "select array_to_json($pgArrayStrQuoted::$arrayType)";
            $rows = Db::sql($query);
            $row = $rows[0];
            $val = $row['array_to_json'];
            return json_decode($val);
        }

        public static function fieldname_given_base_table($fieldname, $base_table) {
            $base_table = DbUtil::strip_quotes($base_table);
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

        #todo improve pg_array guess, maybe use column type
        public static function seems_like_pg_array($val) {
            $db_type = Config::$config['db_type'];
            if ($db_type == 'pgsql'
                && is_string($val)
            ) {
                # first see if it's valid json (not a pg array)
                    # #todo use column type instead
                    # this may have a bad #performance impact
                    # always trying to json_decode every value
                $json = json_decode($val, true);
                if (is_array($json)
                    && count($json) > 0
                ) {
                    return false;
                }

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

        # postgres-specific setup
        public static function set_db_search_path($search_path) {
            $db_type = Config::$config['db_type'];
            if ($db_type == 'pgsql') {
                if ($search_path) {
                    Db::sql("set search_path to $search_path");
                }
            }
        }

        # get comma-sep search_path as array of schemas
        public static function schemas_in_path($search_path) {
            $search_path = trim($search_path);
            $search_path_no_spaces = str_replace(' ', '', $search_path);
            $schemas_in_path = explode(',', $search_path_no_spaces);
            if (count($schemas_in_path) == 1 && $schemas_in_path[0] == '') {
                return null;
            }
            else {
                return $schemas_in_path;
            }
        }


        # Query Manipulation / Interpretation Functions
        #----------------------------------------------

        public static function quote_char() {
            $db_type = Config::$config['db_type'];
            return ($db_type == 'mysql'
                            ? '`'
                            : '"');
        }

        public static function infer_table_from_query($query) {
            #todo improve inference - fix corner cases
            $quote_char = self::quote_char();
            if (preg_match(
                    "/ \b from \s+ ((?:\w|\.|$quote_char)+) /ix",
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

                        ; ?

                        $
                        /six";

            $result = array(
                'limit' => null,
                'offset' => null,
                'query_wo_limit' => $query,
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
    query = '$query'
"
    #regex = '$regex'
);
            }
            #die(print_r($result,1));
            return $result;
        }

        #todo move these to TableView class
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

        public static function link_to_query_w_limit(
            $query, $limit=null, $offset=null
        ) {
            $db_type = Config::$config['db_type'];
            $maybeLimit = ($limit !== null
                                ? "\nlimit $limit" #todo #fixme why is this newline not working?
                                : "");
            $maybeOffset = ($offset !== null
                                ? "\noffset $offset"
                                : "");
            return "?sql=" . urlencode($query . $maybeLimit . $maybeOffset)
                   . "&db_type=$db_type";
        }

        # get id field
        public static function get_primary_key_field($id_mode, $tablename_no_quotes) {
            $pk_fields_by_table = Config::$config['primary_key_fields_by_table'];
            if (isset($pk_fields_by_table[$tablename_no_quotes])) {
                return $pk_fields_by_table[$tablename_no_quotes];
            }
            elseif ($id_mode == 'id_only') {
                $primary_key_field = 'id';
            }
            else {
                $primary_key_field = $tablename_no_quotes.'_id';
            }
            return $primary_key_field;
        }

        public static function get_name_field($table) {
            $name_fields_by_table = Config::$config['name_fields_by_table'];
            if (isset($name_fields_by_table[$table])) {
                return $name_fields_by_table[$table];
            }
            else {
                return 'name';
            }
        }

        // pass by ref for performance, non-destructive
        public static function get_name_val($table, &$row) {
            $name_field = DbUtil::get_name_field($table);
            $filter_fns = Config::$config['name_field_filter_fn_by_table'];
            $name_val = $row[$name_field];
            if (isset($filter_fns[$table])) {
                $fn = $filter_fns[$table];
                $name_val = $fn($name_val);
            }
            if (!$name_val) {
                $name_val = '[untitled]';
            }
            return $name_val;
        }

        public static function get_columns_sql(
            $table, $schemas_in_path
        ) {
            $schemas_val_list = DbUtil::val_list_str($schemas_in_path);
            $db_type = Config::$config['db_type'];

            if ($db_type == 'sqlite') {
                $get_columns_sql = "
                    pragma table_info('entity')
                ";
            }
            else {
                ob_start();
?>
                select
                    table_schema, table_name,
                    column_name
                from information_schema.columns
                where table_name='<?= $table ?>'
<?php
                if ($schemas_val_list) {
?>
                    and table_schema in (<?= $schemas_val_list ?>)
<?php
                }
                $get_columns_sql = ob_get_clean();
            }
            return $get_columns_sql;
        }

        # get fields of table from db
        # returns false if table $table doesn't exist
        public static function get_table_fields(
            $table, $schemas_in_path=null
        ) {
            $db_type = Config::$config['db_type'];
            #todo #fixme factor this with dup code in obj_editor/index.php
            $get_columns_sql = DbUtil::get_columns_sql(
                $table, $schemas_in_path
            );

            # get fieldsRows
            if ($db_type == 'sqlite') {
                $rawFieldsRows = Db::sql($get_columns_sql);
                $fieldsRows = array();
                foreach ($rawFieldsRows as $rawFieldsRow) {
                    $row['table_schema'] = 'public';
                    $row['column_name'] = $rawFieldsRow['name'];
                    $fieldsRows[] = $row;
                }
            }
            else {
                $fieldsRows = Db::sql($get_columns_sql);
            }

            if (count($fieldsRows) == 0) {
                return false;
            }

            /*if ($db_type == 'sqlite') {
                $minimal_fields_by_table = Config::$config['minimal_fields_by_table'];
                if (isset($minimal_fields_by_table[$table])) {
                    return $minimal_fields_by_table[$table];
                }
                else {
                    return array('name','txt','id','time');
                }
            }
            else {*/
                /*{ # do query
                    $fieldsRows = Db::sql($get_columns_sql);
                    if (count($fieldsRows) == 0) {
                        #die("Table $table doesn't exist");
                        return false;
                    }
                }*/

                { # group by schema
                    $fieldsRowsBySchema = array();
                    #todo #fixme Warning: Invalid argument supplied for foreach()
                    foreach ($fieldsRows as $fieldsRow) {
                        $schema = $fieldsRow['table_schema'];
                        $fieldsRowsBySchema[$schema][] = $fieldsRow;
                    }
                }

                { # choose 1st schema that applies
                    if ($schemas_in_path) {
                        $schema = null;

                        foreach ($schemas_in_path as $schema_in_path) {
                            if (isset($fieldsRowsBySchema[$schema_in_path])) {
                                $schema = $schema_in_path;
                                break;
                            }
                        }
                        if ($schema === null) {
                            die("Whoops!  Couldn't select a DB schema for table $table");
                        }
                    }
                }

                { # get just the column_names
                    $fields = array_map(
                        function($x) {
                            return $x['column_name'];
                        },
                        $fieldsRowsBySchema[$schema]
                    );
                }

                /* #todo pass this out
                { # so we can give a warning/notice about it later
                    $multipleTablesFoundInDifferentSchemas =
                        count(array_keys($fieldsRowsBySchema)) > 1;
                }
                */

                return $fields;
            #}
        }

        # if returns false, maybe table doesn't exist
        public static function get_time_field($table, $schemas_in_path=null) {
            $fields = self::get_table_fields($table, $schemas_in_path);
            if ($fields === false) {
                return false;
            }
            else {
                $possible_time_fields = array(
                    'time',
                    'time_added',
                    'creation_timestamp',
                );
                foreach ($possible_time_fields as $this_field) {
                    if (in_array($this_field, $fields)) {
                        return $this_field;
                    }
                }
                return null;
            }
        }

        public static function quote_tablename($table) {
            $db_type = Config::$config['db_type'];

            $quote_char = self::quote_char();
            #todo don't try to quote if there are already quotes
            $result = $quote_char
                    . str_replace(
                        '.',
                        "$quote_char.$quote_char",
                        $table)
                    . $quote_char;
            return $result;
        }

        public static function query_is_destructive($query) {
            if (preg_match(
                    "/\\b(INSERT|UPDATE|DROP|DELETE|CREATE|ALTER|TRUNCATE)\\b/i",
                    $query, $match
                )
            ) {
                $destrictive_kw = $match[1];
                return $destrictive_kw;
            }
            else {
                return false;
            }
        }

        #todo #fixme duplicate of sql_tables()
        public static function get_tables_array() {
            {   ob_start();
?>
                    select table_schema, table_name
                    from information_schema.tables
                    where table_schema = 'public' -- #todo #fixme
<?php
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

}

