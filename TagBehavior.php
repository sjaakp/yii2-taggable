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
 * Behavior for Yii 2.x ActiveRecord
 *
 * Lets record act as tag.
 */

namespace sjaakp\taggable;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class TagBehavior
 * @package sjaakp\taggable
 */
class TagBehavior extends Behavior
{
    /**
     * @var string name of the junction table. Should be set.
     */
    public $junctionTable;

    /**
     * @var string column names in the junction table
     */
    public $tagKeyColumn = 'tag_id';
    public $modelKeyColumn = 'model_id';

    /**
     * @var ActiveRecord class name
     */
    public $modelClass;

    /**
     * @var array model class where() condition, format like QueryInterface::where()
     * @link https://www.yiiframework.com/doc/api/2.0/yii-db-queryinterface#where()-detail
     */
    public $condition = [];

    public function getModels()
    {
        /** @var $owner ActiveRecord */
        $owner = $this->owner;
        $tagPk = $owner->primaryKey()[0];  // tag pk name

        $mc = $this->modelClass;
        $modelPk = $mc::primaryKey()[0];  // model pk name

        return $owner->hasMany($this->modelClass, [ $modelPk => $this->modelKeyColumn ])->where($this->condition)
            ->viaTable($this->junctionTable, [ $this->tagKeyColumn => $tagPk ]);
    }

    /**
     * @return int
     * @throws \yii\db\Exception
     */
    public function getModelCount()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $db = $owner->db;
        if (empty($this->condition))    {   // if no condition, just count junctions (more efficient, I guess)
            $tagPk = $owner->primaryKey;  // value

            $sql = $db->quoteSql("SELECT COUNT(*) FROM {{%{$this->junctionTable}}} WHERE [[{$this->tagKeyColumn}]] = $tagPk");
            return $db->createCommand($sql)->queryScalar();
        }
        return $this->getModels()->count('*', $db);
    }

    /**
     * Remove tag's model links from junction table
     * @throws \yii\db\Exception
     * @return int number of rows affected
     */
    protected function removeModels()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;

        return $owner->db->createCommand()->delete($this->junctionTable, [
            $this->tagKeyColumn => $owner->primaryKey   // value
        ])->execute();
    }

    /**
     * @inheritDoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @param $event
     * @throws \yii\db\Exception
     */
    public function beforeDelete($event)
    {
        $this->removeModels();
    }
}
