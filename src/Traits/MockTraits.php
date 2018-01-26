<?php

namespace LaraTest\Traits;

use Illuminate\Support\Collection;
use LaraValidation\CoreValidator;

trait MockTraits
{
    /**
     * @param $class
     * @param string $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockValidator($class, $methods = '')
    {
        $mockBuilder = $this->getMockBuilder($class)
            ->setConstructorArgs([new CoreValidator()]);

        if ($this->getMethodsBy($methods)) {
            $mockBuilder->setMethods($methods);
        }
        return $mockBuilder->getMock();
    }

    /**
     * @param $class
     * @param string $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockRepository($class, $methods = '')
    {
        $mockBuilder = $this->getMockBuilder($class)
            ->setConstructorArgs([$this->container, $this->collection]);

        if ($this->getMethodsBy($methods)) {
            $mockBuilder->setMethods($methods);
        } else {
            $mockBuilder->setMethods(null);
        }
        return $mockBuilder->getMock();
    }

    /**
     * @param $method
     * @param $object
     * @return mixed
     */
    protected function expectsOnceMethod($method, $object)
    {
        return $object->expects($this->once())->method($method);
    }

    /***
     * @param $value
     * @param $method
     * @param $object
     */
    protected function methodWillReturn($value, $method, $object)
    {
        $this->expectsOnceMethod($method, $object)
            ->will($this->returnCallback(function () use ($value) {
                return $value;
            }));
    }

    /**
     * @param $method
     * @param $object
     */
    protected function methodWillReturnEmptyArray($method, $object)
    {
        $this->methodWillReturn([], $method, $object);
    }

    /***
     * @param $object
     * @param $method
     */
    protected function methodWillReturnTrue($method, $object)
    {
        $this->methodWillReturn(true, $method, $object);
    }

    /**
     * @param $methods
     * @param $object
     */
    protected function methodsWillReturnTrue($methods, $object)
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }
        foreach ($methods as $method) {
            $this->methodWillReturn(true, $method, $object);
        }
    }

    /***
     * @param $object
     * @param $method
     */
    protected function methodWillReturnFalse($method, $object)
    {
        $this->methodWillReturn(false, $method, $object);
    }

    /**
     * @param $method
     * @param $mockObject
     */
    protected function methodWillThrowException($method, $mockObject)
    {
        $mockObject->method($method)->will($this->throwException(new \Exception('exception')));
    }

    /**
     * @param $method
     * @param $object
     * @param int $argumentNumber
     * @param string $dataKey
     */
    protected function methodWillThrowExceptionWithArgument($method, $object, $argumentNumber = 0, $dataKey = 'status')
    {
        $this->expectsOnceMethod($method, $object)
            ->will($this->returnCallback(function ($argument) use ($argumentNumber, $dataKey) {
                $arguments = func_get_args();
                $argument = $arguments[$argumentNumber];

                if (is_array($argument)) {
                    if (!empty($argument[$dataKey])) {
                        $message = $this->getExceptionArrayKeyExistsMessage($dataKey, '', $argument);
                    } else {
                        $message = $this->getExceptionArrayKeyDoesNotExistsMessage($dataKey);
                    }
                } else {
                    $message = sprintf('method Argument is %s ', $argument);
                }

                throw  new \Exception($message);
            }));
    }

    protected function getExceptionArrayKeyExistsMessage($key, $value = '', $data = [])
    {
        $value = !empty($value) ? $value : $data[$key];
        return sprintf('method array Argument %s => %s', $key, $value);
    }

    protected function getExceptionArrayKeyDoesNotExistsMessage($key)
    {
        return sprintf('method array Argument does not contain %s key', $key);
    }

    /***
     * @param $argumentNumber
     * @param $method
     * @param $object
     */
    protected function methodWillReturnArgument($argumentNumber, $method, $object)
    {
        $this->expectsOnceMethod($method, $object)->will($this->returnArgument($argumentNumber));
    }

    /***
     * @param $object
     * @param $method
     */
    protected function methodWillReturnArguments($method, $object)
    {
        $this->expectsOnceMethod($method, $object)->will($this->returnArguments());
    }

    /**
     * @param $method
     * @param $object
     * @param array $data
     */
    protected function methodWillReturnObject($method, $object, $data = [])
    {
        $stdObject = new \stdClass();
        $stdObject->id = 1;

        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }
            $stdObject->{$key} = $value;
        }

        $this->expectsOnceMethod($method, $object)
            ->will($this->returnCallback(function () use ($stdObject) {
                return $stdObject;
            }));
    }

    /**
     * @param $method
     * @param $object
     * @param array $array
     */
    protected function methodWillReturnCollection($method, $object, $array = [])
    {
        $this->methodWillReturn(new Collection($array), $method, $object);
    }


    /**
     * @param $object
     * @param $method
     */
    protected function methodWillReturnCollectionObject($method, $object)
    {
        $this->expectsOnceMethod($method, $object)
            ->will($this->returnCallback(function () {
                return $this->makeCollectionObject();
            }));
    }


    /**
     * @param array $array
     * @return Collection
     */
    protected function makeCollectionObject($array = [])
    {
        return new Collection($array);
    }

    /**
     * @param $methods
     * @return array|string
     */
    private function getMethodsBy($methods)
    {
        if (is_string($methods) && !empty(trim($methods))) {
            $methods = [$methods];
        }

        if (!empty($methods)) {
            return $methods;
        }

        return [];
    }

    protected function getMockedObjectWithMockedMethods($methods, $class = '')
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }
        if (empty($class)) {
            $class = StubClass::class;
        }
        return $this->getMockBuilder($class)->setMethods($methods)->getMock();
    }

}

class StubClass
{

}
