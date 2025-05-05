<?php
/*
 * @Author 		engr.sumonazma@gmail.com
 * Copyright: 	mage-people.com
 */
if (!defined('ABSPATH')) {
    die;
} // Cannot access pages directly

$post_id = $post_id ?? '';
$original_price_based = $price_based ?? '';
$feature_class = ''; // Default empty value
if (MP_Global_Function::get_settings('mptbm_general_settings', 'enable_filter_via_features') == 'yes') {
    $max_passenger = MP_Global_Function::get_post_info($post_id, 'mptbm_maximum_passenger');
    $max_bag = MP_Global_Function::get_post_info($post_id, 'mptbm_maximum_bag');
    if ($max_passenger != '' && $max_bag != '') {
        $feature_class = 'feature_passenger_'.$max_passenger.'_feature_bag_'.$max_bag.'_post_id_'.$post_id;
    }else{
        $feature_class = '';
    }
}

// Get display features setting
$display_features = MP_Global_Function::get_post_info($post_id, 'display_mptbm_features', 'on');

$all_features = MP_Global_Function::get_post_info($post_id, 'mptbm_features');

$fixed_time = $fixed_time ?? 0;
$start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
$start_date = $start_date ? date('Y-m-d', strtotime($start_date)) : '';
$start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
$all_dates = MPTBM_Function::get_date($post_id);

// Check if inventory is enabled
$enable_inventory = MP_Global_Function::get_post_info($post_id, 'mptbm_enable_inventory', 'no');
$total_quantity = 1;
$available_quantity = 1;

if ($enable_inventory == 'yes') {
    // Get booking interval time from transport settings
    $booking_interval_time = MP_Global_Function::get_post_info($post_id, 'mptbm_booking_interval_time', 0);

    // Calculate available quantity based on overlapping bookings
    $total_quantity = MP_Global_Function::get_post_info($post_id, 'mptbm_quantity', 1);
    $available_quantity = $total_quantity;
    if ($start_date && $start_time) {
        // Debug output for desired booking
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">';
        echo '<h4>Debug Information:</h4>';
        echo '<p><strong>Desired Booking:</strong></p>';
        echo '<p>Post ID: ' . esc_html($post_id) . '</p>';
        echo '<p>Date: ' . esc_html($start_date) . '</p>';
        echo '<p>Time: ' . esc_html($start_time) . '</p>';
        echo '<p>Interval Time: ' . esc_html($booking_interval_time) . ' minutes</p>';
        echo '</div>';

        // Format the time properly
        $hours = floor($start_time);
        $minutes = ($start_time - $hours) * 60;
        $formatted_time = sprintf('%02d:%02d', $hours, $minutes);
        
        // Convert start date and time to timestamp
        $start_datetime = strtotime($start_date . ' ' . $formatted_time);
        
        // Calculate the time range to check (interval time before and after) - now in minutes
        $interval_before = $start_datetime - ($booking_interval_time * 60); // Convert minutes to seconds
        $interval_after = $start_datetime + ($booking_interval_time * 60);
        
        // Debug output for time range
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">';
        echo '<p><strong>Time Range Check:</strong></p>';
        echo '<p>Formatted Time: ' . esc_html($formatted_time) . '</p>';
        echo '<p>Start Time: ' . date('Y-m-d H:i:s', $start_datetime) . '</p>';
        echo '<p>Interval Before: ' . date('Y-m-d H:i:s', $interval_before) . '</p>';
        echo '<p>Interval After: ' . date('Y-m-d H:i:s', $interval_after) . '</p>';
        echo '</div>';

        // Get all bookings that could overlap with our time range
        $query = new WP_Query([
            'post_type' => 'mptbm_booking',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'mptbm_id',
                    'value' => $post_id,
                    'compare' => '='
                ]
            ]
        ]);

        if ($query->have_posts()) {
            echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">';
            echo '<p><strong>Existing Bookings:</strong></p>';
            
            while ($query->have_posts()) {
                $query->the_post();
                $booking_datetime = get_post_meta(get_the_ID(), 'mptbm_date', true);
                $booking_transport_quantity = get_post_meta(get_the_ID(), 'mptbm_transport_quantity', true);
                $booking_transport_quantity = $booking_transport_quantity ? absint($booking_transport_quantity) : 1;
                
                // Convert booking datetime to timestamp
                $booking_timestamp = strtotime($booking_datetime);
                
                // Debug output for each booking
                echo '<div style="margin: 10px 0; padding: 10px; border: 1px solid #ccc;">';
                echo '<p>Booking ID: ' . get_the_ID() . '</p>';
                echo '<p>Booking DateTime: ' . esc_html($booking_datetime) . '</p>';
                echo '<p>Booking Timestamp: ' . date('Y-m-d H:i:s', $booking_timestamp) . '</p>';
                echo '<p>Transport Quantity: ' . esc_html($booking_transport_quantity) . '</p>';
                
                // Check if booking time falls within our interval range
                $is_in_range = ($booking_timestamp >= $interval_before && $booking_timestamp <= $interval_after);
                echo '<p>Is in Range: ' . ($is_in_range ? 'Yes' : 'No') . '</p>';
                
                if ($is_in_range) {
                    $available_quantity -= $booking_transport_quantity;
                    echo '<p style="color: red;">Quantity reduced by: ' . esc_html($booking_transport_quantity) . '</p>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
        wp_reset_postdata();

        // Debug output for final quantity
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">';
        echo '<p><strong>Final Quantity Calculation:</strong></p>';
        echo '<p>Total Quantity: ' . esc_html($total_quantity) . '</p>';
        echo '<p>Available Quantity: ' . esc_html($available_quantity) . '</p>';
        echo '</div>';
    }
}

$mptbm_enable_view_search_result_page  = MP_Global_Function::get_settings('mptbm_general_settings', 'enable_view_search_result_page');
if ($mptbm_enable_view_search_result_page == '') {
    $hidden_class = 'mptbm_booking_item_hidden';
} else {
    $hidden_class = '';
}
if (sizeof($all_dates) > 0 && in_array($start_date, $all_dates)) {
    $distance = $distance ?? (isset($_COOKIE['mptbm_distance']) ? absint($_COOKIE['mptbm_distance']) : '');
    $duration = $duration ?? (isset($_COOKIE['mptbm_duration']) ? absint($_COOKIE['mptbm_duration']) : '');
    $label = $label ?? MPTBM_Function::get_name();
    $start_place = $start_place ?? isset($_POST['start_place']) ? sanitize_text_field($_POST['start_place']) : '';
    $end_place = $end_place ?? isset($_POST['end_place']) ? sanitize_text_field($_POST['end_place']) : '';
    $two_way = $two_way ?? 1;
    $waiting_time = $waiting_time ?? 0;
    $location_exit = MPTBM_Function::location_exit($post_id, $start_place, $end_place);
    if ($location_exit && $post_id) {
        $thumbnail = MP_Global_Function::get_image_url($post_id);
        $price = MPTBM_Function::get_price($post_id, $distance, $duration, $start_place, $end_place, $waiting_time, $two_way, $fixed_time);
        if(!$price || $price == 0){
            return false;
        }
        
        $wc_price = MP_Global_Function::wc_price($post_id, $price);
        $raw_price = MP_Global_Function::price_convert_raw($wc_price);
?>
       
        <div class="_dLayout_dFlex mptbm_booking_item  <?php echo 'mptbm_booking_item_' . $post_id; ?> <?php echo $hidden_class; ?> <?php echo $feature_class; ?>" data-placeholder>
            <div class="_max_200_mR">
                <div class="bg_image_area"  data-placeholder>
                    <div data-bg-image="<?php echo esc_attr($thumbnail); ?>"></div>
                </div>
            </div>
            <div class="fdColumn _fullWidth mptbm_list_details">
                <h5><?php echo esc_html(get_the_title($post_id)); ?></h5>
                <div class="justifyBetween _mT_xs">
                    <?php if ($display_features == 'on' && is_array($all_features) && sizeof($all_features) > 0) { ?>
                        <ul class="list_inline_two">
                            <?php
                            foreach ($all_features as $features) {
                                $label = array_key_exists('label', $features) ? $features['label'] : '';
                                $text = array_key_exists('text', $features) ? $features['text'] : '';
                                $icon = array_key_exists('icon', $features) ? $features['icon'] : '';
                                $image = array_key_exists('image', $features) ? $features['image'] : '';
                            ?>
                                <li>
                                    <?php if ($icon) { ?>
                                        <span class="<?php echo esc_attr($icon); ?> _mR_xs"></span>
                                    <?php } ?>
                                    <?php echo esc_html($label); ?>&nbsp;:&nbsp;<?php echo esc_html($text); ?>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div></div>
                    <?php } ?>
                    <div class="_min_150_mL_xs">
                        <h4 class="textCenter"> <?php echo wp_kses_post(wc_price($raw_price)); ?></h4>
                        <?php if (class_exists('MPTBM_Plugin_Pro')) { 
                            if ($enable_inventory == 'yes' && $available_quantity > 1) { ?>
                                <div style="margin-bottom: 2px;" class="textCenter _mT_xs mptbm_quantity_selector mptbm_booking_item_hidden <?php echo 'mptbm_quantity_selector_' . $post_id; ?> ">
                                    <div class="mp_quantity_selector">
                                        <button type="button" class="mp_quantity_minus" data-post-id="<?php echo esc_attr($post_id); ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" 
                                               class="mp_quantity_input" 
                                               name="vehicle_quantity[<?php echo esc_attr($post_id); ?>]" 
                                               value="1" 
                                               min="1" 
                                               max="<?php echo esc_attr($available_quantity); ?>" 
                                               data-post-id="<?php echo esc_attr($post_id); ?>"
                                               readonly />
                                        <button type="button" class="mp_quantity_plus" data-post-id="<?php echo esc_attr($post_id); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                        <?php if ($enable_inventory == 'yes' && $available_quantity > 0) { ?>
                            <button type="button" class="_mpBtn_xs_w_150 mptbm_transport_select" data-transport-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-transport-price="<?php echo esc_attr($raw_price); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" data-open-text="<?php esc_attr_e('Select Car', 'ecab-taxi-booking-manager'); ?>" data-close-text="<?php esc_html_e('Selected', 'ecab-taxi-booking-manager'); ?>" data-open-icon="" data-close-icon="fas fa-check mR_xs">
                                <span class="" data-icon></span>
                                <span data-text><?php esc_html_e('Select Car', 'ecab-taxi-booking-manager'); ?></span>
                            </button>
                        <?php } else if ($enable_inventory == 'yes' && $available_quantity <= 0) { ?>
                            <button type="button" class="_mpBtn_xs_w_150 mptbm_out_of_stock" disabled style="background-color: #ccc; cursor: not-allowed;">
                                <span><?php esc_html_e('Out of Stock', 'ecab-taxi-booking-manager'); ?></span>
                            </button>
                        <?php } else { ?>
                            <button type="button" class="_mpBtn_xs_w_150 mptbm_transport_select" data-transport-name="<?php echo esc_attr(get_the_title($post_id)); ?>" data-transport-price="<?php echo esc_attr($raw_price); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" data-open-text="<?php esc_attr_e('Select Car', 'ecab-taxi-booking-manager'); ?>" data-close-text="<?php esc_html_e('Selected', 'ecab-taxi-booking-manager'); ?>" data-open-icon="" data-close-icon="fas fa-check mR_xs">
                                <span class="" data-icon></span>
                                <span data-text><?php esc_html_e('Select Car', 'ecab-taxi-booking-manager'); ?></span>
                            </button>
                        <?php } ?>
                    </div>
                </div>
                <!-- poro feature used this hook for showing driver's data -->
                <?php do_action('mptbm_booking_item_after_feature',$post_id); ?>
            </div>
        </div>
<?php
    }
}
?>