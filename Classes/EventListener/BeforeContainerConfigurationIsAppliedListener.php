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

use B13\Container\Events\BeforeContainerConfigurationIsAppliedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

class BeforeContainerConfigurationIsAppliedListener
{
    #[AsEventListener('collapsible-container-beforecontainer', BeforeContainerConfigurationIsAppliedEvent::class)]
    public function __invoke(BeforeContainerConfigurationIsAppliedEvent $event): void
    {
        $event->getContainerConfiguration()->addGridPartialPath(
            'EXT:ew_collapsible_container/Resources/Private/Partials/'
        );
    }
}
