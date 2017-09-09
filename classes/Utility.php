<?php

if (!class_exists('Utility')) {

    class Utility {

        public static function ends_with($needle,$haystack) {
            $len = strlen($needle);
            $LEN = strlen($haystack);
            return substr($haystack, $LEN - $len) == $needle;
        }

        public static function esc_str_for_js($string) {
            $string = str_replace("'", "\\'", $string);
            return    str_replace("\n", '\n', $string);
        }

        public static function quot_str_for_js($string) {
            return "'" . self::esc_str_for_js($string) . "'";
        }

    }

}

