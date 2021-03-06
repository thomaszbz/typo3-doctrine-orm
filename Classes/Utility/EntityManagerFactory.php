<?php
namespace Cyberhouse\DoctrineORM\Utility;

/*
 * This file is (c) 2017 by Cyberhouse GmbH
 *
 * It is free software; you can redistribute it and/or
 * modify it under the terms of the GPLv3 license
 *
 * For the full copyright and license information see
 * <https://www.gnu.org/licenses/gpl-3.0.html>
 */

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Create entity managers by extension context
 *
 * @author Georg Großberger <georg.grossberger@cyberhouse.at>
 */
class EntityManagerFactory implements SingletonInterface
{
    /**
     * @inject
     * @var \Cyberhouse\DoctrineORM\Utility\ExtensionRegistry
     */
    protected $registry;

    /**
     * @inject
     * @var \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @inject
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var array|[]EntityManager
     */
    protected $known = [];

    /**
     * @param string $extKey
     * @return EntityManager
     */
    public function get(string $extKey)
    {
        if (!isset($this->known[$extKey])) {
            $paths = $this->registry->getExtensionPaths($extKey);
            $paths[] = ExtensionManagementUtility::extPath('doctrine_orm') . 'Classes/Domain/Model';

            if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['doctrine_orm']['devMode']) {
                $cache = new ArrayCache();
            } else {
                if ($this->cacheManager->hasCache($extKey . '_orm')) {
                    $cache = $this->cacheManager->getCache($extKey . '_orm');
                } else {
                    if (!$this->cacheManager->hasCache('doctrine_orm')) {
                        $config = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
                        $this->cacheManager->setCacheConfigurations($config);
                    }

                    $cache = $this->cacheManager->getCache('doctrine_orm');
                }
            }

            $proxiesDir = rtrim($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['doctrine_orm']['proxyDir'], '/') . '/';

            $config = Setup::createAnnotationMetadataConfiguration(
                $paths,
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['doctrine_orm']['devMode'],
                $proxiesDir . $extKey,
                $cache,
                false
            );

            $connection = $this->objectManager->get(ConnectionPool::class)->getConnectionForTable($extKey);

            $this->known[$extKey] = EntityManager::create($connection, $config);
        }

        return $this->known[$extKey];
    }

    public function reset($extKey)
    {
        if (isset($this->known[$extKey])) {
            $em = $this->known[$extKey];

            if ($em instanceof EntityManager && $em->isOpen()) {
                $em->close();
            }
            unset($em, $this->known[$extKey]);
        }
    }
}
