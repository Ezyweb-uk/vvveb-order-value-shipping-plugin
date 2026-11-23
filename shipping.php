<?php

/**
 * Order Value Shipping for Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 * Copyright (C) 2026  Ezyweb.uk
 *
 * Based on the Vvveb Default Shipping plugin (https://github.com/Vvveb/shipping),
 * adapted by Ezyweb.uk to charge by order value and destination, like
 * Magento's table rates (price vs destination).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Vvveb\Plugins\OrderValueShipping;

use function Vvveb\__;
use function Vvveb\getSetting;
use Vvveb\Sql\Region_groupSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Core\View;
use Vvveb\System\ShippingMethod;

if (! defined('V_VERSION')) {
	die('Invalid request!');
}

class Shipping extends ShippingMethod {
	private $namespace = 'order_value_shipping';

	private $method_data = [
		'name'            => 'order_value_shipping',
		'title'           => 'Order Value Shipping',
		'description'     => 'Order value tier based shipping',
		'cost'            => 0,
		'tax_type_id'     => 0,
		'region_group_id' => 0,
		'free_shipping'   => 0,
		'difference'      => 0,
		'text'            => '',
		'free_message'    => 'Add more products worth at least %s to get free shipping!',
	];

	private function getCost() {
		$subtotal = $this->cart->getSubTotal();
		$total    = $this->cart->getGrandTotal();

		$cost = floatval($this->method_data['cost'] ?? 0);

		// Check if subtotal tiers exist for this method
		if (isset($this->method_data['rates']) && is_array($this->method_data['rates'])) {
			// Highest "price from" first, so the tiers work whatever order they were entered in
			$rates = $this->method_data['rates'];
			usort($rates, fn ($a, $b) => floatval($b['price_from'] ?? 0) <=> floatval($a['price_from'] ?? 0));

			foreach ($rates as $rate) {
				$priceFrom = floatval($rate['price_from'] ?? 0);
				if ($subtotal >= $priceFrom) {
					$cost = floatval($rate['cost'] ?? 0);
					break; // Found matching tier
				}
			}
		}

		// Apply free shipping override if threshold met
		$freeShipping = floatval($this->method_data['free_shipping'] ?? 0);
		if ($freeShipping > 0 && $total >= $freeShipping) {
			$cost = 0;
		}

		$this->method_data['cost'] = $cost;

		if ($this->method_data['cost'] == 0) {
			$this->method_data['text'] = __('Free shipping');
		}

		if ($freeShipping > 0) {
			$this->method_data['difference'] = max($freeShipping - $total, 0);
		} else {
			$this->method_data['difference'] = 0;
		}
	}

	public function getMethod(array &$checkoutInfo = [], array $options = []): array|false {
		$this->method_data = $options + $this->method_data;

            $shipToDifferent = !empty($_POST['ship_to_different_address'])
                || !empty($checkoutInfo['ship_to_different_address']);

		// 1. Safe parameter extraction: POST -> checkoutInfo array -> Site Defaults
		if ($shipToDifferent) {
			$shipping_country_id = $_POST['shipping_address']['country_id']
				?? $_POST['shipping_country_id']
				?? $checkoutInfo['shipping_country_id']
				?? $checkoutInfo['billing_country_id']
				?? $checkoutInfo['country_id']
				?? $this->global['site']['country_id']
				?? 222;

			$shipping_region_id = $_POST['shipping_address']['region_id']
				?? $_POST['shipping_region_id']
				?? $checkoutInfo['shipping_region_id']
				?? $checkoutInfo['billing_region_id']
				?? $checkoutInfo['region_id']
				?? $this->global['site']['region_id']
				?? 0;
		} else {
			$shipping_country_id = $_POST['billing_address']['country_id']
				?? $_POST['billing_country_id']
				?? $checkoutInfo['billing_country_id']
                                ?? $checkoutInfo['shipping_country_id']
				?? $checkoutInfo['country_id']
				?? $this->global['site']['country_id']
				?? 222;

			$shipping_region_id = $_POST['billing_address']['region_id']
				?? $_POST['billing_region_id']
				?? $checkoutInfo['billing_region_id']
                                ?? $checkoutInfo['shipping_region_id']
				?? $checkoutInfo['region_id']
				?? $this->global['site']['region_id']
				?? 0;
		}

            // Convert to integers
            $shipping_country_id = (int) $shipping_country_id;
            $shipping_region_id  = (int) $shipping_region_id;

		// 2. Region group match
		$region_group_id = (int) ($this->method_data['region_group_id'] ?? 0);

		if ($region_group_id != 0) {
                        if ($shipping_country_id <= 0) {
				return false;
			}

			$region  = new Region_groupSQL();
			$params = [
				'region_group_id' => $region_group_id,
                            'country_id'      => $shipping_country_id
			];

                        if ($shipping_region_id > 0) {
                            $params['region_id'] = $shipping_region_id;
                        }

			$regions = $region->isRegion($params);

			if (!isset($regions['count']) || $regions['count'] == 0) {
				return false; // Country/region does not match this method
			}
		}

            // Resolve language texts
		$language_id = $checkoutInfo['language_id'] ?? 1;

		$displayText = $this->method_data['lang'][$language_id]['title']
			?? $this->method_data['title']
			?? $this->method_data['name']
			?? 'Order Value Shipping';

		$displayDescription = $this->method_data['lang'][$language_id]['description']
			?? $this->method_data['description']
			?? '';

            // Set unique method key
		$methodKey = $this->method_data['shipping_method_id'] ?? $this->method_data['code'] ?? $this->namespace;

		$this->method_data['id']                 = 'order_value_shipping';
		$this->method_data['key']                = 'order_value_shipping';
		$this->method_data['title']              = $displayText;
		$this->method_data['name']               = $displayText;
		$this->method_data['description']        = $displayDescription;
		$this->method_data['code']               = $this->namespace . '.' . $methodKey;
		$this->method_data['shipping_method_id'] = $methodKey;

		$this->getCost();
		
		if (isset($_SESSION['cart']['shipping_method']) && str_contains($_SESSION['cart']['shipping_method'], $this->namespace)) {
			$this->setMethod();
		}
	
		return $this->method_data;
	}

	public function init(): void {
		$this->cart->removeTotal($this->namespace);
		$this->cart->removeTax($this->namespace);
	}

	public function setMethod(): void {
		$view = View::getInstance();

		if ($this->method_data['cost'] || $this->method_data['text'] || $this->method_data['difference']) {
			if ($this->method_data['free_message'] && $this->method_data['difference']) {
				$currency                       = Currency::getInstance();
				$view->info['shipping-message'] = sprintf($this->method_data['free_message'], $currency->format($this->method_data['difference']));
			}

			if (!empty($this->method_data['tax_type_id'])) {
				$this->cart->addTax($this->namespace, $this->method_data['cost'], $this->method_data['tax_type_id']);
			}

			$this->cart->addTotal('shipping', $this->namespace, $this->method_data['title'], $this->method_data['cost'], $this->method_data['text']);
		}
	}

	public function ship(array &$checkoutInfo = []): bool {
		return true;
	}
}