<?php

namespace RUB\LocalizationDemoExternalModule;

require_once "em-i18n-polyfill/em-i18n-polyfill.php";

use ExternalModules\TranslatableExternalModule;

/**
 * ExternalModule class for Localization Demo.
 * This demo module will get a string and print it to the browser's
 * console as info, warning, or error, depending on the module's 
 * settings.
 */
class LocalizationDemoExternalModule extends TranslatableExternalModule {


    function __construct() {
        parent::__construct("English");
    }

    function redcap_every_page_top($project_id = null) {

        // 
        $name = $this->tt("module_name");

    }

}