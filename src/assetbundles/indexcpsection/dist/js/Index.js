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

class PermissionGroup {
  constructor($group) {
    this.$group = $group;
    this.$toggle = this.$group.querySelector("[data-toggle]");
    this.$label = this.$group.querySelector("[data-toggle-label]");
    this.$checkAll = this.$group.querySelector("[data-check-all-label]");
    this.$checkboxes = this.$group.querySelectorAll("input");
    this.enabled = (this.$group.dataset.enabled == "true");

    this.handleToggle = this.handleToggle.bind(this);
    this.handleCheckAll = this.handleCheckAll.bind(this);

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
    this.$checkAll.addEventListener("click", this.handleCheckAll);
  }

  handleToggle(ev) {
    ev.preventDefault();

    if (this.enabled) {
      this.disable();
    } else {
      this.enable();
    }
  }

  handleCheckAll(ev) {
    ev.preventDefault();
    this.$checkboxes.forEach($checkbox => $checkbox.checked = true);
  }

  enable() {
    this.enabled = true;
    this.$checkboxes.forEach($checkbox => $checkbox.checked = false);
    this.enableCheckboxes();
  }

  disable() {
    this.enabled = false;
    this.$checkboxes.forEach($checkbox => $checkbox.checked = true);
    this.disableCheckboxes();
  }

  enableCheckboxes() {
    this.$checkboxes.forEach($checkbox => $checkbox.removeAttribute("disabled"));
    this.$toggle.classList.remove("insecure");
    this.$toggle.classList.add("secure");
    this.$label.innerHTML = `Allow user to access all entries`;
  }

  disableCheckboxes() {
    this.$checkboxes.forEach($checkbox => $checkbox.setAttribute("disabled", "disabled"));
    this.$toggle.classList.remove("secure");
    this.$toggle.classList.add("insecure");
    this.$label.innerHTML = "Assign a subset of entries";
  }
}

document.querySelectorAll("[data-isolate-group]").forEach($el => new PermissionGroup($el));
