<?php

function md5_to_rgb($md5) {
    return '#'.substr($md5,0,6);
}

function name_to_rgb($name) {
    return md5_to_rgb(md5($name));
}

