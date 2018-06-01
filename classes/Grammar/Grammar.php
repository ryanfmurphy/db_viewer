<?php

include(__DIR__.'/../SimpleParser.php'); #todo #fixme

class Grammar {

    public $terminal_regexes; # should be an array
    public $production_rules; # should be an array
    public $simple_parser;    # type SimpleParser

    public $var_regex = '/<(\\w+)>/'; # regex for <variables> found in rule_text etc
                                      # could be terminal or non-terminal

    function __construct($terminal_regexes, $production_rules, $simple_parser = null) {
        $this->terminal_regexes = $terminal_regexes; # should be an array
        $this->production_rules = $production_rules; # should be an array
        $this->simple_parser = ($simple_parser === null
                                    ? new SimpleParser()
                                    : $simple_parser);
    }

    # e.g. rule_text = '<subject> <predicate>.' or '<item_with_comma>+ and <item>'
    function parse_rule_text($rule_text) {
        return preg_replace_callback_offset(
            $this->var_regex,
            function($match) {
                $symbol_name = $match[1][0];
                if (isset($this->terminal_regexes[$symbol_name])) {
                    return '('.$this->terminal_regexes[$symbol_name].')'; # () for clean separation / nesting within regex
                }
                else {
                    $full_match_txt = $match[0][0];
                    return $full_match_txt; # leave unmodified
                }
            },
            $rule_text
        );
    }

    function parse($input) {
        
    }

}

