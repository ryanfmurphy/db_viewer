<?php
	require_once('db_config.php');

    if (!isset($background)) $background = 'light';

	if (!isset($inlineCss) || !$inlineCss) {
		header('Content-Type: text/css');
	}
?>
/*<style>*/
html {
    margin: 50px;
    padding: 0;
    font-family: sans-serif;
}
body{
    margin: 0px;
    padding: 0;
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
}
td, th {
    padding: .3em;
<?php
    if ($background=='dark') {
?>
    border: solid 1px #aaa; /* gray */
<?php
    }
    else {
?>
    border: solid 1px gray;
<?php
    }
?>
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
    background: black;
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
tr.shadowing th {
    border: solid 2px #aaa;
}

<?php
    $joinColors = array(
        1 => array(
            'handle' => 'rgba(225, 0, 0, .5)', #'#ff9999',
            'row' => 'rgba(150, 0, 0, .5)', #'#ffbbbb',
        ),
        2 => array(
            'handle' => 'rgba(0, 225, 0, .5)', # '#99ff99',
            'row' => 'rgba(0, 150, 0, .5)', # '#bbffbb',
        ),
        3 => array(
            'handle' => 'rgba(0, 0, 225, .5)', # '#9999ff',
            'row' => 'rgba(0, 0, 150, .5)', # '#bbbbff',
        ),
    );

    for ($level = 1; $level <= 3; $level++) {
?>
.level<?= $level ?>handle {
    background: <?= $joinColors[$level]['handle'] ?>;
}
.level<?= $level ?> {
    background: <?= $joinColors[$level]['row'] ?>;
}
<?php
    }
?>

/*
.level2handle {
    background: #99ff99;
}
.level2 {
    background: #bbffbb;
}

.level3handle {
    background: #9999ff;
}
.level3 {
    background: #bbbbff;
}
*/
