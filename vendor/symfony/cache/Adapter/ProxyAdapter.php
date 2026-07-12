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
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\Cache\ResettableInterface;
use Symfony\Component\Cache\Traits\ContractsTrait;
use Symfony\Component\Cache\Traits\ProxyTrait;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ProxyAdapter implements AdapterInterface, CacheInterface, PruneableInterface, ResettableInterface
{
    use ContractsTrait;
    use ProxyTrait;

<<<<<<< HEAD
    private string $namespace = '';
    private int $namespaceLen;
    private string $poolHash;
    private int $defaultLifetime;

    private static \Closure $createCacheItem;
    private static \Closure $setInnerItem;
=======
    private $namespace = '';
    private $namespaceLen;
    private $poolHash;
    private $defaultLifetime;

    private static $createCacheItem;
    private static $setInnerItem;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    public function __construct(CacheItemPoolInterface $pool, string $namespace = '', int $defaultLifetime = 0)
    {
        $this->pool = $pool;
<<<<<<< HEAD
        $this->poolHash = spl_object_hash($pool);
=======
        $this->poolHash = $poolHash = spl_object_hash($pool);
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
        if ('' !== $namespace) {
            \assert('' !== CacheItem::validateKey($namespace));
            $this->namespace = $namespace;
        }
        $this->namespaceLen = \strlen($namespace);
        $this->defaultLifetime = $defaultLifetime;
        self::$createCacheItem ?? self::$createCacheItem = \Closure::bind(
            static function ($key, $innerItem, $poolHash) {
                $item = new CacheItem();
                $item->key = $key;

                if (null === $innerItem) {
                    return $item;
                }

                $item->value = $v = $innerItem->get();
                $item->isHit = $innerItem->isHit();
                $item->innerItem = $innerItem;
                $item->poolHash = $poolHash;

                // Detect wrapped values that encode for their expiry and creation duration
                // For compactness, these values are packed in the key of an array using
                // magic numbers in the form 9D-..-..-..-..-00-..-..-..-5F
                if (\is_array($v) && 1 === \count($v) && 10 === \strlen($k = (string) array_key_first($v)) && "\x9D" === $k[0] && "\0" === $k[5] && "\x5F" === $k[9]) {
                    $item->value = $v[$k];
                    $v = unpack('Ve/Nc', substr($k, 1, -1));
                    $item->metadata[CacheItem::METADATA_EXPIRY] = $v['e'] + CacheItem::METADATA_EXPIRY_OFFSET;
                    $item->metadata[CacheItem::METADATA_CTIME] = $v['c'];
                } elseif ($innerItem instanceof CacheItem) {
                    $item->metadata = $innerItem->metadata;
                }
                $innerItem->set(null);

                return $item;
            },
            null,
            CacheItem::class
        );
        self::$setInnerItem ?? self::$setInnerItem = \Closure::bind(
            /**
             * @param array $item A CacheItem cast to (array); accessing protected properties requires adding the "\0*\0" PHP prefix
             */
            static function (CacheItemInterface $innerItem, array $item) {
                // Tags are stored separately, no need to account for them when considering this item's newly set metadata
                if (isset(($metadata = $item["\0*\0newMetadata"])[CacheItem::METADATA_TAGS])) {
                    unset($metadata[CacheItem::METADATA_TAGS]);
                }
                if ($metadata) {
                    // For compactness, expiry and creation duration are packed in the key of an array, using magic numbers as separators
                    $item["\0*\0value"] = ["\x9D".pack('VN', (int) (0.1 + $metadata[self::METADATA_EXPIRY] - self::METADATA_EXPIRY_OFFSET), $metadata[self::METADATA_CTIME])."\x5F" => $item["\0*\0value"]];
                }
                $innerItem->set($item["\0*\0value"]);
                $innerItem->expiresAt(null !== $item["\0*\0expiry"] ? \DateTime::createFromFormat('U.u', sprintf('%.6F', $item["\0*\0expiry"])) : null);
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
        if (!$this->pool instanceof CacheInterface) {
            return $this->doGet($this, $key, $callback, $beta, $metadata);
        }

        return $this->pool->get($this->getId($key), function ($innerItem, bool &$save) use ($key, $callback) {
            $item = (self::$createCacheItem)($key, $innerItem, $this->poolHash);
            $item->set($value = $callback($item, $save));
            (self::$setInnerItem)($innerItem, (array) $item);

            return $value;
        }, $beta, $metadata);
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
        $item = $this->pool->getItem($this->getId($key));

        return (self::$createCacheItem)($key, $item, $this->poolHash);
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
        if ($this->namespaceLen) {
            foreach ($keys as $i => $key) {
                $keys[$i] = $this->getId($key);
            }
        }

        return $this->generateItems($this->pool->getItems($keys));
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
        return $this->pool->hasItem($this->getId($key));
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
        if ($this->pool instanceof AdapterInterface) {
            return $this->pool->clear($this->namespace.$prefix);
        }

        return $this->pool->clear();
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
        return $this->pool->deleteItem($this->getId($key));
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
        if ($this->namespaceLen) {
            foreach ($keys as $i => $key) {
                $keys[$i] = $this->getId($key);
            }
        }

        return $this->pool->deleteItems($keys);
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
        return $this->doSave($item, __FUNCTION__);
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
        return $this->doSave($item, __FUNCTION__);
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
        return $this->pool->commit();
    }

<<<<<<< HEAD
    private function doSave(CacheItemInterface $item, string $method): bool
=======
    private function doSave(CacheItemInterface $item, string $method)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        if (!$item instanceof CacheItem) {
            return false;
        }
        $item = (array) $item;
        if (null === $item["\0*\0expiry"] && 0 < $this->defaultLifetime) {
            $item["\0*\0expiry"] = microtime(true) + $this->defaultLifetime;
        }

        if ($item["\0*\0poolHash"] === $this->poolHash && $item["\0*\0innerItem"]) {
            $innerItem = $item["\0*\0innerItem"];
        } elseif ($this->pool instanceof AdapterInterface) {
            // this is an optimization specific for AdapterInterface implementations
            // so we can save a round-trip to the backend by just creating a new item
            $innerItem = (self::$createCacheItem)($this->namespace.$item["\0*\0key"], null, $this->poolHash);
        } else {
            $innerItem = $this->pool->getItem($this->namespace.$item["\0*\0key"]);
        }

        (self::$setInnerItem)($innerItem, $item);

        return $this->pool->$method($innerItem);
    }

    private function generateItems(iterable $items): \Generator
    {
        $f = self::$createCacheItem;

        foreach ($items as $key => $item) {
            if ($this->namespaceLen) {
                $key = substr($key, $this->namespaceLen);
            }

            yield $key => $f($key, $item, $this->poolHash);
        }
    }

<<<<<<< HEAD
    private function getId(mixed $key): string
=======
    private function getId($key): string
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        \assert('' !== CacheItem::validateKey($key));

        return $this->namespace.$key;
    }
}
