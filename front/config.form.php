<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 -------------------------------------------------------------------------
 */


Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
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
