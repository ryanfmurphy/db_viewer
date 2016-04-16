<?php
    require_once('db-config.php');
    $db = mysqli_connect(
        $db_host, $db_user, $db_password,
        $db_name #, $db_port
    );

    class Util {

        public static function sql($query, $returnType='array') {
            global $db;
            $result = mysqli_query($db, $query);
            $rows = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
            return $rows;
        }

        public static function choose_table_and_field($field_name) {
            $root = substr($field_name, 0, -3);
            $suffix = substr($field_name, -3);
            return array(
                'table' => $root,
                'field' => $field_name,
            );
        }

    }

    #$jquery_url = "/js/jquery.min.js"; #todo #fixme give cdn url by default
?>
