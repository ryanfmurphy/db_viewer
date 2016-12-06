<?php
class Config {

    public static $config = null;

    # config is a regular PHP file
    # include it within the function scope and capture the $config vars
    public static function load_config($config_filepath, $trunk, $default_values=array()) {
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
            'dash_links',
            'table_plural',
            'minimal_fields',
            'minimal_fields_by_table',
            'minimal_field_inheritance',
            'use_field_ordering_from_minimal_fields',
            'links_minimal_by_default',
            'default_values_by_table',
            'fields_to_make_selects',
            'header_every',
            'multipleTablesFoundInDifferentSchemas',
            'edit',
            'minimal',
            'js_path',
            'dash_path',
            'db_viewer_path',
            'crud_api_path',
            'trunk',
            'pluralize_table_names',
            'special_ops',
            'poprJsPath',
            'popr_css_path',
            'custom_select_magic_value',
            'fields_to_make_selects',
        );
        $config = compact($config_vars);
        self::$config =& $config;
        return $config;
    }

    public static function default_values($trunk) {
        return array(
            # should these really be configs?
            'inferred_table' => null,
            'only_include_these_fields' => null,
            'edit' => null,
            'custom_select_magic_value' => sha1('custom'),

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

            # both
            'pluralize_table_names' => false,
            'backgroundImages' => array(),
            'background_image_settings' => array(),
            'links_minimal_by_default' => false,
            'minimal_field_inheritance' => true,
            'use_field_ordering_from_minimal_fields' => false,
            'minimal' => isset($requestVars['minimal']) ? true : false,
            'minimal_fields' => null,

            # URI paths
            'js_path' => '/db_viewer/js',
            'dash_path' => '/dash/index.php',
            'crud_api_path' => "/dash/crud_api.php",
            'poprJsPath' => '',

            # filesystem paths
            'db_viewer_path' => __DIR__,
            'trunk' => $trunk,
        );
    }

}
?>
