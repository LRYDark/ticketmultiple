# TicketMultiple — Plugin GLPI 11

Crée en une seule fois **plusieurs tickets identiques**, un par entité sélectionnée.

## Fonctionnement

Menu **Assistance → Tickets multiples**.

Sur la page on choisit :
- le **type** du ticket (Incident / Demande) ;
- la **catégorie** ;
- le **titre** ;
- la **description** ;
- la **tâche** (description + **durée**) ajoutée à chaque ticket ;
- les **entités** cibles (sélection multiple).

→ Un ticket identique est créé **par entité** sélectionnée.

### Acteurs des tickets créés
- **Attribué à (technicien)** : l'utilisateur qui crée les tickets.
- **Temps passé de la tâche** : au nom de ce même technicien.
- **Demandeur** : un utilisateur habilité sur l'entité (tiré au hasard s'il y en a plusieurs).
  S'il n'y a **aucun** utilisateur habilité sur l'entité → le technicien créateur devient demandeur.

### Résultat
Après création, la liste **cliquable** des tickets créés s'affiche (n° + entité + titre)
pour les ouvrir un par un.

## Droits / accès

Page de configuration : **Configuration → Plugins → TicketMultiple**.

- **Mode d'accès** :
  - *Tous les utilisateurs des profils autorisés* : droit `plugin_ticketmultiple_use` (onglet Profil).
  - *Uniquement les utilisateurs choisis* : seule la liste d'utilisateurs sélectionnés a accès.
- **Demandeur — récursif** :
  - *Non* : uniquement les utilisateurs habilités **directement** sur l'entité.
  - *Oui* : inclut aussi les habilitations **récursives** des entités parentes.

## Compatibilité
GLPI 11.0.x.
