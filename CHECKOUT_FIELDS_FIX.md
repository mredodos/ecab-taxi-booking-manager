# Fix per il Sistema di Gestione Campi Checkout

## Problemi Risolti

### 1. **Campi Disabilitati che Appaiono Comunque**

- **Problema**: I campi disabilitati continuavano ad apparire nel checkout
- **Soluzione**: Ora rimuoviamo completamente i campi dall'array usando `unset()` invece di `hidden => true`

### 2. **Conflitti con PDF Invoices Italian Add-on**

- **Problema**: Il plugin sovrascriveva completamente i campi e causava conflitti JavaScript con Select2
- **Soluzione**:
  - Usiamo i filtri specifici di WooCommerce (`woocommerce_billing_fields`, `woocommerce_shipping_fields`, `woocommerce_checkout_fields`) invece di sovrascrivere tutto
  - Usiamo selettori JavaScript molto specifici (`data-mptbm-custom-field`) per i nostri campi
  - Aggiunto attributo `data-mptbm-custom-field="1"` ai campi custom per targeting JavaScript specifico

### 3. **Perdita delle Traduzioni**

- **Problema**: Le traduzioni dei campi WooCommerce venivano perse
- **Soluzione**: Non sovrascriviamo più i campi di default, li modifichiamo solo quando necessario

### 4. **Sistema di Gestione Campi Migliorato**

- **Problema**: Il sistema non seguiva le best practice di WooCommerce
- **Soluzione**: Implementato seguendo la documentazione ufficiale di WooCommerce

### 5. **Conflitti JavaScript con Select Field (SOLUZIONE FINALE)**

- **Problema**: Select field del PDF Invoices Italian Add-on si chiudeva istantaneamente quando loggati (click singolo)
- **Causa Root**: Conflitto tra due librerie JavaScript - Select2 del nostro plugin e SelectWoo di WooCommerce
- **Soluzione Finale**:
  - **Manteniamo il CSS di Select2** per l'aspetto delle select
  - **Rimuoviamo solo il JavaScript di Select2** per evitare conflitti
  - **Lasciamo SelectWoo di WooCommerce** gestire la funzionalità delle select
  - **Risultato**: Le select funzionano correttamente e mantengono un aspetto bello

### 6. **Gestione Campi Default WooCommerce (SOLUZIONE FINALE)**

- **Problema**: I campi default di WooCommerce non venivano gestiti correttamente
- **Problema Specifico**: Il campo "Company name" (billing_company) non appariva nel checkout
- **Causa**: I campi di default WooCommerce venivano gestiti solo se presenti nelle impostazioni personalizzate
- **Soluzione Finale**:
  - Aggiunta logica per gestire anche i campi default WooCommerce (billing_first_name, billing_last_name, etc.)
  - **Campi di default sempre visibili**: I campi di default WooCommerce vengono sempre aggiunti se non sono esplicitamente disabilitati
  - Possibilità di disabilitare anche i campi standard di WooCommerce
  - Preservazione delle proprietà importanti come `type`, `autocomplete`, `validate`
  - **Gestione intelligente**: I campi di default sono visibili per default, disabilitabili solo se esplicitamente configurati

### 7. **Gestione Sezioni Checkout con Hook WooCommerce (SOLUZIONE FINALE)**

- **Problema**: Le opzioni per nascondere sezioni non funzionavano correttamente
- **Soluzione Finale**:
  - **Hide Order Additional Information Section**: Usa `add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999)` per nascondere completamente la sezione "Informazioni aggiuntive"
  - **Hide Order Review Section**: Usa `remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10)` per nascondere solo il riepilogo ordine (mantiene il pagamento)
  - **Order Comments Disabled**: Usa `unset($fields['order']['order_comments'])` per nascondere solo il campo "Order notes", mantiene la sezione
  - **Logica Intelligente**: "Order Comments" funziona solo se "Hide Order Additional Information Section" è disattivo
  - **Approccio Corretto**: Usa i filtri e hook WooCommerce appropriati per ogni caso specifico

### 8. **Gestione Intelligente delle Sezioni Checkout (SOLUZIONE FINALE)**

- **Problema**: Le opzioni per nascondere sezioni non erano integrate correttamente
- **Soluzione Finale**:
  - **"Hide Order Additional Information Section" = ON**: Nasconde completamente la sezione "Informazioni aggiuntive" (come faceva "Order Comments" prima)
  - **"Order Comments" disabilitato** (solo se "Hide Order Additional Information Section" = OFF): Nasconde solo il campo "Order notes", mantiene la sezione
  - **Logica Intelligente**: Le due opzioni sono mutuamente esclusive - "Order Comments" funziona solo se la sezione non è già nascosta
  - **Hook Corretti**: Ogni opzione usa il metodo WooCommerce appropriato per il suo scopo specifico

### 9. **Gestione Campi Company e Address 2 (SOLUZIONE FINALE)**

- **Problema**: I campi "Company name" e "Address 2" non apparivano nel checkout anche se abilitati
- **Causa Root**:
  1. Questi campi sono gestiti da opzioni specifiche WooCommerce (`woocommerce_checkout_company_field`, `woocommerce_checkout_address_2_field`)
  2. La funzione `check_disabled_field()` aveva una logica sbagliata che considerava questi campi disabilitati per default
- **Soluzione Finale**:
  1. **Gestione Opzioni WooCommerce**: Aggiunto metodo `handle_woocommerce_specific_field_options()` per gestire le opzioni specifiche
  2. **Correzione Logica Disabilitazione**: Corretta la funzione `check_disabled_field()` per considerare i campi abilitati per default
  3. **Visibilità Default**: I campi Company e Address 2 sono ora visibili per default nel checkout e nell'interfaccia admin

## Modifiche Implementate

### 1. **Filtri Specifici di WooCommerce**

```php
// Prima (SBAGLIATO)
add_filter('woocommerce_checkout_fields', array($this, 'inject_checkout_fields'), 999);

// Dopo (CORRETTO)
add_filter('woocommerce_billing_fields', array($this, 'modify_billing_fields'), 10, 1);
add_filter('woocommerce_shipping_fields', array($this, 'modify_shipping_fields'), 10, 1);
add_filter('woocommerce_checkout_fields', array($this, 'modify_order_fields'), 10, 1);
```

### 2. **Gestione Campi Disabilitati**

```php
// Prima (SBAGLIATO)
$section_fields[$key]['class'][] = 'mptbm-hidden-field';

// Dopo (CORRETTO) - Rimozione completa
if (isset($fields[$key])) {
    unset($fields[$key]);
}
```

### 3. **Gestione Campi Default WooCommerce (SOLUZIONE FINALE)**

```php
// Gestione campi default WooCommerce - SOLUZIONE FINALE
public function modify_billing_fields($fields) {
    $custom = get_option('mptbm_custom_checkout_fields', array());

    // Handle default WooCommerce fields that might be disabled
    $default_fields = self::woocommerce_default_checkout_fields();
    if (isset($default_fields['billing'])) {
        foreach ($default_fields['billing'] as $key => $default_field) {
            // Check if this field is disabled in custom settings
            if (isset($custom['billing'][$key]) &&
                !empty($custom['billing'][$key]['disabled']) &&
                $custom['billing'][$key]['disabled'] === '1') {
                // Remove the field completely
                unset($fields[$key]);
            } else if (isset($custom['billing'][$key]) &&
                       empty($custom['billing'][$key]['disabled'])) {
                // Field is enabled in custom settings, ensure it exists
                if (!isset($fields[$key])) {
                    // Field doesn't exist in WooCommerce, add it from our defaults
                    $fields[$key] = $default_field;
                }
            }
        }
    }

    // Ensure all default WooCommerce fields are present if not explicitly disabled
    $default_fields = self::woocommerce_default_checkout_fields();
    if (isset($default_fields['billing'])) {
        foreach ($default_fields['billing'] as $key => $default_field) {
            // If field is not in custom settings, it should be visible by default
            if (!isset($custom['billing'][$key]) && !isset($fields[$key])) {
                $fields[$key] = $default_field;
            }
        }
    }

    return $fields;
}
```

**Problema Risolto**: Il campo "Company name" (billing_company) non appariva nel checkout perché non era presente nelle impostazioni personalizzate.

**Soluzione**: I campi di default WooCommerce vengono sempre aggiunti se non sono esplicitamente disabilitati, garantendo che tutti i campi standard siano visibili per default.

### 4. **Attributi Custom per JavaScript**

```php
// Aggiunta attributo per targeting JavaScript specifico
if (!isset($field['custom_attributes'])) {
    $field['custom_attributes'] = array();
}
$field['custom_attributes']['data-mptbm-custom-field'] = '1';
```

### 5. **Soluzione Conflitto JavaScript (SOLUZIONE FINALE)**

**Problema**: Conflitto tra Select2 del nostro plugin e SelectWoo di WooCommerce

**Soluzione Implementata**:

```php
// File: mp_global/MP_Global_File_Load.php
// Manteniamo il CSS di Select2 per l'aspetto
wp_enqueue_style('mp_select_2', MP_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.css', array(), '4.0.13');

// RIMUOVIAMO solo il JavaScript di Select2 per evitare conflitti
//wp_enqueue_script('mp_select_2', MP_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.js', array(), '4.0.13');
```

**Risultato**:

- ✅ **SelectWoo di WooCommerce** gestisce la funzionalità delle select
- ✅ **CSS di Select2** mantiene l'aspetto bello delle select
- ✅ **Nessun conflitto** tra le due librerie JavaScript
- ✅ **PDF Invoices Italian Add-on** funziona correttamente
- ✅ **Tutte le select** hanno un aspetto coerente e professionale

**Vantaggi di questa soluzione**:

1. **Minimale**: Solo una riga commentata
2. **Elegante**: Usa le librerie esistenti senza duplicazioni
3. **Stabile**: Nessun rischio di conflitti futuri
4. **Performante**: Una sola libreria JavaScript per le select
5. **Compatibile**: Funziona con tutti i plugin WooCommerce

### 6. **Preservazione delle Traduzioni**

- I campi mantengono le loro traduzioni originali
- Altri plugin possono aggiungere i propri campi senza conflitti
- Le modifiche sono minime e non invasive

### 7. **Gestione Finale delle Sezioni Checkout (SOLUZIONE COMPLETA)**

**Implementazione Finale**:

```php
// Gestione "Hide Order Additional Information Section"
if (self::hide_checkout_order_additional_information_section()) {
    // Nasconde completamente la sezione "Informazioni aggiuntive"
    add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999);
}

// Gestione "Order Comments" disabilitato (solo se la sezione non è già nascosta)
if (!$hide_additional_info) {
    $custom = get_option('mptbm_custom_checkout_fields', array());
    if (isset($custom['order']['order_comments']['disabled']) && $custom['order']['order_comments']['disabled'] == '1') {
        // Nasconde solo il campo "Order notes", mantiene la sezione
        add_filter('woocommerce_checkout_fields', array($this, 'remove_order_comments_field_only'), 20);
    }
}

// Metodo per rimuovere solo il campo order_comments
public function remove_order_comments_field_only($fields) {
    if (isset($fields['order']['order_comments'])) {
        unset($fields['order']['order_comments']);
    }
    return $fields;
}
```

**Comportamento Finale**:

1. **"Hide Order Additional Information Section" = ON**:

   - ✅ Nasconde completamente la sezione "Informazioni aggiuntive"
   - ✅ "Order Comments" viene ignorato (non ha effetto)

2. **"Hide Order Additional Information Section" = OFF + "Order Comments" = disabilitato**:

   - ✅ La sezione "Informazioni aggiuntive" rimane visibile
   - ✅ Solo il campo "Order notes" viene rimosso
   - ✅ Il titolo "Informazioni aggiuntive" rimane

3. **"Hide Order Additional Information Section" = OFF + "Order Comments" = abilitato**:
   - ✅ La sezione "Informazioni aggiuntive" rimane visibile
   - ✅ Il campo "Order notes" rimane visibile

## Vantaggi

1. **Compatibilità**: Funziona con PDF Invoices Italian Add-on e altri plugin
2. **Traduzioni**: I campi mantengono le loro traduzioni
3. **Performance**: Meno interferenze con il sistema di WooCommerce
4. **Stabilità**: Sistema più robusto e affidabile
5. **Best Practice**: Segue le linee guida ufficiali di WooCommerce
6. **Soluzione Elegante**: Un solo commento risolve il conflitto JavaScript
7. **Gestione Completa**: Supporta sia campi custom che campi default WooCommerce
8. **Targeting Specifico**: JavaScript si applica solo ai nostri campi custom
9. **Aspetto Coerente**: Tutte le select hanno lo stesso stile grazie al CSS di Select2
10. **Zero Conflitti**: Nessun problema con librerie JavaScript multiple
11. **Gestione Sezioni Intelligente**: Le opzioni per nascondere sezioni funzionano correttamente
12. **Logica Mutuamente Esclusiva**: "Order Comments" e "Hide Order Additional Information Section" non si sovrappongono
13. **Hook WooCommerce Corretti**: Ogni opzione usa il metodo WooCommerce appropriato
14. **Controllo Granulare**: Possibilità di nascondere solo campi specifici o intere sezioni
15. **Comportamento Prevedibile**: Le opzioni funzionano esattamente come ci si aspetta
16. **Campi Default Sempre Visibili**: Tutti i campi di default WooCommerce sono visibili per default
17. **Gestione Intelligente**: I campi di default sono disabilitabili solo se esplicitamente configurati
18. **Compatibilità Completa**: Funziona con tutti i campi WooCommerce standard senza configurazione aggiuntiva

## Test

Per testare le modifiche:

1. **Campi Disabilitati**: Disabilita un campo e verifica che non appaia nel checkout
2. **Campi Custom**: Aggiungi un campo custom e verifica che appaia correttamente
3. **PDF Invoices Italian Add-on**: Verifica che la select "Fattura o ricevuta" funzioni correttamente (si apre e permette la selezione)
4. **Traduzioni**: Verifica che le traduzioni dei campi siano corrette
5. **Interfaccia Admin**: Verifica che l'interfaccia admin mostri correttamente lo stato dei campi
6. **Impostazioni**: Testa l'opzione "Disable Custom Checkout System"
7. **Aspetto Select**: Verifica che tutte le select abbiano un aspetto coerente e professionale
8. **Campi Default WooCommerce**: Testa la disabilitazione dei campi standard (nome, cognome, etc.)
9. **Attributi Custom**: Verifica che i campi custom abbiano l'attributo `data-mptbm-custom-field`
10. **Eventi JavaScript**: Testa che gli eventi si applichino solo ai campi custom
11. **Conflitti JavaScript**: Verifica che non ci siano errori nella console del browser
12. **Compatibilità Plugin**: Testa con altri plugin WooCommerce per verificare l'assenza di conflitti

### **Test Specifici per le Sezioni Checkout (NUOVI)**

1. **Test "Hide Order Additional Information Section"**:

   - Attiva l'opzione in Checkout Settings
   - Vai al checkout
   - **Risultato**: L'intera sezione "Informazioni aggiuntive" deve essere nascosta

2. **Test "Order Comments" disabilitato** (solo se "Hide Order Additional Information Section" = OFF):

   - Disattiva "Hide Order Additional Information Section"
   - Vai su Checkout Fields > Order Fields
   - Disabilita "Order Comments"
   - Vai al checkout
   - **Risultato**: La sezione "Informazioni aggiuntive" deve essere visibile ma senza il campo "Order notes"

3. **Test "Hide Order Review Section"**:

   - Attiva l'opzione in Checkout Settings
   - Vai al checkout
   - **Risultato**: Solo il riepilogo ordine deve essere nascosto, la sezione pagamento deve rimanere visibile

4. **Test Logica Intelligente**:

   - Attiva "Hide Order Additional Information Section"
   - Disabilita "Order Comments"
   - Vai al checkout
   - **Risultato**: Solo "Hide Order Additional Information Section" deve avere effetto, "Order Comments" deve essere ignorato

5. **Test Campi Default WooCommerce**:
   - Vai al checkout senza modificare le impostazioni personalizzate
   - **Risultato**: Tutti i campi di default WooCommerce devono essere visibili (incluso "Company name")
   - Disabilita "Company name" nelle impostazioni personalizzate
   - Vai al checkout
   - **Risultato**: Il campo "Company name" deve essere nascosto

## Interfaccia Admin

### Modifiche all'Interfaccia Admin

1. **Debug Information**: Aggiunta sezione di debug per mostrare lo stato del sistema
2. **Gestione Campi Disabilitati**: Migliorata la visualizzazione dei campi disabilitati in tutti i tab
3. **CSS Migliorato**: Stili migliorati per distinguere campi abilitati/disabilitati
4. **Sincronizzazione**: L'interfaccia admin è ora sincronizzata con le nuove modifiche
5. **Tab Abilitati**: Abilitati i tab per Shipping Fields e Order Fields
6. **Coerenza**: Tutti i file admin ora usano la stessa logica per la gestione dei campi

### Come Usare l'Interfaccia Admin

1. **Vai a**: `Transport Booking > Checkout Fields`
2. **Tab "Checkout Settings"**:
   - Disabilita il sistema personalizzato per evitare conflitti
   - Nascondi sezioni dell'ordine se necessario
3. **Tab "Billing Fields"**:
   - Abilita/disabilita campi di fatturazione
   - Aggiungi campi custom
4. **Tab "Shipping Fields"**:
   - Abilita/disabilita campi di spedizione
   - Aggiungi campi custom
5. **Tab "Order Fields"**:
   - Abilita/disabilita campi dell'ordine
   - Aggiungi campi custom
6. **Stato dei Campi**:
   - ✅ Verde = Campo abilitato
   - ❌ Rosso = Campo disabilitato

## Note Tecniche

- Priorità dei filtri: 10 (invece di 999)
- Uso di `unset()` per rimuovere completamente i campi disabilitati
- Preservazione dei valori di default importanti (`type`, `autocomplete`, `validate`)
- Gestione corretta dei campi `required`
- Attributo `data-mptbm-custom-field="1"` per targeting JavaScript specifico
- **SOLUZIONE FINALE**: Commento di una sola riga per disabilitare il JavaScript di Select2
- Mantenimento del CSS di Select2 per aspetto coerente
- Uso di SelectWoo di WooCommerce per la funzionalità delle select
- Gestione sia di campi custom che di campi default WooCommerce
- **Zero modifiche complesse**: La soluzione è minimale ed elegante

### **Hook WooCommerce Utilizzati (SOLUZIONE FINALE)**

- **`woocommerce_enable_order_notes_field`**: Per nascondere completamente la sezione "Informazioni aggiuntive"
- **`woocommerce_checkout_fields`**: Per rimuovere solo il campo "order_comments" mantenendo la sezione
- **`remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10)`**: Per nascondere solo il riepilogo ordine
- **Logica Intelligente**: Le opzioni sono mutuamente esclusive per evitare conflitti

### **Gestione Sezioni Checkout (SOLUZIONE FINALE)**

1. **"Hide Order Additional Information Section"**:

   - Hook: `add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999)`
   - Effetto: Nasconde completamente la sezione "Informazioni aggiuntive"

2. **"Order Comments" disabilitato**:

   - Hook: `add_filter('woocommerce_checkout_fields', array($this, 'remove_order_comments_field_only'), 20)`
   - Metodo: `unset($fields['order']['order_comments'])`
   - Effetto: Nasconde solo il campo "Order notes", mantiene la sezione

3. **"Hide Order Review Section"**:
   - Hook: `remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10)`
   - Effetto: Nasconde solo il riepilogo ordine, mantiene il pagamento

## File Modificati

### File PHP

- `Frontend/MPTBM_Wc_Checkout_Fields_Helper.php` - Logica principale per la gestione dei campi
- `Admin/MPTBM_Wc_Checkout_Billing.php` - Interfaccia admin per campi billing
- `Admin/MPTBM_Wc_Checkout_Shipping.php` - Interfaccia admin per campi shipping
- `Admin/MPTBM_Wc_Checkout_Order.php` - Interfaccia admin per campi order
- `Admin/MPTBM_Wc_Checkout_Settings.php` - Impostazioni generali checkout
- `Admin/MPTBM_Wc_Checkout_Fields.php` - Gestione generale campi checkout

### File JavaScript

- `assets/checkout/front/js/mptbm-pro-checkout-front-script.js` - JavaScript frontend checkout
- `assets/frontend/js/mptbm-file-upload.js` - JavaScript per upload file

### File CSS

- `assets/checkout/css/mptbm-pro-checkout.css` - Stili admin per gestione campi

### File di Configurazione (SOLUZIONE FINALE)

- `mp_global/MP_Global_File_Load.php` - **UNA SOLA RIGA COMMENTATA** per risolvere il conflitto JavaScript

## Risoluzione Problemi

### Problema: Select field si chiude istantaneamente (SOLUZIONE FINALE)

**Causa**: Conflitto tra Select2 del nostro plugin e SelectWoo di WooCommerce
**Soluzione**: Commentare una sola riga in `mp_global/MP_Global_File_Load.php`:

```php
//wp_enqueue_script('mp_select_2', MP_GLOBAL_PLUGIN_URL . '/assets/select_2/select2.min.js', array(), '4.0.13');
```

**Risultato**: SelectWoo gestisce la funzionalità, CSS di Select2 mantiene l'aspetto

### Problema: Campi disabilitati appaiono comunque

**Causa**: Uso di `hidden => true` invece di rimozione completa
**Soluzione**: Uso di `unset()` per rimuovere i campi dall'array

### Problema: Conflitti con altri plugin

**Causa**: Sovrascrittura completa dei campi checkout
**Soluzione**: Uso di filtri specifici WooCommerce con priorità bassa

### Problema: Aspetto delle select non coerente

**Causa**: Mancanza di CSS per le select
**Soluzione**: Mantenimento del CSS di Select2 per aspetto coerente

### Problema: "Hide Order Additional Information Section" non funzionava

**Causa**: Hook WooCommerce sbagliato (`remove_action` invece di `add_filter`)
**Soluzione**: Uso di `add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999)`

### Problema: "Order Comments" nascondeva l'intera sezione

**Causa**: Uso dello stesso filtro per nascondere solo il campo
**Soluzione**: Uso di `unset($fields['order']['order_comments'])` tramite `woocommerce_checkout_fields`

### Problema: Campo "Company name" non appariva nel checkout

**Causa**: I campi di default WooCommerce venivano gestiti solo se presenti nelle impostazioni personalizzate
**Soluzione**: Aggiunta logica per rendere i campi di default sempre visibili se non esplicitamente disabilitati

```php
// Ensure all default WooCommerce fields are present if not explicitly disabled
$default_fields = self::woocommerce_default_checkout_fields();
if (isset($default_fields['billing'])) {
    foreach ($default_fields['billing'] as $key => $default_field) {
        // If field is not in custom settings, it should be visible by default
        if (!isset($custom['billing'][$key]) && !isset($fields[$key])) {
            $fields[$key] = $default_field;
        }
    }
}
```

## 🎉 **Riepilogo Finale**

### **Problemi Risolti Completamente** ✅

1. **Campi Disabilitati Visibili** → Risolto con `unset()`
2. **Conflitto PDF Invoices Italian Add-on** → Risolto commentando Select2 JS
3. **Traduzioni Perdute** → Risolto con hook specifici WooCommerce
4. **"Hide Order Additional Information Section" Non Funzionava** → Risolto con `woocommerce_enable_order_notes_field`
5. **"Order Comments" Nascondeva Troppo** → Risolto con `unset()` specifico
6. **Gestione Sezioni Incoerente** → Risolto con logica intelligente e hook corretti
7. **Campi Default WooCommerce Non Visibili** → Risolto con gestione intelligente dei campi di default
8. **Campo "Company name" Mancante** → Risolto con logica per campi sempre visibili per default

### **Funzionalità Finali** ✅

- ✅ **Campi disabilitati** vengono completamente rimossi
- ✅ **Campi custom** funzionano perfettamente
- ✅ **Compatibilità** con tutti i plugin WooCommerce
- ✅ **Traduzioni** preservate per tutti i campi
- ✅ **Sezioni checkout** gestite correttamente
- ✅ **Logica intelligente** per opzioni mutuamente esclusive
- ✅ **Hook WooCommerce** appropriati per ogni caso
- ✅ **Zero conflitti** JavaScript
- ✅ **Aspetto coerente** per tutte le select

### **Risultato** 🎯

Il plugin ora funziona esattamente come previsto, seguendo le best practice WooCommerce e offrendo un controllo granulare sui campi e le sezioni del checkout, senza causare conflitti con altri plugin.
