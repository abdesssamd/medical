# Module RIS

Structure proposee pour separer le RIS du reste de l'application:

- `Modules/RIS/Http/Controllers` : Controleurs du workflow RIS.
- `Modules/RIS/Services` : Integration Orthanc, logique metier RIS.
- `Modules/RIS/Models` : Modeles Eloquent des tables RIS.
- `Modules/RIS/Events` : Evenements metier RIS.
- `Modules/RIS/Listeners` : Synchronisations inter-modules (dossier dentaire, etc.).
- `Modules/RIS/Routes` : Routes web/api dediees RIS.
- `Modules/RIS/Database/Migrations` : Migrations du schema RIS.
- `Modules/RIS/Resources/views` : Vues Blade RIS.
- `Modules/RIS/Providers` : Bootstrap du module RIS.

## Variables .env recommandees

```env
RIS_ENABLED=true

RIS_ORTHANC_BASE_URL=http://127.0.0.1:8042
RIS_ORTHANC_USERNAME=orthanc
RIS_ORTHANC_PASSWORD=orthanc
RIS_ORTHANC_TIMEOUT=8
RIS_ORTHANC_WORKLIST_PATH=/worklists
RIS_ORTHANC_WEBHOOK_TOKEN=change-me

# Viewer Orthanc ou OHIF expose en frontal
RIS_ORTHANC_VIEWER_BASE_URL=http://127.0.0.1:8042
```

## Endpoints principaux

- `GET /ris/examens` : liste des examens RIS.
- `PATCH /ris/examens/{order}/terminer` : cloture examen et synchronise vers dossier clinique.
- `POST /ris/orthanc/webhook` : webhook Orthanc `OnStoredInstance` (sans CSRF).

## Webhook Orthanc (OnStoredInstance)

- Header securite attendu : `X-Orthanc-Token: <RIS_ORTHANC_WEBHOOK_TOKEN>`.
- Le webhook lit les tags DICOM via Orthanc REST (`/instances/{id}/simplified-tags`).
- Le dernier ordre actif du patient passe automatiquement en `images_recues`.

Exemple de payload minimal:

```json
{
	"OrthancID": "7f0f3b61-2d4f2f13-278f6e06-22f0a44a-9a5f9c1d"
}
```

## Notes MWL / Orthanc

- Verifier que le plugin Modality Worklist est actif sur Orthanc.
- Le service `Modules\\RIS\\Services\\OrthancService` envoie les tags MWL via API REST.
- Sur Windows, preferer les chemins avec `/` dans `.env` pour eviter les erreurs d'echappement.
