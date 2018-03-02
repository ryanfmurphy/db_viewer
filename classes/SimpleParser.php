<?php

include('lib/preg_replace_callback_offset.php');

class SimpleParser {

    public $line_comment = '--'; # set for SQL
    public $start_block_comment = '/*';
    public $end_block_comment = '*/';
    public $start_braces=['(','['];
    public $end_braces=[')',']'];
    public $str_quotes=["'",'"'];
    public $str_escape_mode = 'double'; # 'double' or 'backslash'

    function __construct($vars=[]) {
        foreach ($vars as $varname => $val) {
            $this->{$varname} = $val;
        }
    }

    public function blank_out_comments($txt) {
        return $this->blank_out_line_comments(
            self::blank_out_block_comments($txt, $this->start_block_comment, $this->end_block_comment),
            $this->line_comment
        );
    }

    public function blank_out_line_comments($txt) {
        $comment_start = preg_quote($this->line_comment,'/');
        return preg_replace_callback(
            '/'.$comment_start.'[^\n]*$/m',
            function($match){
                return str_repeat(' ',strlen($match[0]));
            },
            $txt
        );
    }

    public function blank_out_block_comments($txt) {
        $start = preg_quote($this->start_block_comment,'/');
        $end = preg_quote($this->end_block_comment,'/');
        $regex = "/$start.*$end/s";
        $txt = preg_replace_callback(
            $regex,
            function($match){
                return str_repeat(' ',strlen($match[0]));
            },
            $txt
        );
        return $txt;
    }

    #todo
    public function blank_out_inside_braces($txt) {
        for ($n=0; $n<strlen($txt); $n++) {
            $ch = $txt[$n];
            if (in_array($ch, $this->start_braces)) {
                
            }
            $start_brace = $this->start_braces[$n];
            $end_brace = $this->end_braces[$n];
        }
    }

    public function blank_out_strings($txt) {
        foreach ($this->str_quotes as $quote) {
            $txt = preg_replace_callback(
                "/".$quote."[^".$quote."]*".$quote."/",
                function($match){
                    return str_repeat(' ',strlen($match[0]));
                },
                $txt
            );
        }
        return $txt;
    }

    public function top_level($txt) {
        return $this->blank_out_comments($txt);
    }

}
