<?php

/**
 * Admin Settings Page
 * 
 * Handles plugin settings registration and admin interface
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register plugin settings
 */
function fmi_register_settings()
{
    register_setting(
        'fmi_settings_group',
        'fmi_funding_calc_options',
        'fmi_sanitize_options'
    );

    add_settings_section(
        'fmi_main_section',
        __('General Settings', 'fmi-funding-calculator'),
        'fmi_main_section_callback',
        'fmi-settings'
    );

    // Training value per employee
    add_settings_field(
        'training_value_per_employee',
        __('Training value per employee (€)', 'fmi-funding-calculator'),
        'fmi_field_training_value_callback',
        'fmi-settings',
        'fmi_main_section'
    );

    // Max reimbursement per employee
    add_settings_field(
        'max_reimbursement_per_employee',
        __('Max reimbursement per employee (€)', 'fmi-funding-calculator'),
        'fmi_field_max_reimbursement_callback',
        'fmi-settings',
        'fmi_main_section'
    );

    // Training funding rate
    add_settings_field(
        'training_funding_rate',
        __('Training funding rate (0-1)', 'fmi-funding-calculator'),
        'fmi_field_training_rate_callback',
        'fmi-settings',
        'fmi_main_section'
    );

    // Default training duration
    add_settings_field(
        'default_training_duration',
        __('Default training duration (months)', 'fmi-funding-calculator'),
        'fmi_field_default_duration_callback',
        'fmi-settings',
        'fmi_main_section'
    );

    // Company size section
    add_settings_section(
        'fmi_company_size_section',
        __('Company Size Reimbursement Rates', 'fmi-funding-calculator'),
        'fmi_company_size_section_callback',
        'fmi-settings'
    );

    // Wage reimbursement for under 50 employees
    add_settings_field(
        'wage_reimbursement_under_50',
        __('Reimbursement rate (under 50 employees)', 'fmi-funding-calculator'),
        'fmi_field_wage_under_50_callback',
        'fmi-settings',
        'fmi_company_size_section'
    );

    // Wage reimbursement for 50-500 employees
    add_settings_field(
        'wage_reimbursement_50_500',
        __('Reimbursement rate (50-500 employees)', 'fmi-funding-calculator'),
        'fmi_field_wage_50_500_callback',
        'fmi-settings',
        'fmi_company_size_section'
    );

    // Wage reimbursement for over 500 employees
    add_settings_field(
        'wage_reimbursement_over_500',
        __('Reimbursement rate (over 500 employees)', 'fmi-funding-calculator'),
        'fmi_field_wage_over_500_callback',
        'fmi-settings',
        'fmi_company_size_section'
    );

    // Age section
    add_settings_section(
        'fmi_age_section',
        __('Age-Based Funding', 'fmi-funding-calculator'),
        'fmi_age_section_callback',
        'fmi-settings'
    );

    // Full funding for employees over 45
    add_settings_field(
        'age_full_funding_over45',
        __('Full funding for employees over 45', 'fmi-funding-calculator'),
        'fmi_field_age_full_funding_callback',
        'fmi-settings',
        'fmi_age_section'
    );
}
add_action('admin_init', 'fmi_register_settings');

/**
 * Section callbacks
 */
function fmi_main_section_callback()
{
    echo '<p>' . __('Configure the basic parameters for funding calculations.', 'fmi-funding-calculator') . '</p>';
}

function fmi_company_size_section_callback()
{
    echo '<p>' . __('Set different wage reimbursement rates based on company size.', 'fmi-funding-calculator') . '</p>';
}

function fmi_age_section_callback()
{
    echo '<p>' . __('Configure special funding rules for employees over 45 years old.', 'fmi-funding-calculator') . '</p>';
}

/**
 * Field callbacks
 */
function fmi_field_training_value_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['training_value_per_employee']) ? esc_attr($options['training_value_per_employee']) : '10000';
    echo '<input type="number" step="0.01" min="0" name="fmi_funding_calc_options[training_value_per_employee]" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">' . __('The value of training per employee in euros.', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_max_reimbursement_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['max_reimbursement_per_employee']) ? esc_attr($options['max_reimbursement_per_employee']) : '7100';
    echo '<input type="number" step="0.01" min="0" name="fmi_funding_calc_options[max_reimbursement_per_employee]" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">' . __('Maximum wage reimbursement cap per employee.', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_training_rate_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['training_funding_rate']) ? esc_attr($options['training_funding_rate']) : '0.5';
    echo '<input type="number" step="0.01" min="0" max="1" name="fmi_funding_calc_options[training_funding_rate]" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">' . __('Percentage of training costs covered by funding (0.5 = 50%).', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_default_duration_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['default_training_duration']) ? esc_attr($options['default_training_duration']) : '12';
    echo '<select name="fmi_funding_calc_options[default_training_duration]" class="regular-text">';
    $durations = array(6, 8, 12, 18, 24);
    foreach ($durations as $duration) {
        $selected = ($value == $duration) ? 'selected' : '';
        echo '<option value="' . $duration . '" ' . $selected . '>' . $duration . ' ' . __('months', 'fmi-funding-calculator') . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . __('Default training duration to be pre-selected in the calculator form.', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_wage_under_50_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['wage_reimbursement_under_50']) ? esc_attr($options['wage_reimbursement_under_50']) : '0.75';
    echo '<input type="number" step="0.01" min="0" max="1" name="fmi_funding_calc_options[wage_reimbursement_under_50]" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">' . __('Wage reimbursement rate for companies with under 50 employees (0.75 = 75%).', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_wage_50_500_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['wage_reimbursement_50_500']) ? esc_attr($options['wage_reimbursement_50_500']) : '0.5';
    echo '<input type="number" step="0.01" min="0" max="1" name="fmi_funding_calc_options[wage_reimbursement_50_500]" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">' . __('Wage reimbursement rate for companies with 50-500 employees (0.5 = 50%).', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_wage_over_500_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $value = isset($options['wage_reimbursement_over_500']) ? esc_attr($options['wage_reimbursement_over_500']) : '0.25';
    echo '<input type="number" step="0.01" min="0" max="1" name="fmi_funding_calc_options[wage_reimbursement_over_500]" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">' . __('Wage reimbursement rate for companies with over 500 employees (0.25 = 25%).', 'fmi-funding-calculator') . '</p>';
}

function fmi_field_age_full_funding_callback()
{
    $options = get_option('fmi_funding_calc_options', array());
    $checked = isset($options['age_full_funding_over45']) && $options['age_full_funding_over45'] ? 'checked' : '';
    echo '<label><input type="checkbox" name="fmi_funding_calc_options[age_full_funding_over45]" value="1" ' . $checked . ' /> ';
    echo __('Enable 100% training funding for employees over 45 years old', 'fmi-funding-calculator') . '</label>';
    echo '<p class="description">' . __('When enabled, employees over 45 will receive full training funding instead of the normal rate.', 'fmi-funding-calculator') . '</p>';
}

/**
 * Sanitize and validate options
 */
function fmi_sanitize_options($input)
{
    $output = array();

    // Sanitize numeric fields
    $numeric_fields = array(
        'training_value_per_employee',
        'max_reimbursement_per_employee',
        'training_funding_rate',
        'wage_reimbursement_under_50',
        'wage_reimbursement_50_500',
        'wage_reimbursement_over_500'
    );

    foreach ($numeric_fields as $field) {
        if (isset($input[$field])) {
            $output[$field] = floatval($input[$field]);

            // Validate rates (must be between 0 and 1) - exclude max_reimbursement_per_employee
            if (
                $field !== 'max_reimbursement_per_employee' &&
                (strpos($field, 'rate') !== false || strpos($field, 'reimbursement') !== false)
            ) {
                $output[$field] = max(0, min(1, $output[$field]));
            } else {
                $output[$field] = max(0, $output[$field]);
            }
        }
    }

    // Sanitize default training duration
    if (isset($input['default_training_duration'])) {
        $allowed_durations = array(6, 8, 12, 18, 24);
        $duration = intval($input['default_training_duration']);
        $output['default_training_duration'] = in_array($duration, $allowed_durations) ? $duration : 12;
    }

    // Sanitize checkbox
    $output['age_full_funding_over45'] = isset($input['age_full_funding_over45']) ? 1 : 0;

    return $output;
}

/**
 * Add settings page to admin menu
 */
function fmi_add_settings_page()
{
    add_options_page(
        __('FMI Funding Calculator', 'fmi-funding-calculator'),
        __('FMI Funding Calc', 'fmi-funding-calculator'),
        'manage_options',
        'fmi-settings',
        'fmi_render_settings_page'
    );
}
add_action('admin_menu', 'fmi_add_settings_page');

/**
 * Render settings page
 */
function fmi_render_settings_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Show success message if settings saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'fmi_messages',
            'fmi_message',
            __('Settings saved successfully.', 'fmi-funding-calculator'),
            'success'
        );
    }

    // Show error/success messages
    settings_errors('fmi_messages');
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="fmi-admin-header" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #0073aa;">
            <h2><?php _e('Funding Calculator Configuration', 'fmi-funding-calculator'); ?></h2>
            <p><?php _e('Configure the parameters used to calculate government funding for training programs.', 'fmi-funding-calculator'); ?></p>
            <p><strong><?php _e('Shortcode:', 'fmi-funding-calculator'); ?></strong> <code>[fmi_funding_calculator]</code></p>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('fmi_settings_group');
            do_settings_sections('fmi-settings');
            submit_button(__('Save Settings', 'fmi-funding-calculator'));
            ?>
        </form>

        <div class="fmi-admin-footer" style="background: #f5f5f5; padding: 15px; margin-top: 30px; border-radius: 4px;">
            <h3><?php _e('Need Help?', 'fmi-funding-calculator'); ?></h3>
            <p><?php _e('Place the shortcode [fmi_funding_calculator] on any page or post to display the funding calculator.', 'fmi-funding-calculator'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Add admin styles
 */
function fmi_admin_styles()
{
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_fmi-settings') {
    ?>
        <style>
            .fmi-admin-header h2 {
                margin-top: 0;
            }

            .form-table th {
                width: 300px;
            }

            .regular-text {
                width: 25em;
            }
        </style>
<?php
    }
}
add_action('admin_head', 'fmi_admin_styles');
