body {
    font-family: sans-serif;
    margin: 3em;
}

<?php 
        if ($background=='dark') {
            $input_bg = 'rgba(0,0,0,.5)';
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
            $input_bg = 'rgba(255,255,255,.5)';
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
.formInput textarea
{
    width: 30rem;
    display: inline-block;
    vertical-align: middle;
    padding: .2em;
}
.formInput textarea {
    padding: .4em;
    height: 7rem;
}

#whoami {
    font-size: 80%;
}

#table_header_top > *,
#prev_next_row_links > * {
    display: inline-block;
    vertical-align: middle;
    margin: .5rem;
}

#table_header_top > h1 {
    margin-left: 0;
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
        if ($background_image_url) {
?>
body {
    background-image: url(<?= $background_image_url ?>);
    background-position: center;
    background-repeat: repeat;
}
<?php
        }
?>


@media (max-width: 420px) {

    body {
        margin: 1em;
        margin-top: 0;
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

    #whoami, #view_all_link, #clear_fields_link {
        display: none;
        /*display: block;
        margin: .5em 0;*/
    }

    h1 {
        margin-top: 0;
        font-size: 20px;
    }
    h1 code { /* table name */
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

