<?php
/**
 * General purpose class for references plugin.
 * Used to load all plugin config settings statically
 *
 * @copyright &copy; 2011 The Open University
 * @author j.platts@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package references
 */

require_once(dirname(__FILE__).'/../../config.php');

class references_lib{

    private static $settings;

    /**
     * Get a plugin config setting
     * Loads all settings once called - so will only make one db call no matter how many times called
     * @param string $name setting name e.g. accesskeyid
     */
    public static function get_setting($name) {
        if (!is_object(self::$settings)) {
            self::$settings = get_config('local_references');
        }
        if (isset(self::$settings->$name)) {
            return self::$settings->$name;
        } else {
            return '';
        }
    }
}