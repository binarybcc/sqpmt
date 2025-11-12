/**
 * Square Payment Form JavaScript
 *
 * Handles form validation, Square Web Payments SDK integration, and payment processing.
 */

(function($) {
    'use strict';

    let card = null;
    let payments = null;

    /**
     * Initialize payment form
     */
    async function initPaymentForm() {
        if (!window.Square) {
            console.error('Square.js failed to load');
            showError('Payment system failed to load. Please refresh the page.');
            return;
        }

        try {
            // Initialize Square Payments
            payments = window.Square.payments(sqpmtSettings.applicationId, sqpmtSettings.locationId);

            // Initialize card payment method
            card = await payments.card();
            await card.attach('#sqpmt-card-container');

            console.log('Square Payments initialized successfully');

        } catch (e) {
            console.error('Failed to initialize Square Payments:', e);
            showError('Failed to initialize payment system: ' + e.message);
        }
    }

    /**
     * Format phone number as user types
     */
    function formatPhoneNumber(value) {
        // Remove all non-numeric characters
        const phone = value.replace(/\D/g, '');

        // Format as (XXX) XXX-XXXX
        if (phone.length <= 3) {
            return phone;
        } else if (phone.length <= 6) {
            return `(${phone.slice(0, 3)}) ${phone.slice(3)}`;
        } else {
            return `(${phone.slice(0, 3)}) ${phone.slice(3, 6)}-${phone.slice(6, 10)}`;
        }
    }

    /**
     * Calculate and display payment breakdown
     */
    function updateCalculation() {
        const amount = parseFloat($('#sqpmt-amount').val()) || 0;

        if (amount <= 0) {
            $('#sqpmt-calculation-breakdown').hide();
            return;
        }

        const serviceFeePercentage = sqpmtSettings.serviceFee;
        const serviceFee = (amount * serviceFeePercentage) / 100;
        const total = amount + serviceFee;

        // Update display
        $('#sqpmt-original-amount').text('$' + amount.toFixed(2));
        $('#sqpmt-service-fee').text('$' + serviceFee.toFixed(2));
        $('#sqpmt-total-amount').text('$' + total.toFixed(2));
        $('#sqpmt-fee-percentage').text('(' + serviceFeePercentage + '%)');
        $('#sqpmt-calculation-breakdown').show();
    }

    /**
     * Validate individual field
     */
    function validateField(fieldName, value) {
        const errors = [];

        switch (fieldName) {
            case 'amount':
                if (!value || parseFloat(value) <= 0) {
                    errors.push('Amount is required and must be greater than zero.');
                } else if (parseFloat(value) < 1.00) {
                    errors.push('Amount must be at least $1.00.');
                }
                break;

            case 'name':
                if (!value || value.trim().length < 2) {
                    errors.push('Name must be at least 2 characters.');
                }
                break;

            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!value || !emailRegex.test(value)) {
                    errors.push('Please enter a valid email address.');
                }
                break;

            case 'phone':
                const phoneDigits = value.replace(/\D/g, '');
                if (phoneDigits.length !== 10) {
                    errors.push('Phone number must be 10 digits.');
                }
                break;

            case 'address_line1':
                if (!value || value.trim().length < 5) {
                    errors.push('Address must be at least 5 characters.');
                }
                break;

            case 'city':
                if (!value || value.trim().length < 2) {
                    errors.push('City must be at least 2 characters.');
                }
                break;

            case 'state':
                if (!value) {
                    errors.push('Please select a state.');
                }
                break;

            case 'zip':
                const zipRegex = /^\d{5}$/;
                if (!value || !zipRegex.test(value)) {
                    errors.push('ZIP code must be 5 digits.');
                }
                break;
        }

        return errors;
    }

    /**
     * Display field error
     */
    function showFieldError(fieldName, message) {
        const errorElement = $('[data-field="' + fieldName + '"]');
        errorElement.text(message).show();
        $('#sqpmt-' + fieldName.replace('_', '-')).addClass('sqpmt-field-error');
    }

    /**
     * Clear field error
     */
    function clearFieldError(fieldName) {
        const errorElement = $('[data-field="' + fieldName + '"]');
        errorElement.text('').hide();
        $('#sqpmt-' + fieldName.replace('_', '-')).removeClass('sqpmt-field-error');
    }

    /**
     * Validate all form fields
     */
    function validateForm() {
        let isValid = true;

        const fields = [
            'amount', 'name', 'email', 'phone',
            'address_line1', 'city', 'state', 'zip'
        ];

        fields.forEach(function(fieldName) {
            const inputName = fieldName.replace('_', '-');
            const value = $('#sqpmt-' + inputName).val();
            const errors = validateField(fieldName, value);

            if (errors.length > 0) {
                showFieldError(fieldName, errors[0]);
                isValid = false;
            } else {
                clearFieldError(fieldName);
            }
        });

        return isValid;
    }

    /**
     * Enable or disable submit button based on validation
     */
    function updateSubmitButton() {
        const isValid = validateForm();
        $('#sqpmt-submit-btn').prop('disabled', !isValid);
    }

    /**
     * Show error message
     */
    function showError(message) {
        const messagesDiv = $('#sqpmt-messages');
        messagesDiv.html('<div class="sqpmt-message sqpmt-message-error">' + escapeHtml(message) + '</div>');
        messagesDiv[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        const messagesDiv = $('#sqpmt-messages');
        messagesDiv.html('<div class="sqpmt-message sqpmt-message-success">' + escapeHtml(message) + '</div>');
        messagesDiv[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmit(event) {
        event.preventDefault();

        // Clear previous messages
        $('#sqpmt-messages').html('');

        // Validate form
        if (!validateForm()) {
            showError('Please fix the errors in the form.');
            return;
        }

        // Disable submit button and show loading state
        const submitBtn = $('#sqpmt-submit-btn');
        submitBtn.prop('disabled', true);
        $('.sqpmt-btn-text').hide();
        $('.sqpmt-btn-spinner').show();

        try {
            // Tokenize card details
            const result = await card.tokenize();

            if (result.status === 'OK') {
                // Set payment token
                $('#sqpmt-payment-token').val(result.token);

                // Submit form via AJAX
                const formData = {
                    action: 'sqpmt_process_payment',
                    nonce: sqpmtSettings.nonce,
                    amount: $('#sqpmt-amount').val(),
                    name: $('#sqpmt-name').val(),
                    email: $('#sqpmt-email').val(),
                    phone: $('#sqpmt-phone').val(),
                    address_line1: $('#sqpmt-address-line1').val(),
                    address_line2: $('#sqpmt-address-line2').val(),
                    city: $('#sqpmt-city').val(),
                    state: $('#sqpmt-state').val(),
                    zip: $('#sqpmt-zip').val(),
                    payment_token: result.token
                };

                $.ajax({
                    url: sqpmtSettings.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Payment successful
                            showSuccess(response.data.message);

                            // Reset form
                            $('#sqpmt-payment-form')[0].reset();
                            $('#sqpmt-calculation-breakdown').hide();

                            // Reinitialize card element
                            card.destroy();
                            initPaymentForm();

                        } else {
                            // Payment failed
                            showError(response.data.message || sqpmtSettings.strings.error);
                        }
                    },
                    error: function() {
                        showError(sqpmtSettings.strings.error);
                    },
                    complete: function() {
                        // Re-enable submit button
                        submitBtn.prop('disabled', false);
                        $('.sqpmt-btn-text').show();
                        $('.sqpmt-btn-spinner').hide();
                    }
                });

            } else {
                // Tokenization failed
                let errorMessage = 'Card verification failed. Please check your card details.';

                if (result.errors && result.errors.length > 0) {
                    errorMessage = result.errors.map(error => error.message).join(', ');
                }

                showError(errorMessage);
                showFieldError('card', errorMessage);

                // Re-enable submit button
                submitBtn.prop('disabled', false);
                $('.sqpmt-btn-text').show();
                $('.sqpmt-btn-spinner').hide();
            }

        } catch (e) {
            console.error('Payment error:', e);
            showError('An error occurred while processing your payment. Please try again.');

            // Re-enable submit button
            submitBtn.prop('disabled', false);
            $('.sqpmt-btn-text').show();
            $('.sqpmt-btn-spinner').hide();
        }
    }

    /**
     * Document ready
     */
    $(document).ready(function() {
        // Initialize Square Payments SDK
        initPaymentForm();

        // Amount field - update calculation
        $('#sqpmt-amount').on('input', function() {
            updateCalculation();
            updateSubmitButton();
        });

        // Phone field - format as user types
        $('#sqpmt-phone').on('input', function() {
            const formatted = formatPhoneNumber($(this).val());
            $(this).val(formatted);
            updateSubmitButton();
        });

        // ZIP field - only allow digits
        $('#sqpmt-zip').on('input', function() {
            $(this).val($(this).val().replace(/\D/g, '').slice(0, 5));
            updateSubmitButton();
        });

        // Validate fields on blur
        $('#sqpmt-payment-form input, #sqpmt-payment-form select').on('blur', function() {
            const fieldName = $(this).attr('name');
            if (fieldName) {
                const value = $(this).val();
                const errors = validateField(fieldName, value);

                if (errors.length > 0) {
                    showFieldError(fieldName, errors[0]);
                } else {
                    clearFieldError(fieldName);
                }
            }
            updateSubmitButton();
        });

        // Clear error on focus
        $('#sqpmt-payment-form input, #sqpmt-payment-form select').on('focus', function() {
            const fieldName = $(this).attr('name');
            if (fieldName) {
                clearFieldError(fieldName);
            }
        });

        // Update submit button state on any input
        $('#sqpmt-payment-form input, #sqpmt-payment-form select').on('input change', function() {
            updateSubmitButton();
        });

        // Handle form submission
        $('#sqpmt-payment-form').on('submit', handleFormSubmit);

        // Initial validation check
        setTimeout(updateSubmitButton, 500);
    });

})(jQuery);
