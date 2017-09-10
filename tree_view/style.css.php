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
            }
            h1 span {
                font-weight: bold;
            }

            #alert {
                position: fixed;
                text-align: center;
                display: none;
                background: orange;
                padding: 1em;
                color: white;
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

            .node.selected text {
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
?>

            #popup {
                border: solid 1px #77c;
                background: #ccf;
                padding: .2em;
                font-size: 80%;
                white-space: nowrap;
            }
            #popup li {
                list-style-type: none;
                padding: .25em;
                padding-bottom: .2em;
                margin: .1em;
            }
            #popup li a {
                display: block;
                width: 100%;
                height: 100%;
                text-decoration: none;
                color: initial;
            }
            #popup li:hover {
                background: white;
                text-decoration: none;
                cursor: pointer;
            }

</style>
