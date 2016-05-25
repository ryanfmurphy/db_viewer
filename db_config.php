<?php
# database config
$db_type = 'mysql';
#$db_host = 'wha.tev.er.ip4';
#$db_user = 'mysql_user';
#$db_password = 'mysql_password';
#$db_name = 'mysql_database';
#$db_port;

# database schema layout and joining options
$id_mode = 'table_id';

# postgres-specific options
$search_path = (DEV ? 'cough_dev' : 'cough');

# cosmetic / UI options
#$background = 'dark'; # vs 'light', the default
?>
