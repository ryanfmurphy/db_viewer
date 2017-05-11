<style>
            body {
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
                stroke: <?= $color ?>;
            }
<?php
    }
?>

</style>
