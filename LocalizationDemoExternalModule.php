<?php

namespace RUB\LocalizationDemoExternalModule;

use ExternalModules\AbstractExternalModule;

/**
 * ExternalModule class for Localization Demo.
 * This demo adds a plugin page showcasing the use of the string translation 
 * features provided by the EM framework (v3+, REDCap 9.5.0+).
 */
class LocalizationDemoExternalModule extends AbstractExternalModule {


    function __construct() {
        parent::__construct();

        // Add any constructor logic below only.
    }

    function redcap_every_page_top($project_id = null) {

        // The goal here is to log a startup message to the browser's console on every page.

        // Read system settings.
        $msg_type = $this->getSystemSetting("msg_type");
        if ($msg_type == null) $msg_type = "info";
        $msg = $this->getSystemSetting("msg_startup");

        // What if there is no message?
        if (!strlen($msg)) {
            // Do not alert the user unnecessarily, set to 'info'.
            $msg_type = "info";
            // And use a default message.
            $msg = $this->tt("startup_nomsg");
        }
        
        // Now we need to get stuff over to JavaScript.
        // First, prepare the JMO:
        $this->initializeJavascriptModuleObject();


        // Transfer a string that is interpolated here, on the PHP side. 
        // The string contains the module's name, which is itself translatable.
        // This is the template, defined in Language.ini:
        //   startup_intro = "'{0:Name des Moduls}' initialisiert: "
        $this->tt_transferToJavascriptModuleObject("startup_intro", $this->tt("module_name"));
        
        // Transfer the message. This is added as new entry.
        $this->tt_addToJavascriptModuleObject("msg", $msg);

        // Finally, output a <script> that writes to the console.
        echo "<script>
            // Localization Demo External Module
            $(function() {
                var em = {$this->framework->getJavascriptModuleObjectName()}
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