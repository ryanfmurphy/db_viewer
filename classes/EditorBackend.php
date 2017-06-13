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

    }

}
