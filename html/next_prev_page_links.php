<?php
                if ($limit_info['limit']) {
?>
            <span id="page_links">
                <a  id="page_prev"
                    href="<?= DbUtil::link_to_prev_page($limit_info) ?>"
                >
                    &lt;
                </a>
                <span id="num_per_page">
                    <?= $limit_info['limit'] ?> per page
                </span>
                <a  id="page_prev"
                    href="<?= DbUtil::link_to_next_page($limit_info) ?>"
                >
                    &gt;
                </a>
            </span>
<?php
                }
?>
