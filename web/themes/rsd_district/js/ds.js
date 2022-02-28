(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
		function startTime() {
      var date = new Date(),
            hour = date.getHours(),
            minute = checkTime(date.getMinutes()),
            ss = checkTime(date.getSeconds());
            hour = hour % 12;
            hour = hour ? hour : 12; // the hour '0' should be '12'

            const d = new Date();
            document.getElementById("ds-date").innerHTML = d.toDateString();

        function checkTime(i) {
          if( i < 10 ) {
            i = "0" + i;
          }
          return i;
        }

      if ( hour > 12 ) {
        hour = hour - 12;
        if ( hour == 12 ) {
          hour = checkTime(hour);
        document.getElementById("ds-time").innerHTML = hour+":"+minute+" PM";
        }
        else {
          hour = checkTime(hour);
          document.getElementById("ds-time").innerHTML = hour+":"+minute+" AM";
        }
      }
      else {
        document.getElementById("ds-time").innerHTML = hour+":"+minute+" PM";;
      }
      var time = setTimeout(startTime,1000);
      }
		startTime();
    }
  };
})(jQuery, Drupal);
