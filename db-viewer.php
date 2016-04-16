<?php
	$cmp = class_exists('Util');

    if ($cmp) {
        #todo move out
        $jquery_url = "https://mbeta.contractormarketingpros.com/js/shared/jquery.current.js";
    }
    # other larger programs can integrate this one
    # by writing their own Util class with a sql()
    # function that takes a $query and returns an
    # array of rows, with each row itself an array
    else {
        require_once('init.php');
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
			shadeCol(n-1);
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
			console.log(nthRow(n));
			nthRow(n).hide();
			shadeRow(n-1);
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
			.css('background','initial'); // unshade
		unshadeCol(n);
	}

	function unfoldRowsFrom(n) {
		var row = nthRow(n);
		row.nextUntil(':visible')
			.show()
			.css('background','initial'); // unshade
		unshadeRow(n);
	}


	function shadeCol(n) {
		nthCol(n).css('background','#eeefee')
	}
	function unshadeCol(n) {
		nthCol(n).css('background','initial')
	}
	function shadeRow(n) {
		nthRow(n).css('background','#eeefee')
	}
	function unshadeRow(n) {
		nthRow(n).css('background','initial')
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
        #todo send the data to js via a var
        #todo generate the table via javascript
        #todo only render 100 rows or so in the table at first
        #todo then populate as you scroll
        #todo see if it's fast enough!
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
        var CELLS; // #todo #fixme delete

        // fold / unfold via click
		var tdClickHandler = function(e){
            // alt to fold/unfold row
            if (e.altKey) {
                rowN = $(e.target).closest('tr').attr('data-row');
                console.log(rowN);
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

        // data is keyed by id
        // add to HTML Table, lined up with relevant row
        function addDataToTable(cells, ids, data) {

            console.log('addDataToTable');

            // loop thru cells and add additional cells after
            cells.each(function(e){
                console.log(e);
            });

        }

        // click on header field name (e.g. site_id) - joins to that table, e.g. site
        // displays join inline and allows you to toggle it back
		var thClickHandler = function(e){

            var field_name = e.target.innerHTML.trim();
            var col_no = e.target.cellIndex;
            var suffix = field_name.slice(-3);

            if (suffix === '_id') {

                var prefix = field_name.slice(0,-3);
                console.log(prefix);
                console.log(field_name);

                // #todo validate prefix = valid table name?

                { // request adjoining table data #todo split into function

                    var table_name = prefix;

                    var all_cells = nthCol(col_no);
                    var val_cells = all_cells.filter('td');
                    CELLS = val_cells; // #todo #fixme delete
                    var ids = getColVals(val_cells);

                    console.log('ids');
                    console.log(ids);

                    var ids_str = ids.join(',');
                    var uri = 'query-id-in.php?table='+prefix+'&ids='+ids;

                    console.log(uri);

                    { // make request
                        $.ajax({
                            url: uri,
                            dataType: 'json',
                            success: function(data) {
                                console.log(data);
                                addDataToTable(all_cells, ids, data);
                            },
                            error: function(r) {
                                console.log("Failure");
                                alert("Failure");
                            }
                        });
                    }
                }
            }
            else {
                alert("Cannot expand this field \""+field_name+"\" - it doesn't end in \"_id\"");
            }
        };

<?php if ($cmp) { ?>
        $('td').live('click', tdClickHandler);
        $('th').live('click', thClickHandler);
<?php } else { ?>
        $('td').on('click', tdClickHandler);
        $('th').on('click', thClickHandler);
<?php }
?>

</script>

<?php
	}
}
?>
</body>
</html>
