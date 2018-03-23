<?php

include('SimpleParser.php');

class Query {

    /*
        Query class - loose Query builder/parser

        Goals:

        - model a query by having slots for its parts
          and then having a sql() function at the end
          to build the full SQL text

        - be able to take in a query as pure text
          and deconstruct it part by part until it's
          "deeply populated" - limit/order by is separated out
    */

    public $body;

    public $select_fields;
    public $where_clauses;  # a string, or an array w where_clause's
        # a "where_clause" is a string
    public $order_by_limit; # a string, or an array w keys 'order_by' and 'limit'
        # a "order_by" is a string
        # a "limit" is a string

    public function sql() {
        return $this->body;
    }

    public function top_level() {
        return SimpleParser::top_level($this->body);
    }

    public function parse_limit() {
    }

    # if $sqlish is just a tablename, expand it to actual sql
    # return expanded sql if applicable, false otherwise
    public static function expand_tablename_into_query(
        $sqlish, $where_vars=array(), $select_fields=null,
        $order_by_limit=null, $strict_wheres=true
    ) {
        $sql_has_no_spaces = (strpos(trim($sqlish), ' ') === false);

        # allow sqlite directives like '.schema' without accidentally expanding them into SELECT queries
        $starts_with_dot = preg_match('/^\./', $sqlish);
        if (strlen($sqlish) > 0
            && $sql_has_no_spaces
            && !$starts_with_dot
        ) {
            # (tablename has no quotes)
            $tablename = $sqlish;

            if ($order_by_limit === null) {
                $order_by_limit = DbUtil::default_order_by_limit(
                                                        $tablename);
            }

            # optionally filter out archived rows
            $is_archived_field = DbUtil::is_archived_field($tablename);
            if ($is_archived_field) {
                $where_vars[$is_archived_field] = Db::false_exp();
            }

            return Db::build_select_sql(
                $tablename, $where_vars, $select_fields,
                $order_by_limit, $strict_wheres
            );
        }
        else {
            return false;
        }
    }

}

