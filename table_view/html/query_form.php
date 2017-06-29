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
        if (is_array($custom_query_links)) {
?>
    <div>
        <h3>Suggestions</h3>
        <ul>
<?php
            foreach ($custom_query_links as $name => $url) {
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
