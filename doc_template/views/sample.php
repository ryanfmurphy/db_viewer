<?php
    $cur_view = 'sample.php';
    require_once('../../includes/init.php');
    require_once('../DocTemplate.php');
    require_once('../ContentModule.php');
    if (!isset($editable)) {
        $editable = true;
    }
    $varnames = array('editable');
 
    $docTemplate = DocTemplate::getByName('RyanFMurphy');
    $contentModules = $docTemplate->getContentModule_Collection();
?>

<?php
    $title = count($contentModules > 0)
                ? $contentModules[0]->name
                : null; 
?>
<!DOCTYPE html>
<html>
    <head>
<?php
    if ($editable) {
?>
        <!-- <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script> #todo #fixme -->
        <script src="/db_viewer/table_view/js/jquery-1.12.3.js"></script>
        <?= DocTemplate::editableCss() ?>
<?php
    }
?>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<?php
    if ($title) {
?>
        <title><?= $title ?></title>
<?php
    }
?>
        <style>
        body {
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        #header {
            width: 100%;
        }
        #header_image {
            max-width: 200px;
            text-align: center;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        </style>
    </head>
    <body>
        <div id="header">
            <img src="http://127.0.0.1:89/imgroot/chandelier.jpg"
                 id="header_image"
            />
        </div>
        <div id="content">
            <?= $docTemplate->renderContentModules(
                    compact($varnames), $editable #, 'main_body'
                )
            ?>
        </div>
<?php
    # js has to go at the end
    if ($editable) {
?>
    <?= DocTemplate::editableJs() ?>
<?php
    }
?>
    </body>
</html>

<?php

if ($editable) {
    DocTemplate::editableJs();
}

?>
