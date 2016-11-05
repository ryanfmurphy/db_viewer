<?php
        { # vars
            # strip quotes because e.g. dash doesn't want quotes
            $tablename_no_quotes = DbUtil::strip_quotes($inferred_table);
        }

        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN, $has_edit_column, $num_action_columns) {

                $currentRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
<?php
                if ($has_edit_column) {
?>
        <th class="action_cell"></th>
<?php
                }
                for ($i=0; $i<$num_action_columns; $i++) {
?>
        <th class="action_cell"></th>
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
                                      : 15; #todo make this editable in config

                    { # vars
                        $primary_key_field = DbUtil::getPrimaryKeyField(
                            $id_mode, $inferred_table);

                        $current_row = current($rows);
                        $has_primary_key_field = (isset($current_row[$primary_key_field])
                                                     ? true : false);

                        $special_ops_cols = isset($special_ops[$tablename_no_quotes])
                                                ? $special_ops[$tablename_no_quotes]
                                                : array();

                        #todo factor this with the other def in dash
                        $crud_api_path = "/dash/crud_api.php";
                    }

                    #todo will/does dash accept "schema.table" format?

                    $rowN = 0;
                    foreach ($rows as $row) {
                        { # vars per row
                            $primary_key = (isset($row[$primary_key_field])
                                                ? $row[$primary_key_field]
                                                : null);
                        }

                        { # sometimes add a header row
                            if ($rowN % $headerEvery == 0) {
                                $num_action_columns = count($special_ops_cols);
                                headerRow($rows, $rowN, $primary_key !== null, $num_action_columns);
                                $rowN++;
                            }
                        }

                        { # create table row
?>
    <tr data-row="<?= $rowN ?>">
<?php
                            { # action column(s): edit link & special_ops

                                { # edit link
                                    if ($has_primary_key_field) {

?>
        <td class="action_cell">
<?php
                                        { # edit link
?>
            <a  href="<?= DbViewer::dash_edit_url($dash_path, $tablename_no_quotes, $primary_key) ?>"
                class="row_edit_link"
                target="_blank"
            >
                edit
            </a>
<?php
                                        }
?>
        </td>
<?php
                                    }
                                }

                                { # special ops (if any)
                                    foreach ($special_ops_cols as $special_ops_col) {
?>
        <td class="action_cell">
            <ul>
<?php
                                        foreach ($special_ops_col as $special_op) {

                                            # the kind of special_op that changes fields
                                            if (isset($special_op['changes'])) {
                                                # query string
                                                $query_vars = array_merge(
                                                    array(
                                                        'action' => "update_$tablename_no_quotes",
                                                        'where_clauses' => array(
                                                            $primary_key_field => $primary_key,
                                                        ),
                                                    ),
                                                    $special_op['changes']
                                                );
                                                $query_str = http_build_query($query_vars);

                                                $special_op_url = "$crud_api_path?$query_str";
                                            }
                                            # the kind of special_op that goes to a url
                                            # with {{mustache_vars}} subbed in
                                            elseif (isset($special_op['url'])) {
                                                $special_op_url = preg_replace_callback(
                                                    '/{{([a-z_]+)}}/',
                                                    function($match) use ($row) {
                                                        $fieldname = $match[1];
                                                        return $row[$fieldname];
                                                    },
                                                    $special_op['url']
                                                );
                                            }
?>
            <li>
                <nobr>
                    <a  href="<?= $special_op_url ?>"
                        class="row_edit_link"
                        target="_blank"
                    >
                        <?= $special_op['name'] ?>
                    </a>
                </nobr>
            </li>
<?php
                                        }
?>
        </ul>
<?php
                                    }
?>
    </td>
<?php
                                }
                            }

                            { # loop thru fields and make <td>s
                                foreach ($row as $fieldname => $val) {
?>
        <td
<?php
                                    { # figure out classes, if any, for <td>

                                        # id fields: make them smaller, and copy on click
                                        $idness = DbUtil::is_id_field($fieldname);
                                        if ($idness) {
?>
            class="id_field <?= ($idness == 'id'
                                    ? 'uuid_field'
                                    : null) ?>"
            onclick="selectText(this)"
<?php
                                        }
                                        elseif ($fieldname == 'time' || $fieldname == 'time_added') {
?>
            class="time_field"
<?php
                                        }
                                    }
?>
        ><?=
            DbViewer::val_html($val, $fieldname, $tablename_no_quotes)
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
