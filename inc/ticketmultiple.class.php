<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTicketmultipleTicketmultiple extends CommonGLPI
{
   static $rightname = 'plugin_ticketmultiple_use';

   static function getTypeName($nb = 0)
   {
      return __('Tickets multiples', 'ticketmultiple');
   }

   /**
    * L'utilisateur courant peut-il acceder a la page ?
    * Mode "profil"  : droit profil READ suffit.
    * Mode "users"   : il faut etre dans la liste des utilisateurs autorises.
    *
    * @return boolean
    */
   static function canUserAccess(): bool
   {
      if (!Session::getLoginUserID()) {
         return false;
      }

      $cfg = PluginTicketmultipleConfig::getConfig();

      if ((int)($cfg['access_mode'] ?? 0) === PluginTicketmultipleConfig::MODE_USERS) {
         return in_array(
            (int)Session::getLoginUserID(),
            PluginTicketmultipleConfig::getAllowedUserIds(),
            true
         );
      }

      // Mode profil
      return (bool)Session::haveRight('plugin_ticketmultiple_use', READ);
   }

   /**
    * Trouve un demandeur pour une entite donnee.
    * Choisit un utilisateur habilite sur l'entite (aleatoire si plusieurs).
    * Retourne $fallback si aucun utilisateur n'est trouve.
    *
    * @param int $entity_id
    * @param int $fallback   utilisateur de repli (le technicien createur)
    *
    * @return int
    */
   static function getEntityRequester(int $entity_id, int $fallback): int
   {
      global $DB;

      $cfg       = PluginTicketmultipleConfig::getConfig();
      $recursive = (int)($cfg['recursive_users'] ?? 0) === 1;

      $entity_crit = ['glpi_profiles_users.entities_id' => $entity_id];
      if ($recursive) {
         $ancestors = getAncestorsOf('glpi_entities', $entity_id);
         if (!empty($ancestors)) {
            $entity_crit = [
               'OR' => [
                  ['glpi_profiles_users.entities_id' => $entity_id],
                  [
                     'glpi_profiles_users.entities_id'   => $ancestors,
                     'glpi_profiles_users.is_recursive'  => 1,
                  ],
               ],
            ];
         }
      }

      $where = array_merge($entity_crit, [
         'glpi_users.is_active'  => 1,
         'glpi_users.is_deleted' => 0,
      ]);

      $ids = [];
      $iterator = $DB->request([
         'SELECT'     => 'glpi_profiles_users.users_id',
         'DISTINCT'   => true,
         'FROM'       => 'glpi_profiles_users',
         'INNER JOIN' => [
            'glpi_users' => [
               'ON' => [
                  'glpi_profiles_users' => 'users_id',
                  'glpi_users'          => 'id',
               ],
            ],
         ],
         'WHERE'      => $where,
      ]);
      foreach ($iterator as $r) {
         $ids[] = (int)$r['users_id'];
      }

      if (empty($ids)) {
         return $fallback;
      }
      return $ids[array_rand($ids)];
   }

   /**
    * Cree un ticket (avec sa tache) par entite fournie.
    *
    * @param array $params
    *    - name              string  Titre
    *    - content           string  Description
    *    - type              int     Ticket::INCIDENT_TYPE | DEMAND_TYPE
    *    - itilcategories_id int
    *    - task_content      string
    *    - task_actiontime   int     duree en secondes
    *    - entities          int[]   entites cibles
    *
    * @return array  liste [ ['id'=>int, 'entity'=>string, 'name'=>string], ... ]
    */
   static function createTickets(array $params): array
   {
      $tech_id = (int)Session::getLoginUserID();

      $created = [];
      $entities = $params['entities'] ?? [];
      if (!is_array($entities)) {
         $entities = [$entities];
      }

      foreach ($entities as $entity_id) {
         $entity_id = (int)$entity_id;
         if ($entity_id < 0) {
            continue;
         }

         $requester = self::getEntityRequester($entity_id, $tech_id);

         $ticket = new Ticket();
         $input = [
            'name'                  => $params['name'],
            'content'               => $params['content'],
            'type'                  => (int)$params['type'],
            'itilcategories_id'     => (int)$params['itilcategories_id'],
            'entities_id'           => $entity_id,
            '_users_id_requester'   => $requester,
            '_users_id_assign'      => $tech_id,
            'status'                => CommonITILObject::ASSIGNED,
         ];
         $tid = $ticket->add($input);

         if ($tid) {
            // Tache associee, avec temps passe au nom du technicien createur
            if (trim((string)$params['task_content']) !== '' || (int)$params['task_actiontime'] > 0) {
               $task = new TicketTask();
               $task->add([
                  'tickets_id'    => $tid,
                  'content'       => $params['task_content'] !== '' ? $params['task_content'] : ' ',
                  'actiontime'    => (int)$params['task_actiontime'],
                  'users_id'      => $tech_id,
                  'users_id_tech' => $tech_id,
                  'state'         => Planning::DONE,
               ]);
            }

            $created[] = [
               'id'     => (int)$tid,
               'entity' => Dropdown::getDropdownName('glpi_entities', $entity_id),
               'name'   => $params['name'],
            ];
         }
      }

      return $created;
   }
}
