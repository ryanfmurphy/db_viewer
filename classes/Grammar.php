<?php

include('SimpleParser.php');

class Grammar {

    public $production_rules;
    public $simple_parser;

    function __construct($production_rules, $simple_parser = null) {
        $this->production_rules = $production_rules;
        $this->simple_parser = ($simple_parser === null
                                    ? new SimpleParser()
                                    : $simple_parser);
    }

    # e.g. rule_text = '<subject> <predicate>.' or '<item_with_comma>+ and <item>'
    function parse_rule_text($rule_text) {
        
    }

    function parse($input) {
        
    }

}

