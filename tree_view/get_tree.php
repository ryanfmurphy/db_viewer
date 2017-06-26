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
                                    'arrays',
                                    'sql',
                                    'rel',
                                    'overview',
                                    'tables_n_fields',
                                    #'fields',
                                    'parent_filter',
                                    'loop_child_rows',
                                    'relationship_lists',
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

    { # service the API call
        my_debug(NULL, "parent_relationships: " . print_r($parent_relationships,1));

        if ($backend == 'db') {
            include_once('get_tree_db.php');

            $tree = db_get_tree(
                $root_table, $root_cond, $parent_relationships,
                $order_by_limit, $root_nodes_w_child_only
            );
            if (DEBUG_PRE_UNKEYED) {
                die(print_r($tree,1));
            }
            $tree = unkey_tree($tree);

            $result = array(
                '_node_name' => '',
                'children' => $tree,
            );
        }
        elseif ($backend == 'fs') {
            include_once('get_tree_fs.php');

            $root_dir = Config::$config['fs_tree_default_root_dir'];;
            if (!$root_dir) {
                die("Can't do filesystem-based tree without defining 'fs_tree_default_root_dir' in db_config");
            }
            $tree = fs_get_tree($root_dir);
            $result = fs_prep_data_for_json($tree, $root_dir);
        }
        else {
            die("unknown backend $backend");
        }

        die(
            encode_response($result)
        );
    }

