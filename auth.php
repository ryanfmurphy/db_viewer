<?php
    $trunk = __DIR__;
    $cur_view = 'auth';
    require("$trunk/includes/init.php");

    $uri = $table_view_uri;
?>
<html>
    <head>
    </head>

    <h1>
        LOGIN
    </h1>
    <form action="<?= $uri ?>" method="post">
        <input name="db_user">
        <input name="db_password" type="password">
        <input type="submit" value="submit">
    </form>
</html>
