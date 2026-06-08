<?php

namespace Modules\ClinicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\ClinicalRecord\Models\Questionnaire;

class QuestionnaireTemplateController extends Controller
{
    /**
     * Vérifier que l'utilisateur est Super Admin
     */
    public function ensureAuthorized(): void
    {
        if (!auth()->check() || !auth()->user()->hasRole('super_admin')) {
            throw new AuthorizationException('Unauthorized');
        }
    }

    /**
     * Constructor pour les middewares
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->ensureAuthorized();
            return $next($request);
        });
    }

    /**
     * Afficher la liste des modèles de questionnaires
     */
    public function index(): View
    {
        $questionnaires = Questionnaire::with(['specialty', 'creator', 'practitioner'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $specialties = Specialty::orderBy('name')->get();

        return view('modules.questionnaire-templates.index', compact('questionnaires', 'specialties'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create(): View
    {
        $specialties = Specialty::orderBy('name')->get();
        $practitioners = User::whereHas('roles', fn ($q) => $q->whereIn('code', ['professional', 'doctor', 'medecin']))
            ->orderBy('name')
            ->get();

        $fieldTypes = $this->getAvailableFieldTypes();

        return view('modules.questionnaire-templates.create', compact('specialties', 'practitioners', 'fieldTypes'));
    }

    /**
     * Stocker un nouveau modèle de questionnaire
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:questionnaires,name',
            'description' => 'nullable|string|max:1000',
            'specialty_id' => 'nullable|exists:specialties,id',
            'practitioner_id' => 'nullable|exists:users,id',
            'group_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:text,textarea,select,radio,checkbox,yesno,date,email,number',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.helpText' => 'nullable|string|max:500',
        ]);

        $fieldSchema = $this->buildFieldSchema($validated['fields']);

        $questionnaire = Questionnaire::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'practitioner_id' => $validated['practitioner_id'] ?? null,
            'group_name' => $validated['group_name'] ?? null,
            'field_schema' => $fieldSchema,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('clinical.questionnaire-templates.show', $questionnaire->id)
            ->with('success', "Modèle de questionnaire « {$questionnaire->name} » créé avec succès.");
    }

    /**
     * Afficher un modèle de questionnaire
     */
    public function show(int $id): View
    {
        $questionnaire = Questionnaire::with(['specialty', 'creator', 'practitioner', 'responses'])
            ->findOrFail($id);

        $responseCount = $questionnaire->responses()->count();
        $recentResponses = $questionnaire->responses()
            ->with(['patient'])
            ->latest()
            ->limit(10)
            ->get();

        return view('modules.questionnaire-templates.show', compact('questionnaire', 'responseCount', 'recentResponses'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(int $id): View
    {
        $questionnaire = Questionnaire::findOrFail($id);

        $specialties = Specialty::orderBy('name')->get();
        $practitioners = User::whereHas('roles', fn ($q) => $q->whereIn('code', ['professional', 'doctor', 'medecin']))
            ->orderBy('name')
            ->get();

        $fieldTypes = $this->getAvailableFieldTypes();
        $formattedFields = $this->formatFieldsForEdit($questionnaire->field_schema);

        return view('modules.questionnaire-templates.edit', compact(
            'questionnaire',
            'specialties',
            'practitioners',
            'fieldTypes',
            'formattedFields'
        ));
    }

    /**
     * Mettre à jour un modèle de questionnaire
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $questionnaire = Questionnaire::findOrFail($id);

        $validated = $request->validate([
            'name' => "required|string|max:255|unique:questionnaires,name,{$id}",
            'description' => 'nullable|string|max:1000',
            'specialty_id' => 'nullable|exists:specialties,id',
            'practitioner_id' => 'nullable|exists:users,id',
            'group_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:text,textarea,select,radio,checkbox,yesno,date,email,number',
            'fields.*.placeholder' => 'nullable|string|max:255',
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.helpText' => 'nullable|string|max:500',
        ]);

        $fieldSchema = $this->buildFieldSchema($validated['fields']);

        $questionnaire->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'practitioner_id' => $validated['practitioner_id'] ?? null,
            'group_name' => $validated['group_name'] ?? null,
            'field_schema' => $fieldSchema,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('clinical.questionnaire-templates.show', $questionnaire->id)
            ->with('success', "Modèle de questionnaire « {$questionnaire->name} » mis à jour avec succès.");
    }

    /**
     * Supprimer un modèle de questionnaire
     */
    public function destroy(int $id): RedirectResponse
    {
        $questionnaire = Questionnaire::findOrFail($id);
        $name = $questionnaire->name;

        // Vérifier s'il y a des réponses liées
        if ($questionnaire->responses()->exists()) {
            return redirect()
                ->route('clinical.questionnaire-templates.index')
                ->with('error', "Impossible de supprimer le modèle « {$name} » car il a déjà des réponses associées.");
        }

        $questionnaire->delete();

        return redirect()
            ->route('clinical.questionnaire-templates.index')
            ->with('success', "Modèle de questionnaire « {$name} » supprimé avec succès.");
    }

    /**
     * Rediriger les anciens liens GET vers la fiche sans exécuter l'action.
     */
    public function duplicateRedirect(int $id): RedirectResponse
    {
        return redirect()
            ->route('clinical.questionnaire-templates.show', $id)
            ->with('error', 'Utilisez le bouton Dupliquer pour confirmer cette action.');
    }

    /**
     * Dupliquer un modèle de questionnaire
     */
    public function duplicate(int $id): RedirectResponse
    {
        $original = Questionnaire::findOrFail($id);

        $duplicate = $original->replicate();
        $duplicate->name = $original->name . ' (Copie)';
        $duplicate->created_by = auth()->id();
        $duplicate->save();

        return redirect()
            ->route('clinical.questionnaire-templates.show', $duplicate->id)
            ->with('success', "Modèle de questionnaire dupliqué avec succès.");
    }

    /**
     * Activer/désactiver un modèle
     */
    public function toggleActive(int $id): JsonResponse
    {
        $questionnaire = Questionnaire::findOrFail($id);
        $questionnaire->is_active = !$questionnaire->is_active;
        $questionnaire->save();

        return response()->json([
            'success' => true,
            'is_active' => $questionnaire->is_active,
            'message' => $questionnaire->is_active ? 'Modèle activé' : 'Modèle désactivé',
        ]);
    }

    /**
     * Obtenir les types de champs disponibles
     */
    private function getAvailableFieldTypes(): array
    {
        return [
            'text' => ['label' => 'Texte court', 'icon' => 'ti-text', 'placeholder' => 'Ex: Nom complet'],
            'textarea' => ['label' => 'Texte long', 'icon' => 'ti-text-wrap', 'placeholder' => 'Ex: Notes importantes'],
            'email' => ['label' => 'Email', 'icon' => 'ti-mail', 'placeholder' => 'Ex: email@example.com'],
            'number' => ['label' => 'Nombre', 'icon' => 'ti-numbers', 'placeholder' => 'Ex: 42'],
            'date' => ['label' => 'Date', 'icon' => 'ti-calendar', 'placeholder' => 'JJ/MM/AAAA'],
            'select' => ['label' => 'Liste déroulante', 'icon' => 'ti-list', 'placeholder' => 'Sélectionnez...'],
            'radio' => ['label' => 'Choix unique', 'icon' => 'ti-circle-dot', 'placeholder' => ''],
            'checkbox' => ['label' => 'Choix multiples', 'icon' => 'ti-checkbox', 'placeholder' => ''],
            'yesno' => ['label' => 'Oui / Non', 'icon' => 'ti-help', 'placeholder' => ''],
        ];
    }

    /**
     * Construire le schéma des champs depuis la requête
     */
    private function buildFieldSchema(array $fields): array
    {
        return collect($fields)
            ->map(function (array $field, int $index) {
                return [
                    'id' => "field_{$index}",
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'placeholder' => $field['placeholder'] ?? '',
                    'required' => $field['required'] ?? false,
                    'options' => match ($field['type']) {
                        'select', 'radio', 'checkbox' => array_filter($field['options'] ?? []),
                        default => [],
                    },
                    'helpText' => $field['helpText'] ?? '',
                    'order' => $index,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Formater les champs pour l'édition
     */
    private function formatFieldsForEdit(array $fieldSchema): array
    {
        return collect($fieldSchema)
            ->map(function (array $field) {
                return [
                    'id' => $field['id'] ?? null,
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'placeholder' => $field['placeholder'] ?? '',
                    'required' => $field['required'] ?? false,
                    'options' => $field['options'] ?? [],
                    'helpText' => $field['helpText'] ?? '',
                ];
            })
            ->all();
    }

    /**
     * Exporter un modèle au format JSON
     */
    public function export(int $id): JsonResponse
    {
        $questionnaire = Questionnaire::findOrFail($id);

        return response()->json($questionnaire->only([
            'id',
            'name',
            'description',
            'group_name',
            'field_schema',
        ]), 200, [
            'Content-Disposition' => "attachment; filename=\"questionnaire_{$questionnaire->id}.json\"",
        ]);
    }

    /**
     * Importer un modèle depuis JSON
     */
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:json|max:1024',
        ]);

        try {
            $content = json_decode(file_get_contents($validated['file']->getRealPath()), true);

            if (!isset($content['name']) || !isset($content['field_schema'])) {
                throw new \Exception('Format JSON invalide.');
            }

            $name = $content['name'] . ' (Importé)';
            if (Questionnaire::where('name', $name)->exists()) {
                $name .= ' - ' . now()->timestamp;
            }

            Questionnaire::create([
                'name' => $name,
                'description' => $content['description'] ?? null,
                'group_name' => $content['group_name'] ?? null,
                'field_schema' => $content['field_schema'],
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            return redirect()
                ->route('clinical.questionnaire-templates.index')
                ->with('success', 'Modèle de questionnaire importé avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', "Erreur lors de l'importation : " . $e->getMessage());
        }
    }
}
