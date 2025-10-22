/**
 * FMI Funding Calculator - Frontend JavaScript
 * 
 * Handles form interactions and AJAX calculations
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        initFMICalculator();
    });

    /**
     * Initialize calculator functionality
     */
    function initFMICalculator() {
        var $calculator = $('.fmi-calculator');
        if (!$calculator.length) return;

        var selectedCompanyGroup = 'under_50';

        // Company size button toggle
        $('.fmi-company-btn').on('click', function(e) {
            e.preventDefault();
            
            $('.fmi-company-btn').removeClass('active');
            $(this).addClass('active');
            
            selectedCompanyGroup = $(this).data('value');
            $('#fmi-company-group').val(selectedCompanyGroup);
            
            // Show/hide age fields based on company size
            if (selectedCompanyGroup === 'under_50') {
                $('#fmi-over45-group').hide();
                $('#fmi-over45').val(0); // Reset value for small companies
            } else {
                $('#fmi-over45-group').show();
            }
        });

        // Radio button handler for age type
        $('input[name="fmi-over45-type-radio"]').on('change', function() {
            var type = $(this).val();
            $('#fmi-over45-type').val(type);
            
            // Update placeholder based on type
            var placeholder = type === 'percentage' ? 'e.g. 30%' : 'e.g. 2';
            var step = type === 'percentage' ? '1' : '1';
            var max = type === 'percentage' ? '100' : '';
            
            $('#fmi-over45')
                .attr('placeholder', placeholder)
                .attr('step', step)
                .attr('max', max)
                .val(0);
            
            // Trigger recalculation
            performCalculation();
        });

        // FTE Calculator Toggle
        $('#fmi-fte-toggle-btn').on('click', function(e) {
            e.preventDefault();
            var $calculator = $('#fmi-fte-calculator');
            var $btn = $(this);
            
            if ($calculator.is(':visible')) {
                $calculator.slideUp(300);
                $btn.removeClass('active');
            } else {
                $calculator.slideDown(300);
                $btn.addClass('active');
            }
        });

        // FTE Calculator - Live calculation
        $('.fmi-fte-input').on('input', function() {
            calculateFTE();
        });

        function calculateFTE() {
            var over30 = parseFloat($('#fmi-fte-over30').val()) || 0;
            var range2030 = parseFloat($('#fmi-fte-20-30').val()) || 0;
            var range1020 = parseFloat($('#fmi-fte-10-20').val()) || 0;
            var under10 = parseFloat($('#fmi-fte-under10').val()) || 0;

            var result1 = over30 * 1.0;
            var result2 = range2030 * 0.75;
            var result3 = range1020 * 0.5;
            var result4 = under10 * 0.25;

            $('#fmi-fte-result-over30').text(result1.toFixed(2));
            $('#fmi-fte-result-20-30').text(result2.toFixed(2));
            $('#fmi-fte-result-10-20').text(result3.toFixed(2));
            $('#fmi-fte-result-under10').text(result4.toFixed(2));

            var total = result1 + result2 + result3 + result4;
            $('#fmi-fte-total').text(total.toFixed(2));
        }

        // Real-time calculation on any input change
        // Bind to all form inputs and selects that affect calculation
        $('#fmi-calculator-form').on('input change', 'input[type="number"], select', function() {
            // Skip FTE calculator inputs
            if ($(this).hasClass('fmi-fte-input')) {
                return;
            }
            
            // Instant calculation - no delay
            performCalculation();
        });

        // Trigger calculation when company size changes
        $('.fmi-company-btn').on('click', function() {
            // Immediate calculation for button clicks
            performCalculation();
        });

        // Trigger initial calculation on page load with default values
        setTimeout(function() {
            performCalculation();
        }, 300);

        /**
         * Perform AJAX calculation
         */
        function performCalculation() {
            // Get form values
            var durationMonths = parseInt($('#fmi-duration').val());
            var companyGroup = selectedCompanyGroup;
            var numEmployees = parseFloat($('#fmi-num-employees').val());
            var employeesOver45 = parseFloat($('#fmi-over45').val()) || 0;
            var employeesOver45Type = $('#fmi-over45-type').val();
            var avgGrossSalary = parseFloat($('#fmi-avg-salary').val());

            // Basic validation - only calculate if we have minimum required data
            if (!durationMonths || !numEmployees || numEmployees <= 0 || !avgGrossSalary || avgGrossSalary <= 0) {
                // Hide results if validation fails
                hideResults();
                return;
            }

            // Prepare AJAX data
            var ajaxData = {
                action: 'fmi_calculate',
                nonce: fmiCalcVars.nonce,
                durationMonths: durationMonths,
                companyGroup: companyGroup,
                numEmployees: numEmployees,
                employeesOver45: employeesOver45,
                employeesOver45Type: employeesOver45Type,
                avgGrossSalary: avgGrossSalary
            };

            // Send AJAX request
            $.ajax({
                url: fmiCalcVars.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        displayResults(response.data);
                    } else {
                        hideResults();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    hideResults();
                }
            });
        }

        /**
         * Display calculation results
         */
        function displayResults(data) {
            var currencySymbol = fmiCalcVars.currencySymbol || '€';

            // Basic info
            $('#fmi-result-labor').text(formatCurrency(data.totalLaborCost, currencySymbol));
            $('#fmi-result-num-employees').text(data.numEmployees);
            $('#fmi-result-duration').text(data.durationMonths + ' months');

            // Funding breakdown with percentages
            var wagePercentageText = (data.wagePercentage || 50) + '%';
            var trainingPercentageText = (data.trainingPercentage || 50) + '%';
            
            $('#fmi-result-wage').html('<span class="percentage">' + wagePercentageText + '</span> ' + formatCurrency(data.wageReimbursement, currencySymbol));
            $('#fmi-result-training').html('<span class="percentage">' + trainingPercentageText + '</span> ' + formatCurrency(data.trainingValue, currencySymbol));

            // Totals
            $('#fmi-result-total').text(formatCurrency(data.totalFunding, currencySymbol));
            $('#fmi-result-cash').text(formatCurrency(data.cashAdvantage, currencySymbol));
            
            // Show/hide cash advantage section based on company size and value
            var $cashBox = $('.fmi-result-cash');
            if (data.companyGroup === 'under_50' || data.trainingSelfCovered === 0) {
                // Hide cash advantage for small companies or when no self-covered costs
                $cashBox.hide();
            } else {
                $cashBox.show();
                // Color code based on positive/negative
                if (data.cashAdvantage > 0) {
                    $cashBox.removeClass('negative').addClass('positive');
                } else {
                    $cashBox.removeClass('positive').addClass('negative');
                }
            }

            // Show results
            showResults();
        }

        /**
         * Show results section
         */
        function showResults() {
            var $results = $('#fmi-result-container');
            
            if (!$results.is(':visible')) {
                $results.slideDown(300);
            }
        }

        /**
         * Hide results section
         */
        function hideResults() {
            $('#fmi-result-container').slideUp(200);
        }

        /**
         * Format number as currency
         */
        function formatCurrency(value, symbol) {
            if (typeof value !== 'number' || isNaN(value)) {
                return '-';
            }
            
            // Format with thousand separators and 2 decimals
            var formatted = value.toLocaleString('de-DE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            return formatted + ' ' + symbol;
        }
    }

})(jQuery);
