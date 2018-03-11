<?php
    $cur_view = 'cloud_view';
    include('../includes/init.php');

    function words_sql(
        $table_name = 'entity_view', $mode = 'tag', $where=null,
        $size_mult=20, $size_offset=1, $log_base=2.7,
        $ago=null, $interval=null, # 'week','month', etc
        $limit=null
    ) {
        $table_name_q = DbUtil::quote_ident($table_name);
        $size_mult = number_format($size_mult, 3);
        $size_offset = (int)$size_offset;
        $log_base = number_format($log_base, 3);
        $ago = ($ago !== null
                    ? (int)$ago
                    : null);
        $maybe_ago_where = 
                    ($ago !== null
                    && $interval
                        ? "
                        where time_added between
                            current_timestamp - '$ago $interval'::interval
                            and
                            current_timestamp - '".($ago-1)." $interval'::interval
                        " : "");
        $maybe_limit = ($limit !== null
                            ? "limit $limit"
                            : null);
        switch ($mode) {
            case 'tag': {
                return "
                    select
                        unnest(tags) \"text\",
                        $size_mult * log($log_base, count(*) + $size_offset) size
                        $maybe_ago_where
                    from $table_name_q
                    $where
                    group by text
                    order by size desc
                    $maybe_limit
                ";
            }
            case 'fulltext': {
                return "
                    select
                        unnest(tsv) \"text\",
                        -- #todo #fixme optionalize log_base
                        $size_mult * log($log_base, count(*) + $size_offset) size
                        -- $size_mult * (count(*) + $size_offset) size
                    from (
                        select *,
                        tsvector_to_array(
                            to_tsvector(
                                coalesce(name,'')
                                || ' ' || coalesce(txt,'')
                                || ' ' || coalesce(ref,'')
                                || ' ' || coalesce(array_to_string(tags::text[],' '),'')
                            )
                        ) as tsv
                        from $table_name_q
                        $maybe_ago_where
                    ) t
                    $where
                    -- where tsv @@ to_tsquery('filter_kw')
                    group by text
                    order by size desc
                    $maybe_limit
                ";
            }
            default: die("bad mode '$mode'");
        }
    }

    { # vars
        $table = isset($requestVars['table'])
                    ? $requestVars['table']
                    : 'entity_view';
        $mode = isset($requestVars['mode'])
                    ? $requestVars['mode']
                    : 'tag';
        $where = isset($requestVars['where'])
                    ? $requestVars['where']
                    : null;
        $size_mult = isset($requestVars['size_mult'])
                    ? $requestVars['size_mult']
                    : 20;
        $size_offset = isset($requestVars['size_mult'])
                        ? $requestVars['size_mult']
                        : 1;
        $log_base = isset($requestVars['log_base'])
                        ? $requestVars['log_base']
                        : 2.7;
        $ago = isset($requestVars['ago'])
                    ? $requestVars['ago']
                    : null;
        $interval = isset($requestVars['interval'])
                        ? $requestVars['interval']
                        : null;
        # move forward a time interval at a time, "animate"
        $play = isset($requestVars['play'])
                    ? (is_numeric($requestVars['play']) # seconds delay
                        ? (int)$requestVars['play']
                        : 1)
                    : null;
        $limit = isset($requestVars['limit'])
                    ? $requestVars['limit']
                    : null;

        { # get ignore_words
            $ignore_words = isset($requestVars['ignore_words'])
                                ? $requestVars['ignore_words']
                                : null;
            if (!is_array($ignore_words)
                && $ignore_words
            ) {
                $ignore_words = explode(',', $ignore_words);
            }
        }
    }

    $sql = words_sql(
        $table, $mode, $where,
        $size_mult, $size_offset, $log_base,
        $ago, $interval, $limit
    );
    $words = Db::sql($sql);

    # ignore_words
    if (is_array($ignore_words)
        && count($ignore_words)
    ) {
        foreach ($words as $key => $word) {
            if (in_array(strtolower($word['text']), $ignore_words)) {
                unset($words[$key]);
            }
        }
        $words = array_values($words); # rekey
    }

?>
<html>
    <head>
        <style>
<?php
    if ($background == 'dark') {
?>
            body {
                background: black;
            }
<?php
    }
?>
            text {
                cursor: pointer;
            }
            text:hover {
                stroke: yellow;
            }
        </style>
    </head>
    <body>
        <script src="../js/d3.js"></script>
        <script src="d3-cloud/d3.layout.cloud.js"></script>
        <script>

var cloud = d3.layout.cloud;

var fill = d3.scale.category20();

// between 0 and n-1
function rand_int(n) {
    return ~~(Math.random() * n);
}

function rand_range(min, max) {
    var n = max - min + 1
    return rand_int(n) + min;
}

var cloud_size = {
    width: 1400, // 3000,
    height: 700 // 1600
};
var layout = cloud()
    .size([cloud_size.width, cloud_size.height])
    .words( <?= json_encode($words) ?>)
    .padding(.5)
    .rotate(function() {
        //return (rand_int(3) - 1) * 30;
        return rand_range(-1, 1) * 45 - 10;
    })
    .font("Impact")
    .fontSize(function(d) { return d.size; })
    .spiral('archimedean')
    .on("end", draw);

layout.start();

function draw(words) {
  d3.select("body").append("svg")
      .attr("width", layout.size()[0])
      .attr("height", layout.size()[1])
    .append("g")
      .attr("transform", "translate(" + layout.size()[0] / 2 + "," + layout.size()[1] / 2 + ")")
    .selectAll("text")
      .data(words)
    .enter().append("text")
      .style("font-size", function(d) { return d.size + "px"; })
      .style("font-family", "Impact")
      .style("fill", function(d, i) { return fill(i); })
      .attr("text-anchor", "middle")
      .attr("transform", function(d) {
        return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
      })
      .text(function(d) { return d.text; });
}

// add click
var word_elems = document.getElementsByTagName('text');
for (var i=0; i < word_elems.length; i++) {
    var word_elem = word_elems[i];
    word_elem.addEventListener('click', function(e){
        var elem = e.target;
        var word = elem.textContent;
        var table_quoted = '<?= DbUtil::quote_ident($table) ?>'; // #todo #fixme js tpl
        var url = '<?= $table_view_uri ?>?sql=select * from '+table_quoted
                                                +' where tags @> $${'+word+'}$$'
                                                +' order by time_added desc'
                                                +' limit 100';
        window.open(url);
    });
}

<?php
    if ($play
        && $ago > 1
    ) {
        $query_vars = compact(
            'table', 'mode', 'where', 'size_mult', 'size_offset', 'log_base', 'interval', 'play'
        );
        $query = http_build_query($query_vars);
?>
setTimeout(function(){
    document.location = '?<?= $query ?>&ago=<?= $ago - 1 ?>';
}, <?= $play ?> * 1000);
<?php
    }
?>
        </script>
    </body>
</html>
