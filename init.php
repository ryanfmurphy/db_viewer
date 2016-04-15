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
    }

    #$jquery_url = "/js/jquery.min.js"; #todo #fixme give cdn url by default
?>
