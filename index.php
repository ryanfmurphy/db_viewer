<?php
    header("HTTP/1.1 302 Moved");
    $maybe_query_vars = (count($_GET) > 0
                            ? '?' . http_build_query($_GET)
                            : '');
    header("Location: table_view/index.php$maybe_query_vars"); 
?>
