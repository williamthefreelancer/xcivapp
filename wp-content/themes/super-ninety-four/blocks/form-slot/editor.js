(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var InnerBlocks = wp.blockEditor.InnerBlocks;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var TextControl = wp.components.TextControl;
  var TextareaControl = wp.components.TextareaControl;
  var __ = wp.i18n.__;

  registerBlockType("snf/form-slot", {
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
            { title: __("Form slot", "super-ninety-four"), initialOpen: true },
            el(TextControl, {
              label: __("Heading", "super-ninety-four"),
              value: attrs.heading || "",
              onChange: function (value) {
                props.setAttributes({ heading: value });
              },
            }),
            el(TextareaControl, {
              label: __("Empty-state hint", "super-ninety-four"),
              value: attrs.hint || "",
              onChange: function (value) {
                props.setAttributes({ hint: value });
              },
            })
          )
        ),
        el(
          "section",
          { className: "snf-block snf-block-form-slot" },
          attrs.heading
            ? el("h2", { className: "snf-block-form-slot__heading" }, attrs.heading)
            : null,
          el(
            "div",
            { className: "snf-block-form-slot__inner" },
            el(InnerBlocks, {
              allowedBlocks: ["core/shortcode", "core/paragraph", "core/heading", "core/html"],
              template: [["core/paragraph", { placeholder: __("Add a form or shortcode here…", "super-ninety-four") }]],
              templateLock: false,
            })
          )
        )
      );
    },
    save: function () {
      return el(InnerBlocks.Content, null);
    },
  });
})(window.wp);
