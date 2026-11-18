(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var __ = wp.i18n.__;

  registerBlockType("snf/site-footer", {
    edit: function () {
      return el(
        "div",
        { className: "snf-block snf-block-site-footer snf-block--editor" },
        el("strong", null, __("SNF Site Footer", "super-ninety-four")),
        el("p", null, __("Renders the live brand footer and policy links on the front end.", "super-ninety-four"))
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
