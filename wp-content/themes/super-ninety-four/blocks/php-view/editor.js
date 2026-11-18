(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var __ = wp.i18n.__;

  var views = [
    { label: "Front page", value: "front-page" },
    { label: "Home (blog)", value: "home" },
    { label: "Index", value: "index" },
    { label: "Page", value: "page" },
    { label: "Single", value: "single" },
    { label: "Archive", value: "archive" },
    { label: "Search", value: "search" },
    { label: "404", value: "404" },
    { label: "Archive event", value: "archive-event" },
    { label: "Single event", value: "single-event" },
    { label: "Apply Hub", value: "apply-hub" },
    { label: "About", value: "about" },
    { label: "Blank Canvas", value: "blank-canvas" },
    { label: "Expo Marketplace", value: "expo-marketplace" },
    { label: "Home page template", value: "home-page" },
    { label: "Legal", value: "legal" },
    { label: "Member Login", value: "member-login" },
    { label: "Messages", value: "messages" },
    { label: "Public Content", value: "public-content" },
    { label: "Reciprocal Club", value: "reciprocal-club" },
    { label: "Reciprocal Hub", value: "reciprocal-hub" },
    { label: "Select Events", value: "select-events" },
    { label: "Under Consideration", value: "under-consideration" },
    { label: "Videos & Audio", value: "videos-audio" },
    { label: "WooCommerce Account", value: "woocommerce-account" },
    { label: "Billing Details", value: "billing-details" },
    { label: "BuddyPress", value: "buddypress" },
  ];

  registerBlockType("snf/php-view", {
    edit: function (props) {
      var attrs = props.attributes;
      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __("PHP view", "super-ninety-four"), initialOpen: true },
            el(SelectControl, {
              label: __("Template view", "super-ninety-four"),
              value: attrs.view || "page",
              options: views,
              onChange: function (value) {
                props.setAttributes({ view: value });
              },
            })
          )
        ),
        el(
          "div",
          { className: "snf-block snf-block-php-view snf-block--editor" },
          el("strong", null, __("SNF PHP View", "super-ninety-four")),
          el("p", null, (attrs.view || "page"))
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
