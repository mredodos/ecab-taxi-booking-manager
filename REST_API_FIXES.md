# REST API Fixes - ECAB Taxi Booking Manager

## Issues Resolved

### 1. **Empty Data in Bookings**

**Problem:** The `/mptbm/v1/bookings` endpoint returned empty data for bookings created through WooCommerce.

**Cause:** Bookings created through WooCommerce use different metadata names than what the REST API expected:

- **REST API looked for:** `mptbm_pickup_location`, `mptbm_dropoff_location`, `mptbm_journey_date`, `mptbm_total_price`
- **WooCommerce saved:** `mptbm_start_place`, `mptbm_end_place`, `mptbm_date`, `mptbm_tp`

**Solution:** Modified the `prepare_booking_data()` function to search for data in both metadata sets:

```php
// Try to get pickup location from different possible meta fields
$pickup_location = get_post_meta($booking_id, 'mptbm_pickup_location', true);
if (empty($pickup_location)) {
    $pickup_location = get_post_meta($booking_id, 'mptbm_start_place', true);
}
```

### 2. **Display of Cancelled Bookings**

**Problem:** The endpoint returned both active and cancelled bookings.

**Cause:** The query used `'post_status' => 'any'` which included all statuses.

**Solution:**

- Added `status` parameter with default `'publish'`
- Implemented validation for allowed statuses: `publish`, `trash`, `any`, `draft`, `private`

### 3. **Missing Filters and Query Parameters**

**Problem:** The endpoint didn't support advanced filters for bookings.

**Solution:** Added complete query parameters:

- `status`: Filter for booking status
- `per_page`: Number of results per page (max 100)
- `offset`: Offset for pagination
- `orderby`: Field for sorting (date, id, title, modified)
- `order`: Sort direction (ASC, DESC)
- `customer_id`: Filter for customer ID
- `transport_id`: Filter for transport service ID
- `order_id`: Filter for WooCommerce order ID

### 4. **Empty Customer ID in Bookings**

**Problem:** The `customer_id` field returned empty values for both logged-in users and guests.

**Cause:** The plugin used `get_post_meta()` to retrieve `_customer_user` from the WooCommerce order instead of WooCommerce's CRUD API.

**Solution:**

- Replaced `get_post_meta($order_id)['_customer_user'][0]` with `$order->get_customer_id()` for new orders
- Added fallback to retrieve `customer_id` from WooCommerce order for existing orders
- Implemented correct handling for logged-in users (numeric ID) and guests (0)

### 5. **Empty Vehicle Details in REST API**

**Problem:** Vehicle details (model, engine, fuel, transmission, max_passenger, max_bag) appeared empty in the API response.

**Cause:** The `prepare_transport_service()` function used wrong metadata names and didn't properly process the `mptbm_features` array.

**Solution:**

- Corrected metadata names: `mptbm_maximum_passenger` and `mptbm_maximum_bag` instead of `mptbm_max_passenger` and `mptbm_max_bag`
- Implemented dynamic handling of the `mptbm_features` array to extract predefined features
- Added support for custom features through `custom_features` array
- Intelligent vehicle name handling (customizable through "Name" feature)

## Implemented Changes

### File: `inc/MPTBM_Rest_Api.php`

1. **Function `get_all_bookings()`:**

   - Added support for query parameters
   - Implemented filters for customer_id and transport_id
   - Improved status handling

2. **Function `prepare_booking_data()`:**

   - Added logic to search metadata in both formats
   - Added `order_id` field to link booking and order
   - Improved handling of customer_id and transport_id fields
   - Added fallback to retrieve customer_id from WooCommerce order

3. **New function `get_bookings_query_args()`:**

   - Complete definition of query parameters
   - Parameter validation
   - Integrated documentation

4. **Endpoint registration:**

   - Added support for query parameters in `/bookings` endpoint

5. **Function `prepare_booking_data()`:**

   - Added "Vehicle details" section in API response
   - Reused existing `prepare_transport_service()` function to retrieve vehicle details
   - Extracted `vehicle_details` section from transport service response

6. **Specific endpoint `get_booking_details()`:**

   - Corrected metadata names for WooCommerce compatibility
   - Replaced wrong metadata with correct ones saved by the plugin

7. **Function `prepare_transport_service()`:**
   - Corrected metadata names for vehicle details (`mptbm_maximum_passenger`, `mptbm_maximum_bag`)
   - Implemented dynamic handling of custom features through `mptbm_features` array
   - Added support for predefined features: Name, Model, Engine, Fuel Type, Transmission, Seating Capacity
   - Added `custom_features` array for custom features
   - Removed unused "type" field

## Usage

### API Call Examples

```bash
# Get only active bookings (default)
GET /wp-json/mptbm/v1/bookings

# Get all bookings (including cancelled)
GET /wp-json/mptbm/v1/bookings?status=any

# Filter by specific customer
GET /wp-json/mptbm/v1/bookings?customer_id=123

# Filter by transport service
GET /wp-json/mptbm/v1/bookings?transport_id=456

# Filter by WooCommerce order
GET /wp-json/mptbm/v1/bookings?order_id=433744

# Pagination
GET /wp-json/mptbm/v1/bookings?per_page=10&offset=20

# Sorting
GET /wp-json/mptbm/v1/bookings?orderby=date&order=ASC
```

### Improved API Response

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

  // Customer information
  "customer_name": "Edoardo Guzzi",
  "customer_email": "edoardo.guzzi@aifb.ch",
  "customer_phone": "+39 123 456 7890",

  // Trip details
  "passengers": 2,
  "bags": 1,
  "transport_quantity": 1,

  // Vehicle details
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

  // Return trip
  "is_return": false,
  "return_date": null,
  "return_time": null,

  // Additional services
  "waiting_time": "0",
  "fixed_hours": "0",
  "extra_services": [
    {
      "name": "Child Seat",
      "quantity": 1,
      "price": "50.00"
    },
    {
      "name": "Bouquet of Flowers",
      "quantity": 2,
      "price": "300.00"
    }
  ],

  // Route information
  "distance": "15.5 km",
  "duration": "25 min",

  // Pricing breakdown
  "base_price": "73.75",
  "extra_service_price": "440.00",

  // Payment information
  "payment_method": "Credit Card",
  "order_status": "processing"
}
```

## New Fields Added

### **Customer Information**

- `customer_name`: Customer's full name
- `customer_email`: Customer's email
- `customer_phone`: Customer's phone

### **Trip Details**

- `passengers`: Number of passengers (from `mptbm_passengers`)
- `bags`: Number of bags (from `mptbm_bags`)
- `transport_quantity`: Number of vehicles booked
- `journey_time`: Journey time

### **Vehicle Details**

- `vehicle_details`: Object containing details of the selected vehicle
  - `name`: Vehicle name (customizable through "Name" feature)
  - `model`: Vehicle model
  - `engine`: Engine type
  - `fuel`: Fuel type
  - `transmission`: Transmission type
  - `seating_capacity`: Seating capacity
  - `max_passenger`: Maximum number of passengers (from `mptbm_maximum_passenger`)
  - `max_bag`: Maximum number of bags (from `mptbm_maximum_bag`)
  - `image`: Vehicle image URL
  - `custom_features`: Array of custom features
    - `label`: Feature name
    - `value`: Feature value
    - `icon`: Associated icon
    - `image`: Associated image

### **Return Trip**

- `is_return`: Whether it's a return trip (boolean)
- `return_date`: Return date
- `return_time`: Return time

### **Additional Services**

- `waiting_time`: Extra waiting time
- `fixed_hours`: Fixed service hours
- `extra_services`: Array of extra services with details

### **Route Information**

- `distance`: Route distance
- `duration`: Estimated journey duration

### **Price Breakdown**

- `base_price`: Base transport price
- `extra_service_price`: Total extra services price

### **Payment Information**

- `payment_method`: Payment method used
- `order_status`: WooCommerce order status

## Technical Notes

- **Compatibility:** Changes are backward compatible
- **Performance:** Added parameter validation to avoid inefficient queries
- **Security:** All parameters are sanitized before use
- **Documentation:** Parameters fully documented for API documentation auto-generation
- **Fallback:** Fields search in multiple metadata to ensure compatibility with bookings created in different ways
- **Dynamic Features:** Automatic handling of custom features added by admin
- **Vehicle Details:** Complete and accurate retrieval of all vehicle details

## Testing

To test the changes:

1. Verify that the `/bookings` endpoint returns complete data
2. Test filters for status, customer and service
3. Verify that WooCommerce bookings show correct data
4. Test pagination and sorting
5. Verify that vehicle details appear correctly
6. Test adding custom features in admin and verify them in API

## Vehicle Features Handled

### **Predefined Features**

The following features are handled automatically and appear as separate fields in `vehicle_details`:

- **Name**: Customizable vehicle name (if present, overrides post_title)
- **Model**: Vehicle model
- **Engine**: Engine type
- **Fuel Type**: Fuel type
- **Transmission**: Transmission type
- **Seating Capacity**: Seating capacity

### **Custom Features**

All other features added in the admin panel appear in the `custom_features` array with the following structure:

```json
{
  "custom_features": [
    {
      "label": "Feature Name",
      "value": "Feature Value",
      "icon": "fas fa-icon",
      "image": "image-url"
    }
  ]
}
```

### **Metadata Used**

- `mptbm_maximum_passenger`: Maximum number of passengers
- `mptbm_maximum_bag`: Maximum number of bags
- `mptbm_features`: Serialized array containing all vehicle features
