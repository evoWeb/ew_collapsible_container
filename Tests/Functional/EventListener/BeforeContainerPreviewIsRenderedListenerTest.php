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

use B13\Container\Backend\Grid\ContainerGridColumn;
use B13\Container\Backend\Grid\ContainerGridColumnItem;
use B13\Container\Domain\Factory\Database;
use B13\Container\Domain\Model\Container;
use B13\Container\Events\BeforeContainerPreviewIsRenderedEvent;
use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use Evoweb\EwCollapsibleContainer\EventListener\BeforeContainerPreviewIsRenderedListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Fluid\View\FluidViewFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BeforeContainerPreviewIsRenderedListenerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/ew_collapsible_container',
    ];

    private BackendUserAuthentication $backendUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->configureTCA();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
    }

    protected function configureTCA(): void
    {
        $configuration = new ContainerConfiguration(
            'test-container',
            'CType.I.test-container',
            'CType.I.test-container-plus_wiz_description',
            [
                [
                    [
                        'name' => 'Elements',
                        'colPos' => 200,
                        'allowed' => [
                            'CType' => 'test-child',
                        ],
                    ]
                ]
            ]
        );

        $configuration->setGroup('ew_fischer');
        $configuration->setIcon('content-card-group');

        $this->get(Registry::class)->configureContainer($configuration);

        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['test-container'] = 'content-card-group';
    }

    protected function getContentRecords(string $field, int $uid): array|RecordInterface
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $record = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq($field, $uid))
            ->executeQuery()
            ->fetchAssociative();

        $computedProperties = new ComputedProperties(
            $record['_ORIG_uid'] ?? null,
            $record['_LOCALIZED_UID'] ?? null,
            $record['_REQUESTED_OVERLAY_LANGUAGE'] ?? null,
            $record['_TRANSLATION_SOURCE'] ?? null
        );
        return new RawRecord($record['uid'], $record['pid'], $record, $computedProperties, 'tt_content');
    }

    protected function getBeforeContainerPreviewIsRenderedEvent(
        RecordInterface $record
    ): BeforeContainerPreviewIsRenderedEvent {
        $request = $this->getReuqest();
        if (class_exists(PageContext::class)) {
            $pageContext = $this->getPageContext($request);
            $context = new PageLayoutContext(
                $pageContext,
                new BackendLayout('', '', []),
                new DrawingConfiguration(),
                $request,
            );
        } else {
            $context = new PageLayoutContext(
                [],
                new BackendLayout('', '', []),
                new Site('test', 1, []),
                new DrawingConfiguration(),
                $request,
            );
            $record = $record->toArray();
        }

        $item = new GridColumnItem($context, (new GridColumn($context, [])), $record);
        $grid = new Grid($context);

        $language = (int)(is_array($record) ? $record['sys_language_uid'] : $record->get('sys_language_uid'));
        /** @var Database $database */
        $database = $this->get(Database::class);
        $children = $database->fetchRecordsByParentAndLanguage(
            (int)(is_array($record) ? $record['uid'] : $record->getUid()),
            $language
        );
        $childRecordByColPosKey = [];
        foreach ($children as $child) {
            if (empty($childRecordByColPosKey[$child['colPos']])) {
                $childRecordByColPosKey[$child['colPos']] = [];
            }
            $childRecordByColPosKey[$child['colPos']][] = $child;
        }

        $container = GeneralUtility::makeInstance(
            Container::class,
            is_array($record) ? $record : $record->toArray(),
            $childRecordByColPosKey,
            $language,
        );

        $containerGrid = $this->get(Registry::class)->getGrid(
            is_array($record) ? $record['CType'] : $record->get('CType')
        );
        foreach ($containerGrid as $cols) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $context);
            foreach ($cols as $col) {
                $columnObject = GeneralUtility::makeInstance(
                    ContainerGridColumn::class,
                    $context,
                    $col,
                    $container,
                    '',
                    false
                );
                $rowObject->addColumn($columnObject);
                if (isset($col['colPos'])) {
                    $records = $container->getChildrenByColPos($col['colPos']);
                    foreach ($records as $contentRecord) {
                        $columnItem = GeneralUtility::makeInstance(
                            ContainerGridColumnItem::class,
                            $context,
                            $columnObject,
                            $contentRecord,
                            $container,
                            ''
                        );
                        $columnObject->addItem($columnItem);
                    }
                }
            }
            $grid->addRow($rowObject);
        }

        $viewFactoryData = new ViewFactoryData();
        $viewFactory = $this->get(FluidViewFactory::class);
        $view = $viewFactory->create($viewFactoryData);
        return new BeforeContainerPreviewIsRenderedEvent($container, $view, $grid, $item);
    }

    #[Test]
    public function getCountOfHiddenItems(): void
    {
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);

        $subject = new BeforeContainerPreviewIsRenderedListener($this->get(PageRenderer::class));
        $subject->__invoke($event);

        $definition = $event->getGrid()->getColumns()[200]->getDefinition();
        $this->assertEquals(1, $definition['countOfHiddenItems']);
    }

    public static function getCollapsedProvider(): array
    {
        return [
            'falseIsDefault' => [ false ],
            'trueIsDefault' => [ true ],
        ];
    }

    #[Test]
    #[DataProvider('getCollapsedProvider')]
    public function getCollapsed(bool $state): void
    {
        $GLOBALS['TCA']['tt_content']['containerConfiguration']['test-container']['grid'][0][0]['collapsed'] = $state;
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);
        if ($event === null) {
            $this->markTestSkipped('StandaloneView is not available');
        }

        $subject = new BeforeContainerPreviewIsRenderedListener($this->get(PageRenderer::class));
        $subject->__invoke($event);

        $definition = $event->getGrid()->getColumns()[200]->getDefinition();
        $this->assertEquals($state, $definition['collapsed']);
    }

    public static function showMinItemsProvider(): array
    {
        return [
            'minItemsIsHigherThenAvailableItems' => [3, true],
            'minItemsIsNotHigherThenAvailableItems' => [2, false],
        ];
    }

    #[Test]
    #[DataProvider('showMinItemsProvider')]
    public function getShowMinItemsWarning(int $minitems, bool $expected): void
    {
        $GLOBALS['TCA']['tt_content']['containerConfiguration']['test-container']['grid'][0][0]['minitems'] = $minitems;
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);
        if ($event === null) {
            $this->markTestSkipped('StandaloneView is not available');
        }

        $subject = new BeforeContainerPreviewIsRenderedListener($this->get(PageRenderer::class));
        $subject->__invoke($event);

        $definition = $event->getGrid()->getColumns()[200]->getDefinition();
        $this->assertEquals($expected, $definition['showMinItemsWarning']);
    }

    #[Test]
    public function addFrontendResourcesAddJavascriptAndStylesheets(): void
    {
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);
        if ($event === null) {
            $this->markTestSkipped('StandaloneView is not available');
        }

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = $this->get(PageRenderer::class);

        $subject = new BeforeContainerPreviewIsRenderedListener($pageRenderer);
        $subject->__invoke($event);

        $reflectedClass = new \ReflectionClass($pageRenderer);
        $property = $reflectedClass->getProperty('cssFiles');

        $arrayValuesHasSubstring = 0 < count(
            array_filter(
                $property->getValue($pageRenderer),
                function ($value) {
                    return str_contains($value['file'], 'Resources/Public/Css/container.css');
                }
            )
        );

        $this->assertTrue($arrayValuesHasSubstring);

        $moduleName = '@evoweb/ew-collapsible-container/container.js';
        $javascriptInstruction = array_map(
            fn (array $item) => $item['payload']->getName() === $moduleName ? $moduleName : '',
            $pageRenderer->getJavaScriptRenderer()->toArray()
        );

        $this->assertContains($moduleName, $javascriptInstruction);
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
