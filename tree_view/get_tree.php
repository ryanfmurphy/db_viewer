<?php
    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
    }

    $root_table = isset($requestVars['root_table'])
                    ? $requestVars['root_table']
                    : die('need root_table');
    $root_cond = isset($requestVars['root_cond'])
                 && $requestVars['root_cond']
                    ? $requestVars['root_cond']
                    : 'parent_id is null';

    # starting with an array of $parent_nodes,
    # look in the DB and add all the child_nodes
    function add_tree_lev_by_lev($parent_nodes, $parent_ids, $root_table) {
        if (count($parent_ids) > 0) {
            # children for next level
            $parent_id_list = Db::make_val_list($parent_ids);
            $sql = "
                select id, name, parent_id
                from ".$root_table."
                where parent_id in $parent_id_list
            ";
            $rows = Db::sql($sql);

            # the parent_node already has the children (which are about to be parents)
            # that are in the parent_id_list
            foreach ($rows as $row) {
                $this_parent_id = $row['parent_id'];
                $id = $row['id'];
                $ids_this_lev = array();

                if (!isset($parent_nodes->$this_parent_id->children)) {
                    $parent_nodes->$this_parent_id->children = new stdClass();
                }
                $children = $parent_nodes->$this_parent_id->children;

                $child = (object)array(
                    'id' => $id,
                    'name' => $row['name'],
                );
                $children->$id = $child;

                $ids_this_lev[] = $id;

                add_tree_lev_by_lev(
                    $children, $ids_this_lev, $root_table
                );
            }
        }
    }

    function get_tree($root_table, $root_cond) {
        #todo define where condition for root

        $sql = "
            select id, name, parent_id
            from $root_table
            where $root_cond
        ";
        $rows = Db::sql($sql);

        $parent_nodes = new stdClass();
        $ids_this_lev = array();

        foreach ($rows as $row) {
            #echo "adding node ".print_r($row,1);
            $tree_node = new stdClass();
            $id = $row['id'];
            $parent_nodes->$id = $tree_node;

            $parent_nodes->$id->parent_id = $row['parent_id'];
            $parent_nodes->$id->id = $id;
            $parent_nodes->$id->name = $row['name'];
            $ids_this_lev[] = $id;
        }
        add_tree_lev_by_lev(
            $parent_nodes, $ids_this_lev, $root_table
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

    $tree = get_tree($root_table, $root_cond);
    $tree = unkey_tree($tree);

    die(
        json_encode(
            array(
                'name' => '',
                'children' => $tree,
            )
        )
    );