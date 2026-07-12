<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\DependencyInjection;

use Symfony\Component\Cache\PruneableInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Rob Frawley 2nd <rmf@src.run>
 */
class CachePoolPrunerPass implements CompilerPassInterface
{
<<<<<<< HEAD
=======
    private $cacheCommandServiceId;
    private $cachePoolTag;

    public function __construct(string $cacheCommandServiceId = 'console.command.cache_pool_prune', string $cachePoolTag = 'cache.pool')
    {
        if (0 < \func_num_args()) {
            trigger_deprecation('symfony/cache', '5.3', 'Configuring "%s" is deprecated.', __CLASS__);
        }

        $this->cacheCommandServiceId = $cacheCommandServiceId;
        $this->cachePoolTag = $cachePoolTag;
    }

>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
<<<<<<< HEAD
        if (!$container->hasDefinition('console.command.cache_pool_prune')) {
=======
        if (!$container->hasDefinition($this->cacheCommandServiceId)) {
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
            return;
        }

        $services = [];

<<<<<<< HEAD
        foreach ($container->findTaggedServiceIds('cache.pool') as $id => $tags) {
=======
        foreach ($container->findTaggedServiceIds($this->cachePoolTag) as $id => $tags) {
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass());

            if (!$reflection = $container->getReflectionClass($class)) {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $class, $id));
            }

            if ($reflection->implementsInterface(PruneableInterface::class)) {
                $services[$id] = new Reference($id);
            }
        }

<<<<<<< HEAD
        $container->getDefinition('console.command.cache_pool_prune')->replaceArgument(0, new IteratorArgument($services));
=======
        $container->getDefinition($this->cacheCommandServiceId)->replaceArgument(0, new IteratorArgument($services));
>>>>>>> 3a5b7382167f26153998906199b73a658eb282a1
    }
}
