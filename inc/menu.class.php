<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Entree de menu sous Assistance.
 */
class PluginTicketmultipleMenu extends CommonGLPI
{
   static string $rightname = 'plugin_ticketmultiple_use';

   static function getMenuName()
   {
      return __('Tickets multiples', 'ticketmultiple');
   }

   static function getMenuContent()
   {
      $menu = [];

      if (PluginTicketmultipleTicketmultiple::canUserAccess()) {
         $menu['title'] = self::getMenuName();
         $menu['page']  = PLUGIN_TICKETMULTIPLE_NOTFULL_WEBDIR . "/front/ticketmultiple.php";
         $menu['icon']  = self::getIcon();
      }

      return $menu;
   }

   static function getIcon()
   {
      return "fa-solid fa-layer-group";
   }
}
