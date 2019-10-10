<?php

namespace RUB\LocalizationDemoExternalModule;

require_once "em-i18n-polyfill/em-i18n-polyfill.php";

use ExternalModules\TranslatableExternalModule;
use ExternalModules\AbstractExternalModule;

/**
 * ExternalModule class for Localization Demo.
 * This demo module will get a string and print it to the browser's
 * console as info, warning, or error, depending on the module's 
 * settings.
 */
class LocalizationDemoExternalModule extends AbstractExternalModule {


    function __construct() {
        parent::__construct();
        /*
        parent::__construct("English"); // Could omit, as English is the default.

        if (!$this->hasNativeLocalizationSupport()) {
            // No native support, so we have to take care of language switching ourselves.
            $sysLang = $this->getSystemSetting("system_language");
            $projLang = $this->getProjectId() != null ?  $this->getProjectSetting("project_language") : "";
            $lang = strlen($projLang) ? $projLang : $sysLang;
            if (strlen($lang) && $lang !== "English") {
                // Only switch if set and not default.
                $this->loadLanguage($lang);
            }
        }
        */
    }

    function redcap_every_page_top($project_id = null) {

        // Read from settings.
        $verbose = $this->getSystemSetting("verbose");
        if ($verbose == null) $verbose = false;

        $msg_type = $this->getSystemSetting("msg_type");
        if ($msg_type == null) $msg_type = "info";

        $msg = $this->getSystemSetting("msg");
        
        $this->initializeJavascriptModuleObject();

        if ($verbose) {
            $this->tt_transferToJavascriptModuleObject("verbose_intro", $this->tt("module_name"));
            // Ok, technically we are cheating here ;)
            $this->tt_transferToJavascriptModuleObject("verbose_settings");
            $this->tt_transferToJavascriptModuleObject("verbose_done");
            // Be creative in accessing strings!
            // Note that we put this under a new name!
            $this->tt_addToJavascriptModuleObject("verbose_task", $this->tt("verbose_" . $msg_type));
        }
        // What if there is no message?
        if (!strlen($msg) && $verbose) {
            $msg_type = "info";
            $msg = $this->tt("no_msg");
        }
        // Transfer the message via the store.
        $this->tt_addToJavascriptModuleObject("msg", $msg);
        // 'Misuse' the store to transfer a boolean ;)
        $this->tt_addToJavascriptModuleObject("verbose", $verbose);

        // Output <script>
        echo "<script>
            // Localization Demo External Module
            $(function() {
                var em = {$this->framework->getJavascriptModuleObjectName()}
                if (em.tt('verbose')) {
                    console.log(em.tt('verbose_intro'))
                    console.log(em.tt('verbose_settings'))
                    console.log(em.tt('verbose_done'))
                    console.log(em.tt('verbose_task'))
                }
                console.{$msg_type}(em.tt('msg'))
            })
        </script>";

        // Count up ... shows how to transfer an array via the JS store.
        $count = $this->getSystemSetting("count");
        if ($count == null || !is_numeric($count)) $count = 0;
        if ($count > 0) {
            $arr = array();
            for ($i = 1; $i <= $count; $i++)
                array_push($arr, $i);
            $this->tt_addToJavascriptModuleObject("array", $arr);
            $this->tt_transferToJavascriptModuleObject("countup", $count);
            echo "<script>
                // Log array elements.
                $(function() {
                    const em = {$this->framework->getJavascriptModuleObjectName()}
                    console.log(em.tt('countup'))
                    const arr = em.tt('array')
                    arr.forEach((item) => console.log(item))
                });
            </script>";
        }
    }
}