<?php
    { # dynamic style / CSS choices

        { # main style

            { # choose / setup background image if any
                $background_image_url =
                    TableView::choose_background_image(
                        $tablename_no_quotes, $backgroundImages
                    );
                include("$trunk/includes/background_image_settings.php");
            }

            { # main CSS rules
?>
    <style>
    html {
        margin: 50px;
        padding: 0;
        font-family: sans-serif;
    }
    body{
        margin: 0px;
        padding: 0;
        padding-bottom: 1em;
<?php
                if ($background=='dark') {
?>
    background: black;
    color: white;
<?php
                }
?>
    }
    table {
        border-spacing: 0;
        border-collapse: collapse;
        margin: 1.2rem auto;
    }
    td, th {
        padding: .3em;
    }

    input[type=text] {
        font-size: 100%;
        padding: .2em;
    }

    /* border color */
    td, th, textarea, input[type=text] {
        background: none;
<?php
                if ($background=='dark') {
?>
        border: solid 1px #777; /* gray */
<?php
                }
                else {
?>
        border: solid 1px #bbb;
<?php
                }
?>
    }

    td .wide_col {
        width: 20em;
    }

    textarea, input {
        font-family: sans-serif;
    }
    textarea {
        font-size: 1em;
        margin-bottom: .5em;
    }
    input[type=submit] {
        margin-top: 1em;
        margin-bottom: 1.5em;
        padding: .2em;
    }

    #query_header {
        margin-bottom: .2em;
    }
    #limit_warning {
        margin-top: .2em;
        font-size: 70%;
    }

    textarea, input[type=text] {
<?php
                if ($background=='dark') {
?>
        background: rgba(0,0,0,.5);
        color: white;
<?php
                }
?>
    }

    textarea {
        width: 90%;
        height: 5em;
        padding: .6em;
    }

    .shadowing,
    tr.shadowing td,
    tr.shadowing th
    {
        border: solid 2px #aaa;
    }

    ul {
        /* reduce big indent */
        padding-left: 1.2em;
        padding-right: .5em;
    }
    li {
        margin-top: .3em;
        margin-bottom: .3em;
    }

<?php
                if ($background=='dark') {
?>
    a {
        color: #88f;
    }
<?php
                }
?>


    .page_links {
        padding: 1em;
    }
    .num_per_page {
        font-size: 80%;
    }

    .id_field {
        cursor: pointer;
    }
    .uuid_field {
        font-size: 30%;
    }

    .row_edit_link {
        font-size: 80%;
    }

    /*
    .id_field, .time_field {
        color: blue;
    }
    */

    .link_nocolor {
        text-decoration: none;
        color: inherit;
    }
    .link_nocolor:hover {
        text-decoration: underline;
    }

    .action_cell ul > li {
        list-style-type: none;
    }
    .action_cell ul {
        padding-left: .5em;
        padding-right: .5em;
        font-size: 90%;
        line-height: .7;
<?php
                if ($background=='dark') {
?>
        color: white;
<?php
                }
?>
    }

    #help_legend {
        font-size: 60%;
        padding: 1em;
    }
    #help_legend.help_open {
        background-color: #444;
        color: #ddd;
    }
    #help_legend h3, #help_legend h4, #help_legend h5 {
        font-weight: normal;
    }
    #help_legend.help_open h3,
    #help_legend.help_open h4,
    #help_legend.help_open h5 {
        color: white;
    }
    #help_legend h3 {
        font-size: 100%;
    }
    #help_legend h4 {
        font-size: 150%;
    }
    #help_legend h5 {
        font-size: 130%;
        margin-bottom: 0;
    }
    #help_legend_header {
        cursor: pointer;
    }
    #help_legend_header:hover {
        text-decoration: underline;
    }

    .bold_border_above {
        border-top: solid 5px blue;
    }

<?php
                if (file_exists('custom_css.php')) {
                    include('custom_css.php');
                }
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
