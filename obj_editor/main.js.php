//<script>

{ // main javascript

    // global-ish state
    scope = {
        table_name: '<?= $table ?>',
        table_view_uri: '<?= $table_view_uri ?>',
        vals_to_always_include: {}
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

    // Serialize an array of form elements
    // into a query string - inspired by jQuery
    function serializeForm(
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

    // for submitting the form in the traditional way
    // while using js to dynamically change which action
    // to submit to (used by View buttons for example)
    function setFormAction(url, extra_vars = null) {
        var form = document.getElementById('mainForm');
        var inputs = getFormInputs(form);
        console.log('extra_vars',extra_vars);
        addVarsToForm(form, extra_vars);
        hideNamesForBlankVals(inputs);
        form.action = url;
        console.log('form action url =', url);
        // #todo #fixme why is logging this showing an <input name="action"> instead of the url?
        // console.log('form action =', form.action);
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

    }

    function formSubmitCallback(xhttp, event, action) {
        if (xhttp.readyState == 4) {
            if (xhttp.status == 200) {
                result = JSON.parse(xhttp.responseText);
                if (event.altKey) {
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
                            alert('Thanks! Row Deleted');
                        }
                        else if (action == 'create') {
                            alert('Thanks! Row Created');
                            //#todo can only do this change if we get the primary_key var set
                            //changeCreateButtonToUpdateButton();
                        }
                        else if (action == 'update') {
                            alert('Thanks! Row Updated');
                        }
                        else {
                            alert('Thanks! Unknown action "' + action + '" done to Row');
                        }
                    }
                }
                else {
                    alert('Failed... Try alt-clicking to see the Query');
                }
                console.log(result);
            }
            else {
                alert('Non-200 Response Code: ' + xhttp.status);
            }
        }
    }

    function submitForm(url, event, action) {
        var form = document.getElementById('mainForm');

        { // do ajax
            var xhttp = new XMLHttpRequest();
            {   // callback
                xhttp.onreadystatechange = function() {
                    return formSubmitCallback(xhttp, event, action);
                }
            }
            { // do post
                xhttp.open("POST", url, true);
                xhttp.setRequestHeader(
                    "Content-type",
                    "application/x-www-form-urlencoded"
                );

                var data = getFormInputs(form);
                var postData;

                var valsToAlwaysInclude = scope;
                postData  = (action == 'delete'
                                ? "" // don't include key-val pairs for delete
                                     // (except where_clause for primary key)
                                : serializeForm(
                                    data, false,
                                    scope.vals_to_always_include
                                  )
                            );

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
        var table = selectTableInput.value;
        var newLocation = '?table='+table;

        // alt key - don't refresh page,
        // just change the table we're pointing at
        if (keyEvent.altKey) {

            // change which table to submit to
            scope.table_name = table;

            // replace input with header again
            document.getElementById('selectTable')
                    .outerHTML = '\
                            <code onclick="becomeSelectTableInput(this)">\
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

        }
        // no alt key - redirect page
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
        var form = document.getElementById('mainForm');
        if (form) {
            var selects = form.getElementsByTagName('select');
            for (var i=0; i < selects.length; i++) {
                var elem = selects[i];
                handleCustomValueInputForSelect(elem);
            }

            // get all names from form, to pay attention to those fields
            // even if we set them to blank (which becomes NULL on the backend)
            var form = document.getElementById('mainForm');
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
        }
        else {
            useSelectValue(elem);
            removeCustomInput(elem);
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

    // blank out all form fields
    function clearAllFields() {
        var form = document.getElementById('mainForm');
        var inputs = getFormInputs(form);
        for (var i in inputs) {
            var input = inputs[i];
            if (input.tagName == 'INPUT'
                || input.tagName == 'TEXTAREA'
            ) {
                input.value = '';
            }
            else if (input.tagName == 'SELECT') {
                var option1 = input.getElementsByTagName('option')[0];
                if (!option1.selected) {
                    // this assumes we don't have to
                    // deselect the previously chosen 1
                    option1.selected = 'selected';

                    // we just chose 'custom'
                    // so open the custom input
                    handleCustomValueInputForSelect(input);
                }
            }
        }
    }

}
