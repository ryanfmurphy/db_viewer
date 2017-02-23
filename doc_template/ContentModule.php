<?php

class ContentModule {

    public function getId() {
        $id_mode = Config::$config['id_mode'];
        $field_name = DbUtil::get_primary_key_field($id_mode, 'content_module');
        return $this->$field_name;
    }

    #todo #fixme - ensure that txt is always saved with no more than 2 \n's at once
    #              1 \n   => <br>
    #              2 \n's => <p>...</p>

    public function getParagraphs($vars=null) {
        $txt = (is_array($vars)
                    ? self::subMustacheVars($vars, $this->txt)
                    : $this->txt);
        return explode("\n\n", $txt);
    }

    #todo #fixme don't assume doc_template type 'email'
    public function render($vars, $headerTag='<h1>', $editable=false) {
        #$doc_template = DocTemplate::get1($this->doc_template_id);
        switch ($this->type) {
            case 'include_element':
                return $this->renderIncludeElement(
                    $vars, null, $editable
                );

            default:
                #return $this;
                return $this->renderTextBlock(
                    $vars, $headerTag, $editable
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
    public function renderTextBlock($vars, $headerTag='<h1>', $editable=false) {
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
        <h2>
            <?= $title ?>
        </h2>
<?php
            }

            foreach ($paragraphs as $paragraph) {
?>
        <p>
            <?= $paragraph ?>
        </p>
<?php
            }
?>
    </div>
<?php
            return ob_get_clean();
        }
    }

}

?>
