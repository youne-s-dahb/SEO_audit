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

use Symfony\Component\Cache\PruneableInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
trait ProxyTrait
{
<<<<<<< HEAD
    private object $pool;
=======
    private $pool;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function prune(): bool
=======
    public function prune()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->pool instanceof PruneableInterface && $this->pool->prune();
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        if ($this->pool instanceof ResetInterface) {
            $this->pool->reset();
        }
    }
}
