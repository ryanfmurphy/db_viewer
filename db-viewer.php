<?php
    if (class_exists('Util')) {
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
		return $('#query_table tr > *:nth-child('+(n+1)+')');
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
		return $('#query_table tr:nth-child('
			+(parseInt(n)+1).toString()+
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
        // fold / unfold via click
        $('td').on('click', function(e){
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
        });

        // click on header field name (e.g. site_id) - joins to that table, e.g. site
        // displays join inline and allows you to toggle it back
        $('th').on('click', function(e){
            var field_name = e.target.innerHTML.trim();
            var suffix = field_name.slice(-3);
            if (suffix === '_id') {
                var prefix = field_name.slice(0,-3);
                console.log(prefix);
                console.log(field_name);

                { // #todo validate prefix = valid table name?
                    var table_name = prefix;
                    var ids = '1,2,3,4,5'; // #todo
                    var uri = 'query-id-in.php?table='+prefix+'&ids='+ids;
                    console.log(uri);
                    $.ajax({
                        url: uri,
                        dataType: 'json',
                        function(r) {
                            console.log("Success");
                            alert("Success");
                        },
                        function(r) {
                            console.log("Failure");
                            alert("Failure");
                        }
                    });
                }
            }
            else {
                alert("Cannot expand this field \""+field_name+"\" - it doesn't end in \"_id\"");
            }
        });

</script>

<?php
	}
}
?>
</body>
</html>
