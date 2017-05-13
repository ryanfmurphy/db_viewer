<?php
    $root_table = isset($requestVars['root_table'])
                    ? $requestVars['root_table']
                    : null;

    $root_cond = isset($requestVars['root_cond'])
                 && $requestVars['root_cond']
                    ? $requestVars['root_cond']
                    : "$default_parent_field is null";

    $order_by_limit = isset($requestVars['order_by_limit'])
                        ? $requestVars['order_by_limit']
                        : null;

    $root_nodes_w_child_only = isset($requestVars['root_nodes_w_child_only'])
                                    ? $requestVars['root_nodes_w_child_only']
                                    : null;

    $parent_relationships = isset($requestVars['parent_relationships'])
                                ? $requestVars['parent_relationships']
                                : array(
                                    array(
                                        'child_table' => $root_table,
                                        'parent_table' => $root_table,
                                        'parent_field' => $default_parent_field,
                                        'matching_field_on_parent' => '{{USE PRIMARY KEY}}',
                                    )
                                  );

    $name_cutoff = isset($requestVars['name_cutoff'])
                        ? $requestVars['name_cutoff']
                        : null;

    $edit_vars = isset($requestVars['edit_vars'])
                    ? $requestVars['edit_vars']
                    : null;

    # make sure tables are filled out for all relationships
    # (default to root_table)
    /*
    foreach ($parent_relationships as $no => $relationship) {

        if (!isset($relationship['child_table'])
            || !$relationship['child_table']
        ) {
            $parent_relationships[$no]['child_table'] = $root_table;
        }

        if (!isset($relationship['parent_table'])
            || !$relationship['parent_table']
        ) {
            $parent_relationships[$no]['parent_table'] = $root_table;
        }

    }
    */

    # mostly color for now
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

    Config::$config['use_stars_for_node_size']
        = $use_stars_for_node_size
            = isset($requestVars['use_stars_for_node_size'])
                ? $requestVars['use_stars_for_node_size']
                : Config::$config['use_stars_for_node_size'];

