(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var RichText = wp.blockEditor.RichText;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var TextareaControl = wp.components.TextareaControl;
  var __ = wp.i18n.__;

  registerBlockType("snf/page-hero", {
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
            { title: __("Page hero", "super-ninety-four"), initialOpen: true },
            el(TextareaControl, {
              label: __("Lede", "super-ninety-four"),
              value: attrs.lede || "",
              onChange: function (value) {
                props.setAttributes({ lede: value });
              },
            })
          )
        ),
        el(
          "section",
          { className: "snf-block snf-block-page-hero" },
          el(RichText, {
            tagName: "h1",
            className: "snf-block-page-hero__title",
            placeholder: __("Page title", "super-ninety-four"),
            value: attrs.title || "",
            onChange: function (value) {
              props.setAttributes({ title: value });
            },
          }),
          el(RichText, {
            tagName: "p",
            className: "snf-block-page-hero__lede",
            placeholder: __("Short supporting sentence", "super-ninety-four"),
            value: attrs.lede || "",
            onChange: function (value) {
              props.setAttributes({ lede: value });
            },
          })
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
