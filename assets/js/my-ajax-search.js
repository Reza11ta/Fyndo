(function (window, document, $) {
  "use strict";

  var debounce = function (func, wait) {
    var timeout;
    return function () {
      var context = this,
        args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        func.apply(context, args);
      }, wait);
    };
  };

  function initInstance($form) {
    var instance =
      $form.data("fyndo-instance") ||
      "fyndo_" + Math.random().toString(36).substr(2, 9);
    var perPage = parseInt(
      $form.data("results-per-page") ||
        (window.fyndo_ajax && window.fyndo_ajax.results_per_page) ||
        5,
      10
    );
    var $input = $form.find('input[type="search"]').first();
    var $results = $form.find(".fyndo-search-results").first();
    var $live = $form.find(".fyndo-search-live").first();
    var controller = null;
    var currentPage = 1;

    function closeResults() {
      $results.empty().hide();
    }

    function renderResults(data) {
      $results.empty();
      if (!data || !data.results || data.results.length === 0) {
        $live.text("");
        var q = $input.val();
        if (q && q.length >= 3) {
          var li = $("<li>")
            .attr("role", "listitem")
            .addClass("fyndo-result-item no-results");
          var span = $("<span>")
            .addClass("fyndo-result-none")
            .text(
              (window.fyndo_ajax && window.fyndo_ajax.i18n_no_results) ||
                "محصول مورد نظر پیدا نشد"
            );
          li.append(span);
          $results.append(li);
          $results.show();
        } else {
          closeResults();
        }
        return;
      }
      $live.text("");
      data.results.forEach(function (item) {
        var li = $("<li>")
          .attr("role", "listitem")
          .addClass("fyndo-result-item");
        var a = $("<a>")
          .attr("href", item.permalink)
          .attr("tabindex", 0)
          .addClass("fyndo-result-link");
        if (item.thumbnail) {
          var thumb = $("<img>")
            .attr("src", item.thumbnail)
            .attr("alt", item.title)
            .addClass("fyndo-thumb");
          a.append(thumb);
        }
        var title = $("<span>").text(item.title).addClass("fyndo-result-title");
        a.append(title);
        if (item.sale_price || item.regular_price) {
          var priceWrap = $("<div>").addClass("fyndo-result-prices");
          if (item.sale_price) {
            priceWrap.append(
              $("<span>").addClass("fyndo-price-sale").text(item.sale_price)
            );
          }
          if (item.regular_price) {
            priceWrap.append(
              $("<span>")
                .addClass("fyndo-price-regular")
                .text(item.regular_price)
            );
          }
          a.append(priceWrap);
        }
        li.append(a);
        $results.append(li);
      });
      $results.show();
    }

    function doSearch(page) {
      page = page || 1;
      var q = $input.val();
      if (!q || q.length < 3) {
        closeResults();
        $live.text("");
        return;
      }

      var data = {
        action: "fyndo_search",
        nonce: (window.fyndo_ajax && window.fyndo_ajax.nonce) || "",
        q: q,
        page: page,
        per_page: perPage,
      };

      if (controller && controller.readyState && controller.readyState !== 4) {
        controller.abort();
      }

      controller = $.ajax({
        url:
          (window.fyndo_ajax && window.fyndo_ajax.ajax_url) ||
          "/wp-admin/admin-ajax.php",
        type: "POST",
        data: data,
        dataType: "json",
      })
        .done(function (resp) {
          if (resp && resp.success) {
            renderResults(resp.data);
          } else {
            closeResults();
            $live.text(
              resp && resp.data && resp.data.message
                ? resp.data.message
                : (window.fyndo_ajax && window.fyndo_ajax.i18n_error) || "Error"
            );
          }
        })
        .fail(function () {
          closeResults();
          $live.text(
            (window.fyndo_ajax && window.fyndo_ajax.i18n_error) || "Error"
          );
        });
    }

    $input.attr("autocomplete", "off");
    $results.hide();
    $input.on(
      "input",
      debounce(function () {
        currentPage = 1;
        doSearch(1);
      }, 250)
    );

    $input.on("keydown", function (e) {
      var $items = $results.find(".fyndo-result-link");
      if (!$items.length) return;
      var idx = $items.index($items.filter(":focus"));
      if (e.key === "ArrowDown") {
        e.preventDefault();
        var next = idx + 1 < $items.length ? idx + 1 : 0;
        $items.eq(next).focus();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        var prev = idx - 1 >= 0 ? idx - 1 : $items.length - 1;
        $items.eq(prev).focus();
      }
    });

    $(document).on("click.fyndo." + instance, function (e) {
      if (!$(e.target).closest($form).length) {
        closeResults();
      }
    });

    $form.on("remove", function () {
      $(document).off("click.fyndo." + instance);
    });
  }

  $(document).ready(function () {
    $(".fyndo-search-form.mas-search-form").each(function () {
      initInstance($(this));
    });
  });
})(window, document, jQuery);
