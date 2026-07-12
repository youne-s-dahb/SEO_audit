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
 * Can be used in unit testing or in a situations where persisted sessions are not desired.
 *
 * @author Drak <drak@zikula.org>
 */
class NullSessionHandler extends AbstractSessionHandler
{
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
        return true;
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
        return true;
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
        return '';
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
        return true;
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
        return true;
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
        return 0;
    }
}
