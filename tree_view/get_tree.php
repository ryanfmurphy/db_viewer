<?php
    const DEBUG = false;

    # get_tree.php - returns a JSON of tree nodes obtained by
    # progressively running SQL queries,
    # 1 per each level per each parent relationship

    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
        require("$trunk/tree_view/vars.php");
    }

    function my_debug($msg) {
        if (DEBUG) echo $msg;
    }
    function encode_response($data) {
        if (DEBUG) {
            return print_r($data, 1);
        }
        else {
            return json_encode($data);
        }
    }

    function field_list($parent_relationships, $table) {
        $id_mode = Config::$config['id_mode'];
        $id_field = DbUtil::get_primary_key_field($id_mode, $table);

        $fields = array($id_field=>1, "name"=>1);
        foreach ($parent_relationships as $relationship) {
            #todo #fixme when we have multiple tables in the future
            #            make sure to limit these fields to the root table
            $fields[ $relationship['parent_field'] ] = 1;

            $matching_field_on_parent = get_matching_field_on_parent($relationship, $table); 
            $fields[ $matching_field_on_parent ] = 1;
        }
        return implode(', ', array_keys($fields));
    }

    my_debug("parent_relationships: " . print_r($parent_relationships,1));

    # get an array of the matching values for a given relationship
    # helps to create the next level SQL query to get the next level of tree nodes
    function get_field_values_for_matching($parent_nodes, $matching_field_on_parent) {
        $vals = array();
        foreach ($parent_nodes as $node) {
            my_debug("here's the node, we're looking for '$matching_field_on_parent' field: ".print_r($node,1));
            $vals[] = $node->{$matching_field_on_parent};
        }
        return $vals;
    }

    # SQL query to get children for next level
    function get_next_level_of_children(
        $parent_vals, $fields, $parent_field, $root_table, $order_by_limit
    ) {
        my_debug("about to make val_list for query.  parent_ids = ".print_r($parent_vals,1));
        $parent_val_list = Db::make_val_list($parent_vals);

        $sql = "
            select $fields
            from $root_table
            where $parent_field in $parent_val_list
            $order_by_limit
        ";
        my_debug("sql = '$sql'\n\n");
        $rows = Db::sql($sql);
        return $rows;
    }

    function add_node_to_relationship_lists(
        $row, $child, $parent_relationships, &$all_children_by_relationship, $table
    ) {
        # add val to each applicable relationship
        foreach ($parent_relationships as $rel_no => $parent_relationship) {

            #todo #fixme when relationships can span tables, make sure to check for correct table
            $matching_field_on_parent = get_matching_field_on_parent($parent_relationship, $table);
            $parent_match_val = $row[$matching_field_on_parent];

            if ($parent_match_val) {
                my_debug("adding $row[name] to children_this_rel->'$parent_match_val', relationship_no = $rel_no\n");

                # detructively modify $add_children_by_relationship
                if (!isset($all_children_by_relationship[$rel_no])) {
                    $all_children_by_relationship[$rel_no] = new stdClass();
                }
                $all_children_by_relationship[$rel_no]->{$parent_match_val} = $child;

                #if ($row['name'] == 'Rod Frank') {
                #    my_debug("all_children_by_relationship[$this_rel_no] now looks like this: "
                #            .print_r($all_children_by_relationship[$this_rel_no],1));
                #}
            }
            else {
                my_debug("no parent_match_val, not adding $row[name] to"
                    ." children_this_rel->'$parent_match_val', relationship_no = $rel_no\n");
            }
        }
    }

    function add_child_to_tree($child, $parent, $parent_match_val, $table) {
        $id_mode = Config::$config['id_mode'];
        $id_field = DbUtil::get_primary_key_field($id_mode, $table);
        $id = $parent->{$id_field};

        # add children container if needed
        if (!isset($parent->children)) {
            my_debug("creating new children container for $id to add $child->name\n"); #todo #test
            $parent->children = new stdClass();
        }
        else {
            my_debug("children container for $id already exists, adding $child->name\n"); #todo #test
        }

        # add child off node
        my_debug("adding child '$parent_match_val' within container for '$id': ".print_r($child,1));
        $parent->children->{$parent_match_val} = $child;
        my_debug("now $id looks like this: ".print_r($parent,1));
    }

    # starting with an array of $parent_nodes,
    # look in the DB and add all the child_nodes
    function add_tree_lev_by_lev(
        $all_nodes_by_id,
        $parent_nodes_by_relationship,
        $root_table, $order_by_limit=null,
        $parent_relationships
    ) {
        my_debug("starting add_tree_lev_by_lev: parent_nodes = "
                .print_r($parent_nodes_by_relationship,1));
        $id_mode = Config::$config['id_mode'];

        $all_children_by_relationship = array();
        $more_children_to_look_for = false;
        $fields = field_list($parent_relationships, $root_table);

        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            my_debug("starting new relationship $relationship_no: ".print_r($parent_relationship,1)
                    . "{\n");
            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = get_matching_field_on_parent($parent_relationship, $root_table);

            $parent_nodes = $parent_nodes_by_relationship[$relationship_no];
            $parent_vals = get_field_values_for_matching($parent_nodes, $matching_field_on_parent);

            # values to populate and pass to next level
            $children_this_rel = new stdClass();

            if (count($parent_vals) > 0) {
                $rows = get_next_level_of_children(
                    $parent_vals, $fields, $parent_field, $root_table, $order_by_limit
                );

                # the parent_node already has the children (which are about to be parents)
                # that are in the parent_id_list
                my_debug("  starting loop thru rows, {\n");
                foreach ($rows as $row) {
                    $this_parent_id = $row[$parent_field];
                    $id_field = DbUtil::get_primary_key_field(
                        $id_mode, $root_table
                    );
                    $id = $row[$id_field];
                    my_debug("matching_field_on_parent = $matching_field_on_parent\n");
                    $parent_match_val = $row[$matching_field_on_parent];

                    # get or create node
                    if (isset($all_nodes_by_id->{$id})) {
                        # need to do anything? all fields should be there.
                        $child = $all_nodes_by_id->{$id};
                    }
                    else {
                        $child = (object)$row;
                        $all_nodes_by_id->{$id} = $child;
                    }

                    # parent SHOULD exist...
                    if (isset($parent_nodes->{$this_parent_id})) {
                        $parent = $parent_nodes->{$this_parent_id};
                        add_child_to_tree($child, $parent, $parent_match_val, $root_table);
                    }
                    else {
                        my_debug("WARNING don't actually have the parent $this_parent_id at all, let alone a children container\n");
                        my_debug("skipping this node\n");
                    }

                    add_node_to_relationship_lists(
                        $row, $child, $parent_relationships, /*&*/$all_children_by_relationship, $root_table
                    );

                    $more_children_to_look_for = true;
                }
                my_debug("  }\n");
            }
            my_debug("}\n");
        }

        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes_by_id,
                $all_children_by_relationship,
                $root_table, $order_by_limit,
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
        $parent_relationships
    ) {
        $id_mode = Config::$config['id_mode'];

        #$parent_vals_next_lev_by_relationship = array();

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
        my_debug("root sql = '$sql'\n\n");
        $rows = Db::sql($sql);

        # to make sure we never recreate a node from scratch
        # and always keep building on its relationships
        $all_nodes_by_id = new stdClass();
        # to pass forth to recursive calls that need to separate based on relationship
        #$parent_nodes_by_relationship = array();
        # to make sure we stop when we are done
        $more_children_to_look_for = false;

        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            my_debug("starting new relationship $relationship_no: ".print_r($parent_relationship,1)
                    . "{\n");

            $id_field = DbUtil::get_primary_key_field(
                $id_mode, $root_table
            );
            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = get_matching_field_on_parent($parent_relationship, $root_table);
            #$parent_vals_next_lev = array();
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
                if (isset($all_nodes_by_id->{$id})) {
                    # need to do anything? all fields should be there.
                    $tree_node = $all_nodes_by_id->{$id};
                }
                else {
                    $tree_node = (object)$row;
                    $all_nodes_by_id->{$id} = $tree_node;
                }

                /*
                # see if we have a node already or need to make a new one
                if (isset($all_nodes_by_id->{$id})) {
                    $tree_node = $all_nodes_by_id->{$id};
                }
                else {
                    $tree_node = new stdClass();
                    $all_nodes_by_id->{$id} = $tree_node;
                }

                $tree_node->$parent_field = $row[$parent_field];
                $tree_node->id = $id;
                $tree_node->name = $row['name'];
                */

                # we have a parent_match_val so we can actually put it in the array
                if ($parent_match_val) {
                    $parent_nodes_this_rel->{$parent_match_val} = $tree_node; #todo #fixme I think we don't need this
                    #$parent_vals_next_lev[] = $row[$matching_field_on_parent];
                    $more_children_to_look_for = true;
                }
                else {
                    my_debug("no parent_match_val for this one, id=$id - skipping\n");
                }
            }

            #$parent_vals_next_lev_by_relationship[$relationship_no] = $parent_vals_next_lev;
            $parent_nodes_by_relationship[$relationship_no] = $parent_nodes_this_rel;

            my_debug("}\n");
        }

        # recursive call
        #my_debug("about to send parent_vals_next_lev_by_relationship: ".print_r($parent_vals_next_lev_by_relationship,1));
        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes_by_id,
                $parent_nodes_by_relationship,
                #$parent_vals_next_lev_by_relationship,
                $root_table,
                $order_by_limit, #$parent_field, $matching_field_on_parent
                $parent_relationships
            );
        }
        #todo #fixme only return the root nodes, they're still connected to the children
        return $all_nodes_by_id;
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



    # service the API call

    $tree = get_tree(
        $root_table, $root_cond, $order_by_limit,
        $parent_relationships
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

