<?php
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
?>
    <tr data-row="<?= $rowN ?>">
<?php
                                    foreach ($row as $val) {
?>
        <td>
            <?= DbViewer::val_html($val) ?>
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
<?php
                            }
                        }
                        else {
                            DbViewer::outputDbError($db);
                        }
                    }
