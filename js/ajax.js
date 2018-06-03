
    // serializes a js object into a query string
    function obj2queryString(data) {
        console.log('obj2queryString: data=',data);
        { // vars
            var pairs = [];

            // add a key-value pair to the array
            var addPair = function(key, value, encode_key) {

                if (encode_key === undefined) encode_key = true;

                console.log('addPair key',key,'value',value);
                var scalar = false;
                if (value === null) {
                    scalar = true;
                }
                else if (typeof value === 'array') {
                    console.log('  ahh an array');
                    for (var i=0; i<value.length; i++) {
                        var full_key = key + '[' + i + ']';
                        console.log('    full_key',full_key,'value',value[i]);
                        addPair(full_key, value[i]);
                    }
                }
                else if (typeof value === 'object') {
                    console.log('  ahh an obj');
                    for (var k in value) {
                        if (value.hasOwnProperty(k)) {
                            console.log('    k',k);
                            var full_key = key + '[' + encodeURIComponent(k) + ']';
                            console.log('    full_key',full_key,'value',value[k]);
                            addPair(full_key, value[k], false);
                        }
                    }
                }
                else {
                    scalar = true;
                }

                if (scalar) {
                    console.log('  a plain pair');
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
            console.log('joining pairs, pairs=',pairs);
            var join_str = pairs.join( "&" );
            console.log('  join_str',join_str);
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

