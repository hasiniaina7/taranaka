# 000 — Project Foundation

## 1. Audit du code existant

Base : fork de `MGeurts/genealogy` (Laravel 12, PHP 8.4, Jetstream Teams, Livewire 4, TallStackUI, Filament).

### 1.1 Ce qui est déjà en place et à conserver tel quel

- **`Person`** (`app/Models/Person.php`) : firstname/surname/birthname/nickname, sex, gender_id, father_id/mother_id/parents_id, dob/yob/pob, dod/yod/pod, adresse, photo, metadata (clé/valeur), events, soft deletes, media (Spatie MediaLibrary), activity log (Spatie Activitylog).
- **`Couple`** (`app/Models/Couple.php`) : person1_id/person2_id, date_start/date_end, is_married/has_ended, unique(person1_id, person2_id, date_start) → partenaires multiples et remariages déjà supportés.
- **Relations de parenté** : père/mère/couple parental (`parents_id` → `Couple`), enfants via `HasManyMerged` sur father_id/mother_id OU parents_id, demi-frères/soeurs, fratrie complète — logique déjà correcte et réutilisable.
- **Requêtes récursives** : `app/Queries/{MySql,PgSql,SQLite}{Ancestors,Descendants}Query.php` — CTE récursives déjà écrites et portées sur 3 moteurs de BDD. **À réutiliser sans réécriture** pour F004/F005.
- **Recherche** : `Person::scopeSearch()` — recherche multi-mots sur firstname/surname/birthname/nickname, échappement LIKE correct. Réutilisable pour F006, mais actuellement scopée à une team (voir 1.2).
- **Détection de similarité (embryonnaire)** : `Person::scopeSimilarTo()` existe déjà — base de départ pour F009, mais sans scoring.
- **Journal d'activité** : Spatie Activitylog déjà branché sur Person, Couple, Team, User — base réutilisable pour F010 (contributions/modération), mais c'est un log technique (avant/après), pas un flux de proposition/validation.

### 1.2 Obstacle structurel majeur : le couplage Team = tenant = arbre

**C'est le point bloquant identifié par l'audit.**

- `people.team_id` et `couples.team_id` sont des colonnes uniques (une personne appartient à **une seule** team).
- `Person::booted()` et `Couple::booted()` posent un **global scope** qui filtre systématiquement par `auth()->user()->currentTeam->id`.
- Toutes les routes `people/*` (`routes/web.php`) sont derrière `auth:sanctum + verified` — **il n'existe aujourd'hui aucune route publique de consultation** (seules `/`, `/about`, `/help` sont publiques).

Conséquence directe : le modèle de données actuel **empêche par construction** deux exigences produit du cadrage :
1. F04 (connexion entre familles) — une personne ne peut pas être rattachée à plusieurs lignées puisqu'elle n'a qu'un seul `team_id`.
2. Le persona "visiteur public" — aucune personne n'est consultable sans compte + appartenance à la team.

C'est un changement de modèle de données, pas juste une nouvelle fonctionnalité — d'où sa place en fondation (spec 000) avant tout le reste.

### 1.3 Logique métier à ne pas perdre en migrant

- `Person::isDeletable()` / `Team::isDeletable()` — règles d'intégrité (pas de suppression si enfants/couples/personnes existent).
- `Team::performDeveloperDelete()` — cascade de suppression développeur (couples puis personnes puis détachement users).
- `Person::timeline()` — agrège naissance, décès, unions, enfants et `PersonEvent` custom en une chronologie triée — logique métier non triviale, entièrement dans le modèle (pas dans une vue Livewire), donc réutilisable telle quelle.
- `PersonMetadata` (clé/valeur, ex. cimetière lat/long) — mécanisme d'extension déjà générique.

### 1.4 Ce qui n'existe pas encore (à construire dans les specs suivantes)

| Manque | Spec cible |
|---|---|
| Entité `Lineage` + pivot `LineagePerson` (N:N personne ↔ lignée) | 001/002 |
| Découplage `team_id` (tenant/permissions) vs appartenance à une lignée | 002 |
| Champ de confidentialité sur `Person` (vivant/mort, visibilité publique) | 007 |
| Routes et contrôleurs publics (hors auth) pour fiche personne / arbre / recherche | 003–006 |
| Score de similarité exploitable (au-delà de `scopeSimilarTo` brut) | 009 |
| Flux proposition → validation par un modérateur (au-delà du log technique Activitylog) | 008 |
| Endpoints REST en lecture | 010 |

### 1.5 Risque identifié à surveiller

Toute feature qui touche `Person`/`Couple` doit passer par le global scope `team`. Une fois `Lineage` introduit, ce scope devra soit être retiré au profit d'un filtrage explicite par lignée/visibilité, soit cohabiter temporairement avec lui — cette décision est le premier arbitrage technique de la spec 001/002, pas de la 000.

---

## 2. Constitution du projet (règles non négociables)

1. **Une personne = une seule entité en base**, quel que soit le nombre de lignées auxquelles elle est rattachée. Aucune duplication de `Person` pour représenter une appartenance à plusieurs familles.
2. **Les personnes vivantes sont protégées par défaut.** Une personne sans `dod`/`yod` connu n'est jamais exposée publiquement sans opt-in explicite.
3. **Stack backend = Laravel 12 + Livewire 4 + TallStackUI**, y compris pour l'interface publique du MVP. Pas de réécriture Next.js avant que le moteur métier (lignées, arbre, doublons) soit validé — Next.js reste une option pour une spec ultérieure, hors MVP.
4. **MySQL 8 reste la base de référence.** Le support PgSQL/SQLite existant dans `app/Queries` est conservé mais n'est pas une exigence bloquante pour le MVP.
5. **Toute nouvelle fonctionnalité métier doit avoir un test Pest** (feature ou unit) avant d'être considérée terminée.
6. **Le code CTE existant (`app/Queries/*`) n'est pas réécrit**, seulement adapté au nouveau modèle `Lineage` si nécessaire.
7. **`team_id` change de rôle progressivement** : d'un conteneur d'arbre vers un simple groupe de contributeurs/permissions. On ne supprime pas Teams/Jetstream, on le repositionne.
8. **Aucune donnée sensible (adresse, téléphone, décès récent) n'est exposée côté public** sans passer par la règle de confidentialité définie en spec 007.
9. **Les migrations de colonnes existantes conservent tous les attributs déjà définis** (contrainte Laravel 12 déjà en vigueur dans ce projet).

---

## 3. Portée du MVP (rappel)

Voir roadmap condensée validée avec l'utilisateur : specs 001 à 010, stack Laravel/Livewire pour tout le MVP (y compris le public), API REST posée en lecture seule (010) sans consommateur externe pour l'instant. GEDCOM, sources avancées, médias avancés et refonte Next.js sont hors périmètre MVP.

## 4. Prochaine étape

Spec **001-lineages** : modéliser `Lineage` et `LineagePerson`, et trancher l'arbitrage sur le global scope `team` mentionné en 1.5.
