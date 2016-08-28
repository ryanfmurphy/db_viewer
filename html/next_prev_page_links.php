<?php
                if ($limit_info['limit']) {
?>
            <span class="page_links">
                <a  class="page_prev"
                    href="<?= DbUtil::link_to_prev_page($limit_info) ?>"
                >
                    &lt;
                </a>
                <span class="num_per_page">
                    <?= $limit_info['limit'] ?> per page
                </span>
                <a  class="page_next"
                    href="<?= DbUtil::link_to_next_page($limit_info) ?>"
                >
                    &gt;
                </a>
            </span>
<?php
                }
?>
