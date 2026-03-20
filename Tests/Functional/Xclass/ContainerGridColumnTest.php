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

namespace Evoweb\EwCollapsibleContainer\Tests\Functional\Xclass;

use B13\Container\Domain\Model\Container;
use Evoweb\EwCollapsibleContainer\Xclass\ContainerGridColumn;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ContainerGridColumnTest extends FunctionalTestCase
{
    private BackendUserAuthentication $backendUser;

    #[Test]
    public function overrideIsPartOfDefinition(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $request = $this->getReuqest();
        if (class_exists(PageContext::class)) {
            $pageContext = $this->getPageContext($request);
            $pageLayoutContext = new PageLayoutContext(
                $pageContext,
                new BackendLayout('', '', []),
                new DrawingConfiguration(),
                $request,
            );
        } else {
            $pageLayoutContext = new PageLayoutContext(
                [],
                new BackendLayout('', '', []),
                new Site('test', 1, []),
                new DrawingConfiguration(),
                $request
            );
        }

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

    private function getPageContext(ServerRequestInterface $request): PageContext
    {
        $pageContextFactory = $this->get(PageContextFactory::class);
        return $pageContextFactory->createFromRequest(
            $request,
            1,
            $this->backendUser
        );
    }

    private function getReuqest(): ServerRequestInterface
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $moduleData = new ModuleData('web_layout', []);
        $moduleData->set('languages', [0]);

        return (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('moduleData', $moduleData)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));
    }
}
