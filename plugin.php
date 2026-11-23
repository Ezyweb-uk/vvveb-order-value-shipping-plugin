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

/*
Name: Order Value Shipping
Slug: order-value-shipping
Category: shipping
Url: https://github.com/Ezyweb-uk/vvveb-order-value-shipping-plugin
Description: Shipping charged by order value tiers, per destination region group, with an optional free shipping threshold.
Author: Ezyweb.uk
Author url: https://ezyweb.uk
Version: 0.3
Thumb: order-value-shipping.svg
Settings: /admin/index.php?module=plugins/order-value-shipping/settings
*/

use function Vvveb\getSetting;
use Vvveb\Plugins\OrderValueShipping\Shipping as ShippingMethod;
use function Vvveb\slugify;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Locale;
use Vvveb\System\Shipping as ShippingApi;

if (! defined('V_VERSION')) {
	die('Invalid request!');
}

class OrderValueShipping {
	private $namespace = 'order_value_shipping';

	public function init() {
		$settings = getSetting($this->namespace, null, []);
		$shipping = ShippingApi::getInstance();

		foreach ($settings as $method) {
			$lang = $method['lang'][Locale::getLanguageId()] ?? [];

			if ($lang) {
				unset($method['lang']);
				$method += $lang;
			}

			$name = slugify($lang['title'] ?? $this->namespace);
			$shipping->registerMethod($name, ShippingMethod::class, $method);
		}
	}

	public function admin() {
		$request = Request::getInstance();
		$module  = $request->get['module'] ?? '';

		if ($module == 'order/order' || $module == 'settings/site') {
			$this->init();
		}
	}

	public function app() {
		Event::on('Vvveb\Controller\Base', 'init', __CLASS__, function ($site) {
			$request = Request::getInstance();
			$route = $request->get['route'] ?? '';
			$module = $request->get['module'] ?? '';

			if ($route == '/checkout' || $module == 'checkout/checkout' || $module == 'checkout/checkout/index' 
				|| $module == 'cart' || $module == 'cart/cart' || $module == 'cart/cart/index') {
				$this->init();
			}

			return [$site];
		});
	}

	public function __construct() {
		if (\Vvveb\isEditor()) {
			return;
		}

		if (APP == 'admin') {
			$this->admin();
		} elseif (APP == 'app') {
			$this->app();
		}
	}
}

$orderValueShipping = new OrderValueShipping();
