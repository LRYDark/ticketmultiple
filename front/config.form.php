<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
   if (!empty($_POST['plugin_ticketmultiple_csrf_token'])) {
      Session::checkCSRF(
         ['_glpi_csrf_token' => (string)$_POST['plugin_ticketmultiple_csrf_token']],
         true
      );
   } else {
      Session::checkCSRF($_POST);
   }
   PluginTicketmultipleConfig::updateConfig($_POST);
   Session::addMessageAfterRedirect(__('Configuration enregistree', 'ticketmultiple'));
   Html::back();
}

Html::header(
   __('Tickets multiples', 'ticketmultiple'),
   $_SERVER['PHP_SELF'],
   "config",
   "plugins"
);

PluginTicketmultipleConfig::showConfigForm();

Html::footer();
