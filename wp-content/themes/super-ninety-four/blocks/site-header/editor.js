(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var __ = wp.i18n.__;

  registerBlockType("snf/site-header", {
    edit: function () {
      return el(
        "div",
        { className: "snf-block snf-block-site-header snf-block--editor" },
        el("strong", null, __("SNF Site Header", "super-ninety-four")),
        el("p", null, __("Renders the live primary nav and account menu on the front end.", "super-ninety-four"))
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp);
