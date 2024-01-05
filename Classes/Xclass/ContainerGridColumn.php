<?php

declare(strict_types=1);

namespace Evoweb\EwCollapsibleContainer\Xclass;

/*
 * This file is part of TYPO3 CMS-based extension "ew_collapsible_container" by evoweb.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;
use B13\Container\Domain\Model\Container;
use TYPO3\CMS\Backend\View\PageLayoutContext;

class ContainerGridColumn extends BaseContainerGridColumn
{
    public function __construct(
        PageLayoutContext $context,
        array $columnDefinition,
        Container $container,
        int $newContentElementAtTopTarget,
        bool $allowNewContentElements = true,
        protected bool $collapsed = false
    ) {
        parent::__construct(
            $context,
            $columnDefinition,
            $container,
            $newContentElementAtTopTarget,
            $allowNewContentElements
        );
    }

    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }
}
