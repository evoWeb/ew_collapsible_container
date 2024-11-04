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

use B13\Container\Backend\Grid\ContainerGridColumnItem;
use B13\Container\Backend\Grid\ContainerGridColumn as BaseContainerGridColumn;
use B13\Container\Domain\Model\Container;
use TYPO3\CMS\Backend\View\PageLayoutContext;

class ContainerGridColumn extends BaseContainerGridColumn
{
    protected bool $collapsed = false;

    protected int $minItems = 0;

    public function __construct(
        PageLayoutContext $context,
        array $columnDefinition,
        Container $container,
        ?string $newContentUrl,
        bool $skipNewContentElementWizard
    ) {
        parent::__construct(
            $context,
            $columnDefinition,
            $container,
            $newContentUrl,
            $skipNewContentElementWizard
        );
        $this->minItems = (int)($columnDefinition['minitems'] ?? 0);
    }

    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    public function hasShowMinItemsWarning(): bool
    {
        return count($this->items) > 0
            && (count($this->items) - $this->getHiddenItemCount()) < $this->getMinItems();
    }

    public function getMinItems(): int
    {
        return $this->minItems;
    }

    public function getHiddenItemCount(): int
    {
        return count(
            array_filter(
                $this->items,
                fn (ContainerGridColumnItem $item) => ($item->getRecord()['hidden'] ?? 0) > 0
            )
        );
    }
}
