<?php

defined('TYPO3') or die();

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;
use B13\Container\Backend\Preview\ContainerPreviewRenderer as BaseContainerPreviewRenderer;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerPreviewRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function () {
    if (ExtensionManagementUtility::isLoaded('container')) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerGridColumn::class] = [
            'className' => ContainerGridColumn::class,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][BaseContainerPreviewRenderer::class] = [
            'className' => ContainerPreviewRenderer::class,
        ];
    }
});
