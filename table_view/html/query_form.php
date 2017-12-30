<?php
    { # form
?>
	<form
        id="query_form"
        method="post"
        action="<?= TableView::get_submit_url($requestVars) ?>"
    >
        <h2 id="query_header">
            Enter SQL Query
        </h2>
        <p id="limit_warning">
            Warning:
            BYOL - "Bring Your Own Limit"
            (otherwise query may be slow)
        </p>
        <textarea id="query-box" name="sql"><?= $sql ?></textarea>
        <br>
        <div>
            <label for="db_type">DB Type</label>
            <input name="db_type"
                   value="<?= $db_type ?>"
                   type="text"
            >
        </div>
        <input type="submit" value="Submit">
	</form>
<?php
        $new_column_every = 3;
        if (is_array($custom_query_links)) {
?>
    <div id="custom_query_links">
        <h3>Suggestions</h3>
        <ul>
<?php
            $n = 0;
            foreach ($custom_query_links as $name => $url) {
                if ($n >= $new_column_every) {
                    $n = 0;
?>
        </ul>
        <ul>
<?php
                }
                $n++;
?>
            <li>
                <a href="<?= $url ?>">
                    <?= $name ?>
                </a>
            </li>
<?php
            }
?>
        </ul>
    </div>
<?php
        }
    }
?>
