<?php
/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
if (!defined('ABSPATH')) {
	die;
} // Cannot access pages directly.
if (!class_exists('MPTBM_Woocommerce')) {
	class MPTBM_Woocommerce
	{
		private $custom_order_data = array(); // Property to store the data
		private $ordered_item_name;
		private $error;
		public function __construct()
		{
			$this->error = new WP_Error();
			add_filter('woocommerce_checkout_fields', array($this, 'custom_override_checkout_fields'), 99999);
			add_action('woocommerce_checkout_update_order_meta', array($this, 'product_custom_field_to_custom_order_notes'), 100, 2);
			add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 90, 3);
			add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 90);
			add_filter('woocommerce_cart_item_thumbnail', array($this, 'cart_item_thumbnail'), 90, 3);
			add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 90, 2);
			//************//
			add_action('woocommerce_after_checkout_validation', array($this, 'after_checkout_validation'));
			add_action('woocommerce_checkout_create_order_line_item', array($this, 'checkout_create_order_line_item'), 90, 4);

			add_action('woocommerce_checkout_order_processed', array($this, 'checkout_order_processed'), 90, 3);
			add_action('woocommerce_store_api_checkout_order_processed', array($this, 'checkout_order_processed'), 90, 3);
			add_filter('woocommerce_order_status_changed', array($this, 'order_status_changed'));
			/*****************************/
			add_action('wp_ajax_mptbm_add_to_cart', [$this, 'mptbm_add_to_cart']);
			add_action('wp_ajax_nopriv_mptbm_add_to_cart', [$this, 'mptbm_add_to_cart']);

			// Add filter to modify WooCommerce validation process
			add_filter('woocommerce_checkout_posted_data', array($this, 'modify_checkout_posted_data'), 99);
			add_filter('woocommerce_checkout_required_field_notice', array($this, 'modify_required_field_notice'), 99, 2);
		}

		public function custom_override_checkout_fields($fields) {
			$checkout_helper = new MPTBM_Wc_Checkout_Fields_Helper();
			$custom_fields = $checkout_helper->get_checkout_fields_for_checkout();

			// Get all custom fields from options
			$options = get_option('mptbm_custom_checkout_fields', array());
			$special_fields = array();
			$deleted_fields = array();

			// First, get all deleted fields
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						if (isset($field['deleted']) && $field['deleted'] == 'deleted') {
							$deleted_fields[] = $field_key;
						}
					}
				}
			}

			// Build special fields array dynamically from custom fields, excluding deleted ones
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						// Skip if field is deleted
						if (in_array($field_key, $deleted_fields)) {
							continue;
						}
						// Check if the field is marked as special in the settings
						if (isset($field['special']) && $field['special'] == '1') {
							$special_fields[] = $field_key;
							$special_fields[] = str_replace('_', ' ', $field_key);
						}
					}
				}
			}

			// Check if we have values for any of our special fields
			$has_special_field_value = false;
			foreach ($special_fields as $special_field) {
				if (isset($_POST[$special_field]) && !empty($_POST[$special_field])) {
					$has_special_field_value = true;
					break;
				}
			}

			// Get disabled fields
			$disabled_fields = array();
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						if (isset($field['disabled']) && $field['disabled'] == '1') {
							$disabled_fields[] = $field_key;
						}
					}
				}
			}

			// Remove disabled and deleted fields from the original fields array
			foreach ($fields as $key => $section_fields) {
				if (is_array($section_fields)) {
					foreach ($section_fields as $field_key => $field) {
						// Remove deleted fields first
						if (in_array($field_key, $deleted_fields)) {
							unset($fields[$key][$field_key]);
							continue;
						}

						// Skip special fields that aren't deleted
						if (in_array($field_key, $special_fields)) {
							continue;
						}

						// Remove disabled fields
						if (in_array($field_key, $disabled_fields)) {
							unset($fields[$key][$field_key]);
						}

						// Remove any 'disable_filed' fields
						if ($field_key === 'disable_filed') {
							unset($fields[$key][$field_key]);
						}
					}
				}
			}

			// Now merge with custom fields, excluding deleted ones
			if (!empty($custom_fields)) {
				foreach ($custom_fields as $key => $section_fields) {
					if (isset($fields[$key]) && is_array($section_fields)) {
						foreach ($section_fields as $field_key => $field) {
							// Skip if field is deleted
							if (in_array($field_key, $deleted_fields)) {
								continue;
							}

							// Special handling for special fields
							if (in_array($field_key, $special_fields)) {
								if ($has_special_field_value) {
									$section_fields[$field_key]['required'] = false;
								}
							}

							// Make sure required is set correctly
							if (isset($field['required'])) {
								$section_fields[$field_key]['required'] = ($field['required'] == '1' || $field['required'] === true);
							}

							// Only add the field if it's not disabled and not deleted
							if (!in_array($field_key, $disabled_fields)) {
								$fields[$key][$field_key] = $section_fields[$field_key];
							}
						}
					}
				}
			}

			return $fields;
		}
		public function product_custom_field_to_custom_order_notes($order_id, $data)
		{
			// Get all custom fields from options
			$options = get_option('mptbm_custom_checkout_fields', array());
			$special_fields = array();

			// Build special fields array dynamically from custom fields
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						// Check if the field is marked as special in the settings
						if (isset($field['special']) && $field['special'] == '1') {
							// Add both underscore and space versions of the field key
							$special_fields[] = $field_key;
							$special_fields[] = str_replace('_', ' ', $field_key);
						}
					}
				}
			}

			// Process all data
			foreach ($data as $key => $value) {
				// Save order data
				if (strpos($key, 'order') === 0) {
					$this->custom_order_data[$key] = $value;
				}

				// Save special fields
				foreach ($special_fields as $special_field) {
					if ($key === $special_field && !empty($value)) {
						// Save with both formats (with and without underscore prefix)
						update_post_meta($order_id, $key, sanitize_text_field($value));
						update_post_meta($order_id, '_' . $key, sanitize_text_field($value));

						// Also save with normalized key (spaces replaced with underscores)
						$normalized_key = str_replace(' ', '_', $key);
						if ($normalized_key !== $key) {
							update_post_meta($order_id, $normalized_key, sanitize_text_field($value));
							update_post_meta($order_id, '_' . $normalized_key, sanitize_text_field($value));
						}
					}
				}
			}
		}
		public function add_cart_item_data($cart_item_data, $product_id)
		{
			$linked_id = MP_Global_Function::get_post_info($product_id, 'link_mptbm_id', $product_id);
			$post_id = is_string(get_post_status($linked_id)) ? $linked_id : $product_id;
			if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
				$distance = isset($_COOKIE['mptbm_distance']) ? absint($_COOKIE['mptbm_distance']) : '';
				$duration = isset($_COOKIE['mptbm_duration']) ? absint($_COOKIE['mptbm_duration']) : '';
				$start_place = isset($_POST['mptbm_start_place']) ? sanitize_text_field($_POST['mptbm_start_place']) : '';
				$end_place = isset($_POST['mptbm_end_place']) ? sanitize_text_field($_POST['mptbm_end_place']) : '';
				$waiting_time = isset($_POST['mptbm_waiting_time']) ? sanitize_text_field($_POST['mptbm_waiting_time']) : 0;
				$return = isset($_POST['mptbm_taxi_return']) ? sanitize_text_field($_POST['mptbm_taxi_return']) : 1;
				$fixed_hour = isset($_POST['mptbm_fixed_hours']) ? sanitize_text_field($_POST['mptbm_fixed_hours']) : 0;
				$total_price = $this->get_cart_total_price($post_id);
				$price = MPTBM_Function::get_price($post_id, $distance, $duration, $start_place, $end_place, $waiting_time, $return, $fixed_hour);
				$wc_price = MP_Global_Function::wc_price($post_id, $price);
				$raw_price = MP_Global_Function::price_convert_raw($wc_price);
				$cart_item_data['mptbm_date'] = isset($_POST['mptbm_date']) ? sanitize_text_field($_POST['mptbm_date']) : '';
				$cart_item_data['mptbm_taxi_return'] = $return;
				$cart_item_data['mptbm_waiting_time'] = $waiting_time;
				$cart_item_data['mptbm_start_place'] = wp_strip_all_tags($start_place);
				$cart_item_data['mptbm_end_place'] = wp_strip_all_tags($end_place);
				$cart_item_data['mptbm_distance'] = $distance;
				$cart_item_data['mptbm_distance_text'] = isset($_COOKIE['mptbm_distance_text']) ? sanitize_text_field($_COOKIE['mptbm_distance_text']) : '';
				$cart_item_data['mptbm_duration'] = $duration;
				$cart_item_data['mptbm_fixed_hours'] = $fixed_hour;
				$cart_item_data['mptbm_duration_text'] = isset($_COOKIE['mptbm_duration_text']) ? sanitize_text_field($_COOKIE['mptbm_duration_text']) : '';
				$cart_item_data['mptbm_base_price'] = $raw_price;
				$cart_item_data['mptbm_extra_service_info'] = self::cart_extra_service_info($post_id);
				$cart_item_data['mptbm_tp'] = $total_price;
				$cart_item_data['line_total'] = $total_price;
				$cart_item_data['line_subtotal'] = $total_price;
				$cart_item_data['mptbm_passengers'] = isset($_POST['mptbm_passengers']) ? absint($_POST['mptbm_passengers']) : 1;
				if ($return > 1 && MP_Global_Function::get_settings('mptbm_general_settings', 'enable_return_in_different_date') == 'yes') {
					$return_target_date = isset($_POST['mptbm_return_date']) ? sanitize_text_field($_POST['mptbm_return_date']) : '';
					$return_target_time = isset($_POST['mptbm_return_time']) ? sanitize_text_field($_POST['mptbm_return_time']) : '';
					$cart_item_data['mptbm_return_target_date'] = $return_target_date;
					$cart_item_data['mptbm_return_target_time'] = $return_target_time;
				}
				$cart_item_data = apply_filters('mptbm_add_cart_item', $cart_item_data, $post_id);
			}
			$cart_item_data['mptbm_id'] = $post_id;
			// echo '<pre>';print_r($cart_item_data);echo '</pre>';
			return $cart_item_data;
		}
		public function before_calculate_totals($cart_object)
		{

			foreach ($cart_object->cart_contents as $value) {
				$post_id = array_key_exists('mptbm_id', $value) ? $value['mptbm_id'] : 0;
				if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
					$total_price = $value['mptbm_tp'];
					if (isset($_SESSION['geo_fence_post_' . $post_id])) {
						// Extract amount from session
						$session_data = $_SESSION['geo_fence_post_' . $post_id];
						// Check if session data contains the amount
						if (isset($session_data[0])) {
							// Add the amount to the price
							$total_price += (float)$session_data[0];
						}
					}
					$value['data']->set_price($total_price);
					$value['data']->set_regular_price($total_price);
					$value['data']->set_sale_price($total_price);
					$value['data']->set_sold_individually('yes');
					$value['data']->get_price();
				}
			}
		}
		public function cart_item_thumbnail($thumbnail, $cart_item)
		{
			$mptbm_id = array_key_exists('mptbm_id', $cart_item) ? $cart_item['mptbm_id'] : 0;
			if (get_post_type($mptbm_id) == MPTBM_Function::get_cpt()) {
				$thumbnail = '<div class="bg_image_area" data-href="' . get_the_permalink($mptbm_id) . '"><div data-bg-image="' . MP_Global_Function::get_image_url($mptbm_id) . '"></div></div>';
			}
			return $thumbnail;
		}
		public function get_item_data($item_data, $cart_item)
		{
			$post_id = array_key_exists('mptbm_id', $cart_item) ? $cart_item['mptbm_id'] : 0;
			if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
				ob_start();
				$this->show_cart_item($cart_item, $post_id);
				do_action('mptbm_show_cart_item', $cart_item, $post_id);
				$item_data[] = array('key' => esc_html__('Booking Details ', 'ecab-taxi-booking-manager'), 'value' => ob_get_clean());
				
			
			}
			return $item_data;
		}
		//**************//
		public function after_checkout_validation()
		{
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();

			// Optional fields that should not trigger validation errors
			$optional_fields = array(
				'mptbm_passengers',
				'mptbm_pickup_time',
				'mptbm_pickup_date',
				'mptbm_luggage_size',
				'mptbm_car_type'
			);

			// Get all custom fields from options
			$options = get_option('mptbm_custom_checkout_fields', array());
			$special_fields = array();

			// Build special fields array dynamically from custom fields
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						// Check if the field is marked as special in the settings
						if (isset($field['special']) && $field['special'] == '1') {
							// Add both underscore and space versions of the field key
							$special_fields[] = $field_key;
							$special_fields[] = str_replace('_', ' ', $field_key);
						}
					}
				}
			}

			// Check if we have values for any of our special fields
			$has_special_field_value = false;
			foreach ($special_fields as $special_field) {
				if (isset($_POST[$special_field]) && !empty($_POST[$special_field])) {
					$has_special_field_value = true;
					break;
				}
			}

			// Get custom checkout fields
			$options = get_option('mptbm_custom_checkout_fields');

			// Add a filter to remove validation errors for disabled fields
			add_filter('woocommerce_checkout_required_field_notice', function($message, $field_label) use ($options, $optional_fields, $special_fields, $has_special_field_value) {
				// First check if it's one of our optional fields
				foreach ($optional_fields as $optional_field) {
					if (strpos($field_label, $optional_field) !== false) {
						return '';
					}
				}

				// Special handling for custom fields
				foreach ($special_fields as $custom_field) {
					if (strpos($field_label, $custom_field) !== false) {
						// If any special field has a value, skip validation for all special fields
						if ($has_special_field_value) {
							return '';
						}
					}
				}

				// Then check if it's a disabled field in our custom fields
				foreach (['billing', 'shipping', 'order'] as $section) {
					if (isset($options[$section]) && is_array($options[$section])) {
						foreach ($options[$section] as $field_key => $field) {
							// If field is disabled, remove any validation errors for it
							if (isset($field['disabled']) && $field['disabled'] == '1') {
								// Check if this field label contains our field key
								if (strpos($field_label, $field_key) !== false) {
									return '';
								}
							}
							// If field is not required, remove any validation errors for it
							if (isset($field['required']) && $field['required'] == '0') {
								// Check if this field label contains our field key
								if (strpos($field_label, $field_key) !== false) {
									return '';
								}
							}
						}
					}
				}

				return $message;
			}, 10, 2);

			foreach ($items as $values) {
				$post_id = array_key_exists('mptbm_id', $values) ? $values['mptbm_id'] : 0;
				if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
					// Add a filter to remove WooCommerce validation errors for our custom fields
					add_filter('woocommerce_checkout_posted_data', function($data) {
						// Special handling for custom fields
						$custom_fields = array('billing_Passport_No', 'billing_Flight_No');

						// Check if at least one custom field has a value
						$has_custom_field_value = false;
						foreach ($custom_fields as $custom_field) {
							if (isset($data[$custom_field]) && !empty($data[$custom_field])) {
								$has_custom_field_value = true;
								break;
							}
						}

						// If at least one custom field has a value, make sure all custom fields pass validation
						if ($has_custom_field_value) {
							foreach ($custom_fields as $custom_field) {
								if (!isset($data[$custom_field]) || empty($data[$custom_field])) {
									// Set a dummy value to pass validation
									$data[$custom_field] = 'validated';
								}
							}
						}

						// Get custom checkout fields
						$options = get_option('mptbm_custom_checkout_fields');

						// Handle disabled fields
						foreach (['billing', 'shipping', 'order'] as $section) {
							if (isset($options[$section]) && is_array($options[$section])) {
								foreach ($options[$section] as $field_key => $field) {
									// If field is disabled, set a dummy value to pass validation
									if (isset($field['disabled']) && $field['disabled'] == '1') {
										$data[$field_key] = 'disabled_field';
									}
									// If field is not required, make sure it passes validation
									if (isset($field['required']) && $field['required'] == '0' && (!isset($data[$field_key]) || empty($data[$field_key]))) {
										$data[$field_key] = 'optional_field';
									}
								}
							}
						}

						return $data;
					});

					do_action('mptbm_validate_cart_item', $values, $post_id);
				}
			}
		}
		/**
		 * Modify the checkout posted data to handle disabled fields
		 *
		 * @param array $data The posted data
		 * @return array Modified posted data
		 */
		public function modify_checkout_posted_data($data) {
			// Get custom checkout fields
			$options = get_option('mptbm_custom_checkout_fields', array());
			$special_fields = array();
			$deleted_fields = array();

			// First, get all deleted fields
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						if (isset($field['deleted']) && $field['deleted'] == 'deleted') {
							$deleted_fields[] = $field_key;
						}
					}
				}
			}

			// Build special fields array dynamically from custom fields, excluding deleted ones
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						// Skip if field is deleted
						if (in_array($field_key, $deleted_fields)) {
							continue;
						}
						// Check if the field is marked as special in the settings
						if (isset($field['special']) && $field['special'] == '1') {
							$special_fields[] = $field_key;
							$special_fields[] = str_replace('_', ' ', $field_key);
						}
					}
				}
			}

			// Check if we have values for any of our special fields
			$has_special_field_value = false;
			foreach ($special_fields as $special_field) {
				if (isset($data[$special_field]) && !empty($data[$special_field])) {
					$has_special_field_value = true;
					break;
				}
			}

			// If any special field has a value, make sure all fields pass validation
			if ($has_special_field_value) {
				// Set dummy values for all required fields to pass validation
				foreach (['billing', 'shipping', 'order'] as $section) {
					if (isset($options[$section]) && is_array($options[$section])) {
						foreach ($options[$section] as $field_key => $field) {
							// Skip if field is deleted
							if (in_array($field_key, $deleted_fields)) {
								continue;
							}
							// If field is disabled or not required, set a dummy value
							if ((isset($field['disabled']) && $field['disabled'] == '1') ||
								(isset($field['required']) && $field['required'] == '0')) {
								if (!isset($data[$field_key]) || empty($data[$field_key])) {
									$data[$field_key] = 'validated_field';
								}
							}
						}
					}
				}

				// Also handle default WooCommerce fields
				$default_fields = array(
					'billing_first_name', 'billing_last_name', 'billing_company', 'billing_country',
					'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state',
					'billing_postcode', 'billing_phone', 'billing_email'
				);

				foreach ($default_fields as $field) {
					if (!isset($data[$field]) || empty($data[$field])) {
						$data[$field] = 'validated_field';
					}
				}
			}

			// Handle disabled fields
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						// Skip if field is deleted
						if (in_array($field_key, $deleted_fields)) {
							if (isset($data[$field_key])) {
								unset($data[$field_key]);
							}
							continue;
						}
						// If field is disabled, set a dummy value
						if (isset($field['disabled']) && $field['disabled'] == '1') {
							if (!isset($data[$field_key]) || empty($data[$field_key])) {
								$data[$field_key] = 'disabled_field';
							}
						}
					}
				}
			}

			// Remove any 'disable_filed' fields
			if (isset($data['disable_filed'])) {
				unset($data['disable_filed']);
			}

			return $data;
		}

		/**
		 * Modify the required field notice to handle disabled fields
		 *
		 * @param string $message The error message
		 * @param string $field_label The field label
		 * @return string Modified error message
		 */
		public function modify_required_field_notice($message, $field_label) {
			// Get custom checkout fields
			$options = get_option('mptbm_custom_checkout_fields');

			// Special fields that should be handled specially
			// Include both formats (with space and with underscore)
			$special_fields = array(
				'billing_Passport_No', 'billing_Flight_No',
				'billing_Passport No', 'billing_Flight No'
			);

			// Check if we have values for any of our special fields
			$has_special_field_value = false;
			foreach ($special_fields as $special_field) {
				if (isset($_POST[$special_field]) && !empty($_POST[$special_field])) {
					$has_special_field_value = true;
					break;
				}
			}

			// If any special field has a value, skip validation for all fields
			if ($has_special_field_value) {
				return '';
			}

			// Check if it's a disabled field
			foreach (['billing', 'shipping', 'order'] as $section) {
				if (isset($options[$section]) && is_array($options[$section])) {
					foreach ($options[$section] as $field_key => $field) {
						// If field is disabled, remove any validation errors for it
						if (isset($field['disabled']) && $field['disabled'] == '1') {
							// Check if this field label contains our field key
							if (strpos($field_label, $field_key) !== false) {
								return '';
							}
						}
					}
				}
			}

			return $message;
		}

		public function checkout_create_order_line_item($item, $cart_item_key, $values)
		{
			$this->ordered_item_name = $item->get_name();

			$post_id = array_key_exists('mptbm_id', $values) ? $values['mptbm_id'] : 0;
			if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
				$date = $values['mptbm_date'] ?? '';
				$start_location = $values['mptbm_start_place'] ?? '';
				$end_location = $values['mptbm_end_place'] ?? '';
				$distance = $values['mptbm_distance'] ?? '';
				$distance_text = $values['mptbm_distance_text'] ?? '';
				$duration = $values['mptbm_duration'] ?? '';
				$duration_text = $values['mptbm_duration_text'] ?? '';
				$base_price = $values['mptbm_base_price'] ?? '';
				$return = $values['mptbm_taxi_return'] ?? '';
				$waiting_time = $values['mptbm_waiting_time'] ?? '';
				$fixed_time = $values['mptbm_fixed_hours'] ?? 0;
				$extra_service = $values['mptbm_extra_service_info'] ?? [];
				$price = $values['mptbm_tp'] ?? '';

				$item->add_meta_data(esc_html__('Pickup Location ', 'ecab-taxi-booking-manager'), $start_location);
				$item->add_meta_data(esc_html__('Drop-Off Location ', 'ecab-taxi-booking-manager'), $end_location);
				$price_type = MP_Global_Function::get_post_info($post_id, 'mptbm_price_based');
				if ($price_type !== 'manual') {
					$item->add_meta_data(esc_html__('Approximate Distance ', 'ecab-taxi-booking-manager'), $distance_text);
					$item->add_meta_data(esc_html__('Approximate Time ', 'ecab-taxi-booking-manager'), $duration_text);
				}

				if ($waiting_time && $waiting_time > 0) {
					$item->add_meta_data(esc_html__('Extra Waiting Hours', 'ecab-taxi-booking-manager'), $waiting_time . ' ' . esc_html__('Hour ', 'ecab-taxi-booking-manager'));
				}
				if ($fixed_time && $fixed_time > 0) {
					$item->add_meta_data(esc_html__('Service Times', 'ecab-taxi-booking-manager'), $fixed_time . ' ' . esc_html__('Hour ', 'ecab-taxi-booking-manager'));
				}
				$item->add_meta_data(esc_html__('Date ', 'ecab-taxi-booking-manager'), esc_html(MP_Global_Function::date_format($date)));
				$item->add_meta_data(esc_html__('Time ', 'ecab-taxi-booking-manager'), esc_html(MP_Global_Function::date_format($date, 'time')));
				
				// Add passenger count to order meta only if the setting is enabled
				$show_passengers = MP_Global_Function::get_settings('mptbm_general_settings', 'show_number_of_passengers', 'yes');
				if ($show_passengers === 'yes') {
					$passengers = isset($values['mptbm_passengers']) ? absint($values['mptbm_passengers']) : 1;
					$item->add_meta_data(esc_html__('Number of Passengers', 'ecab-taxi-booking-manager'), $passengers);
					$item->add_meta_data('_mptbm_passengers', $passengers);
				}

				if ($return && $return > 1) {
					$item->add_meta_data(esc_html__('Transfer Type', 'ecab-taxi-booking-manager'), esc_html__('Return ', 'ecab-taxi-booking-manager'));
					if (MP_Global_Function::get_settings('mptbm_general_settings', 'enable_return_in_different_date') == 'yes') {
						$return_date = $values['mptbm_return_target_date'] ?? '';
						$return_time = $values['mptbm_return_target_time'] ?? '';

						if ($return_time !== "") {
							if ($return_time !== "0") {
								// Convert start time to hours and minutes
								list($hours, $decimal_part) = explode('.', $return_time);
								$interval_time = MPTBM_Function::get_general_settings('mptbm_pickup_interval_time');
								if ($interval_time == "5" || $interval_time == "15") {
									$minutes = isset($decimal_part) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
								} else {
									$minutes = isset($decimal_part) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
								}
							} else {
								$hours = 0;
								$minutes = 0;
							}
						} else {
							$hours = 0;
							$minutes = 0;
						}

						// Format hours and minutes
						$return_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

						// Combine date and time if both are available
						$return_date_time = $return_date ? gmdate("Y-m-d", strtotime($return_date)) : "";
						if ($return_date_time && $return_time !== "") {
							$return_date_time .= " " . $return_time_formatted;
						}


						$item->add_meta_data(esc_html__('Return Date', 'ecab-taxi-booking-manager'), esc_html(MP_Global_Function::date_format($return_date_time)));
						$item->add_meta_data(esc_html__('Return Time', 'ecab-taxi-booking-manager'), esc_html(MP_Global_Function::date_format($return_date_time, 'time')));
						$item->add_meta_data('_mptbm_return_date', $return_date);
						$item->add_meta_data('_mptbm_return_time', $return_time);
					}
				}
				$item->add_meta_data(esc_html__('Price ', 'ecab-taxi-booking-manager'), wp_kses_post(wc_price($base_price)));
				if (sizeof($extra_service) > 0) {
					$item->add_meta_data(esc_html__('Optional Service ', 'ecab-taxi-booking-manager'), '');
					foreach ($extra_service as $service) {
						$item->add_meta_data(esc_html__('Services Name ', 'ecab-taxi-booking-manager'), $service['service_name']);
						$item->add_meta_data(esc_html__('Services Quantity ', 'ecab-taxi-booking-manager'), $service['service_quantity']);
						$item->add_meta_data(esc_html__('Price ', 'ecab-taxi-booking-manager'), esc_html(' ( ') . wp_kses_post(wc_price($service['service_price'])) . esc_html(' X ') . esc_html($service['service_quantity']) . esc_html(') = ') . wp_kses_post(wc_price($service['service_price'] * $service['service_quantity'])));
					}
				}
				if (class_exists('MPTBM_Plugin_Ecab_Calendar_Addon')) {
					// Prepare date and time for Google Calendar format
					$formatted_date = MP_Global_Function::date_format($date);
					$formatted_time = MP_Global_Function::date_format($date, 'time');
					// Combine the provided formatted date and time
					$date_time_string = $formatted_date . ' ' . $formatted_time; // Combine date and time as a single string

					// Get the WordPress time zone
					$timezone = new DateTimeZone(wp_timezone_string());

					// Create DateTime object with the combined date and time, and apply WordPress time zone
					$start_date_time = new DateTime($date_time_string, $timezone);

					// Convert to UTC (Google Calendar requires UTC time format)
					$start_date_time->setTimezone(new DateTimeZone('UTC'));

					// Format date and time for Google Calendar
					$formatted_date_time = $start_date_time->format('Ymd\THis\Z'); // Start time in Google Calendar format

					// For the event end time (assuming 1 hour duration)
					$end_date_time = clone $start_date_time;
					$end_date_time->modify('+2  hour'); // Set the end time to 1 hour later
					$formatted_end_time = $end_date_time->format('Ymd\THis\Z'); // End time in Google Calendar format
					$driver_id = get_post_meta($post_id, 'mptbm_selected_driver', true);
					if ($driver_id) {
						$driver_info = get_userdata($driver_id);
						$driver_name = $driver_info->display_name;
						$driver_email = $driver_info->user_email;
					} else {
						$driver_name = '';
						$driver_email = '';
					}

					// Build the details string conditionally
					$details = "Transport service from " . $start_location . " to " . $end_location;
					if ($driver_email) {
						$details .= ". Driver email: " . $driver_email;
					}
					if ($driver_name) {
						$driver_name = $driver_name;
					}

					// Create Google Calendar link
					$google_calendar_link = "https://www.google.com/calendar/render?action=TEMPLATE&text="
						. urlencode($this->ordered_item_name) // Event title
						. "&dates=" . $formatted_date_time . "/" . $formatted_end_time // Start and end times
						. "&details=" . urlencode($details)
						. "&location=" . urlencode($start_location)
						. "&sf=true&output=xml";

					// Add Google Calendar link as meta data
					$item->add_meta_data(
						esc_html__('Add this event to your Google Calendar', 'ecab-taxi-booking-manager'),
						'<a href="' . esc_url($google_calendar_link) . '" target="_blank">' . esc_html__('Add this event to your Google Calendar', 'ecab-taxi-booking-manager') . '</a>'
					);
				}
				$item->add_meta_data('_mptbm_id', $post_id);
				$item->add_meta_data('_mptbm_date', $date);
				$item->add_meta_data('_mptbm_start_place', $start_location);
				$item->add_meta_data('_mptbm_end_place', $end_location);
				$item->add_meta_data('_mptbm_taxi_return', $return);
				$item->add_meta_data('_mptbm_waiting_time', $waiting_time);
				$item->add_meta_data('_mptbm_fixed_hours', $fixed_time);
				$item->add_meta_data('_mptbm_distance', $distance);
				$item->add_meta_data('_mptbm_distance_text', $distance_text);
				$item->add_meta_data('_mptbm_duration', $duration);
				$item->add_meta_data('_mptbm_duration_text', $duration_text);
				$item->add_meta_data('_mptbm_base_price', $base_price);
				$item->add_meta_data('_mptbm_tp', $price);
				$item->add_meta_data('_mptbm_service_info', $extra_service);

				do_action('mptbm_checkout_create_order_line_item', $item, $values);
			}
		}
		public function checkout_order_processed($order_id)
		{

			$result   = ! is_numeric( $order_id ) ? json_decode( $order_id ) : [ 0 ];
			$order_id = ! is_numeric( $order_id ) ? $result->id : $order_id;
			if ( ! $order_id ) {
				return;
			}

			// Send email notification
			$admin_email = get_option('admin_email');
			wp_mail($admin_email, 'MPTBM Order Processed', 'Order ID: ' . $order_id);
			if ($order_id) {

				$order = wc_get_order($order_id);

				// Get all meta data
				$meta_data = $order->get_meta_data();

				// Initialize an associative array to store meta keys and values
				$meta_array = [];

				foreach ($meta_data as $meta) {
					// Get the meta key and value
					$meta_key = $meta->get_data()['key'];
					$meta_value = $meta->get_data()['value'];

					// Store the key-value pair in the associative array
					$meta_array[$meta_key] = $meta_value;
				}

				// Unset any meta keys you don't want to include
				unset($meta_array['_billing_address_index']);
				unset($meta_array['_shipping_address_index']);
				unset($meta_array['is_vat_exempt']);
				// Add the filtered custom order data to the meta array
				if (!empty($this->custom_order_data)) {
					foreach ($this->custom_order_data as $key => $value) {
						$meta_array[$key] = $value;
					}
				}
				$order_status = $order->get_status();
				$order_meta = get_post_meta($order_id);
				$payment_method = $order_meta['_payment_method_title'][0] ?? '';
				$user_id = $order_meta['_customer_user'][0] ?? '';

				if ($order_status != 'failed') {
					foreach ($order->get_items() as $item_id => $item) {
						$post_id = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_id');
						if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
							$date = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_date');
							$date = $date ? MP_Global_Function::data_sanitize($date) : '';
							$start_place = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_start_place');
							$start_place = $start_place ? MP_Global_Function::data_sanitize($start_place) : '';
							$end_place = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_end_place');
							$end_place = $end_place ? MP_Global_Function::data_sanitize($end_place) : '';
							$waiting_time = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_waiting_time');
							$waiting_time = $waiting_time ? MP_Global_Function::data_sanitize($waiting_time) : '';
							$return = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_taxi_return');
							$return = $return ? MP_Global_Function::data_sanitize($return) : '';
							if ($return > 1 && MP_Global_Function::get_settings('mptbm_general_settings', 'enable_return_in_different_date') == 'yes') {
								$return_target_date = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_return_date');
								$return_target_time = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_return_time');
								$data['mptbm_return_target_date'] = $return_target_date;
								$data['mptbm_return_target_time'] = $return_target_time;
							}
							$fixed_time = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_fixed_hours');
							$fixed_time = $fixed_time ? MP_Global_Function::data_sanitize($fixed_time) : '';
							$distance = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_distance');
							$distance = $distance ? MP_Global_Function::data_sanitize($distance) : '';
							$duration = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_duration');
							$duration = $duration ? MP_Global_Function::data_sanitize($duration) : '';
							$base_price = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_base_price');
							$base_price = $base_price ? MP_Global_Function::data_sanitize($base_price) : '';
							$service = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_service_info');
							$service_info = $service ? MP_Global_Function::data_sanitize($service) : [];
							$price = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_tp');
							$price = $price ? MP_Global_Function::data_sanitize($price) : [];

							// Add meta array data to the $data array
							$data = array_merge($meta_array, [
								'mptbm_id' => $post_id,
								'mptbm_date' => $date,
								'mptbm_start_place' => $start_place,
								'mptbm_end_place' => $end_place,
								'mptbm_waiting_time' => $waiting_time,
								'mptbm_taxi_return' => $return,
								'mptbm_fixed_hours' => $fixed_time,
								'mptbm_distance' => $distance,
								'mptbm_duration' => $duration,
								'mptbm_base_price' => $base_price,
								'mptbm_order_id' => $order_id,
								'mptbm_order_status' => $order_status,
								'mptbm_payment_method' => $order->get_payment_method_title(),
								'mptbm_user_id' => $user_id,
								'mptbm_tp' => $price,
								'mptbm_service_info' => $service_info,
								'mptbm_billing_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
								'mptbm_billing_email' => $order->get_billing_email(),
								'mptbm_billing_phone' => $order->get_billing_phone(),
								'mptbm_target_pickup_interval_time' => MPTBM_Function::get_general_settings('mptbm_pickup_interval_time', '30')
							]);

							// Only add passenger count if the setting is enabled
							$show_passengers = MP_Global_Function::get_settings('mptbm_general_settings', 'show_number_of_passengers', 'yes');
							if ($show_passengers === 'yes') {
								$data['mptbm_passengers'] = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_passengers') ?? 1;
							}

							$booking_data = apply_filters('add_mptbm_booking_data', $data, $post_id);
							self::add_cpt_data('mptbm_booking', $booking_data['mptbm_billing_name'], $booking_data);

							if (sizeof($service_info) > 0) {
								foreach ($service_info as $service) {
									$ex_data = [
										'mptbm_id' => $post_id,
										'mptbm_date' => $date,
										'mptbm_order_id' => $order_id,
										'mptbm_order_status' => $order_status,
										'mptbm_service_name' => $service['service_name'],
										'mptbm_service_quantity' => $service['service_quantity'],
										'mptbm_service_price' => $service['service_price'],
										'mptbm_payment_method' => $payment_method,
										'mptbm_user_id' => $user_id
									];
									self::add_cpt_data('mptbm_service_booking', '#' . $order_id . $ex_data['mptbm_service_name'], $ex_data);
								}
							}
						}
					}
				}
				$data['mptbm_item_name'] = $this->ordered_item_name;

				$driver_id = get_post_meta($post_id, 'mptbm_selected_driver', true);
				if ($driver_id) {
					$driver_info = get_userdata($driver_id);
					$data['mptbm_item_driver_name'] = $driver_info->display_name;
					$data['mptbm_item_driver_email'] = $driver_info->user_email;
					$data['mptbm_item_driver_phone'] = get_user_meta($driver_id, 'user_phone', true);
				}
				do_action('mptbm_checkout_order_processed', $data);
			}
		}
		public function order_status_changed($order_id)
		{
			$order = wc_get_order($order_id);
			$order_status = $order->get_status();
			foreach ($order->get_items() as $item_id => $item_values) {
				$post_id = MP_Global_Function::get_order_item_meta($item_id, '_mptbm_id');
				if (get_post_type($post_id) == MPTBM_Function::get_cpt()) {
					if ($order->has_status('processing') || $order->has_status('pending') || $order->has_status('on-hold') || $order->has_status('completed') || $order->has_status('cancelled') || $order->has_status('refunded') || $order->has_status('failed') || $order->has_status('requested')) {
						$this->wc_order_status_change($order_status, $post_id, $order_id);
					}
				}
			}
		}
		//**************************//
		public function show_cart_item($cart_item, $post_id)
		{
			$date = array_key_exists('mptbm_date', $cart_item) ? $cart_item['mptbm_date'] : '';

			$start_location = array_key_exists('mptbm_start_place', $cart_item) ? $cart_item['mptbm_start_place'] : '';
			$end_location = array_key_exists('mptbm_end_place', $cart_item) ? $cart_item['mptbm_end_place'] : '';
			$base_price = array_key_exists('mptbm_base_price', $cart_item) ? $cart_item['mptbm_base_price'] : '';
			$return = array_key_exists('mptbm_taxi_return', $cart_item) ? $cart_item['mptbm_taxi_return'] : '';
			$waiting_time = array_key_exists('mptbm_waiting_time', $cart_item) ? $cart_item['mptbm_waiting_time'] : '';
			$fixed_time = array_key_exists('mptbm_fixed_hours', $cart_item) ? $cart_item['mptbm_fixed_hours'] : '';
			$extra_service = array_key_exists('mptbm_extra_service_info', $cart_item) ? $cart_item['mptbm_extra_service_info'] : [];
?>
			<div class="mpStyle">
				<?php do_action('mptbm_before_cart_item_display', $cart_item, $post_id); ?>
				<div class="dLayout_xs">
					<ul class="cart_list">
						<li>
							<span class="fas fa-map-marker-alt"></span>
							<h6 class="_mR_xs"><?php esc_html_e('Pickup Location', 'ecab-taxi-booking-manager'); ?> :</h6>
							<span><?php echo esc_html($start_location); ?></span>
						</li>
						<li>
							<span class="fas fa-map-marker-alt"></span>
							<h6 class="_mR_xs"><?php esc_html_e('Drop-Off Location', 'ecab-taxi-booking-manager'); ?> :</h6>
							<span><?php echo esc_html($end_location); ?></span>
						</li>
						<?php
						$price_type = MP_Global_Function::get_post_info($post_id, 'mptbm_price_based');
						if ($price_type !== 'manual') {
						?>
							<li>
								<span class="fas fa-route"></span>
								<h6 class="_mR_xs"><?php esc_html_e('Approximate Distance', 'ecab-taxi-booking-manager'); ?> :</h6>
								<span><?php echo esc_html($cart_item['mptbm_distance_text']); ?></span>
							</li>
							<li>
								<span class="far fa-clock"></span>
								<h6 class="_mR_xs"><?php esc_html_e('Approximate Time', 'ecab-taxi-booking-manager'); ?> :</h6>
								<span><?php echo esc_html($cart_item['mptbm_duration_text']); ?></span>
							</li>
						<?php } ?>
						<li>
							<span class="far fa-calendar-alt"></span>
							<h6 class="_mR_xs"><?php esc_html_e('Date', 'ecab-taxi-booking-manager'); ?> :</h6>
							<span><?php echo esc_html(MP_Global_Function::date_format($date)); ?></span>
						</li>
						<li>
							<span class="far fa-clock"></span>
							<h6 class="_mR_xs"><?php esc_html_e('Time : ', 'ecab-taxi-booking-manager'); ?></h6>
							<span><?php echo esc_html(MP_Global_Function::date_format($date, 'time')); ?></span>
						</li>
						<?php if ($return && $return > 1) { ?>
							<li>
								<h6 class="_mR_xs"><?php esc_html_e('Transfer Type', 'ecab-taxi-booking-manager'); ?> :</h6>
								<span><?php esc_html_e('Return', 'ecab-taxi-booking-manager'); ?></span>
							</li>

							<?php if (MP_Global_Function::get_settings('mptbm_general_settings', 'enable_return_in_different_date') == 'yes') {

								$return_date = array_key_exists('mptbm_return_target_date', $cart_item) ? $cart_item['mptbm_return_target_date'] : '';
								$return_time = array_key_exists('mptbm_return_target_time', $cart_item) ? $cart_item['mptbm_return_target_time'] : '';
								if ($return_time !== "") {
									if ($return_time !== "0") {
										// Convert start time to hours and minutes
										list($hours, $decimal_part) = explode('.', $return_time);
										$interval_time = MPTBM_Function::get_general_settings('mptbm_pickup_interval_time');
										if ($interval_time == "5" || $interval_time == "15") {
											$minutes = isset($decimal_part) ? (int) $decimal_part * 1 : 0; // Multiply by 1 to convert to minutes
										} else {
											$minutes = isset($decimal_part) ? (int) $decimal_part * 10 : 0; // Multiply by 10 to convert to minutes
										}
									} else {
										$hours = 0;
										$minutes = 0;
									}
								} else {
									$hours = 0;
									$minutes = 0;
								}

								// Format hours and minutes
								$return_time_formatted = sprintf('%02d:%02d', $hours, $minutes);

								// Combine date and time if both are available
								$return_date_time = $return_date ? gmdate("Y-m-d", strtotime($return_date)) : "";
								if ($return_date_time && $return_time !== "") {
									$return_date_time .= " " . $return_time_formatted;
								}



							?>
								<li>
									<span class="far fa-calendar-alt"></span>
									<h6 class="_mR_xs"><?php esc_html_e('Return Date', 'ecab-taxi-booking-manager'); ?> :</h6>
									<span><?php echo esc_html(MP_Global_Function::date_format($return_date_time)); ?></span>
								</li>
								<li>
									<span class="far fa-clock"></span>
									<h6 class="_mR_xs"><?php esc_html_e('Return Time', 'ecab-taxi-booking-manager'); ?> :</h6>
									<span><?php echo esc_html(MP_Global_Function::date_format($return_date_time, 'time')); ?></span>
								</li>
							<?php } ?>
						<?php } ?>
						<?php if ($waiting_time && $waiting_time > 0) { ?>
							<li>
								<h6 class="_mR_xs"><?php esc_html_e('Extra Waiting Hours', 'ecab-taxi-booking-manager'); ?> :</h6>
								<span><?php echo esc_html($waiting_time); ?><?php esc_html_e('Hours', 'ecab-taxi-booking-manager'); ?></span>
							</li>
						<?php } ?>
						<?php if ($fixed_time && $fixed_time > 0) { ?>
							<li>
								<h6 class="_mR_xs"><?php esc_html_e('Service Times', 'ecab-taxi-booking-manager'); ?> :</h6>
								<span><?php echo esc_html($fixed_time); ?><?php esc_html_e('Hours', 'ecab-taxi-booking-manager'); ?></span>
							</li>
						<?php } ?>
						<?php 
						$show_passengers = MP_Global_Function::get_settings('mptbm_general_settings', 'show_number_of_passengers', 'yes');
						if ($show_passengers === 'yes') { 
						?>
						<li>
							<span class="fas fa-users"></span>
							<h6 class="_mR_xs"><?php esc_html_e('Number of Passengers', 'ecab-taxi-booking-manager'); ?> :</h6>
							<span><?php echo esc_html($cart_item['mptbm_passengers']); ?></span>
						</li>
						<?php } ?>
						<li>
							<span class="fa fa-tag"></span>
							<h6 class="_mR_xs"><?php esc_html_e('Base Price : ', 'ecab-taxi-booking-manager'); ?></h6>
							<span><?php echo wp_kses_post(wc_price($base_price)); ?></span>
						</li>
						<?php do_action('mptbm_cart_item_display', $cart_item, $post_id); ?>
					</ul>
				</div>
				<?php if (sizeof($extra_service) > 0) { ?>
					<h5 class="_mB_xs"><?php esc_html_e('Extra Services', 'ecab-taxi-booking-manager'); ?></h5>
					<?php foreach ($extra_service as $service) { ?>
						<div class="dLayout_xs">
							<ul class="cart_list">
								<li>
									<h6 class="_mR_xs"><?php esc_html_e('Name : ', 'ecab-taxi-booking-manager'); ?></h6>
									<span><?php echo esc_html($service['service_name']); ?></span>
								</li>
								<li>
									<h6 class="_mR_xs"><?php esc_html_e('Quantity : ', 'ecab-taxi-booking-manager'); ?></h6>
									<span><?php echo esc_html($service['service_quantity']); ?></span>
								</li>
								<li>
									<h6 class="_mR_xs"><?php esc_html_e('Price : ', 'ecab-taxi-booking-manager'); ?></h6>
									<span><?php echo esc_html(' ( ') . wp_kses_post(wc_price($service['service_price'])) . esc_html(' X ') . esc_html($service['service_quantity']) . esc_html(' ) =') . wp_kses_post(wc_price($service['service_price'] * $service['service_quantity'])); ?></span>
								</li>
							</ul>
						</div>
					<?php } ?>
				<?php } ?>
				<?php do_action('mptbm_after_cart_item_display', $cart_item, $post_id); ?>
			</div>
			<?php
		}
		public function wc_order_status_change($order_status, $post_id, $order_id)
		{
			$args = array(
				'post_type' => 'mptbm_booking',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						array(
							'key' => 'mptbm_id',
							'value' => $post_id,
							'compare' => '='
						),
						array(
							'key' => 'mptbm_order_id',
							'value' => $order_id,
							'compare' => '='
						)
					)
				)
			);
			$loop = new WP_Query($args);
			foreach ($loop->posts as $user) {
				$user_id = $user->ID;
				update_post_meta($user_id, 'mptbm_order_status', $order_status);
			}
			$args = array(
				'post_type' => 'mptbm_service_booking',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						array(
							'key' => 'mptbm_id',
							'value' => $post_id,
							'compare' => '='
						),
						array(
							'key' => 'mptbm_order_id',
							'value' => $order_id,
							'compare' => '='
						)
					)
				)
			);
			$loop = new WP_Query($args);
			foreach ($loop->posts as $user) {
				$user_id = $user->ID;
				update_post_meta($user_id, 'mptbm_order_status', $order_status);
			}
		}
		//**********************//
		public static function cart_extra_service_info($post_id): array
		{
			$start_date = isset($_POST['mptbm_date']) ? sanitize_text_field($_POST['mptbm_date']) : '';
			$service_name = isset($_POST['mptbm_extra_service']) ? array_map('sanitize_text_field', $_POST['mptbm_extra_service']) : [];
			$service_quantity = isset($_POST['mptbm_extra_service_qty']) ? array_map('sanitize_text_field', $_POST['mptbm_extra_service_qty']) : [];
			$extra_service = array();
			if (sizeof($service_name) > 0) {
				for ($i = 0; $i < count($service_name); $i++) {
					if ($service_name[$i] && $service_quantity[$i] > 0) {
						$price = MPTBM_Function::get_extra_service_price_by_name($post_id, $service_name[$i]);
						$wc_price = MP_Global_Function::wc_price($post_id, $price);
						$raw_price = MP_Global_Function::price_convert_raw($wc_price);
						$extra_service[$i]['service_name'] = $service_name[$i];
						$extra_service[$i]['service_quantity'] = $service_quantity[$i];
						$extra_service[$i]['service_price'] = $raw_price;
						$extra_service[$i]['mptbm_date'] = $start_date ?? '';
					}
				}
			}
			return $extra_service;
		}
		public function get_cart_total_price($post_id)
		{
			$distance = isset($_COOKIE['mptbm_distance']) ? absint($_COOKIE['mptbm_distance']) : '';
			$duration = isset($_COOKIE['mptbm_duration']) ? absint($_COOKIE['mptbm_duration']) : '';
			$start_place = isset($_POST['mptbm_start_place']) ? sanitize_text_field($_POST['mptbm_start_place']) : '';
			$end_place = isset($_POST['mptbm_end_place']) ? sanitize_text_field($_POST['mptbm_end_place']) : '';
			$waiting_time = isset($_POST['mptbm_waiting_time']) ? sanitize_text_field($_POST['mptbm_waiting_time']) : 0;
			$return = isset($_POST['mptbm_taxi_return']) ? sanitize_text_field($_POST['mptbm_taxi_return']) : 1;
			$fixed_hour = isset($_POST['mptbm_fixed_hours']) ? sanitize_text_field($_POST['mptbm_fixed_hours']) : 0;
			$price = MPTBM_Function::get_price($post_id, $distance, $duration, $start_place, $end_place, $waiting_time, $return, $fixed_hour);
			$wc_price = MP_Global_Function::wc_price($post_id, $price);
			$raw_price = MP_Global_Function::price_convert_raw($wc_price);
			$service_name = isset($_POST['mptbm_extra_service']) ? array_map('sanitize_text_field', $_POST['mptbm_extra_service']) : [];
			$service_quantity = isset($_POST['mptbm_extra_service_qty']) ? array_map('absint', $_POST['mptbm_extra_service_qty']) : [];
			if (sizeof($service_name) > 0) {
				for ($i = 0; $i < count($service_name); $i++) {
					if ($service_name[$i]) {
						if (array_key_exists($i, $service_quantity) && isset($service_quantity[$i])) {
							$raw_price = $raw_price + MPTBM_Function::get_extra_service_price_by_name($post_id, $service_name[$i]) * $service_quantity[$i];
						} else {
							$raw_price = $raw_price + MPTBM_Function::get_extra_service_price_by_name($post_id, $service_name[$i]);
						}
					}
				}
			}
			$wc_price = MP_Global_Function::wc_price($post_id, $raw_price);
			return MP_Global_Function::price_convert_raw($wc_price);
		}
		public static function add_cpt_data($cpt_name, $title, $meta_data = array(), $status = 'publish', $cat = array())
		{
			$new_post = array(
				'post_title' => $title,
				'post_content' => '',
				'post_category' => $cat,
				'tags_input' => array(),
				'post_status' => $status,
				'post_type' => $cpt_name
			);

			$post_id = wp_insert_post($new_post);
			if (sizeof($meta_data) > 0) {
				foreach ($meta_data as $key => $value) {
					update_post_meta($post_id, $key, $value);
				}
			}
			if ($cpt_name == 'mptbm_booking') {
				$mptbm_pin = $meta_data['mptbm_user_id'] . $meta_data['mptbm_order_id'] . $meta_data['mptbm_id'] . $post_id;
				update_post_meta($post_id, 'mptbm_pin', $mptbm_pin);
			}
		}
		/****************************/
		public function mptbm_add_to_cart()
		{
			$link_id = absint($_POST['link_id']);
			$product_id = apply_filters('woocommerce_add_to_cart_product_id', $link_id);
			$quantity = 1;
			$passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
			$product_status = get_post_status($product_id);
			WC()->cart->empty_cart();
			ob_start();
			if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity) && 'publish' === $product_status) {
				$checkout_system = MP_Global_Function::get_settings('mptbm_general_settings', 'single_page_checkout', 'yes');
				if ($checkout_system == 'yes') {
					echo wc_get_checkout_url();
				} else {
			?>
					<div class="dLayout woocommerce-page">
						<?php echo do_shortcode('[woocommerce_checkout]'); ?>
						<?php //do_action('woocommerce_ajax_checkout');
						?>
					</div>
					<!-- <div class="divider"></div>
                    <div class="justifyBetween">
                        <button type="button" class="_themeButton_min_200 mptbm_summary_prev">
                            <span>&larr; &nbsp;<?php esc_html_e('Previous', 'ecab-taxi-booking-manager'); ?></span>
                        </button>
                        <div></div>
                    </div> -->
<?php
				}
			}
			echo ob_get_clean();
			die();
		}
	}
	new MPTBM_Woocommerce();
}
