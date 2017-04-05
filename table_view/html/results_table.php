<?php
        { # vars
            # strip quotes because e.g. obj_editor doesn't want quotes
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
                $currentRow = TableView::prep_row($row);
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
        <th field_name="<?= $field_name ?>" class="popr" data-id="1">
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
<?= TableView::echo_js_handle_edit_link_onclick_fn() ?>

<table id="query_table">
<?php
                    { # vars
                        $primary_key_field = DbUtil::get_primary_key_field(
                            $id_mode, $tablename_no_quotes
                        );

                        $current_row = current($rows);
                        $has_primary_key_field =
                                (array_key_exists($primary_key_field, $current_row)
                                                        ? true
                                                        : false
                                );

                        $special_ops_cols = isset($special_ops[$tablename_no_quotes])
                                                ? $special_ops[$tablename_no_quotes]
                                                : array();
                    }

                    #todo will/does obj_editor accept "schema.table" format?

                    $rowN = 0;
                    $last_row_time_added = null; # for bold_border_between_days
                    foreach ($rows as $row) {
                        { # vars per row
                            $primary_key = (isset($row[$primary_key_field])
                                                ? $row[$primary_key_field]
                                                : null);
                        }

                        { # sometimes add a header row
                            if ($rowN % $header_every == 0) {
                                $num_action_columns = count($special_ops_cols);
                                #$has_edit_column = ($primary_key !== null);
                                $has_edit_column = ($has_primary_key_field);
                                headerRow($rows, $rowN, $has_edit_column, $num_action_columns);
                                $rowN++;
                            }
                        }

                        { # create bold border between weeks (using "weekday" field)

                            # assuming: weekday field that uses "Mon" "Tue" etc. formatting
                            # and ordering desc by time so that the border between Sat and Sun
                            # would be above Sat
                            $bold_border_above = false;

                            if ($bold_border_between_weeks
                                && isset($row['weekday'])
                                && $row['weekday'] == 'Sat'
                                && !isset($row['time_added'])
                            ) {
                                $bold_border_above = true;
                            }

                            #todo #fixme require order by time_added or this doesn't make sense
                            if ($bold_border_between_days
                                && isset($row['time_added'])
                            ) {
                                if ($last_row_time_added
                                    && date('Y-m-d', strtotime($last_row_time_added))
                                        != date('Y-m-d', strtotime($row['time_added']))
                                    && TableView::query_is_order_by_field($sql, 'time_added')
                                ) {
                                    $bold_border_above = true;
                                }
                                $last_row_time_added = $row['time_added']; # for next time
                            }
                        }

                        { # create table row
                            # figure out color if any
                            $row_color = null;
                            if ($row_colors
                                && isset($row['color'])
                                && $row['color']
                            ) {
                                $row_color = $row['color'];
                            }
                            else {
                                $relname = isset($row['relname'])
                                                ? $row['relname']
                                                : null;
                                if ($color_rows_by_relname
                                    && $relname
                                    && isset($row_colors_by_relname)
                                    && isset($row_colors_by_relname[$relname])
                                ) {
                                    $row_color = $row_colors_by_relname[$relname];
                                }
                            }

?>
    <tr data-row="<?= $rowN ?>"
        <?= ($bold_border_above
                ? ' class="bold_border_above" '
                : '') ?>
<?php
                if ($row_color) {
?>
        style="background: <?= $row_color ?>"
<?php
                }
?>
    >
<?php
                            { # action column(s): edit link & special_ops

                                # edit link (needs pk)
                                if ($has_primary_key_field) {
                                    TableView::echo_edit_link(
                                        $obj_editor_uri, $tablename_no_quotes,
                                        $primary_key, $links_minimal_by_default
                                    );
                                }

                                # special ops (optional)
                                TableView::echo_special_ops(
                                    $special_ops_cols, $tablename_no_quotes,
                                    $primary_key_field, $primary_key, $crud_api_uri,
                                    $row
                                );
                            }

                            $row = TableView::prep_row($row);

                            { # loop thru fields and make <td>s
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
            TableView::val_html($val, $field_name, $tablename_no_quotes)
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
                $db = Db::conn();
                TableView::output_db_error($db);
            }
        }
