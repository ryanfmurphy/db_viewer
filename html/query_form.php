<?php
            { # form
?>
	<form id="query_form" method="post">
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
            <input name="db_type" value="<?= $db_type ?>">
        </div>
		<input type="submit" value="Submit">
	</form>
<?php
            }
?>
