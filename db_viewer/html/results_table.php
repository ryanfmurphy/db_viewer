<?php
        { # vars
            # strip quotes because e.g. dash doesn't want quotes
            $tablename_no_quotes = DbUtil::strip_quotes($inferred_table);
        }

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

                                    #todo will dash accept "schema.table" format?
?>
        <td>
            <a  href="<?= DbViewer::dash_edit_url($dash_path, $tablename_no_quotes, $primary_key) ?>"
                class="row_edit_link"
                target="_blank"
            >
                edit
            </a>
<?php
                                { # special ops (if any)
                                    $special_ops = [ #todo #fixme move to config
                                        [   'name' => 'present',
                                            'changes' => [
                                                'tense' => 'present',
                                            ],
                                        ],
                                        [   'name' => 'adj',
                                            'changes' => [
                                                'part_of_speech' => 'adj',
                                            ],
                                        ],
                                        [   'name' => 'comp adj',
                                            'changes' => [
                                                'part_of_speech' => 'adj',
                                                'comparative' => 't',
                                            ],
                                        ],
                                        [   'name' => 'noun',
                                            'changes' => [
                                                'part_of_speech' => 'noun',
                                            ],
                                        ],
                                        [   'name' => "don't know",
                                            'changes' => [
                                                'dont_know' => 't',
                                            ],
                                        ],
                                    ];
                                    if (isset($special_ops)
                                        && is_array($special_ops)
                                        && $primary_key
                                    ) {

                                        #todo factor this with the other def in dash
                                        $crud_api_path = "/dash/crud_api.php";

                                        foreach ($special_ops as $special_op) {
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
?>
            <nobr>
                <a  href="<?= $special_op_url ?>"
                    class="row_edit_link"
                    target="_blank"
                >
                    <?= $special_op['name'] ?>
                </a>
            </nobr><br>
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
