<?php
class Config {

    public static $config = null;

    # config is a regular PHP file
    # include it within the function scope and capture the $config vars
    # NOTE: file at $config_filepath must exist or there's an include warning
    public static function load_config($config_filepath, $default_values=array()) {
        $trunk = self::get_trunk();
        $requestVars = array_merge($_GET, $_POST);
        extract($default_values);
        include($config_filepath);
        $config_vars = array(
            'db_type',
            'requestVars',
            'meetup',
            'db_host',
            'db_user',
            'db_password',
            'db_name',
            'id_mode',
            'id_fields_are_uuids',
            'search_path',
            'backgroundImages',
            'background_image_settings',
            'background',
            'field_render_filters_by_table',
            'special_ops',
            'slow_tables',
            'table_plural',
            'minimal_fields',
            'minimal_fields_by_table',
            'minimal_field_inheritance',
            'use_field_ordering_from_minimal_fields',
            'links_minimal_by_default',
            'default_values_by_table',
            'fields_to_make_selects',
            'fields_to_make_textarea',
            'header_every',
            'multipleTablesFoundInDifferentSchemas',
            'edit',
            'minimal',
            'js_path',
            'obj_editor_uri',
            'table_view_uri',
            'crud_api_uri',
            'trunk',
            'uri_trunk',
            'pluralize_table_names',
            'special_ops',
            'poprJsPath',
            'popr_css_path',
            'custom_select_magic_value',
            'magic_null_value',
        );
        $config = compact($config_vars);
        self::$config =& $config;
        return $config;
    }

    public static function default_values($view_uri) {
        $trunk = self::get_trunk();
        $requestVars = array_merge($_GET, $_POST);
        $uri_trunk = self::guess_uri_trunk($view_uri);
        $default_values = array(
            # should these really be configs?
            'inferred_table' => null,
            'only_include_these_fields' => null,
            'edit' => null,

            # magic values - usually don't need to be changed
            'custom_select_magic_value' => sha1('custom'),
            'magic_null_value' => sha1('_null_'),

            # table view
            'header_every' => 15,
            'id_fields_are_uuids' => null, # neither true nor false if unspecified
            'slow_tables' => array(),
            'field_render_filters_by_table' => array(),
            'special_ops' => array(),

            # obj view
            'multipleTablesFoundInDifferentSchemas' => false,
            'search_path' => null,
            'default_values_by_table' => array(),
            'fields_to_make_selects' => array(),
            'fields_to_make_textarea' => array(),

            # both
            'pluralize_table_names' => false,
            'backgroundImages' => array(),
            'background_image_settings' => array(),
            'background' => 'light',
            'links_minimal_by_default' => false,
            'minimal_field_inheritance' => true,
            'use_field_ordering_from_minimal_fields' => false,
            #todo #fixme - collapse this with
            # the init.php for minimal
            'minimal' => isset($requestVars['minimal'])
                            ? true : false,
            'minimal_fields' => null,

            # URI paths
            'uri_trunk' => $uri_trunk,
            'js_path' => "$uri_trunk/table_view/js",
            'obj_editor_uri' => "$uri_trunk/obj_editor/index.php",
            'crud_api_uri' => "$uri_trunk/obj_editor/crud_api.php",
            'table_view_uri' => "$uri_trunk/table_view/index.php",
            'poprJsPath' => '',

            # filesystem paths
            'trunk' => $trunk,
        );
        return $default_values;
    }

    public static function get_trunk() {
        return dirname(__DIR__);
    }

    # try to figure out where db_viewer is installed URI-wise
    # so that we can make our asset links robust and flexible
    public static function guess_uri_trunk($current_view_uri) {
        $uri = $_SERVER['REQUEST_URI'];
        if (preg_match("#(.*)$current_view_uri(\?.*)?$#", $uri, $matches)) {
            return $matches[1];
        }
        else {
            return null;
        }
    }

}
?>
