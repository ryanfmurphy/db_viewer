<?php
class Config {

    public static $config = null;

    # config is a regular PHP file
    # include it within the function scope and capture the $config vars
    # NOTE: file at $config_filepath must exist or there's an include warning
    public static function load_config(
        $config_filepath, $default_values=array()
    ) {
        $trunk = self::get_trunk();
        $requestVars = array_merge($_GET, $_POST);
        extract($default_values);
        include($config_filepath);

        #todo #simplify why have these 2 places?
        # maybe just get the keys from the default_values?
        $config_vars = array(
            'db_type',
            'requestVars',
            'meetup',

            'db_host',
            'db_user',
            'db_password',
            'db_name',
            # if true, user will log in using db username/password via a form
            'db_prompt_for_auth',
            'session_timeout_secs',

            'table_schemas',

            'id_mode',
            'primary_key_field',
            'id_fields_are_uuids',
            'search_path',
            'backgroundImages',
            'background_image_settings',
            'background_image_opacity',
            'background_image_method',
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
            #'fields_to_make_selects_by_table', #todo
            'fields_to_make_textarea',
            'fields_to_make_textarea_by_table',
            'header_every',
            'multipleTablesFoundInDifferentSchemas',
            'edit',
            'minimal',

            'obj_editor_uri',
            'table_view_uri',
            'tree_view_uri',
            'd3_js_uri',
            'crud_api_uri',
            'get_tree_uri',
            'prompt_for_auth_uri',

            'trunk',
            'uri_trunk',
            'pluralize_table_names',
            'special_ops',
            'js_path',
            'popr_js_path',
            'popr_css_path',
            'custom_select_magic_value',
            'magic_null_value',

            'log_path',

            'show_images',
            'image_max_width',

            'obj_editor_exclude_fields',
            'table_view_exclude_fields_by_table',


            # more table view vars

            'allow_destructive_queries',
            'disable_delete_button',

            'is_archived_field',
            'archive_instead_of_delete',

            'bold_border_between_weeks',
            'bold_border_between_days',

            # for tables/views that aren't directly updatable
            # you can give them a different table/view to update instead
            'table_aliases_for_update',


            # some special settings for when you're on a mobile device
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

            'table_field_spaces_to_underscores',

            # array fields
            'fields_w_array_type',
            'automatic_curly_braces_for_arrays',
            # MTM stands for Mobile Travel Mode
            # an array of fields that should not require commas
            # between array items, unless you provide a leading space
            'MTM_array_fields_to_not_require_commas',

            # json fields
            'fields_w_json_type',

            'fields_that_render_html',
            'fields_that_render_html_by_table',

            # color_rows_by_relname
            # ---------------------
            # Option to color rows not just by their direct row_color field
            # but based on the "relname" or child table that they come from
            # This is intended to be used with Postgres inheritance, but
            # could be adapted to work with views as well.
            #
            # Possible values: true, false, or 'from db'
            #
            # if 'from db', db_viewer will look for a field
            # called 'row_color_by_relname' in each row
            # which you will furnish in your SQL views
            #
            # if true, db_viewer will use the 'row_colors_by_relname'
            # array, looking in the key corresponding to the 'relname'
            # field of the row.
            #
            'color_rows_by_relname',
            'row_colors_by_relname',

            'db_viewer_macro_uri',

            'include_row_delete_button',
            'include_row_tree_button',

            # useful for schemas that use table inheritance
            # (especially Postgres) - if the "relname" is in
            # the row, use that as the table to look in for
            # the edit link, so you can see all the fields
            'use_relname_for_edit_link',

            # UNDER CONSTRUCTION
            # if true (default), use javascript/AJAX to
            # retrieve data that connects to existing rows
            # within the view, and dynamically extend the
            # existing table.  if false, refresh the page,
            # adding the appropriate JOIN to the query
            'do_joins_in_place',

            # array of URLs of extra javascripts to load on table_view
            'custom_js_scripts',

            # optional js function name to be called
            # when clicking a <td> data cell in table_view
            # function should take 1 arg: the event
            'custom_td_click_handler',

            'cmp',

            # tree view stuff
            'default_parent_field',
            'load_d3_via_cdn',
            'start_w_tree_fully_expanded',
            'vary_node_colors',
            'node_color_by_table',
            'do_tree_transitions',
            'default_root_table_for_tree_view',
            'tree_height_factor',
            # may be a bool or a list of tables
            'use_relname_for_tree_node_table',
            # stars field can made node text bigger or smaller
            'use_stars_for_node_size',
            # the field that will be considered the
            # "name" or display field e.g. for trees
            # ('name' by default)
            'name_fields_by_table',
            # filter fn: in case you need to strip_tags or something
            'name_field_filter_fn_by_table',
            # exceptions to your id_mode primary_key scheme go here:
            'primary_key_fields_by_table',
            # what to do if you specify 0 relationships? leave empty or assume
            'assume_default_tree_relationship',
            'tree_view_order_by_limit',
            'tree_view_relationship_order_by_limit',
            'tree_view_relationship_expression_name',
            'tree_view_relationship_expression',
            'store_tree_views_in_db',

            # filesystem-based tree (even more experimental/unstable)
            'fs_tree_default_root_dir',

            # UNDER CONSTRUCTION
            # mobile_travel_mode: when you save your locally-saved rows
            # also save a JSON file on the server of the rows,
            # (in case some of them didn't make it into the database
            #  e.g. if they reference a nonexistent table or field)
            'backup_local_storage_rows_as_json',
            'save_json_dump_uri',
            'save_json_dump_of_stored_rows',

            # obj_editor
            'include_tree_buttons',
            'obj_editor_default_tablename',
            'non_removable_form_fields',

            'recognize_numbered_id_fields',

            'edit_in_place',

            'custom_query_links',

            'obj_editor_show_image',
            'change_to_update_after_insert',
            'alt_enter_reverses_change2update_setting',

            # kanban view
            'kanban_table',
            'kanban_default_lists',
            'kanban_list_field',
            'kanban_list_name_field',
            'kanban_wheres',
            'kanban_root_level_nodes_only',
            'kanban_root_level_override_field',
        );

        $config = compact($config_vars);
        self::handle_auth($config /*&*/);
        self::$config =& $config;
        return $config;
    }

    # username/pw auth: populate config and check/populate session vars
    public static function handle_auth(&$config) {
        $session_timeout_secs = Config::$config['session_timeout_secs'];
        if ($session_timeout_secs) {
            ini_set('session.cookie_lifetime', $session_timeout_secs);
            ini_set('session.gc_maxlifetime', $session_timeout_secs);
        }

        $requestVars = array_merge($_GET, $_POST);
        if ($config['db_prompt_for_auth']) {
            if (!isset($_SESSION)) {
                session_start();
            }

            # if it's already in the SESSION, use that
            if (isset($_SESSION['db_user'])) {
                $config['db_user'] = $_SESSION['db_user'];
            }
            if (isset($_SESSION['db_password'])) {
                $config['db_password'] = $_SESSION['db_password'];
            }

            # if it's in the requestVars, use that and save in SESSION
            if (isset($requestVars['db_user'])) {
                $config['db_user'] = $_SESSION['db_user'] = $requestVars['db_user'];
            }
            if (isset($requestVars['db_password'])) {
                $config['db_password'] = $_SESSION['db_password'] = $requestVars['db_password'];
            }
        }
    }

    public static function default_values($view_uri) {
        #todo #fixme - don't require passing in view_uri
        #              the guess_uri_trunk thing is kind of brittle
        do_log("view_uri = $view_uri\n");
        $trunk = self::get_trunk();
        do_log("trunk = $trunk\n");
        $requestVars = array_merge($_GET, $_POST);
        $uri_trunk = self::guess_uri_trunk($view_uri);
        do_log("uri_trunk = $uri_trunk\n");

        $default_values = array(
            # for SQLite e.g. these should be null
            'db_user' => null,
            'db_password' => null,

            # if true, make user log in using db username/password via a form
            'db_prompt_for_auth' => false,
            'session_timeout_secs' => null,

            'table_schemas' => array(),

            # should these really be configs?
            'inferred_table' => null,
            'only_include_these_fields' => null,
            'edit' => null,

            # magic values - usually don't need to be changed
            'custom_select_magic_value' => sha1('custom'),
            'magic_null_value' => sha1('_null_'),

            # not used everywhere yet, but tree_log goes there
            'log_path' => "$trunk/log",

            # table view
            'header_every' => 15,
            'id_fields_are_uuids' => null, # neither true nor false if unspecified
            'slow_tables' => array(),
            'field_render_filters_by_table' => array(),
            'special_ops' => array(),

            'id_mode' => 'id_only', #todo is this a good default?
            'primary_key_field' => 'id',

            # obj view
            'multipleTablesFoundInDifferentSchemas' => false,
            'search_path' => null,
            'default_values_by_table' => array(),
            'fields_to_make_selects' => array(),
            #'fields_to_make_selects_by_table', #todo
            'fields_to_make_textarea' => array(),
            'fields_to_make_textarea_by_table' => array(),

            # both
            'pluralize_table_names' => false,
            'links_minimal_by_default' => false,
            'minimal_fields_by_table' => null,
            'minimal_field_inheritance' => true,
            'use_field_ordering_from_minimal_fields' => false,
            #todo #fixme - collapse this with
            # the init.php for minimal
            'minimal' => isset($requestVars['minimal'])
                            ? true : false,
            'minimal_fields' => null,

            # background
            'backgroundImages' => array(),
            'background_image_settings' => array(),
            'background' => 'light',
            # opacity: only works with
            # background_image_method = 'css3:after'
            'background_image_opacity' => 1.0,
            'background_image_method' => 'normal', # 'css3:after',

            # URI paths
            'uri_trunk' => $uri_trunk,
            'obj_editor_uri' => "$uri_trunk/obj_editor/index.php",
            'crud_api_uri' => "$uri_trunk/obj_editor/crud_api.php",
            'table_view_uri' => "$uri_trunk/table_view/index.php",
            'tree_view_uri' => "$uri_trunk/tree_view/index.php",
            'get_tree_uri' => "$uri_trunk/tree_view/get_tree.php",
            'prompt_for_auth_uri' => "$uri_trunk/auth.php",
            'd3_js_uri' => "$uri_trunk/js/d3.js",
            'js_path' => "$uri_trunk/table_view/js",
            'save_json_dump_uri' => "$uri_trunk/obj_editor/save_json_dump.php",
            'popr_js_path' => '',

            # filesystem paths
            'trunk' => $trunk,

            'show_images' => false,
            'image_max_width' => null,

            'obj_editor_exclude_fields' => array(),
            'table_view_exclude_fields_by_table' => array(),


            # more table view vars
            'allow_destructive_queries' => false,
            'disable_delete_button' => false,

            'is_archived_field' => null,
            # archive instead of delete for table_view
            # #todo apply to obj_editor delete button too
            'archive_instead_of_delete' => false,

            'bold_border_between_weeks' => false,
            'bold_border_between_days' => false,

            # for tables/views that aren't directly updatable
            # you can give them a different table/view to update instead
            'table_aliases_for_update' => array(),


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

            'table_field_spaces_to_underscores' => true, #todo maybe not be default?

            # array fields
            'fields_w_array_type' => array(),
            'automatic_curly_braces_for_arrays' => false,
            # MTM stands for Mobile Travel Mode
            # an array of fields that should not require commas
            # between array items, unless you provide a leading space
            'MTM_array_fields_to_not_require_commas' => null,

            # json fields
            'fields_w_json_type' => array(),

            'fields_that_render_html' => array(),
            'fields_that_render_html_by_table' => array(),

            'color_rows_by_relname' => false,
            'row_colors_by_relname' => array(),

            'db_viewer_macro_uri' => "$uri_trunk/table_view/db_viewer_macro.php",

            'include_row_delete_button' => true,
            'include_row_tree_button' => false,

            # useful for schemas that use table inheritance
            # (especially Postgres) - if the "relname" is in
            # the row, use that as the table to look in for
            # the edit link, so you can see all the fields
            'use_relname_for_edit_link' => true,
            # the field that will be considered the
            # "name" or display field e.g. for trees
            # ('name' by default)
            'name_fields_by_table' => array(),
            # filter fn: in case you need to strip_tags or something
            'name_field_filter_fn_by_table' => array(),
            # exceptions to your id_mode primary_key scheme go here:
            'primary_key_fields_by_table' => array(),

            # UNDER CONSTRUCTION
            # if true (default), use javascript/AJAX to
            # retrieve data that connects to existing rows
            # within the view, and dynamically extend the
            # existing table.  if false, refresh the page,
            # adding the appropriate JOIN to the query
            'do_joins_in_place' => true,

            # array of URLs of extra javascripts to load on table_view
            'custom_js_scripts' => array(),

            # optional js function name to be called
            # when clicking a <td> data cell in table_view
            # function should take 1 arg: the event
            'custom_td_click_handler' => null,

            'cmp' => false,

            # tree view stuff
            'default_parent_field' => 'parent_id',
            'load_d3_via_cdn' => false,
            'start_w_tree_fully_expanded' => false,
            'vary_node_colors' => false,
            'node_color_by_table' => array(),
            'do_tree_transitions' => true,
            'default_root_table_for_tree_view' => 'entity',
            'tree_height_factor' => 1,
            # may be a bool or a list of tables
            'use_relname_for_tree_node_table' => false,
            # stars field can made node text bigger or smaller
            'use_stars_for_node_size' => false,
            # the field that will be considered the
            # "name" or display field e.g. for trees
            # ('name' by default)
            'name_fields_by_table' => array(),
            # filter fn: in case you need to strip_tags or something
            'name_field_filter_fn_by_table' => array(),
            # exceptions to your id_mode primary_key scheme go here:
            'primary_key_fields_by_table' => array(),
            # what to do if you specify 0 relationships? leave empty or assume
            'assume_default_tree_relationship' => false,
            'tree_view_order_by_limit' => 'order by time_added desc',
            'tree_view_relationship_order_by_limit' => null,
            'tree_view_relationship_expression_name' => null,
            'tree_view_relationship_expression' => null,
            'store_tree_views_in_db' => false,

            # filesystem-based tree (even more experimental/unstable)
            'fs_tree_default_root_dir' => null,

            # mobile_travel_mode: when you save your locally-saved rows
            # also save a JSON file on the server of the rows,
            # (in case some of them didn't make it into the database
            #  e.g. if they reference a nonexistent table or field)
            'backup_local_storage_rows_as_json' => false,
            'save_json_dump_of_stored_rows' => false,

            # obj_editor
            'include_tree_buttons' => false,
            'obj_editor_default_tablename' => 'note',
            'non_removable_form_fields' => array(),

            'recognize_numbered_id_fields' => true,

            'edit_in_place' => false,

            'custom_query_links' => null,

            'obj_editor_show_image' => false,
            'change_to_update_after_insert' => true,
            'alt_enter_reverses_change2update_setting' => true,

            # kanban view
            'kanban_table' => 'todo',
            'kanban_default_lists' => array(),
            'kanban_list_field' => 'kanban_list',
            'kanban_list_name_field' => null, # defaults to same as kanban_list_field
            'kanban_wheres' => array(),
            'kanban_root_level_nodes_only' => true,
            # allows a node to be considered "root" enough to show up even though it has a parent
            'kanban_root_level_override_field' => null,
        );

        return $default_values;
    }

    public static function get_trunk() {
        return dirname(__DIR__);
    }

    # try to figure out where db_viewer is installed URI-wise
    # so that we can make our asset links robust and flexible
    public static function guess_uri_trunk($current_view_uri) {
        #todo #fixme - this is brittle - get uri_trunk in a smarter / more solid way
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $regex = "#(.*)$current_view_uri(\?.*)?$#";
            do_log("regex = '$regex'\n");
            if (preg_match($regex, $uri, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

}
?>
