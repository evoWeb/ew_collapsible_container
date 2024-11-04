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

use B13\Container\Backend\Grid\ContainerGridColumn;
use Evoweb\EwCollapsibleContainer\Xclass\BeforeContainerPreviewIsRenderedEvent;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn as BaseContainerGridColumn;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class BeforeContainerPreviewIsRenderedListener
{
    #[AsEventListener('collapsible-container-beforepreview', BeforeContainerPreviewIsRenderedEvent::class)]
    public function __invoke(BeforeContainerPreviewIsRenderedEvent $event): void
    {
        $grid = $event->getGrid();
        $record = $event->getItem()->getRecord();

        /** @var ContainerGridColumn $columnObject */
        foreach ($grid->getColumns() as $columnObject) {
            $this->setColumnCollapsedState((int)$record['uid'], $columnObject);
        }
    }

    protected function setColumnCollapsedState(int $recordUid, BaseContainerGridColumn $columnObject): void
    {
        $backendUser = $this->getBackendUser();
        $collapseId = $recordUid . ContainerGridColumn::CONTAINER_COL_POS_DELIMITER . $columnObject->getColumnNumber();
        if (isset($backendUser->uc['moduleData']['list']['containerExpanded'][$collapseId])) {
            $collapsed = $backendUser->uc['moduleData']['list']['containerExpanded'][$collapseId] > 0;
        } else {
            $collapsed = (bool)($columnObject->getDefinition()['collapsed'] ?? false);
        }
        $columnObject->setCollapsed($collapsed);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
