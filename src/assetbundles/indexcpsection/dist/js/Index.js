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

// $("[data-disabled='false'] input").attr("disabled", false);
// $("[data-disabled='true'] input").attr("checked", true);

class PermissionGroup {
  constructor($group) {
    this.$group = $group;
    this.$toggle = this.$group.querySelector("[data-toggle]");
    this.$label = this.$group.querySelector("[data-toggle-label]");
    this.$checkboxes = this.$group.querySelectorAll("input");
    this.enabled = (this.$group.dataset.enabled == "true");

    this.handleToggle = this.handleToggle.bind(this);

    this.init();
    this.events();
  }

  init() {
    if (!this.enabled) {
      this.disable();
    } else {
      this.enableCheckboxes();
    }
  }

  events() {
    this.$toggle.addEventListener("click", this.handleToggle);
  }

  handleToggle(ev) {
    ev.preventDefault();

    if (this.enabled) {
      this.disable();
    } else {
      this.enable();
    }
  }

  enable() {
    this.enabled = true;
    this.$checkboxes.forEach($checkbox => $checkbox.removeAttribute("checked"));
    this.enableCheckboxes();
  }

  disable() {
    this.enabled = false;
    this.$checkboxes.forEach($checkbox => $checkbox.setAttribute("checked", "checked"));
    this.disableCheckboxes();
  }

  enableCheckboxes() {
    this.$checkboxes.forEach($checkbox => $checkbox.removeAttribute("disabled"));
    this.$toggle.classList.remove("insecure");
    this.$toggle.classList.add("secure");
    this.$label.innerHTML = "Reset";
  }

  disableCheckboxes() {
    this.$checkboxes.forEach($checkbox => $checkbox.setAttribute("disabled", "disabled"));
    this.$toggle.classList.remove("secure");
    this.$toggle.classList.add("insecure");
    this.$label.innerHTML = "Assign Entries";
  }
}

document.querySelectorAll("[data-isolate-group]").forEach($el => new PermissionGroup($el));

// $(".js-manage").on("click", function(ev) {
//   ev.preventDefault();

//   if (this.classList.contains("modified")) {
//     $(this).find("span").text("Assign");
//     $(this).removeClass("modified").removeClass("insecure").addClass("secure");

//     $(`.${this.id} input`).attr("disabled", true);
//     $(`.${this.id} input`).attr("checked", true);
//   } else {
//     $(this).find("span").text("Enable All");
//     $(this).addClass("modified").addClass("insecure").removeClass("secure");

//     $(`.${this.id} input`).attr("disabled", false);
//     $(`.${this.id} input`).attr("checked", false);
//   }
// });
