<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * GlobFileLoader loads files from a glob pattern.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class GlobFileLoader extends FileLoader
{
    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function load(mixed $resource, string $type = null): mixed
=======
    public function load($resource, ?string $type = null)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        $collection = new RouteCollection();

        foreach ($this->glob($resource, false, $globResource) as $path => $info) {
            $collection->addCollection($this->import($path));
        }

        $collection->addResource($globResource);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
<<<<<<< HEAD
    public function supports(mixed $resource, string $type = null): bool
=======
    public function supports($resource, ?string $type = null)
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    {
        return 'glob' === $type;
    }
}
