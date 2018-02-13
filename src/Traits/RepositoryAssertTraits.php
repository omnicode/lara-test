<?php

namespace LaraTest\Traits;

use LaraRepo\Criteria\Criteria;

trait RepositoryAssertTraits
{
    /**
     * @param $manyCriteria
     * @param $object
     * @param string $repository
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \ReflectionException
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
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \ReflectionException
     */
    protected function assertRepositoryAttributeContainsCriteria(Criteria $criteria, $object, $repository = 'repository')
    {
        $this->assertTrue($this->getProtectedAttributeOf($object, $repository)->getCriteria()->contains($criteria));
    }

    /**
     * @param Criteria $criteria
     * @param $object
     * @param string $repository
     * @throws \PHPUnit_Framework_AssertionFailedError
     * @throws \ReflectionException
     */
    protected function assertRepositoryAttributeNotContainsCriteria(Criteria $criteria, $object, $repository = 'repository')
    {
        $this->assertFalse($this->getProtectedAttributeOf($object, $repository)->getCriteria()->contains($criteria));
    }

    /**
     * @param $class
     * @param $object
     * @param string $validator
     * @throws \ReflectionException
     */
    protected function assertValidatorAttributeInstanceOf($class, $object, $validator = 'validator')
    {
        $this->assertInstanceOf($class, $this->getProtectedAttributeOf($object, $validator));
    }

    /**
     * @param $class
     * @param $object
     * @param string $repository
     * @throws \ReflectionException
     */
    protected function assertRepositoryAttributeInstanceOf($class, $object, $repository = 'repository')
    {
        $this->assertInstanceOf($class, $this->getProtectedAttributeOf($object, $repository));
    }

    /**
     * @param $class
     * @param $object
     * @param $property
     * @throws \ReflectionException
     */
    protected function assertProtectedAttributeInstanceOf($class, $object, $property)
    {
        $this->assertInstanceOf($class, $this->getProtectedAttributeOf($object, $property));
    }

    /**
     * @param Criteria $criteria
     * @param $repository
     * @throws \PHPUnit_Framework_AssertionFailedError
     */
    protected function assertRepositoryContainsCriteria(Criteria $criteria, $repository)
    {
        $this->assertTrue($repository->getCriteria()->contains($criteria));
    }


}
