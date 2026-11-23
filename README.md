# Order Value Shipping for Vvveb

A [Vvveb CMS](https://www.vvveb.com) shipping plugin that charges shipping by **order value**, per destination,
in the style of Magento's table rates (price vs destination): each shipping method covers one region group and
has a list of value tiers, plus an optional free shipping threshold.

Based on Vvveb's [Default Shipping plugin](https://github.com/Vvveb/shipping) by Ziadin Givan, adapted by
Ezyweb.uk.

## Features

- **Several shipping methods**, each with its own title, description and free shipping message per language,
  sort order, tax class and shipping status.
- **Destination:** each method can be limited to a region group (Settings › Region groups), e.g. "UK" or
  "Europe". At checkout only the methods for the customer's country (and region, where the group lists regions)
  are offered. "All regions" offers the method everywhere.
- **Order value tiers:** "From order value X, shipping costs Y". The highest tier the cart subtotal reaches
  applies, so tiers can be entered in any order. With no tiers, or below the lowest tier, the method's base
  cost applies.
- **Free shipping threshold:** shipping is free once the order total reaches it. A cost of 0 also shows as
  "Free shipping". Below the threshold the cart can show a message such as "Add more products worth at least
  £12.00 to get free shipping!".

Tiers compare the cart **subtotal** (before tax and shipping) and the free shipping threshold compares the
**order total**, the same as the Default Shipping plugin.

Example, a "UK delivery" method limited to a UK region group:

| From order value | Shipping |
|---|---|
| 0 | 6.00 |
| 50 | 4.00 |
| 150 | 2.00 |

with free shipping from 250: a £99 order pays £4.00, a £160 order £2.00, and a £260 order nothing.

## Requirements

- Vvveb 1.0.9.0 or later.
- Front end: tested with the Landing theme (Oct 2026). The plugin adds nothing to the theme: Vvveb lists its
  methods in the checkout's shipping section and adds the cost to the totals.

## Installation

1. Download `order-value-shipping.zip` from the [latest release](../../releases/latest).
2. In the Vvveb admin go to **Plugins › Add new**, upload the zip and activate **Order Value Shipping**.
3. Open its **Settings**, click **Add shipping method**, choose the region group, add the value tiers with
   **Add rate** and click **Save**.

Manual install: copy the files to `plugins/order-value-shipping/` and the `public/` folder to
`public/plugins/order-value-shipping/`, then activate the plugin. The folder must be named
`order-value-shipping` (the plugin's slug).

## Using it with CSP Manager

Order Value Shipping is built to sit on a checkout protected by a strict Content Security Policy, such as the
one Ezyweb's [CSP Manager](https://github.com/Ezyweb-uk/vvveb-csp-manager-plugin) sends:

- **Nothing to allow.** The plugin works entirely on the server: it adds no scripts, styles, frames or
  third-party hosts to the storefront, so CSP Manager's Checkout profile needs no extra sources for it and it
  causes no violation reports. Its settings page is in the admin, which CSP Manager doesn't touch.
- **But it depends on the checkout's address scripts.** The methods offered, and their cost, depend on the
  country and region the customer chooses. When the country changes, the theme's checkout script loads that
  country's regions and then asks the server for the shipping methods again. On the stock Landing theme that
  script needs a small inline setting (`regionsUrl`), and "ship to a different address" is an inline `onclick`.
  When CSP Manager **enforces** its policy, the browser blocks both, so the shipping section isn't refreshed:
  in testing, a customer who switched from the United States to the United Kingdom was still offered, and
  charged, US delivery.
- **What keeps it working:** CSP Manager 0.5 or later adds a small compatibility script whenever its enforced
  policy blocks the theme's inline code, which restores the region list, the shipping refresh and the address
  toggles from an allowed file. (With CSP Manager 0.4, the
  [PayPal Smart Buttons plugin](https://github.com/Ezyweb-uk/vvveb-paypal-smart-buttons-plugin) 0.4 or later
  supplied it, so it only worked with that plugin active.) A theme that has moved this code into its own
  script files works too. In CSP Manager's report-only mode nothing is blocked.

Tested on Vvveb 1.0.9.0 with Landing:

| Checkout | United Kingdom chosen | United States chosen |
|---|---|---|
| CSP Manager report-only | UK delivery, +4.00 | US delivery, +15.00 |
| CSP Manager 0.5 enforce (with or without PayPal Smart Buttons) | UK delivery, +4.00 | US delivery, +15.00 |

If you enforce the policy with CSP Manager 0.4, keep PayPal Smart Buttons 0.4 or later active (it supplied the
script before CSP Manager 0.5), or update CSP Manager. Without either, a customer who changes country is still
offered the previous country's shipping.

## Version history

- **0.3** (2026-11-23): checked with Vvveb 1.0.9.0 and the Oct 2026 Landing theme. Tiers are sorted by
  "from order value", so they work in any entry order (they had to be entered lowest first). Plugin list icon
  fixed (it named a file the plugin didn't have) and changed to the admin's Tabler truck style. Copyright
  notice, AGPLv3 LICENSE and this README added.
- **0.2**: settings page updated for the Vvveb 1.0.9.0 admin (SVG icons, new header layout).
- **0.1**: order value tiers per region group, based on the Default Shipping plugin.

## Licence

Copyright (C) 2022 Ziadin Givan (Vvveb Default Shipping plugin) and Copyright (C) 2026 Ezyweb.uk (order value
tiers, destination matching and the other changes). Released under the GNU Affero General Public License v3 or
later (see LICENSE), the licence the Default Shipping plugin's files carried when this plugin was based on them.
Built for Vvveb CMS by Ziadin Givan, which is also AGPLv3.
