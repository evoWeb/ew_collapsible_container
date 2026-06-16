<?php

declare(strict_types=1);

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;
use B13\Container\Backend\Preview\GridRenderer as BaseGridRenderer;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use Evoweb\EwCollapsibleContainer\Xclass\GridRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

if (ExtensionManagementUtility::isLoaded('container')) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerGridColumn::class] = [
        'className' => ContainerGridColumn::class,
    ];
}
