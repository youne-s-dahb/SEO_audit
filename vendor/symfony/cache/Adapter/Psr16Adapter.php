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

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Cache\ResettableInterface;
use Symfony\Component\Cache\Traits\ProxyTrait;

/**
 * Turns a PSR-16 cache into a PSR-6 one.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class Psr16Adapter extends AbstractAdapter implements PruneableInterface, ResettableInterface
{
    use ProxyTrait;

    /**
     * @internal
     */
    protected const NS_SEPARATOR = '_';

<<<<<<< HEAD
    private object $miss;
=======
    private $miss;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    public function __construct(CacheInterface $pool, string $namespace = '', int $defaultLifetime = 0)
    {
        parent::__construct($namespace, $defaultLifetime);

        $this->pool = $pool;
        $this->miss = new \stdClass();
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doFetch(array $ids): iterable
=======
    protected function doFetch(array $ids)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        foreach ($this->pool->getMultiple($ids, $this->miss) as $key => $value) {
            if ($this->miss !== $value) {
                yield $key => $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doHave(string $id): bool
=======
    protected function doHave(string $id)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->pool->has($id);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doClear(string $namespace): bool
=======
    protected function doClear(string $namespace)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doDelete(array $ids): bool
=======
    protected function doDelete(array $ids)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->pool->deleteMultiple($ids);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doSave(array $values, int $lifetime): array|bool
=======
    protected function doSave(array $values, int $lifetime)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->pool->setMultiple($values, 0 === $lifetime ? null : $lifetime);
    }
}
