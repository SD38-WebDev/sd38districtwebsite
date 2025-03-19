/**
 * @file
 * JavaScript behaviours for sd38_content_sync module.
 */
(function (Drupal, $, once) {

  Drupal.behaviors.SelectUnselect = {
    attach: function (context) {
      // Selector.
      var districtSchoolAll = 'input[name="field_district_school[all]"]';
      $(districtSchoolAll).on('change', function () {
        $('#edit-field-district-school input').each(function (key, school) {
          let checked = $(districtSchoolAll).prop('checked');
          $(school).prop('checked', checked);
        });
      });
    },
  };
})(Drupal, jQuery, once);
