<?php
/**
 * MIT licence
 * Version 1.0
 * Sjaak Priester, Amsterdam 13-05-2015.
 *
 * Input widget for Yii 2.0
 *
 * Widget to enter, delete and interactively sort tags.
 *
 * Uses jQuery tagEditor.
 * @link http://goodies.pixabay.com/jquery/tag-editor/demo.html
 *
 */

namespace sjaakp\taggable;

use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\helpers\Json;

class TagEditor extends InputWidget {

    /**
     * @var array
     * The Javascript options of the jQuery tagEditor widget.
     */
    public $tagEditorOptions = [];

    public function run()   {
        $view = $this->getView();

        $asset = new TagEditorAsset();
        $asset->register($view);

        $id = $this->getId();
        $this->options['id'] = $id;

        $teOpts = count($this->tagEditorOptions) ? Json::encode($this->tagEditorOptions) : '';
        $view->registerJs("jQuery('#$id').tagEditor($teOpts);");

        return $this->hasModel() ? Html::activeTextInput($this->model, $this->attribute, $this->options)
            : Html::textInput($this->name, $this->value);
    }
}