<?php
{ # pre-HTML
    { # init
        { # includes & misc
            $trunk = __DIR__;
            include("$trunk/init.php");
            $requestVars = array_merge($_GET, $_POST);
        }

        { # edit?
            if (isset($edit) && $edit) {
                if (isset($_GET['primary_key'])) {
                    $primary_key_field = 'id'; #todo
                    $primary_key = $_GET['primary_key'];
                    $primary_key = Db::sqlLiteral($primary_key);

                    $table = $_GET['table'];
                    $sql = "
                        select * from $table
                        where $primary_key_field = $primary_key
                    ";
                    $all1rows = Db::sql($sql);

                    if (count($all1rows)) {
                        $row2edit = $all1rows[0];
                    }
                    else {
                        die("couldn't find/edit the row with $primary_key_field = $primary_key");
                    }
                }
                else {
                    die("can't edit with no primary_key");
                }
            }
            else {
                $row2edit = null;
            }
        }
    }

    { # prep logic - get fields from db
        { # vars
            $schemas_in_path = DbUtil::schemas_in_path($search_path);
            $schemas_val_list = DbUtil::val_list_str($schemas_in_path);

            $table = isset($requestVars['table'])
                        ? $requestVars['table']
                        : null;
        }

        { # get fields
            if ($table) {
                { # get fields of table from db, #todo factor into fn
                    { # do query
                        { ob_start();
?>
                            select
                                table_schema, table_name,
                                column_name
                            from information_schema.columns
                            where table_name='<?= $table ?>'
<?php
                            if ($schemas_val_list) {
?>
                                and table_schema in (<?= $schemas_val_list ?>)
<?php
                            }
                            $get_columns_sql = ob_get_clean();
                        }
                        $fieldsRows = Db::sql($get_columns_sql);
                        if (count($fieldsRows) == 0) {
                            die("Table $table doesn't exist");
                        }
                    }

                    { # group by schema
                        $fieldsRowsBySchema = array();
                        foreach ($fieldsRows as $fieldsRow) {
                            $schema = $fieldsRow['table_schema'];
                            $fieldsRowsBySchema[$schema][] = $fieldsRow;
                        }
                    }

                    { # choose 1st schema that applies
                        if ($schemas_in_path) {
                            $schema = null;
                            foreach ($schemas_in_path as $schema_in_path) {
                                if (isset($fieldsRowsBySchema[$schema_in_path])) {
                                    $schema = $schema_in_path;
                                    break;
                                }
                            }
                            if ($schema === null) {
                                die("Whoops!  Couldn't select a DB schema for table $table");
                            }
                        }
                    }

                    { # get just the column_names
                        $fields = array_map(
                            function($x) {
                                return $x['column_name'];
                            },
                            $fieldsRowsBySchema[$schema]
                        );
                    }

                    { # so we can give a warning/notice about it later
                        $multipleTablesFoundInDifferentSchemas =
                            count(array_keys($fieldsRowsBySchema)) > 1;
                    }
                }

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

    { # PHP functions
        function echoFormFieldHtml($name, $row2edit=null) {
            { # vars
                $inputTag = (( $name == "txt"
                               || $name == "src"
                               || $name == "lyrics"
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
<?php
                if ($name == 'artist') {
                    $artists = Db::sql('select name from artist');
?>
            <div class="select_from_options">
                <select name="<?= $name ?>">
                    <option>
                        custom
                    </option>
<?php
                    foreach ($artists as $artist) {
?>
                    <option value="<?= $artist['name'] ?>">
                        <?= $artist['name'] ?>
                    </option>
<?php
                    }
?>
                </select>
                <!--<br>
                <input name="<?= $name ?>" >-->
            </div>
<?php
                }
                else {
?>
            <<?= $inputTag ?>
                name="<?= $name ?>"
<?php
                    if ($row2edit) {
?>
                value="<?= $row2edit[$name] ?>"
<?php
                    }
?>
            ><?= "</$inputTag>" ?> 
<?php
                }

                if ($name == 'duration') {
?>
            <span id="durationTimer" onclick="startTimer()">⧖</span>
<?php
                }
?>
        </div>
<?php
            }
        }

        function jsStringify($txt) {
            // add backslashes at end of line
            $txt = str_replace("\n", "\\n\\"."\n", $txt);
            // escape single quotes
            $txt = str_replace("'", "\\'", $txt);
            // fill-in {{vars}}
            $txt = preg_replace(
                       "/
                           {{
                               ( [A-Za-z0-9_]+ )
                           }}
                       /x",
                       "'+\\1+'",
                       $txt
                   );
            return "'$txt'";
        }

        function echoFormFieldHtml_JsFormat($name, $row2edit=null) {
            { ob_start();
                echoFormFieldHtml("{{".$name."}}", $row2edit);
                $txt = ob_get_clean();
            }
            echo jsStringify($txt);
        }

        function echoSelectTableInputHtml() {
?>
                <input id="selectTable"
                       placeholder="select table"
                       onkeypress="selectTableOnEnter(event)"
                />
<?php
        }

        function echoSelectTableInputHtml_JsFormat() {
            { ob_start();
                echoSelectTableInputHtml();
                $txt = ob_get_clean();
            }
            echo jsStringify($txt);
        }
    }

}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Dash</title>
        <style type="text/css">
<?php
    { # css style
?>
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

#table_header_top > * {
    display: inline-block;
    vertical-align: middle;
    margin: .5rem;
}

#table_header_top > h1 {
    margin-left: 0;
}

#multipleTablesWarning {
    font-size: 80%;
    font-style: italic;
}

#addNewField {
    font-size: 150%;
    cursor: pointer;
}

#durationTimer {
    cursor: pointer;
}

.select_from_options {
    display: inline-block;
}

        </style>
<?php
    }
?>

        <script>

{ // main javascript

    // get array of form inputs / textareas / etc
    function getFormInputs(form) {

        { // fetch all formInputs from document
            var inputs = form.getElementsByTagName('input');
            var textareas = form.getElementsByTagName('textarea');
            var selects = form.getElementsByTagName('select');
        }

        { // build up all formInputs in an array
            var formInputs = [];

            for (var i=0; i<inputs.length; i++) {
                input = inputs[i];
                // exclude submit inputs
                if (input.type != 'submit') {
                    formInputs.push(input);
                }
            }
            for (var i=0; i<textareas.length; i++) {
                item = textareas[i];
                formInputs.push(item);
            }
            for (var i=0; i<selects.length; i++) {
                item = selects[i];
                formInputs.push(item);
            }
        }

        return formInputs;
    }

    // Serialize an array of form elements
    // into a query string - inspired by jQuery
    function serializeForm(formInputs) {

        { // vars
            var prefix,
                pairs = [],

                // add a key-value pair to the array
                addPair = function(key, value) {

                    // Q. is this better/worse than pairs.push()?
                    pairs[ pairs.length ] =
                        encodeURIComponent( key ) + "=" +
                        encodeURIComponent( value == null ? "" : value );

                };
        }

        { // Serialize the form elements
            for (var i = 0; i < formInputs.length; i++) {
                pair = formInputs[i];
                addPair(pair.name, pair.value);
            }
        }

        { // Return the resulting serialization
            return pairs.join( "&" );
        }
    }

    function setFormAction(url) {
        var form = document.getElementById('mainForm');
        form.action = url;
    }

    function submitForm(url, event) {
        var form = document.getElementById('mainForm');

        { // do ajax
            var xhttp = new XMLHttpRequest();
            { // callback
                xhttp.onreadystatechange = function() {
                    if (xhttp.readyState == 4
                        && xhttp.status == 200
                    ) {
                        result = JSON.parse(xhttp.responseText);
                        if (event.altKey) {
                            alert('SQL Query Logged to Console');
                            console.log(result.sql);
                        }
                        else if (result) {
                            alert('Thanks! Row Saved');
                        }
                        else {
                            alert('Failed... Try alt-clicking to see the Query');
                        }
                        console.log(result);
                    }
                };
            }
            { // handle post
                xhttp.open("POST", url, true);
                xhttp.setRequestHeader(
                    "Content-type",
                    "application/x-www-form-urlencoded"
                );

                var data = getFormInputs(form);
                var postData = serializeForm(data);

                // just show the query on altKey
                if (event.altKey) {
                    postData += "&show_sql_query=1"
                }
            }
            xhttp.send(postData);
        }
    }

    // add the new form field html
    // requires the funny container trick
    function createElemFromHtml(html) {
        var tempContainer = document.createElement('div');
        html = html.trim();
        tempContainer.innerHTML = html;
        var newElem = tempContainer.firstChild;
        return newElem;
    }

    function openAddNewField(elem) { // #todo don't need elem: it's always that +
        console.log(elem);
        var plusSignFormInputDiv = elem.parentNode;
        var mainForm = plusSignFormInputDiv.parentNode;
        console.log(plusSignFormInputDiv);
        console.log(mainForm);

        var fieldName = prompt("Enter Field Name to add:");
        if (fieldName) {
            var newField = createElemFromHtml(
                <?= echoFormFieldHtml_JsFormat('fieldName'); ?>
            );
            console.log('newField', newField);
            mainForm.insertBefore(newField, plusSignFormInputDiv);
        }
    }

    function removeFormField(clickedElem) {
        var formRow = clickedElem.parentNode;
        console.log('formRow', formRow);
        var parentElem = formRow.parentNode;
        console.log('parentElem', parentElem);
        parentElem.removeChild(formRow);
    }

    function selectTable() {
        var selectTableInput = document.getElementById('selectTable');
        document.location = '?table='+selectTableInput.value;
    }

    function selectTableOnEnter(keyEvent) {
        var ENTER = 13;
        if (keyEvent.which == ENTER) {
            selectTable();
        }
    }

    function becomeSelectTableInput(elem) {
        var currentTableName = elem.innerText.trim();
        var parentElem = elem.parentNode;

        // swap in new <input> DOM element
        var tempContainer = document.createElement('div');
        var html = <?= echoSelectTableInputHtml_JsFormat() ?>;
        html = html.trim();
        tempContainer.innerHTML = html;
        var selectTableInput = tempContainer.firstChild;
        selectTableInput.value = currentTableName;
        parentElem.replaceChild(selectTableInput, elem);

        // focus and select all text
        selectTableInput.focus();
        selectTableInput.select();
        //selectTableInput.setSelectionRange(0, this.value.length);
    }


    {   // durationTimer - used for `duration` field
        // an hourglass appears next to the duration field
        // clicking it starts the timer
        // clicking it again stops the timer
        // and puts the elapsed duration into the textbox

        var durationTimer = {}

        function startTimer() {
            var timerElem = document.getElementById('durationTimer');
            timerElem.innerHTML = '⧗';
            timerElem.onclick = stopTimerAndSetDuration;

            durationTimer.t0 = new Date();
            return durationTimer.t0;
        }

        function stopTimer() {
            if ('t0' in durationTimer) {
                durationTimer.t1 = new Date();
                durationTimer.dur_ms = durationTimer.t1 - durationTimer.t0;
                durationTimer.dur_min = durationTimer.dur_ms / 1000 / 60;
                durationTimer.duration = durationTimer.dur_min.toString() + ' minutes';
                return durationTimer.duration;
            }
            else {
                console.log("Can't stopTimer - haven't started one!");
            }
        }

        function stopTimerAndSetDuration() {
            var timerElem = document.getElementById('durationTimer');
            timerElem.innerHTML = '⧖';
            timerElem.onclick = startTimer;

            var duration = stopTimer();

            // get duration input
            var elems = document.getElementsByName('duration');
            if (elems.length > 0) {
                if (elems.length > 1) {
                    console.log("Warning - more than one elem with name 'duration'");
                }
                var duration_input = elems[0];
                duration_input.value = duration;
            }
            else {
                console.log("No duration <input> to fill");
            }
        }
    }
}

        </script>
    </head>
    <body>
<?php
    { # header stuff
?>
        <p id="whoami">
            <a href="/dash/index.php">
                Dash
            </a>
        </p>
        <div id="table_header">
            <div id="table_header_top">
                <h1>
<?php
        if ($table) {
?>
                <code onclick="becomeSelectTableInput(this)">
                    <?= $table ?>
                </code>
<?php
        }
        else {
?>
                    <?= echoSelectTableInputHtml() ?>
<?php
        }
?>
                    table
                </h1>
<?php
        if ($table) {
?>
                <a href="/db_viewer/db_viewer.php?sql=select * from <?= $table ?>"
                   target="_blank"
                >
                    view all
                </a>
<?php
        }
?>
            </div>
<?php
        if ($multipleTablesFoundInDifferentSchemas) {
?>
            <div id="multipleTablesWarning">
                FYI: tables named
                <code><?= $table ?></code>
                were found in more than one schema.

                This one is <code><?= "$schema.$table" ?></code>.
            </div>
<?php
        }
?>
        </div>
<?php
    }

    { # body content
        if ($table) {

            { # the form
?>
        <form id="mainForm" target="_blank">
<?php
                { # create form fields
                    foreach ($fields as $name) {
                        if (in_array($name, $fields2omit)
                            && !in_array($name, $fields2keep)
                        ) {
                            continue;
                        }
                        echoFormFieldHtml($name, $row2edit);
                    }
                }

                { # dynamically add a new field
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

                { # submit buttons
?>

            <div id="submits">
                <input onclick="submitForm('/orm_router/create_<?= $table ?>', event); return false"
                    value="Create" type="submit"
                />
                <input onclick="submitForm('/orm_router/update_<?= $table ?>', event); return false"
                    value="Update" type="submit"
                />
                <input onclick="setFormAction('/orm_router/view_<?= $table ?>', event)"
                    value="View" type="submit"
                />
                <input onclick="submitForm('/orm_router/delete_<?= $table ?>', event); return false"
                    value="Delete" type="submit"
                />
            </div>
<?php
                }
?>
        </form>
<?php
            }
        }
        else {
            include("choose_table.php");
        }
    }
?>
    </body>
</html>
