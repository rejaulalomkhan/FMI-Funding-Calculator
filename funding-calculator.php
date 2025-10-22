<?php

/**
 * Plugin Name: FMI Funding Calculator
 * Plugin URI:  https://fb.com/armanaazij/
 * Description: Professional government funding calculator with company-size specific reimbursement rates and enhanced UI.
 * Version:     1.0.0
 * Author:      Arman azij
 * Author URI:  https://armanazij.me/
 * Text Domain: fmi-funding-calculator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('FMI_PLUGIN_VERSION', '1.0.0');
define('FMI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FMI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FMI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once FMI_PLUGIN_DIR . 'includes/admin-settings.php';
require_once FMI_PLUGIN_DIR . 'includes/shortcode-template.php';

/**
 * Enqueue frontend assets
 */
function fmi_enqueue_assets()
{
    // Only load on pages with the shortcode
    global $post;
    if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'fmi_funding_calculator')) {
        return;
    }

    wp_enqueue_style(
        'fmi-calculator-style',
        FMI_PLUGIN_URL . 'assets/css/style.css',
        array(),
        FMI_PLUGIN_VERSION
    );

    wp_enqueue_script(
        'fmi-calculator-script',
        FMI_PLUGIN_URL . 'assets/js/public.js',
        array('jquery'),
        FMI_PLUGIN_VERSION,
        true
    );

    // Localize script with AJAX URL and nonce
    wp_localize_script('fmi-calculator-script', 'fmiCalcVars', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fmi_calc_nonce'),
        'currencySymbol' => '€'
    ));
}
add_action('wp_enqueue_scripts', 'fmi_enqueue_assets');

/**
 * Register AJAX handlers for both logged-in and non-logged-in users
 */
add_action('wp_ajax_fmi_calculate', 'fmi_ajax_calculate');
add_action('wp_ajax_nopriv_fmi_calculate', 'fmi_ajax_calculate');

/**
 * AJAX calculation handler
 */
function fmi_ajax_calculate()
{
    // Verify nonce for security
    check_ajax_referer('fmi_calc_nonce', 'nonce');

    // Get and sanitize inputs
    $duration_months = isset($_POST['durationMonths']) ? intval($_POST['durationMonths']) : 0;
    $company_group = isset($_POST['companyGroup']) ? sanitize_text_field($_POST['companyGroup']) : 'under_50';
    $num_employees = isset($_POST['numEmployees']) ? intval($_POST['numEmployees']) : 0;
    $employees_over45 = isset($_POST['employeesOver45']) ? floatval($_POST['employeesOver45']) : 0;
    $over45_type = isset($_POST['employeesOver45Type']) ? sanitize_text_field($_POST['employeesOver45Type']) : 'absolute';
    $avg_salary = isset($_POST['avgGrossSalary']) ? floatval($_POST['avgGrossSalary']) : 0;

    // Validate inputs
    if ($duration_months <= 0 || $num_employees <= 0 || $avg_salary <= 0) {
        wp_send_json(array(
            'success' => false,
            'message' => __('Please fill in all required fields with valid values.', 'fmi-funding-calculator')
        ));
    }

    // Get plugin options with defaults
    $options = get_option('fmi_funding_calc_options', array());
    // Training value is FIXED at €10,000 per employee regardless of duration
    $training_value = isset($options['training_value_per_employee']) ? floatval($options['training_value_per_employee']) : 10000;
    $max_cap = isset($options['max_reimbursement_per_employee']) ? floatval($options['max_reimbursement_per_employee']) : 7100;
    $training_rate = isset($options['training_funding_rate']) ? floatval($options['training_funding_rate']) : 0.5;
    $age_full_funding = isset($options['age_full_funding_over45']) ? (bool)$options['age_full_funding_over45'] : false;

    // Get company-size specific wage reimbursement rate
    $wage_rate = 0.5; // default
    $training_rate_base = 0.5; // default for medium companies

    if ($company_group === 'under_50') {
        $wage_rate = isset($options['wage_reimbursement_under_50']) ? floatval($options['wage_reimbursement_under_50']) : 0.75;
        $training_rate_base = 1.0; // 100% for small companies
    } elseif ($company_group === '50_500') {
        $wage_rate = isset($options['wage_reimbursement_50_500']) ? floatval($options['wage_reimbursement_50_500']) : 0.5;
        $training_rate_base = 0.5; // 50% for medium companies
    } elseif ($company_group === 'over_500') {
        $wage_rate = isset($options['wage_reimbursement_over_500']) ? floatval($options['wage_reimbursement_over_500']) : 0.25;
        $training_rate_base = 0.25; // 25% for large companies
    }

    // Calculate number of employees over 45 (only for medium and large companies)
    $num_over45 = 0;
    if ($company_group !== 'under_50') {
        if ($over45_type === 'percentage') {
            $num_over45 = round($num_employees * $employees_over45 / 100);
        } else {
            $num_over45 = intval($employees_over45);
        }
        $num_over45 = min($num_over45, $num_employees);
    }
    $num_regular = $num_employees - $num_over45;

    // Calculate total labor cost
    $total_labor_cost = $avg_salary * $num_employees * $duration_months;

    // Calculate wage reimbursement with cap (cap is per employee per month)
    $wage_reimbursement_base = $total_labor_cost * $wage_rate;
    $wage_reimbursement_cap = $num_employees * $max_cap * $duration_months;
    $wage_reimbursement = min($wage_reimbursement_base, $wage_reimbursement_cap);

    // Calculate training value
    // For small companies: 100% funding for all
    // For medium/large companies: 100% for over 45, base rate for others
    if ($company_group === 'under_50') {
        // Small companies get 100% training funding for all employees
        $training_value_total = $training_value * $num_employees;
        $training_self_covered = 0;
        $effective_training_percentage = 100;
    } else {
        // Medium and large companies: 100% for over 45, base rate for others
        $training_value_over45 = $training_value * $num_over45;
        $training_value_regular = $training_value * $num_regular * $training_rate_base;
        $training_value_total = $training_value_over45 + $training_value_regular;

        // Calculate self-covered portion
        $training_self_covered = $training_value * $num_regular * (1 - $training_rate_base);

        // Calculate weighted average percentage for display
        if ($num_employees > 0) {
            $over45_contribution = ($num_over45 * 100) / $num_employees;
            $regular_contribution = ($num_regular * $training_rate_base * 100) / $num_employees;
            $effective_training_percentage = round($over45_contribution + $regular_contribution);
        } else {
            $effective_training_percentage = round($training_rate_base * 100);
        }
    }

    // Calculate total funding
    $total_funding = $wage_reimbursement + $training_value_total;

    // Calculate cash advantage (wage reimbursement minus self-covered training costs)
    $cash_advantage = $wage_reimbursement - $training_self_covered;

    // Prepare response data
    $response_data = array(
        'success' => true,
        'data' => array(
            'totalLaborCost' => round($total_labor_cost, 2),
            'wageReimbursement' => round($wage_reimbursement, 2),
            'wagePercentage' => round($wage_rate * 100),
            'trainingValue' => round($training_value_total, 2),
            'trainingPercentage' => $effective_training_percentage,
            'trainingValuePerEmployee' => round($training_value, 2),
            'trainingValueFull' => round($training_value * $num_employees, 2), // Full value before funding
            'totalFunding' => round($total_funding, 2),
            'cashAdvantage' => round($cash_advantage, 2),
            'trainingSelfCovered' => round($training_self_covered, 2),
            'numOver45' => $num_over45,
            'durationMonths' => $duration_months,
            'numEmployees' => $num_employees,
            'companyGroup' => $company_group
        )
    );

    // Allow other plugins to modify the response
    $response_data = apply_filters('fmi_calc_before_response', $response_data, $_POST);

    wp_send_json($response_data);
}

/**
 * Register shortcode
 */
function fmi_register_shortcode()
{
    add_shortcode('fmi_funding_calculator', 'fmi_render_calculator');
}
add_action('init', 'fmi_register_shortcode');

/**
 * Add plugin action links
 */
function fmi_add_action_links($links)
{
    $settings_link = '<a href="' . admin_url('options-general.php?page=fmi-settings') . '">' . __('Settings', 'fmi-funding-calculator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . FMI_PLUGIN_BASENAME, 'fmi_add_action_links');
