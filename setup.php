<?php
/**
 * ---------------------------------------------------------------------
 *  hyticat is a plugin to customizes the list of accessible
 *  ticket categories for ticket requesters.
 *  ---------------------------------------------------------------------
 *  LICENSE
 *
 *  This file is part of hyticat.
 *
 *  hyticat is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  hyticat is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 *  ---------------------------------------------------------------------
 *  @copyright Copyright © 2022-2023 probeSys'
 *  @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 *  ---------------------------------------------------------------------
 */

// Version of the plugin

define('PLUGIN_HYTICAT_VERSION', '1.0.0');
define('PLUGIN_HYTICAT_GLPI_MIN_VERSION', '10.0');
define('PLUGIN_HYTICAT_NAMESPACE', 'hyticat');
// Maximum GLPI version, exclusive
define("PLUGIN_HYTICAT_GLPI_MAX_VERSION", "11.0");

if (!defined("PLUGIN_HYTICAT_DIR")) {
    define("PLUGIN_HYTICAT_DIR", Plugin::getPhpDir("hyticat"));
}
if (!defined("PLUGIN_HYTICAT_WEB_DIR")) {
    define("PLUGIN_HYTICAT_WEB_DIR", Plugin::getWebDir("hyticat"));
}


/**
 * Plugin description
 *
 * @return boolean
 */
function plugin_version_hyticat()
{
    return [
      'name' => 'HytiCat',
      'version' => PLUGIN_HYTICAT_GLPI_MIN_VERSION,
      'author' => '<a href="https://www.linkedin.com/in/thiago-martins-54ba11265/">Thiago de Paula</a>',
      'homepage' => 'https://www.linkedin.com/in/thiago-martins-54ba11265/',
      'license' => 'GPLv2+',
      'minGlpiVersion' => PLUGIN_HYTICAT_GLPI_MIN_VERSION,
    ];
}

/**
 * Initialize plugin
 *
 * @return boolean
 */
function plugin_init_hyticat()
{
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['csrf_compliant'][PLUGIN_HYTICAT_NAMESPACE] = true;
    $PLUGIN_HOOKS['post_item_form'][PLUGIN_HYTICAT_NAMESPACE] = ['PluginHytiCatHytiCat', 'post_item_form'];
    $PLUGIN_HOOKS['pre_item_update'][PLUGIN_HYTICAT_NAMESPACE] = [
      'Group' => 'plugin_hyticat_group_update',
    ];
}

/**
 * Check plugin's prerequisites before installation
 */
function plugin_hyticat_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, PLUGIN_HYTICAT_GLPI_MIN_VERSION, 'lt') || version_compare(GLPI_VERSION, PLUGIN_HYTICAT_GLPI_MAX_VERSION, 'ge')) {
        echo __('This plugin requires GLPI >= ' . PLUGIN_HYTICAT_GLPI_MIN_VERSION . ' and GLPI < ' . PLUGIN_HYTICAT_GLPI_MAX_VERSION . '<br>');
    } else {
        return true;
    }
    return false;
}

/**
 * Check if config is compatible with plugin
 *
 * @return boolean
 */
function plugin_hyticat_check_config()
{
    // nothing to do
    return true;
}
