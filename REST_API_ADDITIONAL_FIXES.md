# REST API Additional Fixes - ECAB Taxi Booking Manager

## Problemi Risolti

### 1. **Custom Features che Mostrano Campi Non Custom**

**Problema:** I campi "Max people" e "Max luggage" apparivano nelle custom features anche se erano già gestiti separatamente nei campi `max_passenger` e `max_bag`.

**Causa:** La funzione `prepare_transport_service()` non filtrava correttamente i campi predefiniti dalle custom features.

**Soluzione:** Aggiunto filtro per escludere i campi predefiniti dalle custom features:

```php
case 'Max people':
case 'Max luggage':
case 'Maximum Passengers':
case 'Maximum Bags':
    // Skip these as they are handled separately in max_passenger and max_bag
    break;
```

### 2. **Logica del Parametro is_return**

**Problema:** Il parametro `is_return` veniva impostato a `true` anche quando non c'era un viaggio di ritorno effettivo.

**Causa:** La logica controllava solo se il campo esisteva, non se era effettivamente un viaggio di ritorno.

**Soluzione:** Implementata logica più robusta per determinare se è un viaggio di ritorno:

```php
// Check if this is actually a return trip (has return date and is not empty/false)
$is_actual_return = false;
if (!empty($is_return) && $is_return != '0' && $is_return != 'false' && $is_return !== false) {
    if (!empty($return_date)) {
        $is_actual_return = true;
    }
}
```

E aggiornata la risposta dell'API:

```php
'is_return' => $is_actual_return,
'return_date' => $is_actual_return ? $return_date : null,
'return_time' => $is_actual_return ? $return_time : null,
```

### 3. **Valori di Passengers e Bags Non Corrispondenti**

**Problema:** I valori di `passengers` e `bags` non corrispondevano a quelli selezionati nel frontend.

**Causa:** Utilizzo di campi metadata non corretti invece di quelli effettivamente utilizzati dal plugin.

**Soluzione:** Utilizzati i campi metadata corretti identificati dal codice del plugin:

```php
// Get passengers and bags information - use correct metadata fields from plugin
$passengers = get_post_meta($booking_id, 'mptbm_passengers', true);
if (empty($passengers)) {
    $passengers = 1; // Default value
}

$bags = get_post_meta($booking_id, 'mptbm_bags', true);
if (empty($bags)) {
    $bags = 0; // Default value
}
```

**Campi Metadata Identificati:**

- **Passengers**: `mptbm_passengers` (usato nel frontend e WooCommerce)
- **Bags**: `mptbm_bags` (usato nel frontend e WooCommerce)

### 4. **Order Notes Mancanti**

**Problema:** Le order notes non erano incluse nella risposta dell'API.

**Causa:** Le order notes non erano state implementate nella funzione `prepare_booking_data()`.

**Soluzione:** Aggiunta recupero delle order notes dall'ordine WooCommerce associato seguendo le best practice di WooCommerce:

```php
// Get order notes from WooCommerce order if available - using WooCommerce best practices
$order_notes = array();
$order_id = get_post_meta($booking_id, 'mptbm_order_id', true);
if (!empty($order_id) && class_exists('WooCommerce')) {
    $order = wc_get_order($order_id);
    if ($order) {
        // Get all order notes using WooCommerce best practices
        $notes = wc_get_order_notes(array(
            'order_id' => $order_id,
            'limit' => 50,
            'orderby' => 'date_created',
            'order' => 'DESC'
        ));

        foreach ($notes as $note) {
            $order_notes[] = array(
                'id' => $note->comment_ID,
                'date' => $note->comment_date,
                'author' => $note->comment_author,
                'content' => $note->comment_content,
                'customer_note' => $note->comment_type === 'customer',
                'added_by' => $note->comment_author,
                'date_created' => $note->comment_date
            );
        }
    }
}
```

**Best Practice WooCommerce:**

- Utilizzo della funzione `wc_get_order_notes()` per recuperare le note
- Parametri corretti per limit, orderby e order
- Gestione corretta dei tipi di note (customer vs private)

E aggiunta alla risposta dell'API:

```php
// Order notes
'order_notes' => $order_notes
```

## Nuovi Campi Aggiunti

### **Order Notes**

- `order_notes`: Array di note dell'ordine con la seguente struttura:
  - `id`: ID della nota
  - `date`: Data di creazione della nota
  - `author`: Autore della nota
  - `content`: Contenuto della nota
  - `customer_note`: Se è una nota del cliente (boolean)

## Miglioramenti Implementati

### **1. Filtro Custom Features Migliorato**

- Esclusi i campi predefiniti dalle custom features
- Gestione di varianti dei nomi dei campi (Max people, Maximum Passengers, etc.)
- Custom features ora contengono solo campi effettivamente personalizzati

### **2. Logica is_return Più Robusta**

- Controllo effettivo se è un viaggio di ritorno
- Validazione della presenza di una data di ritorno
- Gestione corretta dei valori null per viaggi non di ritorno

### **3. Recupero Dati Semplificato e Corretto**

- Utilizzo dei campi metadata corretti identificati dal codice del plugin
- Rimozione di ricerche non necessarie in campi inesistenti
- Codice più pulito e mantenibile

### **4. Order Notes Complete con Best Practice WooCommerce**

- Recupero di tutte le note dell'ordine WooCommerce usando `wc_get_order_notes()`
- Informazioni complete per ogni nota
- Distinzione tra note del sistema e note del cliente
- Ordinamento per data di creazione (più recenti prima)
- Conformità alle best practice di WooCommerce

## Esempio di Risposta API Migliorata

```json
{
  "id": 44,
  "status": "publish",
  "date_created": "2025-09-12 08:38:52",
  "customer_id": 123,
  "transport_id": "456",
  "pickup_location": "Via Scigliano, 19, Roma, Italy",
  "dropoff_location": "Stazione Termini, Piazza dei Cinquecento, Roma, Italy",
  "journey_date": "2025-09-12",
  "journey_time": "09:00",
  "total_price": "513.75",
  "order_id": "433744",

  "customer_name": "Edoardo Guzzi",
  "customer_email": "edoardo.guzzi@aifb.ch",
  "customer_phone": "+39 123 456 7890",

  "passengers": 2,
  "bags": 1,
  "transport_quantity": 1,

  "vehicle_details": {
    "name": "BMW 5 Series Long",
    "model": "EXPRW",
    "engine": "3000",
    "fuel": "Diesel",
    "transmission": "Automatic",
    "seating_capacity": "5",
    "max_passenger": 4,
    "max_bag": 3,
    "image": "https://example.com/vehicle-image.jpg",
    "custom_features": [
      {
        "label": "Custom Feature",
        "value": "Custom Value",
        "icon": "fas fa-icon",
        "image": ""
      }
    ]
  },

  "is_return": false,
  "return_date": null,
  "return_time": null,

  "waiting_time": "0",
  "fixed_hours": "0",
  "extra_services": [
    {
      "name": "Child Seat",
      "quantity": 1,
      "price": "50.00"
    }
  ],

  "distance": "15.5 km",
  "duration": "25 min",

  "base_price": "73.75",
  "extra_service_price": "440.00",

  "payment_method": "Credit Card",
  "order_status": "processing",

  "order_notes": [
    {
      "id": 123,
      "date": "2025-09-12 08:40:00",
      "author": "Edoardo Guzzi",
      "content": "Please pick up at the main entrance",
      "customer_note": true
    },
    {
      "id": 124,
      "date": "2025-09-12 08:45:00",
      "author": "Admin",
      "content": "Driver assigned: Mario Rossi",
      "customer_note": false
    }
  ]
}
```

## Note Tecniche

- **Compatibilità:** Tutte le modifiche sono backward compatible
- **Performance:** Le query sono ottimizzate per evitare overhead
- **Sicurezza:** Tutti i dati sono sanitizzati prima dell'uso
- **Fallback:** Implementati fallback robusti per garantire dati consistenti
- **Filtri:** Custom features ora contengono solo campi effettivamente personalizzati

## Testing

Per testare le modifiche:

1. Verificare che le custom features non mostrino più campi predefiniti
2. Testare che `is_return` sia `false` per viaggi non di ritorno
3. Verificare che passengers e bags corrispondano ai valori del frontend
4. Controllare che le order notes siano incluse nella risposta API
5. Testare con ordini creati in diverse modalità (WooCommerce, diretti, etc.)

### 5. **Extra Service Price Vuoto**

**Problema:** Il campo `extra_service_price` non riportava alcun valore nelle REST API.

**Causa:** Il plugin non salvava il campo `mptbm_extra_service_price` quando veniva creato il booking tramite WooCommerce.

**Soluzione:** Aggiunta logica per calcolare il prezzo dei servizi extra dall'array `extra_services` quando il campo non è presente:

```php
// If extra_service_price is empty, calculate it from extra_services array
if (empty($extra_service_price) && !empty($extra_services) && is_array($extra_services)) {
    $calculated_extra_price = 0;
    foreach ($extra_services as $service) {
        // Check for different possible field names
        if (isset($service['service_price']) && isset($service['service_quantity'])) {
            $calculated_extra_price += floatval($service['service_price']) * intval($service['service_quantity']);
        } elseif (isset($service['price']) && isset($service['quantity'])) {
            $calculated_extra_price += floatval($service['price']) * intval($service['quantity']);
        }
    }
    $extra_service_price = $calculated_extra_price > 0 ? number_format($calculated_extra_price, 2, '.', '') : '';
}
```

**Vantaggi:**

- Compatibilità con bookings creati tramite WooCommerce che non avevano questo campo salvato
- Calcolo dinamico basato sui servizi extra effettivamente selezionati
- Fallback robusto per garantire che il campo abbia sempre un valore corretto
- Formattazione standardizzata con sempre 2 decimali (es. "25.00" invece di "25")

### 6. **Journey Time Non Popolato**

**Problema:** Il campo `journey_time` non veniva popolato nelle REST API anche se `journey_date` conteneva la data e l'ora completa.

**Causa:** Il plugin non salvava separatamente il campo `mptbm_time` o `mptbm_journey_time`, ma `journey_date` conteneva già la data e l'ora completa.

**Soluzione:** Implementata logica per estrarre l'orario dalla data completa:

```php
// If journey_time is still empty, extract time from journey_date
if (empty($journey_time) && !empty($journey_date)) {
    // Check if journey_date contains time (format: Y-m-d H:i:s or Y-m-d H:i)
    if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $journey_date)) {
        $journey_time = date('H:i', strtotime($journey_date));
    }
}
```

La data originale viene mantenuta intatta per preservare tutte le informazioni:

**Vantaggi:**

- Estrazione intelligente dell'orario dalla data completa
- Preservazione della data originale con tutte le informazioni
- Compatibilità con diversi formati di data
- Soluzione semplice e robusta

fix
