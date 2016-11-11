<?php
    { # dynamic style / CSS choices

        { # main style include
            { # choose / setup background image if any
                $background_image_url = DbViewer::choose_background_image(
                    $tablename_no_quotes, $backgroundImages
                );
                include("$trunk/background_image_settings.php");
            }

            { # launch style from here so we have the accurate $background
?>
    <style>
<?php
                include("$trunk/db_viewer/style.css.php");
?>
    </style>
<?php
            }

            if ($background_image_url) {
?>
    <style>
    body {
        /*background-color: black;
        color: white;*/
        background-image: url(<?= $background_image_url ?>);
        background-position: center;
        background-repeat: repeat;
    }
    td,th, table, textarea {
<?php
                if ($background == 'dark') {
?>
        border: solid 1px white;
<?php
                }
                else {
?>
        border: solid 1px black;
<?php
                }
?>
    }
    table, textarea {
<?php
                if ($background == 'dark') {
?>
        color: white;
        background: rgba(0,0,0,.5);
<?php
                }
                else {
?>
        color: black;
        background: rgba(255,255,255,.4);
<?php
                }
?>
    }
    a {
        color: #88f;
    }

    .link_nocolor {
    }
    </style>
<?php
            }
        }

        { # join colors

            $isDarkBackground = (isset($background)
                                 && $background == 'dark');

            if ($background_image_url
                || $isDarkBackground
            ) {
                {
                    $handleColor = 225;
                    $rowColor = 150;
                }
                $joinColors = array(
                    1 => array(
                        'handle' => array($handleColor, 0, 0, .5), #'#ff9999',
                        'row' => array($rowColor, 0, 0, .5), #'#ffbbbb',
                    ),
                    2 => array(
                        'handle' => array(0, $handleColor, 0, .5), # '#99ff99',
                        'row' => array(0, $rowColor, 0, .5), # '#bbffbb',
                    ),
                    3 => array(
                        'handle' => array(0, 0, $handleColor, .5), # '#9999ff',
                        'row' => array(0, 0, $rowColor, .5), # '#bbbbff',
                    ),
                );
            }
            else {
                {
                    $handleColor = 153;
                    $rowColor = 187;
                }
                $joinColors = array(
                    1 => array(
                        'handle' => array(255, $handleColor, $handleColor, 1),
                        'row' => array(255, $rowColor, $rowColor, 1),
                    ),
                    2 => array(
                        'handle' => array($handleColor, 255, $handleColor, 1),
                        'row' => array($rowColor, 255, $rowColor, 1),
                    ),
                    3 => array(
                        'handle' => array($handleColor, $handleColor, 255, 1),
                        'row' => array($rowColor, $rowColor, 255, 1),
                    ),
                );
            }
?>

    <style>
<?php
            # template out rgba() color in CSS based on array
            function rgbaColor($colorArray, $mult=1) {
                if ($mult !== 1) {
                    foreach ($colorArray as $i => &$color) {
                        if ($i < 3) { // alpha stays the same
                            $color *= $mult;
                            $color &= (int)(round($color));
                        }
                    }
                }
                $colorStr = implode(",", $colorArray);
                return "rgba($colorStr)";
            }

            $oddRowDarkness = ($isDarkBackground
                                ? .4
                                : .9);
            for ($level = 1; $level <= 3; $level++) {
?>
    .join_color_<?= $level ?>_handle {
        background: <?= rgbaColor($joinColors[$level]['handle']) ?>;
    }
    .join_color_<?= $level ?> {
        background: <?= rgbaColor($joinColors[$level]['row']) ?>;
    }

    /* darker for odd-row's */
    .odd-row .join_color_<?= $level ?>_handle {
        background: <?= rgbaColor($joinColors[$level]['handle'], $oddRowDarkness) ?>;
    }
    .odd-row .join_color_<?= $level ?> {
        background: <?= rgbaColor($joinColors[$level]['row'], $oddRowDarkness) ?>;
    }
    .odd-row {
<?php
                if ($isDarkBackground) {
?>
        background: rgba(220,220,220, <?= $oddRowDarkness ?>); /* #todo adjust for dark bg & backgroundImage */
<?php
                }
                else {
?>
        background: rgb(220,220,220);
<?php
                }
?>
    }

<?php
            }
?>
        </style>

<?php
        }
    }
