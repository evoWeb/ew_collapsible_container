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

use B13\Container\Events\BeforeContainerConfigurationIsAppliedEvent;
use B13\Container\Tca\ContainerConfiguration;
use Evoweb\EwCollapsibleContainer\EventListener\BeforeContainerConfigurationIsAppliedListener;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BeforeContainerConfigurationIsAppliedListenerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/container',
        'typo3conf/ext/ew_collapsible_container',
    ];

    #[Test]
    public function invokeAddsPartial(): void
    {
        $fixture = new ContainerConfiguration('a', 'a', 'a', []);
        $event = new BeforeContainerConfigurationIsAppliedEvent($fixture);

        $subject = new BeforeContainerConfigurationIsAppliedListener();
        $subject->__invoke($event);

        $this->assertContains(
            'EXT:ew_collapsible_container/Resources/Private/Partials/',
            $event->getContainerConfiguration()->getGridPartialPaths()
        );
    }
}
