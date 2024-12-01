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
use B13\Container\Domain\Factory\Exception;
use B13\Container\Domain\Factory\PageView\Backend\ContainerFactory;
use B13\Container\Events\BeforeContainerPreviewIsRenderedEvent;
use B13\Container\Tca\ContainerConfiguration;
use B13\Container\Tca\Registry;
use Evoweb\EwCollapsibleContainer\EventListener\BeforeContainerPreviewIsRenderedListener;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BeforeContainerPreviewIsRenderedListenerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/ew_collapsible_container',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->configureTCA();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tt_content.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
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
                        'collapsed' => true,
                    ]
                ]
            ]
        );

        $configuration->setGroup('ew_fischer');
        $configuration->setIcon('content-card-group');

        $this->get(Registry::class)->configureContainer($configuration);

        $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['test-container'] = 'content-card-group';
    }

    protected function getContentRecords(string $field, int $uid): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq($field, $uid))
            ->executeQuery()
            ->fetchAssociative();
    }

    protected function getBeforeContainerPreviewIsRenderedEvent(array $record): ?BeforeContainerPreviewIsRenderedEvent
    {
        $context = new PageLayoutContext(
            [],
            new BackendLayout('', '', []),
            new Site('test', 1, []),
            new DrawingConfiguration(),
            $this->get(ServerRequestFactory::class)->createServerRequest('GET', '/'),
        );
        $item = new GridColumnItem($context, (new GridColumn($context, [])), $record);
        $grid = new Grid($context);

        try {
            $containerFactory = $this->get(ContainerFactory::class);
            $container = $containerFactory->buildContainer((int)$record['uid']);
        } catch (Exception $e) {
            // not a container
            return null;
        }

        /** @var Registry $tcaRegistry */
        $tcaRegistry = $this->get(Registry::class);
        $containerGrid = $tcaRegistry->getGrid($record['CType']);
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

        /** @var StandaloneView $view */
        $view = $this->get(StandaloneView::class);

        return new BeforeContainerPreviewIsRenderedEvent($container, $view, $grid, $item);
    }

    #[Test]
    public function getCountOfHiddenItems(): void
    {
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = $this->get(PageRenderer::class);

        $subject = new BeforeContainerPreviewIsRenderedListener($pageRenderer);
        $subject->__invoke($event);

        $columns = $event->getGrid()->getColumns();
    }

    #[Test]
    public function addFrontendResourcesAddJavascriptAndStylesheets(): void
    {
        $containerRecord = $this->getContentRecords('tx_container_parent', 0);
        $event = $this->getBeforeContainerPreviewIsRenderedEvent($containerRecord);

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = $this->get(PageRenderer::class);

        $subject = new BeforeContainerPreviewIsRenderedListener($pageRenderer);
        $subject->__invoke($event);

        $reflectedClass = new \ReflectionClass($pageRenderer);
        $property = $reflectedClass->getProperty('cssFiles');

        $this->assertArrayHasKey(
            'EXT:ew_collapsible_container/Resources/Public/Css/container.css',
            $property->getValue($pageRenderer)
        );

        $moduleName = '@evoweb/ew-collapsible-container/container.js';
        $javascriptInstruction = array_map(
            fn (array $item) => $item['payload']->getName() === $moduleName ? $moduleName : '',
            $pageRenderer->getJavaScriptRenderer()->toArray()
        );

        $this->assertContains($moduleName, $javascriptInstruction);
    }
}
