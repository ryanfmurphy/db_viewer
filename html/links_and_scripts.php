<?php
            { # links/scripts
?>
    <!-- load jQuery -->
    <script src="<?= $jquery_url ?>"></script>
    <script src="<?= $poprJsPath ?>popr/popr.js"></script>

<?php
                if ($inlineCss && $cmp) {
		$cssPath = __DIR__ . "/style.css.php";
?>
    <style>
		<?php include($cssPath); ?>
        <?php include('popr/popr.css'); ?>
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
