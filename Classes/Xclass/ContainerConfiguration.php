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

use B13\Container\Tca\ContainerConfiguration as BaseContainerConfiguration;

class ContainerConfiguration extends BaseContainerConfiguration
{
    public function __construct(
        string $cType,
        string $label,
        string $description,
        array $grid
    ) {
        parent::__construct($cType, $label, $description, $grid);
        $this->setGridTemplate('EXT:ew_collapsible_container/Resources/Private/Templates/Container/Grid.html');
        $this->addGridPartialPath('EXT:ew_collapsible_container/Resources/Private/Partials/');
    }
}
