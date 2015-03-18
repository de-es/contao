<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Verifies and optimizes paths in the contao.resoures service.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class OptimizeContaoResourcesPass implements CompilerPassInterface
{
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $kernelRootDir The kernel root directory
     */
    public function __construct($kernelRootDir)
    {
        // We need relative paths starting from "TL_ROOT"
        $this->rootDir = dirname($kernelRootDir) . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('contao.resource_provider')) {
            return;
        }

        $resourcesPaths = [];
        $publicFolders  = [];
        $definition     = $container->getDefinition('contao.resource_provider');
        $calls          = $definition->getMethodCalls();

        foreach ($calls as $k => $call) {
            if ('addResourcesPath' === $call[0]) {
                $resourcesPaths[$call[1][0]] = $this->validatePath($call[1][1]);
                unset($calls[$k]);
            } elseif ('addPublicFolders' === $call[0]) {
                $this->mergePaths($publicFolders, $call[1][0]);
                unset($calls[$k]);
            }
        }

        $definition->setMethodCalls($calls);
        $definition->setArguments([$resourcesPaths, $publicFolders]);
    }

    /**
     * Adds relative paths to an array making sure they actually exist.
     *
     * @param array $current  Current paths
     * @param array $new      Paths to be added
     */
    private function mergePaths(array &$current, array $new)
    {
        foreach ($new as $path) {
            $path = $this->validatePath($path);
            $path = str_replace($this->rootDir, '', $path);

            $current[] = $path;
        }
    }

    /**
     * Make sure the given path exists.
     *
     * @param string $path The path to be checked.
     *
     * @return string The path if it was found.
     *
     * @throws \InvalidArgumentException If the path does not exist
     */
    private function validatePath($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" does not exist.', $path));
        }

        return $path;
    }
}
