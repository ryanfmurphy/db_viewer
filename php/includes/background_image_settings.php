<?php
    # background_image_settings if applicable
    if (isset(
        $background_image_settings[$background_image_url]
    )) {
        $img_settings = $background_image_settings[
                            $background_image_url
                        ];

        if (isset($img_settings['background'])) {
            $background = $img_settings['background'];
        }

        unset($img_settings);
    }

    $opacity_when_dark = .5;
    $opacity_when_light = .6;
