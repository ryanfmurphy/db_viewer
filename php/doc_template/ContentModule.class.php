<?php

class ContentModule {

    public static function getIdField() {
        $id_mode = Config::$config['id_mode'];
        return DbUtil::get_primary_key_field('content_module');
    }

    public function getId() {
        $id_mode = Config::$config['id_mode'];
        $field_name = self::getIdField();
        return $this->$field_name;
    }

    #public static $contentModuleClass = 'ContentModule';

    public static function get1($id) {
        $id = Db::quote($id);
        $id_field = self::getIdField();
        $is_archived_field = Config::$config['is_archived_field'];
        $sql = "
            select *
            from content_module
            where $id_field = $id
            ".($is_archived_field
                    ? "and $is_archived_field = 0"
                    : '')."
        ";
        $rows = Db::sql($sql, PDO::FETCH_CLASS, get_called_class());
        #todo maybe factor into Db::get1?
        if (count($rows) > 0) {
            return $rows[0];
        }
        else {
            do_log('ContentModule::get1() could not get obj');
            return false;
        }
    }

    #todo #fixme - ensure that txt is always saved with no more than 2 \n's at once
    #              1 \n   => <br>
    #              2 \n's => <p>...</p>

    public function getParagraphsTxt($vars=null) {
        $paragraphs = $this->getParagraphs();

        ob_start();
        foreach ($paragraphs as $paragraph) {
?>
        <p>
            <?= $paragraph ?>
        </p>
<?php
        }
        return ob_get_clean();
    }

    public function getTxt($vars=null) {
        return (is_array($vars)
                    ? self::subMustacheVars($vars, $this->txt)
                    : $this->txt);
    }

    public function getParagraphs($vars=null) {
        $txt = $this->getTxt($vars);
        return explode("\n\n", $txt);
    }

    #todo #fixme don't assume doc_template type 'email'
    public function render($vars, $headerLev=1, $editable=false) {
        #$doc_template = DocTemplate::get1($this->doc_template_id);
        switch ($this->type) {
            case 'include_element':
                return $this->renderIncludeElement(
                    $vars, null, $editable
                );

            default:
                #return $this;
                return $this->renderTextBlock(
                    $vars, $headerLev, $editable
                );
        }
    }

    # for type = include_element
    public function renderIncludeElement($vars, $unused=null, $editable=false) {
        $element2include = $this->txt;
        $content = DocTplElements::$element2include($vars);
        if ($editable) {
            ob_start();
?>
        <div class="include_element editable"
             content_module_id="<?= $this->getId() ?>"
        >
            <?= $content ?>
        </div>
<?php
            return ob_get_clean();
        }
        else {
            return $content;
        }
    }

    public static function subMustacheVars($vars, $body) {
        return preg_replace_callback(
            "/
                {{\s*
                    (\w+)
                \s*}}
            /x",
            function($match) use ($vars) {
                $varname = $match[1];
                return $vars[$varname];
            }, 
            $body
        );
    }

    # can be overloaded by subclasses
    # on a template-by-template basis
    public function renderTextBlock($vars, $headerLev=2, $editable=false) {
        #echo $headerTag;
        $title = self::subMustacheVars(
                    $vars, $this->name
                 );
        $subtitle = self::subMustacheVars(
                        $vars, $this->subtitle
                    );

        $textBlockId = $this->getId();

        $editableAttrs = function($extraClasses=null) use ($editable, $textBlockId) {
            return $editable
                        ? " class=\"editable $extraClasses\"
                            content_module_id=\"$textBlockId\"
                        "
                        : null;
        };

        # (does subMustacheVars)
        $paragraphs = $this->getParagraphs($vars);

        { ob_start();
?>
    <div <?= $editableAttrs() ?>>
<?php
            if ($title) {
?>
        <h<?= $headerLev ?>>
            <?= $title ?>
        </h<?= $headerLev ?>>
<?php
            }
?>
        <?= $this->getParagraphsTxt($vars) ?>
    </div>
<?php
            return ob_get_clean();
        }
    }

}

?>
