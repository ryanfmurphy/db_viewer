<?php
        { # vars
            # strip quotes because e.g. obj_editor doesn't want quotes
            $tablename_no_quotes = DbUtil::strip_quotes($inferred_table);
            # sometimes can't update view directly, must update underlying table
            $table_for_update = TableView::table_for_update($tablename_no_quotes);
        }

        function includeField($field_name, $tablename_no_quotes) {
            $minimal = Config::$config['minimal'];
            $minimal_fields = Config::$config['minimal_fields'];
            $table_view_exclude_fields_by_table = Config::$config['table_view_exclude_fields_by_table'];
            $exclude_fields_by_table = Config::$config['exclude_fields_by_table'];
            $exclude_fields = (isset($table_view_exclude_fields_by_table[$tablename_no_quotes])
                                        ? $table_view_exclude_fields_by_table[$tablename_no_quotes]
                                        : isset($exclude_fields_by_table[$tablename_no_quotes])
                                            ? $exclude_fields_by_table[$tablename_no_quotes]
                                            : array());
            return (
                !in_array($field_name, $exclude_fields)
                && (!$minimal
                    || in_array($field_name, $minimal_fields)
                   )
            );
        }

        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN, $has_primary_key_field, $num_action_columns, $tablename_no_quotes) {
                $row = current($rows);
                $currentRow = TableView::prep_row($row);
                
                $has_checkbox = $has_primary_key_field
                                && Config::$config['table_view_checkboxes'];
                $has_edit_column = $has_primary_key_field;

                $has_delete_column = Config::$config['include_row_delete_button']
                                     && $has_edit_column;
                $has_tree_column =  Config::$config['include_row_tree_button']
                                    && $has_edit_column;
?>
    <tr data-row="<?= $rowN ?>">
<?php
                { # action columns
                    if ($has_checkbox) {
?>
        <th class="action_cell"></th>
<?php
                    }

                    if ($has_edit_column) {
?>
        <th class="action_cell"></th>
<?php
                    }

                    # delete button
                    if ($has_delete_column) {
?>
        <th class="action_cell"></th>
<?php
                    }

                    # delete button
                    if ($has_tree_column) {
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
                        if (includeField($field_name, $tablename_no_quotes)) {
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
<?= TableView::echo_js__hit_url_and_rm_row_from_ui__fn() ?>

<table  id="query_table"
        data-table_for_update="<?= $table_for_update ?>"
>
<?php
                    { # vars
                        $primary_key_field = DbUtil::get_primary_key_field($tablename_no_quotes);

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
                            $relname = isset($row['relname'])
                                            ? $row['relname']
                                            : null;
                        }

                        { # sometimes add a header row
                            if ($rowN % $header_every == 0) {
                                $num_action_columns = count($special_ops_cols);
                                headerRow($rows, $rowN, $has_primary_key_field, $num_action_columns, $tablename_no_quotes);
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
                            # figure out row color if any
                            $row_color = null;
                            if ($row_colors
                                && isset($row['row_color'])
                                && $row['row_color']
                            ) {
                                $row_color = $row['row_color'];
                            }
                            else {
                                if ($color_rows_by_relname
                                    && $relname
                                ) {
                                    # can either get these from the DB
                                    if ($color_rows_by_relname == 'from db'
                                        && isset($row['row_color_by_relname'])
                                    ) {
                                        $row_color = $row['row_color_by_relname'];
                                    }
                                    # or just from a regular PHP array in the db_config
                                    elseif (isset($row_colors_by_relname)
                                            && isset($row_colors_by_relname[$relname])
                                    ) {
                                        $row_color = $row_colors_by_relname[$relname];
                                    }
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

                                # edit and delete links (need pk)
                                if ($has_primary_key_field) {

                                    # in polymorphic cases with "relname", update that table instead
                                    $table_for_edit_link = ($relname
                                                                ? DbUtil::strip_quotes($relname)
                                                                : $table_for_update);

                                    # checkbox for selecting
                                    if (Config::$config['table_view_checkboxes']) {
                                        TableView::echo_checkbox(
                                            $obj_editor_uri, $table_for_edit_link, $primary_key
                                        );
                                    }

                                    # edit
                                    TableView::echo_edit_link(
                                        $obj_editor_uri, $table_for_edit_link,
                                        $primary_key, $links_minimal_by_default
                                    );

                                    # delete
                                    if ($include_row_delete_button) {
                                        TableView::echo_delete_button(
                                            $obj_editor_uri, $table_for_edit_link, $primary_key
                                        );
                                    }

                                    # tree
                                    if ($include_row_tree_button) {
                                        #todo #fixme - should we use table_for_edit_link here?
                                        # would fix e.g. trying to get the tree for an obj
                                        # that is excluded from the entity_view
                                        TreeView::echo_tree_button(
                                            $obj_editor_uri, $tablename_no_quotes, $primary_key
                                        );
                                    }
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
                                    if (includeField($field_name, $tablename_no_quotes)) {
?>
        <td data-field_name="<?= $field_name ?>"
<?php
                                        { # figure out classes, if any, for <td>

                                            # id fields: make them smaller, and copy on click
                                            $idness = DbUtil::is_id_field($field_name);
                                            if ($idness) {
?>
            class="id_field <?= ($idness == 'id' && $id_fields_are_uuids
                                    ? 'uuid_field'
                                    : '')
                                . ' ' . ($field_name == $primary_key_field
                                            ? 'primary_key'
                                            : '') ?>"
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
                DbUtil::output_db_error($db);
            }
        }
