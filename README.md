# Educaxio — Cahier des charges

Application web de gestion pédagogique développée dans le cadre d'un projet de fin d'études,
ciblant les enseignants et les directions d'école de l'enseignement officiel de la Fédération
Wallonie-Bruxelles (FWB).

---

## Contexte

Dans l'enseignement officiel belge, les enseignants jonglent chaque jour avec des outils
éparpillés : un registre papier pour les présences, un agenda personnel pour les devoirs,
des fichiers Excel pour les horaires. La communication entre collègues et avec la direction
reste souvent informelle.

Educaxio centralise ces tâches dans une application accessible depuis n'importe quel appareil,
pensée pour s'intégrer naturellement dans le rythme d'une journée scolaire.

---

## Personas

### Sarah — Enseignante (mono-école)

Enseigne dans un seul établissement. Gère ses présences, ses notes de cours et ses devoirs
au quotidien. Utilise principalement son ordinateur en classe et pour préparer
sa semaine.

---

### Thomas — Enseignant (multi-école)

Enseigne dans deux établissements différents. Son horaire, ses classes et ses créneaux
horaires sont distincts selon l'école. Il doit pouvoir naviguer entre ses deux contextes
sans confusion.

---

### Marc — Directeur adjoint

Gère l'administratif d'un établissement : élèves, enseignants, classes, années scolaires.
N'enseigne pas mais a besoin d'une vue d'ensemble et du contrôle sur la structure de l'école.

---

## Scénarios d'utilisation

### 1. S'inscrire et rejoindre un établissement

Sarah crée son compte en trois étapes : informations personnelles, matières enseignées, puis
sélection de son établissement. Thomas, lui, sélectionne ses deux écoles lors de l'inscription.
Dans les deux cas, l'accès attend la validation de Marc, qui approuve ou refuse depuis son
tableau de bord. À l'approbation, un horaire vierge est automatiquement créé dans chaque école.

---

### 2. Construire son emploi du temps

Sarah clique sur une cellule vide. Comme elle n'est rattachée qu'à une seule école, le champ
établissement est déjà rempli. Si elle n'enseigne qu'une matière dans la classe choisie, le
cours est pré-rempli aussi. Elle confirme et le créneau apparaît. Pour déplacer un cours,
elle clique dessus, change le jour ou l'heure et valide.

Thomas, rattaché à deux écoles, choisit manuellement l'établissement à chaque ajout. Les
heures de début et de fin affichées dans la grille sont celles que Marc a configurées pour
chaque école dans ses paramètres.

---

### 3. Prendre les présences

Depuis son tableau de bord, Sarah voit l'horaire du jour avec un bouton « Présences » par
cours. Elle y accède, ajuste les statuts des élèves absents ou en retard, et valide. Les
statistiques se mettent à jour avant même la validation.

---

### 4. Programmer des devoirs et interrogations

Sarah crée une entrée dans l'agenda avec un type, un titre et une date. L'application
déduit automatiquement le créneau horaire associé. Son tableau de bord lui donne une vue
synthétique des prochains devoirs et interrogations, modifiables directement depuis là.

---

### 5. Tenir le journal de cours

Après chaque séance, Sarah saisit une note dans le journal de la classe. La sauvegarde est
automatique. Elle peut consulter l'historique de toutes les séances passées.

---

### 6. Consulter la fiche d'un élève

Marc — ou Sarah pour ses propres cours — ouvre la fiche d'un élève pour voir son historique
d'absences et son taux de présence global.

---

### 7. Gérer les classes et les élèves

Marc crée les groupes (classes) pour l'année en cours et y ajoute les élèves, soit en les
choisissant dans la liste de l'école, soit en les créant directement. Il peut modifier la
composition d'un groupe à tout moment, retirer un élève ou le déplacer dans une autre classe.

Sarah, de son côté, a accès en lecture à la liste de ses classes. Elle peut consulter la
liste des élèves, rechercher un élève par nom et accéder à sa fiche individuelle depuis la
vue de classe.

---

### 8. Consulter et filtrer l'agenda pédagogique

Sarah veut avoir une vue complète sur ce qu'elle a programmé pour le mois à venir. Elle
ouvre l'agenda, bascule sur l'onglet « Devoirs & Interrogations » et filtre par classe ou
par type. Elle peut trier par date, par classe ou par matière. Les entrées passées sont
accessibles dans l'onglet « Passés ».

---

### 9. Configurer son profil et ses paramètres

En début d'année, Sarah met à jour ses informations, ajoute une photo de profil et vérifie
que les cours qui lui sont assignés sont corrects. Elle peut en ajouter ou en retirer depuis
ses paramètres.

Marc configure les heures de début et de fin de chaque créneau horaire pour son école
(par exemple : 1ère heure de 8h20 à 9h10). L'application l'empêche de saisir une heure de
début inférieure à l'heure de fin du créneau précédent. Ces heures s'affichent ensuite dans
la grille horaire des enseignants.

---

### 10. Administrer l'école

Marc crée une nouvelle année académique, l'active et archive l'ancienne. Il gère les
enseignants de l'école, leur attribue des cours et traite les demandes d'adhésion depuis
son tableau de bord. Il peut également gérer les matières disponibles et les niveaux de
langue moderne associés à certains cours.

---

## Rôles

| Fonctionnalité | Admin école | Enseignant |
| -------------- | :---------: | :--------: |
| Tableau de bord admin | ✓ | — |
| Tableau de bord enseignant | — | ✓ |
| Années académiques | ✓ | — |
| Gestion des groupes | ✓ | lecture |
| Gestion des élèves | ✓ | — |
| Gestion des enseignants | ✓ | — |
| Gestion des cours | ✓ | — |
| Présences | — | ✓ |
| Emploi du temps | — | ✓ |
| Journal / Devoirs | — | ✓ |
| Fiche élève | ✓ (global) | ✓ (ses cours) |
| Stats présences | ✓ (global) | ✓ (ses cours) |

---

## Technologies

| Couche | Stack |
| ------ | ----- |
| Backend | Laravel 13 · PHP 8.5 |
| Surcouche SPA | Inertia.js 3 |
| Frontend | Vue 3 · TypeScript · Pinia |
| Style | Tailwind CSS 4 |
| Base de données | MySQL |
| Build | Vite 8 |
| Tests | Pest 4 |

---

## Installation

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed

composer run dev
```

> `composer run dev` lance en parallèle le serveur Laravel, la queue, les logs et Vite.

---

## Perspectives d'évolution

- Suivi des évaluations et calcul de moyennes
- Notifications (absences, devoirs à venir)
- Export PDF (relevés de présences, horaires)
- Accès parents/élèves
- Chat entre membres du personnel
