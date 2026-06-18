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
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Fluid\View\FluidViewFactory;
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
        /** @var LanguageServiceFactory $languageServiceFactory */
        $languageServiceFactory = $this->get(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($this->backendUser);
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
                    ],
                ],
            ]
        );

        $configuration->setGroup('ew_fischer');
        $configuration->setIcon('content-card-group');

        /** @var Registry $registry */
        $registry = $this->get(Registry::class);
        $registry->configureContainer($configuration);

        /** @var array<string, array<string, array<string, array<string, string>>>> $tca */
        $tca = & $GLOBALS['TCA'];
        $tca['tt_content']['ctrl']['typeicon_classes']['test-container'] = 'content-card-group';
    }

    protected function getContentRecords(string $field, int $uid): RecordInterface
    {
        $table = 'tt_content';
        /** @var ConnectionPool $connectionPool */
        $connectionPool = $this->get(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        /** @var array<string, string|int|float|null>|false $row */
        $row = $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq($field, $uid))
            ->executeQuery()
            ->fetchAssociative();
        assert($row !== false, 'Content record not found');

        /** @var RecordFactory $recordFactory */
        $recordFactory = $this->get(RecordFactory::class);
        return $recordFactory->createFromDatabaseRow($table, $row);
    }

    protected function getBeforeContainerPreviewIsRenderedEvent(
        RecordInterface $record
    ): BeforeContainerPreviewIsRenderedEvent {
        $request = $this->getRequest();
        if (class_exists(PageContext::class)) {
            $pageContext = $this->getPageContext($request);
            $context = new PageLayoutContext(
                $pageContext,
                new BackendLayout('', '', []),
                new DrawingConfiguration(),
                $request,
            );
        } else {
            // v13.4 PageLayoutContext has different signature
            // @phpstan-ignore arguments.count
            $context = new PageLayoutContext(
                // @phpstan-ignore argument.type
                [],
                new BackendLayout('', '', []),
                // @phpstan-ignore argument.type
                new Site('test', 1, []),
                // @phpstan-ignore argument.type
                new DrawingConfiguration(),
                $request,
            );
        }

        $grid = new Grid($context);

        /** @var array<string, string|int|float|null> $rawRecord */
        $rawRecord = $record->getRawRecord()?->toArray();

        $language = (int)$rawRecord['sys_language_uid'];
        /** @var Database $database */
        $database = $this->get(Database::class);
        $children = $database->fetchRecordsByParentAndLanguage($record->getUid(), $language);
        $childRecordByColPosKey = [];
        foreach ($children as $child) {
            if (empty($childRecordByColPosKey[$child['colPos']])) {
                $childRecordByColPosKey[$child['colPos']] = [];
            }
            $childRecordByColPosKey[$child['colPos']][] = $child;
        }

        $container = GeneralUtility::makeInstance(
            Container::class,
            $record->toArray(),
            $childRecordByColPosKey,
            $language,
        );

        /** @var Registry $registry */
        $registry = $this->get(Registry::class);
        $containerGrid = $registry->getGrid((string)$rawRecord['CType']);
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
                            $registry,
                            $container,
                            ''
                        );
                        $columnObject->addItem($columnItem);
                    }
                }
            }
            $grid->addRow($rowObject);
        }

        /** @var FluidViewFactory $viewFactory */
        $viewFactory = $this->get(FluidViewFactory::class);
        $view = $viewFactory->create(new ViewFactoryData());
        return new BeforeContainerPreviewIsRenderedEvent($container, $view, $grid);
    }

    #[Test]
    public function getCountOfHiddenItems(): void
    {
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);

        $subject = new BeforeContainerPreviewIsRenderedListener();
        if ($event instanceof BeforeContainerPreviewIsRenderedEvent) {
            $subject->processPre14($event);
        } else {
            $subject->processFor14($event);
        }

        $definition = iterator_to_array($event->getGrid()->getColumns())[200]->getDefinition();
        self::assertEquals(1, $definition['countOfHiddenItems']);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function getCollapsedProvider(): iterable
    {
        yield 'falseIsDefault' => [false];

        yield 'trueIsDefault' => [true];
    }

    #[Test]
    #[DataProvider('getCollapsedProvider')]
    public function getCollapsed(bool $state): void
    {
        /** @var array<string, array<string, array<string, array<string, array<int, array<int, array<string, bool>>>>>>> $tca */
        $tca = & $GLOBALS['TCA'];
        $tca['tt_content']['containerConfiguration']['test-container']['grid'][0][0]['collapsed'] = $state;
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);

        $subject = new BeforeContainerPreviewIsRenderedListener();
        if ($event instanceof BeforeContainerPreviewIsRenderedEvent) {
            $subject->processPre14($event);
        } else {
            $subject->processFor14($event);
        }

        $definition = iterator_to_array($event->getGrid()->getColumns())[200]->getDefinition();
        self::assertEquals($state, $definition['collapsed']);
    }

    /**
     * @return iterable<string, array{bool|int}>
     */
    public static function showMinItemsProvider(): iterable
    {
        yield 'minItemsIsHigherThenAvailableItems' => [3, true];

        yield 'minItemsIsNotHigherThenAvailableItems' => [2, false];
    }

    #[Test]
    #[DataProvider('showMinItemsProvider')]
    public function getShowMinItemsWarning(int $minItems, bool $expected): void
    {
        /** @var array<string, array<string, array<string, array<string, array<int, array<int, array<string, int>>>>>>> $tca */
        $tca = & $GLOBALS['TCA'];
        $tca['tt_content']['containerConfiguration']['test-container']['grid'][0][0]['minitems'] = $minItems;
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);

        $subject = new BeforeContainerPreviewIsRenderedListener();
        if ($event instanceof BeforeContainerPreviewIsRenderedEvent) {
            $subject->processPre14($event);
        } else {
            $subject->processFor14($event);
        }

        $definition = iterator_to_array($event->getGrid()->getColumns())[200]->getDefinition();
        self::assertEquals($expected, $definition['showMinItemsWarning']);
    }

    private function getPageContext(ServerRequestInterface $request): PageContext
    {
        /** @var PageContextFactory $pageContextFactory */
        $pageContextFactory = $this->get(PageContextFactory::class);
        return $pageContextFactory->createFromRequest($request, 1, $this->backendUser);
    }

    private function getRequest(): ServerRequestInterface
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
