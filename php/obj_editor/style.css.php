<?php
    $mobile_size = 420;
?>

body {
    font-family: sans-serif;
    <?php #box-sizing: border-box; /* e.g. makes the input and textarea exactly the same width */ ?>
}
.header_image {
    display: block;
    margin-left: auto;
    margin-right: auto;
    max-height: 250px;
}

@media (min-width: <?= $mobile_size-1 ?>px) {
    #main_container {
        /* center the form */
        width: 50rem;
        margin-left: auto;
        margin-right: auto;
    }
    input, select, textarea {
        font-family: inherit;
        font-size: 80%;
    }
}

#table_name {
    cursor: pointer;
}

<?php 
        $input_opacity = .7;
        if ($background=='dark') {
            $input_bg = "rgba(0,0,0,$input_opacity)";
?>
body {
    background: black;
    color: white;
}
input, textarea {
    background: <?= $input_bg ?>;
    color: white;
    border: solid 1px white;
}
select {
    -webkit-appearance: none;
    -moz-appearance: none;
    background: <?= $input_bg ?>;
    color: white;
    border: solid 1px white;
}
option {
    border: none;
}

a, .link {
    color: yellow;
    text-decoration: none;
    cursor: pointer;
}
a:hover, .link:hover {
    text-decoration: underline;
}

<?php
        }
        else {
            $input_bg = "rgba(255,255,255,$input_opacity)";
?>
input, textarea {
    background: <?= $input_bg ?>;
}
select {
    background: <?= $input_bg ?>;
}

a, .link {
    color: #0000EE;
    text-decoration: none;
    cursor: pointer;
}
a:hover, .link:hover {
    text-decoration: underline;
}

<?php
        }
?>

form#mainForm {
}

form#mainForm label {
    min-width: 8rem;
    display: inline-block;
    vertical-align: middle;
}
.formInput {
    margin: 2rem auto;
}
.formInput label {
    cursor: not-allowed; /* looks like delete */
}

.formInput input,
.formInput textarea,
.formInput select
{
    width: 30rem;
    display: inline-block;
    vertical-align: middle;
    padding: .2em;
    padding-top: .4em;
<?php
    // we have slightly bigger text that ends up slightly too high on FF
    if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'firefox') === false) {
?>
    padding-bottom: .4em;
<?php
    }
?>
}
.formInput select {
    width: 30.75rem; /* otherwise select is a little less wide for some reason */
}
.formInput textarea {
    padding: .4em;
    height: 7rem;
}

#whoami {
    font-size: 80%;
}

#table_header_top > * {
    display: inline-block;
    vertical-align: middle;
    margin: .5rem;
}

#prev_next_row_links > * {
    display: inline-block;
    vertical-align: middle;
    margin: .8rem;
}

#table_header_top > h1 {
    margin-left: 0;
}

input#selectTable {
    font-size: 20px;
    width: 7em;
}

#multipleTablesWarning {
    font-size: 80%;
    font-style: italic;
}

#addNewField {
    font-size: 150%;
    cursor: pointer;
}

#durationTimer, #doneTime {
    cursor: pointer;
}

.select_from_options {
    display: inline-block;
}

#nonexistent_table {
    margin: 2em auto 1em;
}

.select_from_options select {
    display: block;
    margin-bottom: .5em;
}
.select_from_options input {
    display: block;
    margin-top: .5em;
}

<?php
    include("$trunk/css/background_image.css.php");
    include("$trunk/css/popup.css");
?>

@media (max-width: <?= $mobile_size ?>px) {

    body {
<?php
        if ($background_image_method == 'css3:after') {
?>
        padding: 1em;
        padding-top: 0;
<?php
        }
        else {
?>
        margin: 1em;
        margin-top: 0;
<?php
        }
?>
        font-size: 16px;
    }

    .formInput {
        margin: .5em 0;
    }

    .formInput input,
    .formInput textarea
    {
        width: 96%;
        font-size: 20px;
    }

    .formInput textarea {
        padding: .15em; /* a bit less padding than on desktop */
    }

    /* #todo selects */

    input[type=submit] {
        font-size: 20px;
        display: block;
        margin: 1.25em auto;
    }

    form#mainForm label {
        font-size: 20px;
        display: block;
    }

    #addNewField {
        font-size: 32px;
        display: block;
        text-align: center;
        margin-left: auto;
        margin-right: auto;
    }

    #whoami {
        display: none;
    }

    h1 {
        margin-top: 0;
        font-size: 20px;
    }
    h1 code#table_name {
        font-size: 40px;
    }

    #prev_next_row_links {
        display: block;
        font-size: 150%;
        margin-top: -.5em;
        margin-bottom: -.2em;
    }
    #prev_next_row_links,
    #prev_row_link {
        margin-left: 0;
    }
    #prev_next_row_links,
    #next_row_link {
        margin-right: 0;
    }

    #its_a_table {
        display: none;
    }

}

