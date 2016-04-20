<?php
    #todo when you fetch more rows, incorporate all the joins you've done
        # but then you have to mark all those columns so you can collapse them
    #todo implement limit and offset

    #caveat - may have to set max_input_vars in php.ini for large views
    #caveat - names / text fields can be used to join, but they must match exactly,
            # even if the varchar is collated as case insensitive

	$cmp = class_exists('Util');

    if ($cmp) {
        #todo move out
        $jquery_url = "https://mbeta.contractormarketingpros.com/js/shared/jquery.current.js";
		$maybe_url_php_ext = ""; # no .php on end of url
    }
    # other larger programs can integrate this one
    # by writing their own Util class with a sql()
    # function that takes a $query and returns an
    # array of rows, with each row itself an array
    else {
        require_once('init.php');
		$maybe_url_php_ext = ".php"; # .php on end of url
    }


    # run a sql query
    # then build an html table to display the results

	# header row html
	function headerRow(&$rows, $rowN) {
		$firstRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
<?php
		foreach ($firstRow as $fieldName => $val) {
?>
		<th>
			<?= $fieldName ?>
		</th>
<?php
		}
?>
	</tr>
<?php
	}

	if (isset($_GET['sql'])) {
		$sql = $_GET['sql'];
	}
	else {
		$sql = null;
	}
?>
<html>
<head>
<!-- load jQuery -->
<script src="<?= $jquery_url ?>"></script>

<style>
	html {
		margin: 50px;
		padding:0;
        font-family: sans-serif;
	}
	body{
		margin: 0px;
		padding:0;
	}
	table {
		border-spacing: 0;
	}
	td, th {
		padding: .3em;
		border: solid 1px gray;
	}

	textarea, input {
        font-family: sans-serif;
		margin: 1em;
	}
	textarea {
		width: 90%;
		height: 5em;
        padding: .6em;
	}

    .shadowing,
    tr.shadowing td,
    tr.shadowing th {
        border: solid 2px #aaa;
    }

    .level1handle {
        background: #ff9999;
    }
    .level1 {
        background: #ffbbbb;
    }

    .level2handle {
        background: #99ff99;
    }
    .level2 {
        background: #bbffbb;
    }

    .level3handle {
        background: #9999ff;
    }
    .level3 {
        background: #bbbbff;
    }

</style>

<script>

	function nthCol(n) {
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

        var CELL_CLASSES; // #todo delete

        // fold / unfold via click
		var tdClickHandler = function(e){
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
        };

        function getColVals(cells) {
            var vals = $.map(cells, function(n,i){return n.innerText});
            return vals;
        }

        // util
        function firstObjKey(obj) {
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
            cells.each(function(row_index,e){

                var cols_open = 0;

                // loop thru all fields and add a column for each row
                for (i in field_names) {

                    var field_name = field_names[i];
                    if (field_name in exclude_fields) continue;

                    var cell_val = e.innerHTML.trim();
                    var key = cell_val;

                    var val = (key in data
                                    ? data[key][field_name]
                                    : '');

                    var TD_or_TH = e.tagName;
                    var display_val = (TD_or_TH == 'TH'
                                            ? field_name
                                            : showVal(val));
                    var content = '\
                                <'+TD_or_TH+'                   \
                                    class="level'+innerLevel+'" \
                                    level="'+innerLevel+'"      \
                                >                               \
                                    '+display_val+'             \
                                </'+TD_or_TH+'>                 \
                               ';
                    $(e).after(content);

                    cols_open++;
                }

                $(e).addClass('level'+innerLevel+'handle')
                    .attr('cols_open', cols_open);

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

        // click on header field name (e.g. site_id) - joins to that table, e.g. site
        // displays join inline and allows you to toggle it back
		var thClickHandler = function(e){

            var elem = e.target;
            var col_no = elem.cellIndex;

            // already opened - close
            if ($(elem).attr('cols_open')) {
                var all_cells = nthCol(col_no);
                var cols_open = parseInt($(elem).attr('cols_open'));

                var handle_class;

                { // find handle_class ("levelXhandle" class) to remove to undo color

                    // use first cell as an example - all of them are the same
                    var first_cell = all_cells.first();
                    // use native DomElement.classList
                    var cell_classes = first_cell.get(0).classList;

                    CELL_CLASSES = cell_classes; // #todo delete

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
            else { // not already opened - do open
                var field_name = elem.innerHTML.trim();

                if (isValidJoinField(field_name)) {

                    var prefix = field_name.slice(0,-3);

                    // #todo validate prefix = valid table name?

                    { // request adjoining table data #todo split into function

                        { // figure out what data to ask for based on ids in col
                            //var table_name = prefix;

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
        };

<?php if ($cmp) { ?>
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
</body>
</html>
