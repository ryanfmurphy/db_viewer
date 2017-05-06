<html>
    <head>
        <style>
        form {
            font-family: sans-serif;
            margin-left: auto;
            margin-right: auto;
            width: 25rem;
        }
        form#choose_vars label {
            display: block;
        }
        form#choose_vars div {
            margin: 1rem auto;
        }
        form#choose_vars input[type=text] {
            width: 100%;
        }
        h2 {
            font-size: 125%;
        }
        </style>
    </head>
    <body>
        <form id="choose_vars" action="">
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
            <div>
                <label>Field on Child...</label>
                <input name="parent_field" type="text">
            </div>
            <div>
                <label>...matching Field on Parent</label>
                <input name="matching_field_on_parent" type="text">
            </div>
            <div>
                <label>Order By / Limit</label>
                <input name="order_by_limit" type="text">
            </div>
            <div>
                <label>Name Cutoff</label>
                <input name="name_cutoff" type="text">
            </div>
            <div>
                <input type="submit">
            </div>
        </form>
    </body>
</html>
