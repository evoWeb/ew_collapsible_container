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

namespace Evoweb\EwCollapsibleContainer\Event;

use B13\Container\Domain\Model\Container;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Core\View\ViewInterface;

final class BeforeContainerPreviewIsRenderedEvent14
{
    public function __construct(
        protected Container $container,
        protected ViewInterface $view,
        protected Grid $grid
    ) {
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }

    public function getGrid(): Grid
    {
        return $this->grid;
    }
}
