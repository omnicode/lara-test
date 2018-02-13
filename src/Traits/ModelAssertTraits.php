<?php

namespace LaraTest\Traits;

use function dd;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LaraTools\Utility\LaraUtil;

trait ModelAssertTraits
{
    use TableAssertTraits;
    /**
     * @var int
     */
    private $hasOne = 'has_one';

    /**
     * @var int
     */
    private $hasMany = 'has_many';

    /**
     * @var int
     */
    private $belongsTo = 'belongs_to';

    /**
     * @param $class
     * @param $method
     * @param $related
     * @param null $foreignKey
     * @param null $localKey
     * @param string $message
     */
    public function assertModelHasOneRelation($class, $method, $related, $foreignKey = null,
                                              $localKey = null,  $message = '')
    { 
        $this->checkHas_OrBelongsTo($class, $method, $related, $this->hasOne, $message);
    }

    /**
     * @param $class
     * @param $method
     * @param $related
     * @param null $foreignKey
     * @param null $localKey
     * @param string $message
     */
    public function assertModelHasManyRelation($class, $method, $related, $foreignKey = null, 
                                               $localKey = null, $message = '')
    {
        $this->checkHas_OrBelongsTo($class, $method, $related, $this->hasMany, $message);
    }

    /**
     * This test also check key columns has in table
     *
     * @param $class
     * @param $method
     * @param $related
     * @param null $foreignKey
     * @param null $otherKey
     * @param null $relation
     * @param string $message
     */
    public function assertModelBelongsToRelation($class, $method, $related, $foreignKey = null,
                                                 $otherKey = null, $relation = null, $message = '')
    {
        $model  = $this->getModel($class);
        $relationType = $model->{$method}();
        $relationModel = $relationType->getRelated();

        if (!is_null($foreignKey)) {
            $this->assertEquals($foreignKey, $relationType->getForeignKey());
        } else {
            $foreignKey = $relationType->getForeignKey();
        }

        if (!is_null($otherKey)) {
            $this->assertEquals($otherKey, $relationType->getOtherKey());
        } else {
            $otherKey = $relationType->getOtherKey();
        }

        $table = $model->getTable();
        $this->assertTableHasColumns($table, [$foreignKey, $otherKey]);

        if (!is_null($relation)) {
            $this->assertEquals($relation, $relationType->getRelation());
        }

        if (empty($message)) {
            $message = sprintf("'%s' model does not have '%s' belongs to relation method", $class, $method);
        }
        $this->assertInstanceOf(BelongsTo::class, $relationType, $message);

        $message = sprintf("'%s' model belongs to '%s' relation method does not related with '%s' model",
            $class, $method, $related);
        $this->assertInstanceOf($related, $relationModel, $message);

    }

    /**
     * @param $class
     * @param $method
     * @param $related
     * @param string $message
     */
    public function assertModelBelongsToManyRelation($class, $method, $related, $table = null, 
                                                 $foreignKey = null, $otherKey = null, $relation = null, $message = '')
    {
        $model  = $this->getModel($class);
        $relation = $model->{$method}();


        if (empty($message)) {
            $message = sprintf("'%s' model does not have '%s' belongs to many relation method",
                $class, $method);
        }
        $this->assertInstanceOf(BelongsToMany::class, $relation, $message);

        $message = sprintf("'%s' model belongs to many '%s' relation method does not related with '%s' model",
            $class, $method, $related);

        $this->assertInstanceOf($related, $relation->getRelated(), $message);

        $table = $relation->getTable();
        $foreignKey = $relation->getForeignKey();
        $otherKey = $relation->getOtherKey();

        $message = sprintf("%s table does not have a %s column", $table, $foreignKey);
        $this->assertTrue(LaraUtil::hasColumn($table, $foreignKey), $message);

        $message = sprintf("%s table does not have a %s column", $table, $otherKey);
        $this->assertTrue(LaraUtil::hasColumn($table, $otherKey), $message);
    }

    /**
     * @param $class
     * @return mixed
     */
    protected function getModel($class)
    {
        return is_string($class)? new $class() : $class;
    }

    /**
     * @param $class
     * @param $method
     * @param $relationClass
     * @param $relationType
     * @param string $message
     */
    protected function checkHas_OrBelongsTo($class, $method, $relationClass, $relationType, $message = '')
    {
        $model  = $this->getModel($class);
        $relation = $model->{$method}();
        $relationModel = $relation->getRelated();
        $foreignKey = $relation->getForeignKey();

        $relationTypeName = HasOne::class;
        $table = $relationModel->getTable();

        if ($relationType === $this->hasMany) {
            $relationTypeName = HasMany::class;
        } elseif ($relationType === $this->belongsTo) {
            $relationTypeName = BelongsTo::class;
            $table = $model->getTable();
        }

        $relationTypeMessage = str_replace('_', ' ', $relationType);
        if (empty($message)) {
            $message = sprintf("%s model does not have %s %s relation method",
                $class, $method, $relationTypeMessage);
        }
        $this->assertInstanceOf($relationTypeName, $relation, $message);

        $message = sprintf("'%s' model %s '%s' relation method does not related with '%s' model",
            $class, $relationTypeMessage, $method, $relationClass);
        $this->assertInstanceOf($relationClass, $relationModel, $message);

        $message = sprintf("%s table does not have a %s column", $relationModel->getTable(), last(explode('.', $foreignKey)));
        $this->assertTrue(LaraUtil::hasColumn($table, $foreignKey), $message);
    }

}
