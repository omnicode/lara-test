<?php

namespace LaraTest\Traits;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use LaraTest\ReturnArguments;
use LaraValidation\CoreValidator;

trait MockTraits
{
    /**
     * @param $class
     * @param array $arguments
     * @param array $methods
     */
    protected function getMockForAbstract($class, $arguments = [], $methods = [])
    {
        make_array($methods);

        return $this->getMockForAbstractClass(
            $class,
            $arguments,
            '',
            true,
            true,
            true,
            $methods
        );
    }

    /**
     * @param $class
     * @param string $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockValidator($class, $methods = '')
    {
        $mockBuilder = $this->getMockBuilder($class)
            ->setConstructorArgs([app(CoreValidator::class)]);

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
            ->setConstructorArgs([app(Container::class), app(Collection::class)]);

        if ($this->getMethodsBy($methods)) {
            $mockBuilder->setMethods($methods);
        } else {
            $mockBuilder->setMethods(null);
        }

        return $mockBuilder->getMock();
    }

    /**
     * @param $methods
     * @param string $class
     * @param array $arguments
     * @return mixed
     */
    protected function getMockObjectWithMockedMethods($methods, $class = '', $arguments = [])
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }
        if (empty($class)) {
            $class = \stdClass::class;
        }

        return $this->getMockBuilder($class)->setMethods($methods)->setConstructorArgs($arguments)->getMock();
    }

    /**
     * @param $object
     * @param $method
     * @param array $with
     * @param $return
     * @param string $time
     * @param string $param
     * @return mixed
     */
    protected function methodWillReturn($object, $method, $return, $with = [], $time = 'once', $param = '')
    {
        make_array($with);
        return $this->fixExpectsMethod($object, $method, $time, $param, $with)->willReturn($return);
    }

    /***
     * @param $object
     * @param $method
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodWillReturnTrue($object, $method, $with = [], $time = 'once', $param = '')
    {
        $this->methodWillReturn($object, $method, true, $with, $time, $param);
    }

    /**
     * @param $object
     * @param $methods
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodsWillReturnTrue($object, $methods, $with = [], $time = 'once', $param = '')
    {
        make_array($methods);

        foreach ($methods as $method) {
            $this->methodWillReturn($object, $method, true, $with, $time, $param);
        }
    }

    /***
     * @param $object
     * @param $method
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodWillReturnFalse($object, $method, $with = [], $time = 'once', $param = '')
    {
        $this->methodWillReturn($object, $method, false, $with, $time, $param);
    }

    /**
     * @param $object
     * @param $method
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodWillReturnEmptyArray($object, $method, $with = [], $time = 'once', $param = '')
    {
        $this->methodWillReturn($object, $method, [], $with , $time, $param);
    }


    /**
     * @param $method
     * @param $object
     * @param array $data
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodWillReturnObject($method, $object, $data = [], $with = [], $time = 'once', $param = '')
    {
        $stdObject = new \stdClass();
        $stdObject->id = 1;

        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }
            $stdObject->{$key} = $value;
        }

        $this->fixExpectsMethod($object, $method, $time, $param, $with)
            ->will($this->returnCallback(function () use ($stdObject) {
                return $stdObject;
            }));
    }

    /***
     * @param $object
     * @param $method
     * @param $argumentNumber
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodWillReturnArgument($object, $method, $argumentNumber, $with = [], $time = 'once', $param = '')
    {
        $this->fixExpectsMethod($object, $method, $time, $param, $with)->will($this->returnArgument($argumentNumber));
    }

    /**
     * @return ReturnArguments
     */
    public function returnArguments()
    {
        return new ReturnArguments();
    }

    /**
     * @param $object
     * @param $method
     * @param string $message
     * @param array $with
     * @param string $time
     * @param string $param
     */
    protected function methodWillThrowException($object, $method, $message = 'exception', $with = [], $time = 'once', $param = '')
    {
        $this->fixExpectsMethod($object, $method, $time, $param, $with)->will($this->throwException(new \Exception($message)));
    }


    /**
     * @param $methods
     * @param $object
     * @param array $arguments
     */
    protected function chainMethodsWillReturnArguments(&$methods, $object, $arguments = [])
    {
        //TODO
        $method = array_shift($methods);
        $this->expectsOnceMethod($method, $object)
            ->will($this->returnCallback(function () use ($arguments, $methods) {
                $arguments = array_merge($arguments, func_get_args());
                if (empty($methods)) {
                    return $arguments;
                }

                $method = head($methods);
                $instances = $this->getMockObjectWithMockedMethods($method);
                $this->chainMethodsWillReturnArguments($methods, $instances, $arguments);
                return $instances;
            }));
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

    /**
     * @param $object
     * @param $method
     * @param string $time
     * @param string $param
     * @param array $with
     * @return mixed
     */
    protected function fixExpectsMethod($object, $method, $time = 'once', $param = '', $with = [])
    {
        $expects = ($param !== '') ?  $this->{$time}($param) : $this->{$time}();
        return $object->expects($expects)->method($method)->with(...$with);
    }
}

