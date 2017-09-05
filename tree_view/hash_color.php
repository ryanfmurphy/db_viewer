<?php

function md5_to_rgb($md5) {
    return '#'.substr($md5,0,6);
}

function name_to_rgb($name) {
    $node_color_by_table = Config::$config['node_color_by_table'];
    if (isset($node_color_by_table[$name])) {
        return $node_color_by_table[$name];
    }
    else {
        return md5_to_rgb(md5($name));
    }
}

