/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.rsd_district = {
    attach: function (context, settings) {

    }
  };

})(jQuery, Drupal);


/** Enable Navbar dropdowns on hover - See CSS as well **/

(function ($) {
	$('.menu--main .dropdown > a').click(function () {
		if (window.innerWidth > 959) {
			location.href = this.href;
		}
	});
})(jQuery);

/** Enable Navbar dropdowns for Mobile - See CSS as well **/

(function ($) {
	$('.menu--main .dropdown div.menu-toggle').attr("data-toggle", "dropdown");
	$('.menu--main .dropdown > a').click(function () {
		if (window.innerWidth < 959) {
			location.href = this.href;
		}
	});
})(jQuery);