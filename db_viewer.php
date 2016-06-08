<?php
    {
    /*

    DB Viewer - database table view with inline dynamic joins
    =========================================================
    Copyright (c) 2016 Ryan Murphy

    This program provides a PHP-HTML-Javascript web interface
    for a SQL Database, allowing you to type in queries and view
    the results in a table format.  You can hide/show rows and
    columns, and click on key fields to join in data from new
    tables.


    Summary of This File
    --------------------
    * run a sql query
    * build an html table to display the results
    * javascript to allow splicing in join data from other tables
    
    Caveats
    -------
    * may have to set max_input_vars in php.ini for large views
      because column vals[] sent in POST as array - sometimes hits max

    * names / text fields can be used to join, but they must match exactly,
      even if the varchar is collated as case insensitive

    */
    }

    { # init

        { # vars
            $cmp = class_exists('Util');
            $inlineCss = $cmp;
        }

        # other larger programs that have their own db setup
        # can integrate with DbViwer by providing their own
        # Util class with a sql() function that takes a $query
        # and returns an array of rows, each row an array

        {   # init: defines $db, DbViewer,
            # and Util (if not already present)
            require_once('init.php');
        }

        { # vars
            { # url & resource setup - jquery etc
                $jsPath = ($cmp ? '/js/shared' : '/js');
                $jquery_url = "$jsPath/jquery-1.12.3.js";
                if ($cmp) {
                    #todo move out
                    $maybe_url_php_ext = ""; # no .php on end of url
                }
                else {
                    $maybe_url_php_ext = ".php"; # .php on end of url
                }

                $poprJsPath = ($cmp ? '/js/shared/' : '');
            }

            { # get sql query (if any) from incoming request
                $sql = (isset($requestVars['sql'])
                            ? $requestVars['sql']
                            : null);
            }

            $inferred_table = DbViewer::infer_table_from_query($sql);
        }

        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN) {
                $firstRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
<?php
                foreach ($firstRow as $fieldName => $val) {
?>
		<th class="popr" data-id="1">
			<?= $fieldName ?>
		</th>
<?php
                }
?>
	</tr>
<?php
            }
        }
    }

    { # html
?>
<!DOCTYPE html>
<html>
<?php
        { # <head> (including js)
?>
<head>
<?php
            { # links/scripts
?>
    <!-- load jQuery -->
    <script src="<?= $jquery_url ?>"></script>
    <script src="<?= $poprJsPath ?>popr/popr.js"></script>

<?php
                if ($inlineCss && $cmp) {
		$cssPath = __DIR__ . "/style.css.php";
?>
    <style>
		<?php include($cssPath); ?>
        <?php include('popr/popr.css'); ?>
    </style>
<?php
                }
                else {
?>
    <link rel="stylesheet" type="text/css" href="style.css.php">
    <link rel="stylesheet" type="text/css" href="popr/popr.css">
<?php
                }
            }

            { # inline js
?>
    <script>

    // GLOBALS

    var lastClickedElem;
    var backlinkJoinTable;

    // keep track of table joins
    // for color choice if nothing else
    var joinNum = 0;


	function nthCol(n) {
        //console.log('nthCol', n);
        var n_plus_1 = (parseInt(n)+1).toString();
		return $('#query_table tr > *:nth-child('
            + n_plus_1 +
        ')');
	}

	function hideCol(n) {
		if (n > 0) {
			nthCol(n).hide();
			setShadowingCol(n-1);
		}
		else {
			alert("Can't hide 1st column");
		}
	}

	function showCol(n) {
		return nthCol(n).show();
	}

	function nthRow(n) {
        var n_plus_1 = (parseInt(n)+1).toString();
		return $('#query_table tr:nth-child('
			+ n_plus_1 +
		')');
	}

	function hideRow(n) {
		if (n > 0) {
			nthRow(n).hide();
			setShadowingRow(n-1);
		}
		else {
			alert("Can't hide 1st row");
		}
	}

	function showRow(n) {
		return nthRow(n).show();
	}

	function unfoldColsFrom(n) {
		var col = nthCol(n);
		col.nextUntil(':visible')
			.show()
			.removeClass('shadowing');
		unsetShadowingCol(n);
	}

	function unfoldRowsFrom(n) {
		var row = nthRow(n);
		row.nextUntil(':visible')
			.show()
			.removeClass('shadowing');
		unsetShadowingRow(n);
	}


	function setShadowingCol(n) {
		nthCol(n).addClass('shadowing'); //.css('background','#eeefee')
	}
	function unsetShadowingCol(n) {
		nthCol(n).removeClass('shadowing'); //.css('background','initial')
	}
	function setShadowingRow(n) {
		nthRow(n).addClass('shadowing'); //.css('background','#eeefee')
	}
	function unsetShadowingRow(n) {
		nthRow(n).removeClass('shadowing'); //.css('background','initial')
	}


    // split the row into `num_rows` rows
    // don't use a rowspan - it's simpler just to duplicate the id_field
        // might not look quite as cool but much simpler to code
        // also allows for tabular copy-and-paste into unix-style programs
    function splitRow($row, num_rows, mark_odd_row) {
        console.log('splitRow, num_rows =', num_rows);

        var $rows = [$row];

        // classes on the original (top) row
        // (even if we don't actually add any rows)
        $row.addClass('has-extra-rows');
        if (mark_odd_row) {
            $row.addClass('odd-row');
        }

        if (num_rows > 1) {

            var $prev_row = $row;
            console.log('adding new rows');

            // add rows
            for (var rowN = 1; rowN < num_rows; rowN++) {
                console.log('adding new extra row');

                var $new_row = $row.clone();
                $new_row.addClass('extra-row');
                if (mark_odd_row) {
                    $new_row.addClass('odd-row');
                }

                $rows.push($new_row);      // add to return array
                $prev_row.after($new_row); // add to DOM
                $prev_row = $new_row;
            }
        }
        else {
            console.log('no extra rows to add!');
        }

        return $rows;
    }

    function extraRowsUnder($row) {
        return $row.nextUntil(':not(.extra-row)');
    }

    function rowOf(elem) {
        return $(elem).closest('tr');
    }

    // jQuery allows handling multiple elements the same as 1
    rowsOf = rowOf;

    // undo the split created by splitRow
    function unsplitRow($row) {
        // remove tr.extra-row's underneath
        var extra_rows = extraRowsUnder($row);
        extra_rows.remove();
        $row.children('td');
        $row.removeClass('has-extra-rows')
            .removeClass('odd-row')
            ;
    }

    // unsplitRow for each row of each cell in cells
    function unsplitRows($cells) {
        $cells.each(function(idx,elem) {
            var $row = rowOf(elem);
            unsplitRow($row)
        });
    }

    // given a row, split it into multiple rows that go alongside it
    // for 1-to-many relationship
    function addExtraRowsBelowRow($row, new_rows, col_no, mark_odd_row) {
        var num_rows = new_rows.length;
        // add the needed rows
        var $rows = splitRow($row, num_rows, mark_odd_row);

        for (var rowN = 0; rowN < num_rows; rowN++) {
            // add to top row - some td's already present
            // must insert after the correct <td>
            var this_row = $rows[rowN];
            var first_elem = this_row.find('td').eq(col_no);
            var new_content = new_rows[rowN];
            //console.log('new_content', new_content);
            first_elem.after(new_content);
            //console.log('appended');
        }
    }

    </script>
<?php
            }

            { # dynamic style / CSS choices

                { # choose background image if any
                    if (!isset($backgroundImages)) $backgroundImages = array();

                    $backgroundImageUrl = (isset($inferred_table)
                                           && isset($backgroundImages[$inferred_table])
                                                ? $backgroundImages[$inferred_table]
                                                : (isset($backgroundImages['fallback image'])
                                                    ? $backgroundImages['fallback image']
                                                    : null)
                                          );

                    $hasBackgroundImage = (isset($backgroundImageUrl)
                                        && $backgroundImageUrl);

                    if ($hasBackgroundImage) {
?>
    <style>
    body {
        background-color: black;
        background-image: url(<?= $backgroundImageUrl ?>);
        background-position: center;
        background-repeat: no-repeat;
        color: white;
    }
    td,th {
        border: solid 1px white;
    }
    table, textarea {
        color: white;
        background: rgba(100,100,100,.5);
        border: solid 1px white;
    }
    a {
        color: #88f;
    }
    </style>
<?php
                    }
                }

                { # join colors
                    if ($hasBackgroundImage) {
                        $joinColors = array(
                            1 => array(
                                'handle' => array(225, 0, 0, .5), #'#ff9999',
                                'row' => array(150, 0, 0, .5), #'#ffbbbb',
                            ),
                            2 => array(
                                'handle' => array(0, 225, 0, .5), # '#99ff99',
                                'row' => array(0, 150, 0, .5), # '#bbffbb',
                            ),
                            3 => array(
                                'handle' => array(0, 0, 225, .5), # '#9999ff',
                                'row' => array(0, 0, 150, .5), # '#bbbbff',
                            ),
                        );
                    }
                    else {
                        $joinColors = array(
                            1 => array(
                                'handle' => array(255, 153, 153, 1),
                                'row' => array(255, 187, 187, 1),
                            ),
                            2 => array(
                                'handle' => array(153, 255, 153, 1),
                                'row' => array(187, 255, 187, 1),
                            ),
                            3 => array(
                                'handle' => array(153, 153, 255, 1),
                                'row' => array(187, 187, 255, 1),
                            ),
                        );
                    }
?>

    <style>
<?php
                    # template out rgba() color in CSS based on array
                    function rgbaColor($colorArray, $mult=1) {
                        if ($mult !== 1) {
                            foreach ($colorArray as $i => &$color) {
                                if ($i < 3) { // alpha stays the same
                                    $color *= $mult;
                                    $color &= (int)(round($color));
                                }
                            }
                        }
                        $colorStr = implode(",", $colorArray);
                        return "rgba($colorStr)";
                    }

                    $oddRowDarkness = .9;
                    for ($level = 1; $level <= 3; $level++) {
?>
    .join_color_<?= $level ?>_handle {
        background: <?= rgbaColor($joinColors[$level]['handle']) ?>;
    }
    .join_color_<?= $level ?> {
        background: <?= rgbaColor($joinColors[$level]['row']) ?>;
    }

    /* darker for odd-row's */
    .odd-row .join_color_<?= $level ?>_handle {
        background: <?= rgbaColor($joinColors[$level]['handle'], $oddRowDarkness) ?>;
    }
    .odd-row .join_color_<?= $level ?> {
        background: <?= rgbaColor($joinColors[$level]['row'], $oddRowDarkness) ?>;
    }
    .odd-row {
        background: rgb(220,220,220); /* #todo adjust for dark bg & backgroundImage */
    }

<?php
                    }
?>
        </style>

<?php
                }
            }
?>

</head>
<?php
        }

        { # <body>

            { # form
?>
<body>
	<form method="post">
        <h2 id="query_header">
            Enter SQL Query
        </h2>
        <p id="limit_warning">
            Warning: BYOL - "Bring Your Own Limit" (otherwise query may be slow)
        </p>
		<textarea id="query-box" name="sql"><?= $sql ?></textarea>
		<br>
        <div>
            <label for="db_type">DB Type</label>
            <input name="db_type" value="<?= $db_type ?>">
        </div>
		<input type="submit" value="Submit">
	</form>
<?php
            }

            { # inferred table
                if ($sql) {
?>
    <p> Query seems to be with respect to the
        <code><?= $inferred_table ?></code>
        table
    </p>
<?php
                }
            }

            { # get & display query data, & js interface

                #todo infinite scroll using OFFSET and LIMIT
                if ($sql) {
                    $rows = Util::sql($sql,'array');

                    # table
                    if (is_array($rows)) {
                        # check if there's some rows
?>

<table id="query_table">
<?php
                        $headerEvery = isset($requestVars['header_every'])
                                          ? $requestVars['header_every']
                                          : 15;

                        $rowN = 0;
                        foreach ($rows as $row) {
                            if ($rowN % $headerEvery == 0) {
                                headerRow($rows, $rowN);
                                $rowN++;
                            }
?>
	<tr data-row="<?= $rowN ?>">
<?php
                            foreach ($row as $val) {
?>
		<td>
			<?= DbViewer::val_html($val) ?>
		</td>
<?php
                            }
?>
	</tr>
<?php
                            $rowN++;
                        }
?>
</table>
<?php
                    }
                    else {
                        DbViewer::outputDbError($db);
                    }

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
                }

                { # js to show even if there's no query in play
?>
<script>

    function queryBoxElem() {
        return document.getElementById('query-box');
    }

</script>
<?php
                }
            }
?>

    <!-- init popup menu -->
    <div class="popr-box" data-box-id="1">
        <!-- #todo dynamically populate with relevant tables -->
        <div class="popr-item">example</div>
        <div class="popr-item">popup</div>
        <div class="popr-item">data</div>
        <div class="popr-item">(will be dynamically overridden)</div>
    </div>
    <script>
        $(document).ready(function() {
            $('.popr').popr();

            // on click popup item
            $(document).on('click', '.popr-item', function(e){
                var elem = lastClickedElem;
                var popupItemElem = e.target;
                backlinkJoinTable = popupItemElem.innerHTML.trim();
                console.log('elem',elem);
                console.log('backlinkJoinTable',backlinkJoinTable);
                openBacklinkedJoin(elem);
            });

            // show_hide_mode toggle
            $(document).on('keypress', 'body', function(e){
                if (queryBoxElem() !== document.activeElement) {
                    var H_code = 104;
                    if (e.keyCode == H_code) {
                        show_hide_mode = 1 - show_hide_mode;
                        if (show_hide_mode) {
                            alert('Show/Hide Mode Enabled:\n\n\
Click a column to hide it, shift-click to reveal it again.\n\
Alt-Click a row to hide it, alt-shift-click to reveal it again.\n\
Press H again to disable.');
                        }
                        else {
                            alert('Show/Hide Mode Disabled');
                        }
                    }
                }
            });
        });
    </script>
</body>
<?php
        }
?>
</html>
<?php
    }
?>
