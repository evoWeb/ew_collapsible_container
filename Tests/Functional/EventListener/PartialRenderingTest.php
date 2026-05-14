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

namespace Evoweb\EwCollapsibleContainer\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PartialRenderingTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/ew_collapsible_container',
    ];

    public function renderPartial(): void
    {
        $templateFile = 'EXT:ew_collapsible_container/Resources/Private/Partials/PageLayout/Grid/ColumnHeader.html';
        /** @var ViewFactoryInterface $viewFactory */
        $viewFactory = $this->get(ViewFactoryInterface::class);
        $view = $viewFactory->create(new ViewFactoryData(templatePathAndFilename: $templateFile));

        $view->assignMultiple([
            'column' => [
                'active' => false,
                'beforeSectionMarkup' => '',
                'newContentUrl' => '',
                'definition' => [
                    'showMinItemsWarning' => false,
                    'countOfHiddenItems' => 0,
                    'minitems' => 0,
                ],
            ],
            'allowEditContent' => false,
            'columnHeaderLevel' => 2,
        ]);
        $view->render();
    }

    #[Test]
    public function addFrontendResourcesAddJavascriptAndStylesheets(): void
    {
        $this->renderPartial();

        /** @var AssetCollector $assetCollector */
        $assetCollector = $this->get(AssetCollector::class);
        self::assertArrayHasKey('collapsible-css', $assetCollector->getStyleSheets());
        self::assertContains('@evoweb/ew-collapsible-container/container.js', $assetCollector->getJavaScriptModules());
    }
}
