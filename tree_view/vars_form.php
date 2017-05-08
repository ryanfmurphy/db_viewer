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
        </style>
    </head>
    <body>
        <form id="vars_form" action="" target="_blank">
            <h1>Table View</h1>
            <h2>Select Root Nodes</h2>
            <div>
                <label>Root Table</label>
                <input name="root_table" type="text">
            </div>
            <div>
                <label>Root Condition</label>
                <input name="root_cond" type="text">
            </div>

            <div class="relationship" data-rel_no=0>
                <h3>Relationship 0</h3>
                <div>
                    <label>Table of Child...</label>
                    <input name="parent_relationships[0][child_table]" type="text">
                </div>
                <div>
                    <label>...hooking to Table of Parent</label>
                    <input name="parent_relationships[0][parent_table]" type="text">
                </div>
                <div>
                    <label>...by Field on Child...</label>
                    <input name="parent_relationships[0][parent_field]" type="text">
                </div>
                <div>
                    <label>...matching Field on Parent</label>
                    <input name="parent_relationships[0][matching_field_on_parent]" type="text">
                </div>
            </div>

            <script>
            var relationship0_elem = document.querySelector('.relationship');
            var max_rel_no = 0;

            function addRelationshipForm() {
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
                <input name="order_by_limit" type="text">
            </div>
            <div>
                <label>Name Cutoff</label>
                <input name="name_cutoff" type="text">
            </div>
            <div>
                <label>Only Include Root Nodes with at least 1 Child</label>
                <input name="root_node_w_child_only" type="text">
            </div>
            <div>
                <input type="submit">
            </div>
        </form>
    </body>
</html>
