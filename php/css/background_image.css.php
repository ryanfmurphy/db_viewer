<?php
        # background_image.css.php
        # ------------------------
        # gets included by styles.css.php in various views

        if ($background_image_method == 'css3:after') {
?>
body {
    padding: 3em;
    margin: 0;
}
<?php
        }
        else {
?>
body {
    margin: 3em;
}
<?php
        }

        if ($background_image_url) {
            switch ($background_image_method) {
                case 'normal': {
                    # old-skool normal way to do background image
?>
body {
    background-image: url(<?= $background_image_url ?>);
    background-position: center;
    background-repeat: repeat;
}
<?php
                    break;
                }
                case 'css3:after': {
                    # cool css3 way using an :after element,
                    # enabling opacity etc on background image
?>
body {
    position: relative;
    /*
    background-image: url(<?= $background_image_url ?>);
    background-position: center;
    background-repeat: repeat;
    */
}
body:after {
    content : "";
    display: block;
    position: absolute;
    top: 0;
    left: 0;
    background-image: url(<?= $background_image_url ?>); 
    width: 100%;
    height: 100%;
    opacity : <?= $background_image_opacity ?>;
    z-index: -1;

<?php
                    # optional options #todo #fixme
                    if (true) {
?>
    background-size: 100%;
<?php
                    }
                    else {
?>
    background-attachment: fixed;
    background-size: cover;
<?php
                    }
?>
}
<?php
                    break;
                }
                default: {
                    die("invalid background_image_method '$background_image_method'");
                }
            }
        }
?>
