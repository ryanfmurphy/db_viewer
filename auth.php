<?php
    $trunk = __DIR__;
    $cur_view = 'auth';
    require("$trunk/includes/init.php");

    $uri = $table_view_uri;
    $app_name = 'DB Viewer';
?>
<html>
    <head>
        <style>
            body {
                font-family: sans-serif;
                width: 30rem;
                margin: 5rem auto;
                font-size: 100%;
<?php
    if ($background == 'dark') {
?>
                background: black;
                color: white;
<?php
    }
?>
            }
            h1 {
                text-align: center;
            }
            form div.form_row {
                margin: .5em;
                text-align: center;
            }
            form input {
                width: 15rem;
                font-size: 150%;
            }
            form input[type=submit] {
                width: initial;
                margin-top: 1rem;
            }
        </style>
    </head>

    <h1>
        <?= $app_name ?> - Login
    </h1>
    <form action="<?= $uri ?>" method="post">
        <div class="form_row">
            <input name="db_user" placeholder="Username">
        </div>
        <div class="form_row">
            <input name="db_password" type="password" placeholder="Password">
        </div>
        <div class="form_row">
            <input type="submit" value="submit">
        </div>
    </form>
</html>
