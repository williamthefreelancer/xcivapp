=== Super Ninety Four ===
Contributors: superninetyfour
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 2.23.0
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Custom Super Ninety Four theme for events, service inquiries, memberships, and reciprocal clubs.

== Description ==

Homepage search for events and services, Lifestyle Concierge workshop landing,
community dashboard listings, WooCommerce account hub, BuddyPress profile shell,
Messages desk, Stripe Checkout + Supabase catalog bridge, and Expo Marketplace
lead-rider map.

Build asynchronous behaviors with jQuery and taxonomies while extending the
WordPress REST API functionality to turn this theme into the web app I want it
to be. Marketing canvases use theme Gutenberg blocks plus SureForms; CRM and
automation stay on SureContact and OttoKit.

== Changelog ==

= 2.23.0 =
* Videos & Audio CPT library (snf_video, snf_audio) with topics/series/channels/formats
* Hybrid/live broadcast meta on event products; WebRTC audio room signaling REST
* Dedicated /videos-audio/ hub template; shared YouTube embed for events/workshop

= 2.22.0 =
* Composer stripe/stripe-php SDK for Checkout + webhook verify
* Admin API health probes (Stripe / Supabase / Mapbox)
* OttoKit-friendly snf_* actions; SureForms public-page host rules
* Theme blocks: page-hero, cta-button, listing-grid, form-slot

= 2.21.0 =
* Expo marketplace REST catalog (snf/v1/marketplace) with product_cat facets
* jQuery async search/chips on Expo + Shop SERP
* Theme description documents the REST / jQuery / taxonomies web-app direction

= 2.13.1 =
* Collapse Expo Marketplace into a single public /expo/ page
* Remove expo-marketplace-by-members and expo-marketplace-by-non-members

= 2.13.0 =
* Restored auto-created funnel pages (about, select-events, expo, service inquiries)
* Wired reciprocal hub/dashboard cards to Woo product landings
* Billing invoice empty state; account + BuddyPress copy polish
* Mapbox token field on SNF Stripe settings; version sync

= 1.5.8 =
* Added critical WordPress theme files: single, archive, search, comments, singular, readme.txt
* Added root .htaccess and uploads/index.php

= 1.5.7 =
* Search includes service inquiries
* Logged-out non-member events route to Apply (workshop stays direct)
