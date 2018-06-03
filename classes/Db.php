<?php

#todo require_once DbUtil, Config

if (!class_exists('Db')) {

    class Db {

        public static $db = null;

        public static function connect_to_db() {
            if (!is_array(Config::$config)) {
                die("Must pass set Db::\$config to the \$config array first\n");
            }
            extract(Config::$config); # creates variables

            $connection_str = ($db_type == 'sqlite'
                                ? "$db_type:$db_name"
                                : "$db_type:host=$db_host;"
                                    . "dbname=$db_name;"
                                    . ($db_type == 'mysql'
                                            ? "charset=utf8" # this charset is to fix issues with PHP
                                                             # understanding/rendering Unicode characters
                                                             # when talking with MySQL / MariaDB
                                                             #      ~ 2017-09-11 RFM
                                            : "")
                              );

            try {
                $db = self::$db = new PDO(
                    $connection_str,
                    $db_user, $db_password
                );
            }
            catch (PDOException $e) {
                # redirect to the auth page if db_prompt_for_auth is enabled
                $db_prompt_for_auth = Config::$config['db_prompt_for_auth'];
                $err_msg = "Error connecting to DB: ".$e->getMessage();
                if ($db_prompt_for_auth) {
                    #todo #fixme foward the error along so that they can see it on the Auth page
                    unset($_SESSION['db_user']);
                    unset($_SESSION['db_password']);
                    header("HTTP/1.1 302 Redirect");
                    header("Location: $prompt_for_auth_uri");
                    die();
                }
                else {
                    die($err_msg);
                }
            }
            return $db;
        }

        # (cached) connection to db
        public static function conn() {
            $db = ( isset(self::$db)
                       ? self::$db
                       : Db::connect_to_db() );
            if (!$db) {
                trigger_error(
                    'problem connecting to database',
                    E_USER_ERROR
                );
            }
            else {
                return $db;
            }
        }

        public static function error($msg=null, $sql=null) {
            $db = Db::conn();
            trigger_error(
    $msg . "
        SQL error:
             errorCode = ".$db->errorCode()."
             errorInfo = ".print_r($db->errorInfo(),1)."
         for query '$sql'
    "
            , E_USER_ERROR);
        }

        public static function error_result($sql=null) {
            $db = Db::conn();
            $result = array(
                'success' => 0,
                'error_code' => $db->errorCode(),
                'error_info' => $db->errorInfo(),
            );
            if ($sql) { $result['sql'] = $sql; }
            return $result;
        }

        public static function result_is_success($result) {
            return (is_array($result)
                    && (!isset($result['success'])
                        || $result['success']));
        }

        public static function sql_literal($val) {
            $magic_null_value = Config::$config['magic_null_value'];

            if ($magic_null_value
                && $val === $magic_null_value
            ) {
                return 'null';
            }
            elseif (is_string($val)) {
                $val = Db::quote($val);
                return $val;
            }
            elseif ($val === NULL) { return "NULL"; }
            elseif ($val === true) { return 1; }
            elseif ($val === false) { return 0; }
            else { return $val; }
        }
 
        # for e.g. an insert query, take an assoc array of key => val pairs
        # and turn it into the field list and the value list
        # e.g. for  insert into table1 (foo,bar,baz) values ('val1',2,3.0);
        public static function sql_fields_and_vals_from_array($vars) {
            $config = Config::$config;
            $hash_password_fields = (isset($config['hash_password_fields'])
                                        ? $config['hash_password_fields']
                                        : false);

            { # key list - #todo #fixme - pull out into fn
              # so we can use it in build_select_sql?
                $keys = array_keys($vars);
                $quotedKeys = array();
                foreach ($keys as $key) {
                    $quotedKeys[] = DbUtil::quote_ident($key); #todo give fn a better name
                }
                $varNameList = implode(', ', $quotedKeys);
            }

            { # val list - #todo #fixme should we use make_val_list()?
                $varValLiterals = array();
                foreach ($keys as $key) {
                    $val = $vars[$key];
                    if (is_array($val) || is_object($val)) {
                        trigger_error(
    "complex object / array passed to sql_fields_and_vals_from_array:
        key = $key,
        val = ".print_r($val,1)
                        );
                    }

                    if (isset($hash_password_fields)
                        && $hash_password_fields
                        && strpos($key, 'password') !== false
                    ) {
                        $safeVal = password_hash($val, PASSWORD_BCRYPT);
                    }
                    else {
                        $safeVal = $val;
                    }
                    $safeVal = Db::sql_literal($safeVal);
                    $varValLiterals[] = $safeVal;
                }
                $varValList = implode(', ', $varValLiterals);
            }

            return array($varNameList, $varValList);
        }

        /*
        public static function sequence_name($table, $field) {
            #todo this is just postgres, return null for mysql?
            return $table.'_'.$field.'_seq';
        }
        */

        public static function sql($query, $fetch_style = PDO::FETCH_ASSOC, $classname = null) {
            $config = Config::$config;
            /*$show_internal_result_details = isset($config['show_internal_result_details'])
                                                ? $config['show_internal_result_details']
                                                : false;*/

            $db = Db::conn();
            $result = $db->query($query);
            if (is_a($result, 'PDOStatement')) {

                if ($fetch_style == PDO::FETCH_CLASS) {
                    $rows = $result->fetchAll($fetch_style, $classname);
                }
                else {
                    $rows = $result->fetchAll($fetch_style);
                }

                return $rows;
                /*
                if (isset($show_internal_result_details)
                    && $show_internal_result_details
                ) {
                    $response = array(
                        'success' => true,
                        'rows' => $rows,
                    );
                    $response['result'] = $result;
                    $response['sql'] = $query;
                }
                else {
                    $response = $rows;
                }

                return $response;
                */

            }
            else {
                return $result;
            }
        }

        public static function sql_get1($query) {
            $rows = self::sql($query);
            if (count($rows) >= 1) {
                return $rows[0];
            }
            else {
                return null;
            }
        }
        
        public static function quote($val) {
            $db = Db::conn();
            #todo #fixme might not work for nulls?
            return $db->quote($val);
        }

        #todo #fixme this is a silly way to do this
        public static function esc($val) {
            return substr(self::quote($val), 1, strlen($val)-2);
        }

        public static function quote_ident($val) {
            return DbUtil::quote_ident($val); #todo move fn from DbUtil to here
        }


        public static function insert_row($tableName, $rowVars) {
            { # detect show_sql_query (filter out that var too)
                if (isset($rowVars['show_sql_query'])
                    && $rowVars['show_sql_query']
                ) {
                    $showSqlQuery = true;
                    unset($rowVars['show_sql_query']);
                }
                else {
                    $showSqlQuery = false;
                }
            }

            $tableNameQuoted = DbUtil::quote_ident($tableName);

            { # build sql
                # special case of no key-value pairs
                if (!count($rowVars)) {
                    if (Config::$config['db_type'] == 'pgsql') {
                        $sql = "insert into $tableNameQuoted default values";
                    }
                    else {
                        trigger_error(
                            "Db::insert_row needs at least one key-value pair for this database type",
                            E_USER_ERROR
                        );
                    }
                }
                else { # actual values passed in
                    list($varNameList, $varValList)
                        = Db::sql_fields_and_vals_from_array($rowVars);

                    $sql = "
                        insert into $tableNameQuoted ($varNameList)
                        values ($varValList)
                    ";
                }

                if (Config::$config['db_type'] == 'pgsql') {
                    $sql .= " returning * ";
                }
                $sql .= ';';
            }

            if ($showSqlQuery) {
                return array('sql'=>$sql);
            }
            else {
                $db = Db::conn();
                $result = self::sql($sql);
                return ($result || is_array($result)
                            ? $result
                            : self::error_result($sql));
            }
        }

        public static function update_rows(
            $table_name, $rowVars,
            $allowEmptyWheres = false # anti-footgun
        ) {
            if (isset($rowVars['where_clauses'])) {
                $whereClauses = $rowVars['where_clauses'];
                unset($rowVars['where_clauses']);
                if (count($whereClauses) > 0
                    || $allowEmptyWheres
                ) {

                    $sql = self::build_update_sql(
                        $table_name, $rowVars,
                        $whereClauses
                    );
                    $result = self::sql($sql);
                    return ($result || is_array($result)
                                ? $result
                                : self::error_result($sql));

                }
                else {
                    die("update_rows needs at least one where_clause, or allowEmptyWheres = true");
                }
            }
            else {
                die("can't do update_rows without where_clauses");
            }
        }

        public static function update_row($table_name, $rowVars) {
            #todo - add additional checking to make sure there's a primary key
            return self::update_rows($table_name, $rowVars);
        }

        public static function delete_rows($table_name, $rowVars, $allowEmptyWheres = false) {
            if (isset($rowVars['where_clauses'])) {
                $whereClauses = $rowVars['where_clauses'];
                unset($rowVars['where_clauses']);
                if (count($whereClauses) > 0 || $allowEmptyWheres) {

                    $sql = self::build_delete_sql($table_name, $whereClauses);
                    return self::sql($sql);
                    #todo use return instead?
                    #return ($result
                    #            ? $result
                    #            : self::error_result($sql));
                }
                else {
                    die("delete_rows needs at least one where_clause, or allowEmptyWheres = true");
                }
            }
            else {
                die("can't do delete_rows without where_clauses");
            }
        }

        # when $strict_wheres is false, look in Config for certain different operators to use
        # e.g. may use @> for arrays, or LIKE for strings, instead of strict =
        public static function build_select_sql(
            $table_name, $wheres, $select_fields=null,
            $order_by_limit=null, $strict_wheres=true
        ) {

            if ($select_fields === null) {
                $select_fields = '*';
            }
            elseif (is_array($select_fields)) {
                #todo maybe use val_list fn
                $select_fields = implode(',', $select_fields);
            }

            $table_name_quoted = DbUtil::quote_ident_or_funcall($table_name);
            $sql = "select $select_fields from $table_name_quoted ";
            $sql .= self::build_where_clause(
                $wheres, 'and', true, $strict_wheres,
                $table_name
            );
            if ($order_by_limit) {
                $sql .= "\n$order_by_limit";
            }
            #$sql .= ";"; #todo #fixme reconsider, maybe we don't want to close the query?
                          #            might want to add more where conditions for example?
                          #            though having it here might be safer
            return $sql;
        }

        # save changes of existing obj/row to db
        public static function build_update_sql(
            $table_name, $setKeyVals, $whereClauses
        ) {

            { # build sql
                $table_name_quoted = DbUtil::quote_ident($table_name);
                $sql = "update $table_name_quoted set ";

                $comma = false;
                foreach ($setKeyVals as $key => $val) {
                    if ($comma) $sql .= ",";
                    $key = DbUtil::quote_ident($key);
                    $val = Db::sql_literal($val);
                    $sql .= "\n$key = $val";
                    $comma = true;
                }
                $id_name_scheme = 'table_id'; #todo #fixme use value from Config
                $idField = self::get_id_field_name($table_name, $id_name_scheme);

                $sql .= self::build_where_clause($whereClauses);
                # return data
                if (Config::$config['db_type'] == 'pgsql') {
                    $sql .= " returning * ";
                }

                $sql .= ';';
            }

            return $sql;
        }

        public static function build_delete_sql($table_name, $whereClauses) {

            { # build sql
                $table_name_quoted = DbUtil::quote_ident($table_name);
                $sql = "delete from $table_name_quoted ";

                $sql .= self::build_where_clause($whereClauses);
                $sql .= ';';
            }

            return $sql;
        }

        public static function get_id_field_name($table_name=null, $id_type) {
            switch ($id_type) {
                case 'id_only':
                    return 'id';
                case 'table_id':
                    return $table_name . '_id';
            }
        }

        # provides url used in view_query() fn below
        public static function view_query_url($sql, $minimal=false) {
            $vars = array_merge($_GET,$_POST);
            $query_string = http_build_query(array(
                'sql' => $sql,
            ));
            $table_view_uri = Config::$config['table_view_uri'];
            $view_query_url = "$table_view_uri?$query_string";
            if ($minimal) {
                $view_query_url .= '&minimal';
            }
            return $view_query_url;
        }

        #todo move out to a Util or Web or Url class
        public static function post_redirect_via_js($url, $vars=[], $redirect_msg=null) {
?>
<!doctype html>
<html>
    <head>
    </head>
    <body>

<?php
            if ($redirect_msg) {
?>
        <p><?= $redirect_msg ?></p>
<?php
            }
?>

        <form action="<?= htmlentities($url) ?>" method="POST">
<?php
            foreach ($vars as $name => $val) {
?>
            <input type="hidden"    name="<?= htmlentities($name) ?>"
                                    value="<?= htmlentities($val) ?>"
            >
<?php
            }
?>
        </form>

        <script>
            var form = document.getElementsByTagName('form')[0];
            console.log(form);
            form.submit();
        </script>

    </body>
</html>
<?php
            die();
        }

        const MAX_URL_LENGTH = 4096; #todo #fixme just pulled this out of nowhere

        #todo move out to a Util type class
        # does a POST if necessary, if the url is too long
        public static function redirect_url($url, $vars=[], $redirect_msg=null) {
            $query_string = http_build_query($vars);
            $overall_url = "$url?$query_string";
            if (strlen($overall_url) > self::MAX_URL_LENGTH) {
                self::post_redirect_via_js($url, $vars, $redirect_msg); # dies
            }
            else {
                header("302 Temporary");
                header("Location: $overall_url");
                die();
            }
        }

        # visit (redirect) to view_query_url()
        public static function view_query($sql, $minimal=false) {
            $url = Config::$config['table_view_uri']; # self::view_query_url($sql, $minimal);

            $vars = ['sql' => $sql];
            if ($minimal) {
                $vars['minimal'] = true;
            }

            self::redirect_url($url, $vars, 'You are being redirected to view your query results'); # dies
        }

        # view_table - given a table_name and optional where_vars
        #              view those rows in the Table View
        # note: $match_aliases is Postgres only
        public static function view_table(
            $table_name, $where_vars=array(), $select_fields=null,
            $minimal=false, $strict_wheres=false,
            $match_aliases_on_name=false #todo remove arg, assume true for !$strict_wheres
        ) {
            /*
            # add aliases to where_vars (moved to Predicate::losely_interpret...
            if ($match_aliases_on_name
                && $where_vars['name']
                && Config::$config['aliases_field']
            ) {
                $name = $where_vars['name'];
                $name_quot = Db::sql_literal($name);
                $aliases_field = Config::$config['aliases_field'];
                unset($where_vars['name']);
                $aliases_pred = new Predicate(
                    '@>', 'array['.$name_quot.']',
                    false # no need to escape val
                );
                $or_clauses = new OrClauses(array(
                    'name' => $name,
                    $aliases_field => $aliases_pred,
                ));
                $where_vars[] = $or_clauses;
            }
            */

            ## if we're not calling match_aliases, we can take advantage
            ## of "looser matching" (i.e. we can have $strict_where=false)
            ## but match_aliases currently implies stricter matching
            #assert(!$match_aliases_on_name || $strict_wheres,
            #    'should not call view_table with !strict_wheres and match_aliases'
            #    .' at the same time') or die();

            # build the query
            $sql = Query::expand_tablename_into_query(
                $table_name, $where_vars, $select_fields,
                null, $strict_wheres
            );
            # go to that query in table_view
            return Db::view_query($sql, $minimal);
        }

        public static function view_row($table_name, $primary_key) {
            $primary_key_field = 'id'; #todo #fixme
            $wheres = array(
                $primary_key_field => $primary_key,
            );
            return self::view_table($table_name, $wheres);
        }

        #todo maybe allow magic null value?
        # when $strict_wheres is false, look in Config for certain different operators to use
        # e.g. may use @> for arrays, or LIKE for strings, instead of strict =
        public static function build_where_clause(
            $wheres, $logical_op='and', $include_where=true, $strict_wheres=true,
            $table=null
        ) {
            $sql = '';

            # add where clauses
            $where_and_or = ($include_where
                                ? 'where'
                                : '');
            foreach ($wheres as $key => $val_or_predicate) {

                if (!$strict_wheres) {
                    Predicate::loosely_interpret_where_clause(
                        $key, $val_or_predicate, # both & (vars may be changed)
                        $table
                    );
                }

                if ($val_or_predicate instanceof Predicate) {
                    # Predicate has a __toString() fn
                    $expr = "$key $val_or_predicate";
                }
                elseif ($val_or_predicate instanceof OrClauses) {
                    # OrClauses has a __toString() fn
                    $expr = "$val_or_predicate";
                }
                else {
                    $val = Db::sql_literal($val_or_predicate);
                    $expr = "$key = $val";
                }

                $sql .= "\n$where_and_or $expr";

                $where_and_or = "    $logical_op";

            }

            return $sql;
        }

        public static function get($table_name, $wheres, $get1=false, $strict_wheres=true) {
            $sql = self::build_select_sql(
                $table_name, $wheres, null, null, $strict_wheres
            );
            return self::query_fetch($sql, $get1);
        }

        public static function get1($table_name, $wheres) {
            return self::get($table_name, $wheres, true);
        }

        private static function query_fetch($sql, $only1=false) {
            if ($only1) {
                return self::sql_get1($sql);
            }
            else {
                return self::sql($sql);
            }
        }

        public static function make_val_list($vals) {
            $val_list = '(';
            $first_time = true;
            foreach ($vals as $val) {
                if (!$first_time) $val_list .= ',';
                $first_time = false;
                $val_list .= self::sql_literal($val);
            }
            $val_list .= ')';
            return $val_list;
        }

        # append val_to_add to the row
        # if $val_to_replace is passed, remove that from the array before adding $val_to_add
        public static function add_to_array(
            $table, $primary_key, $field_name, $val_to_add, $val_to_replace=null
        ) {
            $primary_key_field = DbUtil::get_primary_key_field($table);

            # quote identifiers
            $table_q = DbUtil::quote_ident($table);
            $field_name_q = DbUtil::quote_ident($field_name);
            $primary_key_field_q = DbUtil::quote_ident($primary_key_field);

            # quote vals
            $primary_key_q = Db::sql_literal($primary_key);
            $val_to_add_q = Db::sql_literal($val_to_add);

            if ($val_to_replace === null) {
                $new_val_expr = "array_append($field_name_q, $val_to_add_q)";
            }
            else {
                $val_to_replace_q = Db::sql_literal($val_to_replace);
                $new_val_expr = "
                    array_append(
                        array_remove($field_name_q, $val_to_replace_q),
                        $val_to_add_q
                    )
                ";
            }

            $sql = "
                update $table_q
                set $field_name_q = $new_val_expr
                where $primary_key_field_q = $primary_key_q
            ";
            return Db::sql($sql);
        }

        # remove val_to_add to the row
        # if $val_to_replace is passed, remove that from the array before adding $val_to_add
        public static function remove_from_array(
            $table, $primary_key, $field_name, $val_to_remove
        ) {
            $primary_key_field = DbUtil::get_primary_key_field($table);

            # quote identifiers
            $table_q = DbUtil::quote_ident($table);
            $field_name_q = DbUtil::quote_ident($field_name);
            $primary_key_field_q = DbUtil::quote_ident($primary_key_field);

            # quote vals
            $primary_key_q = Db::sql_literal($primary_key);
            $val_to_remove_q = Db::sql_literal($val_to_remove);

            $sql = "
                update $table_q
                set $field_name_q = array_remove($field_name_q, $val_to_remove_q)
                where $primary_key_field_q = $primary_key_q
            ";
            return Db::sql($sql);
        }

        public static function get_row_name($table_name, $primary_key) { #unused
            $primary_key_field = 'id'; #todo #fixme
            $wheres = array(
                $primary_key_field => $primary_key,
            );
            $name_field = 'name'; #todo #fixme
            $fields = array($name_field);
            $sql = self::build_select_sql($table_name, $wheres, $fields);
            $rows = Db::sql($sql);
            if (count($rows)) {
                return $rows[0][$name_field];
            }
        }

        public static function get_row_aliases($table_name, $primary_key) {
            $primary_key_field = 'id'; #todo #fixme
            $name_field = 'name'; #todo #fixme
            $aliases_field = 'aliases'; #todo #fixme
            $sql = "
                select
                    unnest(
                        array_append($aliases_field, $name_field)
                    ) alias
                from (
                    select $name_field, $aliases_field
                    from $table_name
                    where
                        $primary_key_field = ".self::sql_literal($primary_key)."
                ) t
            ";
            $rows = Db::sql($sql);
            if (count($rows)) {
                $aliases = array();
                foreach ($rows as $row) {
                    $aliases[] = $row['alias'];
                }
                return $aliases;
            }
        }


        # SQL-flavor-agnostic boolean expressions: true/false or 1/0

        public static function true_exp() {
            switch (Config::$config['db_type']) {
                case 'pgsql':
                    return 'true';

                case 'sqlite':
                case 'mysql':
                    return '1';
            }
        }

        public static function false_exp() {
            switch (Config::$config['db_type']) {
                case 'pgsql':
                    return 'false';

                case 'sqlite':
                case 'mysql':
                    return '0';
            }
        }

    }

}

