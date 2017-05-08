<?php
    # get_tree.php - returns a JSON of tree nodes obtained by
    # progressively running SQL queries,
    # 1 per each level per each parent relationship

    error_reporting(E_ALL);
    const DEBUG = false;
    const DEBUG_SQL = true;
    const DEBUG_UNKEY = false;
    const DEBUG_RESULT = false;

    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
        require("$trunk/tree_view/vars.php");
        require("$trunk/tree_view/hash_color.php");
    }

    function tree_log($msg) {
        $log_path = Config::$config['log_path'];
        error_log($msg,3,"$log_path/tree_log");
    }
    function my_debug($msg) {
        if (DEBUG) tree_log($msg);
    }
    function my_debug_sql($msg) {
        if (DEBUG_SQL) tree_log($msg);
    }
    function my_debug_unkey($msg) {
        if (DEBUG_UNKEY) tree_log($msg);
    }
    function encode_response($data) {
        if (DEBUG_RESULT) {
            return print_r($data, 1);
        }
        else {
            return json_encode($data);
        }
    }

    function field_list($parent_relationships, $table) {
        $id_mode = Config::$config['id_mode'];
        $id_field = DbUtil::get_primary_key_field($id_mode, $table);
        $name_field = "name";
        $fields = array($id_field=>1, $name_field=>1);

        foreach ($parent_relationships as $relationship) {
            # we only want fields that make sense for this table
            my_debug("checking relationship: ".print_r($relationship,1));
            if ($relationship['child_table'] == $table) {
                $parent_field = $relationship['parent_field'];
                my_debug("adding parent_field $parent_field because child_table"
                        ." $relationship[child_table] matches table $table\n");
                $fields[$parent_field] = 1;
            }
            if ($relationship['parent_table'] == $table) {
                $matching_field_on_parent = get_matching_field_on_parent($relationship, $table); 
                my_debug("adding matching_field_on_parent $matching_field_on_parent because"
                        ." child_table $relationship[child_table] matches tabl $table\n");
                $fields[ $matching_field_on_parent ] = 1;
            }
        }
        return implode(', ', array_keys($fields));
    }

    # get an array of the matching values for a given relationship
    # helps to create the next level SQL query to get the next level of tree nodes
    function get_field_values_for_matching($parent_nodes, $matching_field_on_parent) {
        $vals = array();
        foreach ($parent_nodes as $node) {
            my_debug("here's the node, we're looking for"
                    ." '$matching_field_on_parent' field: ".print_r($node,1));
            $vals[] = $node->{$matching_field_on_parent};
        }
        return $vals;
    }

    # SQL query to get children for next level
    function get_next_level_of_children(
        $parent_vals, $fields, $parent_field, $table, $order_by_limit
    ) {
        my_debug("about to make val_list for query.".
                "  parent_ids = ".print_r($parent_vals,1));
        $parent_val_list = Db::make_val_list($parent_vals);

        $sql = "
            select $fields
            from $table
            where $parent_field in $parent_val_list
            $order_by_limit
        ";
        my_debug_sql("sql = {'$sql'}\n");
        $rows = Db::sql($sql);
        my_debug_sql("  # rows = ".count($rows)."\n\n");
        return $rows;
    }

    # this is to build up the matching list that goes in the SQL query
    # to look for all children that have {parent_field} within that list
    # #todo #fixme maybe rename $child to $parent / $all_children to $all_parents?
    #              since it's to build up the parent val list to select against?
    function add_node_to_relationship_lists(
        $row, $child, $parent_relationships,
        &$all_children_by_relationship, $table
    ) {
        # add val to each applicable relationship
        foreach ($parent_relationships as $rel_no => $parent_relationship) {

            # only add this child to the relationships w the same table
            if ($parent_relationship['parent_table'] == $table) {
                $matching_field_on_parent = get_matching_field_on_parent($parent_relationship,
                                                                         $table);
                $parent_match_val = $row[$matching_field_on_parent];

                if ($parent_match_val) {
                    my_debug("adding $row[name] to children_this_rel->'$parent_match_val',"
                            ." relationship_no = $rel_no\n");

                    # detructively modify $add_children_by_relationship
                    if (!isset($all_children_by_relationship[$rel_no])) {
                        $all_children_by_relationship[$rel_no] = new stdClass();
                    }
                    $all_children_by_relationship[$rel_no]->{$parent_match_val} = $child;
                }
                else {
                    my_debug("no parent_match_val, not adding $row[name] to"
                        ." children_this_rel->'$parent_match_val', relationship_no = $rel_no\n");
                }
            }
        }
    }

    function add_child_to_tree($child, $parent, $parent_match_val, $parent_table, $child_table) {
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
    }

    # starting with an array of $parent_nodes,
    # look in the DB and add all the child_nodes
    function add_tree_lev_by_lev(
        $all_nodes,
        $parent_nodes_by_relationship,
        /*$root_table,*/ $order_by_limit=null,
        $parent_relationships
    ) {
        my_debug("starting add_tree_lev_by_lev: parent_nodes = "
                .print_r($parent_nodes_by_relationship,1));
        $id_mode = Config::$config['id_mode'];

        $all_children_by_relationship = array();
        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            $all_children_by_relationship[$relationship_no] = new stdClass();
        }

        $more_children_to_look_for = false;

        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            my_debug("starting new relationship $relationship_no: "
                        .print_r($parent_relationship,1)
                        . "{\n");

            $child_table = $parent_relationship['child_table'];
            $parent_table = $parent_relationship['parent_table'];
            $fields = field_list($parent_relationships, $child_table);
            #print_r($fields);

            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = get_matching_field_on_parent($parent_relationship,
                                                                     $parent_table);

            $parent_nodes = $parent_nodes_by_relationship[$relationship_no];
            $parent_vals = get_field_values_for_matching($parent_nodes,
                                                         $matching_field_on_parent);

            $table_color = name_to_rgb($child_table);

            if (count($parent_vals) > 0) {
                $rows = get_next_level_of_children(
                    $parent_vals, $fields, $parent_field,
                    $child_table, $order_by_limit
                );

                # the parent_node already has the children
                # (which are about to be parents)
                # that are in the parent_id_list
                my_debug("  starting loop thru rows, {\n");
                foreach ($rows as $row) {
                    $this_parent_id = $row[$parent_field];
                    $id_field = DbUtil::get_primary_key_field(
                        $id_mode, $child_table
                    );
                    $id = $row[$id_field];
                    my_debug("matching_field_on_parent = $matching_field_on_parent\n");
                    $parent_match_val = $row[$matching_field_on_parent];

                    # get or create node
                    $row['_node_table'] = $child_table;
                    $row['_node_color'] = $table_color;
                    if (isset($all_nodes->{"$child_table:$id"})) {
                        # need to do anything? all fields should be there.
                        $tree_view_avoid_recursion = false; #todo #fixme move to Config
                        if ($tree_view_avoid_recursion) {
                            $child = (object)$row;
                        }
                        else {
                            $child = $all_nodes->{"$child_table:$id"};
                        }
                    }
                    else {
                        $child = (object)$row;
                        $all_nodes->{"$child_table:$id"} = $child;
                    }

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
                            my_debug("WARNING don't actually have the parent $this_parent_id"
                                    ." at all, let alone a children container\n");
                            my_debug("skipping this node\n");
                        }
                    }
                    else {
                        my_debug("WARNING don't have parent_match_val on the parent"
                                ." to connect the child to the parent\n");
                        my_debug("skipping this node\n");
                    }

                    add_node_to_relationship_lists(
                        $row, $child, $parent_relationships,
                        /*&*/$all_children_by_relationship,
                        $child_table
                    );

                    $more_children_to_look_for = true;
                }
                my_debug("  }\n");
            }
            my_debug("}\n");
        }

        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes,
                $all_children_by_relationship,
                /*$root_table,*/ $order_by_limit,
                $parent_relationships
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

    function get_tree(
        $root_table, $root_cond, $order_by_limit=null,
        $parent_relationships, $root_nodes_w_child_only=false
    ) {
        $id_mode = Config::$config['id_mode'];

        $parent_relationship = $parent_relationships[0]; #todo #fixme support more than one
        $parent_field = $parent_relationship['parent_field'];
        $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];

        $fields = field_list($parent_relationships, $root_table);
        $sql = "
            select $fields
            from $root_table
            where $root_cond
            $order_by_limit
        ";
        my_debug_sql("root sql = {'$sql'}\n");
        $rows = Db::sql($sql);
        my_debug_sql("  # rows = ".count($rows)."\n\n");

        # root nodes to return from this function
        $root_nodes = new stdClass();
        # all_nodes, to make sure we never recreate a node from scratch
        # and always keep building on its relationships
        $all_nodes = new stdClass();
        # to make sure we stop when we are done
        $more_children_to_look_for = false;

        $table_color = name_to_rgb($root_table);

        #todo #fixme - does it make more sense for these foreach loops
        #              to be nested the other way?
        foreach ($parent_relationships as $relationship_no => $parent_relationship) {

            my_debug("starting new relationship $relationship_no: "
                        .print_r($parent_relationship,1)
                        . "{\n");

            $id_field = DbUtil::get_primary_key_field(
                $id_mode, $root_table
            );
            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = get_matching_field_on_parent($parent_relationship,
                                                                     $root_table);
            $parent_nodes_this_rel = new stdClass();

            foreach ($rows as $row) {
                my_debug("adding node ".print_r($row,1));
                $id = $row[$id_field];
                if (!$id) {
                    my_debug("row has no id!  skipping.  here's the row: ".print_r($row,1));
                    continue;
                }

                my_debug("matching_field_on_parent = $matching_field_on_parent\n");
                $parent_match_val = $row[$matching_field_on_parent];

                # get or create node
                $row['_node_table'] = $root_table;
                $row['_node_color'] = $table_color;
                if (isset($all_nodes->{"$root_table:$id"})) {
                    # need to do anything? all fields should be there.
                    $tree_view_avoid_recursion = true; #todo #fixme move to Config
                    if ($tree_view_avoid_recursion) {
                        $tree_node = (object)$row;
                    }
                    else {
                        $tree_node = $all_nodes->{"$root_table:$id"};
                    }
                }
                else {
                    $tree_node = (object)$row;
                    $root_nodes->{$id} = $tree_node;
                    $all_nodes->{"$root_table:$id"} = $tree_node;
                }

                # we have a parent_match_val so we can actually put it in the array
                if ($parent_match_val) {
                    $parent_nodes_this_rel->{$parent_match_val} = $tree_node;
                    $more_children_to_look_for = true;
                }
                else {
                    my_debug("no parent_match_val for this one, id=$id - skipping\n");
                }
            }

            $parent_nodes_by_relationship[$relationship_no] = $parent_nodes_this_rel;

            my_debug("}\n");
        }

        # recursive call
        #my_debug("about to send parent_vals_next_lev_by_relationship: ".print_r($parent_vals_next_lev_by_relationship,1));
        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes,
                $parent_nodes_by_relationship,
                #$root_table,
                $order_by_limit,
                $parent_relationships
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
        my_debug("parent_relationships: " . print_r($parent_relationships,1));

        $tree = get_tree(
            $root_table, $root_cond, $order_by_limit,
            $parent_relationships, $root_nodes_w_child_only
        );
        $tree = unkey_tree($tree);

        die(
            encode_response(
                array(
                    'name' => '',
                    'children' => $tree,
                )
            )
        );
    }

