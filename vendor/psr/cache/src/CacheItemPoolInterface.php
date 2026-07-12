<?php

namespace Psr\Cache;

/**
 * CacheItemPoolInterface generates CacheItemInterface objects.
 *
 * The primary purpose of Cache\CacheItemPoolInterface is to accept a key from
 * the Calling Library and return the associated Cache\CacheItemInterface object.
 * It is also the primary point of interaction with the entire cache collection.
 * All configuration and initialization of the Pool is left up to an
 * Implementing Library.
 */
interface CacheItemPoolInterface
{
    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface
     *   The corresponding Cache Item.
     */
<<<<<<< HEAD
    public function getItem(string $key): CacheItemInterface;
=======
    public function getItem(string $key);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
<<<<<<< HEAD
     * @return iterable
     *   An iterable collection of Cache Items keyed by the cache keys of
=======
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
<<<<<<< HEAD
    public function getItems(array $keys = []): iterable;
=======
    public function getItems(array $keys = []);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
<<<<<<< HEAD
    public function hasItem(string $key): bool;
=======
    public function hasItem(string $key);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
<<<<<<< HEAD
    public function clear(): bool;
=======
    public function clear();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
<<<<<<< HEAD
    public function deleteItem(string $key): bool;
=======
    public function deleteItem(string $key);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
<<<<<<< HEAD
    public function deleteItems(array $keys): bool;
=======
    public function deleteItems(array $keys);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
<<<<<<< HEAD
    public function save(CacheItemInterface $item): bool;
=======
    public function save(CacheItemInterface $item);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
<<<<<<< HEAD
    public function saveDeferred(CacheItemInterface $item): bool;
=======
    public function saveDeferred(CacheItemInterface $item);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
<<<<<<< HEAD
    public function commit(): bool;
=======
    public function commit();
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
}
