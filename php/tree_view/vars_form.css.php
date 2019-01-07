        <style>
<?php
    $gray = '#888';
?>
        form {
            font-family: sans-serif;
            margin-left: auto;
            margin-right: auto;
            width: 30rem;
        }
        form#vars_form label {
            display: block;
            color: <?= $gray ?>;
        }
        form#vars_form div {
            margin: 1rem auto;
        }
        form#vars_form input[type=text] {
            width: 100%;
            font-size: 100%;
        }
        form#vars_form input[type=submit] {
            margin: 1em auto;
            /* HACK: for some reason centering wasn't happening */
            margin-left: 7em;
            font-size: 150%;
        }

        h1 {
            text-align: center;
        }
        h2 {
            font-weight: normal;
            margin-top: 1.5em;
        }
        h3 {
        }
        #add_relationship_link {
            cursor: pointer;
            color: blue;
            text-align: center;
            width: 100%;
        }
        #add_relationship_link:hover {
            text-decoration: underline;
        }
        #relationship_template {
            display: none;
        }
        form#vars_form div#rel_order_comment {
            font-size: 80%;
            margin-top: -1em;
            color: <?= $gray ?>;
        }

        form#vars_form .relationship {
            background: #f3f3f3;
            padding: .5em 1.2em;
            margin: 1.5em auto;
        }

        form#vars_form .relationship label {
            text-align: right;
            display: inline-block;
            width: 39%;
            vertical-align: baseline;
            font-size: 90%;
            margin-left: 3em;
        }
        form#vars_form .relationship input {
            display: inline-block;
            width: 49%;
            vertical-align: baseline;
            font-size: 90%;
        }

        .relationship_header {
            text-align: center;
            font-size: 80%;
            color: blue;
            cursor: pointer;
            margin-top: 2em;
        }
        .relationship_header.has_data {
            background: yellow;
        }
        .optional_fields {
            display: none;
        }
        .optional_fields.open {
            display: block;
        }
        </style>
