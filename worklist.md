# Worklist Orthanc

## Objectif

Mettre en place Orthanc sur Windows pour servir une Worklist DICOM exploitable par les modalités Scanner et Échographe depuis MediOffice.

## Pré-requis

- Orthanc installé sur le serveur Windows.
- Dossier racine Orthanc: `D:\Orthanc Server`.
- Service Orthanc démarré en mode automatique.
- Un dossier dédié aux Worklists: `D:\Orthanc Server\Worklists`.
- DCMTK installé si vous souhaitez générer un vrai fichier `.wl` natif.

## Configuration Orthanc

Conserver une seule section `Worklists`.

Si `Worklists` est déjà déclarée dans `orthanc.json`, alors `worklists.json` doit être désactivé ou renommé en `.bak`.

Configuration attendue:

```json
"Worklists": {
  "Enable": true,
  "Database": "D:/Orthanc Server/Worklists",
  "FilterIssuerAet": false,
  "LimitAnswers": 0
}
```

## Dossier Worklists

Créer le dossier suivant:

```text
D:\Orthanc Server\Worklists
```

Vérifier ensuite les droits en lecture et écriture pour le service Orthanc.

## Modalités DICOM

Si `DicomAlwaysAllowFindWorklist` vaut `false`, Orthanc doit connaître explicitement chaque modalité autorisée.

Exemple:

```json
"DicomAlwaysAllowFindWorklist": false,
"DicomModalities": {
  "SCANNER_CT": [ "SCANNER_CT", "192.168.1.50", 104 ],
  "ECHO_US": [ "ECHO_US", "192.168.1.51", 104 ]
}
```

Format de chaque entrée:

1. AET de la modalité.
2. Adresse IP de la modalité.
3. Port DICOM de la modalité.

## Installation DCMTK

Installer DCMTK dans un dossier dédié, par exemple:

```text
D:\dcmtk 3.7
```

Binaire à vérifier:

```text
D:\dcmtk 3.7\bin\dump2dcm.exe
```

Si `dump2dcm.exe` est disponible, MediOffice peut générer un fichier `.wl` DICOM natif en plus du fichier de secours `.wl.json`.

## Variables MediOffice

Configurer le fichier `.env`:

```dotenv
ORTHANC_BASE_URL=http://127.0.0.1:8042
ORTHANC_USERNAME=orthanc
ORTHANC_PASSWORD=orthanc
ORTHANC_WORKLIST_PATH=/worklists
ORTHANC_WORKLIST_DIRECTORY="D:/Orthanc Server/Worklists"
ORTHANC_DUMP2DCM_PATH="D:/dcmtk 3.7/bin/dump2dcm.exe"
DICOM_UID_ROOT=1.2.826.0.1.3680043.10.5432
ORTHANC_WEBHOOK_TOKEN=<secret>
```

## Mise à jour Laravel

Après modification du `.env`:

```bash
php artisan config:clear
php artisan config:cache
```

## Démarrage et contrôle

Redémarrer Orthanc:

```powershell
Start-Service Orthanc
```

Test de base:

```text
http://127.0.0.1:8042/system
```

Le service doit répondre en `200`.

## Vérification fonctionnelle

1. Dans MediOffice, créer une nouvelle demande via `+ Demande Imagerie`.
2. Vérifier qu’un fichier de worklist est généré dans `D:\Orthanc Server\Worklists`.
3. Depuis la modalité, lancer une requête MWL vers Orthanc.
4. Vérifier que le patient et la demande remontent avec les bons champs:
   - `AccessionNumber`
   - `PatientID`
   - `PatientName`
   - `StudyInstanceUID`
5. Vérifier le passage des statuts:
   - `requested`
   - `in_progress`
   - `received`
   - `completed`

## Webhook Orthanc vers MediOffice

Endpoint:

```text
POST /care/module-3/orthanc/webhook
```

Header requis:

```text
X-Orthanc-Token: <secret>
```

Événements pris en charge:

- `study-started` -> `in_progress`
- `study-received` -> `received`
- `study-completed` -> `completed`

## Bonnes pratiques

- Garder une seule source de vérité pour `Worklists`.
- Utiliser un `AccessionNumber` unique généré par MediOffice.
- Préférer les chemins Windows avec `/` dans les variables `.env` pour éviter les erreurs d’échappement.
- Conserver le dossier `D:\Orthanc Server\Worklists` dédié à la MWL.