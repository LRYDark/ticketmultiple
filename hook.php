<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_ticketmultiple_install()
{
   /** @var DBmysql $DB */
   global $DB;

   include_once(GLPI_ROOT . "/plugins/ticketmultiple/inc/profile.class.php");

   // Droit sur le profil courant (celui qui installe)
   PluginTicketmultipleProfile::createFirstAccess($_SESSION["glpiactiveprofile"]["id"]);

   // Table de configuration
   if (!$DB->TableExists("glpi_plugin_ticketmultiple_configs")) {
      $query = "CREATE TABLE `glpi_plugin_ticketmultiple_configs` (
         `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
         `access_mode` TINYINT NOT NULL DEFAULT '0',
         `recursive_users` TINYINT NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      if (!$DB->doQuery($query)) {
         Toolbox::logInFile('plugin_ticketmultiple', "ERROR create configs: " . $DB->error() . PHP_EOL);
         return false;
      }
      // Ligne de config par defaut : mode "par profil", non recursif
      $DB->insert('glpi_plugin_ticketmultiple_configs', [
         'access_mode'     => 0,
         'recursive_users' => 0,
      ]);
   }

   // Table de la liste des utilisateurs autorises (mode "users choisis")
   if (!$DB->TableExists("glpi_plugin_ticketmultiple_users")) {
      $query = "CREATE TABLE `glpi_plugin_ticketmultiple_users` (
         `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
         `users_id` INT UNSIGNED NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         UNIQUE KEY `users_id` (`users_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
      if (!$DB->doQuery($query)) {
         Toolbox::logInFile('plugin_ticketmultiple', "ERROR create users: " . $DB->error() . PHP_EOL);
         return false;
      }
   }

   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_ticketmultiple_uninstall()
{
   /** @var DBmysql $DB */
   global $DB;

   $tables = [
      "glpi_plugin_ticketmultiple_configs",
      "glpi_plugin_ticketmultiple_users",
   ];
   foreach ($tables as $table) {
      $DB->doQuery("DROP TABLE IF EXISTS `$table`;");
   }

   // Nettoyage des droits de profil
   $DB->delete('glpi_profilerights', ['name' => ['LIKE', 'plugin_ticketmultiple%']]);

   return true;
}
