<?php
    class Util {

        public static function sql($query, $returnType='array') {
            global $db;
            $result = $db->query($query);
            if (is_a($result, 'PDOStatement')) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                return $result;
            }
        }

        public static function endsWith($needle,$haystack) {
            $len = strlen($needle);
            $LEN = strlen($haystack);
            return substr($haystack, $LEN - $len) == $needle;
        }

		public static function quote($val) {
			global $db; #todo will this global work in all cases?
			#todo #fixme might not work for nulls?
			return $db->quote($val);
		}
    }
