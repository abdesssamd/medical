# Guide d'Intégration & Déploiement - Module Questionnaire

## ✅ Checklist Pré-Déploiement

### Base de Données
- [ ] Les tables `questionnaires` et `patient_questionnaire_responses` existent
- [ ] Les migrations sont appliquées
- [ ] Les colonnes JSON sont supportées (MySQL 5.7.8+)

### Code Source
- [ ] Les contrôleurs sont dans `Modules/ClinicalRecord/Http/Controllers/`
- [ ] Les vues sont dans `resources/views/modules/questionnaire-templates/` et `questionnaire-response/`
- [ ] Les routes sont ajoutées à `Modules/ClinicalRecord/Routes/web.php`
- [ ] La méthode `hasRole()` est ajoutée au modèle User
- [ ] La sidebar est mise à jour avec "Configuration Clinique"

### Dépendances
- [ ] Sortable.js est chargé (CDN ou local)
- [ ] Tabler Icons sont disponibles
- [ ] Toutes les vues héritent correctement de `layouts.admin`

### Autoloading
```bash
composer dump-autoload
```

### Cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🚀 Installation Rapide

### 1. Ajouter les Fichiers Nouveaux

```bash
# Contrôleurs
cp QuestionnaireTemplateController.php app/Http/Controllers/
cp QuestionnaireResponseController.php app/Http/Controllers/

# Vues
mkdir -p resources/views/modules/questionnaire-templates
mkdir -p resources/views/modules/questionnaire-response
cp index.blade.php resources/views/modules/questionnaire-templates/
cp create.blade.php resources/views/modules/questionnaire-templates/
# ... autres fichiers de vue
```

### 2. Mettre à Jour les Fichiers Existants

```bash
# Routes
# Ajouter les nouvelles routes à Modules/ClinicalRecord/Routes/web.php

# Sidebar
# Modifier resources/views/layouts/admin.blade.php
# Changer "Paramètres" par "Configuration Clinique"

# User Model
# Ajouter la méthode hasRole() à app/Models/User.php
```

### 3. Migrations (si nécessaire)

```php
// database/migrations/YYYY_MM_DD_create_questionnaire_tables.php

Schema::create('questionnaires', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('specialty_id')->nullable();
    $table->unsignedBigInteger('practitioner_id')->nullable();
    $table->string('group_name')->nullable();
    $table->unsignedBigInteger('created_by');
    $table->string('name')->unique();
    $table->text('description')->nullable();
    $table->json('field_schema');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('set null');
    $table->foreign('practitioner_id')->references('id')->on('users')->onDelete('set null');
    $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
});

Schema::create('patient_questionnaire_responses', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('patient_id');
    $table->unsignedBigInteger('questionnaire_id');
    $table->json('answers');
    $table->date('filled_on')->nullable();
    $table->unsignedBigInteger('answered_by');
    $table->unsignedBigInteger('validated_by')->nullable();
    $table->timestamp('validated_at')->nullable();
    $table->boolean('has_critical_risk')->default(false);
    $table->text('critical_notes')->nullable();
    $table->json('risk_tags')->nullable();
    $table->timestamps();
    
    $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
    $table->foreign('questionnaire_id')->references('id')->on('questionnaires')->onDelete('cascade');
    $table->foreign('answered_by')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
    $table->unique(['patient_id', 'questionnaire_id']);
});

// Puis exécuter
php artisan migrate
```

## 🧪 Tests Fonctionnels

### Test 1: Accès Super Admin

```bash
# En tant qu'utilisateur Super Admin:
GET /clinical/questionnaire-templates
# Attendu: 200 OK - liste des questionnaires
```

### Test 2: Créer un Questionnaire

```bash
# POST avec données valides
POST /clinical/questionnaire-templates
{
    "name": "Test Questionnaire",
    "description": "Test Description",
    "specialty_id": null,
    "fields": [
        {
            "label": "Test Field",
            "type": "text",
            "placeholder": "Test",
            "required": true,
            "options": [],
            "helpText": "Test help"
        }
    ]
}
# Attendu: 302 redirect to show page + success message
```

### Test 3: Accès Non Super Admin

```bash
# En tant qu'utilisateur praticien/autre:
GET /clinical/questionnaire-templates
# Attendu: 403 Unauthorized
```

### Test 4: Remplir un Questionnaire

```bash
# POST les réponses
POST /clinical/patients/1/questionnaires/1
{
    "answers": {
        "field_0": "Valeur réponse",
        "field_1": "oui"
    }
}
# Attendu: 302 redirect + success message
```

## 🐛 Dépannage Courant

### Erreur: "Class not found QuestionnaireTemplateController"

**Solution**:
```bash
composer dump-autoload
php artisan clear-cache
```

### Erreur: "Undefined route 'questionnaire-templates.index'"

**Solution**: Vérifier que les routes sont ajoutées à `Modules/ClinicalRecord/Routes/web.php`

### Erreur: "Method hasRole() not found on User"

**Solution**: Ajouter la méthode au modèle User:
```php
public function hasRole(string $role): bool
{
    return $this->hasAnyRole([$role]);
}
```

### Formulaire ne valide pas

**Solution**: Vérifier la console du navigateur pour les erreurs JavaScript
- Vérifier que Sortable.js est chargé
- Vérifier les erreurs Laravel dans les logs

### Questionnaires n'apparaissent pas

**Vérifier**:
1. `is_active = true` dans la base de données
2. Spécialité compatible avec le patient
3. User a le rôle `super_admin`

## 📊 Requêtes SQL Utiles

### Voir tous les questionnaires

```sql
SELECT * FROM questionnaires 
WHERE is_active = true 
ORDER BY created_at DESC;
```

### Voir les réponses avec risques critiques

```sql
SELECT pr.*, p.full_name, q.name as questionnaire_name
FROM patient_questionnaire_responses pr
JOIN patients p ON pr.patient_id = p.id
JOIN questionnaires q ON pr.questionnaire_id = q.id
WHERE pr.has_critical_risk = true
ORDER BY pr.filled_on DESC;
```

### Compter les questionnaires par spécialité

```sql
SELECT s.name, COUNT(q.id) as count
FROM questionnaires q
LEFT JOIN specialties s ON q.specialty_id = s.id
GROUP BY q.specialty_id
ORDER BY count DESC;
```

## 🔧 Configuration Recommandée

### Permissions (Optionnel)

```php
// Ajouter à la table permissions
- questionnaire.create
- questionnaire.edit
- questionnaire.delete
- questionnaire.view
- questionnaire.respond

// Assigner au rôle Super Admin
```

### Notifications (Optionnel)

```php
// Dans QuestionnaireResponseController->analyzeRisks()
// Ajouter la notification:
Notification::send(
    $questionnaire->creator,
    new CriticalRiskDetectedNotification($patient, $questionnaire, $analysis)
);
```

### Audit (Optionnel)

```php
// Ajouter audit logging:
AuditLog::create([
    'user_id' => auth()->id(),
    'action' => 'questionnaire_created',
    'model' => 'Questionnaire',
    'model_id' => $questionnaire->id,
]);
```

## 📈 Métriques et Monitoring

### Requêtes à surveiller

```php
// Total de questionnaires actifs
Questionnaire::active()->count()

// Total de réponses cette semaine
PatientQuestionnaireResponse::where('filled_on', '>=', now()->subWeek())->count()

// Risques détectés cette semaine
PatientQuestionnaireResponse::where('has_critical_risk', true)
    ->where('filled_on', '>=', now()->subWeek())
    ->count()

// Taux de validation
PatientQuestionnaireResponse::whereNotNull('validated_by')->count() /
PatientQuestionnaireResponse::count() * 100
```

## 🔐 Sécurité Supplémentaire

### Rate Limiting

```php
// Dans routes/web.php
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    // Routes des questionnaires
});
```

### Logging

```php
// Dans contrôleur
Log::info("User {$user->id} created questionnaire", [
    'questionnaire_id' => $questionnaire->id,
    'fields_count' => count($questionnaire->field_schema)
]);
```

### CORS (si API)

```php
// config/cors.php
'allowed_methods' => ['*'],
'allowed_origins' => ['localhost'],
```

## 📚 Documentation pour l'Équipe

- Partager `QUESTIONNAIRE_MODULE_DOCS.md`
- Former les Super Admins à l'utilisation
- Former les praticiens au remplissage
- Documenter les workflows internes

## ✨ Enhancements Futurs

**Priority Haute**:
1. [ ] Conditional field display logic
2. [ ] Email notifications for critical risks
3. [ ] Template versioning
4. [ ] Advanced analytics dashboard

**Priority Moyenne**:
1. [ ] PDF export with DomPDF
2. [ ] Multi-language support
3. [ ] Mobile app integration
4. [ ] QR codes for mobile access

**Priority Basse**:
1. [ ] AI-powered risk assessment
2. [ ] Integration with lab systems
3. [ ] Voice input for accessibility
4. [ ] Template marketplace

## 📞 Support

Pour les problèmes, consulter:
1. Les logs Laravel: `storage/logs/laravel.log`
2. Les logs du navigateur (F12 - Console)
3. La documentation complète: `QUESTIONNAIRE_MODULE_DOCS.md`
4. Le code source du contrôleur pour la logique métier

---

**Version**: 1.0.0  
**Dernière mise à jour**: Mai 2026  
**Status**: ✅ Production Ready
