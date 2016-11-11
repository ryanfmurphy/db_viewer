<?php

class Curl {

    public static function post($url, $vars, $username=NULL, $password=NULL, $headers=NULL) {
        /*
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($vars));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        # collect the $result and close the curl handle
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        */
        return Curl::do_curl($url, null, $vars, $username, $password, false, $headers);
    }

    public static function get($url, $vars=NULL, $username=NULL, $password=NULL, $headers=NULL) {
        return Curl::do_curl($url, $vars, null, $username, $password, false, $headers);
    }

    public static function do_curl($url, $get_vars=null, $post_vars=NULL, $username=NULL, $password=NULL, $do_PUT_request=false, $headers) {
        $ch = curl_init();

        { # set the options
            # get
            if (is_array($get_vars)) {
                $query_string = http_build_query($get_vars);
                if ($query_string) {
                    $url .= "?$query_string";
                }
            }

            # url
            curl_setopt($ch, CURLOPT_URL, $url);

            # post
            if (is_array($post_vars)) {
                if ($do_PUT_request) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                }
                else {
                    curl_setopt($ch, CURLOPT_POST, 1);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_vars));
            }

            # auth
            if ($username) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, "$username:$password"); 
            }

            if ($headers) { # headers
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $fileSizeLimit = 10000000; #todo move this
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_INFILESIZE, $fileSizeLimit);
            }

            # return val
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        }

        # collect the $result and close the curl handle
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}

