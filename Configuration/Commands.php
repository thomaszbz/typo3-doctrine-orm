<?php

/*
 * This file is (c) 2017 by Cyberhouse GmbH
 *
 * It is free software; you can redistribute it and/or
 * modify it under the terms of the GPLv3 license
 *
 * For the full copyright and license information see
 * <https://www.gnu.org/licenses/gpl-3.0.html>
 */

return [
    'doctrine:migrate' => [
        'class' => \Cyberhouse\DoctrineORM\Command\MigrateCommand::class,
    ],
    'doctrine:proxies' => [
        'class' => \Cyberhouse\DoctrineORM\Command\ProxiesCommand::class,
    ],
];