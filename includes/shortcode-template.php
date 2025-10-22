<?php

/**
 * Shortcode Template
 * 
 * Renders the funding calculator frontend form
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Render the funding calculator shortcode
 */
function fmi_render_calculator($atts = array())
{
    // Parse shortcode attributes with defaults
    $atts = shortcode_atts(array(
        'title' => __('Calculate your government funding', 'fmi-funding-calculator'),
    ), $atts, 'fmi_funding_calculator');

    // Get default training duration from settings
    $options = get_option('fmi_funding_calc_options', array());
    $default_duration = isset($options['default_training_duration']) ? intval($options['default_training_duration']) : 12;

    // Start output buffering
    ob_start();
?>

    <div class="fmi-calculator-wrapper">
        <div class="fmi-calculator">

            <?php if (!empty($atts['title'])) : ?>
                <h2 class="fmi-calculator-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>

            <form id="fmi-calculator-form" class="fmi-calculator-form">

                <div class="fmi-form-grid">

                    <!-- Duration -->
                    <div class="fmi-form-group fmi-full-width">
                        <label for="fmi-duration" class="fmi-label">
                            <?php _e('Duration of training (months)', 'fmi-funding-calculator'); ?>
                        </label>
                        <select id="fmi-duration" name="duration" class="fmi-select">
                            <option value="6" <?php selected($default_duration, 6); ?>>6 <?php _e('months', 'fmi-funding-calculator'); ?></option>
                            <option value="8" <?php selected($default_duration, 8); ?>>8 <?php _e('months', 'fmi-funding-calculator'); ?></option>
                            <option value="12" <?php selected($default_duration, 12); ?>>12 <?php _e('months', 'fmi-funding-calculator'); ?></option>
                            <option value="18" <?php selected($default_duration, 18); ?>>18 <?php _e('months', 'fmi-funding-calculator'); ?></option>
                            <option value="24" <?php selected($default_duration, 24); ?>>24 <?php _e('months', 'fmi-funding-calculator'); ?></option>
                        </select>
                    </div>

                    <!-- Company Size -->
                    <div class="fmi-form-group fmi-full-width">
                        <label class="fmi-label">
                            <?php _e('Company size', 'fmi-funding-calculator'); ?>
                        </label>
                        <div class="fmi-company-buttons">
                            <button type="button" class="fmi-company-btn active" data-value="under_50">
                                <?php _e('Under 50', 'fmi-funding-calculator'); ?>
                                <br />
                                <small style="font-size:8px;">Full-time equivalent employees</small>
                            </button>
                            <button type="button" class="fmi-company-btn" data-value="50_500">
                                <?php _e('50 - 500', 'fmi-funding-calculator'); ?>
                                <br />
                                <small style="font-size:8px;">Full-time equivalent employees</small>
                            </button>
                            <button type="button" class="fmi-company-btn" data-value="over_500">
                                <?php _e('Over 500', 'fmi-funding-calculator'); ?>
                                <br />
                                <small style="font-size:8px;">Full-time equivalent employees</small>
                            </button>
                        </div>
                        <input type="hidden" id="fmi-company-group" value="under_50" />
                    </div>

                    <!-- Full-Time Equivalent Calculator -->
                    <div class="fmi-form-group fmi-full-width">
                        <div class="fmi-fte-toggle">
                            <a href="#" id="fmi-fte-toggle-btn" class="fmi-fte-toggle-link">
                                <span class="fmi-fte-arrow">▶</span>
                                <span class="fmi-fte-link-text"><?php _e('Calculation aid for full-time equivalents', 'fmi-funding-calculator'); ?></span>
                            </a>
                        </div>
                        <div id="fmi-fte-calculator" class="fmi-fte-calculator" style="display: none;">
                            <p class="fmi-fte-description">
                                <?php _e('Part-time employees are counted proportionally (e.g. 10-20 hours/week = 0.5 full-time equivalent)', 'fmi-funding-calculator'); ?>
                            </p>

                            <div class="fmi-fte-grid">
                                <div class="fmi-fte-row">
                                    <label class="fmi-fte-label">> 30 hours/week:</label>
                                    <input type="number" class="fmi-fte-input" id="fmi-fte-over30" min="0" step="1" value="0" />
                                    <span class="fmi-fte-multiplier">× 1.0 =</span>
                                    <span class="fmi-fte-result" id="fmi-fte-result-over30">0</span>
                                </div>

                                <div class="fmi-fte-row">
                                    <label class="fmi-fte-label">20-30 hours/week:</label>
                                    <input type="number" class="fmi-fte-input" id="fmi-fte-20-30" min="0" step="1" value="0" />
                                    <span class="fmi-fte-multiplier">× 0.75 =</span>
                                    <span class="fmi-fte-result" id="fmi-fte-result-20-30">0</span>
                                </div>

                                <div class="fmi-fte-row">
                                    <label class="fmi-fte-label">10-20 hours/week:</label>
                                    <input type="number" class="fmi-fte-input" id="fmi-fte-10-20" min="0" step="1" value="0" />
                                    <span class="fmi-fte-multiplier">× 0.5 =</span>
                                    <span class="fmi-fte-result" id="fmi-fte-result-10-20">0</span>
                                </div>

                                <div class="fmi-fte-row">
                                    <label class="fmi-fte-label">
                                        < 10 hours/week:</label>
                                            <input type="number" class="fmi-fte-input" id="fmi-fte-under10" min="0" step="1" value="0" />
                                            <span class="fmi-fte-multiplier">× 0.25 =</span>
                                            <span class="fmi-fte-result" id="fmi-fte-result-under10">0</span>
                                </div>

                                <div class="fmi-fte-total-row">
                                    <strong class="fmi-fte-total-label"><?php _e('Total full-time equivalents:', 'fmi-funding-calculator'); ?></strong>
                                    <strong class="fmi-fte-total-value" id="fmi-fte-total">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Number of Employees -->
                    <div class="fmi-form-group fmi-full-width">
                        <label for="fmi-num-employees" class="fmi-label">
                            <?php _e('Employees to be supported', 'fmi-funding-calculator'); ?>
                        </label>
                        <input
                            type="number"
                            id="fmi-num-employees"
                            name="numEmployees"
                            class="fmi-input"
                            value="5"
                            min="1"
                            step="1"
                            required />
                    </div>

                    <!-- Employees Over 45 (hidden for small companies) -->
                    <div class="fmi-form-group fmi-full-width" id="fmi-over45-group" style="display: none;">
                        <label class="fmi-label">
                            <?php _e('Employees over 45 years of age (of those to be promoted)', 'fmi-funding-calculator'); ?>
                        </label>

                        <!-- Checkbox Options -->
                        <div class="fmi-checkbox-group">
                            <label class="fmi-checkbox-option">
                                <input type="radio" name="fmi-over45-type-radio" value="percentage" class="fmi-radio-input" checked />
                                <span class="fmi-checkbox-custom"></span>
                                <span class="fmi-checkbox-label"><?php _e('Percentage', 'fmi-funding-calculator'); ?></span>
                            </label>
                            <label class="fmi-checkbox-option">
                                <input type="radio" name="fmi-over45-type-radio" value="absolute" class="fmi-radio-input" />
                                <span class="fmi-checkbox-custom"></span>
                                <span class="fmi-checkbox-label"><?php _e('Absolute number', 'fmi-funding-calculator'); ?></span>
                            </label>
                        </div>
                        <input type="hidden" id="fmi-over45-type" value="percentage" />

                        <!-- Input Field -->
                        <input
                            type="number"
                            id="fmi-over45"
                            name="over45"
                            class="fmi-input"
                            value="0"
                            min="0"
                            step="1"
                            placeholder="e.g. 30%" />
                    </div> <!-- Average Salary -->
                    <div class="fmi-form-group fmi-full-width">
                        <label for="fmi-avg-salary" class="fmi-label">
                            <?php _e('Avg. gross salary/month (€)', 'fmi-funding-calculator'); ?>
                        </label>
                        <input
                            type="number"
                            id="fmi-avg-salary"
                            name="avgSalary"
                            class="fmi-input"
                            value="4000"
                            min="0"
                            step="0.01"
                            required />
                    </div>

                </div>

            </form>

            <!-- Results Section -->
            <div id="fmi-result-container" class="fmi-result-container" style="display: none;">

                <h3 class="fmi-result-title"><?php _e('Calculation Results', 'fmi-funding-calculator'); ?></h3>

                <div class="fmi-result-grid">

                    <!-- Basic Info -->
                    <div class="fmi-result-info">
                        <div class="fmi-result-row">
                            <span class="fmi-result-label"><?php _e('Total labor costs:', 'fmi-funding-calculator'); ?></span>
                            <span class="fmi-result-value" id="fmi-result-labor">-</span>
                        </div>
                        <div class="fmi-result-row">
                            <span class="fmi-result-label"><?php _e('Number of employees:', 'fmi-funding-calculator'); ?></span>
                            <span class="fmi-result-value" id="fmi-result-num-employees">-</span>
                        </div>
                        <div class="fmi-result-row">
                            <span class="fmi-result-label"><?php _e('Funding period:', 'fmi-funding-calculator'); ?></span>
                            <span class="fmi-result-value" id="fmi-result-duration">-</span>
                        </div>
                    </div>

                    <!-- Funding Breakdown -->
                    <div class="fmi-result-funding">
                        <div class="fmi-result-highlight fmi-result-wage">
                            <span class="fmi-result-label"><?php _e('Wage reimbursement:', 'fmi-funding-calculator'); ?></span>
                            <strong class="fmi-result-value" id="fmi-result-wage">-</strong>
                        </div>
                        <div class="fmi-result-highlight fmi-result-training">
                            <span class="fmi-result-label"><?php _e('Total value of training:', 'fmi-funding-calculator'); ?></span>
                            <strong class="fmi-result-value" id="fmi-result-training">-</strong>
                        </div>
                    </div>

                    <!-- Total Funding -->
                    <div class="fmi-result-total">
                        <div class="fmi-result-total-box">
                            <span class="fmi-result-label"><?php _e('Your state funding amounts to:', 'fmi-funding-calculator'); ?></span>
                            <strong class="fmi-result-value-large" id="fmi-result-total">-</strong>
                        </div>
                    </div>

                    <!-- Cash Advantage -->
                    <div class="fmi-result-cash">
                        <div class="fmi-result-cash-box">
                            <span class="fmi-result-label"><?php _e('Your cash advantage:', 'fmi-funding-calculator'); ?></span>
                            <strong class="fmi-result-value-large" id="fmi-result-cash">-</strong>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

<?php
    // Return the buffered content
    return ob_get_clean();
}
