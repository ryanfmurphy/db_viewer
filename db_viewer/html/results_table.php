<?php
        { # vars
            # strip quotes because e.g. dash doesn't want quotes
            $tablename_no_quotes = DbUtil::strip_quotes($inferred_table);
        }

        function includeField($field_name) {
            $minimal = Config::$config['minimal'];
            $minimal_fields = Config::$config['minimal_fields'];
            return (
                !$minimal
                || in_array($field_name, $minimal_fields)
            );
        }

        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN, $has_edit_column, $num_action_columns) {
                $row = current($rows);
                $currentRow = DbViewer::prep_row($row);
?>
    <tr data-row="<?= $rowN ?>">
<?php
                { # action columns
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
                }

                { # regular data columns
                    foreach ($currentRow as $field_name => $val) {
                        if (includeField($field_name)) {
?>
        <th class="popr" data-id="1">
            <?= $field_name ?>
        </th>
<?php
                        }
                    }
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

<?php include('next_prev_page_links.php'); ?>
<?= DbViewer::echo_js_handle_edit_link_onclick_fn() ?>

<table id="query_table">
<?php
                    { # vars
                        $primary_key_field = DbUtil::getPrimaryKeyField(
                            $id_mode, $tablename_no_quotes
                        );

                        $current_row = current($rows);
                        $has_primary_key_field = (isset($current_row[$primary_key_field])
                                                     ? true : false);

                        $special_ops_cols = isset($special_ops[$tablename_no_quotes])
                                                ? $special_ops[$tablename_no_quotes]
                                                : array();
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
                            if ($rowN % $header_every == 0) {
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
                                if ($has_primary_key_field) {
                                    DbViewer::echo_edit_link(
                                        $dash_path, $tablename_no_quotes,
                                        $primary_key, $links_minimal_by_default
                                    );
                                }

                                DbViewer::echo_special_ops(
                                    $special_ops_cols, $tablename_no_quotes,
                                    $primary_key_field, $primary_key, $crud_api_path,
                                    $row
                                );
                            }

                            { # loop thru fields and make <td>s
                                $row = DbViewer::prep_row($row);
                                foreach ($row as $field_name => $val) {
                                    if (includeField($field_name)) {
?>
        <td
<?php
                                        { # figure out classes, if any, for <td>

                                            # id fields: make them smaller, and copy on click
                                            $idness = DbUtil::is_id_field($field_name);
                                            if ($idness) {
?>
            class="id_field <?= ($idness == 'id' && $id_fields_are_uuids
                                    ? 'uuid_field'
                                    : null) ?>"
            onclick="selectText(this)"
<?php
                                            }
                                            elseif ($field_name == 'time' || $field_name == 'time_added') {
?>
            class="time_field"
<?php
                                            }
                                        }
?>
        ><?=
            DbViewer::val_html($val, $field_name, $tablename_no_quotes)
        ?></td>
<?php
                                    }
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
                DbViewer::output_db_error($db);
            }
        }
