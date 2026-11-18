(function ($) {
  "use strict";

  var cfg = window.snfMarketplace || null;
  if (!cfg || !cfg.restUrl) {
    return;
  }

  var $form = $("#snf-search-events");
  if (!$form.length) {
    return;
  }

  var $root = $(".snf-expo, .snf-archive").first();
  var $chips = $("#snf-marketplace-chips");
  var $results = $("#snf-marketplace-results");
  var $listings = $(".snf-expo__listings");
  var $serpList = $(".snf-serp__list");
  var $serpStats = $(".snf-serp__stats");
  var $serpEmpty = $(".snf-serp__empty");
  var $input = $form.find('input[name="q"]');
  var activeCat = "";
  var timer = null;
  var lastXhr = null;
  var fallbackShop = cfg.shopUrl || $form.attr("action") || "/shop/";
  var surface = cfg.surface || ($root.hasClass("snf-expo--directory") ? "expo" : "shop");
  var isDirectory = surface === "expo" && $root.hasClass("snf-expo--directory");

  function escapeHtml(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function currentQuery() {
    return $.trim($input.val() || "");
  }

  function setBusy(busy) {
    $root.attr("aria-busy", busy ? "true" : "false");
    if ($results.length) {
      $results.toggleClass("is-loading", !!busy);
    }
  }

  function prepareDirectoryMotion() {
    if (!isDirectory || !$root.length) {
      return;
    }
    $listings.find(".snf-expo-dir__rail").each(function (index) {
      this.style.setProperty("--snf-rail-delay", Math.min(index * 90, 270) + "ms");
    });
    $listings.find(".snf-expo-dir__item").each(function (index) {
      this.style.setProperty("--snf-card-delay", Math.min(80 + index * 55, 360) + "ms");
    });
    window.requestAnimationFrame(function () {
      $root.addClass("is-ready");
    });
  }

  function filterDirectoryRails(cat) {
    if (!$listings.length) {
      return;
    }
    $listings.find(".snf-expo-dir__rail").each(function () {
      var track = $(this).attr("data-track") || $(this).attr("data-listing") || "";
      var show = !cat || track === cat || (cat && track === "register");
      if (show) {
        this.removeAttribute("hidden");
      } else {
        this.setAttribute("hidden", "hidden");
      }
    });
  }

  function renderChips(facets) {
    if (!$chips.length) {
      return;
    }
    var list = Array.isArray(facets) ? facets : cfg.facets || [];
    var html = "";
    html +=
      '<button type="button" class="snf-marketplace__chip' +
      (!activeCat ? " is-active" : "") +
      '" data-product-cat="" aria-pressed="' +
      (!activeCat ? "true" : "false") +
      '">' +
      escapeHtml(cfg.i18n.all) +
      "</button>";
    list.forEach(function (facet) {
      var slug = facet.slug || "";
      var on = activeCat === slug;
      html +=
        '<button type="button" class="snf-marketplace__chip' +
        (on ? " is-active" : "") +
        '" data-product-cat="' +
        escapeHtml(slug) +
        '" aria-pressed="' +
        (on ? "true" : "false") +
        '">' +
        escapeHtml(facet.label || slug) +
        "</button>";
    });
    $chips.html(html);
  }

  function resultItemHtml(row) {
    var title = escapeHtml(row.title);
    var url = escapeHtml(row.url);
    var snippet = escapeHtml(row.snippet);
    var meta = escapeHtml(row.meta);
    var crumb = escapeHtml(row.crumb);
    var action = escapeHtml(row.action || cfg.i18n.viewListing);
    var favicon = row.favicon
      ? '<img src="' +
        escapeHtml(row.favicon) +
        '" alt="" width="28" height="28">'
      : "";

    return (
      '<li class="snf-serp__item snf-marketplace__item" data-kind="' +
      escapeHtml(row.kind || "listing") +
      '">' +
      '<article class="snf-result">' +
      '<a class="snf-result__hit" href="' +
      url +
      '">' +
      '<div class="snf-result__source">' +
      '<span class="snf-result__favicon" aria-hidden="true">' +
      favicon +
      "</span>" +
      '<span class="snf-result__source-text">' +
      '<span class="snf-result__site">Super Ninety Four</span>' +
      '<span class="snf-result__crumb">' +
      crumb +
      "</span>" +
      "</span>" +
      "</div>" +
      '<h2 class="snf-result__title">' +
      title +
      "</h2>" +
      "</a>" +
      (snippet
        ? '<p class="snf-result__snippet">' + snippet + "</p>"
        : "") +
      (meta ? '<p class="snf-result__meta">' + meta + "</p>" : "") +
      '<p class="snf-result__action"><a href="' +
      url +
      '">' +
      action +
      "</a></p>" +
      "</article></li>"
    );
  }

  function paintResults(payload) {
    var results = (payload && payload.results) || [];
    var q = (payload && payload.q) || currentQuery();
    var searching = !!(payload && payload.searching);
    var count = typeof payload.count === "number" ? payload.count : results.length;
    var statsText = (cfg.i18n.resultsFor || "")
      .replace("%1$s", String(count))
      .replace("%2$s", q || "events & services");

    if ($results.length) {
      if (!searching) {
        $results.attr("hidden", true).empty();
        $listings.removeAttr("hidden");
        return;
      }
      $listings.attr("hidden", true);
      $results.removeAttr("hidden");
      if (!results.length) {
        $results.html(
          '<div class="snf-serp snf-marketplace__serp">' +
            '<p class="snf-serp__stats">' +
            escapeHtml(statsText) +
            "</p>" +
            '<div class="snf-serp__empty"><p>' +
            escapeHtml(cfg.i18n.empty) +
            '</p><p><a class="snf-archive__admission-link" href="' +
            escapeHtml(fallbackShop) +
            '">' +
            escapeHtml(cfg.i18n.browseShop) +
            "</a></p></div></div>"
        );
        return;
      }
      $results.html(
        '<div class="snf-serp snf-marketplace__serp">' +
          '<p class="snf-serp__stats">' +
          escapeHtml(statsText) +
          "</p>" +
          '<ol class="snf-serp__list">' +
          results.map(resultItemHtml).join("") +
          "</ol></div>"
      );
      return;
    }

    // Shop archive: refresh SERP list in place.
    if ($serpStats.length) {
      $serpStats.text(statsText);
    }
    if (!results.length) {
      if ($serpList.length) {
        $serpList.remove();
        $serpList = $();
      }
      if ($serpEmpty.length) {
        $serpEmpty.removeAttr("hidden");
      } else {
        $(".snf-serp").append(
          '<div class="snf-serp__empty"><p>' +
            escapeHtml(cfg.i18n.empty) +
            "</p></div>"
        );
        $serpEmpty = $(".snf-serp__empty");
      }
      return;
    }
    if ($serpEmpty.length) {
      $serpEmpty.attr("hidden", true);
    }
    var listHtml = results.map(resultItemHtml).join("");
    if ($serpList.length) {
      $serpList.html(listHtml);
    } else {
      $(".snf-serp").append('<ol class="snf-serp__list">' + listHtml + "</ol>");
      $serpList = $(".snf-serp__list");
    }
  }

  function syncUrl(q, cat) {
    if (!window.history || !window.history.replaceState) {
      return;
    }
    try {
      var url = new URL(window.location.href);
      if (q) {
        url.searchParams.set("q", q);
      } else {
        url.searchParams.delete("q");
      }
      if (cat) {
        url.searchParams.set("product_cat", cat);
      } else {
        url.searchParams.delete("product_cat");
      }
      window.history.replaceState({}, "", url.toString());
    } catch (e) {
      // Ignore URL API failures on older browsers.
    }
  }

  function fetchMarketplace(opts) {
    var options = opts || {};
    var q = typeof options.q === "string" ? options.q : currentQuery();
    var cat =
      typeof options.product_cat === "string" ? options.product_cat : activeCat;
    var allowFallback = !!options.fallback;

    if (lastXhr && lastXhr.abort) {
      lastXhr.abort();
    }

    if (isDirectory && !q) {
      syncUrl("", cat);
      filterDirectoryRails(cat);
      if ($results.length) {
        $results.attr("hidden", true).empty();
      }
      $listings.removeAttr("hidden");
      setBusy(false);
      return;
    }

    setBusy(true);
    syncUrl(q, cat);

    lastXhr = $.ajax({
      url: cfg.restUrl,
      method: "GET",
      dataType: "json",
      data: {
        q: q,
        product_cat: cat,
        surface: surface,
      },
      beforeSend: function (xhr) {
        if (cfg.nonce) {
          xhr.setRequestHeader("X-WP-Nonce", cfg.nonce);
        }
      },
    })
      .done(function (payload) {
        if (payload && Array.isArray(payload.facets)) {
          cfg.facets = payload.facets;
          renderChips(payload.facets);
        }
        paintResults(payload || {});
      })
      .fail(function (xhr, status) {
        if (status === "abort") {
          return;
        }
        if (allowFallback) {
          var dest = fallbackShop;
          var sep = dest.indexOf("?") >= 0 ? "&" : "?";
          if (q) {
            dest += sep + "q=" + encodeURIComponent(q);
            sep = "&";
          }
          if (cat) {
            dest += sep + "product_cat=" + encodeURIComponent(cat);
          }
          window.location.href = dest;
          return;
        }
        if ($results.length) {
          $results
            .removeAttr("hidden")
            .html(
              '<div class="snf-serp__empty"><p>' +
                escapeHtml(cfg.i18n.error) +
                '</p><p><a href="' +
                escapeHtml(fallbackShop) +
                '">' +
                escapeHtml(cfg.i18n.browseShop) +
                "</a></p></div>"
            );
          $listings.attr("hidden", true);
        }
      })
      .always(function () {
        setBusy(false);
      });
  }

  function scheduleFetch() {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(function () {
      fetchMarketplace({ fallback: false });
    }, cfg.debounce || 280);
  }

  // Initial chip state from URL.
  try {
    var params = new URLSearchParams(window.location.search);
    activeCat = params.get("product_cat") || "";
  } catch (e) {
    activeCat = "";
  }

  renderChips(cfg.facets);
  prepareDirectoryMotion();
  if (isDirectory) {
    filterDirectoryRails(activeCat);
  }

  $chips.on("click", ".snf-marketplace__chip", function (event) {
    event.preventDefault();
    activeCat = $(this).attr("data-product-cat") || "";
    renderChips(cfg.facets);
    fetchMarketplace({ fallback: false });
  });

  $form.on("submit", function (event) {
    event.preventDefault();
    if (timer) {
      clearTimeout(timer);
    }
    fetchMarketplace({ fallback: true });
  });

  $input.on("input", function () {
    scheduleFetch();
  });

  // If the page loaded with q or product_cat, hydrate async results.
  if (currentQuery() || activeCat) {
    fetchMarketplace({ fallback: false });
  }
})(jQuery);
