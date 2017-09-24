#!/bin/sh
sql_cmds="$(php tree_fns.sql.php)"
echo "$sql_cmds" | psql
