(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var __ = wp.i18n.__;

  var sources = [
    { label: __("Service inquiries", "super-ninety-four"), value: "service-inquiries" },
    { label: __("Member events", "super-ninety-four"), value: "member-events" },
    { label: __("Non-member events (workshop)", "super-ninety-four"), value: "non-member-events" },
    { label: __("Reciprocal programs", "super-ninety-four"), value: "reciprocal" },
    { label: __("Expo scenic drives", "super-ninety-four"), value: "expo-scenic-drives" },
    { label: __("Expo gatherings", "super-ninety-four"), value: "expo-gatherings" },
    { label: __("Expo live & hybrid", "super-ninety-four"), value: "expo-live" },
  ];

  registerBlockType("snf/listing-grid", {
    edit: function (props) {
      var attrs = props.attributes;
      var sourceLabel =
        (sources.find(function (row) {
          return row.value === attrs.source;
        }) || sources[0]).label;

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __("Listing source", "super-ninety-four"), initialOpen: true },
            el(SelectControl, {
              label: __("Cards", "super-ninety-four"),
              value: attrs.source || "service-inquiries",
              options: sources,
              onChange: function (value) {
                props.setAttributes({ source: value });
              },
            })
          )
        ),
        el(
          "section",
          { className: "snf-block snf-block-listing-grid is-editor-preview" },
          el(
            "p",
            { className: "snf-block-listing-grid__empty" },
            __("Listing grid preview:", "super-ninety-four") + " " + sourceLabel
          )
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
