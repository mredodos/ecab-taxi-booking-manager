<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	/**
	 * Class MPTBM_Wc_Checkout_Fields_Helper
	 *
	 * @since 1.0
	 *
	 * */
	if (!class_exists('MPTBM_Wc_Checkout_Fields_Helper')) {
		class MPTBM_Wc_Checkout_Fields_Helper {
			private $error;
			public static $settings_options;
			public static $default_woocommerce_checkout_fields;
			public static $default_woocommerce_checkout_required_fields;
			public static $default_app_required_fields;
			private $allowed_extensions;
			private $allowed_mime_types;
		public function __construct() {
			$this->error = new WP_Error();
			$this->init();
			
			// Handle section visibility using proper WooCommerce hooks (after init)
			$this->handle_section_visibility();
				
				// Check if custom checkout system is disabled
				if (self::disable_custom_checkout_system()) {
					// If disabled, don't add any filters or actions that modify checkout
				// BUT still allow custom fields to be added
				add_action('woocommerce_after_checkout_billing_form', function() { $this->output_file_fields_for_section('billing'); });
				add_action('woocommerce_after_checkout_shipping_form', function() { $this->output_file_fields_for_section('shipping'); });
				add_action('woocommerce_after_checkout_order_form', function() { $this->output_file_fields_for_section('order'); });
					return;
				}
				
			// Use WooCommerce's specific filters instead of woocommerce_checkout_fields
			// This follows WooCommerce best practices and doesn't interfere with other plugins
			add_filter('woocommerce_billing_fields', array($this, 'modify_billing_fields'), 10, 1);
			add_filter('woocommerce_shipping_fields', array($this, 'modify_shipping_fields'), 10, 1);
			add_filter('woocommerce_checkout_fields', array($this, 'modify_order_fields'), 10, 1);
			
			// Add filter for header field type
			add_filter('woocommerce_form_field_header', array($this, 'header_field_element'), 10, 4);
				
				// Render file fields after WooCommerce fields in each section
				add_action('woocommerce_after_checkout_billing_form', function() { $this->output_file_fields_for_section('billing'); });
				add_action('woocommerce_after_checkout_shipping_form', function() { $this->output_file_fields_for_section('shipping'); });
				add_action('woocommerce_after_checkout_order_form', function() { $this->output_file_fields_for_section('order'); });
				
				// Add CSS for hidden fields
				add_action('wp_head', array($this, 'add_hidden_field_css'));
			
			// Debug info for administrators (uncomment if needed)
			// add_action('woocommerce_before_checkout_form', array($this, 'debug_field_status'));
		}
		
		/**
		 * Handle section visibility using proper WooCommerce hooks
		 */
		private function handle_section_visibility() {
			// Debug: Log the settings options
			if (current_user_can('administrator')) {
				error_log('MPTBM Debug - Settings Options: ' . print_r(self::$settings_options, true));
				error_log('MPTBM Debug - hide_checkout_order_additional_information_section function exists: ' . (method_exists($this, 'hide_checkout_order_additional_information_section') ? 'yes' : 'no'));
			}
			
			// Handle hiding the order review section (only the review, not payment)
			if (self::hide_checkout_order_review_section()) {
				if (current_user_can('administrator')) {
					error_log('MPTBM Debug - Hiding Order Review Section (review only, keeping payment)');
				}
				// Only remove the order review, keep the payment section
				remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
			}
			
		// Handle hiding the additional information section
		$hide_additional_info = self::hide_checkout_order_additional_information_section();
		if (current_user_can('administrator')) {
			error_log('MPTBM Debug - hide_checkout_order_additional_information_section() returned: ' . ($hide_additional_info ? 'true' : 'false'));
			error_log('MPTBM Debug - Settings options array: ' . print_r(self::$settings_options, true));
			if (is_array(self::$settings_options)) {
				error_log('MPTBM Debug - hide_checkout_order_additional_information key exists: ' . (array_key_exists('hide_checkout_order_additional_information', self::$settings_options) ? 'yes' : 'no'));
				if (array_key_exists('hide_checkout_order_additional_information', self::$settings_options)) {
					error_log('MPTBM Debug - hide_checkout_order_additional_information value: ' . self::$settings_options['hide_checkout_order_additional_information']);
				}
			}
		}
		
		if ($hide_additional_info) {
			if (current_user_can('administrator')) {
				error_log('MPTBM Debug - Hiding Additional Information Section using woocommerce_enable_order_notes_field filter');
			}
			// Usa il filtro corretto per nascondere la sezione "Additional Information"
			add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999);
		}
			
		// Check if order comments field is disabled (only disable the field, not the whole section)
		// Solo se "Hide Order Additional Information Section" NON è attivo
		if (!$hide_additional_info) {
			$custom = get_option('mptbm_custom_checkout_fields', array());
			$order_comments_disabled = false;
			if (isset($custom['order']['order_comments']) && isset($custom['order']['order_comments']['disabled']) && $custom['order']['order_comments']['disabled'] == '1') {
				$order_comments_disabled = true;
			}
			
			// If order comments is disabled, only disable the field, not the whole section
			if ($order_comments_disabled) {
				if (current_user_can('administrator')) {
					error_log('MPTBM Debug - Order Comments Disabled, disabling only the field using unset');
				}
				// Usa unset per rimuovere solo il campo, non l'intera sezione
				add_filter('woocommerce_checkout_fields', array($this, 'remove_order_comments_field_only'), 20);
			}
		}
		}
		
		/**
		 * Remove only the order_comments field, not the entire section
		 */
		public function remove_order_comments_field_only($fields) {
			if (isset($fields['order']['order_comments'])) {
				unset($fields['order']['order_comments']);
			}
			return $fields;
		}
		
		public static function woocommerce_default_checkout_fields() {
			// Use a static variable to cache the fields once translations are available
			static $cached_fields = null;
			
			// Check if we need to force cache refresh
			$force_refresh = get_transient('mptbm_force_cache_refresh');
			if ($force_refresh) {
				$cached_fields = null;
				delete_transient('mptbm_force_cache_refresh');
			}
			
			if ($cached_fields === null) {
					$cached_fields = array
					(
						"billing" => array(
							"billing_first_name" => array(
								"label" => __("First name",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-first"
								),
								"autocomplete" => "given-name",
								"priority" => "10",
							),
							"billing_last_name" => array(
								"label" => __("Last name",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-last"
								),
								"autocomplete" => "family-name",
								"priority" => "20",
							),
							"billing_company" => array(
								"label" => __("Company name",'ecab-taxi-booking-manager'),
								"class" => array(
									"0" => "form-row-wide",
								),
								"autocomplete" => "organization",
								"priority" => "30",
								"required" => '',
							),
							"billing_country" => array(
								"type" => "country",
								"label" => __("Country / Region",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field",
									"2" => "update_totals_on_change",
								),
								"autocomplete" => "country",
								"priority" => "40",
							),
							"billing_address_1" => array(
								"label" => __("Street address",'ecab-taxi-booking-manager'),
								"placeholder" => __("House number and street name",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"autocomplete" => "address-line1",
								"priority" => "50"
							),
							"billing_address_2" => array(
								"label" => __("Apartment, suite, unit, etc.",'ecab-taxi-booking-manager'),
								"label_class" => array(
									"0" => "screen-reader-text",
								),
								"placeholder" => __("Apartment, suite, unit, etc. (optional)",'ecab-taxi-booking-manager'),
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"autocomplete" => "address-line2",
								"priority" => "60",
								"required" => "",
							),
							"billing_city" => array(
								"label" => __("Town / City",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field",
								),
								"autocomplete" => "address-level2",
								"priority" => "70",
							),
							"billing_state" => array(
								"type" => "state",
								"label" => __("State / County",'ecab-taxi-booking-manager'),
								"required" => "",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"validate" => array(
									"0" => "state"
								),
								"autocomplete" => "address-level1",
								"priority" => "80",
								"country_field" => "billing_country",
								"country" => "AF"
							),
							"billing_postcode" => array(
								"label" => __("Postcode / ZIP",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"validate" => array(
									"0" => "postcode",
								),
								"autocomplete" => "postal-code",
								"priority" => "90"
							),
							"billing_phone" => array(
								"label" => __("Phone",'ecab-taxi-booking-manager'),
								"required" => "1",
								"type" => "tel",
								"class" => array(
									"0" => "form-row-wide",
								),
								"validate" => array(
									"0" => "phone",
								),
								"autocomplete" => "tel",
								"priority" => "100"
							),
							'billing_email' => array(
								"label" => __("Email address",'ecab-taxi-booking-manager'),
								"required" => "1",
								"type" => "email",
								"class" => array(
									"0" => "form-row-wide",
								),
								"validate" => array(
									"0" => "email",
								),
								"autocomplete" => "email username",
								"priority" => "110",
							)
						),
						'shipping' => array(
							'shipping_first_name' => array(
								"label" => __("First name",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-first",
								),
								"autocomplete" => "given-name",
								"priority" => "10",
							),
							"shipping_last_name" => array(
								"label" => __("Last name",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-last",
								),
								"autocomplete" => "family-name",
								"priority" => "20",
							),
							"shipping_company" => array(
								"label" => __("Company name",'ecab-taxi-booking-manager'),
								"class" => array(
									"0" => "form-row-wide",
								),
								"autocomplete" => "organization",
								"priority" => "30",
								"required" => "",
							),
							"shipping_country" => array(
								"type" => "country",
								"label" => __("Country / Region",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field",
									"2" => "update_totals_on_change",
								),
								"autocomplete" => "country",
								"priority" => "40",
							),
							"shipping_address_1" => array(
								"label" => __("Street address",'ecab-taxi-booking-manager'),
								"placeholder" => __("House number and street name",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"autocomplete" => "address-line1",
								"priority" => "50"
							),
							"shipping_address_2" => array(
								"label" => __("Apartment, suite, unit, etc.",'ecab-taxi-booking-manager'),
								"label_class" => array(
									"0" => "screen-reader-text",
								),
								"placeholder" => __("Apartment, suite, unit, etc. (optional)",'ecab-taxi-booking-manager'),
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"autocomplete" => "address-line2",
								"priority" => "60",
								"required" => "",
							),
							"shipping_city" => array(
								"label" => __("Town / City",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field",
								),
								"autocomplete" => "address-level2",
								"priority" => "70",
							),
							"shipping_state" => array(
								"type" => "state",
								"label" => __("State / County",'ecab-taxi-booking-manager'),
								"required" => "",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"validate" => array(
									"0" => "state"
								),
								"autocomplete" => "address-level1",
								"priority" => "80",
								"country_field" => "shipping_country",
								"country" => "AF",
							),
							"shipping_postcode" => array(
								"label" => __("Postcode / ZIP",'ecab-taxi-booking-manager'),
								"required" => "1",
								"class" => array(
									"0" => "form-row-wide",
									"1" => "address-field"
								),
								"validate" => array(
									"0" => "postcode",
								),
								"autocomplete" => "postal-code",
								"priority" => "90",
							),
						),
						'order' => array(
							'order_comments' => array(
								"type" => "textarea",
								"label" => __("Order notes",'ecab-taxi-booking-manager'),
								"placeholder" => __("Notes about your order, e.g. special notes for delivery.",'ecab-taxi-booking-manager'),
								"class" => array(
									"0" => "notes",
								),
								"priority" => "",
								"required" => "",
							),
						),
					);
				}
				
				return $cached_fields;
			}
			public function init() {
				self::$settings_options = get_option('mptbm_custom_checkout_fields');
				self::$default_woocommerce_checkout_fields = self::woocommerce_default_checkout_fields();
				$this->allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');
				$this->allowed_mime_types = array(
					"jpg|jpeg|jpe" => "image/jpeg",
					"png" => "image/png",
					"pdf" => "application/pdf"
				);

				// Only add checkout-related actions if custom checkout system is not disabled
				if (!self::disable_custom_checkout_system()) {
					// Remove Pro check, always inject fields
					// (No need to add woocommerce_checkout_fields filter here)
					add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_checkout_fields_to_order'), 99, 2);
					add_action('woocommerce_before_order_details', array($this, 'order_details'), 99, 1);
					add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'order_details'), 99, 1);
					add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'order_details'), 99, 1);
				}
			}
			public static function get_checkout_fields_for_list() {
				$fields = array();
				$checkout_fields = self::$default_woocommerce_checkout_fields;
				$fields['billing'] = $checkout_fields['billing'];
				$fields['shipping'] = $checkout_fields['shipping'];
				$fields['order'] = $checkout_fields['order'];
				
				// Get custom fields from admin/pro
				$custom = get_option('mptbm_custom_checkout_fields', array());
				
				// Process custom fields for each section
				foreach (['billing', 'shipping', 'order'] as $section) {
					if (isset($custom[$section]) && is_array($custom[$section])) {
						foreach ($custom[$section] as $name => $field_array) {
							// Skip deleted fields
							if (self::check_deleted_field($section, $name)) {
								unset($fields[$section][$name]);
								continue;
							}
							
							// Add or update field
							$fields[$section][$name] = $field_array;
							
							// Mark as disabled if needed
							if (self::check_disabled_field($section, $name)) {
								$fields[$section][$name]['disabled'] = '1';
							}
						}
					}
				}
				
				return $fields;
			}
			public function get_checkout_fields_for_checkout() {
				$fields = array();
				$checkout_fields = WC()->checkout->get_checkout_fields();
				
				// Initialize sections
				$fields['billing'] = isset($checkout_fields['billing']) ? $checkout_fields['billing'] : array();
				$fields['shipping'] = isset($checkout_fields['shipping']) ? $checkout_fields['shipping'] : array();
				$fields['order'] = isset($checkout_fields['order']) ? $checkout_fields['order'] : array();
				
				// Remove deleted or disabled fields
				foreach ($fields as $key => $section_fields) {
					if (is_array($section_fields)) {
						foreach ($section_fields as $field_key => $field) {
							if (self::check_deleted_field($key, $field_key) || self::check_disabled_field($key, $field_key)) {
								unset($fields[$key][$field_key]);
							}
						}
					}
				}
				
				// Add custom fields from settings
				if (isset(self::$settings_options) && is_array(self::$settings_options)) {
					foreach (self::$settings_options as $key => $section_fields) {
						if (is_array($section_fields)) {
							foreach ($section_fields as $field_key => $field) {
								if (!self::check_deleted_field($key, $field_key) && !self::check_disabled_field($key, $field_key)) {
									$fields[$key][$field_key] = $field;
								}
							}
						}
					}
				}
				
				// Section visibility is now handled in the constructor via handle_section_visibility()
				
				return $fields;
			}
			public static function hide_checkout_order_additional_information_section() {
				if (is_array(self::$settings_options) && array_key_exists('hide_checkout_order_additional_information', self::$settings_options) && self::$settings_options['hide_checkout_order_additional_information'] == 'on') {
					return true;
				}
				return false;
			}
			public static function hide_checkout_order_review_section() {
				if (is_array(self::$settings_options) && array_key_exists('hide_checkout_order_review', self::$settings_options) && self::$settings_options['hide_checkout_order_review'] == 'on') {
					return true;
				}
				return false;
			}
			public static function disable_custom_checkout_system() {
				if ((is_array(self::$settings_options) && ((array_key_exists('disable_custom_checkout_system', self::$settings_options) && self::$settings_options['disable_custom_checkout_system'] == 'on')))) {
					return true;
				}
				return false;
			}
			public static function check_deleted_field($key, $name) {
				if ((isset(self::$settings_options[$key][$name]) && (isset(self::$settings_options[$key][$name]['deleted']) && self::$settings_options[$key][$name]['deleted'] == 'deleted'))) {
					return true;
				} else {
					return false;
				}
			}
			public static function check_disabled_field($key, $name) {
				// Only check if field is explicitly disabled in custom settings
				if (isset(self::$settings_options[$key][$name]) && 
					isset(self::$settings_options[$key][$name]['disabled']) && 
					self::$settings_options[$key][$name]['disabled'] == '1') {
					return true;
				}
					return false;
			}
			public static function default_woocommerce_checkout_required_fields() {
				return array(
					'billing' => array('billing_first_name' => array('required' => true), 'billing_last_name' => array('required' => '1'), 'billing_country' => array('required' => '1'), 'billing_address_1' => array('required' => '1'), 'billing_city' => array('required' => '1'), 'billing_state' => array('required' => '1'), 'billing_postcode' => array('required' => '1'), 'billing_phone' => array('required' => '1'), 'billing_email' => array('required' => '1')),
					'shipping' => array('shipping_first_name' => array('required' => '1'), 'shipping_last_name' => array('required' => '1'), 'shipping_country' => array('required' => '1'), 'shipping_address_1' => array('required' => '1'), 'shipping_city' => array('required' => '1'), 'shipping_state' => array('required' => '1')),
				);
			}
			public static function default_app_required_fields() {
				return array(
					'billing' => array('billing_first_name' => array('required' => true), 'billing_last_name' => array('required' => '1'), 'billing_phone' => array('required' => '1'), 'billing_email' => array('required' => '1')),
					'shipping' => array(),
				);
			}
			public function file_upload_field() {
				$checkout_fields = $this->get_checkout_fields_for_checkout();
				$current_action = current_filter();
				
				
				switch ($current_action) {
					case 'woocommerce_after_checkout_billing_form':
						$section = 'billing';
						break;
					case 'woocommerce_after_checkout_shipping_form':
						$section = 'shipping';
						break;
					case 'woocommerce_after_checkout_order_form':
						$section = 'order';
						break;
					default:
						return;
				}
				
				if (isset($checkout_fields[$section]) && is_array($checkout_fields[$section])) {
					$fields = $checkout_fields[$section];
				
					
					if (in_array('file', array_column($fields, 'type'))) {
						$file_fields = array_filter($fields, array($this, 'get_file_fields'));
						
						if (!empty($file_fields)) {
							$this->file_upload_field_element($file_fields);
						}
					}
				}
			}
			public function get_other_fields($field) {
				return (is_array($field) && isset($field['custom_field']) && $field['custom_field'] == '1' && isset($field['type']) && $field['type'] != 'file');
			}
			public function get_file_fields($field) {
				$is_file_field = (is_array($field) && 
					isset($field['custom_field']) && 
					$field['custom_field'] == '1' && 
					isset($field['type']) && 
					$field['type'] == 'file'
				);

				
				return $is_file_field;
			}
			public static function file_upload_field_element($fields) {
				foreach ($fields as $key => $field) {
					$label = isset($field['label']) ? $field['label'] : $key;
					$required = !empty($field['required']) ? 'required' : '';
					echo '<p class="form-row form-row-wide mptbm-file-upload-field">';
					echo '<label for="' . esc_attr($key) . '">' . esc_html($label);
					if ($required) echo ' <span class="required">*</span>';
					echo '</label>';
					echo '<input type="file" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . $required . ' data-mptbm-file-upload />';
					// Hidden field for AJAX URL
					echo '<input type="hidden" name="' . esc_attr($key) . '_url" />';
					echo '<div class="mptbm-file-preview"></div>';
					echo '</p>';
				}
			}

			public function header_field_element($field, $key, $args, $value) {
				$label = isset($args['label']) ? $args['label'] : $key;
				$header_type = isset($args['header_type']) ? $args['header_type'] : 'h2';
				$class = isset($args['class']) && is_array($args['class']) ? implode(' ', $args['class']) : '';
				$priority = isset($args['priority']) ? $args['priority'] : '10';
				
				// Add default classes for styling
				$default_class = 'mptbm-checkout-header mptbm-header-' . esc_attr($header_type);
				$full_class = $class ? $default_class . ' ' . $class : $default_class;
				
				$html = '<div class="form-row form-row-wide ' . esc_attr($full_class) . '" data-priority="' . esc_attr($priority) . '">';
				$html .= '<' . esc_attr($header_type) . ' class="mptbm-header-text">' . esc_html($label) . '</' . esc_attr($header_type) . '>';
				$html .= '</div>';
				
				return $html;
			}
			function save_custom_checkout_fields_to_order($order_id, $data) {
					
				$checkout_key_fields = $this->get_checkout_fields_for_checkout();				
				foreach ($checkout_key_fields as $key => $checkout_fields) {
					if (is_array($checkout_fields) && count($checkout_fields)) {
						$checkout_other_fields = array_filter($checkout_fields, array($this, 'get_other_fields'));
						foreach ($checkout_other_fields as $key => $field) {
							if (isset($_POST[$key])) {
								update_post_meta($order_id, sanitize_text_field('_' . $key), sanitize_text_field($_POST[$key]));
							}
						}
						
						if (in_array('file', array_column($checkout_fields, 'type'))) {
							
							$checkout_file_fields = array_filter($checkout_fields, array($this, 'get_file_fields'));
							
							
							foreach ($checkout_file_fields as $key => $field) {
								
								$image_url = isset($_POST[$key . '_url']) ? esc_url_raw($_POST[$key . '_url']) : '';
								
								
								if ($image_url) {
									update_post_meta($order_id, '_' . $key, $image_url);
									// Also save to short key if billing_
									if (strpos($key, 'billing_') === 0) {
										$short_key = substr($key, strlen('billing_'));
										update_post_meta($order_id, '_' . $short_key, $image_url);
										
									}
	
								} else {
									
									if (isset($field['required']) && $field['required'] == '1') {
									
										wc_add_notice(sprintf(__('Please upload a file for %s', 'ecab-taxi-booking-manager'), $field['label']), 'error');
									}
								}
							}
						}
					}
				}
			}
			function get_post($order_id) {
				$args = array(
					'post_type' => 'mptbm_booking',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
							'key' => 'link_mptbm_id',
							'value' => $order_id,
							'compare' => '='
						),
					)
				);
				$query = new WP_Query($args);
				$post_ids = array();
				if ($query->have_posts()) {
					while ($query->have_posts()) {
						$query->the_post();
						$post_ids[] = get_the_ID();
					}
					wp_reset_postdata();
				}
				return $post_ids;
			}
			function get_uploaded_image_link($file_field_name) {
			
				
				$file_field_name = sanitize_key($file_field_name);
				$upload_dir = wp_upload_dir();
				$image_url = '';
								
				if (isset($_FILES[$file_field_name]) && !empty($_FILES[$file_field_name]['name'])) {
					$file = $_FILES[$file_field_name];
				
					
					// Basic error checking
					if ($file['error'] !== UPLOAD_ERR_OK) {
						$error_message = $this->get_file_error_message($file['error']);
						
						wc_add_notice($error_message, 'error');
						return false;
					}
					
					$file_name = sanitize_file_name($file['name']);
					$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
					$file_type = wp_check_filetype($file_name, $this->allowed_mime_types);
					
					
					if (!in_array($file_extension, $this->allowed_extensions)) {
						$error_message = sprintf(__('Invalid file type. Allowed types are: %s', 'ecab-taxi-booking-manager'), implode(', ', $this->allowed_extensions));
					
						wc_add_notice($error_message, 'error');
						return false;
					}
					
					if (!$file_type['type']) {
						
						wc_add_notice(__('Invalid file type.', 'ecab-taxi-booking-manager'), 'error');
						return false;
					}
					
					// Generate unique filename
					$file_name = wp_unique_filename($upload_dir['path'], $file_name);
					$path = $upload_dir['path'] . '/' . $file_name;
					
				
					
					if (!move_uploaded_file($file['tmp_name'], $path)) {
						
						wc_add_notice(__('Error saving file. Please try again.', 'ecab-taxi-booking-manager'), 'error');
						return false;
					}
					
					$image_url = $upload_dir['url'] . '/' . $file_name;
				
				} else {
					
				}
				
				return $image_url ? $image_url : false;
			}
			private function get_file_error_message($error_code) {
				switch ($error_code) {
					case UPLOAD_ERR_INI_SIZE:
						return __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'ecab-taxi-booking-manager');
					case UPLOAD_ERR_FORM_SIZE:
						return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'ecab-taxi-booking-manager');
					case UPLOAD_ERR_PARTIAL:
						return __('The uploaded file was only partially uploaded', 'ecab-taxi-booking-manager');
					case UPLOAD_ERR_NO_FILE:
						return __('No file was uploaded', 'ecab-taxi-booking-manager');
					case UPLOAD_ERR_NO_TMP_DIR:
						return __('Missing a temporary folder', 'ecab-taxi-booking-manager');
					case UPLOAD_ERR_CANT_WRITE:
						return __('Failed to write file to disk', 'ecab-taxi-booking-manager');
					case UPLOAD_ERR_EXTENSION:
						return __('File upload stopped by extension', 'ecab-taxi-booking-manager');
					default:
						return __('Unknown upload error', 'ecab-taxi-booking-manager');
				}
			}
			function order_details($order_id) {
				$order = wc_get_order($order_id);
				$checkout_fields = $this->get_checkout_fields_for_checkout();
				$billing_fields = $checkout_fields['billing'];
				$shipping_fields = $checkout_fields['shipping'];
				$order_fields = $checkout_fields['order'];
				$current_action = current_filter();
				if ($current_action == 'woocommerce_admin_order_data_after_billing_address') {
					$checkout_billing_other_fields = array_filter($billing_fields, array($this, 'get_other_fields'));
					$this->prepare_other_field($checkout_billing_other_fields, 'billing', $order);
					if (in_array('file', array_column($billing_fields, 'type'))) {
						$checkout_billing_file_fields = array_filter($billing_fields, array($this, 'get_file_fields'));
						$this->prepare_file_field($checkout_billing_file_fields, 'billing', $order);
					}
				} else if ($current_action == 'woocommerce_admin_order_data_after_shipping_address') {
					$checkout_shipping_other_fields = array_filter($shipping_fields, array($this, 'get_other_fields'));
					$this->prepare_other_field($checkout_shipping_other_fields, 'shipping', $order);
					if (in_array('file', array_column($shipping_fields, 'type'))) {
						$checkout_shipping_file_fields = array_filter($shipping_fields, array($this, 'get_file_fields'));
						$this->prepare_file_field($checkout_shipping_file_fields, 'shipping', $order);
					}
				} else if ($current_action == 'woocommerce_admin_order_data_after_order_address') {
					$checkout_order_other_fields = array_filter($order_fields, array($this, 'get_other_fields'));
					$this->prepare_other_field($checkout_order_other_fields, 'order', $order);
					if (in_array('file', array_column($order_fields, 'type'))) {
						$checkout_order_file_fields = array_filter($order_fields, array($this, 'get_file_fields'));
						$this->prepare_file_field($checkout_order_file_fields, 'order', $order);
					}
				}
			}
			function prepare_other_field($custom_fields, $key, $order) {
				?>
                <div class="order_data_column_container">
					<?php if (is_array($custom_fields) && count($custom_fields)) : ?>
                        <div class="order_data_column">
                            <h3><?php echo esc_html('Custom ' . $key); ?></h3>
							<?php foreach ($custom_fields as $name => $field_array): ?>
								<?php
								unset($key_value);
								$key_value = get_post_meta($order->get_id(), '_' . $name, true);
								$key_value = esc_html($key_value);
								$field_label = isset($field_array['label']) ? esc_html($field_array['label'] . ' :') : '';
								$field_name = esc_attr($name);
								?>
                                <p class="form-field form-field-wide">
                                    <strong><?php echo $field_label; ?></strong>
                                    <label for="<?php echo $field_name; ?>"><?php echo $key_value; ?></label>
                                </p>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>
				<?php
			}
			function prepare_file_field($custom_fields, $key, $order) {
				?>
                <div class="order_data_column_container">
					<?php if (is_array($custom_fields) && count($custom_fields)) : ?>
                        <div class="order_data_column">
                            <h3><?php echo esc_html('Custom ' . $key . ' File'); ?></h3>
							<?php foreach ($custom_fields as $name => $field_array) : ?>
								<?php
								unset($key_value);
								$key_value = get_post_meta($order->get_id(), '_' . $name, true);
								$key_value = esc_url($key_value);
								$field_label = isset($field_array['label']) ? esc_html($field_array['label'] . ' :') : '';
								$field_name = esc_attr($name);
								$file_extension = strtolower(pathinfo($key_value, PATHINFO_EXTENSION));
								$file_type = wp_check_filetype($key_value, $this->allowed_mime_types);
								?>
                                <p class="form-field form-field-wide">
                                    <strong><?php echo $field_label; ?></strong>
									<?php if (in_array($file_extension, $this->allowed_extensions) && $file_type['type']) : ?>
										<?php if ($file_extension !== 'pdf') : ?>
                                            <img src="<?php echo $key_value; ?>" alt="<?php echo $field_name; ?> image" width="100" height="100">
                                            <a class="button button-tiny button-primary" href="<?php echo $key_value; ?>" download>Download</a>
										<?php else : ?>
                                            <a class="button button-tiny button-primary" href="<?php echo $key_value; ?>" download>Download PDF</a>
										<?php endif; ?>
									<?php endif; ?>
                                </p>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>
				<?php
			}
			// Prevent WooCommerce from rendering file fields
			public function prevent_default_file_field_render($field, $key, $args, $value) {
				if (isset($args['type']) && $args['type'] === 'file') {
					return ''; // Return empty string to prevent default rendering
				}
				return $field;
			}
			public function remove_file_fields_from_checkout($fields) {
				foreach ($fields as $section => $section_fields) {
					if (is_array($section_fields)) {
						foreach ($section_fields as $key => $field) {
							if (isset($field['type']) && $field['type'] === 'file') {
								unset($fields[$section][$key]);
							}
						}
					}
				}
				return $fields;
			}
			/**
			 * Modify billing fields using WooCommerce's specific filter
			 * This follows WooCommerce best practices and doesn't interfere with other plugins
			 */
		public function modify_billing_fields($fields) {
			$custom = get_option('mptbm_custom_checkout_fields', array());
			
			// Initialize custom fields if not exists
			if (!isset($custom['billing'])) {
				$custom['billing'] = array();
			}
			
			// Apply custom modifications to existing fields
			foreach ($custom['billing'] as $key => $field) {
				// Skip deleted fields
				if (!empty($field['deleted']) && $field['deleted'] === 'deleted') {
					// Remove the field completely
					if (isset($fields[$key])) {
						unset($fields[$key]);
					}
					continue;
				}
				
				// Handle disabled fields
				if (!empty($field['disabled']) && $field['disabled'] === '1') {
					// Remove the field completely
					if (isset($fields[$key])) {
						unset($fields[$key]);
					}
					continue;
				}
				
				// Apply modifications to existing fields
				if (isset($fields[$key])) {
					// Apply ONLY the specific modifications, don't overwrite everything
					
					// Handle label modification
					if (isset($field['label']) && $field['label'] !== '') {
						$fields[$key]['label'] = $field['label'];
					}
					
					// Handle required field properly
					if (isset($field['required'])) {
						$fields[$key]['required'] = ($field['required'] === '1') ? true : false;
					}
					
					// Handle priority (position)
					if (isset($field['priority']) && $field['priority'] !== '') {
						$fields[$key]['priority'] = intval($field['priority']);
					}
					
					// Handle placeholder
					if (isset($field['placeholder']) && $field['placeholder'] !== '') {
						$fields[$key]['placeholder'] = $field['placeholder'];
					}
					
					// Handle class array properly
					if (isset($field['class']) && is_array($field['class']) && !empty($field['class'])) {
						$fields[$key]['class'] = $field['class'];
					}
					
					// Handle validate array properly
					if (isset($field['validate']) && is_array($field['validate']) && !empty($field['validate'])) {
						$fields[$key]['validate'] = $field['validate'];
					}
					
				} else {
					// Handle header fields specially
					if (isset($field['type']) && $field['type'] === 'header') {
						$header_field = array(
							'type' => 'header',
							'label' => $field['label'],
							'header_type' => isset($field['header_type']) ? $field['header_type'] : 'h2',
							'class' => isset($field['class']) ? $field['class'] : array(),
							'priority' => isset($field['priority']) ? intval($field['priority']) : 10,
							'custom_field' => '1',
							'required' => '',
							'disabled' => '',
						);
						$fields[$key] = $header_field;
						continue;
					}
					
					// It's a completely new custom field
					if (isset($field['required'])) {
						$field['required'] = ($field['required'] === '1') ? true : false;
					}
					
					// Handle priority (position)
					if (isset($field['priority']) && $field['priority'] !== '') {
						$field['priority'] = intval($field['priority']);
					}
					
					// Add data attribute for JavaScript targeting
					if (!isset($field['custom_attributes'])) {
						$field['custom_attributes'] = array();
					}
					$field['custom_attributes']['data-mptbm-custom-field'] = '1';
					
					$fields[$key] = $field;
				}
			}
			
			return $fields;
		}
			
			/**
			 * Modify shipping fields using WooCommerce's specific filter
			 */
			public function modify_shipping_fields($fields) {
				$custom = get_option('mptbm_custom_checkout_fields', array());
				
				// Initialize custom fields if not exists
				if (!isset($custom['shipping'])) {
					$custom['shipping'] = array();
				}
				
				// Apply custom modifications to existing fields
				foreach ($custom['shipping'] as $key => $field) {
					// Skip deleted fields
					if (!empty($field['deleted']) && $field['deleted'] === 'deleted') {
						// Remove the field completely
						if (isset($fields[$key])) {
							unset($fields[$key]);
						}
						continue;
					}
					
					// Handle disabled fields
					if (!empty($field['disabled']) && $field['disabled'] === '1') {
						// Remove the field completely
						if (isset($fields[$key])) {
							unset($fields[$key]);
						}
						continue;
					}
					
					// Apply modifications to existing fields
					if (isset($fields[$key])) {
						// Apply ONLY the specific modifications, don't overwrite everything
						
						// Handle label modification
						if (isset($field['label']) && $field['label'] !== '') {
							$fields[$key]['label'] = $field['label'];
						}
						
						// Handle required field properly
						if (isset($field['required'])) {
							$fields[$key]['required'] = ($field['required'] === '1') ? true : false;
						}
						
						// Handle priority (position)
						if (isset($field['priority']) && $field['priority'] !== '') {
							$fields[$key]['priority'] = intval($field['priority']);
						}
						
						// Handle placeholder
						if (isset($field['placeholder']) && $field['placeholder'] !== '') {
							$fields[$key]['placeholder'] = $field['placeholder'];
						}
						
						// Handle class array properly
						if (isset($field['class']) && is_array($field['class']) && !empty($field['class'])) {
							$fields[$key]['class'] = $field['class'];
						}
						
						// Handle validate array properly
						if (isset($field['validate']) && is_array($field['validate']) && !empty($field['validate'])) {
							$fields[$key]['validate'] = $field['validate'];
						}
						
					} else {
						// Handle header fields specially
						if (isset($field['type']) && $field['type'] === 'header') {
							$header_field = array(
								'type' => 'header',
								'label' => $field['label'],
								'header_type' => isset($field['header_type']) ? $field['header_type'] : 'h2',
								'class' => isset($field['class']) ? $field['class'] : array(),
								'priority' => isset($field['priority']) ? intval($field['priority']) : 10,
								'custom_field' => '1',
								'required' => '',
								'disabled' => '',
							);
							$fields[$key] = $header_field;
							continue;
						}
						
						// It's a completely new custom field
						if (isset($field['required'])) {
							$field['required'] = ($field['required'] === '1') ? true : false;
						}
						
						// Handle priority (position)
						if (isset($field['priority']) && $field['priority'] !== '') {
							$field['priority'] = intval($field['priority']);
						}
						
						// Add data attribute for JavaScript targeting
						if (!isset($field['custom_attributes'])) {
							$field['custom_attributes'] = array();
						}
						$field['custom_attributes']['data-mptbm-custom-field'] = '1';
						
						$fields[$key] = $field;
					}
				}
				
				return $fields;
			}
			
			/**
			 * Modify order fields using WooCommerce's specific filter
			 */
			public function modify_order_fields($fields) {
				$custom = get_option('mptbm_custom_checkout_fields', array());
				
				// Initialize custom fields if not exists
				if (!isset($custom['order'])) {
					$custom['order'] = array();
				}
				
				// Apply custom modifications to existing fields
				foreach ($custom['order'] as $key => $field) {
					// Skip deleted fields
					if (!empty($field['deleted']) && $field['deleted'] === 'deleted') {
						// Remove the field completely
						if (isset($fields['order'][$key])) {
							unset($fields['order'][$key]);
						}
						continue;
					}
					
					// Handle disabled fields
					if (!empty($field['disabled']) && $field['disabled'] === '1') {
						// Remove the field completely
						if (isset($fields['order'][$key])) {
							unset($fields['order'][$key]);
						}
						continue;
					}
					
					// Apply modifications to existing fields
					if (isset($fields['order'][$key])) {
						// Apply ONLY the specific modifications, don't overwrite everything
						
						// Handle label modification
						if (isset($field['label']) && $field['label'] !== '') {
							$fields['order'][$key]['label'] = $field['label'];
						}
						
						// Handle required field properly
						if (isset($field['required'])) {
							$fields['order'][$key]['required'] = ($field['required'] === '1') ? true : false;
						}
						
						// Handle priority (position)
						if (isset($field['priority']) && $field['priority'] !== '') {
							$fields['order'][$key]['priority'] = intval($field['priority']);
						}
						
						// Handle placeholder
						if (isset($field['placeholder']) && $field['placeholder'] !== '') {
							$fields['order'][$key]['placeholder'] = $field['placeholder'];
						}
						
						// Handle class array properly
						if (isset($field['class']) && is_array($field['class']) && !empty($field['class'])) {
							$fields['order'][$key]['class'] = $field['class'];
						}
						
						// Handle validate array properly
						if (isset($field['validate']) && is_array($field['validate']) && !empty($field['validate'])) {
							$fields['order'][$key]['validate'] = $field['validate'];
						}
						
					} else {
						// Handle header fields specially
						if (isset($field['type']) && $field['type'] === 'header') {
							$header_field = array(
								'type' => 'header',
								'label' => $field['label'],
								'header_type' => isset($field['header_type']) ? $field['header_type'] : 'h2',
								'class' => isset($field['class']) ? $field['class'] : array(),
								'priority' => isset($field['priority']) ? intval($field['priority']) : 10,
								'custom_field' => '1',
								'required' => '',
								'disabled' => '',
							);
							$fields['order'][$key] = $header_field;
							continue;
						}
						
						// It's a completely new custom field
						if (isset($field['required'])) {
							$field['required'] = ($field['required'] === '1') ? true : false;
						}
						
						// Handle priority (position)
						if (isset($field['priority']) && $field['priority'] !== '') {
							$field['priority'] = intval($field['priority']);
						}
						
						// Add data attribute for JavaScript targeting
						if (!isset($field['custom_attributes'])) {
							$field['custom_attributes'] = array();
						}
						$field['custom_attributes']['data-mptbm-custom-field'] = '1';
						
						$fields['order'][$key] = $field;
					}
				}
				
				return $fields;
			}
			/**
			 * Output file fields for a given section on the checkout page
			 */
			public function output_file_fields_for_section($section) {
				
				// Get current checkout fields from WooCommerce
				$all_fields = WC()->checkout->get_checkout_fields();
				
				$fields = $this->get_file_fields_for_section($section);
				
				if (!empty($fields)) {
					$this->file_upload_field_element($fields);
				}
			}
			/**
			 * Get file-type fields for a section
			 */
			public function get_file_fields_for_section($section) {
				// Use the raw custom fields option to get file fields
				$custom = get_option('mptbm_custom_checkout_fields', array());
				$fields = isset($custom[$section]) ? $custom[$section] : [];
				return array_filter($fields, function($field) {
					return isset($field['type']) && $field['type'] === 'file' && (empty($field['deleted']) || $field['deleted'] !== 'deleted') && (empty($field['disabled']) || $field['disabled'] !== '1');
				});
			}
			// Enqueue JS for file upload on checkout
			public static function enqueue_file_upload_js() {
				// Check if WooCommerce is active and the is_checkout function exists
				if (function_exists('is_checkout') && is_checkout()) {
					wp_enqueue_script('mptbm-file-upload', plugins_url('assets/frontend/js/mptbm-file-upload.js', dirname(__FILE__)), array('jquery'), '1.0', true);
					wp_localize_script('mptbm-file-upload', 'mptbmFileUpload', array(
						'ajax_url' => admin_url('admin-ajax.php'),
						'nonce' => wp_create_nonce('mptbm_file_upload'),
					));
				}
			}
			// Add CSS for hidden fields (fallback)
			public function add_hidden_field_css() {
				?>
				<style>
				/* Fallback for any remaining hidden fields */
				.mptbm-hidden-field {
					display: none !important;
				}
				
				/* Header field styling */
				.woocommerce .mptbm-checkout-header {
					margin: 20px 0 15px 0 !important;
					padding: 0 !important;
					border: none !important;
					background: none !important;
					clear: both !important;
				}
				
				.woocommerce .mptbm-checkout-header h1,
				.woocommerce .mptbm-checkout-header h2,
				.woocommerce .mptbm-checkout-header h3,
				.woocommerce .mptbm-checkout-header p {
					margin: 0 0 10px 0 !important;
					padding: 10px 0 !important;
					font-weight: bold !important;
					border-bottom: 2px solid #e1e1e1 !important;
					text-transform: uppercase !important;
					letter-spacing: 0.5px !important;
					width: 100% !important;
					display: block !important;
				}
				
				/* H1 styling */
				.woocommerce .mptbm-header-h1 h1 {
					font-size: 24px !important;
					color: #2c3e50 !important;
					border-bottom: 3px solid #3498db !important;
				}
				
				/* H2 styling */
				.woocommerce .mptbm-header-h2 h2 {
					font-size: 20px !important;
					color: #34495e !important;
					border-bottom: 2px solid #95a5a6 !important;
				}
				
				/* H3 styling */
				.woocommerce .mptbm-header-h3 h3 {
					font-size: 16px !important;
					color: #7f8c8d !important;
					border-bottom: 1px solid #bdc3c7 !important;
				}
				
				/* P styling */
				.woocommerce .mptbm-header-p p {
					font-size: 14px !important;
					color: #95a5a6 !important;
					font-style: italic !important;
					border-bottom: 1px dashed #bdc3c7 !important;
					text-transform: none !important;
					letter-spacing: normal !important;
				}
				</style>
				<?php
			}
			
			/**
			 * Debug method to check field status (only for administrators)
			 */
			public function debug_field_status() {
				if (!current_user_can('administrator')) {
					return;
				}
				
				$custom = get_option('mptbm_custom_checkout_fields', array());
				echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
				echo '<h4>Debug: Field Status</h4>';
				echo '<pre>';
				print_r($custom);
				echo '</pre>';
				echo '</div>';
			}
			
			/**
			 * Clear cache and reload fields
			 */
			public static function clear_cache_and_reload() {
				// Clear the static cache by calling the function with a special flag
				// Since $cached_fields is a static local variable, we need to reset it differently
				// We'll use a different approach - set a transient to force cache refresh
				set_transient('mptbm_force_cache_refresh', time(), 60);
				
				// Clear any transients
				delete_transient('mptbm_checkout_fields_cache');
				delete_transient('mptbm_checkout_fields_cache_timestamp');
				
				// Clear any options cache
				wp_cache_delete('mptbm_custom_checkout_fields', 'options');
				
				// Clear WooCommerce checkout fields cache
				wp_cache_delete('checkout_fields', 'woocommerce');
				wp_cache_delete('checkout_fields_billing', 'woocommerce');
				wp_cache_delete('checkout_fields_shipping', 'woocommerce');
				wp_cache_delete('checkout_fields_order', 'woocommerce');
				
				// Clear all object cache
				wp_cache_flush();
				
				return true;
			}
		}
		
		// Initialize the class after 'init' to ensure textdomain is loaded
		add_action('init', function() {
			$instance = new MPTBM_Wc_Checkout_Fields_Helper();
			
			// Only add these if custom checkout system is not disabled
			if (!MPTBM_Wc_Checkout_Fields_Helper::disable_custom_checkout_system()) {
				add_action('wp_enqueue_scripts', array('MPTBM_Wc_Checkout_Fields_Helper', 'enqueue_file_upload_js'));
				// Register AJAX actions outside the class
				add_action('wp_ajax_mptbm_file_upload', array('MPTBM_Wc_Checkout_Fields_Helper', 'ajax_file_upload'));
				add_action('wp_ajax_nopriv_mptbm_file_upload', array('MPTBM_Wc_Checkout_Fields_Helper', 'ajax_file_upload'));
			}
		});
	}