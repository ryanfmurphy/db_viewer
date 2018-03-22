<style>
<?php
        /*
        $background_image_url = TableView::choose_background_image(
            $root_table, $backgroundImages
        );
        if ($background_image_url) {
?>
            body {
                background-image: url(<?= $background_image_url ?>);
                background-position: center;
                background-repeat: repeat;
            }
<?php
        }
        */

        $background_color = 'white'; #todo #fixme
?>
            body {
                background: <?= $background_color ?>;
                font-family: sans-serif;
            }
            h1 {
                color: gray;
                font-weight: normal;
                text-align: center;
                margin-bottom: .3em;
            }
            h1 span {
                font-weight: bold;
            }
            #subheader {
                text-align: center;
                font-size: 80%;
            }

            .node {
                cursor: pointer;
            }

            .node circle {
                fill: #fff;
                stroke: steelblue;
                stroke-width: 1.5px;
            }

            .node text {
                font: 10px sans-serif;
            }

            .node.selected text,
            .node.focus_of_popup text
            {
                font-weight: bold;
            }

            .link {
                fill: none;
                stroke: #ddd;
                stroke-width: 1.5px;
            }

            #edit_vars_link {
                text-decoration: none;
            }
            #edit_vars_link:hover {
                text-decoration: underline;
            }

            #summary {
                margin-left: .15em;
            }

            #settings {
                position: fixed;
                background: rgba(128,128,128,.4);
                padding: .3em;
            }
            #settings > a {
                display: inline;
                font-size: 80%;
                margin-right: .7em;
            }
            #settings #alert {
                text-align: center;
                display: none;
                background: orange;
                padding: 1em;
                margin-bottom: 1em;
                color: white;
            }

            #settings form label {
                display: inline-block;
                min-width: 6em;
            }
            #settings form input[type=text] {
                /*float: right;*/
            }
            .small_copy {
                font-size: 80%;
                margin-bottom: .2em;
            }


<?php
    foreach ($table_info as $table => $info) {
        $color = $info['color'];
?>
            .<?= $table ?>_tbl_color {
                color: <?= $color ?>;
                fill: <?= $color ?>;
            }
<?php
    }

    include("$trunk/css/popup.css");
?>

</style>
