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
}

