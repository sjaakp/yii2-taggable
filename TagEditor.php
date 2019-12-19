<?php
/**
 * sjaakp/yii2-taggable
 * ----------
 * Manage tags of ActiveRecords in PHP-framework Yii 2.x
 * Version 2.0
 * Copyright (c) 2019
 * Sjaak Priester, Amsterdam
 * MIT License
 * https://github.com/sjaakp/yii2-taggable
 * https://sjaakpriester.nl
 *
 * InputWidget for Yii 2.x
 *
 * Wrapper for jQuery tagEditor by PixaBay.
 * @link https://goodies.pixabay.com/jquery/tag-editor/demo.html
 */

namespace sjaakp\taggable;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * Class TagEditor
 * @package sjaakp\taggable
 */
class TagEditor extends InputWidget
{
    /**
     * @var array
     * Options for the underlying jQuery tagEditor
     */
    public $clientOptions = [];

    public function run()
    {
        $view = $this->getView();

        $asset = new TagEditorAsset();
        $asset->register($view);

        $id = $this->getId();
        $this->options['id'] = $id;

        $teOpts = count($this->clientOptions) ? Json::encode($this->clientOptions) : '';
        $view->registerJs("jQuery('#$id').tagEditor($teOpts);");

        return $this->hasModel() ? Html::activeTextInput($this->model, $this->attribute, $this->options)
            : Html::textInput($this->name, $this->value, $this->options);
    }

}