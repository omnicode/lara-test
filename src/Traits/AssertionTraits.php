<?php
namespace LaraTest\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LaraRepo\Criteria\Criteria;
use LaraTools\Utility\LaraUtil;

trait AssertionTraits
{
    /**
     * @param $manyCriteria
     * @param $object
     * @param string $repository
     */
    protected function assertRepositoryAttributeContainsManyCriteria($manyCriteria, $object, $repository = 'repository')
    {
        if (!is_array($manyCriteria)) {
            $manyCriteria = [$manyCriteria];
        }
        foreach ($manyCriteria as $criteria) {
            $this->assertTrue($this->getProtectedAttributeOf($object, $repository)->getCriteria()->contains($criteria));
        }
    }

    /**
     * @param Criteria $criteria
     * @param $object
     * @param string $repository
     */
    protected function assertRepositoryAttributeContainsCriteria(Criteria $criteria, $object, $repository = 'repository')
    {
        $this->assertTrue($this->getProtectedAttributeOf($object, $repository)->getCriteria()->contains($criteria));
    }

    /**
     * @param Criteria $criteria
     * @param $object
     * @param string $repository
     */
    protected function assertRepositoryAttributeNotContainsCriteria(Criteria $criteria, $object, $repository = 'repository')
    {
        $this->assertFalse($this->getProtectedAttributeOf($object, $repository)->getCriteria()->contains($criteria));
    }

    /**
     * @param $class
     * @param $object
     * @param string $validator
     */
    protected function assertValidatorAttributeInstanceOf($class, $object, $validator = 'validator')
    {
        $this->assertInstanceOf($class, $this->getProtectedAttributeOf($object, $validator));
    }

    /**
     * @param $class
     * @param $object
     * @param string $repository
     */
    protected function assertRepositoryAttributeInstanceOf($class, $object, $repository = 'repository')
    {
        $this->assertInstanceOf($class, $this->getProtectedAttributeOf($object, $repository));
    }

    /**
     * @param $class
     * @param $object
     * @param $property
     */
    protected function assertProtectedAttributeInstanceOf($class, $object, $property)
    {
        $this->assertInstanceOf($class, $this->getProtectedAttributeOf($object, $property));
    }

    /**
     * @param Criteria $criteria
     * @param $repository
     */
    protected function assertRepositoryContainsCriteria(Criteria $criteria, $repository)
    {
        $this->assertTrue($repository->getCriteria()->contains($criteria));
    }

    /**
     * @param $object
     * @param $method
     */
    protected function expectCallMethod($object, $method)
    {
        $this->methodWillThrowException($method, $object);
        $this->expectException(\Exception::class);
    }

    /**
     * @param $object
     * @param $method
     * @param $arguments
     */
    protected function expectCallMethodWithArgument($object, $method, $arguments)
    {
        $this->methodWillThrowExceptionWithArgument($method, $object);
        $message = $this->getExceptionArgumentsMessage($arguments);
        $this->expectExceptionMessage($message);
    }
}