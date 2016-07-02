<?php
{
    { # init
        { # includes & misc
            $trunk = __DIR__;
            include("$trunk/init.php");
            $requestVars = array_merge($_GET, $_POST);
        }
    }

    { # main logic
        { # vars
            $schemas_in_path = DbUtil::schemas_in_path($search_path);
            $schemas_val_list = DbUtil::val_list_str($schemas_in_path);

            $table = isset($requestVars['table'])
                        ? $requestVars['table']
                        : null;
        }

        { # get fields
            if ($table) {
                # get fields of table from db
                $dbRowsReFields = Db::sql("
                    select
                        table_schema, table_name, column_name
                    from information_schema.columns
                    where table_name='$table'
                        and table_schema in ($schemas_val_list)
                "
                    #todo fix issue where redundant tables in multiple schemas
                        # leads to redundant fields
                );
                $fields = array_map(
                    function($x) {
                        return $x['column_name'];
                    },
                    $dbRowsReFields
                );

                { # fields2omit
                    $fields2omit = $fields2omit_global;

                    { # from config
                        $tblFields2omit = (isset($fields2omit_by_table[$table])
                                                ? $fields2omit_by_table[$table]
                                                : array());

                        $fields2omit = array_merge($fields2omit, $tblFields2omit);
                    }

                    { # 'omit' get var - allow addition of more omitted fields
                        $omit = isset($requestVars['omit'])
                                    ? $requestVars['omit']
                                    : null;
                        $omitted_fields = explode(',', $omit);
                        $fields2omit = array_merge($fields2omit, $omitted_fields);
                    }
                }

                { # fields2keep - allow addition of more kept fields
                    $keep = isset($requestVars['keep'])
                                ? $requestVars['keep']
                                : null;
                    $kept_fields = explode(',', $keep);
                    $fields2keep = $kept_fields;
                }
            }
        }
    }

    { # functions
        function echoFormFieldHtml($name) {
            { # vars
                $inputTag = (( $name == "txt"
                               || $name == "src"
                             )
                                    ? "textarea"
                                    : "input");
            }
            { # html
?>
        <div class="formInput" remove="true">
            <label for="<?= $name ?>"
                   onclick="removeFormField(this)"
            >
                <?= $name ?> 
            </label>
            <<?= $inputTag ?> name="<?= $name ?>"><?= "</$inputTag>" ?> 
        </div>
<?php
            }
        }

        function echoFormFieldHtml_JsFormat($name) {
            { ob_start();
                echoFormFieldHtml("{{".$name."}}");
                $txt = ob_get_clean();
            }
            $txt = str_replace("\n", "\\n\\"."\n", $txt);
            $txt = str_replace("'", "\\'", $txt);
            $txt = preg_replace(
                        "/
                            {{
                                ( [A-Za-z0-9_]+ )
                            }}
                        /x",
                        "'+\\1+'",
                        $txt
                   );
            echo "'$txt'";
        }
    }

}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Dash</title>
        <style type="text/css">

body {
    font-family: sans-serif;
    margin: 3em;
}

<?php /* # $background='dark'
?>
body {
    background: black;
    color: white;
}
a {
    color: yellow;
}
<?php */
?>

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
.formInput label {
    cursor: not-allowed; /* looks like delete */
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

#table_header > * {
    display: inline-block;
    vertical-align: middle;
    margin: .5rem;
}

#addNewField {
    font-size: 150%;
    cursor: pointer;
}

        </style>

        <script>

    function setFormAction(url) {
        var form = document.getElementById('mainForm');
        form.action = url;
    }

    function openAddNewField(elem) { // #todo don't need elem: it's always that +
        console.log(elem);
        var parentElem = elem.parentNode;
        var grandParent = parentElem.parentNode;
        console.log(parentElem);
        console.log(grandParent);

        var tempContainer = document.createElement('div');
        var fieldName = prompt("Enter Field Name to add:");
        if (fieldName) {
            var html = <?= echoFormFieldHtml_JsFormat('fieldName'); ?>;
            html = html.trim();
            tempContainer.innerHTML = html;
            var newField = tempContainer.firstChild;
            console.log('newField', newField);
            grandParent.insertBefore(newField, parentElem);
        }
    }

    function removeFormField(clickedElem) {
        var formRow = clickedElem.parentNode;
        console.log('formRow', formRow);
        var parentElem = formRow.parentNode;
        console.log('parentElem', parentElem);
        parentElem.removeChild(formRow);
    }

        </script>
    </head>
    <body>
<?php
    {
        if ($table) {
?>
        <p id="whoami">Dash</p>
        <div id="table_header">
            <h1>
                <code><?= $table ?></code> table
            </h1>
            <a href="/db_viewer/db_viewer.php?sql=select * from <?= $table ?>"
               target="_blank"
            >
                view all
            </a>
        </div>

        <!-- action gets set via js when you submit -->
        <form id="mainForm" target="_blank">
<?php
            { # create form fields
                foreach ($fields as $name) {
                    if (in_array($name, $fields2omit)
                        && !in_array($name, $fields2keep)
                    ) {
                        continue;
                    }
                    echoFormFieldHtml($name);
                }
            }

            { # dynamically add a new field
                #todo add margin below
?>
            <div class="formInput">
                <span id="addNewField"
                      onclick="openAddNewField(this)"
                >
                    +
                </span>
            </div>
<?php
            }
?>

            <div id="submits">
                <input onclick="setFormAction('/ormrouter/create_<?= $table ?>')"
                    value="Create" type="submit"
                />
                <input onclick="setFormAction('/ormrouter/update_<?= $table ?>')"
                    value="Update" type="submit"
                />
                <input onclick="setFormAction('/ormrouter/view_<?= $table ?>')"
                    value="View" type="submit"
                />
                <input onclick="setFormAction('/ormrouter/delete_<?= $table ?>')"
                    value="Delete" type="submit"
                />
            </div>
        </form>
<?php
        }
        else {
            include("choose_table.php");
        }
    }
?>
    </body>
</html>
