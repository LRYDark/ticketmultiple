<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 Creation de tickets multiples sous plusieurs entites.
 -------------------------------------------------------------------------
 */

define('PLUGIN_TICKETMULTIPLE_VERSION', '1.1.0');
// Minimal GLPI version, inclusive
define('PLUGIN_TICKETMULTIPLE_MIN_GLPI', '12.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_TICKETMULTIPLE_MAX_GLPI', '12.0.99');

if (!defined("PLUGIN_TICKETMULTIPLE_DIR")) {
   define("PLUGIN_TICKETMULTIPLE_DIR", Plugin::getPhpDir("ticketmultiple"));
   define("PLUGIN_TICKETMULTIPLE_NOTFULL_DIR", Plugin::getPhpDir("ticketmultiple", false));
   define("PLUGIN_TICKETMULTIPLE_WEBDIR", ($GLOBALS['CFG_GLPI']['root_doc'] ?? '') . '/plugins/ticketmultiple');
   define("PLUGIN_TICKETMULTIPLE_NOTFULL_WEBDIR", 'plugins/ticketmultiple');
}

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_ticketmultiple()
{
   global $PLUGIN_HOOKS;


   $plugin = new Plugin();
   if ($plugin->isInstalled('ticketmultiple') && $plugin->isActivated('ticketmultiple')) {

      if (Session::getLoginUserID()) {
         // Onglet "droits" dans la fiche Profil
         Plugin::registerClass('PluginTicketmultipleProfile', ['addtabon' => 'Profile']);

         // Entree de menu sous Assistance (helpdesk) si l'utilisateur a le droit d'acces
         if (PluginTicketmultipleTicketmultiple::canUserAccess()) {
            $PLUGIN_HOOKS["menu_toadd"]['ticketmultiple'] = ['helpdesk' => PluginTicketmultipleMenu::class];
         }

         // Page de configuration (Configuration > Plugins > TicketMultiple)
         $PLUGIN_HOOKS['config_page']['ticketmultiple'] = 'front/config.form.php';
      }
   }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_ticketmultiple()
{
   return [
      'name'         => 'TicketMultiple',
      'version'      => PLUGIN_TICKETMULTIPLE_VERSION,
      'author'       => 'REINERT Joris',
      'license'      => 'GPLv3+',
      'homepage'     => '',
      'requirements' => [
         'glpi' => [
            'min' => PLUGIN_TICKETMULTIPLE_MIN_GLPI,
            'max' => PLUGIN_TICKETMULTIPLE_MAX_GLPI,
         ],
      ],
   ];
}

/**
 * Check pre-requisites before install.
 *
 * @return boolean
 */
function plugin_ticketmultiple_check_prerequisites()
{
   return true;
}

/**
 * Check configuration process.
 *
 * @param boolean $verbose
 *
 * @return boolean
 */
function plugin_ticketmultiple_check_config($verbose = false)
{
   return true;
}
