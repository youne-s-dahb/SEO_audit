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
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Cache\ResettableInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * An adapter that collects data about all cache calls.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Nicolas Grekas <p@tchwork.com>
 */
class TraceableAdapter implements AdapterInterface, CacheInterface, PruneableInterface, ResettableInterface
{
    protected $pool;
<<<<<<< HEAD
    private array $calls = [];
=======
    private $calls = [];
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    public function __construct(AdapterInterface $pool)
    {
        $this->pool = $pool;
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
        if (!$this->pool instanceof CacheInterface) {
            throw new \BadMethodCallException(sprintf('Cannot call "%s::get()": this class doesn\'t implement "%s".', get_debug_type($this->pool), CacheInterface::class));
        }

        $isHit = true;
        $callback = function (CacheItem $item, bool &$save) use ($callback, &$isHit) {
            $isHit = $item->isHit();

            return $callback($item, $save);
        };

        $event = $this->start(__FUNCTION__);
        try {
            $value = $this->pool->get($key, $callback, $beta, $metadata);
            $event->result[$key] = get_debug_type($value);
        } finally {
            $event->end = microtime(true);
        }
        if ($isHit) {
            ++$event->hits;
        } else {
            ++$event->misses;
        }

        return $value;
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
        $event = $this->start(__FUNCTION__);
        try {
            $item = $this->pool->getItem($key);
        } finally {
            $event->end = microtime(true);
        }
        if ($event->result[$key] = $item->isHit()) {
            ++$event->hits;
        } else {
            ++$event->misses;
        }

        return $item;
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
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result[$key] = $this->pool->hasItem($key);
        } finally {
            $event->end = microtime(true);
        }
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
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result[$key] = $this->pool->deleteItem($key);
        } finally {
            $event->end = microtime(true);
        }
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
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result[$item->getKey()] = $this->pool->save($item);
        } finally {
            $event->end = microtime(true);
        }
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
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result[$item->getKey()] = $this->pool->saveDeferred($item);
        } finally {
            $event->end = microtime(true);
        }
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
        $event = $this->start(__FUNCTION__);
        try {
            $result = $this->pool->getItems($keys);
        } finally {
            $event->end = microtime(true);
        }
        $f = function () use ($result, $event) {
            $event->result = [];
            foreach ($result as $key => $item) {
                if ($event->result[$key] = $item->isHit()) {
                    ++$event->hits;
                } else {
                    ++$event->misses;
                }
                yield $key => $item;
            }
        };

        return $f();
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
        $event = $this->start(__FUNCTION__);
        try {
            if ($this->pool instanceof AdapterInterface) {
                return $event->result = $this->pool->clear($prefix);
            }

            return $event->result = $this->pool->clear();
        } finally {
            $event->end = microtime(true);
        }
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
        $event = $this->start(__FUNCTION__);
        $event->result['keys'] = $keys;
        try {
            return $event->result['result'] = $this->pool->deleteItems($keys);
        } finally {
            $event->end = microtime(true);
        }
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
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result = $this->pool->commit();
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function prune(): bool
=======
    public function prune()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        if (!$this->pool instanceof PruneableInterface) {
            return false;
        }
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result = $this->pool->prune();
        } finally {
            $event->end = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        if ($this->pool instanceof ResetInterface) {
            $this->pool->reset();
        }

        $this->clearCalls();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $event = $this->start(__FUNCTION__);
        try {
            return $event->result[$key] = $this->pool->deleteItem($key);
        } finally {
            $event->end = microtime(true);
        }
    }

    public function getCalls()
    {
        return $this->calls;
    }

    public function clearCalls()
    {
        $this->calls = [];
    }

    protected function start(string $name)
    {
        $this->calls[] = $event = new TraceableAdapterEvent();
        $event->name = $name;
        $event->start = microtime(true);

        return $event;
    }
}

<<<<<<< HEAD
/**
 * @internal
 */
class TraceableAdapterEvent
{
    public string $name;
    public float $start;
    public float $end;
    public array|bool $result;
    public int $hits = 0;
    public int $misses = 0;
=======
class TraceableAdapterEvent
{
    public $name;
    public $start;
    public $end;
    public $result;
    public $hits = 0;
    public $misses = 0;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
}
