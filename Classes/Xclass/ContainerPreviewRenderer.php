<?php

declare(strict_types=1);

namespace Evoweb\EwCollapsibleContainer\Xclass;

/*
 * This file is part of TYPO3 CMS-based extension "ew_collapsible_container" by evoweb.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Backend\Preview\ContainerPreviewRenderer as BaseContainerPreviewRenderer;
use B13\Container\Backend\Grid\ContainerGridColumn;
use B13\Container\Backend\Grid\ContainerGridColumnItem;
use B13\Container\Domain\Factory\Exception;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;

class ContainerPreviewRenderer extends BaseContainerPreviewRenderer
{
    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $content = StandardContentPreviewRenderer::renderPageModulePreviewContent($item);
        $context = $item->getContext();
        $record = $item->getRecord();
        $grid = GeneralUtility::makeInstance(Grid::class, $context);
        try {
            $container = $this->containerFactory->buildContainer((int)$record['uid']);
        } catch (Exception $e) {
            // not a container
            return $content;
        }
        $containerGrid = $this->tcaRegistry->getGrid($record['CType']);
        foreach ($containerGrid as $row => $cols) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $context);
            foreach ($cols as $col) {
                $newContentElementAtTopTarget = $this->containerService->getNewContentElementAtTopTargetInColumn($container, $col['colPos']);
                $allowNewContentElements = !$this->containerColumnConfigurationService->isMaxitemsReached($container, $col['colPos']);
                $collapsed = $this->getColumnCollapsedState((int)$record['uid'], (int)$col['colPos'], $col);
                $columnObject = GeneralUtility::makeInstance(
                    ContainerGridColumn::class,
                    $context,
                    $col,
                    $container,
                    $newContentElementAtTopTarget,
                    $allowNewContentElements,
                    $collapsed
                );
                $rowObject->addColumn($columnObject);
                if (isset($col['colPos'])) {
                    $records = $container->getChildrenByColPos($col['colPos']);
                    foreach ($records as $contentRecord) {
                        $columnItem = GeneralUtility::makeInstance(ContainerGridColumnItem::class, $context, $columnObject, $contentRecord, $container);
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

        $view->assign('hideRestrictedColumns', (bool)(BackendUtility::getPagesTSconfig($context->getPageId())['mod.']['web_layout.']['hideRestrictedCols'] ?? false));
        $view->assign('newContentTitle', $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newContentElement'));
        $view->assign('newContentTitleShort', $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content'));
        $view->assign('allowEditContent', $this->getBackendUser()->check('tables_modify', 'tt_content'));
        $view->assign('containerGrid', $grid);
        $view->assign('containerRecord', $record);
        $rendered = $view->render();

        return $content . $rendered;
    }

    protected function getColumnCollapsedState(int $recordUid, int $colPos, array $col): bool
    {
        $collapseId = $recordUid . ContainerGridColumn::CONTAINER_COL_POS_DELIMITER_V12 . $colPos;
        if (isset($this->getBackendUser()->uc['moduleData']['list']['containerExpanded'][$collapseId])) {
            $collapsed = $this->getBackendUser()->uc['moduleData']['list']['containerExpanded'][$collapseId] > 0;
        } else {
            $collapsed = (bool)($col['collapsed'] ?? false);
        }
        return $collapsed;
    }
}
