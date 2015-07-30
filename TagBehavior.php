<?php
/**
 * MIT licence
 * Version 1.0
 * Sjaak Priester, Amsterdam 13-05-2015.
 *
 * ActiveRecord Behavior for Yii 2.0
 *
 * Makes an ActiveRecord behave like a Tag.
 *
 * TagBehavior links an ActiveRecord to one or more Taggable ActiveRecords via a junction table (many-to-many).
 *
 */


namespace sjaakp\taggable;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\Html;

class TagBehavior extends Behavior  {

    /**
     * @var string
     * The name attribute of the Tag class.
     */
    public $nameAttribute = 'name';

    /**
     * @var array|string
     * Table name of the junction table, or array of multiple junction tables
     */
    public $junctionTable;

    /**
     * @var string
     * The name of the attribute in $junctionTable that holds the primary key of the Tag.
     */
    public $tagKeyAttribute = 'tag_id';

    /**
     * @var string
     * Route part of the link address returned in getLink().
     */
    public $linkRoute = 'tag/view';

    /**
     * @param $options array: link options
     * @return string
     * HTML of link to view of Tag (or any other destination, dependent of $linkRoute).
     */
    public function getLink($options = [])   {
        /**
         * @var $owner ActiveRecord
         */
        $owner = $this->owner;
        $tpk = current($owner::primaryKey());

        return Html::a($owner->getAttribute($this->nameAttribute), [ $this->linkRoute, $tpk => $owner->primaryKey], $options);
    }

    public function events()    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function beforeDelete($event)  {
        /**
         * @var $owner ActiveRecord
         */
        $owner = $this->owner;
        $db = $owner->getDb();

        if (is_string($this->junctionTable))    {
            $this->junctionTable = [ $this->junctionTable ];
        }

        foreach ($this->junctionTable as $jt)   {
            $db->createCommand()->delete($this->junctionTable, [
                $this->tagKeyAttribute => $owner->primaryKey
            ])->execute();
        }
    }
}