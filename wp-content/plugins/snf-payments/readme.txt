=== SUPER NINETY FOUR Payments ===
Contributors: superninetyfour
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later

WooCommerce payment gateway with Stripe Payment Element and SNF branded checkout fields.

== Description ==

Accept card payments on-site via Stripe Elements. Custom Apply / workshop / event fields map into WooCommerce orders.

== Installation ==

1. Activate the plugin.
2. WooCommerce → Settings → Payments → SUPER NINETY FOUR → Enable.
3. Keys are read from theme / SNF_STRIPE_* when present; otherwise enter them in gateway settings.
4. Add Stripe webhook: /wp-json/snf-payments/v1/stripe/webhook
   Events: payment_intent.succeeded, payment_intent.payment_failed, charge.refunded

== Changelog ==

= 1.0.1 =
* Enable gateway by default; force classic checkout (Blocks incompatible with Elements flow).
* Confirm PaymentIntent before place-order (fixes Woo "Invalid response" after success).
* Fix webhook signature verification against theme helper.

= 1.0.0 =
* Initial release — Stripe Elements gateway, checkout fields, PaymentIntent webhooks.
