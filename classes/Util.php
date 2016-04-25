<?php
    class Util {

        public static function sql($query, $returnType='array') {
            global $db;
            return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        }

        public static function endsWith($needle,$haystack) {
            $len = strlen($needle);
            $LEN = strlen($haystack);
            return substr($haystack, $LEN - $len) == $needle;
        }
    }
