<?php

namespace LaraTest\Traits;

trait ClassAssertTraits
{
    public function assertClassHasTraits($traits, $className, $message = '')
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        if (!is_array($traits)) {
            $traits = [$traits];
        }

        if (empty($message)) {
            $message = sprintf('%s class dont contain %s traits', $className, implode(', ', $traits));
        }

        $isTrue = array_has(class_uses($className), $traits);
        $this->assertTrue($isTrue, $message);
    }

}
