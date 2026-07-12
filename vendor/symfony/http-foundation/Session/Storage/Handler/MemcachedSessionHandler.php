<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Storage\Handler;

/**
 * Memcached based session storage handler based on the Memcached class
 * provided by the PHP memcached extension.
 *
 * @see https://php.net/memcached
 *
 * @author Drak <drak@zikula.org>
 */
class MemcachedSessionHandler extends AbstractSessionHandler
{
    private $memcached;

    /**
<<<<<<< HEAD
     * Time to live in seconds.
     */
    private ?int $ttl;

    /**
     * Key prefix for shared environments.
     */
    private string $prefix;
=======
     * @var int Time to live in seconds
     */
    private $ttl;

    /**
     * @var string Key prefix for shared environments
     */
    private $prefix;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1

    /**
     * Constructor.
     *
     * List of available options:
     *  * prefix: The prefix to use for the memcached keys in order to avoid collision
     *  * ttl: The time to live in seconds.
     *
     * @throws \InvalidArgumentException When unsupported options are passed
     */
    public function __construct(\Memcached $memcached, array $options = [])
    {
        $this->memcached = $memcached;

        if ($diff = array_diff(array_keys($options), ['prefix', 'expiretime', 'ttl'])) {
            throw new \InvalidArgumentException(sprintf('The following options are not supported "%s".', implode(', ', $diff)));
        }

        $this->ttl = $options['expiretime'] ?? $options['ttl'] ?? null;
        $this->prefix = $options['prefix'] ?? 'sf2s';
    }

<<<<<<< HEAD
    public function close(): bool
=======
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function close()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->memcached->quit();
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doRead(string $sessionId): string
=======
    protected function doRead(string $sessionId)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->memcached->get($this->prefix.$sessionId) ?: '';
    }

<<<<<<< HEAD
    public function updateTimestamp(string $sessionId, string $data): bool
=======
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function updateTimestamp($sessionId, $data)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $this->memcached->touch($this->prefix.$sessionId, $this->getCompatibleTtl());

        return true;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doWrite(string $sessionId, string $data): bool
=======
    protected function doWrite(string $sessionId, string $data)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->memcached->set($this->prefix.$sessionId, $data, $this->getCompatibleTtl());
    }

    private function getCompatibleTtl(): int
    {
        $ttl = (int) ($this->ttl ?? \ini_get('session.gc_maxlifetime'));

        // If the relative TTL that is used exceeds 30 days, memcached will treat the value as Unix time.
        // We have to convert it to an absolute Unix time at this point, to make sure the TTL is correct.
        if ($ttl > 60 * 60 * 24 * 30) {
            $ttl += time();
        }

        return $ttl;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doDestroy(string $sessionId): bool
=======
    protected function doDestroy(string $sessionId)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $result = $this->memcached->delete($this->prefix.$sessionId);

        return $result || \Memcached::RES_NOTFOUND == $this->memcached->getResultCode();
    }

<<<<<<< HEAD
    public function gc(int $maxlifetime): int|false
=======
    /**
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        // not required here because memcached will auto expire the records anyhow.
        return 0;
    }

    /**
     * Return a Memcached instance.
<<<<<<< HEAD
     */
    protected function getMemcached(): \Memcached
=======
     *
     * @return \Memcached
     */
    protected function getMemcached()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->memcached;
    }
}
