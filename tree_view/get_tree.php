<?php
    const DEBUG = false;
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

    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
        require("$trunk/tree_view/vars.php");
    }

    # get fields for query
    function field_list($parent_field, $matching_field_on_parent) {
        $fields = "id, name, $parent_field";
        if ($matching_field_on_parent != 'id') {
            $fields .= ", $matching_field_on_parent";
        }
        return $fields;
    }

    function field_list_all_rels($parent_relationships) {
        $fields = array("id"=>1, "name"=>1);
        foreach ($parent_relationships as $relationship) {
            #todo #fixme when we have multiple tables in the future
            #            make sure to limit these fields to the root table
            $fields[ $relationship['parent_field'] ] = 1;
            $fields[ $relationship['matching_field_on_parent'] ] = 1;
        }
        return implode(', ', array_keys($fields));
    }

    my_debug("parent_relationships: " . print_r($parent_relationships,1));

    # starting with an array of $parent_nodes,
    # look in the DB and add all the child_nodes
    function add_tree_lev_by_lev(
        $all_nodes_by_id,
        $parent_nodes_by_relationship,
        # an array corresponding to the parent_relationships,
        # and containing the matching parent vals for that relationships
        #$parent_vals_this_lev_by_relationship,
        $root_table, $order_by_limit=null,
        $parent_relationships
    ) {
        my_debug("starting add_tree_lev_by_lev: parent_nodes = ".print_r($parent_nodes_by_relationship,1));

        $all_children_by_relationship = array();
        $more_children_to_look_for = false;

        foreach ($parent_relationships as $relationship_no => $parent_relationship) {
            my_debug("starting new relationship $relationship_no: ".print_r($parent_relationship,1)
                    . "{\n");
            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];

            # apparently this array is not complete, and anyway it's cleaner to derive it from parent_nodes_by_relationship
            # now that we have it...
            #$parent_ids = $parent_vals_this_lev_by_relationship[$relationship_no];
            $parent_nodes = $parent_nodes_by_relationship[$relationship_no];
            $parent_ids = array();
            foreach ($parent_nodes as $node) {
                $parent_ids[] = $node->{$matching_field_on_parent};
            }

            # values to populate and pass to next level
            $children_this_rel = new stdClass();
            #$parent_vals_next_lev = array();

            if (count($parent_ids) > 0) {
                # children for next level
                my_debug("about to make val_list for query.  parent_ids = ".print_r($parent_ids,1));
                $parent_id_list = Db::make_val_list($parent_ids); #todo rename parent_ids parent_field_vals?
                #$fields = field_list($parent_field, $matching_field_on_parent);
                $fields = field_list_all_rels($parent_relationships);
                $sql = "
                    select $fields
                    from $root_table
                    where $parent_field in $parent_id_list
                    $order_by_limit
                ";
                my_debug("sql = '$sql'\n\n");
                $rows = Db::sql($sql);

                # the parent_node already has the children (which are about to be parents)
                # that are in the parent_id_list
                my_debug("  starting loop thru rows, {\n");
                foreach ($rows as $row) {
                    $this_parent_id = $row[$parent_field];
                    $id = $row['id'];
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

                        # add children - #todo cleanup
                        if (!isset($parent->children)) {
                            #my_debug("this_parent_id = ".print_r($parent_nodes->{$this_parent_id},1)."\n");
                            my_debug("creating new children container for $this_parent_id to add $row[name]\n");
                            $parent->children = new stdClass();
                        }
                        else {
                            my_debug("children container for $this_parent_id already exists, adding $row[name]\n");
                        }
                        $children = $parent->children;

                        my_debug("adding child '$parent_match_val' within container for '$this_parent_id': ".print_r($child,1));
                        # children off node directly
                        $children->{$parent_match_val} = $child;
                        my_debug("now $this_parent_id looks like this: ".print_r($parent_nodes->{$this_parent_id},1));
                    }
                    else {
                        my_debug("WARNING don't actually have the parent $this_parent_id at all, let alone a children container\n");
                        my_debug("skipping this node\n");
                    }

                    # add val to each applicable relationship
                    foreach ($parent_relationships as $this_rel_no => $this_parent_relationship) {
                        #todo #fixme when relationships can span tables, make sure to check for correct table
                        $this_matching_field_on_parent = $this_parent_relationship['matching_field_on_parent'];
                        $this_parent_match_val = $row[$this_matching_field_on_parent];
                        if ($this_parent_match_val) {
                            my_debug("adding $row[name] to children_this_rel->'$parent_match_val', relationship_no = $this_rel_no\n");
                            if (!isset($all_children_by_relationship[$this_rel_no])) {
                                $all_children_by_relationship[$this_rel_no] = new stdClass();
                            }
                            $all_children_by_relationship[$this_rel_no]->{$this_parent_match_val} = $child;
                            if ($row['name'] == 'Rod Frank') {
                                my_debug("all_children_by_relationship[$this_rel_no] now looks like this: ".print_r($all_children_by_relationship[$this_rel_no],1));
                            }
                        }
                        else {
                            my_debug("no parent_match_val, not adding $row[name] to children_this_rel->'$parent_match_val', relationship_no = $relationship_no\n");
                        }
                    }

                    #$parent_vals_next_lev[] = $parent_match_val;
                    $more_children_to_look_for = true;
                }
                my_debug("  }\n");
            }
            #$parent_vals_next_lev_by_relationship[$relationship_no] = $parent_vals_next_lev;
            #$all_children_by_relationship[$relationship_no] = $children_this_rel;
            my_debug("}\n");
        }

        if ($more_children_to_look_for) {
            add_tree_lev_by_lev(
                $all_nodes_by_id,
                $all_children_by_relationship,
                #$parent_vals_next_lev_by_relationship,
                $root_table, $order_by_limit,
                #$parent_field, $matching_field_on_parent
                $parent_relationships
            );
        }
    }

    function get_tree(
        $root_table, $root_cond, $order_by_limit=null,
        $parent_relationships
    ) {
        #$parent_vals_next_lev_by_relationship = array();

        $parent_relationship = $parent_relationships[0]; #todo #fixme support more than one
        $parent_field = $parent_relationship['parent_field'];
        $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];

        $fields = field_list_all_rels($parent_relationships);
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

            $parent_field = $parent_relationship['parent_field'];
            $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];
            #$parent_vals_next_lev = array();
            $parent_nodes_this_rel = new stdClass();

            foreach ($rows as $row) {
                my_debug("adding node ".print_r($row,1));
                $id = $row['id'];
                my_debug("matching_field_on_parent = $matching_field_on_parent\n");
                $parent_match_val = $row[$matching_field_on_parent];

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
