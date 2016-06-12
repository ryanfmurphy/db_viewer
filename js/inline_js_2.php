<?php
                    { # js
?>
<script>

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
        console.log('getJoinColor', joinNum);
        var val = ((joinNum-1) % 3) + 1;
        console.log('  val', val);
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
            var content = '\
                        <'+TD_or_TH+'\
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
            console.log('a new cell', elem, 'idx', idx);
            console.log('current id val', current_id_val, 'new val', this_val);
            if (!current_id_val
                || this_val !== current_id_val
            ) {
                console.log('changing colors');
                mark_odd_row = 1 - mark_odd_row;
                current_id_val = this_val;
            }
            else {
                console.log('not changing colors');
            }

            // update elem's css classes / color / etc
            $(elem).addClass('level' + innerLevel + 'handle')
                .addClass('join_color_' + joinColor + '_handle')
                .attr('cols_open', num_new_cols);

            var row = $(elem).closest('tr');
            if (isHeaderRow(row)) {
                console.log('header_row', header_cells);
                //console.log('elem', elem);
                var these_header_cells = header_cells.clone();
                $(elem).after(these_header_cells);
            }
            else { // data row - may have data to splice in
                //console.log('elem',elem);
                //console.log('subrows',subrows);

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
                    console.log('subrows not array, adding blanks',subrows);
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

    // elem is the pivot <th> element you click on to
    // close all the rows that have been opened from the join
    function closeJoin(elem) {

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
    }

    function openJoin(elem) {

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

                    var uri = 'query_id_in<?= $maybe_url_php_ext ?>';
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
                            exclude_fields[field_name] = 1;
                            addDataToTable(all_cells, data, exclude_fields);
                        },
                        error: function(r) {
                            alert("Failure");
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
    function openBacklinkedJoin(elem) {

        var field_name = elem.innerHTML.trim();

        joinNum++; // rotate join colors (joinNum is global)

        if (isValidJoinField(field_name)) {

            var all_cells = allColCells(elem);
            var val_cells = all_cells.filter('td');
            var vals = getColVals(val_cells);
            var table = backlinkJoinTable;
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
                }
            );
        }
        else {
            alert("Cannot expand this field \""+field_name+"\" - it doesn't end in \"_id\"");
        }
    }

    // database-wide search for tables with matching fieldname
    // and specifically for rows from those tables
    // whose that_field matches one of vals
    function ajaxRowsWithFieldVals(fieldname, vals, table, data_type, base_table, callback) {

        var uri = 'rows_with_field_vals<?= $maybe_url_php_ext ?>';

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

        var uri = 'tables_with_field<?= $maybe_url_php_ext ?>';
        var request_data = {
            fieldname: fieldname,
            data_type: data_type,
            vals: vals,
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
        //console.log(elem);
        var vals = colVals(elem);

        var callback = function(data) {
            var popupContent = '';
            for (i in data) {
                tablename = data[i];
                popupContent += '<div class="popr-item">' + tablename + '</div>';
            }
            updatePopupContent(popupContent);

            // pull up the popup asynchronously from this callback
            // so it's always after the content is updated.
            // this jumps into the main popr code for triggering a popup:
            showPopup(thisThing, event, popr_show, set, popr_cont);
        }

        var base_table = <?= json_encode($inferred_table) ?>;
        tablesWithField(fieldname, data_type, vals, base_table, callback);
    }


    // GLOBALS
    var show_hide_mode = 0;


    // HANDLERS

    // click on header field name (e.g. site_id) - joins to that table, e.g. site
    // displays join inline and allows you to toggle it back
    var thClickHandler = function(e){
        var elem = e.target;
        if (e.altKey) {
            // backlinked join handled by popr popup menu
        }
        else {
            if (getColsOpen(elem) > 0) {
                // already opened - close
                closeJoin(elem);
            }
            else { // not already opened - do open
                openJoin(elem);
            }
        }
    };

    // fold / unfold via click
    var tdClickHandler = function(e){

        if (show_hide_mode) {
            // alt to fold/unfold row
            if (e.altKey) {
                rowN = $(e.target).closest('tr').attr('data-row');

                if (e.shiftKey) {
                    unfoldRowsFrom(rowN);
                }
                else {
                    hideRow(rowN);
                }
            }
            // no alt to fold/unfold col
            else {
                colN = colNo(e.target);
                if (e.shiftKey) {
                    unfoldColsFrom(colN);
                }
                else {
                    hideCol(colN);
                }
            }
        }

    };


    $('table').on('click', 'td', tdClickHandler);
    $('table').on('click', 'th', thClickHandler);


</script>

<?php
                    }
