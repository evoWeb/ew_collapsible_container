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

use B13\Container\Events\BeforeContainerPreviewIsRenderedEvent;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RecordInterface;

class BeforeContainerPreviewIsRenderedListener
{
    #[AsEventListener('collapsible-container-beforepreview')]
    public function processPre14(BeforeContainerPreviewIsRenderedEvent $event): void
    {
        $this->processGridColumns($event->getContainer()->getContainerRecord(), $event->getGrid());
    }

    /**
     * @param array<string, string|int|float|null> $record
     */
    protected function processGridColumns(array $record, Grid $grid): void
    {
        /** @var ContainerGridColumn $column */
        foreach ($grid->getColumns() as $column) {
            $countOfHiddenItems = $this->getCountOfHiddenItems($column);
            $column->setOverride([
                'countOfHiddenItems' => $countOfHiddenItems,
                'collapsed' => $this->getColumnCollapsed((int)$record['uid'], $column),
                'showMinItemsWarning' => $this->getShowMinItemsWarning($column, $countOfHiddenItems),
            ]);
        }
    }

    protected function getCountOfHiddenItems(ContainerGridColumn $columnObject): int
    {
        return count(
            array_filter(
                iterator_to_array($columnObject->getItems()),
                function (GridColumnItem $item) {
                    /** @var RecordInterface|array<string, mixed> $record */
                    // @phpstan-ignore varTag.nativeType
                    $record = $item->getRecord();
                    /** @var array<string, mixed> $recordFields */
                    $recordFields = $record instanceof RecordInterface ? $record->getRawRecord()?->toArray() : $record;
                    return $recordFields['hidden'] > 0;
                }
            )
        );
    }

    protected function getColumnCollapsed(int $recordUid, ContainerGridColumn $columnObject): bool
    {
        $backendUser = $this->getBackendUser();
        $collapseId = $recordUid . '-' . $columnObject->getColumnNumber();
        if (isset($backendUser->uc['moduleData']['list']['containerExpanded'][$collapseId])) {
            $collapsed = $backendUser->uc['moduleData']['list']['containerExpanded'][$collapseId] > 0;
        } else {
            $collapsed = (bool)($columnObject->getDefinition()['collapsed'] ?? false);
        }
        return $collapsed;
    }

    protected function getShowMinItemsWarning(ContainerGridColumn $columnObject, int $hiddenItemCount): bool
    {
        $itemCount = count(iterator_to_array($columnObject->getItems()));
        $minItems = (int)($columnObject->getDefinition()['minitems'] ?? 0);
        return $itemCount > 0 && ($itemCount - $hiddenItemCount) < $minItems;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        /** @var BackendUserAuthentication $user */
        $user = $GLOBALS['BE_USER'];
        return $user;
    }
}
