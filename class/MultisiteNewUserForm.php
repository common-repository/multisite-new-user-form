<?php

class MultisiteNewUserForm {

    public static $counter = 0;
    
    public function __construct() {
        self::$counter++;
        add_action('admin_action_createuser', array($this, 'listenPost'), 3);
        add_action('user_new_form', array($this, 'passwordFields'));
        add_action('admin_notices', array($this, 'printMessages'));
    }

    static function install() {
        if (!is_multisite()) {
            wp_die(__('This plugin must be used in multisite.'));
        }        
    }

    public function printMessages() {
        if ($transientMessage = get_site_transient('multisite-new-user-form')) {
            $transientMessage = unserialize($transientMessage);

            printf('<div class="notice %1$s"><p>%2$s</p></div>', $transientMessage['type'], $transientMessage['message']);

            delete_site_transient('multisite-new-user-form');
        } 
    }

    public function passwordFields()
    {
        if (self::$counter++ === 2) {
            $new_firstname = isset($_POST['first_name']) ? wp_unslash($_POST['first_name']) : '';
            $new_lastname = isset($_POST['last_name']) ? wp_unslash($_POST['last_name']) : ''; ?>

            <table class="form-table custom-fields">
                <tbody>
                <tr class="form-field">
                    <th scope="row"><label for="first_name"><?php _e('First Name') ?> </label></th>
                    <td><input name="first_name" type="text" id="first_name"
                               value="<?php echo esc_attr($new_firstname); ?>"></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><label for="last_name"><?php _e('Last Name') ?> </label></th>
                    <td><input name="last_name" type="text" id="last_name"
                               value="<?php echo esc_attr($new_lastname); ?>"></td>
                </tr>
                <tr class="form-field form-required user-pass1-wrap">
                    <th scope="row">
                        <label for="pass1">
                            <?php _e('Password'); ?>
                        </label>
                    </th>
                    <td>
                        <input class="hidden" value=" "/><!-- #24364 workaround -->
                        <button type="button"
                                class="button wp-generate-pw hide-if-no-js"><?php _e('Show password'); ?></button>
                        <div class="wp-pwd hide-if-js">
                            <?php $initial_password = wp_generate_password(24); ?>
                            <span class="password-input-wrapper">
                                    <input type="password" name="pass1" id="pass1" class="regular-text"
                                           autocomplete="off"
                                           data-reveal="1" data-pw="<?php echo esc_attr($initial_password); ?>"
                                           aria-describedby="pass-strength-result">
                                </span>
                            <button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0"
                                    aria-label="<?php esc_attr_e('Hide password'); ?>">
                                <span class="dashicons dashicons-hidden"></span>
                                <span class="text"><?php _e('Hide'); ?></span>
                            </button>
                            <button type="button" class="button wp-cancel-pw hide-if-no-js" data-toggle="0"
                                    aria-label="<?php esc_attr_e('Cancel password change'); ?>">
                                <span class="text"><?php _e('Cancel'); ?></span>
                            </button>
                            <div style="display:none" id="pass-strength-result" aria-live="polite"></div>
                        </div>
                    </td>
                </tr>
                <input type="hidden" name="mnuf_security" value="mnuf">
                </tbody>
            </table>
            <?php
        }
    }

    public function listenPost() {
        if (isset($_POST['mnuf_security']) AND 'mnuf' === $_POST['mnuf_security']) {
            if (!is_multisite()) {
                wp_die(__('This plugin must be used in multisite.'));
            }
            else if (!current_user_can('create_users')) {
                wp_die(__('Cheatin&#8217; uh?'));
            }
            
            check_admin_referer('create-user', '_wpnonce_create-user');

            $email_address = $_POST['email'];
            $username = $_POST['user_login'];
            $password = $_POST['pass1'];
            $role = $_POST['role'];
            $first_name = esc_attr($_POST['first_name']);
            $last_name = esc_attr($_POST['last_name']);
            $user_details = wpmu_validate_user_signup($username, $email_address);
            $available_roles = array();

            foreach (get_editable_roles() as $key => $value) {
                $available_roles[] = $key;
            }

            if (is_wp_error($user_details['errors']) && !empty($user_details['errors']->errors)) {
                $add_user_errors = $user_details['errors'];
            }
            else {
                $user_id = wp_create_user($user_details['user_name'], $password, $user_details['user_email']);
                add_user_meta($user_id, 'primary_blog', get_current_blog_id());

                if (isset($first_name)) {
                    update_user_meta($user_id, 'first_name', $first_name);
                }
                
                if (isset($last_name)) {
                    update_user_meta($user_id, 'last_name', $last_name);
                }
                
                $user = new WP_User($user_id);
                $user->set_role($role);

                set_site_transient('multisite-new-user-form', serialize(array('type' => 'updated', 'message' => 'User has been created.')), 10);

                wp_redirect(admin_url('user-new.php'));
                die();                
            }
        }
    }
}
