/**
 * WooCommerce Custom Login Registration with Brand Colors
 * Brand Colors: #008296 & #ffc549
 * Enhanced with Password Reset Tab
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Replace default WooCommerce account forms with custom tabbed version
 */
function custom_wc_account_content() {
    // Only modify the content if the user is not logged in
    if (!is_user_logged_in() && is_account_page()) {
        // Remove the default login form
        remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10);
        remove_action('woocommerce_login_form_start', 'woocommerce_output_all_notices', 10);
        
        // Add our custom tabbed form
        add_action('woocommerce_before_customer_login_form', 'custom_wc_login_registration_tabs', 10);
        
        // Hide the default forms
        add_filter('woocommerce_locate_template', 'hide_default_forms', 10, 3);
    }
}
add_action('template_redirect', 'custom_wc_account_content');

/**
 * Hide default WooCommerce forms
 */
function hide_default_forms($template, $template_name, $template_path) {
    if (($template_name === 'myaccount/form-login.php' || $template_name === 'myaccount/form-lost-password.php') && !is_user_logged_in()) {
        return WP_PLUGIN_DIR . '/wc-custom-forms/templates/empty-template.php'; // Point to an empty template
    }
    return $template;
}

/**
 * Output custom login registration tabs
 */
function custom_wc_login_registration_tabs() {
    // Display any error messages
    wc_print_notices();
    
    // Output our custom form
    echo do_shortcode('[custom_wc_login_registration_tabs]');
}

/**
 * Custom login/registration tabs shortcode
 */
function custom_wc_login_registration_tabs_shortcode() {
    // Buffer output
    ob_start();
    
    // Check if user is logged in
    if (is_user_logged_in()) {
        return ob_get_clean(); // Return empty if logged in, as WooCommerce will show the dashboard
    }
    
    // Check if we need to show the reset password form
    $show_reset = isset($_GET['action']) && $_GET['action'] === 'lostpassword';
    $reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    $reset_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
    $password_reset_key_valid = false;
    
    // Check if we're in the reset password process
    if (!empty($reset_key) && !empty($reset_login)) {
        $user = check_password_reset_key($reset_key, $reset_login);
        if (!is_wp_error($user)) {
            $show_reset = true;
            $password_reset_key_valid = true;
        }
    }
    
    ?>
    <div class="custom-wc-login-registration-container">
        <div class="custom-wc-login-registration-tabs">
            <div class="custom-wc-tab <?php echo (!$show_reset) ? 'active' : ''; ?>" data-tab="login">Login</div>
            <div class="custom-wc-tab <?php echo (!$show_reset) ? '' : ''; ?>" data-tab="register">Register</div>
            <div class="custom-wc-tab <?php echo ($show_reset) ? 'active' : ''; ?>" data-tab="lostpassword">Reset Password</div>
        </div>
        
        <div class="custom-wc-tab-content <?php echo (!$show_reset) ? 'active' : ''; ?>" id="login-tab">
            <form class="wc-login-form" method="post">
                <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                
                <p class="form-row">
                    <label for="username">Email address&nbsp;<span class="required">*</span></label>
                    <input type="email" class="input-text" name="username" id="username" autocomplete="email" required />
                </p>
                
                <p class="form-row">
                    <label for="password">Password&nbsp;<span class="required">*</span></label>
                    <input class="input-text" type="password" name="password" id="password" autocomplete="current-password" required />
                </p>
                
                <p class="form-row remember-me">
                    <label class="woocommerce-form__label">
                        <input class="woocommerce-form__input" name="rememberme" type="checkbox" id="rememberme" value="forever" /> 
                        <span>Remember me</span>
                    </label>
                </p>
                
                <p class="form-row">
                    <input type="hidden" name="custom-woocommerce-login" value="1" />
                    <button type="submit" class="woocommerce-button button" name="login" value="Log in">Sign In</button>
                </p>
                
                <p class="lost-password">
                    <a href="#" class="switch-to-reset">Forgot your password?</a>
                </p>
                
                <p class="no-account">
                    <a href="#" class="switch-to-register">Don't have an account? Create one now</a>
                </p>
            </form>
        </div>
        
        <div class="custom-wc-tab-content" id="register-tab">
            <form class="wc-register-form" method="post">
                <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                
                <p class="form-row">
                    <label for="reg_email">Email address&nbsp;<span class="required">*</span></label>
                    <input type="email" class="input-text" name="email" id="reg_email" autocomplete="email" required />
                </p>
                
                <?php if ('yes' !== get_option('woocommerce_registration_generate_password')) : ?>
                    <p class="form-row">
                        <label for="reg_password">Password&nbsp;<span class="required">*</span></label>
                        <input type="password" class="input-text" name="password" id="reg_password" autocomplete="new-password" required />
                    </p>
                <?php endif; ?>
                
                <?php do_action('woocommerce_register_form'); ?>
                
                <p class="form-row privacy-policy-text">
                    <small>Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our <a href="<?php echo get_privacy_policy_url(); ?>" target="_blank">privacy policy</a>.</small>
                </p>
                
                <p class="form-row">
                    <input type="hidden" name="custom-woocommerce-register" value="1" />
                    <button type="submit" class="woocommerce-button button" name="register" value="Register">Register</button>
                </p>
                
                <p class="has-account">
                    <a href="#" class="switch-to-login">Already have an account? Sign in</a>
                </p>
            </form>
        </div>
        
        <div class="custom-wc-tab-content <?php echo ($show_reset) ? 'active' : ''; ?>" id="lostpassword-tab">
            <?php if ($password_reset_key_valid) : ?>
                <!-- Password reset form -->
                <form method="post" class="wc-reset-password-form">
                    <p class="reset-password-message">Enter your new password below</p>
                    
                    <p class="form-row">
                        <label for="password_1">New password&nbsp;<span class="required">*</span></label>
                        <input type="password" class="input-text" name="password_1" id="password_1" autocomplete="new-password" required />
                    </p>
                    
                    <p class="form-row">
                        <label for="password_2">Confirm new password&nbsp;<span class="required">*</span></label>
                        <input type="password" class="input-text" name="password_2" id="password_2" autocomplete="new-password" required />
                    </p>
                    
                    <input type="hidden" name="reset_key" value="<?php echo esc_attr($reset_key); ?>" />
                    <input type="hidden" name="reset_login" value="<?php echo esc_attr($reset_login); ?>" />
                    <?php wp_nonce_field('reset_password', 'woocommerce-reset-password-nonce'); ?>
                    
                    <p class="form-row">
                        <input type="hidden" name="custom-woocommerce-reset-password" value="1" />
                        <button type="submit" class="woocommerce-button button" name="reset_password" value="Reset password">Reset Password</button>
                    </p>
                    
                    <p class="back-to-login">
                        <a href="#" class="switch-to-login">Back to login</a>
                    </p>
                </form>
            <?php else : ?>
                <!-- Lost password form -->
                <form method="post" class="wc-lost-password-form">
                    <p class="lost-password-message">Lost your password? Please enter your email address. You will receive a link to create a new password via email.</p>
                    
                    <p class="form-row">
                        <label for="user_login">Email address&nbsp;<span class="required">*</span></label>
                        <input class="input-text" type="email" name="user_login" id="user_login" autocomplete="username" required />
                    </p>
                    
                    <?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>
                    
                    <p class="form-row">
                        <input type="hidden" name="custom-woocommerce-lost-password" value="1" />
                        <button type="submit" class="woocommerce-button button" name="lost_password" value="Reset password">Reset Password</button>
                    </p>
                    
                    <p class="back-to-login">
                        <a href="#" class="switch-to-login">Back to login</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
    
    return ob_get_clean();
}
add_shortcode('custom_wc_login_registration_tabs', 'custom_wc_login_registration_tabs_shortcode');

/**
 * Process custom login form submission
 */
function process_custom_login_form() {
    if (isset($_POST['custom-woocommerce-login']) && wp_verify_nonce($_POST['woocommerce-login-nonce'], 'woocommerce-login')) {
        try {
            $credentials = array(
                'user_login'    => trim($_POST['username']),
                'user_password' => $_POST['password'],
                'remember'      => isset($_POST['rememberme']),
            );
            
            $user = wp_signon($credentials, is_ssl());
            
            if (is_wp_error($user)) {
                throw new Exception($user->get_error_message());
            } else {
                // If successful, redirect to my account page
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
}
add_action('template_redirect', 'process_custom_login_form');

/**
 * Process custom registration form
 */
function process_custom_registration_form() {
    if (isset($_POST['custom-woocommerce-register']) && wp_verify_nonce($_POST['woocommerce-register-nonce'], 'woocommerce-register')) {
        try {
            $email = sanitize_email($_POST['email']);
            
            if (empty($email) || !is_email($email)) {
                throw new Exception('Please provide a valid email address.');
            }
            
            if (email_exists($email)) {
                throw new Exception('An account is already registered with your email address. Please log in.');
            }
            
            // Generate username from email (part before @)
            $username = sanitize_user(strstr($email, '@', true));
            
            // Check if username exists and append numbers if needed
            $original_username = $username;
            $counter = 1;
            
            while (username_exists($username)) {
                $username = $original_username . $counter;
                $counter++;
            }
            
            // Create password or use provided one
            if ('yes' === get_option('woocommerce_registration_generate_password') || empty($_POST['password'])) {
                $password = wp_generate_password();
                $password_generated = true;
            } else {
                $password = $_POST['password'];
                $password_generated = false;
            }
            
            // Create new user
            $new_customer = wc_create_new_customer($email, $username, $password);
            
            if (is_wp_error($new_customer)) {
                throw new Exception($new_customer->get_error_message());
            }
            
            // If no password was created, set a flag to show a message
            if ($password_generated) {
                wc_add_notice('Your account was created successfully. Please check your email for the password.');
            } else {
                // If password was provided by user, log them in automatically
                wc_set_customer_auth_cookie($new_customer);
            }
            
            // Redirect
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
            
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
}
add_action('template_redirect', 'process_custom_registration_form');

/**
 * Process lost password form
 */
function process_custom_lost_password_form() {
    if (isset($_POST['custom-woocommerce-lost-password']) && wp_verify_nonce($_POST['woocommerce-lost-password-nonce'], 'lost_password')) {
        $success = WC_Shortcode_My_Account::retrieve_password();
        
        // If successful, display a message and redirect
        if ($success) {
            wc_add_notice('Password reset email has been sent.');
        }
        
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }
}
add_action('template_redirect', 'process_custom_lost_password_form');

/**
 * Process reset password form
 */
function process_custom_reset_password_form() {
    if (isset($_POST['custom-woocommerce-reset-password']) && wp_verify_nonce($_POST['woocommerce-reset-password-nonce'], 'reset_password')) {
        $user = check_password_reset_key($_POST['reset_key'], $_POST['reset_login']);
        
        if (is_wp_error($user)) {
            wc_add_notice('This password reset key is invalid or has already been used. Please request a new password reset.', 'error');
            wp_redirect(add_query_arg('action', 'lostpassword', wc_get_page_permalink('myaccount')));
            exit;
        }
        
        if (isset($_POST['password_1'])) {
            if ($_POST['password_1'] !== $_POST['password_2']) {
                wc_add_notice('Passwords do not match.', 'error');
                wp_redirect(add_query_arg(array('key' => $_POST['reset_key'], 'login' => $_POST['reset_login']), wc_get_page_permalink('myaccount')));
                exit;
            }
            
            if (empty($_POST['password_1'])) {
                wc_add_notice('Please enter your password.', 'error');
                wp_redirect(add_query_arg(array('key' => $_POST['reset_key'], 'login' => $_POST['reset_login']), wc_get_page_permalink('myaccount')));
                exit;
            }
            
            // Change the password
            reset_password($user, $_POST['password_1']);
            
            wc_add_notice('Your password has been reset successfully. Please log in with your new password.');
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
}
add_action('template_redirect', 'process_custom_reset_password_form');

/**
 * Add custom CSS and JS for login/registration tabs
 */
function custom_wc_login_registration_scripts() {
    if (is_account_page() && !is_user_logged_in()) {
        ?>
        <style>
            .custom-wc-login-registration-container {
                max-width: 480px;
                margin: 0 auto 30px;
                padding: 25px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            }
            
            .custom-wc-login-registration-tabs {
                display: flex;
                margin-bottom: 25px;
                border-bottom: 1px solid #e5e5e5;
            }
            
            .custom-wc-tab {
                padding: 12px 20px;
                cursor: pointer;
                font-weight: 600;
                color: #555;
                transition: all 0.3s ease;
                position: relative;
            }
            
            .custom-wc-tab.active {
                color: #008296; /* Primary brand color */
            }
            
            .custom-wc-tab.active:after {
                content: '';
                position: absolute;
                bottom: -1px;
                left: 0;
                width: 100%;
                height: 3px;
                background-color: #008296; /* Primary brand color */
            }
            
            .custom-wc-tab-content {
                display: none;
            }
            
            .custom-wc-tab-content.active {
                display: block;
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .form-row {
                margin-bottom: 20px;
            }
            
            .form-row label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #333;
            }
            
            .form-row input[type="email"],
            .form-row input[type="password"] {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 15px;
                transition: border-color 0.3s ease;
            }
            
            .form-row input[type="email"]:focus,
            .form-row input[type="password"]:focus {
                border-color: #008296; /* Primary brand color */
                outline: none;
                box-shadow: 0 0 0 1px rgba(0, 130, 150, 0.2); /* Primary brand color with opacity */
            }
            
            .form-row button {
                background: #008296; /* Primary brand color */
                color: #fff;
                border: none;
                padding: 12px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-weight: 600;
                width: 100%;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            
            .form-row button:hover {
                background: #006d7d; /* Darker shade of primary color */
            }
            
            .lost-password,
            .no-account,
            .has-account,
            .privacy-policy-text,
            .back-to-login {
                margin-top: 15px;
                text-align: center;
                font-size: 14px;
            }
            
            .no-account a,
            .has-account a,
            .lost-password a,
            .back-to-login a {
                color: #008296; /* Primary brand color */
                text-decoration: none;
                font-weight: 500;
                transition: color 0.3s ease;
            }
            
            .no-account a:hover,
            .has-account a:hover,
            .lost-password a:hover,
            .back-to-login a:hover {
                color: #006d7d; /* Darker shade of primary color */
            }
            
            .remember-me {
                display: flex;
                align-items: center;
            }
            
            .remember-me input {
                margin-right: 8px;
            }
            
            .woocommerce-error {
                background-color: #f8d7da;
                color: #721c24;
                border-left: 4px solid #f5c6cb;
                padding: 12px 15px;
                margin-bottom: 20px;
                border-radius: 5px;
                font-size: 14px;
            }
            
            .woocommerce-message {
                background-color: #d4edda;
                color: #155724;
                border-left: 4px solid #c3e6cb;
                padding: 12px 15px;
                margin-bottom: 20px;
                border-radius: 5px;
                font-size: 14px;
            }
            
            .required {
                color: #e2401c;
            }
            
            /* Highlight elements with accent color */
            .custom-wc-tab:hover:not(.active) {
                color: #ffc549; /* Secondary brand color */
            }
            
            /* Additional styles for password reset */
            .lost-password-message, 
            .reset-password-message {
                margin-bottom: 20px;
                color: #555;
                font-size: 14px;
                line-height: 1.6;
            }
            
            /* Hide the Reset Password tab by default */
            .custom-wc-tab[data-tab="lostpassword"] {
                display: none;
            }
            
            /* Show the Reset Password tab when active */
            .custom-wc-tab.show-reset-tab {
                display: block;
            }
            
            /* Remove default WooCommerce account page title when not logged in */
            .woocommerce-account h1.entry-title,
            .woocommerce-account .woocommerce > h2 {
                display: none;
            }
        </style>
        
        <script>
            jQuery(document).ready(function($) {
                // Check URL parameters for password reset
                const urlParams = new URLSearchParams(window.location.search);
                const action = urlParams.get('action');
                const key = urlParams.get('key');
                const login = urlParams.get('login');
                
                // Show Reset Password tab if we're in the reset process
                if (action === 'lostpassword' || (key && login)) {
                    $('.custom-wc-tab[data-tab="lostpassword"]').addClass('show-reset-tab');
                }
                
                // Tab switching functionality
                $('.custom-wc-tab').on('click', function() {
                    // Remove active class from all tabs
                    $('.custom-wc-tab').removeClass('active');
                    $('.custom-wc-tab-content').removeClass('active');
                    
                    // Add active class to clicked tab
                    $(this).addClass('active');
                    $('#' + $(this).data('tab') + '-tab').addClass('active');
                });
                
                // Switch to registration tab when "Don't have an account" is clicked
                $('.switch-to-register').on('click', function(e) {
                    e.preventDefault();
                    $('.custom-wc-tab[data-tab="register"]').click();
                });
                
                // Switch to login tab when "Already have an account" is clicked
                $('.switch-to-login').on('click', function(e) {
                    e.preventDefault();
                    $('.custom-wc-tab[data-tab="login"]').click();
                });
                
                // Switch to reset password tab when "Forgot your password" is clicked
                $('.switch-to-reset').on('click', function(e) {
                    e.preventDefault();
                    $('.custom-wc-tab[data-tab="lostpassword"]').addClass('show-reset-tab').click();
                    
                    // Update URL without reloading the page
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('action', 'lostpassword');
                    window.history.pushState({}, '', newUrl);
                });
                
                // If there's an error message and we're on the account page, make sure the appropriate tab is shown
                if ($('.woocommerce-error').length > 0) {
                    // Check the error message to determine which tab to show
                    var errorText = $('.woocommerce-error').text().toLowerCase();
                    
                    if (errorText.indexOf('register') > -1) {
                        $('.custom-wc-tab[data-tab="register"]').click();
                    } else if (errorText.indexOf('password') > -1 || errorText.indexOf('reset') > -1) {
                        $('.custom-wc-tab[data-tab="lostpassword"]').addClass('show-reset-tab').click();
                    }
                }
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'custom_wc_login_registration_scripts');

/**
 * Override the default lost password URL to use our custom form
 */
function custom_lostpassword_url($url) {
    return add_query_arg('action', 'lostpassword', wc_get_page_permalink('myaccount'));
}
add_filter('lostpassword_url', 'custom_lostpassword_url', 20, 1);

/**
 * Add login/registration tabs to the WooCommerce account page
 */
function add_custom_tabs_to_myaccount_page($content) {
    if (!is_user_logged_in() && is_account_page()) {
        return do_shortcode('[custom_wc_login_registration_tabs]') . $content;
    }
    return $content;
}
add_filter('the_content', 'add_custom_tabs_to_myaccount_page', 999);
