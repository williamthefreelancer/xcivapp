(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var TextControl = wp.components.TextControl;
  var SelectControl = wp.components.SelectControl;
  var __ = wp.i18n.__;

  var targets = [
    { label: __("Apply", "super-ninety-four"), value: "apply" },
    { label: __("Lifestyle Concierge Workshop", "super-ninety-four"), value: "workshop" },
    { label: __("Expo Marketplace", "super-ninety-four"), value: "expo" },
    { label: __("Litter Pickup Service", "super-ninety-four"), value: "litter-pickup-service" },
    { label: __("Line Sitting & Courier", "super-ninety-four"), value: "professional-line-sitting-courier-service" },
    { label: __("Custom URL", "super-ninety-four"), value: "custom" },
  ];

  registerBlockType("snf/cta-button", {
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
            { title: __("CTA destination", "super-ninety-four"), initialOpen: true },
            el(TextControl, {
              label: __("Label", "super-ninety-four"),
              value: attrs.label || "",
              onChange: function (value) {
                props.setAttributes({ label: value });
              },
            }),
            el(SelectControl, {
              label: __("Target", "super-ninety-four"),
              value: attrs.target || "apply",
              options: targets,
              onChange: function (value) {
                props.setAttributes({ target: value });
              },
            }),
            attrs.target === "custom"
              ? el(TextControl, {
                  label: __("Custom URL", "super-ninety-four"),
                  value: attrs.customUrl || "",
                  onChange: function (value) {
                    props.setAttributes({ customUrl: value });
                  },
                })
              : null
          )
        ),
        el(
          "p",
          { className: "snf-block snf-block-cta" },
          el(
            "span",
            { className: "snf-block-cta__button" },
            attrs.label || __("Continue", "super-ninety-four")
          )
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
