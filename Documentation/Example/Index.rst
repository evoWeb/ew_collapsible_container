..  include:: /Includes.rst.txt
..  index:: Example
..  _example:

=======
Example
=======

..  code-block:: php
    :caption: Configuration/TCA/Overrides/tt_content.php

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
                    'minitems' => 1,
                    'maxitems' => 5,
                ]
            ]
        ]
    );

    $configuration->setGroup('ew_fischer');
    $configuration->setIcon('content-card-group');

    GeneralUtility::makeInstance(Registry::class)->configureContainer($configuration);
