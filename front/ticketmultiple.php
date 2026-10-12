<?php
/*
 -------------------------------------------------------------------------
 TicketMultiple plugin for GLPI
 Page de creation de tickets multiples sous plusieurs entites.
 -------------------------------------------------------------------------
 */


Session::checkLoginUser();

if (!PluginTicketmultipleTicketmultiple::canUserAccess()) {
   throw new \Glpi\Exception\Http\AccessDeniedHttpException();
}

// URL explicite de la page : NE PAS utiliser $_SERVER['PHP_SELF'].
// Sous le routeur Symfony de GLPI 11, PHP_SELF se resout en /index.php, ce qui
// fait poster le formulaire vers "/" -> capture par CatchInventoryAgentRequestListener
// (le corps est alors parse en XML d'inventaire -> erreur "XML not well formed").
$self_url = PLUGIN_TICKETMULTIPLE_WEBDIR . "/front/ticketmultiple.php";

$created = [];
$errors  = [];

// ---- Traitement de la soumission ----
if (isset($_POST['create_tickets'])) {
   if (!Session::haveRight('plugin_ticketmultiple_use', CREATE)
       && PluginTicketmultipleConfig::getConfig()['access_mode'] == PluginTicketmultipleConfig::MODE_PROFILE) {
      throw new \Glpi\Exception\Http\AccessDeniedHttpException();
   }

   $name      = trim((string)($_POST['name'] ?? ''));
   $content   = trim((string)($_POST['content'] ?? ''));
   $task      = trim((string)($_POST['task_content'] ?? ''));
   $type      = (int)($_POST['type'] ?? 0);
   $category  = (int)($_POST['itilcategories_id'] ?? 0);
   $actiontime = (int)($_POST['task_actiontime'] ?? 0);
   $entities  = $_POST['entities'] ?? [];
   if (!is_array($entities)) {
      $entities = [$entities];
   }
   $entities = array_filter(array_map('intval', $entities), function ($v) {
      return $v >= 0;
   });

   // Tous les champs sont obligatoires
   if ($type <= 0) {
      $errors[] = __('Le type est obligatoire', 'ticketmultiple');
   }
   if ($category <= 0) {
      $errors[] = __('La categorie est obligatoire', 'ticketmultiple');
   }
   if ($name === '') {
      $errors[] = __('Le titre est obligatoire', 'ticketmultiple');
   }
   if ($content === '' || $content === '<p></p>') {
      $errors[] = __('La description est obligatoire', 'ticketmultiple');
   }
   if ($task === '' || $task === '<p></p>') {
      $errors[] = __('La description de la tache est obligatoire', 'ticketmultiple');
   }
   if ($actiontime <= 0) {
      $errors[] = __('La duree de la tache est obligatoire', 'ticketmultiple');
   }
   if (empty($entities)) {
      $errors[] = __('Selectionnez au moins une entite', 'ticketmultiple');
   }

   if (empty($errors)) {
      $created = PluginTicketmultipleTicketmultiple::createTickets([
         'name'              => $name,
         'content'           => $content,
         'type'              => $type,
         'itilcategories_id' => $category,
         'task_content'      => $task,
         'task_actiontime'   => $actiontime,
         'entities'          => $entities,
      ]);
   }
}

// ---- Affichage ----
if (Session::getCurrentInterface() == "central") {
   Html::header(__('Tickets multiples', 'ticketmultiple'), $_SERVER['PHP_SELF'], "helpdesk", "pluginticketmultiplemenu");
} else {
   Html::helpHeader(__('Tickets multiples', 'ticketmultiple'), $_SERVER['PHP_SELF']);
}

// Messages d'erreur
foreach ($errors as $e) {
   echo "<div class='alert alert-danger'>" . htmlspecialchars((string)$e) . "</div>";
}

// Resultat : liste cliquable des tickets crees
if (!empty($created)) {
   echo "<div class='card mb-3' style='max-width:900px;margin:auto;'>";
   echo "<div class='card-body'>";
   echo "<h3 class='mb-3'><i class='fa-solid fa-circle-check text-success me-2'></i>"
      . sprintf(_n('%d ticket cree', '%d tickets crees', count($created), 'ticketmultiple'), count($created))
      . "</h3>";
   echo "<table class='table table-hover'>";
   echo "<thead><tr><th>#</th><th>" . __('Entite') . "</th><th>" . __('Titre') . "</th></tr></thead><tbody>";
   foreach ($created as $c) {
      $url = Ticket::getFormURLWithID($c['id']);
      echo "<tr>";
      echo "<td><a href='" . $url . "' target='_blank'>#" . $c['id'] . "</a></td>";
      echo "<td>" . htmlspecialchars((string)$c['entity']) . "</td>";
      echo "<td><a href='" . $url . "' target='_blank'>" . htmlspecialchars((string)$c['name']) . "</a></td>";
      echo "</tr>";
   }
   echo "</tbody></table>";
   echo "<a href='" . $self_url . "' class='btn btn-outline-secondary'>"
      . __('Creer d\'autres tickets', 'ticketmultiple') . "</a>";
   echo "</div></div>";
}

// Formulaire de saisie (masque une fois les tickets crees)
if (empty($created)) {
echo "<form method='post' action='" . htmlspecialchars($self_url) . "'>";

echo "<div class='card' style='max-width:900px;margin:auto;'>";
echo "<div class='card-body'>";
echo "<h3 class='mb-3'><i class='fa-solid fa-layer-group me-2'></i>" . __('Creer des tickets multiples', 'ticketmultiple') . "</h3>";

echo "<div class='row'>";

// Type
echo "<div class='col-md-6 mb-3'>";
echo "<label class='form-label'>" . __('Type') . " *</label>";
Ticket::dropdownType('type', ['value' => $_POST['type'] ?? Ticket::INCIDENT_TYPE, 'width' => '100%']);
echo "</div>";

// Categorie
echo "<div class='col-md-6 mb-3'>";
echo "<label class='form-label'>" . __('Category') . " *</label>";
Dropdown::show('ITILCategory', [
   'name'  => 'itilcategories_id',
   'value' => $_POST['itilcategories_id'] ?? 0,
   'width' => '100%',
]);
echo "</div>";

// Titre
echo "<div class='col-12 mb-3'>";
echo "<label class='form-label'>" . __('Title') . " *</label>";
echo Html::input('name', ['value' => $_POST['name'] ?? '', 'class' => 'form-control']);
echo "</div>";

// Description (editeur enrichi, comme un vrai ticket)
echo "<div class='col-12 mb-3'>";
echo "<label class='form-label'>" . __('Description') . " *</label>";
Html::textarea([
   'name'              => 'content',
   'value'            => $_POST['content'] ?? '',
   'enable_richtext'   => true,
   'enable_fileupload' => false,
   'enable_images'     => false,
   'rows'             => 6,
]);
echo "</div>";

echo "<hr>";
echo "<h5 class='mb-3'>" . __('Tache (ajoutee a chaque ticket)', 'ticketmultiple') . "</h5>";

// Contenu tache (editeur enrichi)
echo "<div class='col-md-8 mb-3'>";
echo "<label class='form-label'>" . __('Description de la tache', 'ticketmultiple') . " *</label>";
Html::textarea([
   'name'              => 'task_content',
   'value'            => $_POST['task_content'] ?? '',
   'enable_richtext'   => true,
   'enable_fileupload' => false,
   'enable_images'     => false,
   'rows'             => 3,
]);
echo "</div>";

// Duree tache
echo "<div class='col-md-4 mb-3'>";
echo "<label class='form-label'>" . __('Duration') . " *</label>";
Dropdown::showTimeStamp('task_actiontime', [
   'value'         => $_POST['task_actiontime'] ?? 0,
   'addfirstminutes' => true,
   'width'         => '100%',
]);
echo "</div>";

echo "<hr>";

// Entites (multiple)
echo "<div class='col-12 mb-3'>";
echo "<label class='form-label'>" . __('Entites cibles (un ticket sera cree par entite)', 'ticketmultiple') . " *</label>";
Dropdown::show('Entity', [
   'name'     => 'entities[]',
   'multiple' => true,
   'value'    => $_POST['entities'] ?? [],
   'width'    => '100%',
   'entity'   => $_SESSION['glpiactiveentities'],
]);
echo "</div>";

echo "</div>"; // row

echo "<div class='text-center mt-2'>";
echo Html::submit(__('Creer les tickets', 'ticketmultiple'),
   ['name' => 'create_tickets', 'class' => 'btn btn-primary', 'icon' => 'fa-solid fa-layer-group']);
echo "</div>";

echo "</div></div>"; // card-body, card
Html::closeForm();
} // fin if (empty($created))

if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}
