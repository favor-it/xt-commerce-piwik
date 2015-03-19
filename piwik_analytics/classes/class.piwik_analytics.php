<?php
/**
 * Piwik Analytics Plugin for xt:Commerce - Tracking Code Integration
 *
 * This file is part of Piwik Analytics Plugin.
 *
 * Piwik Analytics Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Piwik Analytics Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Piwik Analytics Plugin. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   xt:Commerce Plugin
 * @package    Piwik Analytics
 * @author     Daniel Schumacher <info@favor-it.net>
 * @copyright  2015 Daniel Schumacher
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @link       http://www.favor-it.net/xtcommerce-plugins/piwik-analytics/
 *      
 */
defined ( '_VALID_CALL' ) or die ( 'Direct Access is not allowed.' );

/**
 * Piwik Analytics Plugin main class
 */
class piwikAnalytics {
	
	/**
	 * Output Piwik tracking code
	 */
	public function getPiwikCode() {
		
		// basic data for templates
		$tpl_data = array (
				'pa_url' => $this->getURL (),
				'pa_site_id' => PIWIK_ANALYTICS_SITE_ID 
		);
		
		// template file selection + additional data for templates
		$tpl = '';
		if (PIWIK_ANALYTICS_IMAGE_TRACKING == 'true') {
			$tpl = 'piwik_analytics_image_tracking.html';
		} else {
			
			if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
				$tpl = 'piwik_analytics.html';
			} else {
				$tpl = 'piwik_analytics_sync.html';
			}
			
			if (PIWIK_ANALYTICS_ECOMMERCE == 'true') {
				$tpl_data ['pa_ecommerce_data'] = $this->getEcommerceData ();
			} else {
				$tpl_data ['pa_ecommerce_data'] = '';
			}
		}
		
		$template = new Template ();
		$template->getTemplatePath ( $tpl, 'piwik_analytics', '', 'plugin' );
		echo $template->getTemplate ( 'piwik_analytics_smarty', $tpl, $tpl_data );
	}
	
	/**
	 * Get ecommerce data for product, category, cart and checkout
	 *
	 * @return string
	 */
	private function getEcommerceData() {
		global $p_info, $category, $success_order;
		
		$pa_ecommerce_addition = '';
		
		if (array_key_exists ( 'page', $_GET )) {
			
			switch ($_GET ['page']) {
				case 'product' :
					if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
						$pa_ecommerce_addition .= '  _paq.push([\'setEcommerceView\', "' . $this->escapeString ( $p_info->data ['products_model'] ) . '", "' . $this->escapeString ( $p_info->data ['products_name'] ) . '", "' . $this->escapeString ( $category->data ['categories_name'] ) . '", ' . round ( $p_info->data ['products_price'] ['plain'], 2 ) . ']);' . "\n";
					} else {
						$pa_ecommerce_addition .= '    piwikTracker.setEcommerceView("' . $this->escapeString ( $p_info->data ['products_model'] ) . '", "' . $this->escapeString ( $p_info->data ['products_name'] ) . '", "' . $this->escapeString ( $category->data ['categories_name'] ) . '", ' . round ( $p_info->data ['products_price'] ['plain'], 2 ) . ');' . "\n";
					}
					break;
				
				case 'categorie' :
					if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
						$pa_ecommerce_addition .= '  _paq.push([\'setEcommerceView\', productSku = false, productName = false, category = "' . $this->escapeString ( $category->data ['categories_name'] ) . '"]);' . "\n";
					} else {
						$pa_ecommerce_addition .= '    piwikTracker.setEcommerceView(productSku = false, productName = false, category = "' . $this->escapeString ( $category->data ['categories_name'] ) . '");' . "\n";
					}
					break;
				
				case 'cart' :
					foreach ( $_SESSION ['cart']->show_content as $key => $arr ) {
						if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
							$pa_ecommerce_addition .= '  _paq.push([\'addEcommerceItem\', "' . $this->escapeString ( $arr ['products_model'] ) . '", "' . $this->escapeString ( $arr ['products_name'] ) . '", ' . $this->getCategories ( $arr ['products_id'] ) . ', ' . round ( $arr ['products_price'] ['plain'], 2 ) . ', ' . $arr ['products_quantity'] . ']);' . "\n";
						} else {
							$pa_ecommerce_addition .= '    piwikTracker.addEcommerceItem("' . $this->escapeString ( $arr ['products_model'] ) . '", "' . $this->escapeString ( $arr ['products_name'] ) . '", ' . $this->getCategories ( $arr ['products_id'] ) . ', ' . round ( $arr ['products_price'] ['plain'], 2 ) . ', ' . $arr ['products_quantity'] . ');' . "\n";
						}
					}
					if (($_SESSION ['cart']->content_total ['plain']) != 0) {
						if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
							$pa_ecommerce_addition .= '  _paq.push([\'trackEcommerceCartUpdate\', ' . $_SESSION ['cart']->content_total ['plain'] . ']);' . "\n";
						} else {
							$pa_ecommerce_addition .= '    piwikTracker.trackEcommerceCartUpdate(' . $_SESSION ['cart']->content_total ['plain'] . ');' . "\n";
						}
					}
					break;
				
				case 'checkout' :
					if (array_key_exists ( 'page_action', $_GET ) && $_GET ['page_action'] == 'success') {
						$discount = 0;
						foreach ( $success_order->order_products as $key => $arr ) {
							$product = new product ( $arr ['products_id'] );
							if (array_key_exists ( 'old_plain', $product->data ['products_price'] )) {
								// special price
								$discount += $arr ['products_quantity'] * ($product->data ['products_price'] ['old_plain'] - $arr ['products_price'] ['plain']);
							} else {
								// no special price
								$discount += $arr ['products_quantity'] * ($product->data ['products_price'] ['plain'] - $arr ['products_price'] ['plain']);
							}
							
							if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
								$pa_ecommerce_addition .= '  _paq.push([\'addEcommerceItem\', "' . $this->escapeString ( $arr ['products_model'] ) . '", "' . $this->escapeString ( $arr ['products_name'] ) . '", ' . $this->getCategories ( $arr ['products_id'] ) . ', ' . round ( $arr ['products_price'] ['plain'], 2 ) . ', ' . $arr ['products_quantity'] . ']);' . "\n";
							} else {
								$pa_ecommerce_addition .= '    piwikTracker.addEcommerceItem("' . $this->escapeString ( $arr ['products_model'] ) . '", "' . $this->escapeString ( $arr ['products_name'] ) . '", ' . $this->getCategories ( $arr ['products_id'] ) . ', ' . round ( $arr ['products_price'] ['plain'], 2 ) . ', ' . $arr ['products_quantity'] . ');' . "\n";
							}
						}
						
						if (count ( $success_order->order_products ) > 0) {
							if (PIWIK_ANALYTICS_ASYNCHRONOUS_LOADING == 'true') {
								$pa_ecommerce_addition .= '  _paq.push([\'trackEcommerceOrder\', "' . $success_order->order_data ['orders_id'] . '", ' . round ( $success_order->order_total ['total'] ['plain'], 2 ) . ', ' . round ( $success_order->order_total ['product_total'] ['plain'], 2 ) . ', ' . round ( $success_order->order_total ['total'] ['plain'] - $success_order->order_total ['total_otax'] ['plain'], 2 ) . ', ' . round ( $success_order->order_total_data [0] ['orders_total_price'] ['plain'], 2 ) . ', ' . round ( $discount, 2 ) . ']);' . "\n";
							} else {
								$pa_ecommerce_addition .= '    piwikTracker.trackEcommerceOrder("' . $success_order->order_data ['orders_id'] . '", ' . round ( $success_order->order_total ['total'] ['plain'], 2 ) . ', ' . round ( $success_order->order_total ['product_total'] ['plain'], 2 ) . ', ' . round ( $success_order->order_total ['total'] ['plain'] - $success_order->order_total ['total_otax'] ['plain'], 2 ) . ', ' . round ( $success_order->order_total_data [0] ['orders_total_price'] ['plain'], 2 ) . ', ' . round ( $discount, 2 ) . ');' . "\n";
							}
						}
					}
					break;
			}
		}
		
		return $pa_ecommerce_addition;
	}
	
	/**
	 * Determine all categories of a product
	 *
	 * @param int $products_id        	
	 * @return string
	 */
	private function getCategories($products_id) {
		global $db, $language;
		
		$rs = $db->Execute ( "SELECT `categories_id` FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " WHERE `products_id`='" . $products_id . "'" );
		
		$pa_categories = array ();
		while ( ! $rs->EOF ) {
			
			$rs2 = $db->Execute ( "SELECT `categories_name` FROM " . TABLE_CATEGORIES_DESCRIPTION . " WHERE `categories_id`='" . $rs->fields ['categories_id'] . "' and `language_code`='" . $language->code . "' LIMIT 0,1" );
			
			if ($rs2->RecordCount () == 1 && $rs2->fields ['categories_name'] != '' && $rs2->fields ['categories_name'] != NULL) {
				$pa_categories [$rs->fields ['categories_id']] = $rs2->fields ['categories_name'];
			}
			
			$rs->MoveNext ();
		}
		
		$pa_categories_string = '[';
		$i = 0;
		$pa_number_of_categories = count ( $pa_categories );
		foreach ( $pa_categories as $pa_category ) {
			$pa_categories_string .= '"' . $this->escapeString ( $pa_category ) . '"';
			
			if (++ $i != $pa_number_of_categories) {
				$pa_categories_string .= ', ';
			}
		}
		$pa_categories_string .= ']';
		
		return $pa_categories_string;
	}
	
	/**
	 * Determine Piwik URL
	 *
	 * @return string
	 */
	private function getURL() {
		$url = PIWIK_ANALYTICS_URL;
		
		// prefix to be removed
		$prefix_list = array (
				'http://',
				'https://',
				'//',
				'/',
				'://' 
		);
		
		foreach ( $prefix_list as $prefix ) {
			if (substr ( $url, 0, strlen ( $prefix ) ) == $prefix) {
				$url = substr ( $url, strlen ( $prefix ) );
			}
		}
		
		return rtrim ( $url, "/" );
	}
	
	/**
	 * Escape strings
	 *
	 * @param string $input_string        	
	 * @return string
	 */
	private function escapeString($input_string) {
		return addslashes ( $input_string );
	}
}
?>