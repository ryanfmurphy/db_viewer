<?php
        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN, $add_edit_link) {
                $currentRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
<?php
                if ($add_edit_link) {
?>
        <th></th>
<?php
                }
                foreach ($currentRow as $fieldName => $val) {
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

<?php
    include('next_prev_page_links.php');
?>
<table id="query_table">
<?php
                    $headerEvery = isset($requestVars['header_every'])
                                      ? $requestVars['header_every']
                                      : 15;

                    { # primary key stuff for edit_link
                        $primary_key_field = DbUtil::getPrimaryKeyField($id_mode, $inferred_table);

                        $current_row = current($rows);
                        $has_primary_key = (isset($current_row[$primary_key_field])
                                                ? true : false);
                        $add_edit_link = ($has_primary_key
                                            ? true : false);
                    }

                    $rowN = 0;
                    foreach ($rows as $row) {
                        if ($rowN % $headerEvery == 0) {
                            headerRow($rows, $rowN, $add_edit_link);
                            $rowN++;
                        }

                        { # create table row
?>
    <tr data-row="<?= $rowN ?>">
<?php
                            { # edit-row link
                                if ($add_edit_link) {
                                    $primary_key = $row[$primary_key_field];

                                    # strip quotes because dash doesn't want quotes
                                    # #todo will dash accept "schema.table" format?
                                    $tablename4dash = DbUtil::strip_quotes($inferred_table);
?>
        <td>
            <a  href="<?= $dash_path ?>?edit=1&table=<?= $tablename4dash ?>&primary_key=<?= $primary_key ?>"
                class="row_edit_link"
                target="_blank"
            >
                edit
            </a>
        </td>
<?php
                                }
                            }

                            { # loop thru fields and make <td>s
                                foreach ($row as $fieldname => $val) {
?>
        <td
<?php
                                    #todo factor logic into DbUtil fn
                                    {
                                        $is_iid = false;
                                        if ($fieldname == 'id'
                                            || substr($fieldname, strlen($fieldname)-3) == '_id'
                                            || ($is_iid = true # assignment
                                                && ($fieldname == 'iid'
                                                    || substr($fieldname, strlen($fieldname)-4) === '_iid'))
                                        ) {
                                            if ($is_iid) {
?>
            class="id_field"
<?php
                                            }
                                            else {
?>
            class="id_field uuid_field"
<?php
                                            }
?>
            onclick="selectText(this)"
<?php
                                        }
                                    }

                                    if ($fieldname == 'time' || $fieldname == 'time_added') {
?>
            class="time_field"
<?php
                                    }
?>
        ><?=
            DbViewer::val_html($val, $fieldname)
        ?></td>
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
    include('next_prev_page_links.php');
?>
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
