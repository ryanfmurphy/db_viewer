<?php

if (!class_exists('Db')) {

    class Db {

        public static function connectToDb() {
            global $db_type, $db_host, $db_name, $db_user, $db_password;
            #todo will this global work in all cases?
            $db = $GLOBALS['db'] = new PDO(
                "$db_type:host=$db_host;dbname=$db_name",
                $db_user, $db_password
            );
            return $db;
        }

        # (cached) connection to db
        public static function conn() {
            $db = ( isset($GLOBALS['db'])
                       ? $GLOBALS['db']
                       : Db::connectToDb() );
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

        public static function sqlLiteral($val) {
            if (is_string($val)) {
                $db = Db::conn();
                $val = $db->quote($val);
                return $val;
            }
            elseif ($val === NULL) { return "NULL"; }
            elseif ($val === true) { return 1; }
            elseif ($val === false) { return 0; }
            else { return $val; }
        }

        public static function sqlFieldsAndValsFromArray($vars) {
            { # key list
                $keys = array_keys($vars);
                $varNameList = implode(', ', $keys);
            }

            { # val list
                $varValLiterals = array();
                foreach ($keys as $key) {
                    $val = $vars[$key];
                    if (is_array($val) || is_object($val)) {
                        trigger_error(
    "complex object / array passed to sqlFieldsAndValsFromArray:
        key = $key,
        val = ".print_r($val,1)
                        );
                    }
                    $varValLiterals[] = Db::sqlLiteral($val);
                }
                $varValList = implode(', ', $varValLiterals);
            }

            return array($varNameList, $varValList);
        }

        public static function sequenceName($table, $field) {
            #todo this is just postgres, return null for mysql?
            return $table.'_'.$field.'_seq';
        }

        public static function sql($query) {
            $db = Db::conn();
            $result = $db->query($query);
            if (is_a($result, 'PDOStatement')) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                return $result;
            }
        }
        
        public static function quote($val) {
            $db = Db::conn();
            #todo #fixme might not work for nulls?
            return $db->quote($val);
        }


        #todo #fixme - halfway through moving some of the
        # core Model functionality into Db.
        # goal is to remove all the weird $ClassName crap from Model
        # and allow the MetaController to function without Model Objects

        public static function insertRow($tableName, $rowVars) {
            #todo work around limitation of needing at least 1 kv pair
            # e.g. postgres will do:
            #   insert into my_table default values
            if (!count($rowVars)) {
                trigger_error(
                    "Db::insertRow needs at least one key-value pair",
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
                = Db::sqlFieldsAndValsFromArray($rowVars);

            $sql = "
                insert into $tableName ($varNameList)
                values ($varValList);
            ";

            if ($showSqlQuery) {
                return array('sql'=>$sql);
            }
            else {
                $db = Db::conn();
                $result = $db->query($sql);
                return $result;
            }
        }

        public static function updateRow($table_name, $rowVars) {
            if (isset($rowVars['where_clauses'])) {
                $whereClauses = $rowVars['where_clauses'];
                unset($rowVars['where_clauses']);
                $sql = self::buildUpdateSql($table_name, $rowVars, $whereClauses);
                return self::queryFetch($sql);
            }
            else {
                die("can't do updateRow without where_clauses");
            }
        }

        public static function deleteRow($table_name, $rowVars) {
            if (isset($rowVars['where_clauses'])) {
                $whereClauses = $rowVars['where_clauses'];
                unset($rowVars['where_clauses']);
                $sql = self::buildDeleteSql($table_name, $rowVars, $whereClauses);
                die($sql);
                return self::sql($sql);
                #return self::queryFetch($sql);
            }
            else {
                die("can't do updateRow without where_clauses");
            }
        }

        public static function buildSelectSql($table_name, $wheres) {
            $sql = "select * from $table_name";
            $sql .= self::buildWhereClause($wheres);
            $sql .= ";";
            return $sql;
        }

        # save changes of existing obj/row to db
        public static function buildUpdateSql($table_name, $setKeyVals, $whereClauses) {

            { # build sql
                $sql = "update $table_name set ";

                $comma = false;
                foreach ($setKeyVals as $key => $val) {
                    if ($comma) $sql .= ",";
                    $val = Db::sqlLiteral($val);
                    $sql .= "\n$key = $val";
                    $comma = true;
                }
                $id_name_scheme = 'table_id'; #todo
                $idField = self::getIdFieldName($table_name, $id_name_scheme);

                $sql .= self::buildWhereClause($whereClauses);
                $sql .= ';';
            }

            return $sql;
        }

        public static function buildDeleteSql($table_name, $whereClauses) {

            { # build sql
                $sql = "delete from $table_name ";

                $sql .= self::buildWhereClause($whereClauses);
                $sql .= ';';
            }

            return $sql;
        }

        private static function getIdFieldName($table_name=null, $id_type) {
            switch ($id_type) {
                #return 'iid';
                case 'id_only':
                    return 'id';
                case 'table_id':
                    return $table_name . '_id';
            }
        }

        private static function viewQuery($sql) {
            $vars = requestVars();
            $query_string = http_build_query(array(
                'sql' => $sql,
            ));
            $db_viewer_url = "/db_viewer/db_viewer.php?$query_string";
            header("302 Temporary");
            header("Location: $db_viewer_url");
        }

        public static function viewTable($table_name, $whereVars=array()) {
            $sql = self::buildSelectSql($table_name, $whereVars);
            return Db::viewQuery($sql);
        }

        public static function buildWhereClause($wheres) {
            $sql = '';

            # add where clauses
            $where_or_and = 'where';
            foreach ($wheres as $key => $val) {
                $val = Db::sqlLiteral($val);
                $sql .= "\n$where_or_and $key = $val";
                $where_or_and = '    and';
            }

            return $sql;
        }

        public static function get($table_name, $wheres) {
            $sql = self::buildSelectSql($table_name, $wheres);
            return self::queryFetch($sql);
        }

        private static function queryFetch($sql, $only1=false) {
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

