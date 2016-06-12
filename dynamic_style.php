<?php
            { # dynamic style / CSS choices

                { # choose background image if any
                    if (!isset($backgroundImages)) $backgroundImages = array();

                    $backgroundImageUrl = (isset($inferred_table)
                                           && isset($backgroundImages[$inferred_table])
                                                ? $backgroundImages[$inferred_table]
                                                : (isset($backgroundImages['fallback image'])
                                                    ? $backgroundImages['fallback image']
                                                    : null)
                                          );

                    $hasBackgroundImage = (isset($backgroundImageUrl)
                                        && $backgroundImageUrl);

                    if ($hasBackgroundImage) {
?>
    <style>
    body {
        background-color: black;
        background-image: url(<?= $backgroundImageUrl ?>);
        background-position: center;
        background-repeat: no-repeat;
        color: white;
    }
    td,th {
        border: solid 1px white;
    }
    table, textarea {
        color: white;
        background: rgba(100,100,100,.5);
        border: solid 1px white;
    }
    a {
        color: #88f;
    }
    </style>
<?php
                    }
                }

                { # join colors
                    if ($hasBackgroundImage) {
                        $joinColors = array(
                            1 => array(
                                'handle' => array(225, 0, 0, .5), #'#ff9999',
                                'row' => array(150, 0, 0, .5), #'#ffbbbb',
                            ),
                            2 => array(
                                'handle' => array(0, 225, 0, .5), # '#99ff99',
                                'row' => array(0, 150, 0, .5), # '#bbffbb',
                            ),
                            3 => array(
                                'handle' => array(0, 0, 225, .5), # '#9999ff',
                                'row' => array(0, 0, 150, .5), # '#bbbbff',
                            ),
                        );
                    }
                    else {
                        $joinColors = array(
                            1 => array(
                                'handle' => array(255, 153, 153, 1),
                                'row' => array(255, 187, 187, 1),
                            ),
                            2 => array(
                                'handle' => array(153, 255, 153, 1),
                                'row' => array(187, 255, 187, 1),
                            ),
                            3 => array(
                                'handle' => array(153, 153, 255, 1),
                                'row' => array(187, 187, 255, 1),
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

                    $oddRowDarkness = .9;
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
        background: rgb(220,220,220); /* #todo adjust for dark bg & backgroundImage */
    }

<?php
                    }
?>
        </style>

<?php
                }
            }
