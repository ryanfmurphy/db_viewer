<?php

if (!class_exists('EditorBackend')) {

    class EditorBackend {

        public static $default_minimal_fields = array(
            "name", "txt", "tags",
        );

        public static function crud_api_uri($vars) {
            $base_uri = Config::$config['crud_api_uri'];
            return "$base_uri?" . http_build_query($vars);
        }

        public static function process_array_fields(&$vars) {
            foreach ($vars as $field_name => $field_val) {
                $do_add_commas_this_field = (
                    in_array($field_name, Config::$config['MTM_array_fields_to_not_require_commas'])
                    && strlen($field_val) > 0
                    && strpos($field_val, ',') === false
                    && $field_val[0] != '{' #todo check end brace too?
                    && $field_val[0] != ' '
                );

                if ($do_add_commas_this_field) {
                    $vars[$field_name] = self::array_field_add_commas($field_val);
                }
            }
            # modified $vars in place, result isn't returned
        }

        public static function array_field_add_commas($field_val) {
            return preg_replace(
                        # can escape spaces, but other than that, spaces get a comma
                        [         '/\\\\ /', '/\s+/', '/<SPACE_NO_COMMA>/'],
                        ['<SPACE_NO_COMMA>',    ', ',                  ' '],
                        $field_val
                   );
        }

    }

}
