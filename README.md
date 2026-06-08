# Fils Attente / MediOffice Workspace

Application Laravel 12 pour la gestion du flux patient, de la radiologie RIS, du dossier clinique et des modules annexes du cabinet.

Le projet est organise en modules pour garder une base lisible, testable et facile a faire evoluer.

## Fonctionnalites principales

### Gestion du flux patient
- Accueil et tableau de bord multi-profils.
- File d'attente en temps reel avec statuts, priorites et filtrage.
- Gestion des tickets, guichets, agents et superviseurs.
- Billetterie et ecran public.

### Dossier clinique
- Consultation du dossier patient.
- Parcours clinique et suivi des actes.
- Integration avec les modules metier du cabinet.

### Module RIS Radiologie
- Creation et suivi des examens.
- Liaison avec Orthanc / PACS.
- Synchronisation des images et du worklist.
- Edition des comptes-rendus avec editeur riche.
- Historique des examens par patient.
- Envoi du compte-rendu signe par email.

### Editeur de compte-rendu RIS
- Gras, italique, souligne.
- Listes a puces et numerotees.
- Alignement gauche, centre, droite.
- Annuler / refaire.
- Styles de bloc : paragraphe, titre, sous-titre.
- Mode plein ecran.
- Chargement de modele Word au format .docx.
- Export du contenu en .docx.
- Insertion de snippets et de champs de fusion.
- Utilisation de templates de compte-rendu.

### Gestion des templates RIS
- Creation, modification et suppression de templates.
- Classement par categorie.
- Editeur riche identique a la fiche examen.
- Import Word et export Word.
- Mode plein ecran pour la redaction.

### Autres modules metier
- Planning et types d'actes.
- Disponibilites et multi-specialites.
- Facturation.
- Modules de suivi et de paramétrage cabinet.

## Stack technique
- Laravel 12
- PHP 8.2+
- MySQL / MariaDB
- Vite
- Alpine.js
- TailwindCSS 4
- Tabler Icons / Tabler Core
- Modules Laravel dans `Modules/`

## Prerequis
- PHP 8.2 ou superieur
- Composer
- Node.js 18+ ou 20+
- MySQL / MariaDB
- Un serveur web local type Apache, Nginx ou Laragon / XAMPP

## Installation

### 1. Cloner le projet
```bash
git clone <url-du-repo>
cd fils_attente
```

### 2. Installer les dependances PHP
```bash
composer install
```

### 3. Installer les dependances front
```bash
npm install
```

### 4. Configurer l'environnement
Copier le fichier `.env.example` vers `.env`, puis ajuster les valeurs de base :
- `APP_NAME`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Pour Orthanc sous Windows, utiliser des chemins avec `/` au lieu de `\`.
Exemple : `D:/Orthanc Server/Worklists`

Variables RIS utiles :
- `RIS_ENABLED=true`
- `ORTHANC_BASE_URL=http://127.0.0.1:8042`
- `ORTHANC_USERNAME=orthanc`
- `ORTHANC_PASSWORD=orthanc`
- `ORTHANC_WORKLIST_DIRECTORY=D:/Orthanc Server/Worklists`

### 5. Generer la cle Laravel
```bash
php artisan key:generate
```

### 6. Lancer les migrations
```bash
php artisan migrate
```

### 7. Compiler les assets
```bash
npm run build
```

### 8. Demarrer l'application
```bash
php artisan serve
```

Pour le developpement complet :
```bash
npm run dev
```

## Commandes utiles

### Developpement complet
Le projet declare deja une commande composee dans `composer.json` :
```bash
composer run dev
```
Cette commande lance :
- `php artisan serve`
- la queue Laravel
- les logs en temps reel
- `vite`

### Tests
```bash
composer test
```

### Nettoyage du cache de configuration
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Structure du projet
- `app/` : logique applicative commune
- `Modules/` : fonctionnalites metier decoupees par domaine
- `Modules/RIS/` : radiologie, templates, examens, Orthanc, IA
- `resources/views/` : layouts et vues globales
- `routes/` : routes communes
- `public/` : entree web et fichiers publics

## Module RIS
Le module RIS couvre notamment :
- la liste des examens
- la fiche examen
- les templates de compte-rendu
- la generation PDF / partage
- la synchronisation PACS / Orthanc
- les actions de validation, annulation et cloture

### Remarques importantes
- Les modeles Word sont importes en `.docx`.
- Certaines fonctions d'import/export Word utilisent des bibliotheques chargees cote navigateur.
- Si le contenu apparait vide, verifier que le navigateur autorise les scripts distants ou remplacer ces dependances par des fichiers locaux.

## Conseils de production
- Configurer correctement la queue Laravel.
- Configurer un cron pour `schedule:run`.
- Verifier la connexion a Orthanc avant la mise en production.
- Tester les droits utilisateurs par role avant ouverture aux equipes.

## License
Ce projet suit la licence MIT par defaut du squelette Laravel, sauf indication contraire dans le depot.
