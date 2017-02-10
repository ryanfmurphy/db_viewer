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
            $db = self::$db = new PDO(
                "$db_type:host=$db_host;dbname=$db_name",
                $db_user, $db_password
            );
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

            { # key list
                $keys = array_keys($vars);
                $quotedKeys = array();
                foreach ($keys as $key) {
                    $quotedKeys[] = DbUtil::quote_tablename($key); #todo give fn a better name
                }
                $varNameList = implode(', ', $quotedKeys);
            }

            { # val list
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

        public static function sql($query) {
            $config = Config::$config;
            $show_internal_result_details = isset($config['show_internal_result_details'])
                                                ? $config['show_internal_result_details']
                                                : false;

            $db = Db::conn();
            $result = $db->query($query);
            if (is_a($result, 'PDOStatement')) {

                $rows = $result->fetchAll(PDO::FETCH_ASSOC);

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

        public static function esc($val) {
            return substr(self::quote($val), 1, strlen($val)-2);
        }


        public static function insert_row($tableName, $rowVars) {
            #todo work around limitation of needing at least 1 kv pair
            # e.g. postgres will do:
            #   insert into my_table default values
            if (!count($rowVars)) {
                trigger_error(
                    "Db::insert_row needs at least one key-value pair",
                    E_USER_ERROR
                );
            }

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

            list($varNameList, $varValList)
                = Db::sql_fields_and_vals_from_array($rowVars);

            $tableNameQuoted = DbUtil::quote_tablename($tableName);
            $sql = "
                insert into $tableNameQuoted ($varNameList)
                values ($varValList);
            ";

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
            $allowEmptyWheres = false
        ) {
            if (isset($rowVars['where_clauses'])) {
                $whereClauses = $rowVars['where_clauses'];
                unset($rowVars['where_clauses']);
                if (count($whereClauses) > 0 || $allowEmptyWheres) {

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

        public static function build_select_sql($table_name, $wheres, $select_fields=null) {

            if ($select_fields === null) {
                $select_fields = '*';
            }
            elseif (is_array($select_fields)) {
                #todo maybe use val_list fn
                $select_fields = implode(',', $select_fields);
            }

            $table_name_quoted = DbUtil::quote_tablename($table_name);
            $sql = "select $select_fields from $table_name_quoted ";
            $sql .= self::build_where_clause($wheres);
            $sql .= ";";
            return $sql;
        }

        # save changes of existing obj/row to db
        public static function build_update_sql(
            $table_name, $setKeyVals, $whereClauses
        ) {

            { # build sql
                $table_name_quoted = DbUtil::quote_tablename($table_name);
                $sql = "update $table_name_quoted set ";

                $comma = false;
                foreach ($setKeyVals as $key => $val) {
                    if ($comma) $sql .= ",";
                    $val = Db::sql_literal($val);
                    $sql .= "\n$key = $val";
                    $comma = true;
                }
                $id_name_scheme = 'table_id'; #todo
                $idField = self::get_id_field_name($table_name, $id_name_scheme);

                $sql .= self::build_where_clause($whereClauses);
                $sql .= ';';
            }

            return $sql;
        }

        public static function build_delete_sql($table_name, $whereClauses) {

            { # build sql
                $table_name_quoted = DbUtil::quote_tablename($table_name);
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

        public static function view_query($sql, $minimal=false) {
            header("302 Temporary");
            header("Location: ".self::view_query_url($sql, $minimal));
        }

        public static function viewTable(
            $table_name, $whereVars=array(),
            $selectFields=null, $minimal=false
        ) {

            $sql = self::build_select_sql(
                $table_name, $whereVars, $selectFields);

            return Db::view_query($sql, $minimal);
        }

        #todo maybe allow magic null value?
        public static function build_where_clause($wheres) {
            $sql = '';

            # add where clauses
            $where_or_and = 'where';
            foreach ($wheres as $key => $val) {
                $val = Db::sql_literal($val);
                $sql .= "\n$where_or_and $key = $val";
                $where_or_and = '    and';
            }

            return $sql;
        }

        public static function get($table_name, $wheres) {
            $sql = self::build_select_sql($table_name, $wheres);
            return self::query_fetch($sql);
        }

        private static function query_fetch($sql, $only1=false) {
            $rows = self::sql($sql);
            if ($only1) {
                return (count($rows)
                            ? $rows[0]
                            : null);
            }
            else {
                return $rows;
            }
        }

    }

}

