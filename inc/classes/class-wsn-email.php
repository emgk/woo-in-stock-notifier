<?php
/**
 * InStockNotifier
 *
 * @author Govind Kumar
 * @version 1.0.0
 * @package InStockNotifier/Classes
 */

namespace InStockNotifier;

use WC_Email;

/**
 * In-Stock Notifier - WooCommerce Plugin
 * Copyright (C) 2017 Govind Kumar <gkprmr@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published
 * by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

defined('ABSPATH') or die;

if (!class_exists('WSN_Email')) {

    /**
     * Class Email
     * @package InStockNotifier
     */
    class WSN_Email extends WC_Email
    {


        private $users = [];

        /**
         * WSN_Email constructor.
         *
         * @since 1.0.0
         */
        public function __construct()
        {

            $this->id = 'instock_notifier_users_mailout';
            $this->title = __('In-Stock Notifier', 'in-stock-notifier');
            $this->description = __('When a product is Out-of-Stock and when it comes In-Stock again, this email is sent to all users registered in the waiting list for that product.', 'in-stock-notifier');
            $this->heading = __('{product_title} now back in stock at {blogname}', 'in-stock-notifier');
            $this->subject = __('A product you are waiting for is back in stock', 'in-stock-notifier');
            $this->template_base = WSN_EMAIL_TEMPLATE_PATH;
            $this->template_html = 'wsn-email-template.php';
            $this->template_plain = 'plain/wsn-email-template.php';
            $this->customer_email = true;

            // Add action to send the email.
            add_action('send_wsn_email_mailout', array($this, 'trigger'), 10, 2);

            // WC_Email Constructor.
            parent::__construct();
        }

        /**
         * Trigger Email function
         *
         * @access public
         *
         * @param mixed $users Waitlist users array.
         * @param integer $product_id Product id.
         *
         * @return void
         * @since 1.0.0
         *
         */
        public function trigger($users, $product_id)
        {
			error_log('trigger');
			error_log(print_r($users, true));

            // ["one@gmail.com", "two@gmail.com", "three@gmail.com"] à remplacer par $users
            //$this->users = ["nico.mollet@gmail.com", "nmollet@thalasso-saintmalo.com"];
            $this->users = $users;
            // get the product
            $this->product = wc_get_product($product_id);

            // return
            if (!$this->product || !$this->is_enabled()) {
                return;
            }

            // Replace product_title in email template.
            $this->placeholders = array(
                '{product_title}' => $this->product->get_formatted_name()
            );

            // build header
            $header = $this->get_headers() . "\r\n";

            // send email
            //implode( ',', $users )
            $response = $this->send($this->get_from_address(), $this->get_subject(), $this->get_content(), $header, $this->get_attachments());

            // Return true in wsn_email_send_response.
            if ($response) {
                add_filter('wsn_email_send_response', '__return_true');
            }

        }


//        /**
//         * returnUsers Email function
//         *
//         * @access public
//         * @param mixed $users Waitlist users array.
//         * @return array
//         * @since 1.0.0
//         *
//         */
//        public function returnUsers($users)
//        {
//
//            return $users;
//        }




        /**
         * Get the html content of the email.
         *
         * @access public
         * @return string
         * @since 1.0.0
         */
        public function get_content_html()
        {

            ob_start();

            // Get Woo commerce template.
            wc_get_template(
                $this->template_html,
                array(
                    'product_title' => $this->product->get_formatted_name(),
                    'product_link' => get_permalink($this->product->get_id()),
                    'email_heading' => $this->get_heading(),
                ),
                false,
                $this->template_base
            );

            return ob_get_clean();
        }

        /**
         * Get the plain content of the email.
         *
         * @access public
         * @return string
         * @since 1.0.0
         */
        public function get_content_plain()
        {

            ob_start();

            wc_get_template($this->template_plain, array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
            ));

            return ob_get_clean();
        }

        public function get_headers()
        {


            $header = 'Content-Type: ' . $this->get_content_type() . "\r\n";

            if (in_array($this->id, array('new_order', 'cancelled_order', 'failed_order'), true)) {
                if ($this->object && $this->object->get_billing_email() && ($this->object->get_billing_first_name() || $this->object->get_billing_last_name())) {
                    $header .= 'Reply-To: ' . $this->object->get_billing_first_name() . ' ' . $this->object->get_billing_last_name() . ' <' . $this->object->get_billing_email() . ">\r\n";
                }
            } elseif ($this->get_from_address() && $this->get_from_name()) {
	            //$arrayClients = array('04@test.com', '05@test.com', '06@test.com');

	            //$clientsSeparated = implode(",",$arrayClients);
	            $header .= 'Reply-To: ' . $this->get_from_name() . ' <' . $this->get_from_address() . ">\r\n";
	            //$header .= 'cc: 01@test.com, 02@test.com , 03@test.com'."\r\n";
	            //$header.= 'cc: '.$clientsSeparated."\r\n";
	            //$header.= 'cc: '.$this->get_recipient() ."\r\n";
	            //$header.= 'cc: '.implode(",",wsn_get_waitlist)."\r\n";
	            //$header.= 'bcc: 01@test.com, 02@test.com , 03@test.com'."\r\n";

	            $header .= 'bcc :' . implode( ",", $this->users ) . "\r\n";

            }

			error_log('$header');
	        error_log(print_r($header, true));

	        return apply_filters('woocommerce_email_headers', $header, $this->id, $this->object, $this);
        }


    }
}

//end if()
