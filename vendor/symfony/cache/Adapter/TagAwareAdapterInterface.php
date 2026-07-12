<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Adapter;

use Psr\Cache\InvalidArgumentException;

/**
 * Interface for invalidating cached items using tags.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
interface TagAwareAdapterInterface extends AdapterInterface
{
    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
<<<<<<< HEAD
     * @throws InvalidArgumentException When $tags is not valid
     */
    public function invalidateTags(array $tags): bool;
=======
     * @return bool
     *
     * @throws InvalidArgumentException When $tags is not valid
     */
    public function invalidateTags(array $tags);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
}
