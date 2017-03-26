<?php

# general database config
#------------------------
$db_host = 'wha.tev.er.ip4';
$db_type = 'mysql'; # or 'pgsql' or maybe 'sqlite'
$db_user = 'mysql_user';
$db_password = 'mysql_password';
$db_name = 'mysql_database';
#$db_port;


# database schema layout and joining options
#-------------------------------------------

#$id_mode = 'table_id'; # or 'id_only';
#$pluralize_table_names = false;


# postgres-specific options
#--------------------------

#$search_path = 'schema1,schema2,etc';

# cosmetic / UI options
#----------------------

#$background = 'dark'; # vs 'light', the default

# background images URLs for different tables
# (can use a local path or an online image URL)
/*
$backgroundImages = array(
    'plant' => "/imgroot/plants/turmeric-plants.jpg",
    'book' => "/imgroot/books.jpg",
    'fallback image' => "/imgroot/plants/turmeric-plants.jpg",
);
*/

# special_ops: you can define custom links that do things
# to a row of a table within table_view
# (keyed by table)
/*
    $special_ops = [
        'core_verb' => [
            [   'name' => 'present',
                'changes' => [
                    'tense' => 'present',
                ],
            ],
            [   'name' => 'adj',
                'changes' => [
                    'part_of_speech' => 'adj',
                ],
            ],
        ],
    ];
*/
