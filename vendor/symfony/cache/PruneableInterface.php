<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache;

/**
 * Interface extends psr-6 and psr-16 caches to allow for pruning (deletion) of all expired cache items.
 */
interface PruneableInterface
{
<<<<<<< HEAD
    public function prune(): bool;
=======
    /**
     * @return bool
     */
    public function prune();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
}
