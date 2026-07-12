<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Traits;

/**
 * @author Alessandro Chitolina <alekitto@gmail.com>
 *
 * @internal
 */
class RedisClusterProxy
{
    private $redis;
<<<<<<< HEAD
    private \Closure $initializer;
=======
    private $initializer;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    public function __construct(\Closure $initializer)
    {
        $this->initializer = $initializer;
    }

    public function __call(string $method, array $args)
    {
<<<<<<< HEAD
        $this->redis ??= ($this->initializer)();
=======
        $this->redis ?: $this->redis = $this->initializer->__invoke();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

        return $this->redis->{$method}(...$args);
    }

    public function hscan($strKey, &$iIterator, $strPattern = null, $iCount = null)
    {
<<<<<<< HEAD
        $this->redis ??= ($this->initializer)();
=======
        $this->redis ?: $this->redis = $this->initializer->__invoke();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

        return $this->redis->hscan($strKey, $iIterator, $strPattern, $iCount);
    }

    public function scan(&$iIterator, $strPattern = null, $iCount = null)
    {
<<<<<<< HEAD
        $this->redis ??= ($this->initializer)();
=======
        $this->redis ?: $this->redis = $this->initializer->__invoke();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

        return $this->redis->scan($iIterator, $strPattern, $iCount);
    }

    public function sscan($strKey, &$iIterator, $strPattern = null, $iCount = null)
    {
<<<<<<< HEAD
        $this->redis ??= ($this->initializer)();
=======
        $this->redis ?: $this->redis = $this->initializer->__invoke();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

        return $this->redis->sscan($strKey, $iIterator, $strPattern, $iCount);
    }

    public function zscan($strKey, &$iIterator, $strPattern = null, $iCount = null)
    {
<<<<<<< HEAD
        $this->redis ??= ($this->initializer)();
=======
        $this->redis ?: $this->redis = $this->initializer->__invoke();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

        return $this->redis->zscan($strKey, $iIterator, $strPattern, $iCount);
    }
}
