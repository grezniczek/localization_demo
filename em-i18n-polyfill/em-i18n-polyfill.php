<?php

namespace REDCap\CommunityTools;

// Make sure these classes are only defined once.
if (!class_exists("EMi18nPolyfill")) {

    /**
     * Add support for using EM localization features even when running on REDCap version not supporting these features.
     * 
     * This file must be included before defining the module's class. In the module's constructor, call
     * the static EMi18nPolyfill::initialize() method.
     * You may want to preserve the return value to switch on/off handling of manual language change in your module. * 
     * 
     * class ExampleExternalModule extends AbstractExternalModule
     * {
     *     function __construct() {
     *         // Call the parent constructor. This is important!!
     *         parent::__construct();
     *         // Now initialize i18n support.
     *         $installed = EMi18nPolyfill::initialize($this, "English");
     *     }
     *     ...
     * }
     */
    
    class EMi18nPolyfill {
    
        const EM_LANG_PREFIX = "emlang_";
        const LANGUAGE_FOLDER_NAME = "lang";
    
        private $module;
        private $original_framework = null;
        private $default_lang = "English";
        private $current_lang = "";
    
        /**
         * Constructor. Preserves the orginal framework if present.
         */
        function __construct($module, $default_language) {
            $this->module = $module;
            $this->default_lang = $default_language;
            // Store the original framework object if it exists.
            if (isset($module->framework)) {
                $this->original_framework = $module->framework;
            }
            // Check the default language file exists.
            $path = $module->getModulePath(). self::LANGUAGE_FOLDER_NAME;
            if (!is_dir($path)) {
                throw new Exception("Module '{$module->PREFIX}' does not have a '" . self::LANGUAGE_FOLDER_NAME . "' directory.");
            }
            $files = glob($path . DS . $default_language . ".{i,I}{n,N}{i,I}", GLOB_BRACE);
            if (count($files) < 1) {
                throw new Exception("The default language file ({$default_language}.ini) must exist in the '" . self::LANGUAGE_FOLDER_NAME . "' directory of module '{$module->PREFIX}'.");
            }
        }
    
        /**
         * Redirect anything we don't know to the original framework (or the module).
         */
        function __call($name, $arguments) {
            if (method_exists($this->original_framework, $name))
                return call_user_func_array([$this->original_framework, $name], $arguments);
            if (method_exists($this->module, $name))
                return call_user_func_array([$this->module, $name], $arguments);
            throw new Exception("Call to undefined method '$name'.");
        }
    
        //region JavaScript Module Object
    
        /**
         * Setup the JavaScript Module Object.
         */
        public function initializeJavascriptModuleObject() {
            // Let the module's version do it's job.
            $this->module->initializeJavascriptModuleObject();
            // Then fill in the rest.
            $jsObject = self::_getJavascriptModuleObjectName($this->module);
            ?>
            <script>
                (function(){
                    // Ensure ExternalModules.$lang has been initialized. $lang provides localization support for all external modules.
                    if(window.ExternalModules === undefined) {
                        window.ExternalModules = {}
                    }
                    if (window.ExternalModules.$lang === undefined) {
                        window.ExternalModules.$lang = {}
                        var lang = window.ExternalModules.$lang
                        lang.strings = {}
                        lang.count = function() {
                            var n = 0
                            for (var key in this.strings) {
                                if (this.strings.hasOwnProperty(key))
                                    n++
                            }
                            return n
                        }
                        lang.log = function(key) {
                            var s = this.get(key)
                            if (s != null)
                                console.log(key, s)
                        }
                        lang.logAll = function() {
                            console.log(this.strings)
                        }
                        lang.get = function(key) {
                            if (!this.strings.hasOwnProperty(key)) {
                                console.error("Key '" + key + "' does not exist in $lang.")
                                return null
                            }
                            return this.strings[key]
                        }
                        lang.add = function(key, string) {
                            this.strings[key] = string
                        }
                        lang.remove = function(key) {
                            if (this.strings.hasOwnProperty(key))
                                delete this.strings[key]
                        }
                        lang._getValues = function(inputs) {
                            var values = Array()
                            if (inputs.length > 1) {
                                // If the first value is an array or object, use it instead.
                                if (Array.isArray(inputs[1]) || typeof inputs[1] === 'object' && inputs[1] !== null) {
                                    values = inputs[1]
                                }
                                else {
                                    values = Array.prototype.slice.call(inputs, 1)
                                }
                            }
                            return values
                        }
                        lang.tt = function(key) {
                            var string = this.get(key)
                            var values = this._getValues(arguments)
                            return this.interpolate(string, values)
                        }
                        lang.interpolate = function(string, values) {
                            if (typeof string == 'undefined' || string == null) {
                                console.warn('$lang.interpolate() called with undefined or null.')
                                return ''
                            }
                            if (typeof string !== 'string' || string.length == 0) {
                                return string
                            }
                            try{
                                var regex = new RegExp('(?<all>((?<escape>\\\\*){|{)(?<index>[\\d_A-Za-z]+)(:(?<hint>.*))?})', 'gm')
                            }
                            catch(error){
                                console.error("Parameters in translated strings will NOT be interpolated due to limited regex support in your browser described by the following error:")
                                console.error(error)
                                return string
                            }
                            var m
                            var result = ''
                            var prevEnd = 0
                            while ((m = regex.exec(string)) !== null) {
                                if (m.index === regex.lastIndex) {
                                    regex.lastIndex++
                                }
                                var start = m.index
                                var all = m['groups']['all']
                                var len = all.length
                                var key = m['groups']['index']
                                result += string.substr(prevEnd, start - prevEnd)
                                prevEnd = start + len
                                var nSlashes = m['groups']['escape'].length
                                if (nSlashes % 2 == 0) {
                                    result += '\\'.repeat(nSlashes / 2)
                                    if (typeof values[key] !== 'undefined') {
                                        result += values[key]
                                    }
                                    else {
                                        result += all.substr(all.indexOf('{'))
                                    }
                                }
                                else {
                                    result += '\\'.repeat((nSlashes - 1) / 2)
                                    result += all.substr(all.indexOf('{'))
                                }
                            }
                            result += String.prototype.substr.call(string, prevEnd)
                            return result
                        }
                    }
                })()
            </script>
            <script>
                (function(){
                    var parent = window
                    ;<?=json_encode($jsObject)?>.split('.').forEach(function(part){
                        if(parent[part] === undefined){
                            parent[part] = {}
                        }
                        parent = parent[part]
                    })
                    var module = <?=$jsObject?>;
                    module._constructLanguageKey = function(key) {
                        return <?=json_encode(self::EM_LANG_PREFIX . $this->module->PREFIX)?> + '_' + key
                    }
                    module.tt = function (key) {
                        var argArray = Array.prototype.slice.call(arguments)
                        argArray[0] = this._constructLanguageKey(key)
                        var lang = window.ExternalModules.$lang
                        return lang.tt.apply(lang, argArray)
                    }
                    module.tt_add = function(key, value) {
                        key = this._constructLanguageKey(key)
                        window.ExternalModules.$lang.add(key, value)
                    }
                })()
            </script>
            <?php
        }
    
        //endregion
    
        //region Language features - Public API
    
        /**
         * Gets the name of the module's JavaScript Module Object. This is provided for convenience.
         * @return string The name of the Javascript Module Object.
         */
        public function getJavascriptModuleObjectName() {
            return method_exists($this->original_framework, "getJavascriptModuleObjectName") ? 
                $this->original_framework->getJavascriptModuleObjectName() :
                self::_getJavascriptModuleObjectName($this->module);
        }
    
        /**
         * Returns the translation for the given language file key.
         * 
         * @param string $key The language file key.
         * 
         * Note: Any further arguments are used for interpolation. When the first additional parameter is an array, it's members will be used and any further parameters ignored. 
         * 
         * @return string The translation (with interpolations).
         */
        public function tt($key) {
            // Get all arguments and send off for processing.
            return self::tt_process(func_get_args(), $this->module->PREFIX, false);
        }
    
        /**
         * Transfers one (interpolated) or many strings (without interpolation) to the module's JavaScript object.
         * 
         * @param mixed $key (optional) The language key or an array of language keys.
         * 
         * Note: When a single language key is given, any number of arguments can be supplied and these will be used for interpolation. When an array of keys is passed, then any further arguments will be ignored and the language strings will be transfered without interpolation. If no key or null is passed, all language strings will be transferred.
         */
        public function tt_transferToJavascriptModuleObject($key = null) {
            // Get all arguments and send off for processing.
            self::tt_prepareTransfer(func_get_args(), $this->module->PREFIX);
        }
    
        /**
         * Adds a key/value pair directly to the language store for use in the JavaScript module object. 
         * Value can be anything (string, boolean, array).
         * 
         * @param string $key The language key.
         * @param mixed $value The corresponding value.
         */
        public function tt_addToJavascriptModuleObject($key, $value) {
            self::tt_addToJSLanguageStore($key, $value, $this->module->PREFIX, $key);
        }
    
        //endregion
    
        //region Private static implementation
    
        private static function _getJavascriptModuleObjectName($moduleInstance){
            $jsObjectParts = explode('\\', get_class($moduleInstance));
            array_pop($jsObjectParts);
            array_unshift($jsObjectParts, 'ExternalModules');
            return implode('.', $jsObjectParts);
        }
    
        private static function constructLanguageKey($prefix, $key) {
            return is_null($prefix) ? $key : self::EM_LANG_PREFIX . "{$prefix}_{$key}";
        }
    
        private static function getLanguageKeys($prefix = null, $scoped = true) {
            global $lang;
            $keys = array();
            if ($prefix === null) {
                $keys = array_keys($lang);
            }
            else {
                $key_prefix = self::EM_LANG_PREFIX . $prefix . "_";
                $key_prefix_len = strlen($key_prefix);
                foreach (array_keys($lang) as $key) {
                    if (substr($key, 0, $key_prefix_len) === $key_prefix) {
                        array_push($keys, $scoped ? substr($key, $key_prefix_len) : $key);
                    }
                }
            }
            return $keys;
        }
    
        private static function tt_process($args, $prefix = null, $jsEncode = false) {
    
            if (!is_array($args) || count($args) < 1 || !is_string($args[0]) || strlen($args[0]) == 0) {
                throw new Exception("Language key must be a not-empty string."); 
            }
            if (!is_null($prefix) && !is_string($prefix) && strlen($prefix) == 0) {
                throw new Exception("Prefix must either be null or a not-empty string.");
            }
            $original_key = $args[0];
            $key = is_null($prefix) ? $original_key : self::constructLanguageKey($prefix, $original_key);
            $values = array();
            if (count($args) > 1) {
                $values = is_array($args[1]) ? $args[1] : array_slice($args, 1);
            }
            global $lang;
            $string = $lang[$key];
            if ($string == null) {
                $string = self::getLanguageKeyNotDefinedMessage($original_key, $prefix);
                $values = array();
            }
            $interpolated = self::interpolateLanguageString($string, $values);
            return $jsEncode ? json_encode($interpolated) : $interpolated;
        }
    
        public static function getLanguageKeyNotDefinedMessage($key, $prefix) {
            $message = "Language key '{$key}' is not defined";
            $message .= is_null($prefix) ? "." : " for module '{$prefix}'.";
            return $message;
        }
    
        private static function tt_prepareTransfer($args, $prefix = null) {
    
            if (!is_null($prefix) && !is_string($prefix) && strlen($prefix) == 0) {
                throw new Exception("Prefix must either be null or a not-empty string.");
            }
            $keys = $args[0];
            $values = array();
            if ($keys === null) {
                $keys = self::getLanguageKeys($prefix, false);
            }
            else if (!is_array($keys)) {
                $keys = array($keys);
                $values = array_slice($args, 1);
                if (count($values) && is_array($values[0])) $values = $values[0];
            }
            $to_transfer = array();
            foreach ($keys as $key) {
                $scoped_key = self::constructLanguageKey($prefix, $key);
                array_unshift($values, $scoped_key);
                $to_transfer[$scoped_key] = self::tt_process($values);
            }
            self::tt_transferToJS($to_transfer);
        }
    
        private static function tt_addToJSLanguageStore($key, $value, $prefix = null) {
            if (!is_string($key) || !strlen($key) > 0) {
                throw new Exception("Key must be a not-empty string.");
            }
            $scoped_key = self::constructLanguageKey($prefix, $key);
            $to_transfer = array($scoped_key => $value);
            self::tt_transferToJS($to_transfer);
        }
    
        private static function tt_transferToJS($to_transfer) {
            $n = count($to_transfer);
            $lf = $n > 1 ? "\n" : "";
            $tab = $n > 1 ? "\t" : "";
            if ($n) {
                echo "<script>" . $lf;
                foreach ($to_transfer as $key => $value) {
                    $key = json_encode($key);
                    $value = json_encode($value);
                    echo $tab . "ExternalModules.\$lang.add({$key}, {$value})" . $lf;
                }
                echo "</script>" . $lf;
            }
        }
    
        private static function interpolateLanguageString($string, $values) {
            if (count($values)) {
                $re = '/(?\'all\'((?\'escape\'\\\\*){|{)(?\'index\'[\d_A-Za-z]+)(:(?\'hint\'.*))?})/mU';
                preg_match_all($re, $string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, 0);
                $prevEnd = 0;
                if (count($matches)) {
                    $result = "";
                    foreach ($matches as $match) {
                        $start = $match["all"][1];
                        $all = $match["all"][0];
                        $len = strlen($all);
                        $key = $match["index"][0];
                        $result .= substr($string, $prevEnd, $start - $prevEnd);
                        $prevEnd = $start + $len;
                        $nSlashes = strlen($match["escape"][0]);
                        if ($nSlashes % 2 == 0) {
                            $result .= str_repeat("\\", $nSlashes / 2);
                            if (array_key_exists($key, $values)) {
                                $result .= $values[$key];
                            }
                            else {
                                $result .= ltrim($all, "\\");
                            }
                        }
                        else {
                            $result .= str_repeat("\\", ($nSlashes - 1) / 2);
                            $result .= ltrim($all, "\\");
                        }
                    }
                    $result .= substr($string, $prevEnd);
                    $string = $result;
                }
            }
            return $string;
        }
    
        private static function getLanguageFiles($module) {
            $langs = array();
            $path = $module->getModulePath() . self::LANGUAGE_FOLDER_NAME . DS;
            if (is_dir($path)) {
                $files = glob($path . "*.{i,I}{n,N}{i,I}", GLOB_BRACE);
                foreach ($files as $filename) {
                    if (is_file($filename)) {
                        $lang = pathinfo($filename, PATHINFO_FILENAME); 
                        $langs[$lang] = $filename;
                    }
                }
            }
            return $langs;
        }
    
    
        //endregion
    
        /**
         * Loads a language file.
         * @param string $language The name of the language to load (case sensitive!).
         */
        public function loadLanguage($language) {
            if ($this->current_lang != $language) {
                $availableLangs = self::getLanguageFiles($this->module);
                if (count($availableLangs) > 0) {
                    $translationFile = array_key_exists($language, $availableLangs) ? $availableLangs[$language] : null;
                    $defaultFile = array_key_exists($this->default_lang, $availableLangs) ? $availableLangs[$this->default_lang] : null;
                    $default = file_exists($defaultFile) ? parse_ini_file($defaultFile) : array();
                    $translation = $defaultFile != $translationFile && file_exists($translationFile) ? parse_ini_file($translationFile) : array();
                    $moduleLang = array_merge($default, $translation);
                    global $lang;
                    foreach ($moduleLang as $key => $val) {
                        $lang_key = self::constructLanguageKey($this->module->PREFIX, $key);
                        $lang[$lang_key] = $val;
                    }
                }
                $this->current_lang = $language;
            }
        }
    
        /**
         * Initializes the polyfill. Call this in the constructor of the external module.
         * 
         * @param object $module The external module instance.
         * @param string $default_language The default language that is loaded initially.
         * @return boolean Returns true if REDCap is i18n-enabled (the the polyfill therefore is inactive).
         */
        public static function initialize($module, $default_language = "English") {
            // Check for availability of the framework.
            if (isset($module->framework) && method_exists($module->framework, "tt")) {
                // All good. The available EM framework supports localization.
                return true;
            }
            $module->framework = new EMi18nPolyfill($module, $default_language);
            $module->framework->loadLanguage($default_language);
            return false;
        }
    }
}

