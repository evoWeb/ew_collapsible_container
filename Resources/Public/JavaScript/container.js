/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import PersistentStorage from '@typo3/backend/storage/persistent.js';

class ContainerToggle {
  containerColumnToggle = '.t3js-toggle-container-column';

  columnExpand = '.t3js-expand-column';

  persistentStorage = null;

  storageKey = 'moduleData.list.containerExpanded';

  constructor(PersistentStorage) {
    this.persistentStorage = PersistentStorage;
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        this.initialized();
      });
    } else {
      this.initialized();
    }
  }

  initialized() {
    this.initializeContainerToggle();
    this.initializeExpandColumn();
  }

  /**
   * initialize the toggle icons to open listings of nested grid container structure in the list module
   */
  initializeContainerToggle() {
    const containers = Array.from(document.querySelectorAll(this.containerColumnToggle));
    containers.forEach(container=> {
      container.addEventListener('click', event => this.toggleClicked(event));
    });
  }

  initializeExpandColumn() {
    const columnExpanders = Array.from(document.querySelectorAll(this.columnExpand));
    columnExpanders.forEach(columnExpander=> {
      columnExpander.addEventListener('click', event => this.expandClicked(event));
    });
  }

  toggleClicked(event) {
    event.preventDefault();

    let column = event.currentTarget,
      containerCell = column.closest('td'),
      colPos = parseInt(containerCell.dataset['colpos']),
      isExpanded = column.dataset['collapseState'] === 'expanded',
      storedModuleDataList = this.getCurrentModuleDataList(colPos, isExpanded);

    // Store collapse state in UC
    this.setStoredModuleDataList(storedModuleDataList).then(() => {
      if (isExpanded) {
        containerCell.classList.add('collapsed');
      } else {
        containerCell.classList.remove('collapsed');
      }
    });
  }

  expandClicked(event) {
    event.preventDefault();

    let expander = event.currentTarget,
      containerCell = expander.closest('td'),
      colPos = parseInt(containerCell.dataset['colpos']),
      isExpanded = false,
      storedModuleDataList = this.getCurrentModuleDataList(colPos, isExpanded);

    // Store collapse state in UC
    this.setStoredModuleDataList(storedModuleDataList).then(() => {
      containerCell.classList.remove('collapsed');
    });
  }

  /**
   * @param {number} colPos
   * @param {boolean} isExpanded
   *
   * @returns {object}
   */
  getCurrentModuleDataList(colPos, isExpanded) {
    let storedModuleDataList = {};

    if (this.persistentStorage.isset(this.storageKey)) {
      storedModuleDataList = this.persistentStorage.get(this.storageKey);
    }

    let collapseConfig = {};
    collapseConfig[colPos] = isExpanded ? '1' : '0';

    return Object.assign(storedModuleDataList, collapseConfig);
  }

  /**
   * @param {object} moduleData
   *
   * @returns {Promise}
   */
  setStoredModuleDataList(moduleData) {
    return this.persistentStorage.set(this.storageKey, moduleData);
  }
}

export default new ContainerToggle;
