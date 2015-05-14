<?php
/**
 * MIT licence
 * Version 1.0
 * Sjaak Priester, Amsterdam 13-05-2015.
 *
 * Action for Yii 2.0
 *
 * Handles autocomplete requests from TagEditor.
 *
 */

namespace sjaakp\taggable;

use yii\base\Action;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class TagSuggestAction extends Action {

    /**
     * @var string
     * The (full) class name of the Tag class.
     */
    public $tagClass;

    /**
     * @var string
     * The name attribute of the Tag class.
     */
    public $nameAttribute = 'name';

    /**
     * @var string
     * The pattern used for searching suggestions.
     * Default searches for Tag names beginning with the search term.
     * Change this to '%{term}%' to search Tag names with the search term in any position.
     */
    public $like = '{term}%';

    public function run($term = '')  {

        /**
         * @var $tc ActiveRecord
         */
        $tc = $this->tagClass;
        $r = ArrayHelper::getColumn($tc::find()->where(['like', $this->nameAttribute,
            strtr($this->like, ['{term}' => $term]), false])->all(),
            $this->nameAttribute);

        return Json::encode($r);
    }
}