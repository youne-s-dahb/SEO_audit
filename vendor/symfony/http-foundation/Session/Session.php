<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
<<<<<<< HEAD
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
=======
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

// Help opcache.preload discover always-needed symbols
class_exists(AttributeBag::class);
class_exists(FlashBag::class);
class_exists(SessionBagProxy::class);

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Drak <drak@zikula.org>
 *
 * @implements \IteratorAggregate<string, mixed>
 */
class Session implements SessionInterface, \IteratorAggregate, \Countable
{
    protected $storage;

<<<<<<< HEAD
    private string $flashName;
    private string $attributeName;
    private array $data = [];
    private int $usageIndex = 0;
    private ?\Closure $usageReporter;

    public function __construct(SessionStorageInterface $storage = null, AttributeBagInterface $attributes = null, FlashBagInterface $flashes = null, callable $usageReporter = null)
    {
        $this->storage = $storage ?? new NativeSessionStorage();
        $this->usageReporter = $usageReporter instanceof \Closure || !\is_callable($usageReporter) ? $usageReporter : \Closure::fromCallable($usageReporter);
=======
    private $flashName;
    private $attributeName;
    private $data = [];
    private $usageIndex = 0;
    private $usageReporter;

    public function __construct(?SessionStorageInterface $storage = null, ?AttributeBagInterface $attributes = null, ?FlashBagInterface $flashes = null, ?callable $usageReporter = null)
    {
        $this->storage = $storage ?? new NativeSessionStorage();
        $this->usageReporter = $usageReporter;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

        $attributes = $attributes ?? new AttributeBag();
        $this->attributeName = $attributes->getName();
        $this->registerBag($attributes);

        $flashes = $flashes ?? new FlashBag();
        $this->flashName = $flashes->getName();
        $this->registerBag($flashes);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function start(): bool
=======
    public function start()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->storage->start();
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function has(string $name): bool
=======
    public function has(string $name)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->getAttributeBag()->has($name);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function get(string $name, mixed $default = null): mixed
=======
    public function get(string $name, $default = null)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->getAttributeBag()->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function set(string $name, mixed $value)
=======
    public function set(string $name, $value)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $this->getAttributeBag()->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function all(): array
=======
    public function all()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->getAttributeBag()->all();
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes)
    {
        $this->getAttributeBag()->replace($attributes);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function remove(string $name): mixed
=======
    public function remove(string $name)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->getAttributeBag()->remove($name);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->getAttributeBag()->clear();
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function isStarted(): bool
=======
    public function isStarted()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->storage->isStarted();
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator<string, mixed>
     */
<<<<<<< HEAD
    public function getIterator(): \ArrayIterator
=======
    #[\ReturnTypeWillChange]
    public function getIterator()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return new \ArrayIterator($this->getAttributeBag()->all());
    }

    /**
     * Returns the number of attributes.
<<<<<<< HEAD
     */
    public function count(): int
=======
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return \count($this->getAttributeBag()->all());
    }

    public function &getUsageIndex(): int
    {
        return $this->usageIndex;
    }

    /**
     * @internal
     */
    public function isEmpty(): bool
    {
        if ($this->isStarted()) {
            ++$this->usageIndex;
            if ($this->usageReporter && 0 <= $this->usageIndex) {
                ($this->usageReporter)();
            }
        }
        foreach ($this->data as &$data) {
            if (!empty($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function invalidate(int $lifetime = null): bool
=======
    public function invalidate(?int $lifetime = null)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $this->storage->clear();

        return $this->migrate(true, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function migrate(bool $destroy = false, int $lifetime = null): bool
=======
    public function migrate(bool $destroy = false, ?int $lifetime = null)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->storage->regenerate($destroy, $lifetime);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $this->storage->save();
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getId(): string
=======
    public function getId()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->storage->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id)
    {
        if ($this->storage->getId() !== $id) {
            $this->storage->setId($id);
        }
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getName(): string
=======
    public function getName()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->storage->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name)
    {
        $this->storage->setName($name);
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getMetadataBag(): MetadataBag
=======
    public function getMetadataBag()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        ++$this->usageIndex;
        if ($this->usageReporter && 0 <= $this->usageIndex) {
            ($this->usageReporter)();
        }

        return $this->storage->getMetadataBag();
    }

    /**
     * {@inheritdoc}
     */
    public function registerBag(SessionBagInterface $bag)
    {
        $this->storage->registerBag(new SessionBagProxy($bag, $this->data, $this->usageIndex, $this->usageReporter));
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getBag(string $name): SessionBagInterface
=======
    public function getBag(string $name)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $bag = $this->storage->getBag($name);

        return method_exists($bag, 'getBag') ? $bag->getBag() : $bag;
    }

    /**
     * Gets the flashbag interface.
<<<<<<< HEAD
     */
    public function getFlashBag(): FlashBagInterface
=======
     *
     * @return FlashBagInterface
     */
    public function getFlashBag()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->getBag($this->flashName);
    }

    /**
     * Gets the attributebag interface.
     *
     * Note that this method was added to help with IDE autocompletion.
     */
    private function getAttributeBag(): AttributeBagInterface
    {
        return $this->getBag($this->attributeName);
    }
}
