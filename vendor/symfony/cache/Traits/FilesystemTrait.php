<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Traits;

use Symfony\Component\Cache\Exception\CacheException;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Rob Frawley 2nd <rmf@src.run>
 *
 * @internal
 */
trait FilesystemTrait
{
    use FilesystemCommonTrait;

    private $marshaller;

<<<<<<< HEAD
    public function prune(): bool
=======
    /**
     * @return bool
     */
    public function prune()
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $time = time();
        $pruned = true;

        foreach ($this->scanHashDir($this->directory) as $file) {
            if (!$h = @fopen($file, 'r')) {
                continue;
            }

            if (($expiresAt = (int) fgets($h)) && $time >= $expiresAt) {
                fclose($h);
<<<<<<< HEAD
                $pruned = @unlink($file) && !file_exists($file) && $pruned;
=======
                $pruned = (@unlink($file) || !file_exists($file)) && $pruned;
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
            } else {
                fclose($h);
            }
        }

        return $pruned;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doFetch(array $ids): iterable
=======
    protected function doFetch(array $ids)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $values = [];
        $now = time();

        foreach ($ids as $id) {
            $file = $this->getFile($id);
            if (!is_file($file) || !$h = @fopen($file, 'r')) {
                continue;
            }
            if (($expiresAt = (int) fgets($h)) && $now >= $expiresAt) {
                fclose($h);
                @unlink($file);
            } else {
                $i = rawurldecode(rtrim(fgets($h)));
                $value = stream_get_contents($h);
                fclose($h);
                if ($i === $id) {
                    $values[$id] = $this->marshaller->unmarshall($value);
                }
            }
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doHave(string $id): bool
=======
    protected function doHave(string $id)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $file = $this->getFile($id);

        return is_file($file) && (@filemtime($file) > time() || $this->doFetch([$id]));
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    protected function doSave(array $values, int $lifetime): array|bool
=======
    protected function doSave(array $values, int $lifetime)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $expiresAt = $lifetime ? (time() + $lifetime) : 0;
        $values = $this->marshaller->marshall($values, $failed);

        foreach ($values as $id => $value) {
            if (!$this->write($this->getFile($id, true), $expiresAt."\n".rawurlencode($id)."\n".$value, $expiresAt)) {
                $failed[] = $id;
            }
        }

        if ($failed && !is_writable($this->directory)) {
            throw new CacheException(sprintf('Cache directory is not writable (%s).', $this->directory));
        }

        return $failed;
    }

    private function getFileKey(string $file): string
    {
        if (!$h = @fopen($file, 'r')) {
            return '';
        }

        fgets($h); // expiry
        $encodedKey = fgets($h);
        fclose($h);

        return rawurldecode(rtrim($encodedKey));
    }
}
