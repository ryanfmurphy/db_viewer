<?php
class Note extends ArrayObject {

    public static function get_notes_for_obj($primary_key) {
        $note_tbl = Config::$config['obj_editor_note_table'];
        $note_tbl_q = Db::quote_ident($note_tbl);
        $sql = "
            select * from $note_tbl_q
            where parent_ids @> '{ $primary_key }'
            order by time_added desc
        ";
        return $notes = Db::sql($sql);
    }

    function format_content_txt() {
        return $this['txt']; #todo markdown
    }

    function print_byline() {
        $content = $this;
?>
                <div class="byline">
                    Posted
<?php
        if (!empty($content['author'])) {
?>
                    by <?= $content['author'] ?>
<?php
        }
?>
                    on <?= $this->date_posted() ?>
                </div>
<?php
    }

    function date_posted() {
        $note = $this;
        return date_format(
            date_create($note['time_added']),
            "M d, Y"
        );
    }

    function render($just_posted_comment=false) {
        $note = $this;
        $txt = $note->format_content_txt();
?>
                <div class="note"
<?php
        if ($just_posted_comment) {
?>
                        style="border: solid 1px blue"
<?php
        }
?>
                >
<?php
        if (!empty($note['name'])) {
?>
                    <h3><?= $note['name'] ?></h3>
<?php
        }
?>
                    <?= $txt ?>
                    <div class="deets">
                        <?= $note->print_byline() ?>
                    </div>
                </div>
<?php
    }

}
