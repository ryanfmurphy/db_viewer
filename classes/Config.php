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
            'obj_editor_uri',
            'table_view_uri',
            'crud_api_uri',
            'trunk',
            'uri_trunk',
            'pluralize_table_names',
            'special_ops',
            'js_path',
            'popr_js_path',
            'popr_css_path',
            'custom_select_magic_value',
            'magic_null_value',

            'show_images',
            'image_max_width',

            'obj_editor_exclude_fields',

            'allow_destructive_queries',
            'disable_delete_button',

            'is_archived_field',

            'bold_border_between_weeks',
            'bold_border_between_days',


            # some special settings for if you're on a mobile device
            # that is accessing e.g. a locally hosted DB Viewer yet
            # is not always connected to it - you can leave a form open
            # and type your notes and save them whenever you are connected
            # again and ready to save
            'mobile_travel_mode',

            # obj_editor, when clicking on table and typing a new table name
            # should it reload or just keep the existing form?
            # alt toggles "no reload", but you can reverse it so it's the default
            # which is useful if you need the behavior on Mobile for instance
            'need_alt_for_no_reload',

            'row_colors',

            'table_spaces_to_underscores',

            'automatic_curly_braces_for_arrays',
            'fields_w_array_type',

            'color_rows_by_relname',
            'row_colors_by_relname',
        );
        $config = compact($config_vars);
        self::$config =& $config;
        return $config;
    }

    public static function default_values($view_uri) {
        do_log("view_uri = $view_uri\n");
        $trunk = self::get_trunk();
        do_log("trunk = $trunk\n");
        $requestVars = array_merge($_GET, $_POST);
        $uri_trunk = self::guess_uri_trunk($view_uri);
        do_log("uri_trunk = $uri_trunk\n");
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
            'obj_editor_uri' => "$uri_trunk/obj_editor/index.php",
            'crud_api_uri' => "$uri_trunk/obj_editor/crud_api.php",
            'table_view_uri' => "$uri_trunk/table_view/index.php",
            'js_path' => "$uri_trunk/table_view/js",
            'popr_js_path' => '',

            # filesystem paths
            'trunk' => $trunk,

            'show_images' => false,
            'image_max_width' => null,

            'obj_editor_exclude_fields' => array(),

            'allow_destructive_queries' => false,
            'disable_delete_button' => false,

            'is_archived_field' => null,

            'bold_border_between_weeks' => false,
            'bold_border_between_days' => false,

            # some special settings for if you're on a mobile device
            # that is accessing e.g. a locally hosted DB Viewer yet
            # is not always connected to it - you can leave a form open
            # and type your notes and save them whenever you are connected
            # again and ready to save
            'mobile_travel_mode' => false,

            # obj_editor, when clicking on table and typing a new table name
            # should it reload or just keep the existing form?
            # alt toggles "no reload", but you can reverse it so it's the default
            # which is useful if you need the behavior on Mobile for instance
            # NOTE: gets set to false for mobile_travel_mode=true
            'need_alt_for_no_reload' => true,

            'row_colors' => false,

            'table_spaces_to_underscores' => true, #todo maybe not be default?

            'automatic_curly_braces_for_arrays' => false,
            'fields_w_array_type' => null,

            'color_rows_by_relname' => false,
            'row_colors_by_relname' => array(),
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
        $regex = "#(.*)$current_view_uri(\?.*)?$#";
        do_log("regex = '$regex'\n");
        if (preg_match($regex, $uri, $matches)) {
            return $matches[1];
        }
        else {
            return null;
        }
    }

}
?>
