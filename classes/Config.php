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
            'table_view_checkboxes',
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

            'default_values',
            'default_values_by_table',

            'fields_to_make_selects',
            #'fields_to_make_selects_by_table', #todo
            'fields_to_make_textarea',
            'fields_to_make_textarea_by_table',
            'header_every',
            'multipleTablesFoundInDifferentSchemas',
            'edit',
            'minimal',

            'hostname',
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
            'exclude_fields_by_table',


            # more table view vars

            'allow_destructive_queries',
            'disable_delete_button',
            'table_view_tuck_away_query_box',
            'table_view_show_count',
            'query_form_http_method',
            'entity_view_table',
            'default_order_by',
            'default_order_by_by_table',
            'table_view_text_max_len', # if a positive number, cut off text fields with ... after that many chars


            # archive / soft delete
                # designate a field as the universal "is_archived" field
                # when set, Table Views of a table will automatically add
                # "and <fieldname> = false" to the query
            'is_archived_field',
            'tables_without_is_archived_field', # can be an array of table names
                # archive instead of delete for table_view
                # #todo apply to obj_editor delete button too
            'archive_instead_of_delete',

            'aliases_field',
            'is_private_field',
            'show_private_rows', # only does anything if it's false and is_private_field is set

            'bold_border_between_weeks',
            'date_header_between_days',

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

            # field types
            'fields_w_array_type',
            'fields_w_json_type',
            'fields_w_text_type',
            'fields_w_tsvector_type',

            # array fields
            'automatic_curly_braces_for_arrays',
            # MTM stands for Mobile Travel Mode
            # an array of fields that should not require commas
            # between array items, unless you provide a leading space
            'MTM_array_fields_to_not_require_commas',   #todo #fixme - rename this setting to, say, 'array_fields_dont_require_commas'
                                                        # it turns out to be useful regardless of MTM
            # default to postgres @> instead of =
            # when doing where clauses on array fields
            'use_include_op_for_arrays',
            'use_like_op_for_text',
            'like_op_use_lower',
            'use_fulltext_op_for_tsvector',

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

            'enable_objlinks_in_fields',

            'cmp',

            # tree view stuff
            'default_parent_field',
            #'default_matching_field_on_parent', #todo #fixme
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
            'name_field',
            'backup_name_field',
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
            'add_child__interpret_complex_table_as_name',
            'default_tree_relationship_condition',
            'show_matching_rows_on_tree_sideline',
            'sideline_addl_requirements',
            'tree_view_include_header',
            'tree_view_custom_header',
            'tree_view_show_default_header_too',
            # if this is an array of Names of Popup Options,
            # those will be the only ones included in Tree View popup menu
            'tree_view_filter_popup_options',
            'tree_view_include_fields_by_table',
            # optional js function with a single arg d (a d3 node)
            # returns a css color for the node to be
            'custom_tree_node_color_fn',
            # how many chars of tree node name to show before ...?
            'tree_node_name_cutoff',
            'tree_view_custom_header_from_root_id',
            'tree_view_max_levels',

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

            'url_field_links', # e.g. links at top of object editor
            'obj_editor_show_notes', # list any notes on this obj
            'obj_editor_note_table', # which table to draw from to show notes
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
            'primary_key_field' => 'id', #todo #fixme make sure this doesn't conflict with id_mode

            'table_view_checkboxes' => false,

            # obj view
            'multipleTablesFoundInDifferentSchemas' => false,
            'search_path' => null,
            'default_values' => array(),
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
            'hostname' => null,
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
            'exclude_fields_by_table' => array(),


            # more table view vars
            'allow_destructive_queries' => false,
            'disable_delete_button' => false,
            'table_view_tuck_away_query_box' => false,
            'table_view_show_count' => false,
            'query_form_http_method' => 'post',
            'entity_view_table' => null,
            'default_order_by' => 'time_added desc',
            'default_order_by_by_table' => [],
            'table_view_text_max_len' => null, # if a positive number, cut off text fields with ... after that many chars

            # archive / soft delete
                # designate a field as the universal "is_archived" field
                # when set, Table Views of a table will automatically add
                # "and <fieldname> = false" to the query
            'is_archived_field' => null,
            'tables_without_is_archived_field' => null, # can be an array of table names
                # archive instead of delete for table_view
                # #todo apply to obj_editor delete button too
            'archive_instead_of_delete' => false,

            'aliases_field' => null,
            'is_private_field' => null,
            'show_private_rows' => true, # only does anything if it's false and is_private_field is set

            'bold_border_between_weeks' => false,
            'date_header_between_days' => false,

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

            # field types
            'fields_w_array_type' => array(),
            'fields_w_json_type' => array(),
            'fields_w_text_type' => array(),
            'fields_w_tsvector_type' => array(),

            # array fields
            'automatic_curly_braces_for_arrays' => false,
            # MTM stands for Mobile Travel Mode
            # an array of fields that should not require commas
            # between array items, unless you provide a leading space
            'MTM_array_fields_to_not_require_commas' => null,
            # default to postgres @> instead of =
            # when doing where clauses on array fields
            'use_include_op_for_arrays' => false,
            'use_like_op_for_text' => false,
            # when using LIKE '%%' in building where clauses,
            # wrap both sides with lower() fn?
            'like_op_use_lower' => false,
            # postgres fulltext search
            # use postgres "@@" symbol when searching by column w type tsvector
            'use_fulltext_op_for_tsvector' => false,

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
            'name_field' => 'name', #todo #fixme this may not be always paid attn to
            'backup_name_field' => 'txt',
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

            'enable_objlinks_in_fields' => array(),

            'cmp' => false,

            # tree view stuff
            'default_parent_field' => 'parent_ids',
            #'default_matching_field_on_parent' => 'id', #todo #fixme
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
            'tree_view_relationship_expression_name' => null,
            'tree_view_relationship_expression' => null,
            'store_tree_views_in_db' => false,
            'add_child__interpret_complex_table_as_name' => false,
            'default_tree_relationship_condition' => null,
            'show_matching_rows_on_tree_sideline' => false,
            'sideline_addl_requirements' => "parent_ids = '{}'",
            'tree_view_include_header' => true,
            'tree_view_custom_header' => null,
            'tree_view_show_default_header_too' => false,
            # if this is an array of Names of Popup Options,
            # those will be the only ones included in Tree View popup menu
            'tree_view_filter_popup_options' => null,
            # (optional) an array keyed by table, whose values are
            # arrays of field names to get into tree_view nodes
            'tree_view_include_fields_by_table' => null,
            # optional js function with a single arg d (a d3 node)
            # returns a css color for the node to be
            'custom_tree_node_color_fn' => null,
            # how many chars of tree node name to show before ...?
            'tree_node_name_cutoff' => 50,
            'tree_view_order_by_limit' => 'order by time_added desc', #todo should this really be default?
            'tree_view_relationship_order_by_limit' => null,
            'tree_view_custom_header_from_root_id' => true,
            'tree_view_max_levels' => null,

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

            # keys are names of link, values are urls
            'url_field_links' => array('URL' => 'url'),
            'obj_editor_show_notes' => false, # list any note on this obj
            'obj_editor_note_table' => 'entity', # which table to draw from to show notes
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
