# React Native Expo - Exemples

## 1) Vue calendrier (sélection date + slots)
```tsx
import React, { useEffect, useState } from 'react';
import { View, Text, Pressable, FlatList } from 'react-native';
import { Calendar } from 'react-native-calendars';

const API = 'http://127.0.0.1:8000/api/appointment';

export default function BookingScreen() {
  const [date, setDate] = useState<string>(new Date().toISOString().slice(0, 10));
  const [slots, setSlots] = useState<string[]>([]);
  const professionalId = 1;

  useEffect(() => {
    fetch(`${API}/availability?professional_id=${professionalId}&date=${date}`, {
      headers: { Accept: 'application/json' },
      credentials: 'include',
    })
      .then((r) => r.json())
      .then((data) => setSlots(data.slots ?? []));
  }, [date]);

  return (
    <View style={{ flex: 1, padding: 16 }}>
      <Calendar
        onDayPress={(d) => setDate(d.dateString)}
        markedDates={{ [date]: { selected: true } }}
      />
      <Text style={{ marginTop: 16, fontSize: 18, fontWeight: '600' }}>Créneaux disponibles</Text>
      <FlatList
        data={slots}
        keyExtractor={(item) => item}
        renderItem={({ item }) => (
          <Pressable style={{ padding: 12, marginTop: 8, backgroundColor: '#e9f5ff', borderRadius: 10 }}>
            <Text>{item.slice(0, 5)}</Text>
          </Pressable>
        )}
      />
    </View>
  );
}
```

## 2) Dashboard Secrétaire
```tsx
import React, { useEffect, useState } from 'react';
import { View, Text } from 'react-native';

const API = 'http://127.0.0.1:8000/api/appointment';

export default function SecretaryDashboard() {
  const [data, setData] = useState<any>(null);
  const professionalId = 1;

  useEffect(() => {
    fetch(`${API}/dashboard/secretary?professional_id=${professionalId}`, {
      headers: { Accept: 'application/json' },
      credentials: 'include',
    })
      .then((r) => r.json())
      .then(setData);
  }, []);

  if (!data) return <Text>Chargement...</Text>;

  return (
    <View style={{ flex: 1, padding: 16 }}>
      <Text style={{ fontSize: 22, fontWeight: '700' }}>Dashboard Secrétaire</Text>
      <Text style={{ marginTop: 12 }}>Patients du jour: {data.appointments_today}</Text>
      <Text>Consultés: {data.consulted_today}</Text>
      <Text>Commissions cumulées (mois): {data.commissions_month_total}</Text>
    </View>
  );
}
```
