/**
 * Isolate plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    TrendyMinds
 * @copyright Copyright (c) 2019 TrendyMinds
 * @link      https://trendyminds.com
 * @package   Isolate
 * @since     1.0.0
 */

$("[data-disabled='false'] input").attr("disabled", false);

$(".js-manage").on("click", function(ev) {
  ev.preventDefault();

  if (this.classList.contains("modified")) {
    $(this).find("span").text("Assign");
    $(this).removeClass("modified").removeClass("insecure").addClass("secure");

    $(`.${this.id} input`).attr("disabled", true);
    $(`.${this.id} input`).attr("checked", true);
  } else {
    $(this).find("span").text("Enable All");
    $(this).addClass("modified").addClass("insecure").removeClass("secure");

    $(`.${this.id} input`).attr("disabled", false);
    $(`.${this.id} input`).attr("checked", false);
  }
});
