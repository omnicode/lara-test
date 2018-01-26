<?php
namespace LaraTest\Console\Commands\Traits;

use LaraSupport\Str;

trait ControllerMaker
{

    private $comparisonOperators = [
        '===',
        '!==',
        '==',
        '!=',
    ];

    public function getTestBranchesMethodIfAuthenticated($method, $class, $route, $isAdminController, $isMultiRoute)
    {
        $methodActualContent = $this->getMethodBody($this->currentClassContent, $this->classFullName, $method);
        $conditionSubStr = Str::between($methodActualContent, 'if', '{');
        $conditionSubStr = trim($conditionSubStr);

//dd($methodActualContent, $conditionSubStr);
    }


}