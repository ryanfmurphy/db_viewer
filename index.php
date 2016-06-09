<?php
{
    { # init - #todo don't rely on DbViewer
        include("../db_viewer/init.php");
    }

    { #vars
        $table = "example_table";

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
                if ($table == "example_table") {
                    $fields2skip = array("example_field2omit");
                }
                else {
                    $fields2skip = array("iid","id","time");
                }
            }
        }
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
    </head>
    <body>
        <p id="whoami">Dash</p>
        <h1>
            <code><?= $table ?></code> table
        </h1>

        <form id="mainForm" action="/ormrouter/create_<?= $table ?>">
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
                <label for="<?= $name ?>"><?= $name ?></label>
                <<?= $inputTag ?> name="<?= $name ?>"><?= "</$inputTag>" ?>
            </div>
<?php
    }
?>
            <input type="submit" />
        </form>

    </body>
</html>
