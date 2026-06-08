# Module Appointment (RDV)

## Objectif
Module de gestion des rendez-vous pour:
- `professional`: configure planning, paramètres, commissions.
- `secretary`: crée/modifie/annule des RDV et marque `consulted`.

## Tables (MySQL)
- `users` (existante, `role` utilisé: `professional`, `secretary`)
- `plannings`
- `appointments`
- `commissions`
- `appointment_settings` (settings du module RDV)

## Règles métiers clés
- Le planning définit: jours ouvrés, plage horaire, durée consultation.
- Le quota journalier (`max_patients_per_day`) bloque les nouvelles prises.
- Les slots sont générés dynamiquement selon planning - slots déjà pris.
- Quand un RDV passe à `consulted`, une commission `pending` est créée/maj.
- Intégration Queue directe: si `appointment_settings.queue_service_id` est configuré,
  le passage en `consulted` crée automatiquement un ticket Queue et remplit `appointments.queue_ticket_id`.

## Endpoints API (REST)
- `GET /api/appointment/availability?professional_id=&date=YYYY-MM-DD`
- `GET /api/appointment/appointments?professional_id=&date=&status=`
- `POST /api/appointment/appointments`
- `PATCH /api/appointment/appointments/{appointment}/status`
- `GET /api/appointment/professionals/{professional}/plannings`
- `PUT /api/appointment/professionals/{professional}/plannings`
- `PUT /api/appointment/professionals/{professional}/settings`
- `GET /api/appointment/dashboard/secretary?professional_id=&date=`
- `GET /api/appointment/commissions/summary?from=&to=`
- `PATCH /api/appointment/commissions/{commission}/mark-paid`

## Web Laravel
- `GET /appointment/pro/dashboard`
- `GET /appointment/sec/dashboard?professional_id=...`

## React Native (Expo) exemples
Voir: `Modules/Appointment/Docs/react-native-examples.md`
