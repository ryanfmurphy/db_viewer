<?php
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

    function my_debug($msg) {
        # echo $msg
    }

    # starting with an array of $parent_nodes,
    # look in the DB and add all the child_nodes
    function add_tree_lev_by_lev(
        $parent_nodes, $parent_ids, $root_table, $order_by_limit=null,
        #$parent_field='parent_id', $matching_field_on_parent='id'
        $parent_relationships
    ) {
        $parent_relationship = $parent_relationships[0]; #todo #fixme support more than one
        $parent_field = $parent_relationship['parent_field'];
        $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];

        if (count($parent_ids) > 0) {
            # children for next level
            $parent_id_list = Db::make_val_list($parent_ids); #todo rename parent_ids parent_field_vals?
            $fields = field_list($parent_field, $matching_field_on_parent);
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
            foreach ($rows as $row) {
                $this_parent_id = $row[$parent_field];
                $id = $row['id'];
                my_debug("matching_field_on_parent = $matching_field_on_parent\n");
                $parent_match_val = $row[$matching_field_on_parent];
                $parent_vals_this_lev = array();

                #todo cleanup
                if (!isset($parent_nodes->$this_parent_id->children)) {
                    my_debug("this_parent_id = ".print_r($parent_nodes->{$this_parent_id},1)."\n");
                    $parent_nodes->{$this_parent_id}->children = new stdClass();
                }
                $children = $parent_nodes->{$this_parent_id}->children;

                #todo #fixme can I just add the whole row?
                $child = (object)array(
                    'id' => $id,
                    'name' => $row['name'],
                    $parent_field => $row[$parent_field],
                    $matching_field_on_parent => $row[$matching_field_on_parent]
                );
                my_debug("adding child at '$parent_match_val': ".print_r($child,1));
                $children->{$parent_match_val} = $child;

                $parent_vals_this_lev[] = $parent_match_val;

                add_tree_lev_by_lev(
                    $children, $parent_vals_this_lev, $root_table, $order_by_limit,
                    #$parent_field, $matching_field_on_parent
                    $parent_relationships
                );
            }
        }
    }

    function get_tree(
        $root_table, $root_cond, $order_by_limit=null,
        #$parent_field='parent_id', $matching_field_on_parent='id'
        $parent_relationships
    ) {
        $parent_relationship = $parent_relationships[0]; #todo #fixme support more than one
        $parent_field = $parent_relationship['parent_field'];
        $matching_field_on_parent = $parent_relationship['matching_field_on_parent'];

        $fields = field_list($parent_field, $matching_field_on_parent);
        $sql = "
            select $fields
            from $root_table
            where $root_cond
            $order_by_limit
        ";
        my_debug("root sql = '$sql'\n\n");
        $rows = Db::sql($sql);

        $parent_nodes = new stdClass();
        $parent_vals_this_lev = array();

        foreach ($rows as $row) {
            my_debug("adding node ".print_r($row,1));
            $tree_node = new stdClass();
            $id = $row['id'];
            my_debug("matching_field_on_parent = $matching_field_on_parent\n");
            $parent_match_val = $row[$matching_field_on_parent];
            if ($parent_match_val) {
                $parent_nodes->{$parent_match_val} = $tree_node;

                $parent_nodes->{$parent_match_val}->$parent_field = $row[$parent_field];
                $parent_nodes->{$parent_match_val}->id = $id;
                $parent_nodes->{$parent_match_val}->name = $row['name'];
                $parent_vals_this_lev[] = $row[$matching_field_on_parent];
            }
            else {
                my_debug("no parent_match_val for this one, id=$id - skipping\n");
            }
        }
        add_tree_lev_by_lev(
            $parent_nodes, $parent_vals_this_lev, $root_table,
            $order_by_limit, #$parent_field, $matching_field_on_parent
            $parent_relationships
        );
        return $parent_nodes;
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
        #$parent_field, $matching_field_on_parent
        $parent_relationships
    );
    $tree = unkey_tree($tree);

    die(
        json_encode(
        #print_r(
            array(
                'name' => '',
                'children' => $tree,
            )
        )
    );
