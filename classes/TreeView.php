<?php
    class TreeView {

        public static function get_default_tree_url_for_root_cond($root_cond, $root_table=null) {

            $default_root_table_for_tree_view =
                Config::$config['default_root_table_for_tree_view'];
            if ($root_table === null) $root_table = $default_root_table_for_tree_view;
            $primary_key_field = DbUtil::get_primary_key_field($root_table);

            $tree_view_uri = Config::$config['tree_view_uri'];
            $default_parent_field = Config::$config['default_parent_field'];
            $vary_node_colors = Config::$config['vary_node_colors'];
            $default_tree_relationship_condition = Config::$config['default_tree_relationship_condition'];
            $default_tree_relationship_order_by_limit = Config::$config['tree_view_relationship_order_by_limit'];

            $return_url = $tree_view_uri."?root_table=$root_table"
                ."&root_cond=$root_cond"
                ."&parent_relationships[0][child_table]=$default_root_table_for_tree_view"
                ."&parent_relationships[0][parent_table]=$default_root_table_for_tree_view"
                ."&parent_relationships[0][parent_field]=$default_parent_field"
                ."&parent_relationships[0][matching_field_on_parent]=$primary_key_field"
                ."&parent_relationships[0][condition]=$default_tree_relationship_condition"
                .($default_tree_relationship_order_by_limit
                    ? "&parent_relationships[0][order_by_limit]=$default_tree_relationship_order_by_limit"
                    : '')
                #."&order_by_limit=order+by+time_added+desc"
                ."&name_cutoff=50"
                ."&root_nodes_w_child_only="
                ."&use_stars_for_node_size=0"
                ."&vary_node_colors=".(int)$vary_node_colors;
            return $return_url;

        }

        public static function get_default_tree_url($primary_key) {

            $default_root_table_for_tree_view = Config::$config['default_root_table_for_tree_view'];

            $root_cond = "id = '$primary_key'";
            
            if (Config::$config['show_matching_rows_on_tree_sideline']) {
                $aliases = Db::get_row_aliases($default_root_table_for_tree_view, $primary_key);

                if (is_array($aliases)) {
                    { # figure out tags condition
                        $first = true;
                        $tags_condition = '';
                        foreach ($aliases as $alias) {
                            if ($first) {
                                $first = false;
                            }
                            else {
                                $tags_condition .= ' or';
                            }
                            $alias_quoted = Db::sql_literal($alias);
                            $tags_condition .= " tags @> array[$alias_quoted]";
                        }
                    }
                    $sideline_addl_requirements = Config::$config['sideline_addl_requirements'];
                    $sideline_condition = "($tags_condition) and ($sideline_addl_requirements)";
                    $root_cond .= " or ($sideline_condition)";
                }
                else {
                    # leave root_cond alone
                }
            }

            return self::get_default_tree_url_for_root_cond($root_cond);

        }

        public static function get_tree_url($primary_key) {
            $tree_view_uri = Config::$config['tree_view_uri'];
            return $tree_view_uri."?root_id=$primary_key";
        }

        public static function include_js__get_tree_url() {
            $tree_view_uri = Config::$config['tree_view_uri'];
?>
    function get_tree_url(primary_key) {
        var tree_view_uri = <?= Utility::quot_str_for_js($tree_view_uri) ?>;
        return tree_view_uri + "?root_id=" + primary_key;
    }
<?php
        }

        public static function get_full_tree_url($primary_key, &$used_tree_row_from_db=null) {
            $tree_url = null;
            $used_tree_row_from_db = false; # set var for caller

            if (Config::$config['store_tree_views_in_db']) {
                $rows = Db::sql("select tree_url('$primary_key')");

                if (count($rows) >= 1) {
                    $tree_url = $rows[0]['tree_url'];
                }
            }

            # no tree stored in DB? use default url
            if ($tree_url) {
                $used_tree_row_from_db = true; # set var for caller
            }
            else {
                $tree_url = self::get_default_tree_url(
                    $primary_key
                );
            }

            return $tree_url;
        }

        #todo - maybe #factor between table_view and obj_editor's tree button?
        public static function echo_tree_button(
            $obj_editor_uri, $tablename_no_quotes, $primary_key
        ) {
            $url = self::get_tree_url($primary_key);
?>
        <td class="action_cell">
            <a  class="row_delete_button link"
                target="_blank"
                href="<?= $url ?>"
            >
                tree
            </a>
        </td>
<?php
        }

        public static function echo_default_view_toggle_link($requestVars) {
            # toggle Default View / Stored View
            if (isset($requestVars['root_id'])) {
                # Stored View
                if (isset($requestVars['use_default_view'])
                    && $requestVars['use_default_view']
                ) {
                    # $_GET, not $requestVars, because we don't want all the expanded vars
                    $storedViewVars = $_GET;
                    unset($storedViewVars['use_default_view']);
?>
                    <a id="edit_vars_link"
                       href="<?= Config::$config['tree_view_uri'] . '?' . http_build_query($storedViewVars) ?>"
                       target="_blank">
                        Stored View
                    </a>
<?php
                }
                # Default View
                else {
?>
                    <a id="edit_vars_link"
                       href="<?= $_SERVER['REQUEST_URI'] ?>&use_default_view=1"
                       target="_blank">
                        Default View
                    </a>
<?php
                }
            }
        }

        public static function get_tree_url_for_whole_table($table) {
            #todo #fixme - decide between these options depending on whether entity inheritance is configured
            #return self::get_default_tree_url_for_root_cond(Db::true_exp(), $table);
            return self::get_default_tree_url_for_root_cond("relname = '$table'");  # and parent_ids = '{}'
                                                                                    # ^ this is good but is weird if e.g. the $table row
                                                                                    #   wants to have a non-$table parent
        }

    }
