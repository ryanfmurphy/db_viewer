<?php
    /*

    DB Viewer - database table view with inline dynamic joins
    =========================================================

    This program provides a PHP-HTML-Javascript web interface
    for a SQL Database, allowing you to type in queries and view
    the results in a table format.  You can hide/show rows and
    columns, and click on key fields to join in data from new
    tables.

    */

    # run a sql query
    # then build an html table to display the results
    
    #caveat - may have to set max_input_vars in php.ini for large views
    #caveat - names / text fields can be used to join, but they must match exactly,
            # even if the varchar is collated as case insensitive

    { # init
        $cmp = class_exists('Util');
        $oldJquery = $inlineCss = $cmp;

        # other larger programs that have their own db setup
        # can integrate with DbViwer by providing their own
        # Util class with a sql() function that takes a $query
        # and returns an array of rows, each row an array

        {   # init: defines $db, DbViewer,
            # and Util (if not already present)
            require_once('init.php');
        }

        { # url & resource setup - jquery etc
            if ($cmp) {
                #todo move out
                $jquery_url = "https://mbeta.contractormarketingpros.com/js/shared/jquery.current.js";
                $maybe_url_php_ext = ""; # no .php on end of url
            }
            else {
                $maybe_url_php_ext = ".php"; # .php on end of url
            }
        }

        { # get sql query (if any) from incoming request
            if (isset($_GET['sql'])) {
                $sql = $_GET['sql'];
            }
            else {
                $sql = null;
            }
        }
    }

	# header row html <th>'s, functionalized because it's repeated every so many rows
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

?>
<!DOCTYPE html>
<html>
<head>
    <!-- load jQuery -->
    <script src="<?= $jquery_url ?>"></script>
    <script src="popr/popr.js"></script>

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
?>

    <script>

    // GLOBALS
    var lastClickedElem;
    var backlinkJoinTable;


	function nthCol(n) {
        console.log('nthCol', n);
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


    // #todo if row is already split, increase rowspan if needed otherwise leave it alone
    function splitRow($row, num_rows) {
        $row.find('td').attr('rowspan', num_rows);
        var $last_row = $row;
        var $rows = [$row];
        for (var rowN = 1; rowN < num_rows; rowN++) {
            var $new_row = $('<tr></tr>');
            $rows.push($new_row); // array to return
            $last_row.after($new_row); // add to DOM
            $last_row = $new_row;
        }
        return $rows;
    }

    // given a row, add multiple rows that go alongside it
    // use rowspan to juxtapose the 1 row with the multiple joined rows
    // 1-to-many relationship
    function addInnerRowsToRow($row, new_rows, col_num) {
        var num_rows = new_rows.length;
        var $rows = splitRow($row, num_rows);

        // add to top row - some td's already present
        // must insert after the correct <td>
        var $top_elem = $row.find('td').eq(col_num);
        var new_content = new_rows[0];
        //console.log('new_content', new_content);
        $top_elem.after(new_content);
        //console.log('appended');

        // add to subsequent rows - no existing td's to compete with
        for (var rowN = 1; rowN < num_rows; rowN++) {
            //console.log('rowN',rowN);
            var $this_row = $rows[rowN];
            var new_content = new_rows[rowN];
            //console.log('new_content', new_content);
            $this_row.append(new_content);
            //console.log('appended');
        }
    }

    </script>

</head>

<body>
	<form>
		<textarea name="sql"><?= $sql ?></textarea>
		<br>
		<input type="submit">
	</form>
<?php

# get and display query data
{
	if ($sql) {
		$rows = Util::sql($sql,'array');
		#todo check if there's some rows
        #todo infinite scroll using OFFSET and LIMIT
?>
    <script>
        //var rows = <?= json_encode($rows) ?>;
    </script>

<table id="query_table">
<?php
		$headerEvery = isset($_GET['header_every'])
		                  ? $_GET['header_every']
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
			<?= $val ?>
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
                        <'+TD_or_TH+' \
                            class="level' + level + '" \
                            level="' + level + '" \
                        > \
                            '+display_val+' \
                        </'+TD_or_TH+'> \
                       ';
            $(elem).after(content);

            cols_open++;
        }

        $(elem).addClass('level' + level + 'handle')
            .attr('cols_open', cols_open);

    }

    function isHeaderRow(row) {
        return row.children('th').length > 0;
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

        var header_cells_str = '';
        for (i in field_names) {
            field_name = field_names[i];
            console.log('looping headers, field_name', field_name);
            header_cells_str += '\
                <th class="level' + innerLevel + '"\
                    level="' + innerLevel + '"\
                >\
                    ' + field_name + '\
                </th>\
            ';
        }
        var header_cells = $(header_cells_str);
        console.log('header_cells',header_cells);

        cells.each(function(idx,elem){
            var row = $(elem).closest('tr');
            if (isHeaderRow(row)) {
                console.log('header_row', header_cells);
                console.log('elem', elem);
                var these_header_cells = header_cells.clone();
                $(elem).after(these_header_cells);
            }
            else {
                var subrows = getDataKeyedByCellContents(elem, data); //, field_name);
                //console.log('elem',elem);
                //console.log('subrows',subrows);

                var table_subrows = subrows.map(
                    function(data_subrow, idx, arr){
                        var table_subrow = $.map(
                            field_names,
                            function(field_name,idx2) {
                                //console.log('field_name',field_name);
                                var val = data_subrow[field_name];
                                //console.log('val',val);
                                return $('\
                                    <td class="level' + innerLevel + '"\
                                        level="' + innerLevel + '"\
                                    >\
                                        ' + val + '\
                                    </td>\
                                ');
                            }
                        );
                        console.log('table_subrow',table_subrow);
                        return table_subrow;
                    }
                );
                //console.log('table_subrows',table_subrows);

                var col_num = elem.cellIndex;
                addInnerRowsToRow(row, table_subrows, col_num);
            }

            // update elem's css classes / color / etc
            $(elem).addClass('level' + innerLevel + 'handle')
                .attr('cols_open', num_new_cols);

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

    // elem is the pivot <th> element you click on to
    // close all the rows that have been opened from the join
    function closeJoin(elem) {

        var col_no = elem.cellIndex;
        var all_cells = nthCol(col_no);
        var cols_open = parseInt($(elem).attr('cols_open'));

        var handle_class;

        { // find handle_class ("levelXhandle" class) to remove to undo color

            // use first cell as an example - all of them are the same
            var first_cell = all_cells.first();
            // use native DomElement.classList
            var cell_classes = first_cell.get(0).classList;

            for (var i in cell_classes) {
                var classname = cell_classes[i];
                var is_handle_class = (classname.indexOf('handle') != -1);
                if (is_handle_class) {
                    handle_class = classname;
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

            $(elem).removeClass(handle_class); // restores earlier color
            $(elem).removeAttr('cols_open');

        });
    }

    function openJoin(elem) {

        // don't do openJoin if this is a popup menu click
        if ($(elem).is('.popr-item')) {
            return false;
        }

        var field_name = elem.innerHTML.trim();

        if (isValidJoinField(field_name)) {

            var prefix = field_name.slice(0,-3);

            // #todo validate prefix = valid table name?

            { // request adjoining table data #todo split into function

                { // figure out what data to ask for based on ids in col
                    //var table_name = prefix;

                    var col_no = elem.cellIndex;
                    var all_cells = nthCol(col_no);
                    var val_cells = all_cells.filter('td');
                    var ids = getColVals(val_cells);
                    var non_null_ids = ids.filter(isTruthy)
                        .map(trimIfString)
                    ;
                    // #todo remove dups from non_null_ids

                    var uri = 'query_id_in<?= $maybe_url_php_ext ?>';
                    var request_data = {
                        ids: non_null_ids,
                        join_field: field_name
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

        if (isValidJoinField(field_name)) {

            var col_no = elem.cellIndex;
            var all_cells = nthCol(col_no);
            var val_cells = all_cells.filter('td');
            var vals = getColVals(val_cells);
            var table = backlinkJoinTable;
            var data_type = null; // #todo generalize if necessary

            console.log('field_name',field_name);

            ajaxRowsWithFieldVals(
                field_name, vals, table, data_type,

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
    function ajaxRowsWithFieldVals(fieldname, vals, table, data_type, callback) {

        var uri = 'rows_with_field_vals<?= $maybe_url_php_ext ?>';

        var request_data = {
            fieldname: fieldname,
            vals: vals,
            table: table,
            data_type: data_type
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


    // GLOBALS
    var show_hide_mode = false;


    // HANDLERS

    // click on header field name (e.g. site_id) - joins to that table, e.g. site
    // displays join inline and allows you to toggle it back
    var thClickHandler = function(e){
        var elem = e.target;
        if ($(elem).attr('cols_open')) {
            // already opened - close
            closeJoin(elem);
        }
        else { // not already opened - do open
            if (e.altKey) {
                // backlinked join handled by popr popup menu
            }
            else {
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
                colN = e.target.cellIndex;
                if (e.shiftKey) {
                    unfoldColsFrom(colN);
                }
                else {
                    hideCol(colN);
                }
            }
        }

    };


<?php if ($oldJquery) { ?>
    $('td').live('click', tdClickHandler);
    $('th').live('click', thClickHandler);
<?php } else { ?>
    $('table').on('click', 'td', tdClickHandler);
    $('table').on('click', 'th', thClickHandler);
<?php }
?>

</script>

<?php
	}
}
?>

    <!-- init popup menu -->
    <div class="popr-box" data-box-id="1">
        <!-- #todo dynamically populate with relevant tables -->
        <div class="popr-item">contractor2campaign</div>
        <div class="popr-item">note2campaign</div>
    </div>
    <script>
        $(document).ready(function() {
            $('.popr').popr();
            $(document).on('click', '.popr-item', function(e){
                var elem = lastClickedElem;
                var popupItemElem = e.target;
                backlinkJoinTable = popupItemElem.innerHTML.trim();
                console.log('elem',elem);
                console.log('backlinkJoinTable',backlinkJoinTable);
                openBacklinkedJoin(elem);
            });
        });
    </script>
</body>
</html>
