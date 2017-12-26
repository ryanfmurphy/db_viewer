<?php
    # vars.php
    # --------
    # create vars needed for tree_view
    # used for both frontend (index.php)
    # and backend (get_tree*.php)

    if (isset($requestVars['root_id'])) {
        $root_id = $requestVars['root_id'];
    }
    else {
        $root_id = null;
        # if passed in root_table & root_name, figure out root_id
        if (isset($requestVars['root_table'])
            && isset($requestVars['root_name'])
        ) {
            $root_row = Db::get1(
                $requestVars['root_table'],
                array( # wheres
                    'name' => $requestVars['root_name'], #todo #fixme use name_field
                )
            );
            $root_id = $root_row[
                Config::$config['primary_key_field']
            ];
            # change back to generic table
            # so default relationships will work
            $requestVars['root_table'] =
                Config::$config['default_root_table_for_tree_view'];
        }
    }

    $infer_vars_from_backend_url = false;

    # if the root_id was passed in (or derived above),
    # get the tree_url for that row from the DB
    # and parse it out into the $_GET vars
    if ($root_id) {
        if (isset($requestVars['use_default_view'])) {
            $tree_url = TreeView::get_default_tree_url($root_id);
        }
        else {
            $tree_url = TreeView::get_full_tree_url($root_id);
        }

        $infer_vars_from_backend_url = true;
    }
    elseif ( # only root_cond - fill in default tree url
        isset($requestVars['root_cond'])
        && count($requestVars) == 1
    ) {
        $tree_url = TreeView::get_default_tree_url_for_root_cond(
                        $requestVars['root_cond']
                    );
        $infer_vars_from_backend_url = true;
    }

    if ($infer_vars_from_backend_url) {
        if (isset($requestVars['show_tree_url'])) {
            echo $tree_url;
        }

        { # get requestVars from url
            $query_str = parse_url($tree_url, PHP_URL_QUERY);
            parse_str($query_str, $new_url_vars);
            $requestVars = array_merge( #todo: array_merge_recursive_seq(
                $new_url_vars, $requestVars
            );
        }
    }


    # temporarily override / adjust Config vars
    if (isset($requestVars['tree_height_factor'])
        && $requestVars['tree_height_factor'] !== ''
    ) {
        Config::$config['tree_height_factor'] = $requestVars['tree_height_factor'];
    }

    function null_relationship() {
        return array(
            'child_table' => null,
            'parent_table' => null,
            'parent_field' => null,
            'matching_field_on_parent' => null,
            'condition' => null,
            'order_by_limit' => null, #Config::$config['tree_view_relationship_order_by_limit'],
            'parent_filter_field' => null,
            'parent_filter_field_val' => null,

            #todo #fixme should allow more than one expression per relationship
            'expression_name' => null,
            'expression' => null,
        );
    }

    # An associative array with keys that are var names
    # and values that are default values.
    # These vars will be used throughout Tree View
    # as the request variables.
    $tree_var_names_w_default_values = array(

        'backend' => isset($requestVars['backend'])
                        ? $requestVars['backend']
                        : 'db',

        'root_table' => isset($requestVars['root_table'])
                            ? $requestVars['root_table']
                            : null,

        'root_cond' => isset($requestVars['root_cond'])
                       && $requestVars['root_cond'] !== ''
                            ? $requestVars['root_cond']
                            : "$default_parent_field is null",

        'order_by_limit' => isset($requestVars['order_by_limit'])
                                ? $requestVars['order_by_limit']
                                : Config::$config['tree_view_order_by_limit'],

        'root_nodes_w_child_only' => isset($requestVars['root_nodes_w_child_only'])
                                        ? $requestVars['root_nodes_w_child_only']
                                        : null,

        'parent_relationships' => isset($requestVars['parent_relationships'])
                                    ? $requestVars['parent_relationships']
                                    : ($assume_default_tree_relationship
                                        ? array(
                                            array_merge(
                                                null_relationship(),
                                                array(
                                                    'child_table' => $root_table,
                                                    'parent_table' => $root_table,
                                                    'parent_field' => $default_parent_field,
                                                    'matching_field_on_parent' => '{{USE PRIMARY KEY}}',
                                                )
                                            )
                                            /*array(
                                                'child_table' => $root_table,
                                                'parent_table' => $root_table,
                                                'parent_field' => $default_parent_field,
                                                'matching_field_on_parent' => '{{USE PRIMARY KEY}}',
                                                # optional fields
                                                'condition' => null,
                                                'order_by_limit' => null,
                                                'parent_filter_field' => null,
                                                'parent_filter_field_val' => null,
                                            )*/
                                          )
                                        : array()
                                      ),

        'name_cutoff' => isset($requestVars['name_cutoff'])
                            ? $requestVars['name_cutoff']
                            : null,

        'edit_vars' => isset($requestVars['edit_vars'])
                            ? $requestVars['edit_vars']
                            : null,

        'use_stars_for_node_size' => (isset($requestVars['use_stars_for_node_size'])
                                      && $requestVars['use_stars_for_node_size'] !== ''
                                        ? $requestVars['use_stars_for_node_size']
                                        : Config::$config['use_stars_for_node_size']),

        'vary_node_colors' => (isset($requestVars['vary_node_colors'])
                              && $requestVars['vary_node_colors'] !== ''
                                ? $requestVars['vary_node_colors']
                                : Config::$config['vary_node_colors']),

        'start_w_tree_fully_expanded' => (isset($requestVars['start_w_tree_fully_expanded'])
                              && $requestVars['start_w_tree_fully_expanded'] !== ''
                                ? $requestVars['start_w_tree_fully_expanded']
                                : Config::$config['start_w_tree_fully_expanded']),

        'tree_height_factor' => Config::$config['tree_height_factor'],

    );

    # create all the variables in the current scope
    extract($tree_var_names_w_default_values);

    # easy access to var names for other things that need it (e.g. vars_form)
    $tree_var_names = array_keys($tree_var_names_w_default_values);

    # some of the vars affect the Config vars
    Config::$config['use_stars_for_node_size'] = $use_stars_for_node_size;
    Config::$config['vary_node_colors'] = $vary_node_colors;

    # make sure all details are set for all relationships (default to null)
    foreach ($parent_relationships as $no => $relationship) {
        if (!isset($parent_relationships[$no]['child_table']))
            $parent_relationships[$no]['child_table'] = null;
        if (!isset($parent_relationships[$no]['parent_table']))
            $parent_relationships[$no]['parent_table'] = null;
        if (!isset($parent_relationships[$no]['parent_field']))
            $parent_relationships[$no]['parent_field'] = null;
        if (!isset($parent_relationships[$no]['matching_field_on_parent']))
            $parent_relationships[$no]['matching_field_on_parent'] = null;
        # optional fields
        if (!isset($parent_relationships[$no]['condition']))
            $parent_relationships[$no]['condition'] = null;
        if (!isset($parent_relationships[$no]['order_by_limit']))
            $parent_relationships[$no]['order_by_limit'] = Config::$config['tree_view_relationship_order_by_limit'];
        if (!isset($parent_relationships[$no]['parent_filter_field']))
            $parent_relationships[$no]['parent_filter_field'] = null;
        if (!isset($parent_relationships[$no]['parent_filter_field_val']))
            $parent_relationships[$no]['parent_filter_field_val'] = null;

        #todo expand out into multiple expressions per relationship
        if (!isset($parent_relationships[$no]['expression_name']))
            $parent_relationships[$no]['expression_name'] = Config::$config['tree_view_relationship_expression_name'];
        if (!isset($parent_relationships[$no]['expression']))
            $parent_relationships[$no]['expression'] = Config::$config['tree_view_relationship_expression'];
    }

    { # node color stuff
        $table_info = array();
        # use keys for uniqueness
        $table_info[$root_table] = array(
            'color' => name_to_rgb($root_table)
        );
        foreach ($parent_relationships as $relationship) {
            $table = $relationship['parent_table'];
            $table_info[$table] = array(
                'color' => name_to_rgb($table)
            );

            $table = $relationship['child_table'];
            $table_info[$table] = array(
                'color' => name_to_rgb($table)
            );
        }
    }

