<?php
/*
 * This file is part of the PHPUnit_MockObject package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace LaraTest;


/**
 * Stubs a method by returning an argument that was passed to the mocked method.
 *
 * @since Class available since Release 1.0.0
 */
class PHPUnit_Framework_MockObject_Stub_ReturnArguments extends \PHPUnit_Framework_MockObject_Stub_Return
{

    public function __construct($value = null)
    {
        parent::__construct($value);
    }

    public function invoke(\PHPUnit_Framework_MockObject_Invocation $invocation)
    {
        return $invocation->parameters;
    }

    public function toString()
    {
        return 'return all arguments';
    }
}
