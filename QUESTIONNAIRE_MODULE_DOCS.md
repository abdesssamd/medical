# Gestion des Questionnaires - Documentation Complète

## Vue d'ensemble

Le module de gestion des questionnaires permet au **Super Admin** de créer, configurer et gérer des modèles de questionnaires pour les patients. Les praticiens peuvent ensuite lancer ces questionnaires lors des consultations pour collecter des données structurées et identifier les risques.

## 🎯 Fonctionnalités Principales

### 1. Interface d'Administration (Super Admin uniquement)

#### **Accès à la Gestion des Questionnaires**
- Route : `/clinical/questionnaire-templates`
- Bouton dans la sidebar : "Configuration Clinique" (remplace "Paramètres")
- Accessible uniquement aux utilisateurs avec le rôle `super_admin`

#### **Liste des Modèles**
La page d'index affiche :
- 📋 Tous les modèles de questionnaires créés
- 📊 Nombre de champs par modèle
- 📈 Nombre de réponses (non supprimable si des réponses existent)
- ✓/✕ Statut actif/inactif
- Actions : Voir, Éditer, Dupliquer, Exporter, Supprimer

### 2. Création de Questionnaires

**Route** : `/clinical/questionnaire-templates/create`

**Étapes de création** :

1. **Informations Générales**
   - Nom du modèle *
   - Description
   - Spécialité (optionnel - vide = tous les patients)
   - Praticien responsable (optionnel)
   - Catégorie (optionnel - pour grouper les questionnaires)

2. **Form Builder** - Types de Champs Disponibles
   - **Texte court** : Champs texte simple
   - **Texte long** : Textarea pour texte multi-lignes
   - **Email** : Validation format email
   - **Nombre** : Champs numériques
   - **Date** : Sélecteur de date
   - **Liste déroulante** : Sélection unique parmi des options
   - **Choix unique** (Radio) : Boutons radio
   - **Choix multiples** (Checkbox) : Cases à cocher
   - **Oui / Non** : Boutons Yes/No spécifiques

3. **Propriétés de Champ**
   Chaque champ peut avoir :
   - Label (étiquette) *
   - Placeholder (texte d'aide)
   - Texte d'aide supplémentaire
   - Options (pour liste/radio/checkbox)
   - Obligatoire (case à cocher)

4. **Réorganisation par Glisser-Déposer**
   - Utilisez les poignées (☰) pour réorganiser les champs
   - Drag & drop en temps réel avec Sortable.js

5. **Paramètres**
   - Cocher "Activer immédiatement" pour rendre le modèle disponible

### 3. Édition et Duplication

**Routes** :
- Édition : `/clinical/questionnaire-templates/{id}/edit`
- Duplication : `/clinical/questionnaire-templates/{id}/duplicate` (POST)

- Modifiez tous les éléments du questionnaire
- Dupliquez un modèle pour créer rapidement de nouveaux questionnaires similaires

### 4. Import/Export

**Format** : JSON

**Exporter** :
- Bouton "Exporter" sur la page d'index ou détail
- Crée un fichier `questionnaire_{id}.json` avec le schéma complet

**Importer** :
- Bouton "Importer" sur la page d'index
- Accepte les fichiers `.json` (max 1MB)
- Les modèles importés reçoivent automatiquement "(Importé)" dans le nom

### 5. Base de Données

**Table** : `questionnaires`

```sql
- id: INT (Primary Key)
- specialty_id: INT (nullable, Foreign Key)
- practitioner_id: INT (nullable, Foreign Key)
- group_name: VARCHAR(255) (nullable)
- created_by: INT (Foreign Key, User)
- name: VARCHAR(255) (unique)
- description: TEXT (nullable)
- field_schema: JSON
- is_active: BOOLEAN (default: true)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Schéma des Champs** (field_schema) :

```json
[
  {
    "id": "field_0",
    "label": "Avez-vous des allergies ?",
    "type": "yesno",
    "placeholder": "",
    "required": true,
    "options": [],
    "helpText": "Important pour la sécurité du patient",
    "order": 0
  },
  {
    "id": "field_1",
    "label": "Décrivez vos allergies",
    "type": "textarea",
    "placeholder": "Ex: Pénicilline, Latex",
    "required": false,
    "options": [],
    "helpText": "",
    "order": 1
  }
]
```

### 6. Remplissage des Questionnaires

**Route** : `/clinical/patients/{patientId}/questionnaires/{questionnaireId}`

**Fonctionnalités** :
- Formulaire responsive et moderne
- Barre de progression en temps réel
- Contexte patient affiché en haut
- Validation des champs obligatoires
- Types de champs dynamiques selon la configuration

### 7. Gestion des Réponses

**Table** : `patient_questionnaire_responses`

```sql
- id: INT (Primary Key)
- patient_id: INT (Foreign Key)
- questionnaire_id: INT (Foreign Key)
- answers: JSON
- filled_on: DATE
- answered_by: INT (Foreign Key, User)
- validated_by: INT (nullable, Foreign Key)
- validated_at: TIMESTAMP (nullable)
- has_critical_risk: BOOLEAN
- critical_notes: TEXT (nullable)
- risk_tags: JSON (nullable)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Analyse des Risques** :
- Détecte automatiquement les réponses critiques
- Tag les réponses pour le suivi
- Alertes si risques détectés

### 8. Contrôle d'Accès

```
Super Admin  ✓ Accès complet (CRUD + Admin)
Praticien    ✓ Peut remplir et valider
Secrétaire   ✓ Peut remplir
Assistant    - Lecture seule
Autres       ✗ Aucun accès
```

**Middleware** : 
- Tous les contrôleurs utilisent une vérification `auth()->user()->hasRole('super_admin')`
- Erreur 403 si non autorisé

## 📁 Structure des Fichiers

### Contrôleurs

```
Modules/ClinicalRecord/Http/Controllers/
├── QuestionnaireTemplateController.php    # Gestion des modèles (Super Admin)
└── QuestionnaireResponseController.php    # Gestion des réponses (Praticiens)
```

### Vues

```
resources/views/modules/questionnaire-templates/
├── index.blade.php      # Liste des modèles
├── create.blade.php     # Créer un modèle (Form Builder)
├── edit.blade.php       # Éditer un modèle
└── show.blade.php       # Détails d'un modèle

resources/views/modules/questionnaire-response/
└── form.blade.php       # Formulaire de réponse pour le patient
```

### Routes

```
Modules/ClinicalRecord/Routes/web.php
```

**Préfixes** :
- Admin : `/clinical/questionnaire-templates/*`
- Réponses : `/clinical/patients/{patientId}/questionnaires/*`

### Modèles Eloquent

```
Modules/ClinicalRecord/Models/
├── Questionnaire.php                    # Modèle de questionnaire
├── PatientQuestionnaireResponse.php      # Réponse du patient
└── HealthQuestionnaire.php              # Questionnaire de santé global
```

## 🔧 Configuration

### Ajout de la Barre Latérale

Modifié dans `resources/views/layouts/admin.blade.php` :

```blade
@if ($isAdmin) {
    $menu = [
        // ...autres éléments...
        ['label' => 'Configuration Clinique', 
         'icon' => 'ti ti-settings-automation', 
         'route' => 'questionnaire-templates.index', 
         'active' => 'questionnaire-templates.*'],
    ];
}
```

## 🚀 Utilisation

### Pour l'Administrateur

1. **Créer un modèle**
   - Accédez à "Configuration Clinique" dans la sidebar
   - Cliquez sur "Créer un Modèle"
   - Remplissez les informations
   - Ajoutez les champs avec le Form Builder
   - Enregistrez

2. **Gérer les modèles**
   - Voir : Affiche les détails et réponses récentes
   - Éditer : Modifiez le modèle et ses champs
   - Dupliquer : Créez une copie pour modification rapide
   - Exporter : Téléchargez en JSON
   - Supprimer : Uniquement si aucune réponse

3. **Importer un modèle**
   - Cliquez sur le bouton "Importer"
   - Sélectionnez un fichier `.json`
   - Le modèle est créé automatiquement

### Pour le Praticien

1. **Lancer un questionnaire**
   - Ouvrez le dossier d'un patient
   - Cliquez sur "Lancer Questionnaire" dans la sidebar
   - Sélectionnez le modèle souhaité

2. **Remplir le formulaire**
   - Répondez à toutes les questions obligatoires (*)
   - La barre de progression indique l'avancement
   - Validez vos réponses

3. **Consulter l'historique**
   - Voyez les questionnaires précédemment remplis
   - Vérifiez les risques identifiés

## 📊 Points Clés de Design

### Cohérence Visuelle
- Boutons arrondis avec dégradés (12px border-radius)
- Ombres légères et cohérentes
- Palette de couleurs existante du projet
- Animations fluides (0.2s ease)

### Accessibilité
- Labels explicites pour tous les champs
- Placeholder and help text
- Validation côté client et serveur
- Messages d'erreur clairs

### Performance
- Lazy loading des questionnaires
- Pagination (15 par page)
- Schéma JSON optimisé
- Requêtes avec relations préchargées

## 🔐 Sécurité

- ✓ Autorisation par rôle (middleware)
- ✓ Validation CSRF sur tous les formulaires
- ✓ Validation des données côté serveur
- ✓ Utilisateur authentifié dans tous les contrôleurs
- ✓ Permissions granulaires par rôle

## 📋 Modularité et Extensibilité

Le code est conçu pour permettre l'ajout facile de nouvelles fonctionnalités :

### Ajouter un nouveau type de champ

1. Mettez à jour `getAvailableFieldTypes()` dans `QuestionnaireTemplateController` :

```php
'custom_type' => [
    'label' => 'Type Custom',
    'icon' => 'ti-custom-icon',
    'placeholder' => 'Placeholder'
]
```

2. Ajoutez la validation dans `store()` et `update()` :

```php
'fields.*.type' => 'required|string|in:text,textarea,...,custom_type'
```

3. Ajoutez le rendu dans les vues `create.blade.php` et `edit.blade.php`

4. Gérez le type dans le formulaire de réponse (`form.blade.php`)

### Ajouter une nouvelle permission

1. Créer une entrée dans la table `permissions`
2. L'assigner aux rôles appropriés
3. Utiliser `auth()->user()->hasPermission('code')` dans les contrôleurs

## 🐛 Dépannage

### Questionnaire n'apparaît pas
- ✓ Vérifier que `is_active = true`
- ✓ Vérifier la spécialité (null = tous)
- ✓ Vérifier que l'utilisateur est super_admin

### Les champs ne s'enregistrent pas
- ✓ Vérifier la validation du formulaire
- ✓ Vérifier les erreurs de console JavaScript
- ✓ Vérifier le schéma JSON généré

### Import échoue
- ✓ Vérifier le format JSON
- ✓ Vérifier la taille (max 1MB)
- ✓ Vérifier l'extension (.json)

## 📝 Notes

- Les questionnaires supprimés conservent leurs réponses historiques (soft delete recommandé)
- L'analyse des risques peut être étendue avec des règles métier spécifiques
- Les notifications peuvent être intégrées avec le système d'alertes existant
- Les exports PDF peuvent être implémentés avec dompdf/spatie-pdf

## 🎓 Ressources

- **Laravel Docs** : https://laravel.com/docs
- **Tabler Icons** : https://tabler-icons.io
- **Sortable.js** : https://sortablejs.github.io/Sortable/

---

**Version** : 1.0.0  
**Date** : Mai 2026  
**Auteur** : GitHub Copilot
