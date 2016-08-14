<?php
            { # form
?>
	<form
        id="query_form"
        method="post"
        action="<?= DbViewer::get_submit_url($requestVars) ?>"
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

<?php
                if ($limit_info['limit']) {
?>
            <span id="page_links">
                <a  id="page_prev"
                    href="<?= DbUtil::link_to_prev_page($limit_info) ?>"
                >
                    &lt;
                </a>
                <span id="num_per_page">
                    <?= $limit_info['limit'] ?> per page
                </span>
                <a  id="page_prev"
                    href="<?= DbUtil::link_to_next_page($limit_info) ?>"
                >
                    &gt;
                </a>
            </span>
<?php
                }
?>
        </div>
		<input type="submit" value="Submit">
	</form>
<?php
            }
?>
