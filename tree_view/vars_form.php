<html>
    <head>
        <style>
<?php
    $gray = '#888';
?>
        form {
            font-family: sans-serif;
            margin-left: auto;
            margin-right: auto;
            width: 30rem;
        }
        form#vars_form label {
            display: block;
            color: <?= $gray ?>;
        }
        form#vars_form div {
            margin: 1rem auto;
        }
        form#vars_form input[type=text] {
            width: 100%;
            font-size: 100%;
        }
        form#vars_form input[type=submit] {
            margin: 1em auto;
            /* HACK: for some reason centering wasn't happening */
            margin-left: 7em;
            font-size: 150%;
        }

        h1 {
            text-align: center;
        }
        h2 {
            font-weight: normal;
            margin-top: 1.5em;
        }
        h3 {
        }
        #add_relationship_link {
            cursor: pointer;
            color: blue;
            text-align: center;
            width: 100%;
        }
        #add_relationship_link:hover {
            text-decoration: underline;
        }
        #relationship_template {
            display: none;
        }
        form#vars_form div#rel_order_comment {
            font-size: 80%;
            margin-top: -1em;
            color: <?= $gray ?>;
        }

        form#vars_form .relationship {
            background: #f3f3f3;
            padding: .5em 1.2em;
            margin: 1.5em auto;
        }

        form#vars_form .relationship label {
            text-align: right;
            display: inline-block;
            width: 39%;
            vertical-align: baseline;
            font-size: 90%;
            margin-left: 3em;
        }
        form#vars_form .relationship input {
            display: inline-block;
            width: 49%;
            vertical-align: baseline;
            font-size: 90%;
        }
        </style>
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
    function render_relationship_form($relationship, $rel_no) {
        $is_template = ($relationship === null);
        if ($is_template) {
            $relationship = array(
                'child_table' => null,
                'parent_table' => null,
                'parent_field' => null,
                'matching_field_on_parent' => null,
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
            </div>
<?php
    }
?>

<?php
    foreach ($parent_relationships as $rel_no => $relationship) {
        render_relationship_form($relationship, $rel_no);
    }
?>

            <script>
            var max_rel_no = <?= count($parent_relationships)-1 ?>;

            function addRelationshipForm() {
                var relationship0_elem = document.getElementById('relationship_template');
                var new_elem = document.createElement('div');
                new_elem.classList.add('relationship');
                max_rel_no++;
                var new_html = relationship0_elem.innerHTML
                                .replace(/0/g, max_rel_no.toString());
                new_elem.innerHTML = new_html;

                var form = document.getElementById('vars_form');
                var add_rel_link = document.getElementById('add_relationship_link');
                form.insertBefore(new_elem, add_rel_link);
            }
            </script>

            <div id="add_relationship_link" onclick="addRelationshipForm()">
                + Add Relationship
            </div>

            <h2>More Settings</h2>
            <div>
                <label>Order By / Limit (include <code>order by</code> etc)</label>
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
