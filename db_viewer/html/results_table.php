<?php
        { # vars
            # strip quotes because e.g. dash doesn't want quotes
            $tablename_no_quotes = DbUtil::strip_quotes($inferred_table);
        }

        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN, $add_actions_column) {
                $currentRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
<?php
                if ($add_actions_column) {
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
                                      : 15; #todo make this editable in config

                    { # primary key stuff for edit_link
                        $primary_key_field = DbUtil::getPrimaryKeyField($id_mode, $inferred_table);

                        $current_row = current($rows);
                        $has_primary_key = (isset($current_row[$primary_key_field])
                                                ? true : false);
                    }

                    $rowN = 0;
                    foreach ($rows as $row) {
                        if ($rowN % $headerEvery == 0) {
                            headerRow($rows, $rowN, $has_primary_key);
                            $rowN++;
                        }

                        { # create table row
?>
    <tr data-row="<?= $rowN ?>">
<?php
                            {   # edit-row link & special_ops
                                if ($has_primary_key) {
                                    $primary_key = $row[$primary_key_field];

                                    #todo will dash accept "schema.table" format?
?>
        <td>
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

                                    { # special ops (if any)
                                        if (isset($special_ops)
                                            && is_array($special_ops)
                                            && isset($special_ops[$tablename_no_quotes])
                                            && $primary_key
                                        ) {
                                            #todo factor this with the other def in dash
                                            $crud_api_path = "/dash/crud_api.php";

                                            foreach ($special_ops[$tablename_no_quotes]
                                                     as $special_op
                                            ) {
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
            <br>
            <nobr>
                <a  href="<?= $special_op_url ?>"
                    class="row_edit_link"
                    target="_blank"
                >
                    <?= $special_op['name'] ?>
                </a>
            </nobr>
<?php
                                            }
                                        }
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
