<?php
/**
 * MIT licence
 * Version 1.0.2
 * Sjaak Priester, Amsterdam 13-05-2015.
 *
 * ActiveRecord Behavior for Yii 2.0
 *
 * Makes an ActiveRecord taggable.
 *
 * TaggableBehavior links an ActiveRecord to one or more Tag ActiveRecords via a junction table (many-to-many).
 *
 */


namespace sjaakp\taggable;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class TaggableBehavior extends Behavior {

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
     * @var string|boolean
     * - if string: the count attribute of the Tag class, holds the number of associated ActiveRecords;
     * - if false: no such attribute; associated ActiveRecords are not counted in Tag class
     */
    public $countAttribute = 'count';

    /**
     * @var string
     * Table name of the junction table
     */
    public $junctionTable;

    /**
     * @var string
     * The name of the foreign key attribute in $junctionTable that holds the primary key of the Tag.
     */
    public $tagKeyAttribute = 'tag_id';

    /**
     * @var string
     * The name of the foreign key attribute in $junctionTable that holds the primary key of the owner ActiveRecord.
     */
    public $modelKeyAttribute = 'model_id';

    /**
     * @var string|boolean
     * - if string: name of the attribute in $junctionTable where the sorting order is maintained;
     * - if false: no such attribute; Tags can not be sorted
     *
     */
    public $orderAttribute = 'ord';

    /**
     * @var string
     * Text (or HTML) delimiter between items returned from getTagLinks.
     */
    public $linkGlue = ', ';

    /**
     * @var array
     * Options for the links returned from getTagLinks.
     */
    public $linkOptions = [];

    /**
     * @var string
     * Character used as delimiter in TagEditor.
     */
    public $editorDelimiter = ',';

    /**
     * @return ActiveQuery
     * ActiveQuery to query for associated Tags.
     */
    public function getTags()   {
        // Cannot use hasMany()->viaTable() here because the result cannot be ordered.
        /**
         * @var $owner ActiveRecord
         */
        $owner = $this->owner;
        $ownerPk = $owner->primaryKey;

        /**
         * @var $tc ActiveRecord
         */
        $tc = $this->tagClass;
        $tpk = $tc::tableName() . '.' . current($tc::primaryKey());

        $tkn = new Expression($owner->getDb()->quoteSql("[[j]].{{{$this->tagKeyAttribute}}}"));

        return $tc::find()->innerJoin($this->junctionTable . ' j', [ $tpk => $tkn ])
            ->where(['j.' . $this->modelKeyAttribute => $ownerPk])
            ->orderBy('j.' . $this->orderAttribute);
    }


    /**
     * @return string
     * HTML of associated Tag-names, linked to Tag views.
     */
    public function getTagLinks()   {
        $links = [];

        /**
         * @var $tagModel TagBehavior
         */
        foreach ($this->getTags()->all() as $tagModel)    {
            $links[] = $tagModel->getLink($this->linkOptions);
        }

        return implode($this->linkGlue, $links);
    }

    /**
     * @return string
     * Get value for TagEditor
     */
    public function getEditorTags()   {
        return implode($this->editorDelimiter, ArrayHelper::getColumn($this->getTags()->all(), $this->nameAttribute));
    }

    protected $_tagList = '';

    /**
     * @param $tagList
     * Set value from TagEditor
     */
    public function setEditorTags($tagList)   {
        $this->_tagList = $tagList;
    }

    protected function updateCounters($keys, $incr) {
        if ($this->countAttribute)  {
            /**
             * @var $tc ActiveRecord
             */
            $tc = $this->tagClass;
            $pk = current($tc::primaryKey());

            $tc::updateAllCounters([$this->countAttribute => $incr], ['in', $pk, $keys]);
        }
    }


    public function events()    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function afterSave($event)   {
        /**
         * @var $owner ActiveRecord
         */
        $owner = $this->owner;
        $ownerPk = $owner->primaryKey;

        /**
         * @var $tc ActiveRecord
         */
        $tc = $this->tagClass;
        $tagPkName = current($tc::primaryKey());

        $db = $owner->getDb();

        // old tag models indexed by name
        $oldTagModels = ArrayHelper::index($this->getTags()->all(), $this->nameAttribute);
        $newTags = empty($this->_tagList) ? [] : explode($this->editorDelimiter, $this->_tagList);
        $newTagModels = [];

        foreach ($newTags as $newTag)   {
            if (isset($oldTagModels[$newTag]))  {       // new tag is in old tag models as well
                $newTagModels[$newTag] = $oldTagModels[$newTag];
            }
            else    {
                /**
                 * @var $tag ActiveRecord
                 */
                $tag = $tc::findOne([$this->nameAttribute => $newTag]); // is new tag in database?
                if (! $tag)   {                                         // no, create
                    $tag = new $tc();
                    $tag->setAttribute($this->nameAttribute, $newTag);
                    $tag->save();
                }
                $newTagModels[$newTag] = $tag;
            }
        }

        $removeTagKeys = ArrayHelper::getColumn(array_diff_key($oldTagModels, $newTagModels), $tagPkName);
        $addTagKeys = ArrayHelper::getColumn(array_diff_key($newTagModels, $oldTagModels), $tagPkName);

        if (count($removeTagKeys))  {
            $this->updateCounters($removeTagKeys, -1);
            $db->createCommand()->delete($this->junctionTable, [
                $this->tagKeyAttribute => $removeTagKeys,
                $this->modelKeyAttribute => $ownerPk
            ])->execute();
        }

        if (count($addTagKeys))  {
            $this->updateCounters($addTagKeys, 1);
            $rows = [];
            foreach ($addTagKeys as $addTagKey) {
                $rows[] = [ $addTagKey, $ownerPk ];
            }
            $db->createCommand()->batchInsert($this->junctionTable, [$this->tagKeyAttribute, $this->modelKeyAttribute], $rows)
                ->execute();
        }

        if ($this->orderAttribute)  {
            $iOrder = 0;
            while (current($newTagModels)        // skip identical tags; order attribute is already set
                && current($oldTagModels)
                && key($newTagModels) == key($oldTagModels))       {
                $iOrder++;
                next($newTagModels);
                next($oldTagModels);
            }

            while ($currentTag = current($newTagModels))    {
                $db->createCommand()->update($this->junctionTable, [ $this->orderAttribute => $iOrder ],
                    [ $this->tagKeyAttribute => $currentTag->primaryKey, $this->modelKeyAttribute => $ownerPk ])->execute();

                $iOrder++;
                next($newTagModels);
            }
        }
    }

    public function beforeDelete($event)  {
        if ($this->countAttribute)  {
            /**
             * @var $tc ActiveRecord
             */
            $tc = $this->tagClass;
            $pk = current($tc::primaryKey());

            $keys = ArrayHelper::getColumn($this->getTags()->all(), $pk);

            $tc::updateAllCounters([$this->countAttribute => -1], ['in', $pk, $keys]);
        }

        /**
         * @var $owner ActiveRecord
         */
        $owner = $this->owner;
        $db = $owner->getDb();

        $db->createCommand()->delete($this->junctionTable, [
            $this->modelKeyAttribute => $owner->primaryKey
        ])->execute();

    }
}