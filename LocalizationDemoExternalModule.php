<?php

namespace RUB\LocalizationDemoExternalModule;

use ExternalModules\AbstractExternalModule;

// Include this polyfill for backward compatibility with REDCap versions below 9.5.0.
// It is not needed otherwise.
require "em-i18n-polyfill/em-i18n-polyfill.php";

/**
 * ExternalModule class for Localization Demo.
 * This demo adds a plugin page showcasing the use of the string translation 
 * features provided by the EM framework (v3+, REDCap 9.5.0+).
 */
class LocalizationDemoExternalModule extends AbstractExternalModule {

    private $i18n_enabled = true;

    function __construct() {
        parent::__construct();

        // Initialize EM i18n backward-compatibility support.
        // This is only needed if the module must run on REDCap versions below 9.5.0.
        $this->i18n_enabled = \EMi18nPolyfill::initialize($this);
    }

    /**
     * When running on older REDCap versions that do not yet support EM-localization,
     * check what language has been set in the module config and switch appropriately.
     * Once the minimal supported REDCap version is 9.5.0, the manual language 
     * selection can be removed.
     * 
     * @param int $project_id The project id (or null).
     */
    function set_language($project_id) {
        // Do nothing if the framework handles language selection.
        if ($this->i18n_enabled) return;

        // Get the language setting from the appropriate context (system/project).
        $lang = $project_id == null ? $this->getSystemSetting("system_language") : $this->getProjectSetting("project_language");
        $this->framework->loadLanguage($lang);
    }


    /** 
     * The goal here is to log a startup message to the browser's console on every page.
     */
    function redcap_every_page_top($project_id = null) {

        // Set the language -- this can be removed as soon as the minimal REDCap version supported 
        // by this module is 9.5.0 or greater.
        $this->set_language($project_id);

        // EM Framework shortcut.
        $fw = $this->framework;


        // Perform the tasks necessary to output a greeting to the browser console.
        
        // To make it more interesting, part of the message that is output is set by the user
        // in the module's configuration (system level).
        // Some parts, however, are "hardcoded" in the included language files and are 
        // thus translatable.

        // Read system settings.
        $msg_type = $this->getSystemSetting("msg_type");
        if ($msg_type == null) $msg_type = "info";
        $msg = $this->getSystemSetting("msg_startup");

        // What if there is no message?
        if (!strlen($msg)) {
            // Do not alert the user unnecessarily, set to 'info'.
            $msg_type = "info";
            // And use a default message.
            $msg = $fw->tt("startup_nomsg");
        }
        
        // Now we need to get stuff over to JavaScript.
        // First, prepare the JMO. 
        // When using the polyfill for backward compatibility, it is essential to call
        // this via the framework object!
        $fw->initializeJavascriptModuleObject();


        // Transfer a string that is interpolated here, on the PHP side. 
        // The string contains the module's name, which is itself translatable.
        // This is the template, defined in Language.ini:
        //   startup_intro = "'{0:Name des Moduls}' initialisiert: "
        $fw->tt_transferToJavascriptModuleObject("startup_intro", $fw->tt("module_name"));
        

        // Transfer the message. This is added as new entry.
        $fw->tt_addToJavascriptModuleObject("msg", $msg);

        
        // Finally, output a <script> that writes to the console.
        echo "<script>
            // Localization Demo External Module
            $(function() {
                var em = {$fw->getJavascriptModuleObjectName()}
                // Produce the output (combine the startup intro with the message):
                var msg = em.tt('startup_intro') + em.tt('msg')

                // And log it to the console:
                console.{$msg_type}(msg)

                // Note: One could have transferred msg_type as well and
                // used it e.g. in an if statement to produce the desired
                // effects (maybe alert() in case of error).
            })
        </script>";
    }
}