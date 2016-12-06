<?php
            { # links/scripts

                { # javascript
?>
    <script src="<?= $jquery_url ?>"></script>
    <script src="<?= $poprJsPath ?>popr/popr.js"></script>
<?php
                }

                { # css - either inline via include or linked
                    # actually, now being included within dynamic_style

                    #$cssPath = "style.css" . ($php_ext ? ".php" : "");
                    if (/*$inlineCss && */ $cmp) {
                        #$db_viewer_path = dirname(__DIR__); #todo this may be already defined, hence redundant
                        #$cssFullPath =  "$db_viewer_path/$cssPath";
?>
    <style>
        <?php #include($cssPath); ?>
        <?php include("$db_viewer_path/popr/popr.css"); ?>
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
