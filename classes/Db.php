<?php

class Db {

    public static function connectToDb() {
        global $db_type, $db_host, $db_name, $db_user, $db_password;
        $db = $GLOBALS['db'] = new PDO(
            "$db_type:host=$db_host;dbname=$db_name",
            $db_user, $db_password
            #DB_TYPE.":host=".DB_HOST.";dbname=".DB_NAME,
            #DB_USER, DB_PASSWORD
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

    public static function error($msg, $sql) {
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
        $keys = array_keys($vars);
        $varNameList = implode(', ', $keys);

        $varValLiterals = array();
        foreach ($keys as $key) {
            $val = $vars[$key];
            if (is_array($val) || is_object($val)) {
                trigger_error(
"complex object / array passed to sqlFieldsAndValsFromArray:
    key=$key,
    val = ".print_r($val,1)
                );
            }
            $varValLiterals[] = Db::sqlLiteral($val);
        }

        $varValList = implode(', ', $varValLiterals);
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
}

