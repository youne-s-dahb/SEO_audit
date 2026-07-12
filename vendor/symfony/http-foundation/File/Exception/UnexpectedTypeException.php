<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\File\Exception;

class UnexpectedTypeException extends FileException
{
<<<<<<< HEAD
    public function __construct(mixed $value, string $expectedType)
=======
    public function __construct($value, string $expectedType)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        parent::__construct(sprintf('Expected argument of type %s, %s given', $expectedType, get_debug_type($value)));
    }
}
