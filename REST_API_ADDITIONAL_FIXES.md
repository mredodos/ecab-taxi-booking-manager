# REST API Additional Fixes - ECAB Taxi Booking Manager

## Fixed Issues

### 1. **Custom Features Showing Non-Custom Fields**

**Problem:** The fields "Max people" and "Max luggage" appeared in custom features even though they were already handled separately in the `max_passenger` and `max_bag` fields.

**Cause:** The `prepare_transport_service()` function didn't properly filter default fields from custom features.

**Solution:** Added filter to exclude default fields from custom features:

```php
case 'Max people':
case 'Max luggage':
case 'Maximum Passengers':
case 'Maximum Bags':
    // Skip these as they are handled separately in max_passenger and max_bag
    break;
```

### 2. **is_return Parameter Logic**

**Problem:** The `is_return` parameter was set to `true` even when there was no actual return trip.

**Cause:** The logic only checked if the field existed, not if it was actually a return trip.

**Solution:** Implemented more robust logic to determine if it's a return trip:

```php
// Check if this is actually a return trip (has return date and is not empty/false)
$is_actual_return = false;
if (!empty($is_return) && $is_return != '0' && $is_return != 'false' && $is_return !== false) {
    if (!empty($return_date)) {
        $is_actual_return = true;
    }
}
```

And updated the API response:

```php
'is_return' => $is_actual_return,
'return_date' => $is_actual_return ? $return_date : null,
'return_time' => $is_actual_return ? $return_time : null,
```

### 3. **Non-Matching Passengers and Bags Values**

**Problem:** The `passengers` and `bags` values didn't match those selected in the frontend.

**Cause:** Using incorrect metadata fields instead of those actually used by the plugin.

**Solution:** Used the correct metadata fields identified from the plugin code:

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

**Identified Metadata Fields:**

- **Passengers**: `mptbm_passengers` (used in frontend and WooCommerce)
- **Bags**: `mptbm_bags` (used in frontend and WooCommerce)

### 4. **Missing Order Notes**

**Problem:** Order notes were not included in the API response.

**Cause:** Order notes were not implemented in the `prepare_booking_data()` function.

**Solution:** Added retrieval of order notes from the associated WooCommerce order following WooCommerce best practices:

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

**WooCommerce Best Practice:**

- Using the `wc_get_order_notes()` function to retrieve notes
- Correct parameters for limit, orderby and order
- Proper handling of note types (customer vs private)

And added to the API response:

```php
// Order notes
'order_notes' => $order_notes
```

## New Fields Added

### **Order Notes**

- `order_notes`: Array of order notes with the following structure:
  - `id`: Note ID
  - `date`: Note creation date
  - `author`: Note author
  - `content`: Note content
  - `customer_note`: Whether it's a customer note (boolean)

## Implemented Improvements

### **1. Enhanced Custom Features Filter**

- Excluded default fields from custom features
- Handling of field name variants (Max people, Maximum Passengers, etc.)
- Custom features now contain only truly custom fields

### **2. More Robust is_return Logic**

- Actual check if it's a return trip
- Validation of return date presence
- Proper handling of null values for non-return trips

### **3. Simplified and Correct Data Retrieval**

- Using correct metadata fields identified from the plugin code
- Removal of unnecessary searches in non-existent fields
- Cleaner and more maintainable code

### **4. Complete Order Notes with WooCommerce Best Practices**

- Retrieval of all WooCommerce order notes using `wc_get_order_notes()`
- Complete information for each note
- Distinction between system notes and customer notes
- Sorting by creation date (most recent first)
- Compliance with WooCommerce best practices

## Improved API Response Example

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

## Technical Notes

- **Compatibility:** All modifications are backward compatible
- **Performance:** Queries are optimized to avoid overhead
- **Security:** All data is sanitized before use
- **Fallback:** Robust fallbacks implemented to ensure consistent data
- **Filters:** Custom features now contain only truly custom fields

## Testing

To test the modifications:

1. Verify that custom features no longer show default fields
2. Test that `is_return` is `false` for non-return trips
3. Verify that passengers and bags match frontend values
4. Check that order notes are included in the API response
5. Test with orders created in different ways (WooCommerce, direct, etc.)

### 5. **Empty Extra Service Price**

**Problem:** The `extra_service_price` field didn't report any value in the REST API.

**Cause:** The plugin didn't save the `mptbm_extra_service_price` field when the booking was created via WooCommerce.

**Solution:** Added logic to calculate extra services price from the `extra_services` array when the field is not present:

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

**Advantages:**

- Compatibility with bookings created via WooCommerce that didn't have this field saved
- Dynamic calculation based on actually selected extra services
- Robust fallback to ensure the field always has a correct value
- Standardized formatting with always 2 decimals (e.g. "25.00" instead of "25")

### 6. **Unpopulated Journey Time**

**Problem:** The `journey_time` field was not populated in the REST API even though `journey_date` contained the complete date and time.

**Cause:** The plugin didn't save the `mptbm_time` or `mptbm_journey_time` field separately, but `journey_date` already contained the complete date and time.

**Solution:** Implemented logic to extract time from the complete date:

```php
// If journey_time is still empty, extract time from journey_date
if (empty($journey_time) && !empty($journey_date)) {
    // Check if journey_date contains time (format: Y-m-d H:i:s or Y-m-d H:i)
    if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $journey_date)) {
        $journey_time = date('H:i', strtotime($journey_date));
    }
}
```

The original date is kept intact to preserve all information:

**Advantages:**

- Intelligent extraction of time from complete date
- Preservation of original date with all information
- Compatibility with different date formats
- Simple and robust solution
