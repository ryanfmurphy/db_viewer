<?php
        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN) {
                $firstRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
        <th></th>
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

        { # table
            if (is_array($rows)) {
                if (count($rows) > 0) {
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

                        { # create table row
?>
    <tr data-row="<?= $rowN ?>">
<?php
                            {
                                # edit-row link
                                $primary_key_field = 'id';
                                $primary_key = $row[$primary_key_field];
?>
        <td>
            <a  href="/dash/edit.php?table=<?= $inferred_table ?>&primary_key=<?= $primary_key ?>"
                target="_blank"
            >
                edit
            </a>
        </td>
<?php
                            }

                            { # loop thru fields and make <td>s
                                foreach ($row as $fieldname => $val) {
?>
        <td>
            <?= DbViewer::val_html($val, $fieldname) ?>
        </td>
<?php
                                }
                            }
?>
    </tr>
<?php
                        }
                        $rowN++;
                    }
?>
</table>
<?php
                }
                else {
?>
                    0 Rows
<?php
                }
            }
            else {
                DbViewer::outputDbError($db);
            }
        }
