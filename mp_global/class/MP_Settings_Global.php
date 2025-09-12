<?php
	/*
* @Author 		engr.sumonazma@gmail.com
* Copyright: 	mage-people.com
*/
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	if (!class_exists('MP_Settings_Global')) {
		class MP_Settings_Global {
			public function __construct() {
				add_filter('mp_settings_sec_reg', array($this, 'settings_sec_reg'), 10, 1);
				add_filter('mp_settings_sec_reg', array($this, 'global_sec_reg'), 90, 1);
				add_filter('mp_settings_sec_fields', array($this, 'settings_sec_fields'), 10, 1);
				add_action('wsa_form_bottom_mp_basic_license_settings', [$this, 'license_settings'], 5);
				add_action('mp_basic_license_list', [$this, 'licence_area']);
			}
			public function settings_sec_reg($default_sec): array {
				$sections = array(
					array(
						'id' => 'mp_global_settings',
						'icon' => 'fas fa-cogs',
						'title' => esc_html__('Global Settings', 'ecab-taxi-booking-manager')
					),
				);
				return array_merge($default_sec, $sections);
			}
            public function global_sec_reg($default_sec): array {
				$sections = array(
					array(
						'id' => 'mp_style_settings',
						'icon' => 'fas fa-palette',
						'title' => esc_html__('Style Settings', 'ecab-taxi-booking-manager')
					),
					array(
						'id' => 'mp_add_custom_css',
						'icon' => 'fas fa-file-code',
						'title' => esc_html__('Custom CSS', 'ecab-taxi-booking-manager')
					),
					array(
						'id' => 'mp_basic_license_settings',
						'icon' => 'fas fa-certificate',
						'title' => esc_html__('Mage-People License', 'ecab-taxi-booking-manager')
					)
				);
				return array_merge($default_sec, $sections);
			}
			public function settings_sec_fields($default_fields): array {
				$current_date = current_time('Y-m-d');
				$settings_fields = array(
					'mp_global_settings' => apply_filters('filter_mp_global_settings', array(
						array(
							'name' => 'enable_rest_api',
							'label' => esc_html__('Enable Rest API', 'ecab-taxi-booking-manager'),
							'type' => 'select',
							'default' => 'off',
							'options' => array(
								'on' => esc_html__('On', 'ecab-taxi-booking-manager'),
								'off' => esc_html__('Off', 'ecab-taxi-booking-manager')
							)
						),
						array(
							'name' => 'api_authentication_type',
							'label' => esc_html__('API Authentication', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Choose the authentication method for the REST API. Application Passwords use WordPress built-in authentication, while Custom API Key provides a simple key-based authentication.', 'ecab-taxi-booking-manager'),
							'type' => 'select',
							'default' => 'application_password',
							'options' => array(
								'application_password' => esc_html__('Application Passwords (Recommended)', 'ecab-taxi-booking-manager'),
								'custom_api_key' => esc_html__('Custom API Key', 'ecab-taxi-booking-manager'),
							)
						),
                array(
                    'name' => 'api_custom_key',
                    'label' => esc_html__('Custom API Key', 'ecab-taxi-booking-manager'),
                    'desc' => esc_html__('Enter a custom API key for authentication. This key must be included in API requests as "X-API-Key" header. Required when Custom API Key authentication is selected.', 'ecab-taxi-booking-manager'),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => 'Enter your custom API key',
                    'data-depends' => '[{"mp_global_settings[api_authentication_type]": {"type": "equal", "value": "custom_api_key"}}]'
                ),
						array(
							'name' => 'api_rate_limit',
							'label' => esc_html__('API Rate Limit', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Number of requests allowed per minute (0 for unlimited)', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '60',
							'min' => '0',
							'max' => '1000'
						),
						array(
							'name' => 'disable_block_editor',
							'label' => esc_html__('Disable Block/Gutenberg Editor', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('If you want to disable WordPress\'s new Block/Gutenberg editor, please select Yes.', 'ecab-taxi-booking-manager'),
							'type' => 'select',
							'default' => 'yes',
							'options' => array(
								'yes' => esc_html__('Yes', 'ecab-taxi-booking-manager'),
								'no' => esc_html__('No', 'ecab-taxi-booking-manager')
							)
						),
						array(
							'name' => 'date_format',
							'label' => esc_html__('Date Picker Format', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('If you want to change Date Picker Format, please select format. Default  is D d M , yy.', 'ecab-taxi-booking-manager'),
							'type' => 'select',
							'default' => 'D d M , yy',
							'options' => array(
								'yy-mm-dd' => $current_date,
								'yy/mm/dd' => date_i18n('Y/m/d', strtotime($current_date)),
								'yy-dd-mm' => date_i18n('Y-d-m', strtotime($current_date)),
								'yy/dd/mm' => date_i18n('Y/d/m', strtotime($current_date)),
								'dd-mm-yy' => date_i18n('d-m-Y', strtotime($current_date)),
								'dd/mm/yy' => date_i18n('d/m/Y', strtotime($current_date)),
								'mm-dd-yy' => date_i18n('m-d-Y', strtotime($current_date)),
								'mm/dd/yy' => date_i18n('m/d/Y', strtotime($current_date)),
								'd M , yy' => date_i18n('j M , Y', strtotime($current_date)),
								'D d M , yy' => date_i18n('D j M , Y', strtotime($current_date)),
								'M d , yy' => date_i18n('M  j, Y', strtotime($current_date)),
								'D M d , yy' => date_i18n('D M  j, Y', strtotime($current_date)),
							)
						),
						array(
							'name' => 'date_format_short',
							'label' => esc_html__('Short Date  Format', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('If you want to change Short Date  Format, please select format. Default  is M , Y.', 'ecab-taxi-booking-manager'),
							'type' => 'select',
							'default' => 'M , Y',
							'options' => array(
								'D , M d' => date_i18n('D , M d', strtotime($current_date)),
								'M , Y' => date_i18n('M , Y', strtotime($current_date)),
								'M , y' => date_i18n('M , y', strtotime($current_date)),
								'M - Y' => date_i18n('M - Y', strtotime($current_date)),
								'M - y' => date_i18n('M - y', strtotime($current_date)),
								'F , Y' => date_i18n('F , Y', strtotime($current_date)),
								'F , y' => date_i18n('F , y', strtotime($current_date)),
								'F - Y' => date_i18n('F - y', strtotime($current_date)),
								'F - y' => date_i18n('F - y', strtotime($current_date)),
								'm - Y' => date_i18n('m - Y', strtotime($current_date)),
								'm - y' => date_i18n('m - y', strtotime($current_date)),
								'm , Y' => date_i18n('m , Y', strtotime($current_date)),
								'm , y' => date_i18n('m , y', strtotime($current_date)),
								'F' => date_i18n('F', strtotime($current_date)),
								'm' => date_i18n('m', strtotime($current_date)),
								'M' => date_i18n('M', strtotime($current_date)),
							)
						),
					)),
					'mp_style_settings' => apply_filters('filter_mp_style_settings', array(
						array(
							'name' => 'theme_color',
							'label' => esc_html__('Theme Color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Default Theme Color', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#F12971'
						),
						array(
							'name' => 'theme_alternate_color',
							'label' => esc_html__('Theme Alternate Color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Default Theme Alternate  Color that means, if background theme color then it will be text color.', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#fff'
						),
						array(
							'name' => 'default_text_color',
							'label' => esc_html__('Default Text Color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Default Text  Color.', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#303030'
						),
						array(
							'name' => 'default_font_size',
							'label' => esc_html__('Default Font Size', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Default Font Size(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '15'
						),
						array(
							'name' => 'font_size_h1',
							'label' => esc_html__('Font Size h1 Title', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size Main Title(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '35'
						),
						array(
							'name' => 'font_size_h2',
							'label' => esc_html__('Font Size h2 Title', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size h2 Title(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '25'
						),
						array(
							'name' => 'font_size_h3',
							'label' => esc_html__('Font Size h3 Title', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size h3 Title(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '22'
						),
						array(
							'name' => 'font_size_h4',
							'label' => esc_html__('Font Size h4 Title', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size h4 Title(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '20'
						),
						array(
							'name' => 'font_size_h5',
							'label' => esc_html__('Font Size h5 Title', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size h5 Title(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '18'
						),
						array(
							'name' => 'font_size_h6',
							'label' => esc_html__('Font Size h6 Title', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size h6 Title(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '16'
						),
						array(
							'name' => 'button_font_size',
							'label' => esc_html__('Button Font Size ', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size Button(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '18'
						),
						array(
							'name' => 'button_color',
							'label' => esc_html__('Button Text Color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Button Text  Color.', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#FFF'
						),
						array(
							'name' => 'button_bg',
							'label' => esc_html__('Button Background Color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Button Background  Color.', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#222'
						),
						array(
							'name' => 'font_size_label',
							'label' => esc_html__('Label Font Size ', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Type Font Size Label(in PX Unit).', 'ecab-taxi-booking-manager'),
							'type' => 'number',
							'default' => '18'
						),
						array(
							'name' => 'warning_color',
							'label' => esc_html__('Warning Color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Warning  Color.', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#E67C30'
						),
						array(
							'name' => 'section_bg',
							'label' => esc_html__('Section Background color', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select Background  Color.', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#FAFCFE'
						),
						array(
							'name' => 'no_transport_bg_color',
							'label' => esc_html__('No Transport Message Background', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select background color for no transport message', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#fff5f5'
						),
						array(
							'name' => 'no_transport_text_color',
							'label' => esc_html__('No Transport Message Text', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select text color for no transport message', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#333333'
						),
						array(
							'name' => 'no_transport_icon_color',
							'label' => esc_html__('No Transport Message Icon', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select icon color for no transport message', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#ff4d4d'
						),
						array(
							'name' => 'no_transport_link_color',
							'label' => esc_html__('No Transport Message Link', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select link color for no transport message', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#007bff'
						),
						array(
							'name' => 'no_transport_contact_bg',
							'label' => esc_html__('No Transport Contact Background', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select background color for contact section', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#f8f9fa'
						),
						array(
							'name' => 'no_transport_contact_icon',
							'label' => esc_html__('No Transport Contact Icon', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Select icon color for contact section', 'ecab-taxi-booking-manager'),
							'type' => 'color',
							'default' => '#28a745'
						),
					)),
					'mp_add_custom_css' => apply_filters('filter_mp_add_custom_css', array(
						array(
							'name' => 'custom_css',
							'label' => esc_html__('Custom CSS', 'ecab-taxi-booking-manager'),
							'desc' => esc_html__('Write Your Custom CSS Code Here', 'ecab-taxi-booking-manager'),
							'type' => 'textarea',
						)
					))
				);
				return array_merge($default_fields, $settings_fields);
			}
			public function license_settings() {
				?>
				<div class="mp_basic_license_settings">
					<h3><?php esc_html_e('Mage-People License', 'ecab-taxi-booking-manager'); ?></h3>
					<div class="_dFlex">
						<span class="fas fa-info-circle _mR_xs"></span>
						<i><?php esc_html_e('Thanking you for using our Mage-People plugin. Our some plugin  free and no license is required. We have some Additional addon to enhance feature of this plugin functionality. If you have any addon you need to enter a valid license for that plugin below.', 'ecab-taxi-booking-manager'); ?>                    </i>
					</div>
					<div class="divider"></div>
					<div class="dLayout mp_basic_license_area">
						<?php $this->licence_area(); ?>
					</div>
				</div>
				<?php
			}
			public function licence_area(){
				do_action('ecab_before_global_setting_page');
				?>
				<table>
					<thead>
					<tr>
						<th colspan="4"><?php esc_html_e('Plugin Name', 'ecab-taxi-booking-manager'); ?></th>
						<th><?php esc_html_e('Type', 'ecab-taxi-booking-manager'); ?></th>
						<th><?php esc_html_e('Order No', 'ecab-taxi-booking-manager'); ?></th>
						<th colspan="2"><?php esc_html_e('Expire on', 'ecab-taxi-booking-manager'); ?></th>
						<th colspan="3"><?php esc_html_e('License Key', 'ecab-taxi-booking-manager'); ?></th>
						<th><?php esc_html_e('Status', 'ecab-taxi-booking-manager'); ?></th>
						<th colspan="2"><?php esc_html_e('Action', 'ecab-taxi-booking-manager'); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php do_action('mp_license_page_plugin_list'); ?>
					</tbody>
				</table>
				<?PHP
				do_action('ecab_after_global_setting_page');
			}
		}
		new MP_Settings_Global();
	}
