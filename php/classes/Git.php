<?php
class Git {

    public static function log($log) {
        $log_output = shell_exec('git log --format=raw');
        return self::parse_raw_log_output($log_output);
    }

    public static function parse_raw_log_output($log_output) {
        $new = preg_match_all("/^.*$/m", $log_output);
        $commits = [$new];
        return $commits;    
    }

}
