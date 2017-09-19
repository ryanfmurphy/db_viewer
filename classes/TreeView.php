<?php
    class TreeView {

        public static function get_default_tree_url($primary_key) {

            $tree_view_uri = Config::$config['tree_view_uri'];
            $default_root_table_for_tree_view = Config::$config['default_root_table_for_tree_view'];
            $default_parent_field = Config::$config['default_parent_field'];
            $vary_node_colors = Config::$config['vary_node_colors'];
            $primary_key_field = DbUtil::get_primary_key_field($default_root_table_for_tree_view);

            return $tree_view_uri."?root_table=$default_root_table_for_tree_view"
                ."&root_cond=id = '$primary_key'"
                ."&parent_relationships[0][child_table]=$default_root_table_for_tree_view"
                ."&parent_relationships[0][parent_table]=$default_root_table_for_tree_view"
                ."&parent_relationships[0][parent_field]=$default_parent_field"
                ."&parent_relationships[0][matching_field_on_parent]=$primary_key_field"
                ."&parent_relationships[0][condition]="
                #."&order_by_limit=order+by+time_added+desc"
                ."&name_cutoff=50"
                ."&root_nodes_w_child_only="
                ."&use_stars_for_node_size=0"
                ."&vary_node_colors=".(int)$vary_node_colors;
        }

        #todo move to a new class TreeView
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

        public static function get_full_tree_url($primary_key) {
            $tree_url = null;

            if (Config::$config['store_tree_views_in_db']) {
                $rows = Db::sql("select tree_url('$primary_key')");

                if (count($rows) >= 1) {
                    $tree_url = $rows[0]['tree_url'];
                }
            }

            # no tree stored in DB? use default url
            if (!$tree_url) {
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
    }
