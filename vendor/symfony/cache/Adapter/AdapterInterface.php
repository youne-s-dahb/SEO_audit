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

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;

// Help opcache.preload discover always-needed symbols
class_exists(CacheItem::class);

/**
 * Interface for adapters managing instances of Symfony's CacheItem.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface AdapterInterface extends CacheItemPoolInterface
{
    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function getItem(mixed $key): CacheItem;
=======
     *
     * @return CacheItem
     */
    public function getItem($key);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * {@inheritdoc}
     *
<<<<<<< HEAD
     * @return iterable<string, CacheItem>
     */
    public function getItems(array $keys = []): iterable;

    /**
     * {@inheritdoc}
     */
    public function clear(string $prefix = ''): bool;
=======
     * @return \Traversable<string, CacheItem>
     */
    public function getItems(array $keys = []);

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function clear(string $prefix = '');
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
}
