<?php
            { # links/scripts

                { # javascript
?>
    <script src="<?= $jquery_url ?>"></script>
    <script src="<?= $poprJsPath ?>popr/popr.js"></script>
<?php
                }

                { # css - either inline via include or linked
                    if ($inlineCss && $cmp) {
                        $trunk = dirname(__DIR__); #todo this may be already defined, hence redundant
                        $cssPath =  "$trunk/style.css.php";
?>
    <style>
        <?php include($cssPath); ?>
        <?php include("$trunk/popr/popr.css"); ?>
    </style>
<?php
                    }
                    else {
?>
    <link rel="stylesheet" type="text/css" href="style.css.php">
    <link rel="stylesheet" type="text/css" href="popr/popr.css">
<?php
                    }
                }
            }
