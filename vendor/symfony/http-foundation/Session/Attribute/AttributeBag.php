<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Attribute;

/**
 * This class relates to session attribute storage.
 *
 * @implements \IteratorAggregate<string, mixed>
 */
class AttributeBag implements AttributeBagInterface, \IteratorAggregate, \Countable
{
<<<<<<< HEAD
    private string $name = 'attributes';
    private string $storageKey;
=======
    private $name = 'attributes';
    private $storageKey;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    protected $attributes = [];

    /**
     * @param string $storageKey The key used to store attributes in the session
     */
    public function __construct(string $storageKey = '_sf2_attributes')
    {
        $this->storageKey = $storageKey;
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
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$attributes)
    {
        $this->attributes = &$attributes;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function getStorageKey(): string
=======
    public function getStorageKey()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->storageKey;
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
        return \array_key_exists($name, $this->attributes);
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
        return \array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
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
        $this->attributes[$name] = $value;
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
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes)
    {
        $this->attributes = [];
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }
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
        $retval = null;
        if (\array_key_exists($name, $this->attributes)) {
            $retval = $this->attributes[$name];
            unset($this->attributes[$name]);
        }

        return $retval;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function clear(): mixed
=======
    public function clear()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $return = $this->attributes;
        $this->attributes = [];

        return $return;
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
        return new \ArrayIterator($this->attributes);
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
        return \count($this->attributes);
    }
}
