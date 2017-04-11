//<script>

{ // main javascript

    function hasPreviouslyStoredRows() {
        var stored_rows = localStorage.getItem('stored_rows');
        if (stored_rows) {
            stored_rows = JSON.parse(stored_rows);
            return (stored_rows.length > 0);
        }
        else {
            return false;
        }
    }

<?php
    $table_field_spaces_to_underscores = Config::$config['table_field_spaces_to_underscores'];
?>

    // global-ish state
    scope = {
        table_name: '<?= $table ?>',
        table_view_uri: '<?= $table_view_uri ?>',
        vals_to_always_include: {},

        // cursor to go back and visit stored rows
        // {array: 'new'/'old', idx:int}
        stored_row_cursor: null,

        table_field_spaces_to_underscores: <?= $table_field_spaces_to_underscores
                                                    ? 1
                                                    : 0 ?>
    };

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

    // #todo break this up into one part that makes a js object
    //       and another part that serializes it into a query string

    function form2obj(
        formInputs, includeEmptyVals,
        valsToAlwaysInclude // keys = input names
    ) {
        // arg default values
        if (includeEmptyVals === undefined) {
            includeEmptyVals = false;
        }
        console.log('includeEmptyVals',includeEmptyVals);
        if (valsToAlwaysInclude === undefined) {
            valsToAlwaysInclude = {};
        }

        var data = {};

        // add a key-value pair to the array
        var addDatum = function(key, value) {
            //console.log('addDatum(key=',key,'value=',value,')');
            if (key != "" // <select> has a blank name when not in use
                          // we don't want the '"": custom_null_value' pair
                && (includeEmptyVals
                    || value != ""
                    || key in valsToAlwaysInclude
                   )
            ) {
                if (value == "") {
                    value = null;
                }
                data[key] = value;
            }
        };

        // Serialize the form elements
        for (var i = 0; i < formInputs.length; i++) {
            var input = formInputs[i];
            //console.log('input = ',input);
            addDatum(input.name, input.value);
        }

        return data;
    }


    function obj2queryString(data) {
        { // vars
            var pairs = [];

            // add a key-value pair to the array
            var addPair = function(key, value) {

                // Q. is this better/worse than pairs.push()?
                pairs[ pairs.length ] =
                    encodeURIComponent( key ) + "=" +
                    encodeURIComponent( value === null
                                            ? "<?= $magic_null_value ?>"
                                            : value
                                      );

            };
        }

        { // Serialize the form elements
            for (k in data) {
                if (data.hasOwnProperty(k)) {
                    addPair(k, data[k]);
                }
            }
        }

        { // Return the resulting serialization
            return pairs.join( "&" );
        }
    }


    // Serialize an array of form elements
    // into a query string - inspired by jQuery
    function serializeForm(
        formInputs, includeEmptyVals,
        valsToAlwaysInclude // keys = input names
    ) {
        var data = form2obj(formInputs,
                            includeEmptyVals,
                            valsToAlwaysInclude);
        return obj2queryString(data);

        /*
        // arg default values
        if (includeEmptyVals === undefined) {
            includeEmptyVals = false;
        }
        console.log('includeEmptyVals',includeEmptyVals);
        if (valsToAlwaysInclude === undefined) {
            valsToAlwaysInclude = {};
        }

        { // vars
            var prefix,
                pairs = [],

                // add a key-value pair to the array
                addPair = function(key, value) {

                    // Q. is this better/worse than pairs.push()?
                    if (includeEmptyVals
                        || value != ""
                        || key in valsToAlwaysInclude
                    ) {
                        if (value == "") {
                            value = null;
                        }
                        pairs[ pairs.length ] =
                            encodeURIComponent( key ) + "=" +
                            encodeURIComponent( value === null
                                                    ? "<?= $magic_null_value ?>"
                                                    : value
                                              );
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
        */
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

    function getForm() {
        return document.getElementById('mainForm');
    }

    // for submitting the form in the traditional way
    // while using js to dynamically change which action
    // to submit to (used by View buttons for example)
    function setFormAction(url, extra_vars = null) {
        var form = getForm();
        var inputs = getFormInputs(form);
        console.log('extra_vars',extra_vars);
        addVarsToForm(form, extra_vars);
        hideNamesForBlankVals(inputs);
        form.action = url;
        console.log('form action url =', url);
        // #todo #fixme why is logging this showing an <input name="action"> instead of the url?
        console.log('form action =', form.action);
        setTimeout(function(){
            unhideNamesForBlankVals(inputs);
            removeVarsFromForm(form, extra_vars);
        }, 250);
    }

    function addVarsToForm(form, vars) {
        for (var name in vars) {
            if (vars.hasOwnProperty(name)) {
                var val = vars[name];
                //console.log('k',k,'v',v);

                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = val;
                form.appendChild(input);
            }
        }
    }

    // only uses the keys from vars
    function removeVarsFromForm(form, vars) {
        var inputs = getFormInputs(form);
        for (var i=0; i<inputs.length; i++) {
            var input = inputs[i];
            if (input.name in vars) {
                input.parentNode.removeChild(input);
            }
        }
    }

    function getFormKeys(form, only_non_empty) {
        if (only_non_empty === undefined) {
            only_non_empty = false;
        }
        // #todo get them in order regardless of <tagname>
        var form_inputs = getFormInputs(form);
        var fields = form_inputs;
        if (only_non_empty) {
            fields = fields.filter(function(elem){
                        return (elem.value != "");
                     });
        }
        fields = fields.map(function(elem){
                    return elem.getAttribute('name');
                 });
        return fields;
    }

    { // local row storage

        function getLocalStorageArray(key) {
            var json = localStorage.getItem(key);
            return json
                        ? JSON.parse(json)
                        : [];
        }
        function saveLocalStorageJson(key, val) {
            return localStorage.setItem(
                key,
                JSON.stringify(val)
            );
        }
        function getStoredRows() {
            return getLocalStorageArray('stored_rows');
        }
        function saveStoredRows(rows) {
            return saveLocalStorageJson('stored_rows', rows);
        }
        function getOldStoredRows() {
            return getLocalStorageArray('old_stored_rows');
        }
        function saveOldStoredRows(rows) {
            return saveLocalStorageJson('old_stored_rows', rows);
        }

    }

    function updateJsObj(obj_to_update, obj_to_apply) {
        for (var k in obj_to_apply) {
            if (obj_to_apply.hasOwnProperty(k)) {
                obj_to_update[k] = obj_to_apply[k];
            }
        }
        return obj_to_update;
    }

    { // submit button handlers

        function createButtonClickHandler(
            crud_api_uri, table_name, event
        ) {
            var url = crud_api_uri
                    + '?action=create_'+table_name;
            submitForm(url, event, 'create');
            return false;
        }

        function updateButtonClickHandler(
            crud_api_uri, table_name, event
        ) {
            var url = crud_api_uri
                    + '?action=update_'+table_name;
            submitForm(url, event, 'update');
            return false;
        }

        function deleteButtonClickHandler(
            crud_api_uri, table_name, event
        ) {
            var url = crud_api_uri
                    + '?action=delete_'+table_name;
            submitForm(url, event, 'delete');
            return false;
        }

        var links_minimal_by_default = <?= (int)$links_minimal_by_default ?>;
        function maybe_minimal() {
            return links_minimal_by_default
                    ? '&minimal'
                    : '';
        }

        function viewButtonClickHandler(
            crud_api_uri, keyEvent, table_name
        ) {
            var url = crud_api_uri;
            var action = 'view_'+table_name;
            var extra_vars = {'action': action};
            var do_minimal = links_minimal_by_default
                                ? !keyEvent.altKey
                                : keyEvent.altKey
            if (do_minimal) {
                extra_vars.table_view_minimal_mode = 1;
            }
            console.log('url',url);
            console.log('extra_vars',extra_vars);
            return setFormAction(url, extra_vars);
        }

        function saveLocallyButtonClickHandler(
            crud_api_uri, table_name, event
        ) {
            var form = getForm();
            var row_data = form2obj(getFormInputs(form),
                                    false,
                                    scope.vals_to_always_include);

            var stored_rows = getStoredRows();
            var cursor = scope.stored_row_cursor;
            var row_wrapper;
            if (cursor
                && cursor.array == 'new'
            ) {
                // if you're visiting a previous row, and save,
                // whatever fields you've changed/added get applied
                // over the existing row object
                row_wrapper = getStoredRowAtCursor(stored_rows);
                row_wrapper['update_time'] = currentTimestamp();
                // #todo save update_time as last_updated on row itself?
                // write row to array in-place
                updateJsObj(row_wrapper.data, row_data);
                stored_rows[cursor.idx] = row_wrapper;
            }
            else {
                row_wrapper = {
                    data: row_data,
                    table_name: table_name,
                    time: currentTimestamp()
                };
                // add row to end
                stored_rows.push(row_wrapper);
            }

            saveStoredRows(stored_rows); // locally
            alert('Row stored locally');
            //clearAllFields();
            return false;
        }

        // <script>
        // actually save to DB
        function saveStoredRowsClickHandler(
            crud_api_uri, event
        ) {
            cursorReset();
            var stored_rows = getStoredRows();
            // rebuild array of rows that didn't get saved
            var remaining_stored_rows = [];

            if (stored_rows.length) {

                // prepare responses array and callback fn to collect and display responses
                // append all the response msgs into an array instead of showing them one by one
                responses = [];
                // this callback needs a row_name
                // which will be curried in when we have a row and know the name
                generic_respond_callback = function(row_name, response_msg) {
                    console.log('adding ', response_msg, ' to responses');
                    console.log('responses:', responses);

                    var msg_w_details = '';
                    if (row_name) {
                        msg_w_details += row_name + ' - ';
                    }
                    msg_w_details += response_msg;
                    responses.push(msg_w_details);

                    // do we have all the responses? show them
                    if (responses.length >= stored_rows.length) {
                        alert( responses.join('\n') );
                    }
                }

                for (var i=0; i<stored_rows.length; i++) {
                    stored_row = stored_rows[i];
                    var table_name = stored_row.table_name;
                    var data = stored_row.data;

                    // record time more accurately since these
                    // won't go in the DB until later
                    // #todo think about edge cases:
                    //      what if there was no time_added field?
                    //      what if time_added was specified manually?
                    data.time_added = stored_row.time;

                    var url = crud_api_uri
                            + '?action=create_'+table_name;

                    console.log('submitting stored row:', data);

                    // fill in the row data to the generic_respond_callback
                    // passing in the row name using an extra layer of fn call to
                    // freeze the value so that it is not overwritten by the next loop
                    respond_callback = (function(frozen_name) {
                        // this returned fn becomes the respond_callback:
                        return function(response_msg) {
                            // and all it does is call this but give it the name
                            generic_respond_callback(frozen_name, response_msg);
                        };
                    })(data.name /* gets passed in as frozen name */);

                    // save the row and queue up the response_msg in responses
                    submitForm(url, null, 'create', data, respond_callback);

                    // for now we'll just assume it was good and move it to old_stored_rows
                    var old_stored_rows = getOldStoredRows();
                    old_stored_rows.push(stored_row);
                    saveOldStoredRows(old_stored_rows);
                    console.log('marking row ',i,' for deletion');
                    if (false) { // #todo decide whether to retain row - only if it didn't save
                        remaining_stored_rows.push(stored_row);
                    }
                }
                
                // save remaining stored rows
                console.log('saving stored_rows with deletions', remaining_stored_rows);
                saveStoredRows(remaining_stored_rows);
            }
            else {
                alert('No rows to save');
            }

            return false;
        }

        // add all the old_stored_rows to stored_rows
        // so they can be saved to DB in case they were missed
        function recoverOldStoredRowsClickHandler(
            crud_api_uri, event
        ) {
            cursorReset();
            var stored_rows = getStoredRows();
            var old_stored_rows = getOldStoredRows();
            var num_rows_moved = 0;

            for (var i=0; i<old_stored_rows.length; i++) {
                stored_row = old_stored_rows[i];
                stored_rows.push(stored_row);
                num_rows_moved++;
            }
            
            saveStoredRows(stored_rows);

            // blank out old_stored_rows
            saveOldStoredRows([]);

            alert(num_rows_moved + ' rows recovered into stored rows');

            return false;
        }

        function clearStoredRowsClickHandler(
            crud_api_uri, event
        ) {
            cursorReset();
            saveStoredRows([]);
            saveOldStoredRows([]);
            alert('All stored rows cleared');
            return false;
        }

    }

    function formSubmitCallback(xhttp, event, action, respond_callback) {

        // respond_callback is the fn that will be called with the response msg to the user
        // alert by default
        // e.g. Thanks! Row Created
        if (respond_callback === undefined) respond_callback = alert;

        if (xhttp.readyState == 4) {
            if (xhttp.status == 200) {
                result = JSON.parse(xhttp.responseText);
                if (event && event.altKey) {
                    alert('SQL Query Logged to Console');
                    result = JSON.parse(xhttp.responseText);
                    console.log(result.sql);
                }
                else if (result) {
                    if ('success' in result
                        && !result.success
                    ) {
                        var error_details = result.error_info[2];
                        alert('Failed... Error '
                                + result.error_code + ': '
                                + error_details
                        );
                    }
                    else { // success
                        if (action == 'delete') {
                            respond_callback('Thanks! Row Deleted');
                        }
                        else if (action == 'create') {
                            respond_callback('Thanks! Row Created');
                            //#todo can only do this change if we get the primary_key var set
                            //changeCreateButtonToUpdateButton();
                        }
                        else if (action == 'update') {
                            respond_callback('Thanks! Row Updated');
                        }
                        else {
                            respond_callback('Thanks! Unknown action "' + action + '" done to Row');
                        }
                    }
                }
                else {
                    respond_callback('Failed... Try alt-clicking to see the Query');
                }
                console.log(result);
            }
            else {
                respond_callback('Non-200 Response Code: ' + xhttp.status);
            }
        }
    }

    // #todo #test pathway that uses a js obj and submits it
    function submitForm(url, event, action,
                        obj, respond_callback // optional args
    ) {
        var queryString;
        if (obj) { // data provided directly by obj
            queryString  = obj2queryString(obj);
        }
        else { // get the data from the form
            var form = getForm();
            var inputs = getFormInputs(form);
            queryString = serializeForm(
                            inputs, false,
                            scope.vals_to_always_include
                          );
        }

        { // do ajax
            var xhttp = new XMLHttpRequest();
            {   // callback
                xhttp.onreadystatechange = function() {
                    return formSubmitCallback(
                        xhttp, event, action, respond_callback
                    );
                }
            }
            { // do post
                xhttp.open("POST", url, true);
                xhttp.setRequestHeader(
                    "Content-type",
                    "application/x-www-form-urlencoded"
                );

                var postData;

                var valsToAlwaysInclude = scope;
                postData  = (action == 'delete'
                                ? "" // don't include key-val pairs for delete
                                     // (except where_clause for primary key)
                                : queryString
                            );

                {   // further additions to data
                    // #todo be more civilized: join up an array

                    // just show the query on altKey
                    if (event && event.altKey) {
                        postData += "&show_sql_query=1";
                    }

<?php
            if ($edit) {
                # escape for js
                $primary_key__esc = str_replace('"', '\"', $primary_key);
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

    // <script>
    // add the new form field html
    // requires the funny container trick
    function createElemFromHtml(html) {
        var tempContainer = document.createElement('div');
        html = html.trim();
        tempContainer.innerHTML = html;
        var newElem = tempContainer.firstChild;
        return newElem;
    }

    // #todo #fixme don't need plusSignFormInputDiv, it's always the same
    function addNewInput(fieldName) {
        var plusSignFormInputDiv = document.getElementById('addNewFieldDiv');
        var mainForm = getForm();
        var newField = createElemFromHtml(
            <?= echoFormFieldHtml_JsFormat('fieldName'); ?>
        );
        console.log('newField', newField);
        mainForm.insertBefore(newField, plusSignFormInputDiv);
    }

    function openAddNewField() {
        var fieldName = prompt("Enter Field Name to add:");
        if (fieldName) {
            if (scope.table_field_spaces_to_underscores) {
                fieldName = fieldName.replace(/ /g,'_');
            }
            addNewInput(fieldName);
        }
    }

    // <script>
    function getFormRow(elemInside) { // get .formInput row
        var node = elemInside.parentNode;

        // sometimes there are nested elems, go out til we get a formInput row
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

    // #todo this is not used for cursorPrev/Next visiting old rows
    // because it assumes it's already an input
    // but it'd be nice to use it if it does anything we need
    function selectTable(keyEvent) {

        var selectTableInput = document.getElementById('selectTable');
        var table = selectTableInput.value;
        if (scope.table_field_spaces_to_underscores) {
            table = table.replace(/ /g,'_');
        }
        var newLocation = '?table='+table;

        // if you change table while visiting previous stored rows,
        // you're now at the end of the stored rows again
        cursorReset();

        // depending on alt key, don't refresh page,
        // just change the table we're pointing at
        var need_alt_for_no_reload = <?= ($need_alt_for_no_reload
                                            ? 'true'
                                            : 'false') ?>;
        var change_table_no_reload = (need_alt_for_no_reload
                                        ? keyEvent.altKey
                                        : !keyEvent.altKey);
        // no reload - change table w pure JS
        if (change_table_no_reload) {

            // change which table to submit to
            scope.table_name = table;

            // replace input with header again
            document.getElementById('selectTable')
                    .outerHTML = '\
                            <code id="table_name" onclick="becomeSelectTableInput(this)">\
                                ' + table + '\
                            </code>\
                    ';

            // change update button to create button
            changeUpdateButtonToCreateButton();

            // change "view all" link
            console.log('doing view_all_link');
            view_all_link = document.getElementById('view_all_link');
            if (view_all_link) {
                view_all_url = scope.table_view_uri + '?sql=' + table + maybe_minimal();
                view_all_link.setAttribute('href', view_all_url);
            }

            selectFirstFormField();
            keyEvent.preventDefault();

        }
        // reload - redirect page
        else {
            var do_minimal = links_minimal_by_default
                                ? !keyEvent.ctrlKey
                                : keyEvent.ctrlKey;
            if (do_minimal) {
                newLocation += '&minimal';
            }
            document.location = newLocation;
        }
    }

    function changeUpdateButtonToCreateButton() {
        var update_button = document.getElementById('update_button');
        if (update_button) {
            update_button.outerHTML = '\
                <input onclick="return createButtonClickHandler(\'<?= $crud_api_uri ?>\', scope.table_name, event)"\
                    value="Create" type="submit" id="create_button"\
                />\
            ';
        }
    }

    function changeCreateButtonToUpdateButton() {
        var update_button = document.getElementById('create_button');
        if (update_button) {
            update_button.outerHTML = '\
                <input onclick="return updateButtonClickHandler(\'<?= $crud_api_uri ?>\', scope.table_name, event)"\
                    value="Update" type="submit" id="update_button"\
                />\
            ';
        }
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
        selectTableInput.setSelectionRange(0,9999); // needed for mobile
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
                existing_dur_min = (durationTimer.dur_min !== undefined
                                        ? durationTimer.dur_min
                                        : 0);
                durationTimer.dur_min = (durationTimer.dur_ms / 1000 / 60) + existing_dur_min;
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

            // set duration value
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

        function currentTimestamp() {
            // offset in milliseconds
            var tzoffset = (new Date()).getTimezoneOffset() * 60000;
            var localISOTime = (new Date(Date.now() - tzoffset))
                                    .toISOString()
                                    .slice(0,-1) // remove Z for Zulu time
                                    ;
            return localISOTime;
        }

        function setDoneTime() { // #todo generalize 'name' to use on other timestamps
            var timestamp = currentTimestamp();

            // get duration input
            var name = 'done_time';
            var elems = document.getElementsByName(name);
            if (elems.length > 0) {
                if (elems.length > 1) {
                    console.log("Warning - more than one elem with name '"+name+"'");
                }
                var input = elems[0];
                input.value = timestamp;
            }
            else {
                console.log("No '"+name+"' <input> to fill");
            }
        }
    }

    window.onload = function() {

<?php
        # focus selectTable input if no table selected
        if (!$table) {
?>
        var select_table_input = document.getElementById('selectTable');
        select_table_input.focus();
<?php
        }
?>
        
        // if <select> is on Custom, don't send that hash value,
        // and show the custom value <input>
        var form = getForm();
        if (form) {
            var selects = form.getElementsByTagName('select');
            for (var i=0; i < selects.length; i++) {
                var elem = selects[i];
                handleCustomValueInputForSelect(elem);
            }

            // get all names from form, to pay attention to those fields
            // even if we set them to blank (which becomes NULL on the backend)
            var form = getForm();
            var form_names = getFormKeys(form, true);
            for (var i = 0; i < form_names.length; i++) {
                var key = form_names[i];
                scope.vals_to_always_include[key] = true;
            }
            console.log('vals_to_always_include = ', scope.vals_to_always_include);
        }
        else {
            console.log('no form, not doing form-related init');
        }

    }


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

    scope.custom_select_magic_value = "<?= $custom_select_magic_value ?>";
    // elem is <select> element
    function handleCustomValueInputForSelect(elem) {
        console.log('handleCustomValueInputForSelect, elem=', elem);
        // use magic value to detect if they chose "custom"
        // so we can give them a custom <input> to type in
        if (elem.value == scope.custom_select_magic_value) {
            console.log('  matches magic value, creating input');
            var new_input = document.createElement('input');
            new_input.setAttribute('class', "custom_value_input");

            // put custom_value if any into input (e.g. default values)
            var custom_value = (elem.hasAttribute('data-custom_value')
                                    ? elem.getAttribute('data-custom_value')
                                    : null);
            console.log('custom_value',custom_value);
            if (custom_value) {
                new_input.value = custom_value;
                elem.removeAttribute('data-custom_value');
            }

            // #todo is .after well-supported JS?
            elem.after(new_input);
            useCustomValue(new_input);
            return new_input;
        }
        else {
            useSelectValue(elem);
            removeCustomInput(elem);
            return null;
        }
    }

    // when you type in the custom <input> box tied to a <select>
    // move the name from the <select> to the <input>
    function useCustomValue(input_elem) {
        console.log('useCustomValue() input_elem=',input_elem);
        var select_elem = input_elem.previousSibling;
        var name = select_elem.getAttribute('name');
        input_elem.setAttribute('name', name);
        select_elem.removeAttribute('name');
    }

    //<script>
    // and the reverse - move name from <input> back to <select>
    function useSelectValue(select_elem) {
        var input_elem = select_elem.nextSibling;
        console.log('top of useSelectValue()');
        if (input_elem
            && input_elem.getAttribute
        ) {
            console.log('input_elem', input_elem);
            var name = input_elem.getAttribute('name');
            if (name) {
                select_elem.setAttribute('name', name);
                input_elem.removeAttribute('name');
            }
            else {
                console.log('no name found on input_elem, doing nothing', input_elem,
                            'select_elem =', select_elem);
            }
        }
        else {
            console.log('no input_elem in useSelectValue, doing nothing. select_elem =',
                        select_elem);
        }
    }

    // remove custom <input> after <select>, if any
    function removeCustomInput(select_elem) {
        var next = select_elem.nextSibling;
        if (next.classList
            && next.classList.contains('custom_value_input')
        ) {
            next.remove();
        }
    }

    { // fns to set values of inputs

        function clearField(input_elem) {
            if (input_elem.tagName == 'INPUT'
                || input_elem.tagName == 'TEXTAREA'
            ) {
                input_elem.value = '';
            }
            else if (input_elem.tagName == 'SELECT') {
                var option1 = input_elem.getElementsByTagName('option')[0];
                if (!option1.selected) {
                    // this assumes we don't have to
                    // deselect the previously chosen 1
                    option1.selected = 'selected';

                    // we just chose 'custom'
                    // so open the custom input_elem
                    handleCustomValueInputForSelect(input_elem);
                }
            }
        }

        // blank out all form fields
        function clearAllFields() {
            var form = getForm();
            var inputs = getFormInputs(form);
            for (var i in inputs) {
                var input = inputs[i];
                clearField(input);
            }
        }

        // given a DOM element, fill in the value
        // note that for a <select>, this will only work
        // if the select is active, by having the name attribute
        // if the custom_value_input has the name attribute,
        // call this fn on that instead
        function setInputValue(input_elem, value) {
            if (input_elem.tagName == 'INPUT'
                || input_elem.tagName == 'TEXTAREA'
            ) {
                input_elem.value = value;
            }
            else if (input_elem.tagName == 'SELECT') {
                var option1 = input_elem.getElementsByTagName('option')[0];
                if (!option1.selected) {
                    // this assumes we don't have to
                    // deselect the previously chosen 1
                    option1.selected = 'selected';

                    // we just chose 'custom'
                    // so open the custom input_elem
                    console.log('about to handleCustomValueInputForSelect');
                    var custom_value_input =
                        handleCustomValueInputForSelect(input_elem);

                    custom_value_input.value = value;
                }
            }
        }

        // given a js obj, data, add any inputs
        // for data's keys that aren't there yet
        function addMissingInputs(data) {
            var form = getForm();
            var existing_names = getFormKeys(form);
            // find missing_names and add inputs
            for (var name in data) {
                if (data.hasOwnProperty(name)
                    && existing_names.indexOf(name) === -1
                ) {
                    console.log('missing name', name, '- adding input');
                    addNewInput(name);
                }
            }
        }

        // given a js obj, data, fill it into the inputs
        function setInputValues(data) {
            clearAllFields();
            var form = getForm();
            addMissingInputs(data);
            var inputs = getFormInputs(form);
            for (var i=0; i<inputs.length; i++) {
                var input = inputs[i];
                var name = input.getAttribute('name');
                if (name
                    && data.hasOwnProperty(name)
                ) {
                    setInputValue(input, data[name]);
                }
            }
        }

    }

    { // stored_row_cursor to go back and visit stored rows

        // #todo maybe consolidate stored_rows and old_stored_rows into one thing?
        // and just keep track of whether they've been saved or not?

        function cursorPrev() {
            var cursor = scope.stored_row_cursor;

            // not on a row (i.e. at the very end)
            if (cursor === null) {
                var rows = getStoredRows();
                if (rows.length > 0) {
                    cursor = scope.stored_row_cursor = {
                        array: 'new',
                        idx: rows.length - 1
                    };
                    return cursor;
                }
                // else fall thru and end up in old_stored_rows
            }

            // cursor already exists, go back from there
            if (cursor
                && cursor.idx > 0
            ) {
                cursor.idx--;
                return cursor;
            }
            // cursor still doesn't exist or has run out of new rows
            // go to old rows
            else {
                var rows = getOldStoredRows();
                // if we didn't already run thru the old rows
                if ( (cursor === null
                      || cursor.array === 'new')
                     && rows.length > 0
                ) {
                    cursor = scope.stored_row_cursor = {
                        array: 'old',
                        idx: rows.length - 1
                    };
                    return cursor;
                }
                else {
                    console.log("Can't go back any further");
                }
            }
        }

        function cursorNext() {
            var cursor = scope.stored_row_cursor;

            var new_rows = getStoredRows();
            var old_rows = getOldStoredRows();
            if (cursor === null) {
                console.log("Can't go forward any further");
                return;
            }
            // we have a cursor
            else {
                cursor.idx++;

                if (cursor.array === 'new') {
                    if (cursor.idx > new_rows.length-1) {
                        cursor = null;
                    }
                }
                else {
                    if (cursor.idx > old_rows.length-1) {
                        if (new_rows.length > 0) {
                            cursor.array = 'new';
                            cursor.idx = 0;
                        }
                        else {
                            cursor = null;
                        }
                    }
                }
                scope.stored_row_cursor = cursor;
                return cursor;
            }
        }

        function cursorFirst() {
            var old_rows = getOldStoredRows();
            if (old_rows.length > 0) {
                scope.stored_row_cursor = {
                    array: 'old',
                    idx: 0
                };
                return scope.stored_row_cursor;
            }
            else {
                var new_rows = getStoredRows();
                if (new_rows.length > 0) {
                    scope.stored_row_cursor = {
                        array: 'new',
                        idx: 0
                    };
                    return scope.stored_row_cursor;
                }
            }
        }

        function cursorLast() {
            scope.stored_row_cursor = null;
            return null;
        }

        function cursorReset() {
            return cursorLast();
        }

        function getStoredRowAtCursor(
            stored_rows // optional, for caching
        ) {
            var cursor = scope.stored_row_cursor;
            if (cursor === null) {
                return {
                    data: {}
                };
            }
            else {
                var rows = (cursor.array === 'new'
                                ? (stored_rows
                                        ? stored_rows
                                        : getStoredRows())
                                : getOldStoredRows());
                return rows[cursor.idx];
            }
        }

        /*
        // only for saving when you're back visiting a prev row.
        // #todo gracefully handle edge case of going back
        // and editing an old_stored_row (one that's been saved to DB)
        function saveStoredRowAtCursor(
            stored_rows // optional, for caching
        ) {
            var cursor = scope.stored_row_cursor;
            var stored_rows = (stored_rows
                                        ? stored_rows
                                        : getStoredRows());
            if (cursor === null) {
                console.log("Don't call saveStoredRowAtCursor for null cursor");
                return;
            }
            else {
                var row = getStoredRowAtCursor(stored_rows);
                
                saveStoredRows(stored_rows);
            }
        }
        */

        function visitRow(row) {
            // change table name if needed
            if (row.hasOwnProperty('table_name')) {
                var table_name_elem = document.getElementById('table_name');
                table_name_elem.innerHTML = row.table_name;
            }

            // fill in the blanks
            setInputValues(row.data);
        }

        function visitRowAtCursor() {
            var cursor = scope.stored_row_cursor;
            var row = getStoredRowAtCursor();
            if (row) {
                visitRow(row);
            }
        }

        function goToPrevRow() {
            var cursor = cursorPrev();
            visitRowAtCursor();
        }

        function goToNextRow() {
            var cursor = cursorNext();
            visitRowAtCursor();
        }

        function goToFirstRow() {
            var cursor = cursorFirst();
            visitRowAtCursor();
        }

        function goToLastRow() {
            var cursor = cursorLast();
            visitRowAtCursor();
        }

    }

    function selectFirstFormField() {
        var form = getForm();
        var inputs = getFormInputs(form);
        if (inputs.length > 0) {
            var first_input = inputs[0];
            first_input.focus();
        }
    }

}
