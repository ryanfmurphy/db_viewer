<?php
            { # links/scripts

                { # javascript
?>
    <script src="<?= $jquery_url ?>"></script>
    <script src="<?= $poprJsPath ?>popr/popr.js"></script>
<?php
                }

                { # popr css - either inline via include or linked
                  # main css style.css.php now being included from index

                    if ($cmp) {
?>
    <style>
        <?php include("$table_view_path/popr/popr.css"); ?>
    </style>
<?php
                    }
                    else {
                        #todo when running in a subdirectory,
                        # REQUEST_URI must have / at the end
                        # for these relative links to work
    /*link rel="stylesheet" type="text/css" href="<?= $cssPath ?>">*/
?>
    <link rel="stylesheet" type="text/css" href="popr/popr.css">
<?php
                    }
                }
            }
