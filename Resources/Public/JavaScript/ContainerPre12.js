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
class ContainerToggle {
  containerColumnToggle = '.t3js-toggle-container-column';

  persistentStorage = null;

  storageKey = 'moduleData.list.containerExpanded';

  constructor(PersistentStorage) {
    this.persistentStorage = PersistentStorage;
    this.initializeContainerToggle();
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

  toggleClicked(event) {
    event.preventDefault();

    let column = event.currentTarget,
      container = column.closest('td').dataset['colpos'],
      isExpanded = column.dataset['collapseState'] === 'expanded';

    // Store collapse state in UC
    let storedModuleDataList = {};

    if (this.persistentStorage.isset(this.storageKey)) {
      storedModuleDataList = this.persistentStorage.get(this.storageKey);
    }

    let expandConfig = {};
    expandConfig[container] = isExpanded ? '1' : '0';

    storedModuleDataList = Object.assign(storedModuleDataList, expandConfig);

    this.persistentStorage.set(this.storageKey, storedModuleDataList).then(() => {
      if (isExpanded) {
        column.closest('td').classList.add('collapsed');
      } else {
        column.closest('td').classList.remove('collapsed');
      }
    });
  }
}

define(
  [
    'TYPO3/CMS/Backend/Storage/Persistent'
  ],
  function(PersistentStorage) {
    return new ContainerToggle(PersistentStorage);
  }
);
