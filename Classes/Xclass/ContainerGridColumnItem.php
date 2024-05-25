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

use B13\Container\Backend\Grid\ContainerGridColumnItem as BaseContainerGridColumnItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContainerGridColumnItem extends BaseContainerGridColumnItem
{
    public function getNewContentAfterUrl(): string
    {
        if (!($this->column->getDefinition()['allowDirectNewLink'] ?? false)) {
            return parent::getNewContentAfterUrl();
        }

        $urlParameters = [
            'edit' => [
                'tt_content' => [
                    -$this->record['uid'] => 'new',
                ],
            ],
            'defVals' => [
                'tt_content' => [
                    'colPos' => $this->column->getColumnNumber(),
                    // @extensionScannerIgnoreLine
                    'sys_language_uid' => $this->container->getLanguage(),
                    'tx_container_parent' => $this->container->getUidOfLiveWorkspace(),
                    'uid_pid' => -$this->record['uid'],
                ],
            ],
            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
        ];
        $routeName = 'record_edit';

        $allowed = $this->column->getDefinition()['allowed'] ?? [];
        if (!empty($allowed)) {
            $cType = $allowed['CType'] ?? '';
            if ($cType) {
                $urlParameters['defVals']['tt_content']['CType'] = $cType;
            }

            $listType = $allowed['list_type'] ?? '';
            if ($listType) {
                $urlParameters['defVals']['tt_content']['list_type'] = $listType;
            }
        }

        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($routeName, $urlParameters);
    }
}
