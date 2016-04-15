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
        require_once('db-config.php');
        $db = mysqli_connect(
            $db_host, $db_user, $db_password,
            $db_name #, $db_port
        );

        class Util {
            public static function sql($query, $returnType='array') {
                global $db;
                $result = mysqli_query($db, $query);
                $rows = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $rows[] = $row;
                }
                return $rows;
            }
        }

        $jquery_url = "/js/jquery.min.js"; #todo #fixme
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
		margin: 1em;
	}
	textarea {
		width: 90%;
		height: 10vh;
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
?>

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
        $('td,th').on('click', function(e){
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

</script>

<?php
	}
}
?>
</body>
</html>
