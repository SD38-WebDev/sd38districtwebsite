/**
 * @file
 * Cherries by @toddmotto, @cferdinandi, @adamfschwartz, @daniellmb.
 *
 * @todo: Use Cash or Underscore when jQuery is dropped by supported plugins.
 */

/* global window, document, define, module */
(function (root, factory) {

  'use strict';

  // Inspired by https://github.com/addyosmani/memoize.js/blob/master/memoize.js
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([], factory);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but only CommonJS-like
    // environments that support module.exports, like Node.
    module.exports = factory();
  }
  else {
    // Browser globals (root is window).
    root.dBlazy = factory();
  }
})(this, function () {

  'use strict';

  /**
   * Object for public APIs where dBlazy stands for drupalBlazy.
   *
   * @namespace
   */
  var dBlazy = {};

  /**
   * Check if the given element matches the selector.
   *
   * @name dBlazy.matches
   *
   * @param {Element} elem
   *   The current element.
   * @param {String} selector
   *   Selector to match against (class, ID, data attribute, or tag).
   *
   * @return {Boolean}
   *   Returns true if found, else false.
   *
   * @see http://caniuse.com/#feat=matchesselector
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
   */
  dBlazy.matches = function (elem, selector) {
    // Element.matches() polyfill.
    var p = Element.prototype;
    if (!p.matches) {
      p.matches =
        p.matchesSelector ||
        p.mozMatchesSelector ||
        p.msMatchesSelector ||
        p.oMatchesSelector ||
        p.webkitMatchesSelector ||
        function (s) {
          var matches = (window.document || window.ownerDocument).querySelectorAll(s);
          var i = matches.length;
          while (--i >= 0 && matches.item(i) !== this) {
            // Empty block to satisfy coder and eslint.
          }
          return i > -1;
        };
    }

    // Check if matches.
    if (elem.matches(selector)) {
      return true;
    }

    return false;
  };

  /**
   * Get the closest matching element up the DOM tree.
   *
   * Inspired by Chris Ferdinandi, http://github.com/cferdinandi/smooth-scroll.
   *
   * @name dBlazy.closest
   *
   * @param {Element} elem
   *   Starting element.
   * @param {String} selector
   *   Selector to match against (class, ID, data attribute, or tag).
   *
   * @return {Boolean|Element}
   *   Returns null if not match found.
   *
   * @see http://caniuse.com/#feat=element-closest
   * @see http://caniuse.com/#feat=matchesselector
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
   */
  dBlazy.closest = function (elem, selector) {
    for (; elem && elem !== document; elem = elem.parentNode) {
      if (dBlazy.matches(elem, selector)) {
        return elem;
      }
    }

    return null;
  };

  /**
   * Returns a new object after merging two, or more objects.
   *
   * Inspired by @adamfschwartz, @zackbloom, http://youmightnotneedjquery.com.
   *
   * @name dBlazy.extend
   *
   * @param {Object} out
   *   The objects to merge together.
   *
   * @return {Object}
   *   Merged values of defaults and options.
   */
  dBlazy.extend = Object.assign || function (out) {
    out = out || {};

    for (var i = 1, len = arguments.length; i < len; i++) {
      if (!arguments[i]) {
        continue;
      }

      for (var key in arguments[i]) {
        if (arguments[i].hasOwnProperty(key)) {
          out[key] = arguments[i][key];
        }
      }
    }

    return out;
  };

  /**
   * A simple forEach() implementation for Arrays, Objects and NodeLists.
   *
   * @name dBlazy.forEach
   *
   * @author Todd Motto
   * @link https://github.com/toddmotto/foreach
   *
   * @param {Array|Object|NodeList} collection
   *   Collection of items to iterate.
   * @param {Function} callback
   *   Callback function for each iteration.
   * @param {Array|Object|NodeList} scope
   *   Object/NodeList/Array that forEach is iterating over (aka `this`).
   */
  dBlazy.forEach = function (collection, callback, scope) {
    var proto = Object.prototype;
    if (proto.toString.call(collection) === '[object Object]') {
      for (var prop in collection) {
        if (proto.hasOwnProperty.call(collection, prop)) {
          callback.call(scope, collection[prop], prop, collection);
        }
      }
    }
    else {
      for (var i = 0, len = collection.length; i < len; i++) {
        callback.call(scope, collection[i], i, collection);
      }
    }
  };

  /**
   * A simple hasClass wrapper.
   *
   * @name dBlazy.hasClass
   *
   * @param {Element} el
   *   The HTML element.
   * @param {String} name
   *   The class name.
   *
   * @return {bool}
   *   True if of of the method is supported.
   */
  dBlazy.hasClass = function (el, name) {
    if (el.classList) {
      return el.classList.contains(name);
    }
    else {
      return el.className.indexOf(name) !== -1;
    }
  };

  /**
   * A simple wrapper for event delegation like jQuery.on().
   *
   * Inspired by http://stackoverflow.com/questions/30880757/
   * javascript-equivalent-to-on.
   *
   * @name dBlazy.on
   *
   * @param {Element} elm
   *   The parent HTML element.
   * @param {String} eventName
   *   The event name to trigger.
   * @param {String} childEl
   *   Child selector to match against (class, ID, data attribute, or tag).
   * @param {Function} callback
   *   The callback function.
   */
  dBlazy.on = function (elm, eventName, childEl, callback) {
    elm.addEventListener(eventName, function (event) {
      var t = event.target;
      while (t && t !== this) {
        if (dBlazy.matches(t, childEl)) {
          callback.call(t, event);
        }
        t = t.parentNode;
      }
    });
  };

  /**
   * Executes a function once.
   *
   * @name dBlazy.once
   *
   * @author Daniel Lamb <dlamb.open.source@gmail.com>
   * @link https://github.com/daniellmb/once.js
   *
   * @param {Function} fn
   *   The executed function.
   *
   * @return {Object}
   *   The function result.
   */
  dBlazy.once = function (fn) {
    var result;
    var ran = false;
    return function proxy() {
      if (ran) {
        return result;
      }
      ran = true;
      result = fn.apply(this, arguments);
      // For garbage collection.
      fn = null;
      return result;
    };
  };

  /**
   * A simple wrapper for JSON.parse() for string within data-* attributes.
   *
   * @name dBlazy.parse
   *
   * @param {String} str
   *   The string to convert into JSON object.
   *
   * @return {Object|Boolean}
   *   The JSON object, or false in case invalid.
   */
  dBlazy.parse = function (str) {
    try {
      return JSON.parse(str);
    }
    catch (e) {
      return false;
    }
  };

  /**
   * A simple wrapper to delay callback function, tasken out of blazy library.
   *
   * Alternative to core Drupal.debounce for D7 compatibility, and easy port.
   *
   * @name dBlazy.throttle
   *
   * @param {Function} fn
   *   The callback function.
   * @param {Int} minDelay
   *   The execution delay in milliseconds.
   * @param {Object} scope
   *   The the scope of the function to apply to, normally this.
   *
   * @return {Function}
   *   The function executed at the specified minDelay.
   */
  dBlazy.throttle = function (fn, minDelay, scope) {
    var lastCall = 0;
    return function () {
      var now = +new Date();
      if (now - lastCall < minDelay) {
        return;
      }
      lastCall = now;
      fn.apply(scope, arguments);
    };
  };

  /**
   * A simple wrapper to delay callback function on window resize.
   *
   * @name dBlazy.resize
   *
   * @link https://github.com/louisremi/jquery-smartresize
   *
   * @param {Function} c
   *   The callback function.
   * @param {Int} t
   *   The timeout.
   *
   * @return {Function}
   *   The callback function.
   */
  dBlazy.resize = function (c, t) {
    window.onresize = function () {
      window.clearTimeout(t);
      t = window.setTimeout(c, 200);
    };
    return c;
  };

  /**
   * A simple wrapper for triggering event like jQuery.trigger().
   *
   * @name dBlazy.trigger
   *
   * @param {Element} elm
   *   The HTML element.
   * @param {String} eventName
   *   The event name to trigger.
   * @param {Object} custom
   *   The optional object passed into a custom event.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
   * @todo: See if any consistent way for both custom and native events.
   */
  dBlazy.trigger = function (elm, eventName, custom) {
    var event;
    var data = {
      detail: custom || {},
      bubbles: true,
      cancelable: true
    };

    // Native.
    // IE >= 9 compat, else SCRIPT445: Object doesn't support this action.
    // https://msdn.microsoft.com/library/ff975299(v=vs.85).aspx
    if (typeof window.CustomEvent === 'function') {
      event = new CustomEvent(eventName, data);
    }
    else {
      event = document.createEvent('CustomEvent');
      event.initCustomEvent(eventName, true, true, data);
    }

    elm.dispatchEvent(event);
  };

  return dBlazy;

});
;
/**
 * @file
 * Provides Intersection Observer API loader.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 * @see https://developers.google.com/web/updates/2016/04/intersectionobserver
 */

/* global window, document, define, module */
(function (root, factory) {

  'use strict';

  // Inspired by https://github.com/addyosmani/memoize.js/blob/master/memoize.js
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([window.dBlazy], factory);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but only CommonJS-like
    // environments that support module.exports, like Node.
    module.exports = factory(window.dBlazy);
  }
  else {
    // Browser globals (root is window).
    root.Bio = factory(window.dBlazy);
  }
})(this, function (dBlazy) {

  'use strict';

  /**
   * Private variables.
   */
  var _win = window;
  var _doc = document;
  var _db = dBlazy;
  var _bioTick = 0;
  var _revTick = 0;
  var _disconnected = false;
  var _observed = false;

  /**
   * Constructor for Bio, Blazy IntersectionObserver.
   *
   * @param {object} options
   *   The Bio options.
   *
   * @namespace
   */
  function Bio(options) {
    var me = this;

    me.options = _db.extend({}, me.defaults, options || {});

    // Initializes Blazy IntersectionObserver.
    _disconnected = false;
    _observed = false;
    init(me);
  }

  // Cache our prototype.
  var _proto = Bio.prototype;
  _proto.constructor = Bio;

  // Prepare prototype to interchange with Blazy as fallback.
  _proto.count = 0;
  _proto.counted = -1;
  _proto.erCounted = 0;
  _proto._er = -1;
  _proto._ok = 1;
  _proto.defaults = {
    root: null,
    disconnect: false,
    error: false,
    success: false,
    intersecting: false,
    observing: false,
    successClass: 'b-loaded',
    selector: '.b-lazy',
    errorClass: 'b-error',
    bgClass: 'b-bg',
    rootMargin: '0px',
    threshold: [0]
  };

  // BC for interchanging with bLazy.
  _proto.load = function (elms) {
    var me = this;

    // Manually load elements regardless of being disconnected, or not, relevant
    // for Slick slidesToShow > 1 which rebuilds clones of unloaded elements.
    if (me.isValid(elms)) {
      me.intersecting(elms);
    }
    else {
      _db.forEach(elms, function (el) {
        if (me.isValid(el)) {
          me.intersecting(el);
        }
      });
    }

    if (!_disconnected) {
      me.disconnect();
    }
  };

  _proto.isLoaded = function (el) {
    return el.classList.contains(this.options.successClass);
  };

  _proto.isValid = function (el) {
    return typeof el === 'object' && typeof el.length === 'undefined' && !this.isLoaded(el);
  };

  _proto.prepare = function () {
    // Do nothing, let extenders do their jobs.
  };

  _proto.revalidate = function (force) {
    var me = this;

    // Prevents from too many revalidations unless needed.
    if ((me.count !== me.counted || force === true) && (_revTick < me.counted)) {
      _disconnected = false;
      me.elms = (me.options.root || _doc).querySelectorAll(me.options.selector);
      me.observe();

      _revTick++;
    }
  };

  _proto.intersecting = function (el) {
    var me = this;

    // If not extending/ overriding, at least provide the option.
    if (typeof me.options.intersecting === 'function') {
      me.options.intersecting(el, me.options);
    }

    // Be sure to throttle, or debounce your method when calling this.
    _db.trigger(el, 'bio.intersecting', {options: me.options});

    me.lazyLoad(el);
    me.counted++;

    if (!_disconnected) {
      me.observer.unobserve(el);
    }
  };

  _proto.lazyLoad = function (el) {
    // Do nothing, let extenders do their own lazy, can be images, AJAX, etc.
  };

  _proto.success = function (el, status, parent) {
    var me = this;

    if (typeof me.options.success === 'function') {
      me.options.success(el, status, parent, me.options);
    }

    if (me.erCounted > 0) {
      me.erCounted--;
    }
  };

  _proto.error = function (el, status, parent) {
    var me = this;

    if (typeof me.options.error === 'function') {
      me.options.error(el, status, parent, me.options);
    }

    me.erCounted++;
  };

  _proto.loaded = function (el, status, parent) {
    var me = this;

    me[status === me._ok ? 'success' : 'error'](el, status, parent);
    el.classList.add(status === me._ok ? me.options.successClass : me.options.errorClass);
  };

  _proto.equal = function (el, str) {
    return el.nodeName.toLowerCase() === str;
  };

  _proto.observe = function () {
    var me = this;

    _bioTick = me.elms.length;
    _db.forEach(me.elms, function (entry) {
      // Only observes if not already loaded.
      if (!me.isLoaded(entry)) {
        me.observer.observe(entry);
      }
    });
  };

  _proto.observing = function (entries, observer) {
    var me = this;

    me.entries = entries;
    // Stop watching if already disconnected.
    if (_disconnected) {
      return;
    }

    // Load each on entering viewport.
    _db.forEach(entries, function (entry) {
      // Provides option such as to animate bg or elements regardless position.
      if (typeof me.options.observing === 'function') {
        me.options.observing(entry, observer, me.options);
      }

      // The element is being intersected.
      if (entry.isIntersecting || entry.intersectionRatio > 0) {
        if (!me.isLoaded(entry.target)) {
          me.intersecting(entry.target);
        }

        _bioTick--;
      }
    });

    // Disconnect when all is done.
    me.disconnect();
  };

  _proto.disconnect = function (force) {
    var me = this;

    // Do not disconnect if any error found.
    if (me.erCounted > 0 && !force) {
      return;
    }

    // Disconnect when all entries are loaded, if so configured.
    if (((_bioTick === 0 || me.count === me.counted) && me.options.disconnect) || force) {
      me.observer.disconnect();
      me.count = 0;
      me.elms = null;
      _disconnected = true;
    }
  };

  _proto.destroy = function (force) {
    var me = this;
    me.disconnect(force);
    me.observer = null;
  };

  _proto.disconnected = function () {
    return _disconnected;
  };

  _proto.reinit = function () {
    _disconnected = false;
    _observed = false;
    init(this);
  };

  function init(me) {
    var config = {
      rootMargin: me.options.rootMargin,
      threshold: me.options.threshold
    };

    me.options.selector = me.options.selector + ':not(.' + me.options.successClass + ')';
    me.elms = (me.options.root || _doc).querySelectorAll(me.options.selector);
    me.count = me.elms.length;
    me.windowWidth = _win.innerWidth || _doc.documentElement.clientWidth || _doc.body.clientWidth || _win.screen.width;

    me.prepare();

    // Initializes the IO.
    me.observer = new IntersectionObserver(me.observing.bind(me), config);

    // Observes once on the page load regardless multiple observer instances.
    // Possible as we nullify the root option to allow querying the DOM once.
    // Should you need to re-validate, or re-observe, just call ::observe().
    if (!_observed) {
      me.observe();
      _observed = true;
    }
  }

  return Bio;

});
;
/**
 * @file
 * Provides Intersection Observer API loader for media.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 * @see https://developers.google.com/web/updates/2016/04/intersectionobserver
 */

/* global window, document, define, module */
(function (root, factory) {

  'use strict';

  // Inspired by https://github.com/addyosmani/memoize.js/blob/master/memoize.js
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([window.dBlazy, window.Bio], factory);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but only CommonJS-like
    // environments that support module.exports, like Node.
    module.exports = factory(window.dBlazy, window.Bio);
  }
  else {
    // Browser globals (root is window).
    root.BioMedia = factory(window.dBlazy, window.Bio);
  }
})(this, function (dBlazy, Bio) {

  'use strict';

  /**
   * Private variables.
   */
  var _db = dBlazy;
  var _bio = Bio;
  var _src = 'src';
  var _srcSet = 'srcset';
  var _bgSrc = 'data-src';
  var _dataSrc = 'data-src';
  var _dataSrcset = 'data-srcset';
  var _bgSources = [_src];
  var _imgSources = [_srcSet, _src];

  /**
   * Constructor for BioMedia, Blazy IntersectionObserver for media.
   *
   * @param {object} options
   *   The BioMedia options.
   *
   * @return {object}
   *   The BioMedia instance.
   *
   * @namespace
   */
  function BioMedia(options) {
    return _bio.apply(this, arguments);
  }

  // Inherits Bio prototype.
  var _proto = BioMedia.prototype = Object.create(Bio.prototype);
  _proto.constructor = BioMedia;

  _proto.removeAttrs = function (el, attrs) {
    _db.forEach(attrs, function (attr) {
      el.removeAttribute('data-' + attr);
    });
  };

  _proto.setAttrs = function (el, attrs) {
    var me = this;

    _db.forEach(attrs, function (src) {
      me.setAttr(el, src);
    });
  };

  _proto.setAttr = function (el, attr, remove) {
    if (el.hasAttribute('data-' + attr)) {
      var dataAttr = el.getAttribute('data-' + attr);
      if (attr === _src) {
        el.src = dataAttr;
      }
      else {
        el.setAttribute(attr, dataAttr);
      }

      if (remove) {
        el.removeAttribute('data-' + attr);
      }
    }
  };

  _proto.prepare = (function (_bio) {
    return function () {
      var me = this;

      // DIV elements with multi-serving CSS background images.
      if (me.options.breakpoints) {
        var _bgSrcs = [];

        _db.forEach(me.options.breakpoints, function (object) {
          _bgSources.push(object.src.replace('data-', ''));

          // We have several values here, the last wins, but not good.
          // The original bLazy uses max-width, stick to it. The custom aspect
          // ratio works were also already based on this decision.
          if (object.width >= me.windowWidth) {
            _bgSrc = object.src;
            _bgSrcs.push(_bgSrc);
            return false;
          }
        });

        // This part is the betterment to the original bLazy.
        // Fetches the nearest to window width, not the farthest/ largest.
        // Not always available when the window is larger than the last item.
        // In such cases, this is easily fixed via configuration UI.
        if (_bgSrcs.length > 0) {
          _bgSrc = _bgSrcs[0];
        }
      }

      return _bio.call(this);
    };
  })(_proto.prepare);

  _proto.lazyLoad = (function (_bio) {
    return function (el) {
      // Image may take time to load after being hit, and it may be intersected
      // several times till marked loaded. Ensures it is hit once regardless
      // of being loaded, or not. No real issue with normal images on the page,
      // until having VIS alike which may spit out new images on AJAX request.
      if (el.hasAttribute('data-bio-hit')) {
        return;
      }

      var me = this;
      var parent = el.parentNode;
      var isImage = me.equal(el, 'img');
      var isBg = typeof el.src === 'undefined' && el.classList.contains(me.options.bgClass);
      var isPicture = parent && me.equal(parent, 'picture');
      var isVideo = me.equal(el, 'video');

      // PICTURE elements.
      if (isPicture) {
        _db.forEach(parent.getElementsByTagName('source'), function (source) {
          me.setAttr(source, _srcSet, true);
        });
        // Tiny controller image inside picture element won't get preloaded.
        me.setAttr(el, _src, true);
        me.loaded(el, me._ok);
      }
      // VIDEO elements.
      else if (isVideo) {
        _db.forEach(el.getElementsByTagName('source'), function (source) {
          me.setAttr(source, _src, true);
        });
        el.load();
        me.loaded(el, me._ok);
      }
      else {
        // IMG or DIV/ block elements got preloaded for better UX with loading.
        if (isImage || isBg) {
          me.setImage(el, isBg);
        }
        // IFRAME elements, etc.
        else {
          if (el.getAttribute(_dataSrc) && el.hasAttribute(_src)) {
            me.setAttr(el, _src, true);
            me.loaded(el, me._ok);
          }
        }
      }

      // Marks it hit/ requested. Not necessarily loaded.
      el.setAttribute('data-bio-hit', 1);

      return _bio.apply(this, arguments);
    };
  })(_proto.lazyLoad);

  _proto.promise = function (el, isBg) {
    var me = this;

    return new Promise(function (resolve, reject) {
      var img = new Image();

      // Preload `img` to have correct event handlers.
      img.src = el.getAttribute(isBg ? _bgSrc : _dataSrc);
      if (el.hasAttribute(_dataSrcset)) {
        img.srcset = el.getAttribute(_dataSrcset);
      }

      // Applies attributes regardless, will re-observe if any error.
      var applyAttrs = function () {
        if (isBg) {
          me.setBg(el);
        }
        else {
          me.setAttrs(el, _imgSources);
        }
      };

      // Handle onload event.
      img.onload = function () {
        applyAttrs();
        resolve(me._ok);
      };

      // Handle onerror event.
      img.onerror = function () {
        applyAttrs();
        reject(me._er);
      };
    });
  };

  _proto.setImage = function (el, isBg) {
    var me = this;

    return me.promise(el, isBg)
      .then(function (status) {
        me.loaded(el, status);
        me.removeAttrs(el, isBg ? _bgSources : _imgSources);
      })
      .catch(function (status) {
        me.loaded(el, status);

        el.removeAttribute('data-bio-hit');
      })
      .finally(function () {
        // Be sure to throttle, or debounce your method when calling this.
        _db.trigger(el, 'bio.finally', {options: me.options});
      });
  };

  _proto.setBg = function (el) {
    if (el.hasAttribute(_bgSrc)) {
      el.style.backgroundImage = 'url("' + el.getAttribute(_bgSrc) + '")';
      el.removeAttribute(_src);
    }
  };

  return BioMedia;

});
;
/**
 * @file
 * Provides Intersection Observer API or bLazy loader.
 */

(function (Drupal, drupalSettings, _db, window, document) {

  'use strict';

  /**
   * Blazy public methods.
   *
   * @namespace
   */
  Drupal.blazy = Drupal.blazy || {
    init: null,
    windowWidth: 0,
    blazySettings: drupalSettings.blazy || {},
    ioSettings: drupalSettings.blazyIo || {},
    options: {},
    globals: function () {
      var me = this;
      var commons = {
        success: me.clearing.bind(me),
        error: me.clearing.bind(me),
        selector: '.b-lazy',
        errorClass: 'b-error',
        successClass: 'b-loaded'
      };

      return _db.extend(me.blazySettings, me.ioSettings, commons);
    },

    clearing: function (el) {
      var me = this;
      var ie = el.classList.contains('b-responsive') && el.hasAttribute('data-pfsrc');

      // The .b-lazy element can be attached to IMG, or DIV as CSS background.
      el.className = el.className.replace(/(\S+)loading/, '');

      // The .is-loading can be .grid, .slide__content, .box__content, etc.
      var loaders = [
        _db.closest(el, '.is-loading'),
        _db.closest(el, '[class*="loading"]')
      ];

      // Also cleans up closest containers containing loading class.
      _db.forEach(loaders, function (wrapEl) {
        if (wrapEl !== null) {
          wrapEl.className = wrapEl.className.replace(/(\S+)loading/, '');
        }
      });

      // @see http://scottjehl.github.io/picturefill/
      if (window.picturefill && ie) {
        window.picturefill({
          reevaluate: true,
          elements: [el]
        });
      }

      // Provides event listeners for easy overrides without full overrides.
      _db.trigger(el, 'blazy.done', {options: me.options});
    },

    isIo: function () {
      return this.ioSettings && this.ioSettings.enabled && 'IntersectionObserver' in window;
    },

    isBlazy: function () {
      return !this.isIo() && 'Blazy' in window;
    },

    run: function (opts) {
      return this.isIo() ? new BioMedia(opts) : new Blazy(opts);
    }
  };

  /**
   * Initialize the default blazy instance.
   */
  var initBlazyDefault = function () {
    var me = Drupal.blazy;
    me.options = me.globals();
    me.init = me.run(me.options);
  };

  /**
   * Setup all blazy elements.
   */
  function doBlazyDefault(context) {
    var me = Drupal.blazy;
    initBlazyDefault();
    if (typeof me.init.options.selector !== 'undefined' && me.init.options.selector !== null) {
      var blazies = context.querySelectorAll(me.init.options.selector + ':not(.' + me.init.options.successClass + ')');
      if (blazies.length > 0) {
        _db.once(_db.forEach(blazies, doBlazy));
      }
    }
  }

  /**
   * Blazy utility functions.
   *
   * @param {HTMLElement} elm
   *   The Blazy HTML element.
   */
  function doBlazy(elm) {
    var me = Drupal.blazy;
    var dataAttr = elm.getAttribute('data-blazy');
    var hasDefaultOptions = !dataAttr || dataAttr === '1';
    var data = hasDefaultOptions ? {} : _db.parse(dataAttr);
    var opts = _db.extend({}, me.globals(), data);
    // Set docroot in case we are in an iframe.
    // @see Blazy.toArray
    var documentElement = _db.closest(elm, 'html');
    if (!document.documentElement.isSameNode(documentElement)) {
      opts.root = documentElement;
      hasDefaultOptions = false;
    }
    var ratios = elm.querySelectorAll('[data-dimensions]');
    var loopRatio = ratios.length > 0;
    var fallbackRatios = elm.querySelectorAll('[data-ratio]');
    var loopFallbackRatio = fallbackRatios.length > 0;

    /**
     * Updates the dynamic multi-breakpoint aspect ratio.
     *
     * This only applies to multi-serving images with aspect ratio fluid if
     * each element contains [data-dimensions] attribute.
     * Static single aspect ratio, e.g. `media--ratio--169`, will be ignored,
     * and will use CSS instead.
     *
     * @param {HTMLElement} el
     *   The .media--ratio--fluid|enforced HTML element.
     */
    function updateRatio(el) {
      var dimensions = !el.getAttribute('data-dimensions') ? false : _db.parse(el.getAttribute('data-dimensions'));

      if (!dimensions) {
        return;
      }

      var keys = Object.keys(dimensions);
      var xs = keys[0];
      var xl = keys[keys.length - 1];
      var mw = function (w) {
        return w >= me.windowWidth;
      };
      var pad = keys.filter(mw).map(function (v) {
        return dimensions[v];
      }).shift();

      if (pad === 'undefined') {
        pad = dimensions[me.windowWidth >= xl ? xl : xs];
      }

      if (pad !== 'undefined') {
        el.style.paddingBottom = pad + '%';
      }

      el.removeAttribute('data-ratio');
    }

    /**
     * Fix for Twig inline_template and Views rewrite striping out style.
     *
     * @param {HTMLElement} el
     *   The .media--ratio--fluid|enforced HTML element.
     */
    function updateFallbackRatio(el) {
      // Only rewrites if the style is indeed stripped out by Twig, and not set.
      if (!el.hasAttribute('style')) {
        el.style.paddingBottom = el.getAttribute('data-ratio') + '%';
      }
      el.removeAttribute('data-ratio');
    }

    // Initializes IntersectionObserver or Blazy instance.
    if (hasDefaultOptions) {
      initBlazyDefault();
    }
    else {
      me.options = opts;
      me.init = me.run(opts);
    }

    // Reacts on resizing per 200ms, and the magic () also does it on page load.
    _db.resize(function () {
      me.windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || window.screen.width;

      if (loopRatio) {
        _db.forEach(ratios, updateRatio, elm);
      }
      else if (loopFallbackRatio) {
        _db.forEach(fallbackRatios, updateFallbackRatio, elm);
      }

      // BC with bLazy, IO doesn't need to revalidate, Slick multiple-view does.
      if (me.isBlazy() || elm.classList.contains('blazy--revalidate')) {
        me.init.revalidate(true);
      }
    })();

    elm.classList.add('blazy--on');
  }

  /**
   * Attaches blazy behavior to HTML element identified by [data-blazy].
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazy = {
    attach: function (context) {
      // Drupal.attachBehaviors already does this so if this is necessary, someone
      // does an invalid call. But let's be robust here.
      context = context || document;
      var el = context.querySelector('[data-blazy]');

      // Runs basic Blazy if no [data-blazy] found, probably a single image or
      // a theme that does not use field attributes.
      // Cannot use .contains(), as IE11 doesn't support method 'contains'.
      if (el === null) {
        doBlazyDefault(context);
        return;
      }

      // Runs Blazy with multi-serving images, and aspect ratio supports.
      var blazies = context.querySelectorAll('.blazy:not(.blazy--on)');
      _db.once(_db.forEach(blazies, doBlazy));
    }
  };

}(Drupal, drupalSettings, dBlazy, this, this.document));
;
