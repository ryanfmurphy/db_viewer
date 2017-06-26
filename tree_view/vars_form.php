<html>
    <head>
        <?php include('vars_form.css.php') ?>
    </head>
    <body>
        <form id="vars_form" action="" target="_blank">
            <h1>Tree View 🌳</h1>
            <h2>Select Root Nodes</h2>
            <div>
                <label>Root Table</label>
                <input name="root_table" value="<?= $root_table ?>" type="text">
            </div>
            <div>
                <label>Root Condition</label>
                <input name="root_cond" value="<?= $root_cond ?>" type="text">
            </div>

            <h2>Define Relationships</h2>
            <div id="rel_order_comment">
                Relationships can be in any order, order does not matter
            </div>

<?php
    #todo move to e.g. Utility
    function contains_nonempty_item($array) {
        if (!is_array($array)) {
            return false;
        }
        foreach ($array as $key => $value) {
            if ($value) {
                return true;
            }
        }
    }

    #todo move to e.g. Utility
    function filter_to_keys($array, $keys) {
        $result = array();
        foreach ($array as $key => $value) {
            if (in_array($key, $keys)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    function get_optional_fields($relationship) {
        if (is_array($relationship)) {
            return filter_to_keys(
                $relationship,
                array('condition', 'parent_filter_field', 'parent_filter_field_val')
            );
        }
        else {
            return array();
        }
    }

    function render_relationship_form($relationship, $rel_no) {
        $is_template = ($relationship === null);
        if ($is_template) {
            $relationship = array(
                'child_table' => null,
                'parent_table' => null,
                'parent_field' => null,
                'matching_field_on_parent' => null,
                # optional fields
                'condition' => null,
                'parent_filter_field' => null,
                'parent_filter_field_val' => null,
            );
        }
?>
            <div class="relationship" data-rel_no=<?= $rel_no ?>
                <?= ($is_template
                        ? 'id="relationship_template"'
                        : '') ?>
            >
                <h3>Relationship <?= $rel_no ?></h3>
                <div>
                    <label>Table of Child:</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][child_table]"
                            value="<?= $relationship['child_table'] ?>"
                            type="text">
                </div>
                <div>
                    <label>hooking to Table of Parent:</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][parent_table]"
                            value="<?= $relationship['parent_table'] ?>"
                            type="text">
                </div>
                <div>
                    <label>by Field on Child:</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][parent_field]"
                            value="<?= $relationship['parent_field'] ?>"
                            type="text">
                </div>
                <div>
                    <label>matching Field on Parent:</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][matching_field_on_parent]"
                            value="<?= $relationship['matching_field_on_parent'] ?>"
                            type="text">
                </div>
                <div class="relationship_header"
                     onclick="showOptionalFields(this)">
                    Optional Filters
                </div>
<?php
        $optional_fields = get_optional_fields($relationship);
        $is_open = contains_nonempty_item($optional_fields);
        $maybe_open = ($is_open ? 'open' : '');
?>
                <div class="optional_fields <?= $maybe_open ?>">
                    <div>
                        <label>Filter Condition:</label>
                        <input  name="parent_relationships[<?= $rel_no ?>][condition]"
                                value="<?= $relationship['condition'] ?>"
                                type="text">
                    </div>
                    <div>
                        <label>Parent Filter Field:</label>
                        <input  name="parent_relationships[<?= $rel_no ?>][parent_filter_field]"
                                value="<?= $relationship['parent_filter_field'] ?>"
                                type="text">
                    </div>
                    <div>
                        <label>Parent Filter Field Value:</label>
                        <input  name="parent_relationships[<?= $rel_no ?>][parent_filter_field_val]"
                                value="<?= $relationship['parent_filter_field_val'] ?>"
                                type="text">
                    </div>
                </div>
            </div>
<?php
    }

    foreach ($parent_relationships as $rel_no => $relationship) {
        render_relationship_form($relationship, $rel_no);
    }
?>
            <?php include('vars_form.js.php') ?>

            <div id="add_relationship_link" onclick="addRelationshipForm()">
                + Add Relationship
            </div>

            <h2>More Settings</h2>
            <div>
                <label>Root Order By / Limit (include <code>order by</code> etc)</label>
                <input name="order_by_limit" value="<?= $order_by_limit ?>" type="text">
            </div>
            <div>
                <label>Cutoff Names at Approximately X Characters</label>
                <input name="name_cutoff" value="<?= $name_cutoff ?>" type="text">
            </div>
            <div>
                <label>Only Include Root Nodes with at least 1 Child</label>
                <input name="root_nodes_w_child_only"
                       value="<?= $root_nodes_w_child_only ?>"
                       type="text">
            </div>
            <div>
                <label>Use Stars for Node Size (more stars = bigger)</label>
                <input name="use_stars_for_node_size"
                       value="<?= $use_stars_for_node_size ?>"
                       type="text">
            </div>
            <div>
                <label>Vary Node Colors based on Table Name?</label>
                <input name="vary_node_colors"
                       value="<?= $vary_node_colors ?>"
                       type="text">
            </div>
            <div>
                <input type="submit" value="Show Tree">
            </div>
        </form>
<?php
    # template for adding new relationship forms (hidden)
    render_relationship_form(null, 0);
?>
    </body>
</html>
