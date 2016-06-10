<?php
{
    { # init - #todo don't rely on DbViewer
        include("../db_viewer/init.php");
        $requestVars = array_merge($_GET, $_POST);
    }

    { #vars
        $table = isset($requestVars['table'])
                    ? $requestVars['table']
                    : "example_table";

        { # get fields
            $rows = Util::sql("
                select
                    table_schema, table_name, column_name
                from information_schema.columns
                where table_name='$table'
            ");
            $fields = array_map(
                function($x) {
                    return $x['column_name'];
                },
                $rows
            );
            #$fields = array("name","txt");

            { #todo fields2skip
                switch ($table) {
                    case "example_table": {
                        $fields2skip = array(
                            "iid","id","time",
                            "tags", #todo
                        );
                    } break;

                    default:
                        $fields2skip = array("iid","id","time");
                }
            }
        }

        $action = "create"; #todo or "update"
    }
}
?>




<html>
    <head>
        <title>Dash</title>
        <style type="text/css">
body {
    font-family: sans-serif;
    margin: 3em;
}
form#mainForm {
}
form#mainForm label {
    min-width: 8rem;
    display: inline-block;
    vertical-align: middle;
}
.formInput {
    margin: 2rem auto;
}

.formInput input,
.formInput textarea
{
    width: 30rem;
    display: inline-block;
    vertical-align: middle;
}

#whoami {
    font-size: 80%;
}
        </style>
        <script>
        function setFormAction(url) {
            var form = document.getElementById('mainForm');
            form.action = url;
        }
        </script>
    </head>
    <body>
        <p id="whoami">Dash</p>
        <h1>
            <code><?= $table ?></code> table
        </h1>

        <form id="mainForm" action="/ormrouter/<?= $action ?>_<?= $table ?>">
<?php
    foreach ($fields as $name) {
        if (in_array($name, $fields2skip)) {
            continue;
        }
        $inputTag = ($name == "txt"
                        ? "textarea"
                        : "input");
?>
            <div class="formInput">
                <label for="<?= $name ?>">
                    <?= $name ?>
                </label>
                <<?= $inputTag ?> name="<?= $name ?>"><?= "</$inputTag>" ?>
            </div>
<?php
    }
?>
            <div id="submits">
                <input onclick="setFormAction('/ormrouter/create_<?= $table ?>')" value="Create" type="submit" />
                <input onclick="setFormAction('/ormrouter/update_<?= $table ?>')" value="Update" type="submit" />
                <input onclick="setFormAction('/ormrouter/get_<?= $table ?>s?use_db_viewer=1')" value="View" type="submit" />
                    <!-- set action="/ormrouter/get_<?= $table ?>s?use_db_viewer=1" -->
                <input onclick="setFormAction('/ormrouter/delete_<?= $table ?>')" value="Delete" type="submit" />
            </div>
        </form>

    </body>
</html>
