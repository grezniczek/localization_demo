<?php
/**
 * Add support for using EM localization features even when running on REDCap version not supporting these features.
 * 
 * This file must be included before defining the module's class. 
 * Instead of extending AbstractExternalModule, the module's class needs to extend
 * TransalatableExternalModule and set the default language to use by calling the
 * parent's constructor with the name of the language as argument:
 * 
 * require_once "path_to_this_file.php"
 * 
 * class ExampleExternalModule extends TranslatableExternalModule
 * {
 *     function __construct() {
 *         // Call the parent constructor with the default language 
 *         // (or null, in which case English is assumed).
 *         parent::__construct("English");
 *     }
 * 
 *     ...
 * }
 */
if (!defined("EM_i18n_POLYFILL_LOADED")) {
    require_once dirname(__FILE__) . DS . "em-i18n-classes.php";
}