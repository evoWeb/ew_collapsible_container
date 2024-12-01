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

namespace Evoweb\EwCollapsibleContainer\EventListener;

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColum;
use B13\Container\Backend\Grid\ContainerGridColumnItem;
use B13\Container\Events\BeforeContainerPreviewIsRenderedEvent;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;

class BeforeContainerPreviewIsRenderedListener
{
    public function __construct(protected PageRenderer $pageRenderer)
    {
    }

    #[AsEventListener('collapsible-container-beforepreview', BeforeContainerPreviewIsRenderedEvent::class)]
    public function __invoke(BeforeContainerPreviewIsRenderedEvent $event): void
    {
        $record = $event->getItem()->getRecord();

        /** @var ContainerGridColumn $column */
        foreach ($event->getGrid()->getColumns() as $column) {
            $countOfHiddenItems = $this->getCountOfHiddenItems($column);
            $column->setOverride([
                'countOfHiddenItems' => $countOfHiddenItems,
                'collapsed' => $this->getColumnCollapsed((int)$record['uid'], $column),
                'showMinItemsWarning' => $this->getShowMinItemsWarning($column, $countOfHiddenItems)
            ]);
        }

        $this->addFrontendResources();
    }

    protected function getCountOfHiddenItems(ContainerGridColumn $columnObject): int
    {
        return count(
            array_filter(
                $columnObject->getItems(),
                fn (ContainerGridColumnItem $item) => ($item->getRecord()['hidden'] ?? 0) > 0
            )
        );
    }

    protected function getColumnCollapsed(int $recordUid, ContainerGridColumn $columnObject): bool
    {
        $backendUser = $this->getBackendUser();
        $collapseId = $recordUid
            . BaseContainerGridColum::CONTAINER_COL_POS_DELIMITER
            . $columnObject->getColumnNumber();
        if (isset($backendUser->uc['moduleData']['list']['containerExpanded'][$collapseId])) {
            $collapsed = $backendUser->uc['moduleData']['list']['containerExpanded'][$collapseId] > 0;
        } else {
            $collapsed = (bool)($columnObject->getDefinition()['collapsed'] ?? false);
        }
        return $collapsed;
    }

    protected function getShowMinItemsWarning(ContainerGridColumn $columnObject, int $hiddenItemCount): bool
    {
        $itemCount = count($columnObject->getItems());
        $minItems = (int)($columnObject->getDefinition()['minitems'] ?? 0);
        return $itemCount > 0 && ($itemCount - $hiddenItemCount) < $minItems;
    }

    protected function addFrontendResources(): void
    {
        $this->pageRenderer->addCssFile('EXT:ew_collapsible_container/Resources/Public/Css/container.css');
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@evoweb/ew-collapsible-container/container.js')
        );
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
