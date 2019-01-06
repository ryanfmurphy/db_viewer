<?php
    # include_classes.php
    # needs $trunk defined before including

    require_once("$trunk/classes/Predicate.php");
    require_once("$trunk/classes/OrClauses.php");
    require_once("$trunk/classes/Db.php");
    require_once("$trunk/classes/Utility.php");
    require_once("$trunk/classes/DbUtil.php");
    require_once("$trunk/classes/EditorBackend.php");
    require_once("$trunk/classes/TableView.php");
    require_once("$trunk/classes/TreeView.php");
    require_once("$trunk/classes/Wikiness.php");
    require_once("$trunk/classes/Curl.php");
    require_once("$trunk/classes/Note.php");
    require_once("$trunk/classes/Query.php");

    if (Config::$config['table_view_render_txt_as_markdown']) {
        require_once("$trunk/classes/lib/PHP-Markdown-Lib/Michelf/Markdown.inc.php");
    }

?>
