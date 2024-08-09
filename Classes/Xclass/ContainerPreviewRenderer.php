<?php

declare(strict_types=1);

/*
 * This file is developed by evoWeb.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Evoweb\EwCollapsibleContainer\Xclass;

use B13\Container\Backend\Grid\ContainerGridColumn;
use B13\Container\Backend\Grid\ContainerGridColumnItem;
use B13\Container\Backend\Preview\ContainerPreviewRenderer as BaseContainerPreviewRenderer;
use B13\Container\Domain\Factory\Exception;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn as BaseContainerGridColumn;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ContainerPreviewRenderer extends BaseContainerPreviewRenderer
{
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $content = StandardContentPreviewRenderer::renderPageModulePreviewContent($item);
        // @extensionScannerIgnoreLine
        $context = $item->getContext();
        $record = $item->getRecord();
        $grid = GeneralUtility::makeInstance(Grid::class, $context);
        try {
            $container = $this->containerFactory->buildContainer((int)$record['uid']);
        } catch (Exception) {
            // not a container
            return $content;
        }
        $containerGrid = $this->tcaRegistry->getGrid($record['CType']);
        foreach ($containerGrid as $cols) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $context);
            foreach ($cols as $col) {
                $newContentElementAtTopTarget = $this->containerService->getNewContentElementAtTopTargetInColumn(
                    $container,
                    $col['colPos']
                );
                $allowNewContentElements = !$this->containerColumnConfigurationService->isMaxitemsReached(
                    $container,
                    $col['colPos']
                );
                $columnObject = GeneralUtility::makeInstance(
                    ContainerGridColumn::class,
                    $context,
                    $col,
                    $container,
                    $newContentElementAtTopTarget,
                    $allowNewContentElements,
                    false,
                    $col['minitems'] ?? 0
                );
                $this->setColumnCollapsedState((int)$record['uid'], $columnObject, $col);
                $rowObject->addColumn($columnObject);
                if (isset($col['colPos'])) {
                    $records = $container->getChildrenByColPos($col['colPos']);
                    foreach ($records as $contentRecord) {
                        $columnItem = GeneralUtility::makeInstance(
                            ContainerGridColumnItem::class,
                            $context,
                            $columnObject,
                            $contentRecord,
                            $container
                        );
                        $columnObject->addItem($columnItem);
                    }
                }
            }
            $grid->addRow($rowObject);
        }

        $gridTemplate = $this->tcaRegistry->getGridTemplate($record['CType']);
        $partialRootPaths = $this->tcaRegistry->getGridPartialPaths($record['CType']);
        $layoutRootPaths = $this->tcaRegistry->getGridLayoutPaths($record['CType']);
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setPartialRootPaths($partialRootPaths);
        $view->setLayoutRootPaths($layoutRootPaths);
        $view->setTemplatePathAndFilename($gridTemplate);

        $view->assign(
            'hideRestrictedColumns',
            (bool)(
                BackendUtility::getPagesTSconfig(
                    $context->getPageId()
                )['mod.']['web_layout.']['hideRestrictedCols'] ?? false
            )
        );
        $view->assign(
            'newContentTitle',
            $this->getLanguageService()->sL(
                'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newContentElement'
            )
        );
        $view->assign(
            'newContentTitleShort',
            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content')
        );
        $view->assign('allowEditContent', $this->getBackendUser()->check('tables_modify', 'tt_content'));
        $view->assign('containerGrid', $grid);
        $view->assign('containerRecord', $record);
        $rendered = $view->render();

        return $content . $rendered;
    }

    protected function setColumnCollapsedState(int $recordUid, BaseContainerGridColumn $columnObject, array $col): void
    {
        $collapseId = $recordUid . $columnObject->getColumnNumber();
        if (isset($this->getBackendUser()->uc['moduleData']['list']['containerExpanded'][$collapseId])) {
            $collapsed = $this->getBackendUser()->uc['moduleData']['list']['containerExpanded'][$collapseId] > 0;
        } else {
            $collapsed = (bool)($col['collapsed'] ?? false);
        }
        $columnObject->setCollapsed($collapsed);
    }
}
