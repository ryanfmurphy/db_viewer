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

    public function line_comment_regex() {
        $start_line_comment = preg_quote($this->line_comment,'/');
        return $start_line_comment.'[^\n]*$';
    }

    public function block_comment_regex() {
        $start_block_comment = preg_quote($this->start_block_comment,'/');
        $end_block_comment = preg_quote($this->end_block_comment,'/');
        return "$start_block_comment.*?$end_block_comment";

    }

    public function parse($txt, $blank_out_level=1, $blank_out_strings=false) {
        $line_comment_regex = $this->line_comment_regex();
        $block_comment_regex = $this->block_comment_regex();

        $regex = "$line_comment_regex|$block_comment_regex";
        foreach ($this->string_regexes() as $string_regex) {
            $regex .= "|$string_regex";
        }
        $brace_chars = array_merge($this->start_braces, $this->end_braces);
        $regex .= "|[".preg_quote(implode('',$brace_chars))."]";
        $regex = "/$regex/ms";
        #echo "regex = $regex\n";

        $nest_level = 0;
        $level_offsets = []; # keyed by level [start_idx,end_idx] pairs

        $result = preg_replace_callback_offset(
            $regex,

            function($match) use (&$nest_level, &$level_offsets, $blank_out_strings) {
                $match_txt = $match[0][0];
                $offset = $match[0][1];

                # braces
                if (in_array($match_txt, $this->start_braces)) {
                    $nest_level++;
                    $level_offsets[$nest_level][0] = $offset;
                    return $match_txt;
                }
                elseif (in_array($match_txt, $this->end_braces)) {
                    $level_offsets[$nest_level][1] = $offset;
                    $nest_level--;
                    return $match_txt;
                }

                foreach ($match as $key => $match_details) {
                    $match_txt_inner = $match_details[0];
                    $match_offset = $match_details[1];
                    $no_match = ($match_offset == -1);

                    if ($key === 0 || $no_match) continue;

                    # if this is a string match: "abc" 'abc'
                    if (strpos($key, 'str_content') === 0) {
                        if ($blank_out_strings) {
                            $quote_char = $match_txt[0];
                            $blankness = str_repeat(' ',strlen($match_txt_inner));
                            return $quote_char . $blankness . $quote_char; # if there's any match,just pass-thru
                        }
                        else {
                            return $match_txt; # if there's any match,just pass-thru
                        }
                    }
                }
                return str_repeat(' ',strlen($match_txt));
            },

            $txt
        );

        if ($blank_out_level
            && isset($level_offsets[$blank_out_level])
        ) {
            $level_span = $level_offsets[$blank_out_level];
            #echo "level_span = ".print_r($level_span)."\n";
            $level_start = $level_span[0]+1; # leave open brace
            $level_len = $level_span[1] - $level_start;
            $result = substr_replace(
                $result,
                str_repeat(' ', $level_len),
                $level_start, $level_len
            );
        }

        return $result;
    }

    private function string_regexes() {
        $regexes = [];
        $num = 0;
        foreach ($this->str_quotes as $quote) {
            if ($this->str_escape_mode == 'backslash') { # most common way to escape quotes
                $literal_backslash = '\\\\';
                $continue_str = "[^".$literal_backslash.$quote."]*";
                $escaped_quote = $literal_backslash.$quote;
            }
            elseif ($this->str_escape_mode == 'double') { # for SQL syntax
                $continue_str = "[^".$quote."]*";
                $escaped_quote = "$quote$quote";
            }
            elseif ($this->str_escape_mode == 'both') {
                # allow both 'double' and 'backslash' escape modes
                # to coexist, i.e. how MySQL does it
                $literal_backslash = '\\\\';
                $continue_str = "[^".$literal_backslash.$quote."]*";
                $escaped_quote = "(?:$literal_backslash$quote|$quote$quote)";
            }

            $escape_stuff = $this->str_escape_mode
                                ? "(?:$escaped_quote$continue_str)*"
                                : '';

            # build up overall regex for a 'string' / "string" / etc, with any escape logic
            $regexes[] = $quote."(?P<str_content$num>$continue_str$escape_stuff)".$quote;
            $num++;
        }
        return $regexes;
    }

    /*
    # separate contents of strings from the outside
    # returns [$txt, $strs] where $txt is the cleaned txt without the strs
    # and $strs is an array of offsets => str_contents
    public function separate_strings($txt) {
        $strs = [];
        foreach ($this->str_quotes as $quote) {
            $txt = preg_replace_callback_offset(
               "/".$quote."([^".$quote."]*)".$quote."/",
                function($match) use ($quote,&$strs) {
                    $inside_txt = $match[1][0];
                    $inside_pos = $match[1][1];
                    $strs[$inside_pos] = $inside_txt;
                    return $quote . str_repeat(' ',strlen($inside_txt)) . $quote;
                },
                $txt,
                PREG_OFFSET_CAPTURE
            );
        }
        ksort($strs); # make sure offsets are in order
        return [$txt, $strs];
    }

    # opposite of separate_strings()
    # strs is offset=>txt pairs to be subbed into txt
    public function sub_strs_into_txt($txt, $strs) {
        $i = 0;
        $ret = '';
        foreach ($strs as $offset => $str) {
            $len_to_offset = $offset - $i;
            $ret .= substr($txt, $i, $len_to_offset);
            $i += $len_to_offset;

            $ret .= $str;
            $i += strlen($str);
        }

        # add any piece of str leftover at the end
        $remaining_len = strlen($txt) - $i;
        $ret .= substr($txt, $i, $remaining_len);

        return $ret;
    }
    */

    public function top_level($txt) {
        #$txt = $this->blank_out_strings($txt);
        $txt = $this->blank_out_comments($txt);
        return $txt;
    }

}
