<?php
    # get_tree.php - returns a JSON of tree nodes obtained by
    # progressively running SQL queries,
    # 1 per each level per each parent relationship

    error_reporting(E_ALL);
    const DEBUG_ALL = false;
    const DEBUG_ECHO = false;
    const DEBUG_PRE_UNKEYED = DEBUG_ECHO;
    const DEBUG_RESULT = false;

    function my_debug($category, $msg) {
        if (DEBUG_ALL
            || $category === true
            || in_array($category, array(
                                    #'arrays',
                                    'sql',
                                    'rel',
                                    'overview',
                                    'tables_n_fields'
                                    #'fields'
                                   ))
        ) {
            tree_log($msg);
        }
        if (DEBUG_ECHO
            && in_array($category, array(
                                    #'arrays', 'sql', 'rel', 'overview',
                                    #'tables_n_fields'
                                   ))
        ) {
            echo($msg);
        }
    }

    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
        require("$trunk/tree_view/hash_color.php");
        require("$trunk/tree_view/vars.php");
    }

    function tree_log($msg) {
        $log_path = Config::$config['log_path'];
        error_log($msg,3,"$log_path/tree_log");
    }
    function encode_response($data) {
        if (DEBUG_RESULT) {
            return print_r($data, 1);
        }
        else {
            return json_encode($data);
        }
    }

    # if $is_root_level, then don't need the parent field that would be used to match to a parent level
    function field_list($parent_relationships, $table, $is_root_level=false) {
        my_debug('fields', "  { field_list\n");
        $id_mode = Config::$config['id_mode'];
        $id_field = DbUtil::get_primary_key_field($id_mode, $table);
        $name_field = DbUtil::get_name_field($table);
        $fields = array($id_field=>1, $name_field=>1);

        $tables_to_use_relname = Config::$config['use_relname_for_tree_node_table'];
        if (is_array($tables_to_use_relname)
            && in_array($table,
                        $tables_to_use_relname)
        ) {
            $fields['relname'] = 1;
        }

        if (Config::$config['use_stars_for_node_size']) {
            #todo #fixme only add it for tables that have that field
            $fields['stars'] = 1;
        }

        foreach ($parent_relationships as $relationship) {
            # we only want fields that make sense for this table
            my_debug('fields', "checking relationship: ".json_encode($relationship,1)."\n");
            if (!$is_root_level
                && $relationship['child_table'] == $table
            ) {
                $parent_field = $relationship['parent_field'];
                my_debug('fields', "adding parent_field $parent_field because child_table"
                                    ." $relationship[child_table] matches table $table\n");
                $fields[$parent_field] = 1;
            }
            if ($relationship['parent_table'] == $table) {
                $matching_field_on_parent = get_matching_field_on_parent($relationship, $table); 
                my_debug('fields', "adding matching_field_on_parent $matching_field_on_parent because"
                                    ." child_table $relationship[child_table] matches tabl $table\n");
                $fields[ $matching_field_on_parent ] = 1;
            }
        }
        my_debug('fields', "  } (finishing field_list)\n");
        return implode(', ', array_keys($fields));
    }

    function field_is_array($field) {
        return in_array($field,
                        Config::$config['fields_w_array_type']);
    }

    # get an array of the matching values for a given relationship
    # helps to create the next level SQL query to get the next level of tree nodes
    function get_field_values_for_matching($parent_nodes, $matching_field_on_parent) {
        my_debug(NULL, "  { get_field_values_for_matching\n");

        $vals = array();
        foreach ($parent_nodes as $node) {
            my_debug(NULL, "here's the node, we're looking for"
                    ." '$matching_field_on_parent' field: ".print_r($node,1));
            $field_val = $node->{$matching_field_on_parent};
            if ($field_val) {
                # check if the field on the parent is an array
                # if so, all of these values should go the val_list
                # #todo #fixme get this array part working
                if (field_is_array($matching_field_on_parent)) {
                    $arr = DbUtil::pg_array2array($field_val);
                    foreach ($arr as $val) {
                        $vals[] = $val;
                    }
                }
                else {
                    $vals[] = $field_val;
                }
            }
        }
        my_debug(NULL, "  } (get_field_values_for_matching)\n");
        return $vals;
    }

    # SQL query to get children for next level
    function get_next_level_of_children(
        $parent_vals, $fields, $parent_field,
        $table, $order_by_limit, $where_cond
    ) {
        my_debug(NULL, "about to make val_list for query.".
                "  parent_ids = ".print_r($parent_vals,1));
        $parent_val_list = Db::make_val_list($parent_vals);

        $sql = "
            select $fields
            from $table
            where $parent_field in $parent_val_list
        " . ($where_cond
                ? " and $where_cond "
                : null) . "
            $order_by_limit
        ";
        my_debug('sql',"sql = {'$sql'}\n");
        $rows = Db::sql($sql);
        my_debug('sql',"  # rows = ".count($rows)."\n\n");
        return $rows;
    }

    # this is to build up the matching list that goes in the SQL query
    # to look for all children that have {parent_field} within that list
    # (build up the parent val list to select against)
    function add_node_to_relationship_lists(
        $row, $parent, $parent_relationships,
        &$all_parents_by_relationship, $table
    ) {
        my_debug('arrays', "top of add_node_to_relationship_lists...\n");
        # add val to each applicable relationship
        foreach ($parent_relationships as $rel_no => $parent_relationship) {

                $matching_field_on_parent = get_matching_field_on_parent($parent_relationship,
                                                                         $table);

                # only add this child to the relationships w the same table
                if ($parent_relationship['parent_table'] == $table) {
                    #$parent_field = $parent_relationship['parent_field'];
                    $parent_match_val = $row[$matching_field_on_parent];

                    # we have a parent_match_val so we can actually put it in the array
                    if ($parent_match_val) {
                        #my_debug(NULL, "adding $row[name] to children_this_rel->'$parent_match_val',"
                        #        ." relationship_no = $rel_no\n");

                        # detructively modify $add_children_by_relationship
                        if (!isset($all_parents_by_relationship[$rel_no])) {
                            $all_parents_by_relationship[$rel_no] = new stdClass();
                        }

                        # If that the child is going to try to match to this node
                        # is an array, then we break up our array right now and
                        # put the node in under all the different values as keys.
                        # That way, matching any of them will be fine.
                        my_debug('arrays', "in add_node_to_relationship_lists... rel_no = $rel_no\n");
                        my_debug('arrays', "checking if field '$matching_field_on_parent' is an array\n");
                        if (field_is_array($matching_field_on_parent)) {
                            my_debug('arrays', "  it is - deconstructing the array and adding each key\n");
                            $arr = DbUtil::pg_array2array($parent_match_val);
                            foreach ($arr as $val) {
                                my_debug('arrays', "    val = $val\n");
                                if ($val) {
                                    $all_parents_by_relationship[$rel_no]->{$val} = $parent;
                                }
                                else {
                                    my_debug('not truthy, skipping', "    val = $val\n");
                                }
                            }
                        }
                        else {
                            my_debug('arrays', "  it is not, adding it normally\n");
                            $all_parents_by_relationship[$rel_no]->{$parent_match_val} = $parent;
                        }
                    }
                    else {
                        #my_debug(NULL, "no parent_match_val, not adding $row[name] to"
                        #    ." children_this_rel->'$parent_match_val', relationship_no = $rel_no\n");
                    }
                }
        }
    }

    function add_child_to_tree($child, $parent, $parent_match_val, $parent_table, $child_table) {
        my_debug(NULL, " {add_child_to_tree: parent_table = $parent_table, child_table = $child_table\n");
        my_debug(NULL, "  child = ".print_r($child,1));
        my_debug(NULL, "  parent = ".print_r($parent,1));
        $id_mode = Config::$config['id_mode'];
        $parent_id_field = DbUtil::get_primary_key_field($id_mode, $parent_table);
        $parent_id = $parent->{$parent_id_field};

        $child_id_field = DbUtil::get_primary_key_field($id_mode, $child_table);
        $child_id = $child->{$child_id_field};

        # add children container if needed
        if (!isset($parent->children)) {
            $parent->children = new stdClass();
        }

        # add child off node
        $parent->children->{"$child_table:$child_id"} = $child;
        my_debug(NULL, "   did the add, now parent = ".print_r($parent,1));
        my_debug(NULL, " } (add_child_to_tree)\n");
    }

    function determine_node_tablename(&$row, $child_table) {
        if (Config::$config['use_relname_for_tree_node_table']
            && isset($row['relname'])
        ) {
            return $row['relname'];
        }
        else {
            return $child_table;
        }
    }

    # populate extra stuff on row so the node will be populated
    function add_node_metadata_to_row(&$row, $table) {
        $use_relname = Config::$config['use_relname_for_tree_node_table'];

        if ($use_relname) { # must calculate for each row
            # still save original connection table
            # so front-end can look up id field in its hash
            $row['_conn_table'] = $table;

            $node_tablename = determine_node_tablename($row, $table);
            $table_color = name_to_rgb($node_tablename);
            $row['_node_color'] = $table_color;
        }
        else {
            $node_tablename = $table;
        }

        # get or create node
        $row['_node_table'] = $node_tablename;

        # make sure 'name' is available
        # #todo #fixme #performance - cache these couple of values outside the loop
        $row['_node_name'] = DbUtil::get_name_val($table, $row);
    }

    # get_tree() - main function for building the tree
    # ------------------------------------------------
    # * Build a SQL query the queries $root_table
    #   where $root_cond.
    # * Loop thru the parent_relationships and build up
    #   the $parent_relationships hash.
    # * Call add_tree_lev_by_lev() to add the next level
    #   and keep recursively adding the remaining levels.
    function get_tree(
        $root_table, $root_cond, $parent_relationships,
        $order_by_limit=null, $root_nodes_w_child_only=false
    ) {
        my_debug('overview', "top of get_tree...\n");

        { # do sql query
            $id_mode = Config::$config['id_mode'];
            $fields = field_list($parent_relationships, $root_table, true);
            $sql = "
                select $fields
                from $root_table
                where $root_cond
                $order_by_limit
            ";
            my_debug('sql',"root sql = {'$sql'}\n");
            $rows = Db::sql($sql);
            my_debug('sql',"  # rows = ".count($rows)."\n\n");
        }

        { # setup the hashes that will be built up
            # root nodes to return from this function
            $root_nodes = new stdClass();
            # all_nodes, to make sure we never recreate a node from scratch
            # and always keep building on its relationships
            $all_nodes = new stdClass();

            # each parent_relationship gets its own hash
            $parent_nodes_by_relationship = array();
            foreach ($parent_relationships as $rel_no => $parent_relationship) {
                $parent_nodes_by_relationship[$rel_no] = new stdClass();
            }
        }

        # to make sure we stop when we are done
        $more_children_to_look_for = false;

        $id_field = DbUtil::get_primary_key_field($id_mode, $root_table);

        # loop thru rows and build up this level of tree
        foreach ($rows as $row) {
            my_debug(NULL, "adding node ".print_r($row,1));
            $id = $row[$id_field];
            if (!$id) {
                my_debug(NULL, "row has no id!  skipping.  here's the row: ".print_r($row,1));
                continue;
            }

            add_node_metadata_to_row(/*&*/$row, $root_table);
            $tree_node = get_or_create_node($row, $root_table, $id,
                                       /*&*/$all_nodes, /*&*/$root_nodes);

            add_node_to_relationship_lists(
                $row, $tree_node, $parent_relationships,
                $parent_nodes_by_relationship, $root_table
            );

            $more_children_to_look_for = true;
        }

        # recursive call
        #my_debug(NULL, "about to send parent_vals_next_lev_by_relationship: ".print_r($parent_vals_next_lev_by_relationship,1));
        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes,
                $parent_nodes_by_relationship,
                $parent_relationships,
                $order_by_limit
            );
        }

        if ($root_nodes_w_child_only) {
            foreach ($root_nodes as $key => $node) {
                if (!isset($node->children)
                    || count($node->children) == 0
                ) {
                    unset($root_nodes->{$key});
                }
            }
        }

        return $root_nodes;
    }

    # add a new level to the tree:
    # starting with an array of $parent_nodes,
    # look in the DB and add all the child_nodes
    function add_tree_lev_by_lev(
        $all_nodes,
        $parent_nodes_by_relationship,
        $parent_relationships,
        $order_by_limit=null,
        $level = 0
    ) {
        my_debug('overview', "{ top of add_tree_lev_by_lev: level $level,"
                            ." parent_nodes = ".print_r(
                                $parent_nodes_by_relationship,1
                            ));
        $id_mode = Config::$config['id_mode'];

        $all_children_by_relationship = array();
        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            $all_children_by_relationship[$relationship_no] = new stdClass();
        }

        $more_children_to_look_for = false;

        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            my_debug('overview', "starting new relationship $relationship_no: "
                                .print_r($parent_relationship,1)
                                . "{\n");

            $child_table = $parent_relationship['child_table'];
            $parent_table = $parent_relationship['parent_table'];
            my_debug('tables_n_fields', "this relationship,"
                ." child_table='$child_table', parent_table='$parent_table'\n");
            $fields = field_list($parent_relationships, $child_table);
            my_debug('tables_n_fields', "fields = $fields\n");

            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = get_matching_field_on_parent(
                                    $parent_relationship, $parent_table);

            $parent_nodes = $parent_nodes_by_relationship[$relationship_no];
            $parent_vals = get_field_values_for_matching($parent_nodes,
                                                 $matching_field_on_parent);

            if (count($parent_vals) > 0) {
                $where_cond = $parent_relationship['condition'];
                $rows = get_next_level_of_children(
                    $parent_vals, $fields, $parent_field,
                    $child_table, $order_by_limit, $where_cond
                );
                # NOTE:
                # all these $rows are POTENTIAL children,
                # but relationship COULD have a parent_filter_field
                # in which case the row's parent_filter_field
                # must match parent_filter_field_val

                # the parent_node already has the children
                # (which are about to be parents)
                # that are in the parent_id_list
                my_debug(true, "  starting loop thru rows, {\n");
                my_debug('tables_n_fields', "matching_field_on_parent = "
                                            ."$matching_field_on_parent\n");
                foreach ($rows as $row) {
                    $this_parent_id = $row[$parent_field];
                    $id_field = DbUtil::get_primary_key_field(
                        $id_mode, $child_table
                    );
                    $id = $row[$id_field];
                    $parent_match_val = isset($row[$matching_field_on_parent])
                                            ? $row[$matching_field_on_parent]
                                            : null;

                    add_node_metadata_to_row(/*&*/$row, $child_table);
                    $child = get_or_create_node($row, $child_table, $id,
                                           /*&*/$all_nodes);

                    if ($parent_match_val) {
                        # parent SHOULD exist...
                        if (isset($parent_nodes->{$this_parent_id})) {
                            $parent = $parent_nodes->{$this_parent_id};
                            add_child_to_tree($child, $parent,
                                              $parent_match_val,
                                              $parent_table,
                                              $child_table);
                        }
                        else {
                            my_debug(NULL, "WARNING don't actually have the parent $this_parent_id"
                                    ." at all, let alone a children container\n");
                            my_debug(NULL, "skipping this node\n");
                        }
                    }
                    else {
                        my_debug(NULL, "WARNING don't have parent_match_val on the parent"
                                ." to connect the child to the parent\n");
                        my_debug(NULL, "skipping this node\n");
                    }

                    add_node_to_relationship_lists(
                        $row, $child, $parent_relationships,
                        /*&*/$all_children_by_relationship,
                        $child_table
                    );

                    $more_children_to_look_for = true;
                }
                my_debug(NULL, "  }\n");
            }
            my_debug('overview', "}\n");
        }

        my_debug('overview', "} add_tree_lev_by_lev level $level (except for kick off next level)\n");
        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes,
                $all_children_by_relationship,
                $parent_relationships,
                $order_by_limit,
                $level + 1
            );
        }
    }

    function get_matching_field_on_parent($parent_relationship, $table) {
        $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];
        if ($matching_field_on_parent == '{{USE PRIMARY KEY}}') {
            $id_mode = Config::$config['id_mode'];
            $id_field = DbUtil::get_primary_key_field($id_mode, $table);
            $matching_field_on_parent = $id_field;
        }
        return $matching_field_on_parent;
    }

    # Check if the node exists already in the hash
    # and if so return it, otherwise create the object.
    # Populate $all_nodes, and, if passed in, $root_nodes
    # Does not modify $row array, by reference for #performance
    function get_or_create_node(&$row, $table, $id, &$all_nodes, &$root_nodes = null) {
        if (isset($all_nodes->{"$table:$id"})) {
            # need to do anything? all fields should be there.
            $tree_view_avoid_recursion = false; #todo #fixme move to Config
            if ($tree_view_avoid_recursion) {
                $tree_node = (object)$row;
            }
            else {
                $tree_node = $all_nodes->{"$table:$id"};
            }
        }
        else {
            $tree_node = (object)$row;
            if ($root_nodes !== null) {
                $root_nodes->{$id} = $tree_node;
            }
            $all_nodes->{"$table:$id"} = $tree_node;
        }
        return $tree_node;
    }

    # to gain our ordering back before we get off PHP to JS
    # rebuild the array without the associative keys
    function unkey_tree($tree_nodes) {
        $unkeyed_tree = array();
        foreach ($tree_nodes as $tree_node) {
            $new_node = $tree_node;

            if (isset($new_node->children)) {
                $children = $new_node->children;
                unset($new_node->children);
            }
            else {
                $children = array();
            }

            if (count($children) > 0) {
                $new_node->children = unkey_tree($children);
            }

            $unkeyed_tree[] = $new_node;
        }
        return $unkeyed_tree;
    }


    { # service the API call
        my_debug(NULL, "parent_relationships: " . print_r($parent_relationships,1));

        $tree = get_tree(
            $root_table, $root_cond, $parent_relationships,
            $order_by_limit, $root_nodes_w_child_only
        );
        if (DEBUG_PRE_UNKEYED) {
            die(print_r($tree,1));
        }
        $tree = unkey_tree($tree);

        die(
            encode_response(
                array(
                    '_node_name' => '',
                    'children' => $tree,
                )
            )
        );
    }

