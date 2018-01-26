<?php

namespace LaraTest\Traits;

use function dd;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LaraTools\Utility\LaraUtil;

trait ModelRelationTestsTraits
{
    /**
     * @var int
     */
    private $hasOne = 1;

    /**
     * @var int
     */
    private $hasMany = 2;

    /**
     * @var int
     */
    private $belongsTo = 3;

    /**
     * @param $class
     * @param $relationName
     * @param $relationClass
     */
    public function assertHasOneRelation($class, $relationName, $relationClass)
    {
        $this->assertHasManyOrBelongsToRelation($class, $relationName, $relationClass, $this->hasOne);
    }

    /**
     * @param $class
     * @param $relationName
     * @param $relationClass
     */
    public function assertHasManyRelation($class, $relationName, $relationClass)
    {
        $this->assertHasManyOrBelongsToRelation($class, $relationName, $relationClass, $this->hasMany);
    }

    /**
     * @param $class
     * @param $relationName
     * @param $relationClass
     */
    public function assertBelongsToRelation($class, $relationName, $relationClass)
    {
        $this->assertHasManyOrBelongsToRelation($class, $relationName, $relationClass, $this->belongsTo);
    }

    /**
     * @param $class
     * @param $relationName
     * @param $relationClass
     * @param $relationType
     */
    public function assertHasManyOrBelongsToRelation($class, $relationName, $relationClass, $relationType)
    {

        $model = new $class;
        $relation = $model->{$relationName}();
        $relationModel = $relation->getRelated();
        $foreignKey = $relation->getForeignKey();

        $relationTypeName = HasOne::class;
        $relationTypeMessage = 'has one';
        $table = $relationModel->getTable();

        if ($relationType === $this->hasMany) {
            $relationTypeName = HasMany::class;
            $relationTypeMessage = 'has many';
        } elseif ($relationType === $this->belongsTo) {
            $relationTypeName = BelongsTo::class;
            $relationTypeMessage = 'belongs to';
            $table = $model->getTable();
        }

        $message = sprintf("%s model does not have %s %s relation method",
            $class, $relationName, $relationTypeMessage);
        $this->assertInstanceOf($relationTypeName, $relation, $message);

        $message = sprintf("%s model has many %s relation method does not have related with %s model",
            $class, $relationName, $relationClass);
        $this->assertInstanceOf($relationClass, $relationModel, $message);

        $message = sprintf("%s table does not have a %s column", $relationModel->getTable(), $foreignKey);
        $this->assertTrue(LaraUtil::hasColumn($table, $foreignKey), $message);

    }

    /**
     * @param $class
     * @param $relationName
     * @param $relationClass
     */
    public function assertBelongsToManyRelation($class, $relationName, $relationClass)
    {
        $model = new $class;
        $relation = $model->{$relationName}();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertInstanceOf($relationClass, $relation->getRelated());

        $table = $relation->getTable();
        $foreignKey = $relation->getForeignKey();
        $otherKey = $relation->getOtherKey();

        $message = sprintf("%s table does not have a %s column", $table, $foreignKey);
        $this->assertTrue(LaraUtil::hasColumn($table, $foreignKey), $message);

        $message = sprintf("%s table does not have a %s column", $table, $otherKey);
        $this->assertTrue(LaraUtil::hasColumn($table, $otherKey), $message);
    }

}
