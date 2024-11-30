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

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;

class ContainerGridColumn extends BaseContainerGridColumn
{
    protected array $override = [];

    public function setOverride(array $override): void
    {
        $this->override = $override;
    }

    public function getDefinition(): array
    {
        return array_merge($this->definition, $this->override);
    }
}
