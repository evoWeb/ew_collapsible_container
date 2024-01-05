# TYPO3 Extending extbase domain models

![build](https://github.com/evoWeb/ew-collapsible-container/workflows/build/badge.svg?branch=develop)
[![Latest Stable Version](https://poser.pugx.org/evoweb/ew-collapsible-container/v/stable)](https://packagist.org/packages/evoweb/ew-collapsible-container)
[![Monthly Downloads](https://poser.pugx.org/evoweb/ew-collapsible-container/d/monthly)](https://packagist.org/packages/evoweb/ew-collapsible-container)
[![Total Downloads](https://poser.pugx.org/evoweb/ew-collapsible-container/downloads)](https://packagist.org/packages/evoweb/ew-collapsible-container)

## Installation

### via Composer

The recommended way to install ew_collapsible_container is by using [Composer](https://getcomposer.org):

    composer require evoweb/ew-collapsible-container

### quick introduction

Adds ability to collapse a container in backend to get children out of the way.

Add 'collapsed' to column definition when registering ContainerConfiguration to collapse elements initially.
```php
$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \B13\Container\Tca\ContainerConfiguration::class,
    'demo_container',
    $languageFile . 'CType.I.demo_container',
    $languageFile . 'CType.I.demo_container-plus_wiz_description',
    [
        [
            [
                'name' => 'Elements',
                'colPos' => 200,
                'allowed' => ['CType' => 'kwicks_element'],
                'collapsed' => true,
            ]
        ]
    ]
);
```

Add setTemplate to disable ability to collapse for defined container elements.
```php
$configuration->setGridTemplate('EXT:container/Resources/Private/Templates/Grid.html');
```
