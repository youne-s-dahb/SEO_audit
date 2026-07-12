<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Flash;

/**
 * FlashBag flash message container.
 *
 * @author Drak <drak@zikula.org>
 */
class FlashBag implements FlashBagInterface
{
<<<<<<< HEAD
    private string $name = 'flashes';
    private array $flashes = [];
    private string $storageKey;
=======
    private $name = 'flashes';
    private $flashes = [];
    private $storageKey;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * @param string $storageKey The key used to store flashes in the session
     */
    public function __construct(string $storageKey = '_symfony_flashes')
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
    public function initialize(array &$flashes)
    {
        $this->flashes = &$flashes;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function add(string $type, mixed $message)
=======
    public function add(string $type, $message)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $this->flashes[$type][] = $message;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function peek(string $type, array $default = []): array
=======
    public function peek(string $type, array $default = [])
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->has($type) ? $this->flashes[$type] : $default;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function peekAll(): array
=======
    public function peekAll()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->flashes;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function get(string $type, array $default = []): array
=======
    public function get(string $type, array $default = [])
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        if (!$this->has($type)) {
            return $default;
        }

        $return = $this->flashes[$type];

        unset($this->flashes[$type]);

        return $return;
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
        $return = $this->peekAll();
        $this->flashes = [];

        return $return;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function set(string $type, string|array $messages)
=======
    public function set(string $type, $messages)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $this->flashes[$type] = (array) $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function setAll(array $messages)
    {
        $this->flashes = $messages;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function has(string $type): bool
=======
    public function has(string $type)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return \array_key_exists($type, $this->flashes) && $this->flashes[$type];
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function keys(): array
=======
    public function keys()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return array_keys($this->flashes);
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
    public function clear(): mixed
=======
    public function clear()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->all();
    }
}
