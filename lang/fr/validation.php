<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit etre une chaine de caracteres.',
    'integer' => 'Le champ :attribute doit etre un entier.',
    'date' => 'Le champ :attribute doit etre une date valide.',
    'exists' => 'La valeur selectionnee pour :attribute est invalide.',
    'in' => 'La valeur selectionnee pour :attribute est invalide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas depasser :max caracteres.',
    ],

    'attributes' => [
        'report_text' => 'rapport',
        'patient_id' => 'patient',
        'procedure_id' => 'examen',
        'modality_id' => 'modalite',
        'priority' => 'priorite',
        'signing_physician_id' => 'medecin signataire',
        'validated_at' => 'date de validation',
        'severity_tag' => 'niveau de gravite',
        'signature_name' => 'signature',
        'title' => 'titre',
        'content' => 'contenu',
    ],
];
