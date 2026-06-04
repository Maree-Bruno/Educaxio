# Educaxio — Application web de gestion pédagogique

Application web de gestion pédagogique développée dans le cadre d'un projet de fin d'études, ciblant les enseignants 
et les directions d'école de l'enseignement officiel de la Fédération Wallonie-Bruxelles (FWB).

---

## Technologies

| Couche | Stack |
|---|---|
| Backend | Laravel 13 · PHP 8.5 |
| Surcouche SPA | Inertia.js 3 |
| Frontend | Vue 3 · TypeScript · Pinia |
| Style | Tailwind CSS 4 |
| Base de données | MySQL |
| Build | Vite 8 |
| Tests | Pest 4 |

---

## Fonctionnalités implémentées

### Authentification & accès école

- Inscription avec sélection des matières enseignées
- Demande d'adhésion à une école (approbation par un admin)
- Page d'attente avec suivi du statut de la demande
- Rôles distincts : **Admin** et **Enseignant**

---

### Tableau de bord

**Admin**
- Vue d'ensemble par école : nombre d'élèves, d'enseignants, de cours, de demandes en attente
- Liste des demandes d'adhésion récentes (approbation / refus)
- Liste des élèves récemment inscrits
- Liste des enseignants

**Enseignant**
- Horaire du jour avec lien direct vers la prise de présences
- Tableau des devoirs & interrogations à venir (édition, suppression)
- Liste des classes assignées

---

### Liste de classe

- Création, modification et suppression de groupes (classes)
- Gestion des élèves : ajout depuis la liste école, création directe, retrait du groupe
- Tri et recherche dans la liste des élèves
- Statistiques de présence scoped par rôle (global pour l'admin, limité aux cours du prof pour l'enseignant)
- Fiche détaillée de l'élève avec historique des absences et statistiques

---

### Présences

- Sélection par date et créneau horaire (URL lisible `?creneau=…`)
- Saisie des statuts par élève : Présent / Absent / Arrivée tardive / Exclu
- Bouton "Tous présents", validation groupée
- Filtrage et recherche dans la liste (statut, nom)
- Pagination côté client
- Statistiques de présence en temps réel (mise à jour avant validation)
- Mode lecture seule pour les dates futures
- Sélection automatique du créneau actif selon l'heure

---

### Journal de classe & Agenda

**Journal de cours**
- Note de cours par séance (sauvegarde automatique avec debounce)
- Vue historique de toutes les séances

**Devoirs & Interrogations**
- Création, édition, suppression de devoirs et interrogations
- Programmation avec date, type, titre et description
- Vue "à venir" / "passés"
- Filtres par classe, école, type, tri par date / classe / matière
- Prochaine date de cours calculée automatiquement depuis l'horaire

---

### Emploi du temps

- Grille hebdomadaire par jour et créneau
- Ajout, suppression de créneaux de cours
- Configuration des heures de début/fin par école (admin)
- Indicateur du cours en cours en temps réel
- Vue mobile (liste par jour)

---

### Administration école

- Gestion des élèves : création, modification, suppression
- Gestion des enseignants : consultation, retrait de l'école
- Gestion des cours : création, modification, suppression, affectation des enseignants et du niveau LM (pour les langues)
- Approbation / refus des demandes d'adhésion

---

### Profil utilisateur

- Modification du nom, email, photo de profil
- Changement de mot de passe
- Suppression du compte

---

## Rôles et permissions

| Fonctionnalité | Admin école | Enseignant |
|---|:---:|:---:|
| Tableau de bord admin | ✓ | — |
| Tableau de bord enseignant | — | ✓ |
| Gestion des groupes | ✓ | lecture |
| Gestion des élèves (école) | ✓ | — |
| Gestion des enseignants | ✓ | — |
| Gestion des cours | ✓ | — |
| Présences | — | ✓ |
| Horaire | — | ✓ |
| Journal / Devoirs | — | ✓ |
| Fiche élève | ✓ (global) | ✓ (ses cours) |
| Stats présences | ✓ (global) | ✓ (ses cours) |

---

## Installation

```bash
# Cloner et installer les dépendances
composer install
npm install

# Configuration
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate --seed

# Développement
composer run dev
```

> `composer run dev` lance en parallèle le serveur Laravel, la queue, les logs et Vite.

---

## Perspectives d'évolution

- Suivi des évaluations et calcul de moyennes
- Notifications (absences, devoirs)
- Export PDF (relevés, horaires)
- Accès parents
- Formule d'abonnement multi-école
