<?php

declare(strict_types=1);

defined('TYPO3') or die();

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerGridColumn::class] = [
    'className' => ContainerGridColumn::class,
];
