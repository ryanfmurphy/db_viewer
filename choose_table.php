<?php
    $tables = array('example_table1','example_table2');
?>
<ul>
<?php
    foreach ($tables as $table) {
?>
    <li>
        <a href="?table=<?= $table ?>">
            <?= $table ?>
        </a>
    </li>
<?php
    }
?>
</ul>
