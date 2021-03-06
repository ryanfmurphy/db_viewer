<script>

    var macro_to_run = <?= Utility::quot_str_for_js($macroName) ?>;

    function getColVals(cells) {
        var vals = $.map(cells, function(n,i){return n.innerText});
        return vals;
    }

    // util
    function firstObjKey(obj) {
        var first;
        for (first in obj) break;
        return first;
    }
    function getObjKeys(obj) {
        var keys = [];
        for (key in obj) keys.push(key);
        return keys;
    }
    function getObjKeysRev(obj) {
        var keys = [];
        for (key in obj) keys.unshift(key);
        return keys;
    }

    function showVal(val) {
        if (val === null) {
            return '';
        }
        else {
            return val;
        }
    }

    function isValidJoinField(field_name) {
        return true;
    }

    function getJoinColor(joinNum) {
        //console.log('getJoinColor', joinNum);
        var val = ((joinNum-1) % 3) + 1;
        //console.log('  val', val);
        return val;
    }

    // data is keyed by id
    // add to HTML Table, lined up with relevant row
    function addDataToTable(cells, data, exclude_fields) {
        // exclude_fields is a hash with the fields to exclude as keys

        var outerLevel = parseInt( cells.first().attr('level') )
                            || 0;
        var innerLevel = outerLevel + 1;

        // get all fields
        // by getting the keys of the first obj
        var first_obj = data[firstObjKey(data)];

        // we want field_names backwards so
        // when we append them they are forwards
        var field_names = getObjKeysRev(first_obj);

        // loop thru cells and add additional cells after
        cells.each(function(row_index, elem){
            return addColsToRow(elem, data, field_names, innerLevel, exclude_fields);
        });

    }

    function getDataKeyedByCellContents(elem, data, field_name) {
        var cell_val = elem.innerHTML.trim();
        var key = cell_val;

        var val = (key in data
                        ? data[key]
                        : '');

        if (val && field_name) {
            val = val[field_name];
        }

        return val;
    }

    function addColsToRow(elem, data, field_names_reversed, level, exclude_fields){

        var field_names = field_names_reversed;
        var cols_open = 0;

        var joinColor = getJoinColor(joinNum);

        // loop thru all fields and add a column for each row
        for (i in field_names) {

            var field_name = field_names[i];
            if (field_name in exclude_fields) continue;

            var val = getDataKeyedByCellContents(elem, data, field_name);

            var TD_or_TH = elem.tagName;
            var display_val = (TD_or_TH == 'TH'
                                    ? field_name
                                    : showVal(val));
            var extra = (TD_or_TH == 'TH'
                            ? 'field_name="' + field_name + '"'
                            : '');
            var content = '\
                        <'+TD_or_TH+' '+extra+'\
                            class="level' + level + ' join_color_' + joinColor + '"\
                            level="' + level + '"\
                        > \
                            '+display_val+'\
                        </'+TD_or_TH+'>\
                       ';
            $(elem).after(content);

            cols_open++;
        }

        $(elem).addClass('level' + level + 'handle')
            .addClass('join_color_' + joinColor + '_handle')
            .attr('cols_open', cols_open);

    }

    function isHeaderRow(row) {
        return row.children('th').length > 0;
    }

    function cellHTML(val, level, joinColor, tag) {
        tag = tag || 'td';
        val = val || '';
        return '\
            <'+tag+' class="level'+level+' join_color_'+joinColor+'"\
                level="'+level+'"\
            >\
                ' + val + '\
            </'+tag+'>\
        ';
    }

    function blankTableRow(field_names, innerLevel, joinColor) {
        return $.map(
            field_names,
            function(field_name,idx) {
                return $(cellHTML(null, innerLevel, joinColor));
            }
        );
    }

    function addBacklinkedDataToTable(cells, data, exclude_fields) {

        // get # fields from first row of data
        // [0] because multiple sub-rows per key, take the 1st one
        var first_obj = data[firstObjKey(data)][0];
        //console.log('first_obj',first_obj);
        var field_names = getObjKeys(first_obj);
        console.log('data',data);
        console.log('field_names',field_names);
        var num_new_cols = field_names.length;

        var outerLevel = parseInt( cells.first().attr('level') )
                            || 0;
        var innerLevel = outerLevel + 1;

        joinNum++; // rotate join colors (joinNum is global)
        var joinColor = getJoinColor(joinNum);

        var header_cells_str = '';
        for (i in field_names) {
            field_name = field_names[i];

            header_cells_str += '\
                <th class="level' + innerLevel + ' join_color_' + joinColor + '"\
                    level="' + innerLevel + '"\
                >\
                    ' + field_name + '\
                </th>\
            ';
        }
        var header_cells = $(header_cells_str);
        console.log('header_cells',header_cells);

        var mark_odd_row = 0;
        var current_id_val = null;

        cells.each(function(idx,elem){

            // change color only when primary key changes
            // so the join is still easy to visualize
            var this_val = elem.innerText;
            //console.log('a new cell', elem, 'idx', idx);
            //console.log('current id val', current_id_val, 'new val', this_val);
            if (!current_id_val
                || this_val !== current_id_val
            ) {
                //console.log('changing colors');
                mark_odd_row = 1 - mark_odd_row;
                current_id_val = this_val;
            }
            else {
                //console.log('not changing colors');
            }

            // update elem's css classes / color / etc
            $(elem).addClass('level' + innerLevel + 'handle')
                .addClass('join_color_' + joinColor + '_handle')
                .attr('cols_open', num_new_cols);

            var row = $(elem).closest('tr');
            if (isHeaderRow(row)) {
                //console.log('header_row', header_cells);
                var these_header_cells = header_cells.clone();
                $(elem).after(these_header_cells);
            }
            else { // data row - may have data to splice in
                var col_no = colNo(elem);
                var subrows = getDataKeyedByCellContents(elem, data); //, field_name);

                if ($.isArray(subrows)) {
                    var table_subrows = subrows.map(
                        function(data_subrow, idx, arr){
                            var table_subrow = $.map(
                                field_names,
                                function(field_name,idx2) {
                                    var val = data_subrow[field_name];
                                    return $(cellHTML(val, innerLevel, joinColor));
                                }
                            );
                            return table_subrow;
                        }
                    );
                }
                else { // null join field - insert blank row
                    //console.log('subrows not array, adding blanks',subrows);
                    var table_subrow = blankTableRow(field_names, innerLevel, joinColor);
                    table_subrows = [table_subrow];
                }

                addExtraRowsBelowRow(row, table_subrows, col_no, mark_odd_row);
            }

        });
    }

    function isTruthy(x) { return Boolean(x); }

    function trimIfString(x) {
        if (typeof x == 'string') {
            return x.trim();
        }
        else {
            return x;
        }
    }

    function getColsOpenFlat(elem) {
        return parseInt($(elem).attr('cols_open'));
    }

    // how many columns are open starting with the column of elem?
    // include nested open joins
    function getColsOpen(elem) {
        // start with flat number not including nested folds
        // (will go in a loop & sum a running count of nested open joins)
        var cols_open = getColsOpenFlat(elem);
        // jquery elem to iterate thru via .next
        var $elem = $(elem);
        for (var i = 0; i < cols_open; i++) {
            $elem = $elem.next();
            var this_elem = $elem.get(0);
            var inner_cols_open = getColsOpenFlat(this_elem);
            if (inner_cols_open > 0) {
                // increase the running count of cols_open
                // thus giving more time in this loop
                // to keep finding more nested open joins
                cols_open += inner_cols_open;
            }
        }
        return cols_open;
    }


    // used by AJAX callbacks during macro playback
    // to recursively playback remaining events
    function continuePlayingAnyFutureEvents(future_macro_events) {
        if (future_macro_events) {
            console.log('found future_macro_events, continuing playing');
            playbackMacroEvents(future_macro_events);
        }
    }

    // elem is the pivot <th> element you click on to
    // close all the rows that have been opened from the join
    // future_macro_events is optional, used by playbackMacroEvents (see thClickHandler)
    function closeJoin(elem, future_macro_events) {

        var all_cells = allColCells(elem);
        var cols_open = getColsOpen(elem);

        var handle_class, join_color_handle_class;

        unsplitRows(all_cells); // remove any many-to-one backlink-join-expanded rows

        // #todo factor this into a function
        { // find handle_class ("levelXhandle" class)
          // and join_color_X_handle to remove to undo color

            // use first cell as an example - all of them are the same
            var first_cell = all_cells.first();
            // use native DomElement.classList
            var cell_classes = first_cell.get(0).classList;

            for (var i in cell_classes) {
                var classname = cell_classes[i];
                var is_handle_class = (classname.indexOf('handle') != -1)
                                    && (classname.indexOf('join_color_') == -1)
                                    ;
                if (is_handle_class) {
                    handle_class = classname;
                    break;
                }
            }

            for (var i in cell_classes) {
                var classname = cell_classes[i];
                var is_join_color_handle_class = (classname.indexOf('handle') != -1)
                                               && (classname.indexOf('join_color_') != -1)
                                               ;
                if (is_join_color_handle_class) {
                    join_color_handle_class = classname;
                    break;
                }
            }
        }

        // for each row, remove all the open columns after that cell
        all_cells.each(function(idx,elem){
            // loop through and remove that many cells
            for (var i = 0; i < cols_open; i++) {
                $(elem).next().remove();
            }

            $(elem).removeClass(handle_class)
                   .removeClass(join_color_handle_class) // restores earlier color
                   ;
            $(elem).removeAttr('cols_open');
        });

        continuePlayingAnyFutureEvents(future_macro_events);
    }

    // if you click on a header / field_name, join the new data to the table
    // future_macro_events is optional, used by playbackMacroEvents (see thClickHandler)
    function openJoin(elem, future_macro_events) {

        // #todo #fixme - document.location = '?sql='+sql+' join other stuff';

        // don't do openJoin if this is a popup menu click
        if ($(elem).is('.popr-item')) {
            return false;
        }

        joinNum++; // rotate join colors (joinNum is global)

        var field_name = elem.innerHTML.trim();

        if (isValidJoinField(field_name)) {

            var prefix = field_name.slice(0,-3);

            // #todo validate prefix = valid table name?

            { // request adjoining table data #todo split into function

                { // figure out what data to ask for based on ids in col
                    //var table_name = prefix;

                    var col_no = colNo(elem);
                    var all_cells = nthCol(col_no); // #todo factor to allColCells() or one of those?
                    var val_cells = all_cells.filter('td');
                    var ids = getColVals(val_cells);
                    var non_null_ids = ids.filter(isTruthy)
                        .map(trimIfString)
                    ;
                    // #todo remove dups from non_null_ids

                    var uri = 'query_id_in<?= $php_ext ? '.php' : '' ?>';
                    var request_data = {
                        ids: non_null_ids,
                        join_field: field_name,
                        db_type: <?= json_encode($db_type) ?>,
                    };

                }

                { // make request
                    $.ajax({
                        url: uri,
                        type: 'POST',
                        data: request_data,
                        dataType: 'json',
                        success: function(data) {
                            var exclude_fields = {};
                            //exclude_fields[field_name] = 1;
                            addDataToTable(all_cells, data, exclude_fields);
                            continuePlayingAnyFutureEvents(future_macro_events);
                        },
                        error: function(r) {
                            if (future_macro_events === undefined) {
                                alert("Failure");
                            }
                            continuePlayingAnyFutureEvents(future_macro_events);
                        }
                    });
                }
            }
        }
        else {
            alert("Cannot expand this field \""+field_name+"\" - it doesn't end in \"_id\"");
        }
    }

    // reverse of openJoin - search the db for other tables
    // that have an id field pointing to this one
    function openBacklinkedJoin(
        elem,
        backlink_join_table, // optional, otherwise uses global var baclinkJoinTable
        future_macro_events // optional, for playback
    ) {

        if (backlink_join_table === undefined) {
            // choice from popup menu
            // #todo #fixme global var is not ideal
            backlink_join_table = backlinkJoinTable;
        }

        var field_name = elem.innerHTML.trim();

        joinNum++; // rotate join colors (joinNum is global)

        if (isValidJoinField(field_name)) {

            var all_cells = allColCells(elem);
            var val_cells = all_cells.filter('td');
            var vals = getColVals(val_cells);
            var table = backlink_join_table; // choice from popup menu
            var data_type = null; // #todo generalize if necessary

            // clear odd-row coloring so we can reapply it cleanly
            var rows = rowsOf(all_cells);
            rows.removeClass('odd-row');

            console.log('field_name',field_name);

            var base_table = <?= json_encode($inferred_table) ?>;
            ajaxRowsWithFieldVals(
                field_name, vals, table,
                data_type, base_table,

                function(data) {
                    console.log('data', data);
                    addBacklinkedDataToTable(all_cells, data[table]);
                    continuePlayingAnyFutureEvents(future_macro_events);
                }
            );

            if (future_macro_events === undefined) {
                recordMacroEvent(
                    null, field_name, 'openBacklinkedJoin',
                    { // extra_vars
                        backlink_join_table: backlink_join_table
                    }
                );
            }
        }
        else {
            alert("Cannot expand this field \""+field_name+"\" - it doesn't end in \"_id\"");
        }
    }

    // database-wide search for tables with matching fieldname
    // and specifically for rows from those tables
    // whose that_field matches one of vals
    function ajaxRowsWithFieldVals(fieldname, vals, table, data_type, base_table, callback) {

        var uri = 'rows_with_field_vals<?= $php_ext ? '.php' : '' ?>';

        var request_data = {
            fieldname: fieldname,
            vals: vals,
            table: table,
            data_type: data_type,
            base_table: base_table,
            db_type: <?= json_encode($db_type) ?>
        };

        $.ajax({
            url: uri,
            type: 'POST',
            data: request_data,
            dataType: 'json',

            success: callback,
            error: function(r) {
                alert("Failure");
            }
        });
    }

    // get all tables in database with a field called `fieldname`
    function tablesWithField(fieldname, data_type, vals, base_table, callback) {

        var uri = 'tables_with_field<?= $php_ext ? '.php' : '' ?>';
        var request_data = {
            fieldname: fieldname,
            data_type: data_type,
            //vals: vals,
            base_table: base_table,
            db_type: <?= json_encode($db_type) ?>
        };

        { // make request
            $.ajax({
                url: uri,
                type: 'POST',
                data: request_data,
                dataType: 'json',
                success: callback,
                error: function(r) {
                    alert("Failure");
                }
            });
        }
    }

    function updatePopupContent(content) {
        $('.popr-box, .popr_content').html(content);
    }

    function colNo(elem) {
        return elem.cellIndex;
    }

    function allColCells(elem) {
        return nthCol(colNo(elem));
    }

    function colValCells(elem) {
        return allColCells(elem).filter('td');
    }

    function colVals(elem) {
        return getColVals(colValCells(elem));
    }

    // by rfm - callback injected in popr popup menu
    // get dynamic popup content via ajax request
    function popupJoinTableOptions(
        fieldname,
        // all these params needed for showPopup popr callback:
        thisThing, event, popr_show, set, popr_cont
    ) {

        var data_type = null; // #todo use if necessary
        var elem = event.target;
        console.log(elem);
        var vals = colVals(elem);

        var callback = function(data) {
            var popupContent = '';
            for (i in data) {
                tablename = data[i];
                popupContent +=
                    '<div class="popr-item">'
                        + tablename +
                    '</div>';
            }
            updatePopupContent(popupContent);

            // pull up the popup asynchronously from this callback
            // so it's always after the content is updated.
            // this jumps into the main popr code for triggering a popup:
            showPopup(thisThing, event, popr_show, set, popr_cont);
        }

        var base_table = <?= json_encode($inferred_table) ?>;
        // lookup the tables that will become
        // options in the popup
        tablesWithField(
            fieldname, data_type, vals,
            base_table, callback
        );
    }


    // GLOBALS
    var show_hide_mode = 0;
    var sql = <?= Utility::quot_str_for_js(str_replace("\n","\n ",$sql)) ?>;


    // HANDLERS

    // thClickHandler
    // --------------
    // click on header field name (e.g. site_id) - joins to that table, e.g. site
    // displays join inline and allows you to toggle it back

    // future_macro_events is optional, used by the playbackMacroEvents to 
    // let the AJAX callback know what future events to continue playing
    // after it completes
    var thClickHandler = function(e, future_macro_events) {
        console.log('thClickHandler: e', e, 'future_macro_events', future_macro_events);
        var elem = e.target;

        // backlinked join handled by popr popup menu
        if (e.altKey) {
            console.log('altKey, don\'t do anything');
        }

        // avoid thClickHandler on .popr-item, it was already handled elsewhere
        else if (elem.classList.contains('popr-item')) {
            console.log('avoid thClickHandler on .popr-item, it was already handled elsewhere');
            return;
        }

        // regular join - open or close
        else {
            console.log('no altKey, time to do stuff!');
            var field_name = getHeaderFieldName(elem);
            var event_type;

            if (getColsOpen(elem) > 0) {
                console.log('already found open columns, do closeJoin');
                // already opened - close
                closeJoin(elem, future_macro_events);
                event_type = 'closeJoin';
            }
            else { // not already opened - do open
                console.log('found no open columns, do openJoin');
                openJoin(elem, future_macro_events);
                event_type = 'openJoin';
            }

            if (future_macro_events === undefined) {
                console.log('about to recordMacroEvent');
                recordMacroEvent(e, field_name, event_type);
            }
        }
    };

    var getHeaderFieldName = function(elem) {
        return elem.getAttribute('field_name');
    };

    var findHeaderWithFieldName = function(field_name) {
        elem = $('th[field_name='+field_name+']').get(0);
        return elem;
    }

    // fold / unfold via click
    var tdClickHandler = function(e){

        var elem = e.target;

<?php
    if ($custom_td_click_handler) {
?>
        <?= $custom_td_click_handler ?>(e);
<?php
    }

?>
        var edit_in_place = <?= $edit_in_place ? 'true' : 'false' ?>;

        if (show_hide_mode) {

            // make sure we have a table cell
            // and not e.g. an inner <div> for a wide_col
            if (elem.tagName != 'TD'
                && elem.tagName != 'TH'
            ) {
                elem = $(elem).closest('td,th').get(0);
            }

            // alt to fold/unfold row
            if (e.altKey) {
                rowN = $(elem).closest('tr').attr('data-row');

                if (e.shiftKey) {
                    unfoldRowsFrom(rowN);
                }
                else {
                    hideRow(rowN);
                }
            }
            // no alt to fold/unfold col
            else {
                colN = colNo(elem);
                if (e.shiftKey) {
                    unfoldColsFrom(colN);
                }
                else {
                    hideCol(colN);
                }
            }
        }
        else if (edit_in_place) {
            console.log(elem);

            if (elem.tagName != 'INPUT') {

                var td = elem;
                while (td && td.tagName != 'TD') {
                    td = td.parentNode;
                }
                var tr = td.parentNode;

                var val = 'value'; // #todo #fixme
                var table = 'book'; // #todo #fixme
                var field2edit = 'context'; // #todo #fixme
                var action = 'update_'+table;
                var primary_key = 'snoodlydoo'; // #todo #fixme
                var id_field = 'id'; // #todo #fixme
                var crud_api_uri = <?=
                    Utility::quot_str_for_js(
                        Config::$config['crud_api_uri']
                    )
                ?>;
                var input_html = '\
            <form   method="POST"\
                    action="' + crud_api_uri + '"\
                    target="_blank"\
            >\
                <input name="' + field2edit + '"\
                       value="' + val + '"\
                >\
                <input name="where_clauses[' + id_field + ']"\
                       type="hidden"\
                       value="' + primary_key + '"\
                >\
                <input name="action"\
                       type="hidden"\
                       value="' + action + '"\
                >\
                <input class="hidden_submit"\
                       type="submit"\
                >\
            </form>\
                '
                ;
                console.log('input_html', input_html);
                td.innerHTML = input_html;
            }
        }

    };


    $('table').on('click', 'td', tdClickHandler);
    $('table').on('click', 'th', thClickHandler);

    var macroEvents = [];
    function recordMacroEvent(
        event, field_name, event_type,
        extra_vars
    ) {
        console.log('recordMacroEvent', 'event', event, 'field_name', field_name, 'event_type', event_type);
        var eventData = {
            event_type: event_type,
            field_name: field_name
        };

        // add in extra_vars if any
        if (extra_vars) {
            for (var k in extra_vars) {
                if (extra_vars.hasOwnProperty(k)) {
                    eventData[k] = extra_vars[k];
                }
            }
        }

        macroEvents.push(eventData);
    }

    // note: does not redirect to the SQL query in and of itself
    // but just plays back the subsequent events.
    // When actually launching a macro, the page is reloaded
    // with GET var 'play_macro=<name>'
    function playbackMacroEvents(macroEvents) {
        if (macroEvents.length > 0) {

            // separate first event and the remaining events
            macroEvent = macroEvents[0];
            // remaining events will be recursively processed
            // at the end of any (possibly asynchronous) processing
            futureEvents = macroEvents.slice(1);

            // load_query event
            if ('load_query' in macroEvent) {
                console.log('load_query = ', macroEvent.load_query);
                // recursive call to remaining events
                playbackMacroEvents(futureEvents);
            }
            // click field_name event
            else if ('field_name' in macroEvent) {
                console.log('has field name');
                var field_name = macroEvent.field_name;
                var elem = findHeaderWithFieldName(field_name);
                var event_type = macroEvent['event_type'];
                switch (event_type) {
                    case 'openJoin':
                        console.log('dispatching openJoin event');
                        return thClickHandler(
                            { // our "event"
                                target: elem,
                                altKey: false
                            },
                            // pass futureEvents for recursive call
                            futureEvents
                        );

                    case 'openBacklinkedJoin':
                        console.log('dispatching openBacklinkedJoin event');
                        return openBacklinkedJoin(
                            elem,
                            macroEvent['backlink_join_table'],
                            futureEvents // pass futureEvents for recursive call
                        );

                    default:
                        console.log('WARNING - tried to playback unknown event_type', event_type,
                                    'macroEvent =', macroEvent);
                }
            }
        }
        else { // no events
            console.log('no more macroEvents, stopping playback');
            return;
        }
    }

    function playbackMacroEvent(macro_event) {
        return playbackMacroEvents([macro_event]);
    }

    macroEvents.push({
        load_query: sql
    });

<?php
    $db_viewer_macro_uri = Config::$config['db_viewer_macro_uri'];
    $db_viewer_macro_uri_js = Utility::quot_str_for_js($db_viewer_macro_uri);
?>
    var db_viewer_macro_uri = <?= $db_viewer_macro_uri_js ?>;

    function saveMacro(macroEvents, macroName) {
        $.ajax({
            url: db_viewer_macro_uri,
            //url: '/table_view/save_db_viewer_macro.php',
            type: 'POST',
            data: {
                name: macroName,
                events: macroEvents
            },
            dataType: 'json',
            success: function(r) {
                alert('macro successfully saved');
            },
            error: function(r) {
                alert('oh no saving didn\'t work!');
            }
        });
    }

    function loadMacro(macroName, callback) {
        $.ajax({
            url: db_viewer_macro_uri,
            data: {
                name: macroName
            },
            dataType: 'json',
            type: 'GET',
            success: function(r) {
                macroEvents = r; // set global var
                if (callback) {
                    callback(macroEvents);
                }
            },
            error: function(r) {
                alert('oh no couldn\'t get saved macros');
            }
        });
    }

    function playMacro(macroName) {
        loadMacro(
            macroName,
            // callback: after finishing load, playback the macro:
            function(macroEvents){
                playbackMacroEvents(macroEvents);
            }
        );
    }

    function loadMacroFromSelect(clickedElem, event) {
        var macroName = $(clickedElem).val();
        document.location = '?play_macro=' + macroName;
    }

    function saveCurrentMacroOnEnter(clickedElem, event) {
        var ENTER = 13, UNIX_ENTER = 10;
        if (event.which == ENTER
            || event.which == UNIX_ENTER
        ) {
            var macroName = $(clickedElem).val();
            saveMacro(macroEvents, macroName);
        }
    }

    if (macro_to_run) {
        playMacro(macro_to_run);
    }


    function get_id_col_no() {
        var headers = $('tr:first th');
        var selected_idx = undefined;
        headers.each(function(idx,elem) {
            var name = elem.innerHTML.trim();
            if (name == 'id') { // #todo #fixme generalize primary key
                selected_idx = idx;
                return false;
            }
        });
        return selected_idx;
    }

    function get_id_col($row) {
        var col_no = get_id_col_no();
        return $row.find('td').eq(col_no);
    }

    function get_selected_ids() {
        var ids = [];
        $('input[type=checkbox]:checked')
            .each(function(idx, input){
                var row = $(input).closest('tr');
                var id_tr = get_id_col(row);
                ids.push( id_tr.html() )
            })
        return ids;
    }

    function get_selected_id_list() { // 'id','id','id' as in SQL IN clause
        var ids = get_selected_ids();
        var str = '';
        var first = true;
        for (var i=0; i < ids.length; i++) {
            if (first) {
                first = false;
            }
            else {
                str += ',';
            }
            var id = ids[i];
            str += "'" + id + "'";
        }
        return str;
    }

    function get_selected_id_sql() {
        var sql = 'select * from entity where id in ('
                + get_selected_id_list()
                + ');';
        console.log(sql);
        return sql;
    }

    function get_selected_id_tree_url() {
        var id_field = 'id'; // #todo #fixme generalize
        var root_cond = id_field + ' in (' + get_selected_id_list() + ')';
        return '<?= $tree_view_uri ?>?root_cond='+root_cond;
    }

    function view_tree_of_selected_id() {
        var url = get_selected_id_tree_url();
        return window.open(url, '_blank');
    }

</script>

