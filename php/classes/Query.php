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

    public $orig_sql;
    public $body;

    public $select_fields;
    public $where_clauses;  # a string, or an array w where_clause's
        # a "where_clause" is a string
    public $order_by; # a string, e.g. "order by time_added desc"
        # can be null for no order_by
    public $limit; # an integer or null
    public $offset; # an integer or null

    public function __construct($sql, $infer_parts=true) {
        $limit_info = self::infer_limit_info_from_query($sql);
        $this->orig_sql = $sql;
        if ($infer_parts) {
            $this->body = $limit_info['query_wo_limit'];
            $this->limit = (!empty($limit_info['limit'])
                                ? $limit_info['limit']
                                : null);
            $this->offset = (!empty($limit_info['offset'])
                                ? $limit_info['offset']
                                : null);
        }
        else {
            $this->body = $sql;
        }
        #print_r($this);
        #die($this->sql_from_parts());
    }

    public function sql_from_parts() {
        $sql = $this->body;
        if ($this->limit) {
            $sql .= " limit $this->limit";
        }
        if ($this->offset) {
            $sql .= " offset $this->offset";
        }
        return $sql;
    }

    # if $sqlish is just a tablename, expand it to actual sql
    # return expanded sql if applicable, false otherwise
    public static function expand_tablename_into_query(
        $sqlish, $where_vars=array(), $select_fields=null,
        $order_by_limit=null, $strict_wheres=true
    ) {
        $sql_has_no_spaces = (strpos(trim($sqlish), ' ') === false);

        # allow sqlite directives like '.schema'
        # without accidentally expanding them into SELECT queries
        $starts_with_dot = preg_match('/^\./', $sqlish);

        $is_just_tablename = strlen($sqlish) > 0
                             && $sql_has_no_spaces
                             && !$starts_with_dot;
        
        if ($is_just_tablename) {
            $tablename = $sqlish; # (tablename has no quotes)

            if ($order_by_limit === null) {
                $order_by_limit = DbUtil::default_order_by_limit($tablename);
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

    public static function query_is_destructive($sql) {
        #$sql = 'select * from (select * from blah limit 50 offset 10) t limit 100 offset 50'; 
        $query = new Query($sql);
        #die(print_r($query,1));

        if (preg_match(
                "/\\b(INSERT|UPDATE|DROP|DELETE|CREATE|ALTER|TRUNCATE)\\b/i",
                $sql, $match
            )
        ) {
            $destrictive_kw = $match[1];
            return $destrictive_kw;
        }
        else {
            return false;
        }
    }

    public static function infer_table_from_query($query) {
        #todo improve inference - fix corner cases
        $quote_char = DbUtil::quote_char();
        if (preg_match(
                "/ \b from \s+ ((?:\w|\.|$quote_char)+) /ix",
                $query, $matches)
        ) {
            $table = $matches[1];
            return $table;
        }
    }

    public static function infer_limit_info_from_query($sql) {
        #todo improve inference - fix corner cases
        $regex = "/ ^

                    (?P<query_wo_limit>.*)

                    \s+

                    limit
                    \s+ (?P<limit>\d+)
                    (?:
                        \s+ offset
                        \s+ (?P<offset>\d+)
                    ) ?

                    ; ?

                    $
                    /six";

        $simple_parser = new SimpleParser();
        #$sql = $simple_parser->parse($sql);

        $result = array(
            'limit' => null,
            'offset' => null,
            'query_wo_limit' => $sql,
        );

        if ($simple_parser->preg_match_parse(
                $regex, $sql, $matches
            )
        ) {
            if (isset($matches['query_wo_limit'])) {
                $result['query_wo_limit'] = $matches['query_wo_limit'];
            }
            if (isset($matches['limit'])) {
                $result['limit'] = $matches['limit'];
            }
            if (isset($matches['offset'])) {
                $result['offset'] = $matches['offset'];
            }
        }
        else {
            self::log("
infer_limit_from_query: query didn't match regex.
query = '$sql'
"
);
        }
        return $result;
    }

    public static function log($msg) {
        error_log($msg, 3, __DIR__.'/error_log');
    }
}

