<?php
{ # pre-HTML
    { # init
        { # includes & misc
            $trunk = __DIR__;
            include("$trunk/init.php");
            $requestVars = array_merge($_GET, $_POST);
        }

        { # edit?
            $edit = (isset($requestVars['edit'])
                    && $requestVars['edit']);

            if ($edit) {
                if (isset($_GET['primary_key'])) {

                    $table = $_GET['table'];

                    if ($id_mode == 'id_only') { #todo factor, we might have a function already
                        $primary_key_field = 'id';
                    }
                    else {
                        $primary_key_field = $table.'_id';
                    }
                    $primary_key = $_GET['primary_key'];
                    $primary_key__esc = Db::sqlLiteral($primary_key); # escape as sql literal

                    $sql = "
                        select * from $table
                        where $primary_key_field = $primary_key__esc
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
                   onclick="removeFormField(getFormRow(this))"
            >
                <?= $name ?> 
            </label>
<?php
                { ob_start();
?>
                name="<?= $name ?>"
                onkeypress="removeFieldOnCtrlDelete(event,this)"
<?php
                    $inputAttrs = ob_get_clean();
                }
        
                if ($name == 'artist') {
                    $artists = Db::sql('select name from artist');
?>
            <div class="select_from_options">
                <select
                    <?= $inputAttrs ?>
                >
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
                    # split off <input> and <textarea> cases
                    if ($inputTag == 'input') {
?>
            <input
                <?= $inputAttrs ?>
<?php
                    if ($row2edit) {
?>
                value="<?= $row2edit[$name] ?>"
<?php
                    }
?>
            />
<?php
                    }
                    elseif ($inputTag == 'textarea') {
?>
            <textarea
                <?= $inputAttrs ?>
            ><?php
                    if ($row2edit) {
                        echo $row2edit[$name];
                    }
            ?></textarea>
<?php
                    }
                    else {
                        die("unknown inputTag: '$inputTag'");
                    }
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

        function doSkipField($fieldName, $fields2omit, $fields2keep, $only_include_these_fields=null) {
            if (in_array($fieldName, $fields2omit)
                && !in_array($fieldName, $fields2keep)
            ) {
                return true;
            }

            if (is_array($only_include_these_fields)
                && !in_array($fieldName, $only_include_these_fields)
            ) {
                return true;
            }

            return false;
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
    function serializeForm(formInputs, includeEmptyVals = false) {
        console.log('includeEmptyVals',includeEmptyVals);

        { // vars
            var prefix,
                pairs = [],

                // add a key-value pair to the array
                addPair = function(key, value) {

                    // Q. is this better/worse than pairs.push()?
                    if (includeEmptyVals
                        || value != ""
                    ) {
                        pairs[ pairs.length ] =
                            encodeURIComponent( key ) + "=" +
                            encodeURIComponent( value == null ? "" : value );
                    }

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

    // prevent the blank vals from getting submitted in the form
    function hideNamesForBlankVals(inputs) {
        for (var i = 0; i < inputs.length; i++) {
            var elem = inputs[i];

            // hide name
            if (elem.value == '') {
                var name = elem.getAttribute('name');
                elem.setAttribute('unname', name);
                elem.removeAttribute('name');
            }
        }
    }

    // restore the names blank vals after form submit
    // for future submits
    function unhideNamesForBlankVals(inputs) {
        for (var i = 0; i < inputs.length; i++) {
            var elem = inputs[i];

            // unhide name
            if (elem.value == '') {
                var unname = elem.getAttribute('unname');
                elem.setAttribute('name', unname);
                elem.removeAttribute('unname');
            }
        }
    }

    function setFormAction(url) {
        var form = document.getElementById('mainForm');
        var inputs = getFormInputs(form);
        hideNamesForBlankVals(inputs);
        form.action = url
        setTimeout(function(){
            unhideNamesForBlankVals(inputs);
        }, 250);
    }

    /*
    // used for View button: go to db_viewer with the right vars
    function getUrlWithVars(url) {
        console.log('url',url);
        var form = document.getElementById('mainForm');
        var data = getFormInputs(form);
        var queryString = serializeForm(data);
        var fullUrl = url + '?' + queryString;
        console.log('fullUrl',fullUrl);
        return fullUrl;
    }
    */

    function submitForm(url, event, action) {
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
                            result = JSON.parse(xhttp.responseText);
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
            { // do post
                xhttp.open("POST", url, true);
                xhttp.setRequestHeader(
                    "Content-type",
                    "application/x-www-form-urlencoded"
                );

                var data = getFormInputs(form);
                var postData;

                postData  = (action == 'delete'
                                ? "" // don't include key-val pairs for delete
                                     // (except where_clause for primary key)
                                : serializeForm(data));

                {   // further additions to data
                    // #todo be more civilized: join up an array

                    // just show the query on altKey
                    if (event.altKey) {
                        postData += "&show_sql_query=1";
                    }

<?php
            if ($edit) {
                    $primary_key__esc = str_replace('"', '\"', $primary_key); # escape for js
?>
                    // update needs a where clause
                    if (   action == 'update'
                        || action == 'delete'
                    ) {
                        console.log('update');
                        postData += "&" + "where_clauses[<?= $primary_key_field ?>]"
                                                    + "=" + "<?= $primary_key__esc ?>";
                    }
<?php
            }
?>
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

    // <script>
    function getFormRow(elemInside) { // get .formInput row
        var node = elemInside.parentNode;

        // sometimes there are nested elems, go out til we get a formInput row
        //while (!formRow.classList.contains('formInput')) {
        while (node && !isFormRow(node)) {
            node = node.parentNode;
        }
        return node;
    }

    // <script>
    function removeFormField(formRow) {
        { // remove the elem
            console.log('formRow', formRow);
            var parentElem = formRow.parentNode;
            console.log('parentElem', parentElem);
            parentElem.removeChild(formRow);
        }
    }

    function selectTable(keyEvent) {
        var selectTableInput = document.getElementById('selectTable');
        var newLocation = '?table='+selectTableInput.value;
        if (keyEvent.ctrlKey) {
            newLocation += '&minimal';
        }
        document.location = newLocation;
    }

    function selectTableOnEnter(keyEvent) {
        var ENTER = 13, UNIX_ENTER = 10;
        if (keyEvent.which == ENTER
            || keyEvent.which == UNIX_ENTER
        ) {
            selectTable(keyEvent);
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


    // <script>
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

<?php
    { # focus selectTable input if no table selected
        if (!$table) {
?>
    window.onload = function() {
        var select_table_input = document.getElementById('selectTable');
        select_table_input.focus();
    }
<?php
        }
    }
?>

    // <script>
    function isFormRow(elem) {
        return (
            elem.nodeType == 1 // is an element
            && elem.classList.contains('formInput')
        );
    }

    function getPrevFormRow(formRow) {
        var node = formRow.previousSibling;
        while (node && !isFormRow(node)) {
            node = node.previousSibling;
        }
        return node;
    }

    function getInputIn(elem) {
        var elems = elem.getElementsByTagName('input');
        if (elems.length) {
            return elems[0];
        }
        else {
            elems = elem.getElementsByTagName('textarea');
            if (elems.length) {
                return elems[0];
            }
            else {
                elems = elem.getElementsByTagName('select');
                if (elems.length) {
                    return elems[0];
                }
                else {
                    return null;
                }
            }
        }
    }

    function removeFieldOnCtrlDelete(keyEvent, focusedElem) {
        //console.log('keyEvent', keyEvent);
        var DELETE_BS_KEY = 8;
        if (keyEvent.which == DELETE_BS_KEY
            && keyEvent.ctrlKey
        ) {
            var formRow = getFormRow(focusedElem);
            var prevFormRow = getPrevFormRow(formRow);
            removeFormField(formRow);
            if (prevFormRow) {
                var input = getInputIn(prevFormRow);
                if (input) {
                    input.focus();
                }
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
                <a href="<?= $db_viewer_path ?>?sql=select * from <?= $table ?> limit 100"
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
                        if (doSkipField($name, $fields2omit, $fields2keep, $only_include_these_fields)) {
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
<?php
                    if ($edit) {
?>
                <input onclick="submitForm('<?= $orm_router_path ?>/update_<?= $table ?>', event, 'update'); return false"
                    value="Update" type="submit"
                />
<?php
                    }
                    else {
?>
                <input onclick="submitForm('<?= $orm_router_path ?>/create_<?= $table ?>', event, 'create'); return false"
                    value="Create" type="submit"
                />
<?php
                    }
?>
                <input onclick="setFormAction('<?= $orm_router_path ?>/view_<?= $table ?>')"
                    value="View" type="submit"
                />
<?php
                    if ($edit) {
?>
                <input onclick="submitForm('<?= $orm_router_path ?>/delete_<?= $table ?>', event, 'delete'); return false"
                    value="Delete" type="submit"
                />
<?php
                    }
?>
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
