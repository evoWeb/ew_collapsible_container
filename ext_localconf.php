<?php

defined('TYPO3') or die();

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;
use B13\Container\Backend\Grid\ContainerGridColumnItem as BaseContainerGridColumnItem;
use B13\Container\Backend\Preview\ContainerPreviewRenderer as BaseContainerPreviewRenderer;
use B13\Container\Tca\ContainerConfiguration as BaseContainerConfiguration;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerConfiguration;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumnItem;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerPreviewRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function () {
    if (ExtensionManagementUtility::isLoaded('container')) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerConfiguration::class] = [
            'className' => ContainerConfiguration::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerGridColumn::class] = [
            'className' => ContainerGridColumn::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerPreviewRenderer::class] = [
            'className' => ContainerPreviewRenderer::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerGridColumnItem::class] = [
            'className' => ContainerGridColumnItem::class,
        ];
    }
});
