import "./bootstrap";

import Alpine from "alpinejs";

// Import jQuery
import jQuery from "jquery";
window.$ = window.jQuery = jQuery;

// Import Persian Datepicker
import "persian-datepicker/dist/js/persian-datepicker.min.js";

window.Alpine = Alpine;

Alpine.start();
