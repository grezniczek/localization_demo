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
namespace ExternalModules;

use ExternalModules\AbstractExternalModule;

class EMi18nPolyfill {

    const EM_LANG_PREFIX = "emlang_";
    private $module;

    function __construct($module) {
        $this->module = $module;
    }


    /**
	 * Returns the translation for the given language file key.
	 * 
	 * @param string $key The language file key.
	 * @param mixed $values The values to be used for interpolation. If the first parameter is a (sequential) array, it's members will be used and any further parameters ignored.
	 * 
	 * @return string The translation (with interpolations).
	 */
	public function tt($key, ...$values) {
        
		global $lang;

		// Get the full key for $lang.
		$lang_key = self::constructLanguageKey($this->module->PREFIX, $key);
		// Now get the corresponding text.
		$text = $lang[$lang_key];

        return self::interpolateLanguageString($text, $values); 
	}

	/**
	 * Declares to the EM framework that language features support should be added for JavaScript.
	 * Call this before using any of the features such as addToJSLanguageStore().
	 */
	public function useJSLanguageFeatures() {
		// Add $lang JS support, but only once.
		if (!defined("EM_LANGUAGE_SUPPORT_FOR_JS_ADDED")) {
            define("EM_LANGUAGE_SUPPORT_FOR_JS_ADDED", true);
			$fullLocalPath = __DIR__ . "redcap-localization-helper.js";
			// Add the filemtime to the url for cache busting.
			clearstatcache(true, $fullLocalPath);
			$url = ExternalModules::$BASE_URL . "redcap-localization-helper.js?" . filemtime($fullLocalPath);
			echo '<script type="text/javascript" src="' . $url . '"></script>';
			echo '<script>const $EM_LANG_PREFIX = ' . json_encode(self::EM_LANG_PREFIX) . '</script>';
		}
    }
    
	/**
	 * Adds an interpolated language string to the JavaScript store by its key.
	 * To add a raw value, do not supply any values.
	 * 
	 * @param string $key The language key.
	 * @param mixed $values The values to be used for interpolation.
	 */
	public function addToJSLanguageStore($key, ...$values) {
		// Encode for JS.
		$js_string = json_encode($this->tt($key, $values));
		$js_key = json_encode(self::constructLanguageKey($this->module->PREFIX, $key));
		// Add script to add key/value pair to $lang.
		echo '<script>$lang.add('. $js_key . ', ' . $js_string . ')</script>';
	}

	/**
	 * Adds an string directly to the $lang JavaScript store. 
	 * 
	 * @param string $key The language key.
	 * @param string $string The corresponding template string.
	 */
	public function addNewToJSLanguageStore($key, $string) {
		// Encode for JS.
		$js_string = json_encode($string);
		$js_key = json_encode(self::constructLanguageKey($this->module->PREFIX, $key));
		// Add script to add key/value pair to $lang.
		echo '<script>$lang.add('. $js_key . ', JSON.parse(' . $js_string . '))</script>';
    }
    
  	/**
	 * Generates a key for the $lang global from a module prefix and a module-scope language file key.
	 */
	private static function constructLanguageKey($prefix, $key) {
		return self::EM_LANG_PREFIX . "{$prefix}_{$key}";
	}

	/**
	 * Replaces placeholders in a language string with the supplied values.
	 * 
	 * @param string $string The template string.
	 * @param mixed $values The values to be used for interpolation. If the first parameter is a (sequential) array, it's members will be used and any further parameters ignored.
	 * 
	 * @return string The result of the string interpolation.
	 */
	public static function interpolateLanguageString($string, ...$values) {

		// Do we need to do interpolation?
		if (count($values)) {
			// Use array if supplied. 
			if (is_array($values[0])) $values = $values[0];
			// Regular expression to find places where replacements need to be done.
			// Placeholers are in curly braces, e.g. {0}. Optionally, a type hint can be present after a colon (e.g. {0:Date}) which is ignored however.
			// To not replace a placeholder, the first curly can be escaped with a backslash like so: '\{1}' (this will leave '{1}' in the text).
			// When the an even number of backslashes is before the curly, e.g. '\\{0}' with value x this will result in '\x'.
			// Placeholder names can be strings (a-Z0-9_), too (need associative array then). 
			$re = '/(?\'all\'((?\'escape\'\\\\*){|{)(?\'index\'[\d_A-Za-z]+)(:(?\'hint\'.*))?})/mU';
			preg_match_all($re, $string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, 0);
			// Build resulting string.
			$prevEnd = 0;
			if (count($matches)) {
				$result = "";
				foreach ($matches as $match) {
					$start = $match["all"][1];
					$all = $match["all"][0];
					$len = strlen($all);
					$key = $match["index"][0];
					// Add text between previous end and the match and reset end.
					$result .= substr($string, $prevEnd, $start - $prevEnd);
					$prevEnd = $start + $len;
					// Escaped?
					$nSlashes = strlen($match["escape"][0]);
					if ($nSlashes % 2 == 0) {
						// Even number means they escaped themselves, so we add half of them and replace.
						$result .= str_repeat("\\", $nSlashes / 2);
						if (array_key_exists($key, $values)) {
							$result .= $values[$key];
						}
						else {
							// When the key doesn't exist, just leave it unchanged (but remove the backslashes).
							$result .= ltrim($all, "\\");
						}
					}
					else {
						// Uneven number - means to not replace.
						$result .= str_repeat("\\", ($nSlashes - 1) / 2);
						$result .= ltrim($all, "\\");
					}
				}
				// Add rest of original.
				$result .= substr($string, $prevEnd);
				$string = $result;
			}
		}
		return $string;
	}

	/**
	 * Loads a language file.
	 * @param string $language The name of the language to load (case sensitive!).
	 */
	public function load($language) {

		// Check the correspondig file exists.
		$path = $this->module->getModulePath(). "lang";
		if (!is_dir($path)) {
			throw new Exception("Module '{$this->module->PREFIX}' does not have a 'lang' directory.");
		}
		$files = glob($path . DS . $language . ".{i,I}{n,N}{i,I}", GLOB_BRACE);
		if (count($files) < 1) {
			throw new Exception("The language file for '{$language}' could not be found in the 'lang' directory of module '{$this->module->PREFIX}'.");
		}
		$defaultFile = $files[0];
		// Read the files.
		$moduleLang = parse_ini_file($defaultFile);
		if ($moduleLang === false) {
			throw new Exception("Failed to parse language file '{$language}' for module '{$this->module->PREFIX}'.");
		}
		// Add to global language array $lang
		global $lang;
		foreach ($moduleLang as $key => $val) {
			$lang_key = self::constructLanguageKey($this->module->PREFIX, $key);
			$lang[$lang_key] = $val;
		}
	}
}

class TranslatableExternalModule extends AbstractExternalModule {
    
    private $i18n_polyfill = null;

    function __construct($default_language = "English") {
       
        parent::__construct();

		// Check for availability of the framework.
		if (!isset($this->framework)) {
			// Substitute the polyfill and load strings from default language.
			$this->framework = new EMi18nPolyfill($this);
            $this->framework->load($default_language);
		}
		else if (!method_exists($this->framework, "tt")) {
			// Framework without localization support.
			// Class methods will proxy to those provided by the polyfill.
            $this->i18n_polyfill = new EMi18nPolyfill($this);
            $this->i18n_polyfill->load($default_language);
        } 
        else {
			// All good. The available EM framework supports localization.
        }
    }

    /**
	 * Returns the translation for the given language file key.
	 * 
	 * @param string $key The language file key.
	 * @param mixed $values The values to be used for interpolation. If the first parameter is a (sequential) array, it's members will be used and any further parameters ignored.
	 * 
	 * @return string The translation (with interpolations).
	 */
    function tt($key, ...$values) {
		// Proxy.
        return $this->i18n_polyfill == null ? $this->framework->tt($key, $values) : $this->i18n_polyfill->tt($key, $values);
	}
	
	/**
	 * Declares to the EM framework that language features support should be added for JavaScript.
	 * Call this before using any of the features such as addToJSLanguageStore().
	 */
	function useJSLanguageFeatures() {
		// Proxy.
		$this->i18n_polyfill == null ? $this->framework->useJSLanguageFeatures() : $this->i18n_polyfill->useJSLanguageFeatures();
	}

	/**
	 * Adds an interpolated language string to the JavaScript store by its key.
	 * To add a raw value, do not supply any values.
	 * 
	 * @param string $key The language key.
	 * @param mixed $values The values to be used for interpolation.
	 */
	public function addToJSLanguageStore($key, ...$values) {
		// Proxy.
		$this->i18n_polyfill == null ? $this->framework->addToJSLanguageStore($key, $values) : $this->i18n_polyfill->addToJSLanguageStore($key, $values);
	}

	/**
	 * Adds an string directly to the $lang JavaScript store. 
	 * 
	 * @param string $key The language key.
	 * @param string $string The corresponding template string.
	 */
	public function addNewToJSLanguageStore($key, $string) {
		// Proxy.
		$this->i18n_polyfill == null ? $this->framework->addNewToJSLanguageStore($key, $string) : $this->i18n_polyfill->addNewToJSLanguageStore($key, $string);
	}
}