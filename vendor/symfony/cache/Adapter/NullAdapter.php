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

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class NullAdapter implements AdapterInterface, CacheInterface
{
    private static $createCacheItem;

    public function __construct()
    {
        self::$createCacheItem ?? self::$createCacheItem = \Closure::bind(
            static function ($key) {
                $item = new CacheItem();
                $item->key = $key;
                $item->isHit = false;

                return $item;
            },
            null,
            CacheItem::class
        );
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null): mixed
=======
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $save = true;

        return $callback((self::$createCacheItem)($key), $save);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getItem(mixed $key): CacheItem
=======
    public function getItem($key)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return (self::$createCacheItem)($key);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getItems(array $keys = []): iterable
=======
    public function getItems(array $keys = [])
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->generateItems($keys);
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function hasItem(mixed $key): bool
=======
     *
     * @return bool
     */
    public function hasItem($key)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return false;
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function clear(string $prefix = ''): bool
=======
     *
     * @return bool
     */
    public function clear(string $prefix = '')
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return true;
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function deleteItem(mixed $key): bool
=======
     *
     * @return bool
     */
    public function deleteItem($key)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return true;
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function deleteItems(array $keys): bool
=======
     *
     * @return bool
     */
    public function deleteItems(array $keys)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return true;
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function save(CacheItemInterface $item): bool
=======
     *
     * @return bool
     */
    public function save(CacheItemInterface $item)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return true;
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function saveDeferred(CacheItemInterface $item): bool
=======
     *
     * @return bool
     */
    public function saveDeferred(CacheItemInterface $item)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return true;
    }

    /**
     * {@inheritdoc}
<<<<<<< HEAD
     */
    public function commit(): bool
=======
     *
     * @return bool
     */
    public function commit()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->deleteItem($key);
    }

    private function generateItems(array $keys): \Generator
    {
        $f = self::$createCacheItem;

        foreach ($keys as $key) {
            yield $key => $f($key);
        }
    }
}
