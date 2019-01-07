
    // serializes a js object into a query string
    function obj2queryString(data) {
        { // vars
            var pairs = [];

            // add a key-value pair to the array
            var addPair = function(key, value, encode_key) {

                if (encode_key === undefined) encode_key = true;

                var scalar = false;
                if (value === null) {
                    scalar = true;
                }
                else if (typeof value === 'array') {
                    for (var i=0; i<value.length; i++) {
                        var full_key = key + '[' + i + ']';
                        addPair(full_key, value[i]);
                    }
                }
                else if (typeof value === 'object') {
                    for (var k in value) {
                        if (value.hasOwnProperty(k)) {
                            var full_key = key + '[' + encodeURIComponent(k) + ']';
                            addPair(full_key, value[k], false);
                        }
                    }
                }
                else {
                    scalar = true;
                }

                if (scalar) {
                    // Q. is this better/worse than pairs.push()?
                    pairs[ pairs.length ] =
                        encodeURIComponent( key ) + "=" +
                        encodeURIComponent(encodeValueForUri(value));
                }

            };
        }

        { // Serialize the form elements
            for (k in data) {
                if (data.hasOwnProperty(k)) {
                    addPair(k, data[k]);
                }
            }
        }

        { // Return the resulting serialization
            var join_str = pairs.join( "&" );
            return join_str;
        }
    }

    function encodeValueForUri(value) {
        return (value === null
                    ? "<?= $magic_null_value ?>"
                    : (value === true
                        ? '1'
                        : (value === false
                            ? ''
                            : value)));
    }

    // if "callback" AND "error" are provided,
    // use error as the error callback fn
    // else, just use "callback"
    function ajaxCallback(xhttp, callback, error) {
        if (error) {
            if (xhttp.readyState == 4) {
                if (xhttp.status == 200) {
                    callback(xhttp);
                }
                else {
                    error(xhttp);
                }
            }
        }
        else {
            callback(xhttp);
        }
    }

    // #todo #fixme use this in obj_editor/main.js
    function doAjax(method, url, data, success, error) {
        var xhttp = new XMLHttpRequest();

        var queryString = (typeof data == 'string'
                                ? data
                                : obj2queryString(data));

        xhttp.onreadystatechange = function() {
            return ajaxCallback(xhttp, success, error);
        }

        xhttp.open(method, url, true);
        xhttp.setRequestHeader(
            "Content-type",
            "application/x-www-form-urlencoded"
        );
        xhttp.send(queryString);
    }

