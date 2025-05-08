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
			}
			public static function woocommerce_default_checkout_fields() {
				return array
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
							"placeholder" =>__( "House number and street name",'ecab-taxi-booking-manager'),
							"required" => "1",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"autocomplete" => "address-line1",
							"priority" => "50",
						),
						"shipping_address_2" => array(
							"label" => __("Apartment, suite, unit, etc.",'ecab-taxi-booking-manager'),
							"label_class" => array(
								"0" => "screen-reader-text",
							),
							"placeholder" => __("Apartment, suite, unit, etc. (optional)",'ecab-taxi-booking-manager'),
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
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
							"priority" => "70"
						),
						"shipping_state" => array(
							"type" => "state",
							"label" => __("State / County",'ecab-taxi-booking-manager'),
							"required" => "",
							"class" => array(
								"0" => "form-row-wide",
								"1" => "address-field",
							),
							"validate" => array(
								"0" => "state",
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
					"order" => array(
						"order_comments" => array(
							"type" => "textarea",
							"class" => array(
								"0" => "notes",
							),
							"label" => __("Order notes",'ecab-taxi-booking-manager'),
							"placeholder" => __("Notes about your order, e.g. special notes for delivery.",'ecab-taxi-booking-manager'),
						)
					),
				);
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

				// Add filter to modify checkout fields
				add_filter('woocommerce_checkout_fields', array($this, 'add_custom_checkout_fields'));

				// Check if pro version is active
				if (!class_exists('MPTBM_Pro_Wc_Checkout_Fields')) {
					// Only add these hooks if pro version is not active
					add_filter('woocommerce_checkout_fields', array($this, 'remove_file_fields_from_checkout'), 5);
					add_action('woocommerce_after_checkout_billing_form', array($this, 'file_upload_field'));
					add_action('woocommerce_after_checkout_shipping_form', array($this, 'file_upload_field'));
					add_action('woocommerce_after_checkout_order_form', array($this, 'file_upload_field'));
				}

				add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_checkout_fields_to_order'), 99, 2);
				add_action('woocommerce_before_order_details', array($this, 'order_details'), 99, 1);
				add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'order_details'), 99, 1);
				add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'order_details'), 99, 1);
			}
			public static function get_checkout_fields_for_list() {
				$fields = array();
				$checkout_fields = self::$default_woocommerce_checkout_fields;
				$fields['billing'] = $checkout_fields['billing'];
				$fields['shipping'] = $checkout_fields['shipping'];
				$fields['order'] = $checkout_fields['order'];
				if (isset(self::$settings_options) && is_array(self::$settings_options)) {
					foreach (self::$settings_options as $key => $key_fields) {
						if (is_array($key_fields)) {
							foreach ($key_fields as $name => $field_array) {
								if (self::check_deleted_field($key, $name)) {
									unset($fields[$key][$name]);
								} else {
									$fields[$key][$name] = $field_array;
								}
							}
						}
					}
				}
				if (isset($checkout_fields) && is_array($checkout_fields)) {
					foreach ($checkout_fields as $key => $key_fields) {
						if (is_array($key_fields)) {
							foreach ($key_fields as $name => $field_array) {
								if (self::check_disabled_field($key, $name)) {
									$fields[$key][$name]['disabled'] = '1';
								}
							}
						}
					}
				}
				return $fields;
			}
			public function get_checkout_fields_for_checkout() {
				$custom_fields = array();
				$options = get_option('mptbm_custom_checkout_fields');
				
				// Initialize with default fields
				$custom_fields = self::$default_woocommerce_checkout_fields;

				if (is_array($options)) {
					// Add custom fields and override default fields
					foreach ($options as $key => $section_fields) {
						if (!isset($custom_fields[$key])) {
							$custom_fields[$key] = array();
						}
						
						if (is_array($section_fields)) {
							foreach ($section_fields as $name => $field) {
								// Skip if field is marked as deleted or disabled
								if ((isset($field['deleted']) && $field['deleted'] == 'deleted') ||
									(isset($field['disabled']) && $field['disabled'] == '1')) {
									continue;
								}
								
								// Add or override the field
								$custom_fields[$key][$name] = $field;
							}
						}
					}
				}

				return $custom_fields;
			}
			public static function hide_checkout_order_additional_information_section() {
				if (!self::$settings_options || (is_array(self::$settings_options) && ((!array_key_exists('hide_checkout_order_additional_information', self::$settings_options)) || (array_key_exists('hide_checkout_order_additional_information', self::$settings_options) && self::$settings_options['hide_checkout_order_additional_information'] == 'on')))) {
					return true;
				}
			}
			public static function hide_checkout_order_review_section() {
				if ((is_array(self::$settings_options) && ((array_key_exists('hide_checkout_order_review', self::$settings_options) && self::$settings_options['hide_checkout_order_review'] == 'on')))) {
					return true;
				}
			}
			public static function check_deleted_field($key, $name) {
				if ((isset(self::$settings_options[$key][$name]) && (isset(self::$settings_options[$key][$name]['deleted']) && self::$settings_options[$key][$name]['deleted'] == 'deleted'))) {
					return true;
				} else {
					return false;
				}
			}
			public static function check_disabled_field($key, $name) {
				// Default fields that should be disabled
				$default_disabled_field = array('billing' => array('billing_company' => '', 'billing_country' => '', 'billing_address_1' => '', 'billing_address_2' => '', 'billing_city' => '', 'billing_state' => '', 'billing_postcode' => ''));

				// Special fields that should never be disabled
				// Include both formats (with space and with underscore)
				$special_fields = array(
					'billing_Passport_No', 'billing_Flight_No',
					'billing_Passport No', 'billing_Flight No'
				);

				// Check if it's a special field
				if (in_array($name, $special_fields)) {
					return false;
				}

				// Also check for variations of the field name
				$name_with_space = str_replace('_No', ' No', $name);
				$name_with_underscore = str_replace(' No', '_No', $name);

				if (in_array($name_with_space, $special_fields) || in_array($name_with_underscore, $special_fields)) {
					return false;
				}

				// Check if field is disabled in settings
				if (isset(self::$settings_options[$key][$name]) && isset(self::$settings_options[$key][$name]['disabled']) && self::$settings_options[$key][$name]['disabled'] == '1') {
					return true;
				}

				// Check if field is in default disabled fields
				if (!isset(self::$settings_options[$key][$name]) && isset($default_disabled_field[$key][$name])) {
					return true;
				}

				// Field is not disabled
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
				// Check if it's a custom field that's not a file field
				$is_custom_field = is_array($field) &&
					(isset($field['custom_field']) && $field['custom_field'] == '1') &&
					(isset($field['type']) && $field['type'] != 'file');

				// Also include special fields (Passport No and Flight No)
				$is_special_field = false;
				if (is_array($field) && isset($field['label'])) {
					$special_labels = array('Passport No', 'Flight No', 'Passport_No', 'Flight_No');
					foreach ($special_labels as $label) {
						if (stripos($field['label'], $label) !== false) {
							$is_special_field = true;
							break;
						}
					}
				}

				return $is_custom_field || $is_special_field;
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
			public function file_upload_field_element($fields) {
				foreach ($fields as $key => $field) {
					?>
                    <p class="form-row form-row-wide <?php echo esc_attr(isset($field['required']) && $field['required'] == '1' ? ' validate-required ' : ''); ?> <?php echo esc_attr(isset($field['validate']) && is_array($field['validate']) && count($field['validate']) ? implode(' validate-', $field['validate']) : ''); ?>" id="<?php echo esc_attr($key . '_field'); ?>" data-priority="<?php echo esc_attr(isset($field['priority']) ? $field['priority'] : ''); ?>">
                        <label for="<?php echo esc_attr($key); ?>"><?php echo wp_kses_post($field['label']); ?><?php echo isset($field['required']) && $field['required'] == '1' ? ' <abbr class="required" title="' . esc_attr__('required', 'ecab-taxi-booking-manager') . '">*</abbr>' : ''; ?></label>
                        <span class="woocommerce-input-wrapper">
                            <input type="file"
                                id="<?php echo esc_attr($key); ?>"
                                name="<?php echo esc_attr($key); ?>"
                                class="input-text"
                                <?php echo isset($field['required']) && $field['required'] == '1' ? 'required' : ''; ?>
                                accept=".jpg,.jpeg,.png,.pdf"
                            />
                        </span>
                    </p>
					<?php
				}
			}
			function save_custom_checkout_fields_to_order($order_id, $data) {
				$checkout_key_fields = $this->get_checkout_fields_for_checkout();

				// First, save special fields
				$special_fields = array(
					'billing_Passport_No', 'billing_Flight_No',
					'billing_Passport No', 'billing_Flight No'
				);

				foreach ($special_fields as $field_key) {
					if (isset($_POST[$field_key]) && !empty($_POST[$field_key])) {
						// Save with both formats (with and without underscore prefix)
						update_post_meta($order_id, $field_key, sanitize_text_field($_POST[$field_key]));
						update_post_meta($order_id, '_' . $field_key, sanitize_text_field($_POST[$field_key]));

						// Also save with normalized key (spaces replaced with underscores)
						$normalized_key = str_replace(' ', '_', $field_key);
						if ($normalized_key !== $field_key) {
							update_post_meta($order_id, $normalized_key, sanitize_text_field($_POST[$field_key]));
							update_post_meta($order_id, '_' . $normalized_key, sanitize_text_field($_POST[$field_key]));
						}
					}
				}

				// Then process all checkout fields
				foreach ($checkout_key_fields as $section => $checkout_fields) {
					if (is_array($checkout_fields) && count($checkout_fields)) {
						$checkout_other_fields = array_filter($checkout_fields, array($this, 'get_other_fields'));
						foreach ($checkout_other_fields as $field_key => $field) {
							if (isset($_POST[$field_key])) {
								// Save with both formats (with and without underscore prefix)
								update_post_meta($order_id, $field_key, sanitize_text_field($_POST[$field_key]));
								update_post_meta($order_id, '_' . $field_key, sanitize_text_field($_POST[$field_key]));
							}
						}

						if (in_array('file', array_column($checkout_fields, 'type'))) {
							$checkout_file_fields = array_filter($checkout_fields, array($this, 'get_file_fields'));

							foreach ($checkout_file_fields as $key => $field) {
								$image_url = $this->get_uploaded_image_link($key);

								if ($image_url) {
									update_post_meta($order_id, '_' . $key, esc_url($image_url));
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
					return false;
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
								// Try different ways to get the field value
								$key_value = '';

								// First try with underscore prefix
								$key_value = get_post_meta($order->get_id(), '_' . $name, true);

								// If empty, try without underscore prefix
								if (empty($key_value)) {
									$key_value = get_post_meta($order->get_id(), $name, true);
								}

								// If still empty and it's a special field, try with normalized key
								if (empty($key_value) && in_array($name, is_array($special_fields) ? $special_fields : [])) {

									// Try with spaces replaced by underscores
									$normalized_key = str_replace(' ', '_', $name);
									if ($normalized_key !== $name) {
										$key_value = get_post_meta($order->get_id(), $normalized_key, true);
										if (empty($key_value)) {
											$key_value = get_post_meta($order->get_id(), '_' . $normalized_key, true);
										}
									}

									// Try with underscores replaced by spaces
									if (empty($key_value)) {
										$normalized_key = str_replace('_No', ' No', $name);
										if ($normalized_key !== $name) {
											$key_value = get_post_meta($order->get_id(), $normalized_key, true);
											if (empty($key_value)) {
												$key_value = get_post_meta($order->get_id(), '_' . $normalized_key, true);
											}
										}
									}
								}

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
			 * Add custom checkout fields to WooCommerce checkout
			 */
			public function add_custom_checkout_fields($fields) {
				$custom_fields = $this->get_checkout_fields_for_checkout();
				
				// Merge custom fields with existing fields
				foreach ($custom_fields as $key => $section_fields) {
					if (!isset($fields[$key])) {
						$fields[$key] = array();
					}
					
					if (is_array($section_fields)) {
						foreach ($section_fields as $field_key => $field) {
							// Special handling for Passport No and Flight No fields
							$is_special_field = false;
							$special_fields = array(
								'billing_Passport_No', 'billing_Flight_No',
								'billing_Passport No', 'billing_Flight No'
							);
							
							// Check both formats of the field key
							$field_key_with_space = str_replace('_No', ' No', $field_key);
							$field_key_with_underscore = str_replace(' No', '_No', $field_key);
							
							if (in_array($field_key, $special_fields) || 
								in_array($field_key_with_space, $special_fields) || 
								in_array($field_key_with_underscore, $special_fields)) {
								$is_special_field = true;
							}
							
							// Always include special fields
							if ($is_special_field) {
								$fields[$key][$field_key] = $field;
								continue;
							}
							
							// Skip if the field is disabled or deleted (unless it's a special field)
							if (!$is_special_field && 
								((isset($field['disabled']) && $field['disabled'] == '1') ||
								(isset($field['deleted']) && $field['deleted'] == 'deleted'))) {
								continue;
							}
							
							// Add the field
							$fields[$key][$field_key] = $field;
						}
					}
				}
				
				return $fields;
			}
		}
		new MPTBM_Wc_Checkout_Fields_Helper();
	}