<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginTicketmultipleProfile extends Profile
{
   static string $rightname = "profile";

   static function getAllRights()
   {
      return [
         [
            'itemtype' => 'PluginTicketmultipleTicketmultiple',
            'label'    => __('Tickets multiples', 'ticketmultiple'),
            'field'    => 'plugin_ticketmultiple_use',
            'rights'   => [
               READ   => __('Read'),
               CREATE => __('Create'),
            ],
         ],
      ];
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == 'Profile') {
         return __('Tickets multiples', 'ticketmultiple');
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == 'Profile') {
         $profile = new self();
         $profile->showForm($item->getField('id'));
      }
      return true;
   }

   static function addDefaultProfileInfos($profiles_id, $rights)
   {
      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable('glpi_profilerights',
            ['profiles_id' => $profiles_id, 'name' => $right])) {
            $myright = [
               'profiles_id' => $profiles_id,
               'name'        => $right,
               'rights'      => $value,
            ];
            $profileRight->add($myright);
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   static function createFirstAccess($profiles_id)
   {
      self::addDefaultProfileInfos(
         $profiles_id,
         ['plugin_ticketmultiple_use' => CREATE | READ]
      );
   }

   public function showForm($ID, array $options = [])
   {
      echo "<div class='firstbloc'>";
      $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
      if ($canedit) {
         $profile = new Profile();
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $profile = new Profile();
      $profile->getFromDB($ID);

      $profile->displayRightsChoiceMatrix(self::getAllRights(), [
         'canedit'       => $canedit,
         'default_class' => 'tab_bg_2',
         'title'         => __('Tickets multiples', 'ticketmultiple'),
      ]);

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}
