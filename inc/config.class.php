<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTicketmultipleConfig extends CommonDBTM
{
   static string $rightname = 'config';

   const MODE_PROFILE = 0; // tous les users des profils autorises
   const MODE_USERS   = 1; // uniquement les users choisis

   /**
    * Recupere la ligne unique de configuration (cree si absente).
    *
    * @return array
    */
   static function getConfig(): array
   {
      global $DB;

      $row = $DB->request([
         'FROM'  => 'glpi_plugin_ticketmultiple_configs',
         'ORDER' => 'id ASC',
         'LIMIT' => 1,
      ])->current();

      if (!$row) {
         return ['access_mode' => self::MODE_PROFILE, 'recursive_users' => 0];
      }
      return $row;
   }

   /**
    * Liste des ids d'utilisateurs explicitement autorises.
    *
    * @return int[]
    */
   static function getAllowedUserIds(): array
   {
      global $DB;
      $ids = [];
      foreach ($DB->request(['FROM' => 'glpi_plugin_ticketmultiple_users']) as $r) {
         $ids[] = (int)$r['users_id'];
      }
      return $ids;
   }

   /**
    * Enregistre la configuration depuis $_POST.
    */
   static function updateConfig(array $input): void
   {
      global $DB;

      $cfg = self::getConfig();

      $access_mode     = isset($input['access_mode']) ? (int)$input['access_mode'] : self::MODE_PROFILE;
      $recursive_users = !empty($input['recursive_users']) ? 1 : 0;

      if (isset($cfg['id'])) {
         $DB->update('glpi_plugin_ticketmultiple_configs', [
            'access_mode'     => $access_mode,
            'recursive_users' => $recursive_users,
         ], ['id' => $cfg['id']]);
      } else {
         $DB->insert('glpi_plugin_ticketmultiple_configs', [
            'access_mode'     => $access_mode,
            'recursive_users' => $recursive_users,
         ]);
      }

      // Remplace la liste des utilisateurs autorises
      $DB->delete('glpi_plugin_ticketmultiple_users', ['users_id' => ['>=', 0]]);
      $users = $input['allowed_users'] ?? [];
      if (!is_array($users)) {
         $users = [$users];
      }
      foreach (array_unique(array_filter(array_map('intval', $users))) as $uid) {
         $DB->insert('glpi_plugin_ticketmultiple_users', ['users_id' => $uid]);
      }
   }

   /**
    * Affiche le formulaire de configuration.
    */
   static function showConfigForm(): void
   {
      if (!Session::haveRight('config', UPDATE)) {
         echo "<div class='alert alert-warning'>" . __('Access denied') . "</div>";
         return;
      }

      $cfg           = self::getConfig();
      $access_mode   = (int)($cfg['access_mode'] ?? self::MODE_PROFILE);
      $recursive     = (int)($cfg['recursive_users'] ?? 0);
      $allowed_users = self::getAllowedUserIds();

      echo "<form method='post' action='" . PLUGIN_TICKETMULTIPLE_WEBDIR . "/front/config.form.php'>";

      echo "<div class='card' style='max-width:760px;margin:auto;'>";
      echo "<div class='card-body'>";
      echo "<h3 class='mb-3'>" . __('Configuration des tickets multiples', 'ticketmultiple') . "</h3>";

      // Mode d'acces
      echo "<div class='mb-3'>";
      echo "<label class='form-label'>" . __("Mode d'acces", 'ticketmultiple') . "</label>";
      Dropdown::showFromArray('access_mode', [
         self::MODE_PROFILE => __('Tous les utilisateurs des profils autorises', 'ticketmultiple'),
         self::MODE_USERS   => __('Uniquement les utilisateurs choisis', 'ticketmultiple'),
      ], ['value' => $access_mode, 'width' => '100%']);
      echo "</div>";

      // Liste des utilisateurs autorises (mode MODE_USERS)
      echo "<div class='mb-3'>";
      echo "<label class='form-label'>" . __('Utilisateurs autorises (mode "utilisateurs choisis")', 'ticketmultiple') . "</label>";
      User::dropdown([
         'name'     => 'allowed_users[]',
         'multiple' => true,
         'value'    => $allowed_users,
         'width'    => '100%',
         'right'    => 'all',
      ]);
      echo "</div>";

      // Recuperation du demandeur : recursif ou non
      echo "<div class='mb-3'>";
      echo "<label class='form-label'>" . __('Choix du demandeur : utilisateurs des entites parentes (recursif)', 'ticketmultiple') . "</label>";
      Dropdown::showYesNo('recursive_users', $recursive);
      echo "<div class='form-text'>" . __("Non = uniquement les utilisateurs habilites directement sur l'entite. Oui = inclut aussi les habilitations recursives des entites parentes.", 'ticketmultiple') . "</div>";
      echo "</div>";

      echo "<div class='text-center'>";
      echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
      echo "</div>";

      echo "</div></div>";
      Html::closeForm();
   }
}
