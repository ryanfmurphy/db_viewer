<?php
    # gets included by db_viewer/dynamic_style.php
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
    {
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
<?php
    if ($background=='dark') {
?>
    color: white;
<?php
    }
    else {
?>
    color: black;
<?php
    }
?>
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

<?php
    if (file_exists('custom_css.php')) {
        include('custom_css.php');
    }
?>

