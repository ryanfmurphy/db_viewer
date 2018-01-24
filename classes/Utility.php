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
            if ($string === null
                || $string === false
                || $string === 0
            ) {
                return 'undefined';
            }
            else {
                return "'" . self::esc_str_for_js($string) . "'";
            }
        }

        # thanks thomas http://php.net/manual/en/function.parse-url.php
        public static function unparse_url($parsed_url) {
          $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
          $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
          $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
          $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
          $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
          $pass     = ($user || $pass) ? "$pass@" : '';
          $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
          $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
          $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
          return "$scheme$user$pass$host$port$path$query$fragment";
        } 

        public static function escape_vars_in_url($url) {
            $url_parts = parse_url($url);
            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $query_vars /*&*/);
                $url_parts['query'] = http_build_query($query_vars);
            }
            return Utility::unparse_url($url_parts);
        }

    }

}

