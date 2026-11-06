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

// Tache ajoutee a chaque ticket : activee par defaut ; apres une erreur, l'interrupteur garde la position envoyee.
$add_task = !isset($_POST['create_tickets']) || !empty($_POST['add_task']);

// ---- Traitement de la soumission ----
if (isset($_POST['create_tickets'])) {
   if (!Session::haveRight('plugin_ticketmultiple_use', CREATE)
       && PluginTicketmultipleConfig::getConfig()['access_mode'] == PluginTicketmultipleConfig::MODE_PROFILE) {
      throw new \Glpi\Exception\Http\AccessDeniedHttpException();
   }

   $name      = trim((string)($_POST['name'] ?? ''));
   $content   = trim((string)($_POST['content'] ?? ''));
   $task      = trim((string)($_POST['task_content'] ?? ''));
   $status    = (int)($_POST['status'] ?? Ticket::INCOMING);
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

   // Tous les champs sont obligatoires (ceux de la tache seulement si elle est ajoutee)
   // Statut : un de ceux que le profil autorise a la creation (depuis "Nouveau"), comme le formulaire de GLPI.
   if (!array_key_exists($status, Ticket::getAllowedStatusArray(Ticket::INCOMING))) {
      $errors[] = __('Ce statut n\'est pas autorise pour votre profil', 'ticketmultiple');
   }
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
   if ($add_task) {
      if ($task === '' || $task === '<p></p>') {
         $errors[] = __('La description de la tache est obligatoire', 'ticketmultiple');
      }
      if ($actiontime <= 0) {
         $errors[] = __('La duree de la tache est obligatoire', 'ticketmultiple');
      }
   }
   if (empty($entities)) {
      $errors[] = __('Selectionnez au moins une entite', 'ticketmultiple');
   }

   if (empty($errors)) {
      $created = PluginTicketmultipleTicketmultiple::createTickets([
         'name'              => $name,
         'content'           => $content,
         'status'            => $status,
         'type'              => $type,
         'itilcategories_id' => $category,
         'add_task'          => $add_task,
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

// Marque des champs obligatoires : celle des formulaires natifs de GLPI (asterisque rouge).
$required = "<span class='required'>*</span>";

echo "<div class='row'>";

// Statut des tickets crees, "Nouveau" par defaut : les statuts que le profil autorise a la creation.
echo "<div class='col-md-6 mb-3'>";
echo "<label class='form-label'>" . __('Status') . $required . "</label>";
Ticket::dropdownStatus([
   'name'              => 'status',
   'value'             => $_POST['status'] ?? Ticket::INCOMING,
   'showtype'          => 'allowed',
   'value_calculation' => Ticket::INCOMING,
   'width'             => '100%',
]);
echo "</div>";
echo "<div class='w-100'></div>";

// Type
echo "<div class='col-md-6 mb-3'>";
echo "<label class='form-label'>" . __('Type') . $required . "</label>";
Ticket::dropdownType('type', ['value' => $_POST['type'] ?? Ticket::INCIDENT_TYPE, 'width' => '100%']);
echo "</div>";

// Categorie
echo "<div class='col-md-6 mb-3'>";
echo "<label class='form-label'>" . __('Category') . $required . "</label>";
Dropdown::show('ITILCategory', [
   'name'  => 'itilcategories_id',
   'value' => $_POST['itilcategories_id'] ?? 0,
   'width' => '100%',
]);
echo "</div>";

// Titre
echo "<div class='col-12 mb-3'>";
echo "<label class='form-label'>" . __('Title') . $required . "</label>";
echo Html::input('name', ['value' => $_POST['name'] ?? '', 'class' => 'form-control']);
echo "</div>";

// Description (editeur enrichi, comme un vrai ticket)
echo "<div class='col-12 mb-3'>";
echo "<label class='form-label'>" . __('Description') . $required . "</label>";
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

// Tache facultative : l'interrupteur affiche ou masque ses champs (activee par defaut).
echo "<div class='col-12 mb-3'>";
echo "<label class='form-check form-switch mb-0'>";
echo "<input type='checkbox' class='form-check-input' id='ticketmultiple_add_task' name='add_task' value='1'"
   . ($add_task ? " checked" : "") . ">";
echo "<span class='form-check-label fw-bold'>" . __('Ajouter une tache a chaque ticket', 'ticketmultiple') . "</span>";
echo "</label>";
echo "</div>";

echo "<div id='ticketmultiple_task_fields' class='col-12" . ($add_task ? "" : " d-none") . "'>";
echo "<div class='row'>";

// Contenu tache (editeur enrichi)
echo "<div class='col-md-8 mb-3'>";
echo "<label class='form-label'>" . __('Description de la tache', 'ticketmultiple') . $required . "</label>";
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
echo "<label class='form-label'>" . __('Duration') . $required . "</label>";
Dropdown::showTimeStamp('task_actiontime', [
   'value'         => $_POST['task_actiontime'] ?? 0,
   'addfirstminutes' => true,
   'width'         => '100%',
]);
echo "</div>";

echo "</div></div>"; // row, ticketmultiple_task_fields
echo Html::scriptBlock("
   document.getElementById('ticketmultiple_add_task').addEventListener('change', function () {
      document.getElementById('ticketmultiple_task_fields').classList.toggle('d-none', !this.checked);
   });
");

echo "<hr>";

// Entites (multiple)
echo "<div class='col-12 mb-3'>";
echo "<label class='form-label'>" . __('Entites cibles (un ticket sera cree par entite)', 'ticketmultiple') . $required . "</label>";
// Liste native de GLPI à choix multiples (boutons « Tout » / « Aucun ») : entités actives de la session, nom complet
$entity_options = [];
$active_entities = $_SESSION['glpiactiveentities'] ?? [];
$active_entities = array_values(array_map('intval', is_array($active_entities) ? $active_entities : [$active_entities]));
if (!empty($active_entities)) {
   global $DB;
   foreach ($DB->request([
      'SELECT' => ['id', 'completename'],
      'FROM'   => Entity::getTable(),
      'WHERE'  => ['id' => $active_entities],
      'ORDER'  => ['completename'],
   ]) as $entity_row) {
      $entity_options[(int) $entity_row['id']] = html_entity_decode((string) $entity_row['completename'], ENT_QUOTES, 'UTF-8');
   }
}
$selected_entities = $_POST['entities'] ?? [];
$selected_entities = array_values(array_map('intval', array_filter(is_array($selected_entities) ? $selected_entities : [$selected_entities], 'is_scalar')));
Dropdown::showFromArray('entities', $entity_options, [
   'multiple' => true,
   'values'   => $selected_entities,
   'width'    => '100%',
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
