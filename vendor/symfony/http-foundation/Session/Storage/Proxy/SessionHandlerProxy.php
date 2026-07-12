<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Session\Storage\Proxy;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\StrictSessionHandler;

/**
 * @author Drak <drak@zikula.org>
 */
class SessionHandlerProxy extends AbstractProxy implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    protected $handler;

    public function __construct(\SessionHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->wrapper = $handler instanceof \SessionHandler;
        $this->saveHandlerName = $this->wrapper || ($handler instanceof StrictSessionHandler && $handler->isWrapper()) ? \ini_get('session.save_handler') : 'user';
    }

<<<<<<< HEAD
    public function getHandler(): \SessionHandlerInterface
=======
    /**
     * @return \SessionHandlerInterface
     */
    public function getHandler()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->handler;
    }

    // \SessionHandlerInterface

<<<<<<< HEAD
    public function open(string $savePath, string $sessionName): bool
=======
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->handler->open($savePath, $sessionName);
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
        return $this->handler->close();
    }

<<<<<<< HEAD
    public function read(string $sessionId): string|false
=======
    /**
     * @return string|false
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->handler->read($sessionId);
    }

<<<<<<< HEAD
    public function write(string $sessionId, string $data): bool
=======
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->handler->write($sessionId, $data);
    }

<<<<<<< HEAD
    public function destroy(string $sessionId): bool
=======
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return $this->handler->destroy($sessionId);
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
        return $this->handler->gc($maxlifetime);
    }

<<<<<<< HEAD
    public function validateId(string $sessionId): bool
=======
    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function validateId($sessionId)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return !$this->handler instanceof \SessionUpdateTimestampHandlerInterface || $this->handler->validateId($sessionId);
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
        return $this->handler instanceof \SessionUpdateTimestampHandlerInterface ? $this->handler->updateTimestamp($sessionId, $data) : $this->write($sessionId, $data);
    }
}
