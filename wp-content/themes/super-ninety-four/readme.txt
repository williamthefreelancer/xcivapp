=== Super Ninety Four ===
Contributors: superninetyfour
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.3
Stable tag: 2.32.0
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
automation stay on SureContact and OttoKit. SureForms submissions bridge into
the WooCommerce sales funnel via the theme funnel API (leads, quote cart,
workshop interest, reciprocal / apply deep-links).

== Changelog ==

= 2.32.0 =
* Align theme headers: Requires PHP 8.3, License + License URI in style.css
* Sync Stable tag with style.css / SNF_VERSION (2.32.0)
* Move club roles, member guards, admin-bar + Apply applicant tagging into theme
* Drop Content Control / Code Snippets plugin sync (plugins removed)

= 2.28.0 =
* SureForms funnel bridge: form→intent map, embeds on reciprocal/quote, REST webhooks
* Custom integration API snf/v1/funnel/* + Pro native-action registration
* Funnel leads CPT; OttoKit actions for SureForms quote/workshop/reciprocal/apply

= 2.26.0 =
* Expo reuses media ICE settings for walkie WebRTC
* Expo members rail for live/hybrid broadcasts from media library
* Listing-grid block sources: expo scenic drives, gatherings, live
* TicketSpice-style ticket addons/upsells/interactivities on events + Woo forms
* Strip UTF-8 BOM from functions.php / inc shims (fixes login cookie headers)

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
