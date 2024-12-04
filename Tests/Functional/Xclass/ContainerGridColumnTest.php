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

use B13\Container\Domain\Model\Container;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ContainerGridColumnTest extends FunctionalTestCase
{
    #[Test]
    public function overrideIsPartOfDefinition(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $pageLayoutContext = new PageLayoutContext(
            [],
            new BackendLayout('', '', []),
            new Site('test', 1, []),
            new DrawingConfiguration(),
            $this->get(ServerRequestFactory::class)->createServerRequest('GET', '/'),
        );

        $container = new Container([], [], 0);

        $subject = new ContainerGridColumn(
            $pageLayoutContext,
            [
                'colPos' => 200,
            ],
            $container,
            '',
            false
        );

        $subject->setOverride([
            'countOfHiddenItems' => 0,
            'collapsed' => false,
            'showMinItemsWarning' => false,
        ]);

        $this->assertArrayHasKey('countOfHiddenItems', $subject->getDefinition());
    }
}
