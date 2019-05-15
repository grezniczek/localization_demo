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
    }

    function redcap_every_page_top($project_id = null) {

        // Opt-in to use the JS features.
        $this->useJSLanguageFeatures();

        // Read from settings.
        $verbose = $this->getSystemSetting("verbose");
        if ($verbose == null) $verbose = false;

        $msg_type = $this->getSystemSetting("msg_type");
        if ($msg_type == null) $msg_type = "info";

        $msg = $this->getSystemSetting("msg");
        if ($verbose) {
            $this->addToJSLanguageStore("verbose_intro", $this->tt("module_name"));
            // Ok, technically we are cheating here ;)
            $this->addToJSLanguageStore("verbose_settings");
            $this->addToJSLanguageStore("verbose_done");
            // Be creative in accessing strings!
            // Note that we put this under a new name!
            $this->addNewToJSLanguageStore("verbose_task", $this->tt("verbose_" . $msg_type));
        }
        // What if there is no message?
        if (!strlen($msg) && $verbose) {
            $msg_type = "info";
            $msg = $this->tt("no_msg");
        }
        // Transfer the message via the store.
        $this->addNewToJSLanguageStore("msg", $msg);
        // 'Misuse' the store to transfer a boolean ;)
        $this->addNewToJSLanguageStore("verbose", $verbose);

        // Output <script>
        echo "<script>
            // Localization Demo External Module
            $(function() {
                const em = \$lang.getEMHelper('{$this->PREFIX}')
                if (em.get('verbose')) {
                    console.log(em.tt('verbose_intro'))
                    console.log(em.tt('verbose_settings'))
                    console.log(em.tt('verbose_done'))
                    console.log(em.tt('verbose_task'))
                }
                console.{$msg_type}(em.get('msg'))
            })
        </script>";

        // Count up ... shows how to transfer an array via the JS store.
        $count = $this->getSystemSetting("count");
        if ($count == null || !is_numeric($count)) $count = 0;
        if ($count > 0) {
            $arr = array();
            for ($i = 1; $i <= $count; $i++)
                array_push($arr, $i);
            $this->addNewToJSLanguageStore("array", $arr);
            $this->addToJSLanguageStore("countup", $count);
            echo "<script>
                // Log array elements.
                $(function() {
                    const em = \$lang.getEMHelper('{$this->PREFIX}')
                    console.log(em.tt('countup'))
                    const arr = em.get('array')
                    arr.forEach((item) => console.log(item))
                });
            </script>";
        }
    }
}