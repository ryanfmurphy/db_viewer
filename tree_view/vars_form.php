<html>
    <head>
        <style>
        form {
            font-family: sans-serif;
            margin-left: auto;
            margin-right: auto;
            width: 25rem;
        }
        form#vars_form label {
            display: block;
        }
        form#vars_form div {
            margin: 1rem auto;
        }
        form#vars_form input[type=text] {
            width: 100%;
        }
        h2 {
            font-size: 125%;
        }
        #add_relationship_link {
            cursor: pointer;
            color: blue;
        }
        #add_relationship_link:hover {
            text-decoration: underline;
        }
        #relationship_template {
            display: none;
        }
        </style>
    </head>
    <body>
        <form id="vars_form" action="" target="_blank">
            <h1>Tree View</h1>
            <h2>Select Root Nodes</h2>
            <div>
                <label>Root Table</label>
                <input name="root_table" value="<?= $root_table ?>" type="text">
            </div>
            <div>
                <label>Root Condition</label>
                <input name="root_cond" value="<?= $root_cond ?>" type="text">
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
                    <label>Table of Child...</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][child_table]"
                            value="<?= $relationship['child_table'] ?>"
                            type="text">
                </div>
                <div>
                    <label>...hooking to Table of Parent</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][parent_table]"
                            value="<?= $relationship['parent_table'] ?>"
                            type="text">
                </div>
                <div>
                    <label>...by Field on Child...</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][parent_field]"
                            value="<?= $relationship['parent_field'] ?>"
                            type="text">
                </div>
                <div>
                    <label>...matching Field on Parent</label>
                    <input  name="parent_relationships[<?= $rel_no ?>][matching_field_on_parent]"
                            value="<?= $relationship['matching_field_on_parent'] ?>"
                            type="text">
                </div>
            </div>
<?php
    }

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

            <h3>Misc</h3>
            <div>
                <label>Order By / Limit</label>
                <input name="order_by_limit" value="<?= $order_by_limit ?>" type="text">
            </div>
            <div>
                <label>Name Cutoff</label>
                <input name="name_cutoff" value="<?= $name_cutoff ?>" type="text">
            </div>
            <div>
                <label>Only Include Root Nodes with at least 1 Child</label>
                <input name="root_nodes_w_child_only"
                       value="<?= $root_nodes_w_child_only ?>"
                       type="text">
            </div>
            <div>
                <input type="submit">
            </div>
        </form>
<?php
    # template for adding new relationship forms (hidden)
    render_relationship_form(null, 0);
?>
    </body>
</html>
