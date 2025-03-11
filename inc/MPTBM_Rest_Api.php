<?php
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly.

if (!class_exists('MPTBM_Rest_Api')) {
    class MPTBM_Rest_Api {
        private $namespace = 'mptbm/v1';
        private $rate_limit_transient_prefix = 'mptbm_api_rate_limit_';
        private $rate_limit_window = 60;

        public function __construct() {
            if ($this->is_api_enabled()) {
                add_action('rest_api_init', array($this, 'register_routes'));
            }
        }
		

        private function is_api_enabled() {
            return MP_Global_Function::get_settings('mp_global_settings', 'enable_rest_api', 'off') === 'on';
        }

        public function register_routes() {
            add_filter('rest_pre_dispatch', array($this, 'check_request_permissions'), 10, 3);

            // Transport Services
            register_rest_route($this->namespace, '/rents', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_transport_services'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            // Bookings
            register_rest_route($this->namespace, '/bookings', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_all_bookings'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            register_rest_route($this->namespace, '/bookings/(?P<id>\d+)', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_booking_details'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            // Orders
            register_rest_route($this->namespace, '/orders', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_orders'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            register_rest_route($this->namespace, '/orders/(?P<id>\d+)', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_order_details'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            // Customers
            register_rest_route($this->namespace, '/customers', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_customers'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            register_rest_route($this->namespace, '/customers/(?P<id>\d+)', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_customer_details'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));

            register_rest_route($this->namespace, '/customers/(?P<id>\d+)/bookings', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_customer_bookings'),
                    'permission_callback' => array($this, 'check_booking_read_permission'),
                )
            ));
        }

        private function check_rate_limit($request) {
            $rate_limit = (int) MP_Global_Function::get_settings('mp_global_settings', 'api_rate_limit', 60);
            
            if ($rate_limit === 0) {
                return true;
            }

            $client_ip = $request->get_header('X-Real-IP');
            if (empty($client_ip)) {
                $client_ip = $request->get_header('X-Forwarded-For');
            }
            if (empty($client_ip)) {
                $client_ip = $_SERVER['REMOTE_ADDR'];
            }

            $transient_key = $this->rate_limit_transient_prefix . md5($client_ip);
            $current_count = (int) get_transient($transient_key);

            if ($current_count >= $rate_limit) {
                return new WP_Error(
                    'rest_rate_limit_exceeded',
                    sprintf(
                        esc_html__('Rate limit of %d requests per minute exceeded.', 'ecab-taxi-booking-manager'),
                        $rate_limit
                    ),
                    array('status' => 429)
                );
            }

            if ($current_count === 0) {
                set_transient($transient_key, 1, $this->rate_limit_window);
            } else {
                set_transient($transient_key, $current_count + 1, $this->rate_limit_window);
            }

            return true;
        }

        public function check_request_permissions($response, $handler, $request) {
            if (strpos($request->get_route(), '/mptbm/v1') === false) {
                return $response;
            }

            if (!$this->is_api_enabled()) {
                return new WP_Error(
                    'rest_disabled',
                    esc_html__('The E-Cab REST API is disabled.', 'ecab-taxi-booking-manager'),
                    array('status' => 403)
                );
            }

            $rate_limit_check = $this->check_rate_limit($request);
            if (is_wp_error($rate_limit_check)) {
                return $rate_limit_check;
            }

            return $response;
        }

        public function check_booking_read_permission($request) {
            $auth_type = MP_Global_Function::get_settings('mp_global_settings', 'api_authentication_type', 'application_password');
            return $auth_type === 'none' ? true : current_user_can('read');
        }

        public function get_transport_services($request) {
            $args = array(
                'post_type' => 'mptbm_rent',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            );

            $posts = get_posts($args);
            $data = array();

            foreach ($posts as $post) {
                $data[] = $this->prepare_transport_service($post);
            }

            return new WP_REST_Response($data, 200);
        }

        public function get_all_bookings($request) {
            $args = array(
                'post_type' => 'mptbm_booking',
                'posts_per_page' => -1,
                'post_status' => 'any'
            );

            $posts = get_posts($args);
            $data = array();

            foreach ($posts as $post) {
                $data[] = $this->prepare_booking_data($post);
            }

            return new WP_REST_Response($data, 200);
        }

        public function get_booking_details($request) {
            $booking_id = $request['id'];
            $booking = get_post($booking_id);

            if (!$booking || $booking->post_type !== 'mptbm_booking') {
                return new WP_Error(
                    'booking_not_found',
                    esc_html__('Booking not found', 'ecab-taxi-booking-manager'),
                    array('status' => 404)
                );
            }

            $data = $this->prepare_booking_data($booking);
            
            // Add extra booking details
            $data['extra_services'] = get_post_meta($booking_id, 'mptbm_extra_services', true);
            $data['pickup_location'] = get_post_meta($booking_id, 'mptbm_pickup_location', true);
            $data['dropoff_location'] = get_post_meta($booking_id, 'mptbm_dropoff_location', true);
            $data['journey_date'] = get_post_meta($booking_id, 'mptbm_journey_date', true);
            $data['journey_time'] = get_post_meta($booking_id, 'mptbm_journey_time', true);
            $data['total_price'] = get_post_meta($booking_id, 'mptbm_total_price', true);

            return new WP_REST_Response($data, 200);
        }

        public function get_orders($request) {
            if (!class_exists('WooCommerce')) {
                return new WP_Error(
                    'woocommerce_required',
                    esc_html__('WooCommerce is required for order information', 'ecab-taxi-booking-manager'),
                    array('status' => 400)
                );
            }

            $orders = wc_get_orders(array(
                'limit' => -1,
                'type' => 'shop_order',
            ));

            $data = array();
            foreach ($orders as $order) {
                $data[] = $this->prepare_order_data($order);
            }

            return new WP_REST_Response($data, 200);
        }

        public function get_order_details($request) {
            if (!class_exists('WooCommerce')) {
                return new WP_Error(
                    'woocommerce_required',
                    esc_html__('WooCommerce is required for order information', 'ecab-taxi-booking-manager'),
                    array('status' => 400)
                );
            }

            $order_id = $request['id'];
            $order = wc_get_order($order_id);

            if (!$order) {
                return new WP_Error(
                    'order_not_found',
                    esc_html__('Order not found', 'ecab-taxi-booking-manager'),
                    array('status' => 404)
                );
            }

            $data = $this->prepare_order_data($order);
            
            // Add detailed order information
            $data['items'] = array();
            foreach ($order->get_items() as $item) {
                $data['items'][] = array(
                    'product_id' => $item->get_product_id(),
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total(),
                    'tax' => $item->get_total_tax()
                );
            }

            return new WP_REST_Response($data, 200);
        }

        public function get_customers($request) {
            $args = array(
                'role' => 'customer',
                'orderby' => 'registered',
                'order' => 'DESC'
            );

            $customers = get_users($args);
            $data = array();

            foreach ($customers as $customer) {
                $data[] = $this->prepare_customer_data($customer);
            }

            return new WP_REST_Response($data, 200);
        }

        public function get_customer_details($request) {
            $customer_id = $request['id'];
            $customer = get_user_by('id', $customer_id);

            if (!$customer) {
                return new WP_Error(
                    'customer_not_found',
                    esc_html__('Customer not found', 'ecab-taxi-booking-manager'),
                    array('status' => 404)
                );
            }

            $data = $this->prepare_customer_data($customer);
            
            // Add extra customer details
            if (class_exists('WooCommerce')) {
                $data['billing_address'] = array(
                    'first_name' => get_user_meta($customer_id, 'billing_first_name', true),
                    'last_name' => get_user_meta($customer_id, 'billing_last_name', true),
                    'company' => get_user_meta($customer_id, 'billing_company', true),
                    'address_1' => get_user_meta($customer_id, 'billing_address_1', true),
                    'address_2' => get_user_meta($customer_id, 'billing_address_2', true),
                    'city' => get_user_meta($customer_id, 'billing_city', true),
                    'state' => get_user_meta($customer_id, 'billing_state', true),
                    'postcode' => get_user_meta($customer_id, 'billing_postcode', true),
                    'country' => get_user_meta($customer_id, 'billing_country', true),
                    'email' => get_user_meta($customer_id, 'billing_email', true),
                    'phone' => get_user_meta($customer_id, 'billing_phone', true)
                );
            }

            return new WP_REST_Response($data, 200);
        }

        public function get_customer_bookings($request) {
            $customer_id = $request['id'];
            
            $args = array(
                'post_type' => 'mptbm_booking',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_customer_user',
                        'value' => $customer_id,
                        'compare' => '='
                    )
                )
            );

            $bookings = get_posts($args);
            $data = array();

            foreach ($bookings as $booking) {
                $data[] = $this->prepare_booking_data($booking);
            }

            return new WP_REST_Response($data, 200);
        }

        private function prepare_booking_data($booking) {
            return array(
                'id' => $booking->ID,
                'status' => $booking->post_status,
                'date_created' => $booking->post_date,
                'customer_id' => get_post_meta($booking->ID, '_customer_user', true),
                'transport_id' => get_post_meta($booking->ID, 'mptbm_transport_id', true),
                'pickup_location' => get_post_meta($booking->ID, 'mptbm_pickup_location', true),
                'dropoff_location' => get_post_meta($booking->ID, 'mptbm_dropoff_location', true),
                'journey_date' => get_post_meta($booking->ID, 'mptbm_journey_date', true),
                'total_price' => get_post_meta($booking->ID, 'mptbm_total_price', true)
            );
        }

        private function prepare_order_data($order) {
            return array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'total' => $order->get_total(),
                'customer_id' => $order->get_customer_id(),
                'payment_method' => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title()
            );
        }

        private function prepare_customer_data($customer) {
            return array(
                'id' => $customer->ID,
                'username' => $customer->user_login,
                'email' => $customer->user_email,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'date_registered' => $customer->user_registered
            );
        }

        private function prepare_transport_service($post) {
            $price_based = get_post_meta($post->ID, 'mptbm_price_based', true);
            $data = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'price_based' => $price_based,
                'initial_price' => get_post_meta($post->ID, 'mptbm_initial_price', true),
                'min_price' => get_post_meta($post->ID, 'mptbm_min_price', true),
                'hour_price' => get_post_meta($post->ID, 'mptbm_hour_price', true),
                'km_price' => get_post_meta($post->ID, 'mptbm_km_price', true),
                'max_passenger' => get_post_meta($post->ID, 'mptbm_max_passenger', true),
                'max_bag' => get_post_meta($post->ID, 'mptbm_max_bag', true),
                'schedule' => get_post_meta($post->ID, 'mptbm_schedule', true)
            );

            return $data;
        }
    }

    new MPTBM_Rest_Api();
}
