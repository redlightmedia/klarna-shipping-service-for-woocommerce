<?php
/**
 * Shipping method class file.
 *
 * @package KlarnaShippingService/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Shipping_Method' ) ) {

	/**
	 * Shipping method class.
	 */
	class Klarna_Shipping_Serivce_For_WooCommerce_Shipping_Method extends WC_Shipping_Method {

		/**
		 * Class constructor.
		 *
		 * @param integer $instance_id The instance id.
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'klarna_kss';
			$this->instance_id        = absint( $instance_id );
			$this->title              = 'Klarna Shipping Service';
			$this->method_title       = __( 'Klarna Shipping Service', 'klarna-shipping-service-for-woocommerce' );
			$this->method_description = __( 'Enables Klarna Shipping Service for WooCommerce', 'klarna-shipping-service-for-woocommerce' );
			$this->supports           = array(
				'shipping-zones',
			);
			$this->kss_tax_amount     = false;
			add_filter( 'woocommerce_shipping_packages', array( $this, 'kss_add_tax' ) );
		}

		/**
		 * Check if shipping method should be available.
		 *
		 * @param array $package The shipping package.
		 * @return boolean
		 */
		public function is_available( $package ) {
			if ( null !== WC()->session->get( 'kco_kss_enabled' ) && WC()->session->get( 'kco_kss_enabled' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Calculate shipping cost.
		 *
		 * @param array $package The shipping package.
		 * @return void
		 */
		public function calculate_shipping( $package = array() ) {
			$label = 'Klarna Shipping Service';
			$cost  = 0;
			if ( null !== WC()->session->get( 'kco_wc_order_id' ) ) {
				$klarna_order = KCO_WC()->api->get_klarna_order( WC()->session->get( 'kco_wc_order_id' ) );
				if ( isset( $klarna_order['selected_shipping_option'] ) ) {
					$label                = $klarna_order['selected_shipping_option']['name'];
					$cost                 = floatval( $klarna_order['selected_shipping_option']['price'] - $klarna_order['selected_shipping_option']['tax_amount'] ) / 100;
					$tax_amount           = floatval( $klarna_order['selected_shipping_option']['tax_amount'] ) / 100;
					$this->kss_tax_amount = $tax_amount;

					$rate = array(
						'id'    => $this->get_rate_id(),
						'label' => $label,
						'cost'  => $cost,
					);
				}
				$this->add_rate( $rate );
			}
		}

		/**
		 * Add tax amount to shipping.
		 *
		 * @param array $packages packages.
		 * @return array
		 */
		public function kss_add_tax( $packages ) {
			if ( false !== $this->kss_tax_amount ) {
				foreach ( $packages as $i => $package ) {
					foreach ( $package['rates'] as $rate_key => $rate_values ) {
						if ( 'klarna_kss' === $rate_values->method_id ) { // check that the shipping is KSS.
							$taxes = array();
							foreach ( $package['rates'][ $rate_key ]->taxes as $key => $tax ) {
								// set the KSS tax amount in the taxes array.
								$taxes[ $key ] = $this->kss_tax_amount;
							}
							// Set the tax amount.
							$package['rates'][ $rate_key ]->taxes = $taxes;
						}
					}
				}
			}
			return $packages;
		}

	}

	add_filter( 'woocommerce_shipping_methods', 'add_kss_shipping_method' );
	/**
	 * Registers the shipping method.
	 *
	 * @param array $methods WooCommerce shipping methods.
	 * @return array
	 */
	function add_kss_shipping_method( $methods ) {
		$methods['klarna_kss'] = 'Klarna_Shipping_Serivce_For_WooCommerce_Shipping_Method';
		return $methods;
	}
}
