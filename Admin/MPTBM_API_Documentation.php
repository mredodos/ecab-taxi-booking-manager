<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPTBM_API_Documentation')) {
    class MPTBM_API_Documentation {
        public function __construct() {
            add_action('admin_menu', array($this, 'add_api_documentation_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_documentation_assets'));
        }
		

        public function add_api_documentation_menu() {
            add_submenu_page(
                'edit.php?post_type=mptbm_rent',
                'API Documentation',
                'API Documentation',
                'manage_options',
                'mptbm-api-documentation',
                array($this, 'render_documentation_page')
            );
        }

        public function enqueue_documentation_assets($hook) {
            if ($hook !== 'mptbm_rent_page_mptbm-api-documentation') {
                return;
            }

            wp_enqueue_style(
                'mptbm-api-documentation',
                MPTBM_PLUGIN_URL . '/assets/admin/css/api-documentation.css',
                array(),
                MPTBM_PLUGIN_VERSION
            );

            wp_enqueue_script(
                'mptbm-api-documentation',
                MPTBM_PLUGIN_URL . '/assets/admin/js/api-documentation.js',
                array('jquery'),
                MPTBM_PLUGIN_VERSION,
                true
            );
        }

        public function render_documentation_page() {
            $rest_url = rest_url('mptbm/v1/');
            ?>
            <div class="wrap mptbm-api-docs">
                <h1>E-Cab Taxi Booking Manager REST API Documentation</h1>
                
                <div class="mptbm-api-intro">
                    <p>
                        Welcome to the E-Cab Taxi Booking Manager REST API documentation. This API allows you to integrate
                        our taxi booking system with your applications. All endpoints are accessible via HTTPS at:
                        <code><?php echo esc_html($rest_url); ?></code>
                    </p>
                    
                    <div class="notice notice-success" style="margin: 20px 0; padding: 15px;">
                        <h3 style="margin-top: 0;">✅ Recent API Improvements</h3>
                        <ul style="margin-left: 20px;">
                            <li><strong>Authentication Fixed:</strong> Both Application Passwords and Custom API Keys now work correctly</li>
                            <li><strong>Orders Endpoint Enhanced:</strong> No more duplicates, added pagination and filtering options</li>
                            <li><strong>Endpoint Consistency:</strong> All endpoints use proper naming (e.g., <code>/bookings/{id}</code> not <code>/booking/{id}</code>)</li>
                            <li><strong>New Features:</strong> API key generation, better error handling, rate limiting</li>
                        </ul>
                        <p><strong>Important:</strong> Use <code>/bookings/{id}</code> (plural) for individual booking access, not <code>/booking/{id}</code>.</p>
                    </div>
                </div>

                <div class="mptbm-api-settings">
                    <h2>API Settings</h2>
                    <p>
                        The REST API can be configured in the <a href="<?php echo esc_url(admin_url('edit.php?post_type=mptbm_rent&page=mptbm_settings_page')); ?>">Global Settings</a> page. 
                        Current settings:
                    </p>
                    <?php 
                    $api_enabled = MP_Global_Function::get_settings('mp_global_settings', 'enable_rest_api', 'on');
                    $auth_type = MP_Global_Function::get_settings('mp_global_settings', 'api_authentication_type', 'application_password');
                    $rate_limit = (int) MP_Global_Function::get_settings('mp_global_settings', 'api_rate_limit', 60);
                    ?>
                    <ul class="mptbm-api-settings-list">
                        <li>
                            <strong>API Status:</strong> 
                            <span class="<?php echo $api_enabled === 'on' ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo $api_enabled === 'on' ? 'Enabled' : 'Disabled'; ?>
                            </span>
                            <?php if ($api_enabled !== 'on'): ?>
                                <p class="api-status-note">
                                    <em>Note: Enable the API in E-Cab Settings to access the endpoints.</em>
                                </p>
                            <?php endif; ?>
                        </li>
                        <li>
                            <strong>Authentication Type:</strong> 
                            <?php 
                            switch($auth_type) {
                                case 'none':
                                    echo 'No Authentication Required';
                                    break;
                                case 'application_password':
                                    echo 'Application Passwords (Recommended)';
                                    break;
                                case 'custom_api_key':
                                    echo 'Custom API Key';
                                    break;
                                case 'jwt':
                                    echo 'JWT Authentication';
                                    break;
                                default:
                                    echo ucfirst(str_replace('_', ' ', $auth_type));
                            }
                            ?>
                        </li>
                        <li>
                            <strong>Rate Limit:</strong> 
                            <?php echo $rate_limit === 0 ? 'Unlimited' : $rate_limit . ' requests per minute'; ?>
                        </li>
                    </ul>
                </div>

                <div class="mptbm-api-authentication">
                    <h2>Authentication</h2>
                    <?php if ($auth_type === 'none'): ?>
                        <p>Authentication is currently disabled. All endpoints are publicly accessible.</p>
                    <?php elseif ($auth_type === 'application_password'): ?>
                        <p>
                            This API uses WordPress Application Passwords for authentication. To use the API:
                        </p>
                        <ol>
                            <li>Go to your WordPress profile page</li>
                            <li>Scroll down to the "Application Passwords" section</li>
                            <li>Create a new application password for this API</li>
                            <li>Use the generated password in the Authorization header:</li>
                        </ol>
                        <pre><code>Authorization: Basic base64_encode(username:application_password)</code></pre>
                        <p>
                            For more details, see the <a href="https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#application-passwords" target="_blank">WordPress REST API Authentication Documentation</a>.
                        </p>
                    <?php elseif ($auth_type === 'custom_api_key'): ?>
                        <p>
                            This API uses Custom API Key for authentication. To use the API:
                        </p>
                        <ol>
                            <li>Generate or set a custom API key in the Global Settings</li>
                            <li>Include the API key in your requests using one of these methods:</li>
                        </ol>
                        <p><strong>Method 1: X-API-Key Header</strong></p>
                        <pre><code>X-API-Key: your-custom-api-key</code></pre>
                        <p><strong>Method 2: Authorization Header</strong></p>
                        <pre><code>Authorization: Bearer your-custom-api-key</code></pre>
                        <p>
                            <strong>Generate API Key:</strong> You can generate a secure API key by making a POST request to:
                            <code>/wp-json/mptbm/v1/generate-api-key</code> (requires admin authentication)
                        </p>
                    <?php elseif ($auth_type === 'jwt'): ?>
                        <p>JWT Authentication is currently not implemented.</p>
                    <?php endif; ?>
                </div>

                <?php if ($rate_limit > 0): ?>
                <div class="mptbm-api-rate-limit">
                    <h2>Rate Limiting</h2>
                    <p>
                        This API is rate-limited to <?php echo esc_html($rate_limit); ?> requests per minute per IP address. 
                        If you exceed this limit, you'll receive a 429 (Too Many Requests) response.
                    </p>
                    <p>
                        Note: Rate-limit headers are not included. For listing endpoints that support pagination (e.g., <code>/orders</code>), the following pagination headers are provided:
                    </p>
                    <ul>
                        <li><code>X-WP-Total</code>: The total number of matching items</li>
                        <li><code>X-WP-TotalPages</code>: Total number of pages for the current <code>per_page</code> value</li>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="mptbm-api-endpoints">
                    <h2>Available Endpoints</h2>

                    <!-- Transport Services Endpoint -->
                    <div class="endpoint-section">
                        <h3>List Transport Services</h3>
                        <div class="endpoint-details">
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/rents</code></p>
                            <p><strong>Description:</strong> Retrieves a list of available transport services.</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>price_based</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Filter services by pricing type (dynamic, manual, fixed_hourly)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "id": 123,
    "title": "Luxury Sedan",
    "price_based": "dynamic",
    "initial_price": 10.00,
    "min_price": 25.00,
    "hour_price": 30.00,
    "km_price": 2.50,
    "max_passenger": 4,
    "max_bag": 3,
    "schedule": {
        "monday": ["09:00", "17:00"],
        "tuesday": ["09:00", "17:00"]
    }
}
                                </pre>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Endpoints -->
                    <div class="endpoint-section">
                        <h3>Manage Bookings</h3>
                        
                        <!-- Get All Bookings -->
                        <div class="endpoint-details">
                            <h4>Get All Bookings</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/bookings</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
[
    {
        "id": 123,
        "status": "publish",
        "date_created": "2023-05-10T14:30:45",
        "customer_id": "456",
        "transport_id": "789",
        "pickup_location": "Downtown",
        "dropoff_location": "Airport",
        "journey_date": "2023-05-15",
        "total_price": "75.00"
    }
]
                                </pre>
                            </div>
                        </div>
                        
                        <!-- Create Booking -->
                        <div class="endpoint-details">
                            <h4>Create Booking</h4>
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/bookings</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>transport_id</td>
                                            <td>integer</td>
                                            <td>Yes</td>
                                            <td>ID of the transport service</td>
                                        </tr>
                                        <tr>
                                            <td>start_location</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Pickup location</td>
                                        </tr>
                                        <tr>
                                            <td>end_location</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Drop-off location</td>
                                        </tr>
                                        <tr>
                                            <td>booking_date</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Date of booking (YYYY-MM-DD format)</td>
                                        </tr>
                                        <tr>
                                            <td>booking_time</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Time of booking (HH:MM format)</td>
                                        </tr>
                                        <tr>
                                            <td>customer_name</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Customer's name</td>
                                        </tr>
                                        <tr>
                                            <td>customer_email</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Customer's email address</td>
                                        </tr>
                                        <tr>
                                            <td>customer_phone</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Customer's phone number</td>
                                        </tr>
                                        <tr>
                                            <td>return</td>
                                            <td>boolean</td>
                                            <td>No</td>
                                            <td>Whether return journey is required</td>
                                        </tr>
                                        <tr>
                                            <td>return_date</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Return date (YYYY-MM-DD format)</td>
                                        </tr>
                                        <tr>
                                            <td>return_time</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Return time (HH:MM format)</td>
                                        </tr>
                                        <tr>
                                            <td>passengers</td>
                                            <td>integer</td>
                                            <td>No</td>
                                            <td>Number of passengers</td>
                                        </tr>
                                        <tr>
                                            <td>bags</td>
                                            <td>integer</td>
                                            <td>No</td>
                                            <td>Number of bags</td>
                                        </tr>
                                        <tr>
                                            <td>extra_services</td>
                                            <td>array</td>
                                            <td>No</td>
                                            <td>IDs of extra services</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "id": 123,
    "title": "Booking: Downtown to Airport on 2023-05-15",
    "status": "publish",
    "date_created": "2023-05-10T14:30:45",
    "pickup_location": "Downtown",
    "dropoff_location": "Airport",
    "journey_date": "2023-05-15",
    "journey_time": "14:00",
    "return": false,
    "passengers": 2,
    "bags": 1,
    "total_price": "75.00",
    "currency": "$"
}
                                </pre>
                            </div>
                        </div>

                        <!-- Get Booking -->
                        <div class="endpoint-details">
                            <h4>Get Booking Details</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/bookings/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "id": 123,
    "status": "publish",
    "date_created": "2023-05-10T14:30:45",
    "customer_id": "456",
    "transport_id": "789",
    "pickup_location": "Downtown",
    "dropoff_location": "Airport",
    "journey_date": "2023-05-15",
    "journey_time": "14:00",
    "total_price": "75.00",
    "extra_services": [],
    "return": false
}
                                </pre>
                            </div>
                        </div>

                        <!-- Update Booking -->
                        <div class="endpoint-details">
                            <h4>Update Booking</h4>
                            <p><strong>Endpoint:</strong> <code>PUT /wp-json/mptbm/v1/bookings/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <p>Same parameters as Create Booking. All parameters are optional for update.</p>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <p>Same as Create Booking response</p>
                            </div>
                        </div>

                        <!-- Delete Booking -->
                        <div class="endpoint-details">
                            <h4>Delete Booking</h4>
                            <p><strong>Endpoint:</strong> <code>DELETE /wp-json/mptbm/v1/bookings/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "message": "Booking deleted successfully",
    "id": 123
}
                                </pre>
                            </div>
                        </div>

                        <!-- Calculate Price -->
                        <div class="endpoint-details">
                            <h4>Calculate Price</h4>
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/calculate-price</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>transport_id</td>
                                            <td>integer</td>
                                            <td>Yes</td>
                                            <td>ID of the transport service</td>
                                        </tr>
                                        <tr>
                                            <td>start_location</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Pickup location</td>
                                        </tr>
                                        <tr>
                                            <td>end_location</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Drop-off location</td>
                                        </tr>
                                        <tr>
                                            <td>booking_date</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Date of booking (YYYY-MM-DD format)</td>
                                        </tr>
                                        <tr>
                                            <td>return</td>
                                            <td>boolean</td>
                                            <td>No</td>
                                            <td>Whether return journey is required</td>
                                        </tr>
                                        <tr>
                                            <td>extra_services</td>
                                            <td>array</td>
                                            <td>No</td>
                                            <td>IDs of extra services</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "base_price": 65.00,
    "extra_price": 10.00,
    "total_price": 75.00,
    "currency": "$"
}
                                </pre>
                            </div>
                        </div>

                        <!-- Create Order -->
                        <div class="endpoint-details">
                            <h4>Create Order</h4>
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/orders</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>booking_id</td>
                                            <td>integer</td>
                                            <td>Yes</td>
                                            <td>ID of the booking to create an order for</td>
                                        </tr>
                                        <tr>
                                            <td>payment_method</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Payment method (direct_order or woocommerce)</td>
                                        </tr>
                                        <tr>
                                            <td>customer_id</td>
                                            <td>integer</td>
                                            <td>No</td>
                                            <td>Customer user ID (if available)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="example-response">
                                <h4>Example Response (Direct Order)</h4>
                                <pre>
{
    "message": "Order created successfully",
    "order_id": 456,
    "payment_method": "direct_order"
}
                                </pre>
                                <h4>Example Response (WooCommerce)</h4>
                                <pre>
{
    "message": "WooCommerce order created successfully",
    "order_id": 789,
    "payment_method": "woocommerce",
    "payment_url": "https://yoursite.com/checkout/?order_id=789"
}
                                </pre>
                            </div>
                        </div>

                        <!-- Update Order -->
                        <div class="endpoint-details">
                            <h4>Update Order</h4>
                            <p><strong>Endpoint:</strong> <code>PUT /wp-json/mptbm/v1/orders/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>status</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Order status (pending, processing, completed, etc.)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "message": "Order updated successfully",
    "order_id": 456,
    "payment_method": "direct_order"
}
                                </pre>
                            </div>
                        </div>

                        <!-- Create Customer -->
                        <div class="endpoint-details">
                            <h4>Create Customer</h4>
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/customers</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>name</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Customer's name</td>
                                        </tr>
                                        <tr>
                                            <td>email</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Customer's email address</td>
                                        </tr>
                                        <tr>
                                            <td>phone</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Customer's phone number</td>
                                        </tr>
                                        <tr>
                                            <td>address</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Customer's address</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "id": 42,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "phone": "123-456-7890",
    "address": "123 Main St, Anytown, USA"
}
                                </pre>
                            </div>
                        </div>

                        <!-- Update Customer -->
                        <div class="endpoint-details">
                            <h4>Update Customer</h4>
                            <p><strong>Endpoint:</strong> <code>PUT /wp-json/mptbm/v1/customers/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <p>Same parameters as Create Customer. All parameters are optional for update.</p>
                            </div>

                            <div class="example-response">
                                <h4>Example Response</h4>
                                <p>Same as Create Customer response</p>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Endpoint -->
                    <div class="endpoint-section">
                        <h3>Manage Orders (WooCommerce Integration)</h3>
                        
                        <!-- Get All Orders -->
                        <div class="endpoint-details">
                            <h4>Get All Orders (Enhanced with Filtering)</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/orders</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            <p><strong>Note:</strong> Requires WooCommerce to be active. Fixed to prevent duplicates and includes advanced filtering.</p>
                            
                            <div class="parameters">
                                <h4>Query Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Default</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>per_page</td>
                                            <td>integer</td>
                                            <td>20</td>
                                            <td>Number of orders per page (max 100)</td>
                                        </tr>
                                        <tr>
                                            <td>offset</td>
                                            <td>integer</td>
                                            <td>0</td>
                                            <td>Pagination offset</td>
                                        </tr>
                                        <tr>
                                            <td>status</td>
                                            <td>string</td>
                                            <td>any</td>
                                            <td>Filter by order status (pending, processing, completed, any)</td>
                                        </tr>
                                        <tr>
                                            <td>orderby</td>
                                            <td>string</td>
                                            <td>date</td>
                                            <td>Sort field (date, id, total)</td>
                                        </tr>
                                        <tr>
                                            <td>order</td>
                                            <td>string</td>
                                            <td>DESC</td>
                                            <td>Sort direction (ASC, DESC)</td>
                                        </tr>
                                        <tr>
                                            <td>customer_id</td>
                                            <td>integer</td>
                                            <td>-</td>
                                            <td>Filter by customer ID</td>
                                        </tr>
                                        <tr>
                                            <td>start_date</td>
                                            <td>string</td>
                                            <td>-</td>
                                            <td>Filter by start date (YYYY-MM-DD)</td>
                                        </tr>
                                        <tr>
                                            <td>end_date</td>
                                            <td>string</td>
                                            <td>-</td>
                                            <td>Filter by end date (YYYY-MM-DD)</td>
                                        </tr>
                                        <tr>
                                            <td>taxi_orders_only</td>
                                            <td>string</td>
                                            <td>-</td>
                                            <td>Set to "true" to only include taxi booking related orders</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="example-response">
                                <h4>Example Usage</h4>
                                <pre><code>GET /wp-json/mptbm/v1/orders?per_page=10&orderby=date&order=DESC&taxi_orders_only=true</code></pre>
                                
                                <h4>Example Response</h4>
                                <pre>
[
    {
        "id": 789,
        "status": "completed",
        "date_created": "2023-05-15 10:30:45",
        "total": "75.00",
        "customer_id": 456,
        "payment_method": "stripe",
        "payment_method_title": "Credit Card",
        "is_taxi_order": true,
        "taxi_booking_id": 123,
        "booking_details": {
            "pickup_location": "Downtown",
            "dropoff_location": "Airport",
            "journey_date": "2023-05-15",
            "journey_time": "14:00"
        }
    }
]
                                </pre>
                                
                                <h4>Response Headers</h4>
                                <ul>
                                    <li><code>X-WP-Total</code>: Total number of matching orders</li>
                                    <li><code>X-WP-TotalPages</code>: Total pages for current per_page value</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Get Order Details -->
                        <div class="endpoint-details">
                            <h4>Get Order Details</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/orders/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "id": 789,
    "status": "completed",
    "date_created": "2023-05-15 10:30:45",
    "total": "75.00",
    "customer_id": 456,
    "payment_method": "stripe",
    "payment_method_title": "Credit Card",
    "items": [
        {
            "product_id": 101,
            "name": "Taxi Booking Service",
            "quantity": 1,
            "total": "75.00",
            "tax": "0.00"
        }
    ]
}
                                </pre>
                            </div>
                        </div>
                    </div>

                    <!-- Customers Endpoint -->
                    <div class="endpoint-section">
                        <h3>Manage Customers</h3>
                        
                        <!-- Get All Customers -->
                        <div class="endpoint-details">
                            <h4>Get All Customers</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/customers</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
[
    {
        "id": 456,
        "username": "johndoe",
        "email": "john@example.com",
        "first_name": "John",
        "last_name": "Doe",
        "date_registered": "2023-01-15 09:30:00"
    }
]
                                </pre>
                            </div>
                        </div>
                        
                        <!-- Get Customer Details -->
                        <div class="endpoint-details">
                            <h4>Get Customer Details</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/customers/{id}</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "id": 456,
    "username": "johndoe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "date_registered": "2023-01-15 09:30:00",
    "billing_address": {
        "first_name": "John",
        "last_name": "Doe",
        "company": "Example Corp",
        "address_1": "123 Main St",
        "address_2": "Suite 100",
        "city": "Anytown",
        "state": "CA",
        "postcode": "12345",
        "country": "US",
        "email": "john@example.com",
        "phone": "+1-555-123-4567"
    }
}
                                </pre>
                            </div>
                        </div>
                        
                        <!-- Get Customer Bookings -->
                        <div class="endpoint-details">
                            <h4>Get Customer's Bookings</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/customers/{id}/bookings</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
[
    {
        "id": 123,
        "status": "publish",
        "date_created": "2023-05-10T14:30:45",
        "customer_id": "456",
        "transport_id": "789",
        "pickup_location": "Downtown",
        "dropoff_location": "Airport",
        "journey_date": "2023-05-15",
        "total_price": "75.00"
    }
]
                                </pre>
                            </div>
                        </div>
                    </div>

                    <!-- API Management Endpoints -->
                    <div class="endpoint-section">
                        <h3>API Management (Admin Only)</h3>
                        
                        <!-- Generate API Key -->
                        <div class="endpoint-details">
                            <h4>Generate API Key</h4>
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/generate-api-key</code></p>
                            <p><strong>Authentication Required:</strong> Admin only</p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>save_immediately</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Set to "true" to save the key automatically</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "message": "New API key generated successfully",
    "api_key": "abc123def456ghi789jkl012mno345pq",
    "saved": false,
    "note": "Save this key securely. You can use it in your API requests or save it via the settings endpoint."
}
                                </pre>
                            </div>
                        </div>
                        
                        <!-- Get API Settings -->
                        <div class="endpoint-details">
                            <h4>Get API Settings</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/settings</code></p>
                            <p><strong>Authentication Required:</strong> Admin only</p>
                            
                            <div class="example-response">
                                <h4>Example Response</h4>
                                <pre>
{
    "api_enabled": "on",
    "api_authentication_type": "custom_api_key",
    "api_custom_key": "abc123def456ghi789jkl012mno345pq",
    "api_rate_limit": 60
}
                                </pre>
                            </div>
                        </div>
                    </div>

                    <!-- Quote Endpoint -->
                    <div class="endpoint-section">
                        <h3>Get Fare Quote</h3>
                        <div class="endpoint-details">
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/quote</code></p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>transport_id</td>
                                            <td>integer</td>
                                            <td>Yes</td>
                                            <td>ID of the transport service</td>
                                        </tr>
                                        <tr>
                                            <td>distance</td>
                                            <td>number</td>
                                            <td>No</td>
                                            <td>Distance in meters (default: 1000)</td>
                                        </tr>
                                        <tr>
                                            <td>duration</td>
                                            <td>number</td>
                                            <td>No</td>
                                            <td>Duration in seconds (default: 3600)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Locations Endpoint -->
                    <div class="endpoint-section">
                        <h3>Locations</h3>
                        <div class="endpoint-details">
                            <h4>Get Locations</h4>
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/locations</code></p>
                            
                            <div class="parameters">
                                <h4>Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>transport_id</td>
                                            <td>integer</td>
                                            <td>No</td>
                                            <td>Filter locations by transport service</td>
                                        </tr>
                                        <tr>
                                            <td>start_place</td>
                                            <td>string</td>
                                            <td>No</td>
                                            <td>Get available end locations for a start location</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="endpoint-details">
                            <h4>Add/Update Location Price</h4>
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/locations</code></p>
                            <p><strong>Authentication Required:</strong> Yes</p>
                            <div class="parameters">
                                <h4>Body Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>transport_id</td>
                                            <td>integer</td>
                                            <td>Yes</td>
                                            <td>ID of the transport service (manual pricing)</td>
                                        </tr>
                                        <tr>
                                            <td>start_location</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>Start location name</td>
                                        </tr>
                                        <tr>
                                            <td>end_location</td>
                                            <td>string</td>
                                            <td>Yes</td>
                                            <td>End location name</td>
                                        </tr>
                                        <tr>
                                            <td>price</td>
                                            <td>number</td>
                                            <td>Yes</td>
                                            <td>Price for this route</td>
                                        </tr>
                                        <tr>
                                            <td>return_enabled</td>
                                            <td>boolean</td>
                                            <td>No</td>
                                            <td>Whether return is enabled for this route</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Transports Endpoint -->
                    <div class="endpoint-section">
                        <h3>Filter Transports</h3>
                        <div class="endpoint-details">
                            <p><strong>Endpoint:</strong> <code>POST /wp-json/mptbm/v1/filter</code></p>
                            <div class="parameters">
                                <h4>Body Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>price_based</td><td>string</td><td>No</td><td>Filter by price type (dynamic, manual, fixed_hourly)</td></tr>
                                        <tr><td>passengers</td><td>integer</td><td>No</td><td>Minimum passenger capacity</td></tr>
                                        <tr><td>bags</td><td>integer</td><td>No</td><td>Minimum baggage capacity</td></tr>
                                        <tr><td>start_location</td><td>string</td><td>No</td><td>Start location for route filtering</td></tr>
                                        <tr><td>end_location</td><td>string</td><td>No</td><td>End location for route filtering</td></tr>
                                        <tr><td>booking_date</td><td>string</td><td>No</td><td>Booking date (YYYY-MM-DD)</td></tr>
                                        <tr><td>return</td><td>boolean</td><td>No</td><td>Whether return journey is required</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Extra Services Endpoint -->
                    <div class="endpoint-section">
                        <h3>Extra Services</h3>
                        <div class="endpoint-details">
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/extra-services</code></p>
                            <div class="parameters">
                                <h4>Query Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>transport_id</td><td>integer</td><td>Yes</td><td>ID of the transport service</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Endpoint -->
                    <div class="endpoint-section">
                        <h3>Statistics</h3>
                        <div class="endpoint-details">
                            <p><strong>Endpoint:</strong> <code>GET /wp-json/mptbm/v1/statistics</code></p>
                            <div class="parameters">
                                <h4>Query Parameters</h4>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>period</td><td>string</td><td>No</td><td>Preset range: week, month, year (default: month)</td></tr>
                                        <tr><td>date_from</td><td>string</td><td>No</td><td>Start date (YYYY-MM-DD)</td></tr>
                                        <tr><td>date_to</td><td>string</td><td>No</td><td>End date (YYYY-MM-DD)</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    new MPTBM_API_Documentation();
}
