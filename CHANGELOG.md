# Journal des changements

## 1.0.1 — 2026-11-05

**Après la mise à jour : cliquer « Mettre à jour » dans Configuration → Plugins.** Aucune migration de base.

- **Statut des tickets créés**, choisi tout en haut du formulaire : **Nouveau** par défaut, parmi les statuts que le
  profil autorise à la création (liste native de GLPI). Le statut choisi est conservé : GLPI ne le recalcule pas
  (drapeau natif `_do_not_compute_status`), sinon un ticket « Nouveau » avec un technicien attribué passerait de
  lui-même en « En cours (Attribué) ». Avant : toujours « En cours (Attribué) ».
- **Tâche facultative** : interrupteur « Ajouter une tâche à chaque ticket », activé par défaut. Désactivé, les
  champs de la tâche sont masqués et aucune tâche n'est créée ; activé, sa description et sa durée sont
  obligatoires. Après une erreur, l'interrupteur garde sa position.
- **Entités cibles** : liste native de GLPI à choix multiples, avec les boutons « Tout » / « Aucun » (la liste
  des entités chargée au fil de la saisie n'en a pas). Entités actives de la session, nom complet.
- **Champs obligatoires** : astérisque rouge, celui des formulaires natifs de GLPI (`span.required`).

## 1.0.0 — 2026-06-17

Première version : création en une fois d'un ticket identique par entité sélectionnée, avec sa tâche.
