<?php
            { # links/scripts

                { # javascript
                    #todo #fixme why does $popr_js_path not have a / after it? relative link?
?>
    <script src="<?= $jquery_url ?>"></script>
    <script src="<?= $popr_js_path ?>popr/popr.js"></script>
<?php
                }

                { # popr css - either inline via include or linked
                  # main css style.css.php now being included from index

                    if ($cmp) {
?>
    <style>
        <?php include("$trunk/table_view/popr/popr.css"); ?>
    </style>
<?php
                    }
                    else {
?>
    <link rel="stylesheet" type="text/css" href="popr/popr.css">
<?php
                    }
                }
            }
