<?php
	if (!defined('ABSPATH')) {
		die;
	} // Cannot access pages directly.
	/**
	 * Class MPTBM_Wc_Checkout_Billing
	 *
	 * @since 1.0
	 *
	 * */
	if (!class_exists('MPTBM_Wc_Checkout_Billing')) {
		class MPTBM_Wc_Checkout_Billing {
			private $error;
			public function __construct() {
				$this->error = new WP_Error();
				add_action('mptbm_wc_checkout_tab_content', array($this, 'tab_content'), 10, 1);
				add_action('admin_notices', array($this, 'mp_admin_notice'));
			}
			public function tab_content($contents) {
				?>
                <div class="tab-content active" id="mptbm_wc_billing_field_settings">
                    <h2>Woocommerce Billing Fields</h2>
					<?php if (is_plugin_active('service-booking-manager-pro/MPTBM_Plugin_Pro.php')): ?>
						<?php do_action('mptbm_wc_checkout_add', 'billing'); ?>
					<?php else: ?>
						<div class="action-button">
							<a class="button open-modal" data-action="add" data-key="billing">
								<i class="dashicons dashicons-plus-alt2"></i>
								Add Field
							</a>
						</div>
					<?php endif; ?>
                    <!-- <table class="wc_gateways wp-list-table widefat striped"> -->
                    <div>
                        <table class="wc_gateways widefat striped">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Label</th>
                                <th>Type</th>
                                <th>Placeholder</th>
                                <th>Validations</th>
                                <th>Required</th>
                                <th>Disabled</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
							<?php foreach ($contents['billing'] as $key => $checkout_field) : ?>
								<?php $status = '';
								$status = (isset($checkout_field['disabled']) && $checkout_field['disabled'] == '1') ? '' : 'checked'; ?>
                                <tr>
                                    <input id="<?php echo esc_attr(esc_html($key)) ?>" type="hidden" name="<?php echo esc_attr(esc_html($key)) ?>" value="<?php echo esc_attr(esc_html(json_encode(array('name' => $key, 'attributes' => $checkout_field)))) ?>"/>
                                    <td><?php echo esc_html($key); ?></td>
                                    <td><?php echo esc_html(isset($checkout_field['label']) ? $checkout_field['label'] : ''); ?></td>
                                    <td><?php echo esc_html(isset($checkout_field['type']) ? $checkout_field['type'] : ''); ?></td>
                                    <td><?php echo esc_html(isset($checkout_field['placeholder']) ? $checkout_field['placeholder'] : ''); ?></td>
                                    <td><?php echo esc_html(implode(',', (isset($checkout_field['validate']) && is_array($checkout_field['validate'])) ? $checkout_field['validate'] : array())); ?></td>
                                    <td><span class="<?php echo esc_attr(esc_html((isset($checkout_field['required']) && $checkout_field['required'] == '1') ? 'dashicons dashicons-yes tips' : '')); ?>"></span></td>
                                    <td><span class="checkout-disabled <?php echo esc_attr(esc_html((isset($checkout_field['disabled']) && $checkout_field['disabled'] == '1') ? 'dashicons dashicons-yes tips' : '')); ?>"></span></td>
                                    <td>
										<?php if (is_plugin_active('service-booking-manager-pro/MPTBM_Plugin_Pro.php')): ?>
											<?php do_action('mptbm_wc_checkout_action', 'billing', $key, $checkout_field); ?>
										<?php else: ?>
											<?php MPTBM_Wc_Checkout_Fields::switch_button($key, 'checkoutSwitchButton', $key, $status, array('key' => 'billing', 'name' => $key)); ?>
											<?php if(isset($checkout_field['custom_field']) && !isset(MPTBM_Wc_Checkout_Fields_Helper::$default_woocommerce_checkout_fields['billing'][$key])): ?>
												<a class="button button-small button-secondary open-modal" data-action="edit" data-key="billing" data-name="<?php echo esc_attr(esc_html($key))?>">Edit</a>
												<a class="button button-small button-link-delete" href="<?php echo esc_attr(wp_nonce_url(admin_url('edit.php?post_type='.MPTBM_Function::get_cpt().'&page=mptbm_wc_checkout_fields&action=delete&key=billing&name=' . esc_html($key)), 'mptbm_checkout_field_delete', 'mptbm_checkout_field_delete_nonce')); ?>" class="delete" onclick="return confirm(esc_attr('Are you sure you want to delete this field?'))">Delete</a>
											<?php endif; ?>
										<?php endif; ?>
                                    </td>
                                </tr>
							<?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
				<?php
			}
			public function mp_admin_notice() {
				MPTBM_Wc_Checkout_Fields::mp_error_notice($this->error);
			}
		}
		new MPTBM_Wc_Checkout_Billing();
	}