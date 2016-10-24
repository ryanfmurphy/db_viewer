<?php
    #todo use Db class instead for db stuff
    class Util {

        /*
        public static function sql($query, $returnType='array', $tempMain=false) {
            global $db;
            #if ($tempMain) { die('tempMain'); }
            $result = $db->query($query);
            if (is_a($result, 'PDOStatement')) {
                $results = $result->fetchAll(PDO::FETCH_ASSOC);
                return $results;
            }
            else {
                return $result;
            }
        }
        */

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
