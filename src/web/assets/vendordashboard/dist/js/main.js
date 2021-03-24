var Market = (function () {
  'use strict';

  (function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
    typeof define === 'function' && define.amd ? define(factory) :
    (global = global || self, global.Alpine = factory());
  }(undefined, (function () {
    function _defineProperty(obj, key, value) {
      if (key in obj) {
        Object.defineProperty(obj, key, {
          value: value,
          enumerable: true,
          configurable: true,
          writable: true
        });
      } else {
        obj[key] = value;
      }

      return obj;
    }

    function ownKeys(object, enumerableOnly) {
      var keys = Object.keys(object);

      if (Object.getOwnPropertySymbols) {
        var symbols = Object.getOwnPropertySymbols(object);
        if (enumerableOnly) symbols = symbols.filter(function (sym) {
          return Object.getOwnPropertyDescriptor(object, sym).enumerable;
        });
        keys.push.apply(keys, symbols);
      }

      return keys;
    }

    function _objectSpread2(target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i] != null ? arguments[i] : {};

        if (i % 2) {
          ownKeys(Object(source), true).forEach(function (key) {
            _defineProperty(target, key, source[key]);
          });
        } else if (Object.getOwnPropertyDescriptors) {
          Object.defineProperties(target, Object.getOwnPropertyDescriptors(source));
        } else {
          ownKeys(Object(source)).forEach(function (key) {
            Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
          });
        }
      }

      return target;
    }

    // Thanks @stimulus:
    // https://github.com/stimulusjs/stimulus/blob/master/packages/%40stimulus/core/src/application.ts
    function domReady() {
      return new Promise(resolve => {
        if (document.readyState == "loading") {
          document.addEventListener("DOMContentLoaded", resolve);
        } else {
          resolve();
        }
      });
    }
    function arrayUnique(array) {
      return Array.from(new Set(array));
    }
    function isTesting() {
      return navigator.userAgent.includes("Node.js") || navigator.userAgent.includes("jsdom");
    }
    function checkedAttrLooseCompare(valueA, valueB) {
      return valueA == valueB;
    }
    function warnIfMalformedTemplate(el, directive) {
      if (el.tagName.toLowerCase() !== 'template') {
        console.warn(`Alpine: [${directive}] directive should only be added to <template> tags. See https://github.com/alpinejs/alpine#${directive}`);
      } else if (el.content.childElementCount !== 1) {
        console.warn(`Alpine: <template> tag with [${directive}] encountered with an unexpected number of root elements. Make sure <template> has a single root element. `);
      }
    }
    function kebabCase(subject) {
      return subject.replace(/([a-z])([A-Z])/g, '$1-$2').replace(/[_\s]/, '-').toLowerCase();
    }
    function camelCase(subject) {
      return subject.toLowerCase().replace(/-(\w)/g, (match, char) => char.toUpperCase());
    }
    function walk(el, callback) {
      if (callback(el) === false) return;
      let node = el.firstElementChild;

      while (node) {
        walk(node, callback);
        node = node.nextElementSibling;
      }
    }
    function debounce(func, wait) {
      var timeout;
      return function () {
        var context = this,
            args = arguments;

        var later = function later() {
          timeout = null;
          func.apply(context, args);
        };

        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }

    const handleError = (el, expression, error) => {
      console.warn(`Alpine Error: "${error}"\n\nExpression: "${expression}"\nElement:`, el);

      if (!isTesting()) {
        Object.assign(error, {
          el,
          expression
        });
        throw error;
      }
    };

    function tryCatch(cb, {
      el,
      expression
    }) {
      try {
        const value = cb();
        return value instanceof Promise ? value.catch(e => handleError(el, expression, e)) : value;
      } catch (e) {
        handleError(el, expression, e);
      }
    }

    function saferEval(el, expression, dataContext, additionalHelperVariables = {}) {
      return tryCatch(() => {
        if (typeof expression === 'function') {
          return expression.call(dataContext);
        }

        return new Function(['$data', ...Object.keys(additionalHelperVariables)], `var __alpine_result; with($data) { __alpine_result = ${expression} }; return __alpine_result`)(dataContext, ...Object.values(additionalHelperVariables));
      }, {
        el,
        expression
      });
    }
    function saferEvalNoReturn(el, expression, dataContext, additionalHelperVariables = {}) {
      return tryCatch(() => {
        if (typeof expression === 'function') {
          return Promise.resolve(expression.call(dataContext, additionalHelperVariables['$event']));
        }

        let AsyncFunction = Function;
        /* MODERN-ONLY:START */

        AsyncFunction = Object.getPrototypeOf(async function () {}).constructor;
        /* MODERN-ONLY:END */
        // For the cases when users pass only a function reference to the caller: `x-on:click="foo"`
        // Where "foo" is a function. Also, we'll pass the function the event instance when we call it.

        if (Object.keys(dataContext).includes(expression)) {
          let methodReference = new Function(['dataContext', ...Object.keys(additionalHelperVariables)], `with(dataContext) { return ${expression} }`)(dataContext, ...Object.values(additionalHelperVariables));

          if (typeof methodReference === 'function') {
            return Promise.resolve(methodReference.call(dataContext, additionalHelperVariables['$event']));
          } else {
            return Promise.resolve();
          }
        }

        return Promise.resolve(new AsyncFunction(['dataContext', ...Object.keys(additionalHelperVariables)], `with(dataContext) { ${expression} }`)(dataContext, ...Object.values(additionalHelperVariables)));
      }, {
        el,
        expression
      });
    }
    const xAttrRE = /^x-(on|bind|data|text|html|model|if|for|show|cloak|transition|ref|spread)\b/;
    function isXAttr(attr) {
      const name = replaceAtAndColonWithStandardSyntax(attr.name);
      return xAttrRE.test(name);
    }
    function getXAttrs(el, component, type) {
      let directives = Array.from(el.attributes).filter(isXAttr).map(parseHtmlAttribute); // Get an object of directives from x-spread.

      let spreadDirective = directives.filter(directive => directive.type === 'spread')[0];

      if (spreadDirective) {
        let spreadObject = saferEval(el, spreadDirective.expression, component.$data); // Add x-spread directives to the pile of existing directives.

        directives = directives.concat(Object.entries(spreadObject).map(([name, value]) => parseHtmlAttribute({
          name,
          value
        })));
      }

      if (type) return directives.filter(i => i.type === type);
      return sortDirectives(directives);
    }

    function sortDirectives(directives) {
      let directiveOrder = ['bind', 'model', 'show', 'catch-all'];
      return directives.sort((a, b) => {
        let typeA = directiveOrder.indexOf(a.type) === -1 ? 'catch-all' : a.type;
        let typeB = directiveOrder.indexOf(b.type) === -1 ? 'catch-all' : b.type;
        return directiveOrder.indexOf(typeA) - directiveOrder.indexOf(typeB);
      });
    }

    function parseHtmlAttribute({
      name,
      value
    }) {
      const normalizedName = replaceAtAndColonWithStandardSyntax(name);
      const typeMatch = normalizedName.match(xAttrRE);
      const valueMatch = normalizedName.match(/:([a-zA-Z0-9\-:]+)/);
      const modifiers = normalizedName.match(/\.[^.\]]+(?=[^\]]*$)/g) || [];
      return {
        type: typeMatch ? typeMatch[1] : null,
        value: valueMatch ? valueMatch[1] : null,
        modifiers: modifiers.map(i => i.replace('.', '')),
        expression: value
      };
    }
    function isBooleanAttr(attrName) {
      // As per HTML spec table https://html.spec.whatwg.org/multipage/indices.html#attributes-3:boolean-attribute
      // Array roughly ordered by estimated usage
      const booleanAttributes = ['disabled', 'checked', 'required', 'readonly', 'hidden', 'open', 'selected', 'autofocus', 'itemscope', 'multiple', 'novalidate', 'allowfullscreen', 'allowpaymentrequest', 'formnovalidate', 'autoplay', 'controls', 'loop', 'muted', 'playsinline', 'default', 'ismap', 'reversed', 'async', 'defer', 'nomodule'];
      return booleanAttributes.includes(attrName);
    }
    function replaceAtAndColonWithStandardSyntax(name) {
      if (name.startsWith('@')) {
        return name.replace('@', 'x-on:');
      } else if (name.startsWith(':')) {
        return name.replace(':', 'x-bind:');
      }

      return name;
    }
    function convertClassStringToArray(classList, filterFn = Boolean) {
      return classList.split(' ').filter(filterFn);
    }
    const TRANSITION_TYPE_IN = 'in';
    const TRANSITION_TYPE_OUT = 'out';
    const TRANSITION_CANCELLED = 'cancelled';
    function transitionIn(el, show, reject, component, forceSkip = false) {
      // We don't want to transition on the initial page load.
      if (forceSkip) return show();

      if (el.__x_transition && el.__x_transition.type === TRANSITION_TYPE_IN) {
        // there is already a similar transition going on, this was probably triggered by
        // a change in a different property, let's just leave the previous one doing its job
        return;
      }

      const attrs = getXAttrs(el, component, 'transition');
      const showAttr = getXAttrs(el, component, 'show')[0]; // If this is triggered by a x-show.transition.

      if (showAttr && showAttr.modifiers.includes('transition')) {
        let modifiers = showAttr.modifiers; // If x-show.transition.out, we'll skip the "in" transition.

        if (modifiers.includes('out') && !modifiers.includes('in')) return show();
        const settingBothSidesOfTransition = modifiers.includes('in') && modifiers.includes('out'); // If x-show.transition.in...out... only use "in" related modifiers for this transition.

        modifiers = settingBothSidesOfTransition ? modifiers.filter((i, index) => index < modifiers.indexOf('out')) : modifiers;
        transitionHelperIn(el, modifiers, show, reject); // Otherwise, we can assume x-transition:enter.
      } else if (attrs.some(attr => ['enter', 'enter-start', 'enter-end'].includes(attr.value))) {
        transitionClassesIn(el, component, attrs, show, reject);
      } else {
        // If neither, just show that damn thing.
        show();
      }
    }
    function transitionOut(el, hide, reject, component, forceSkip = false) {
      // We don't want to transition on the initial page load.
      if (forceSkip) return hide();

      if (el.__x_transition && el.__x_transition.type === TRANSITION_TYPE_OUT) {
        // there is already a similar transition going on, this was probably triggered by
        // a change in a different property, let's just leave the previous one doing its job
        return;
      }

      const attrs = getXAttrs(el, component, 'transition');
      const showAttr = getXAttrs(el, component, 'show')[0];

      if (showAttr && showAttr.modifiers.includes('transition')) {
        let modifiers = showAttr.modifiers;
        if (modifiers.includes('in') && !modifiers.includes('out')) return hide();
        const settingBothSidesOfTransition = modifiers.includes('in') && modifiers.includes('out');
        modifiers = settingBothSidesOfTransition ? modifiers.filter((i, index) => index > modifiers.indexOf('out')) : modifiers;
        transitionHelperOut(el, modifiers, settingBothSidesOfTransition, hide, reject);
      } else if (attrs.some(attr => ['leave', 'leave-start', 'leave-end'].includes(attr.value))) {
        transitionClassesOut(el, component, attrs, hide, reject);
      } else {
        hide();
      }
    }
    function transitionHelperIn(el, modifiers, showCallback, reject) {
      // Default values inspired by: https://material.io/design/motion/speed.html#duration
      const styleValues = {
        duration: modifierValue(modifiers, 'duration', 150),
        origin: modifierValue(modifiers, 'origin', 'center'),
        first: {
          opacity: 0,
          scale: modifierValue(modifiers, 'scale', 95)
        },
        second: {
          opacity: 1,
          scale: 100
        }
      };
      transitionHelper(el, modifiers, showCallback, () => {}, reject, styleValues, TRANSITION_TYPE_IN);
    }
    function transitionHelperOut(el, modifiers, settingBothSidesOfTransition, hideCallback, reject) {
      // Make the "out" transition .5x slower than the "in". (Visually better)
      // HOWEVER, if they explicitly set a duration for the "out" transition,
      // use that.
      const duration = settingBothSidesOfTransition ? modifierValue(modifiers, 'duration', 150) : modifierValue(modifiers, 'duration', 150) / 2;
      const styleValues = {
        duration: duration,
        origin: modifierValue(modifiers, 'origin', 'center'),
        first: {
          opacity: 1,
          scale: 100
        },
        second: {
          opacity: 0,
          scale: modifierValue(modifiers, 'scale', 95)
        }
      };
      transitionHelper(el, modifiers, () => {}, hideCallback, reject, styleValues, TRANSITION_TYPE_OUT);
    }

    function modifierValue(modifiers, key, fallback) {
      // If the modifier isn't present, use the default.
      if (modifiers.indexOf(key) === -1) return fallback; // If it IS present, grab the value after it: x-show.transition.duration.500ms

      const rawValue = modifiers[modifiers.indexOf(key) + 1];
      if (!rawValue) return fallback;

      if (key === 'scale') {
        // Check if the very next value is NOT a number and return the fallback.
        // If x-show.transition.scale, we'll use the default scale value.
        // That is how a user opts out of the opacity transition.
        if (!isNumeric(rawValue)) return fallback;
      }

      if (key === 'duration') {
        // Support x-show.transition.duration.500ms && duration.500
        let match = rawValue.match(/([0-9]+)ms/);
        if (match) return match[1];
      }

      if (key === 'origin') {
        // Support chaining origin directions: x-show.transition.top.right
        if (['top', 'right', 'left', 'center', 'bottom'].includes(modifiers[modifiers.indexOf(key) + 2])) {
          return [rawValue, modifiers[modifiers.indexOf(key) + 2]].join(' ');
        }
      }

      return rawValue;
    }

    function transitionHelper(el, modifiers, hook1, hook2, reject, styleValues, type) {
      // clear the previous transition if exists to avoid caching the wrong styles
      if (el.__x_transition) {
        el.__x_transition.cancel && el.__x_transition.cancel();
      } // If the user set these style values, we'll put them back when we're done with them.


      const opacityCache = el.style.opacity;
      const transformCache = el.style.transform;
      const transformOriginCache = el.style.transformOrigin; // If no modifiers are present: x-show.transition, we'll default to both opacity and scale.

      const noModifiers = !modifiers.includes('opacity') && !modifiers.includes('scale');
      const transitionOpacity = noModifiers || modifiers.includes('opacity');
      const transitionScale = noModifiers || modifiers.includes('scale'); // These are the explicit stages of a transition (same stages for in and for out).
      // This way you can get a birds eye view of the hooks, and the differences
      // between them.

      const stages = {
        start() {
          if (transitionOpacity) el.style.opacity = styleValues.first.opacity;
          if (transitionScale) el.style.transform = `scale(${styleValues.first.scale / 100})`;
        },

        during() {
          if (transitionScale) el.style.transformOrigin = styleValues.origin;
          el.style.transitionProperty = [transitionOpacity ? `opacity` : ``, transitionScale ? `transform` : ``].join(' ').trim();
          el.style.transitionDuration = `${styleValues.duration / 1000}s`;
          el.style.transitionTimingFunction = `cubic-bezier(0.4, 0.0, 0.2, 1)`;
        },

        show() {
          hook1();
        },

        end() {
          if (transitionOpacity) el.style.opacity = styleValues.second.opacity;
          if (transitionScale) el.style.transform = `scale(${styleValues.second.scale / 100})`;
        },

        hide() {
          hook2();
        },

        cleanup() {
          if (transitionOpacity) el.style.opacity = opacityCache;
          if (transitionScale) el.style.transform = transformCache;
          if (transitionScale) el.style.transformOrigin = transformOriginCache;
          el.style.transitionProperty = null;
          el.style.transitionDuration = null;
          el.style.transitionTimingFunction = null;
        }

      };
      transition(el, stages, type, reject);
    }

    const ensureStringExpression = (expression, el, component) => {
      return typeof expression === 'function' ? component.evaluateReturnExpression(el, expression) : expression;
    };

    function transitionClassesIn(el, component, directives, showCallback, reject) {
      const enter = convertClassStringToArray(ensureStringExpression((directives.find(i => i.value === 'enter') || {
        expression: ''
      }).expression, el, component));
      const enterStart = convertClassStringToArray(ensureStringExpression((directives.find(i => i.value === 'enter-start') || {
        expression: ''
      }).expression, el, component));
      const enterEnd = convertClassStringToArray(ensureStringExpression((directives.find(i => i.value === 'enter-end') || {
        expression: ''
      }).expression, el, component));
      transitionClasses(el, enter, enterStart, enterEnd, showCallback, () => {}, TRANSITION_TYPE_IN, reject);
    }
    function transitionClassesOut(el, component, directives, hideCallback, reject) {
      const leave = convertClassStringToArray(ensureStringExpression((directives.find(i => i.value === 'leave') || {
        expression: ''
      }).expression, el, component));
      const leaveStart = convertClassStringToArray(ensureStringExpression((directives.find(i => i.value === 'leave-start') || {
        expression: ''
      }).expression, el, component));
      const leaveEnd = convertClassStringToArray(ensureStringExpression((directives.find(i => i.value === 'leave-end') || {
        expression: ''
      }).expression, el, component));
      transitionClasses(el, leave, leaveStart, leaveEnd, () => {}, hideCallback, TRANSITION_TYPE_OUT, reject);
    }
    function transitionClasses(el, classesDuring, classesStart, classesEnd, hook1, hook2, type, reject) {
      // clear the previous transition if exists to avoid caching the wrong classes
      if (el.__x_transition) {
        el.__x_transition.cancel && el.__x_transition.cancel();
      }

      const originalClasses = el.__x_original_classes || [];
      const stages = {
        start() {
          el.classList.add(...classesStart);
        },

        during() {
          el.classList.add(...classesDuring);
        },

        show() {
          hook1();
        },

        end() {
          // Don't remove classes that were in the original class attribute.
          el.classList.remove(...classesStart.filter(i => !originalClasses.includes(i)));
          el.classList.add(...classesEnd);
        },

        hide() {
          hook2();
        },

        cleanup() {
          el.classList.remove(...classesDuring.filter(i => !originalClasses.includes(i)));
          el.classList.remove(...classesEnd.filter(i => !originalClasses.includes(i)));
        }

      };
      transition(el, stages, type, reject);
    }
    function transition(el, stages, type, reject) {
      const finish = once(() => {
        stages.hide(); // Adding an "isConnected" check, in case the callback
        // removed the element from the DOM.

        if (el.isConnected) {
          stages.cleanup();
        }

        delete el.__x_transition;
      });
      el.__x_transition = {
        // Set transition type so we can avoid clearing transition if the direction is the same
        type: type,
        // create a callback for the last stages of the transition so we can call it
        // from different point and early terminate it. Once will ensure that function
        // is only called one time.
        cancel: once(() => {
          reject(TRANSITION_CANCELLED);
          finish();
        }),
        finish,
        // This store the next animation frame so we can cancel it
        nextFrame: null
      };
      stages.start();
      stages.during();
      el.__x_transition.nextFrame = requestAnimationFrame(() => {
        // Note: Safari's transitionDuration property will list out comma separated transition durations
        // for every single transition property. Let's grab the first one and call it a day.
        let duration = Number(getComputedStyle(el).transitionDuration.replace(/,.*/, '').replace('s', '')) * 1000;

        if (duration === 0) {
          duration = Number(getComputedStyle(el).animationDuration.replace('s', '')) * 1000;
        }

        stages.show();
        el.__x_transition.nextFrame = requestAnimationFrame(() => {
          stages.end();
          setTimeout(el.__x_transition.finish, duration);
        });
      });
    }
    function isNumeric(subject) {
      return !Array.isArray(subject) && !isNaN(subject);
    } // Thanks @vuejs
    // https://github.com/vuejs/vue/blob/4de4649d9637262a9b007720b59f80ac72a5620c/src/shared/util.js

    function once(callback) {
      let called = false;
      return function () {
        if (!called) {
          called = true;
          callback.apply(this, arguments);
        }
      };
    }

    function handleForDirective(component, templateEl, expression, initialUpdate, extraVars) {
      warnIfMalformedTemplate(templateEl, 'x-for');
      let iteratorNames = typeof expression === 'function' ? parseForExpression(component.evaluateReturnExpression(templateEl, expression)) : parseForExpression(expression);
      let items = evaluateItemsAndReturnEmptyIfXIfIsPresentAndFalseOnElement(component, templateEl, iteratorNames, extraVars); // As we walk the array, we'll also walk the DOM (updating/creating as we go).

      let currentEl = templateEl;
      items.forEach((item, index) => {
        let iterationScopeVariables = getIterationScopeVariables(iteratorNames, item, index, items, extraVars());
        let currentKey = generateKeyForIteration(component, templateEl, index, iterationScopeVariables);
        let nextEl = lookAheadForMatchingKeyedElementAndMoveItIfFound(currentEl.nextElementSibling, currentKey); // If we haven't found a matching key, insert the element at the current position.

        if (!nextEl) {
          nextEl = addElementInLoopAfterCurrentEl(templateEl, currentEl); // And transition it in if it's not the first page load.

          transitionIn(nextEl, () => {}, () => {}, component, initialUpdate);
          nextEl.__x_for = iterationScopeVariables;
          component.initializeElements(nextEl, () => nextEl.__x_for); // Otherwise update the element we found.
        } else {
          // Temporarily remove the key indicator to allow the normal "updateElements" to work.
          delete nextEl.__x_for_key;
          nextEl.__x_for = iterationScopeVariables;
          component.updateElements(nextEl, () => nextEl.__x_for);
        }

        currentEl = nextEl;
        currentEl.__x_for_key = currentKey;
      });
      removeAnyLeftOverElementsFromPreviousUpdate(currentEl, component);
    } // This was taken from VueJS 2.* core. Thanks Vue!

    function parseForExpression(expression) {
      let forIteratorRE = /,([^,\}\]]*)(?:,([^,\}\]]*))?$/;
      let stripParensRE = /^\(|\)$/g;
      let forAliasRE = /([\s\S]*?)\s+(?:in|of)\s+([\s\S]*)/;
      let inMatch = String(expression).match(forAliasRE);
      if (!inMatch) return;
      let res = {};
      res.items = inMatch[2].trim();
      let item = inMatch[1].trim().replace(stripParensRE, '');
      let iteratorMatch = item.match(forIteratorRE);

      if (iteratorMatch) {
        res.item = item.replace(forIteratorRE, '').trim();
        res.index = iteratorMatch[1].trim();

        if (iteratorMatch[2]) {
          res.collection = iteratorMatch[2].trim();
        }
      } else {
        res.item = item;
      }

      return res;
    }

    function getIterationScopeVariables(iteratorNames, item, index, items, extraVars) {
      // We must create a new object, so each iteration has a new scope
      let scopeVariables = extraVars ? _objectSpread2({}, extraVars) : {};
      scopeVariables[iteratorNames.item] = item;
      if (iteratorNames.index) scopeVariables[iteratorNames.index] = index;
      if (iteratorNames.collection) scopeVariables[iteratorNames.collection] = items;
      return scopeVariables;
    }

    function generateKeyForIteration(component, el, index, iterationScopeVariables) {
      let bindKeyAttribute = getXAttrs(el, component, 'bind').filter(attr => attr.value === 'key')[0]; // If the dev hasn't specified a key, just return the index of the iteration.

      if (!bindKeyAttribute) return index;
      return component.evaluateReturnExpression(el, bindKeyAttribute.expression, () => iterationScopeVariables);
    }

    function evaluateItemsAndReturnEmptyIfXIfIsPresentAndFalseOnElement(component, el, iteratorNames, extraVars) {
      let ifAttribute = getXAttrs(el, component, 'if')[0];

      if (ifAttribute && !component.evaluateReturnExpression(el, ifAttribute.expression)) {
        return [];
      }

      let items = component.evaluateReturnExpression(el, iteratorNames.items, extraVars); // This adds support for the `i in n` syntax.

      if (isNumeric(items) && items >= 0) {
        items = Array.from(Array(items).keys(), i => i + 1);
      }

      return items;
    }

    function addElementInLoopAfterCurrentEl(templateEl, currentEl) {
      let clone = document.importNode(templateEl.content, true);
      currentEl.parentElement.insertBefore(clone, currentEl.nextElementSibling);
      return currentEl.nextElementSibling;
    }

    function lookAheadForMatchingKeyedElementAndMoveItIfFound(nextEl, currentKey) {
      if (!nextEl) return; // If we are already past the x-for generated elements, we don't need to look ahead.

      if (nextEl.__x_for_key === undefined) return; // If the the key's DO match, no need to look ahead.

      if (nextEl.__x_for_key === currentKey) return nextEl; // If they don't, we'll look ahead for a match.
      // If we find it, we'll move it to the current position in the loop.

      let tmpNextEl = nextEl;

      while (tmpNextEl) {
        if (tmpNextEl.__x_for_key === currentKey) {
          return tmpNextEl.parentElement.insertBefore(tmpNextEl, nextEl);
        }

        tmpNextEl = tmpNextEl.nextElementSibling && tmpNextEl.nextElementSibling.__x_for_key !== undefined ? tmpNextEl.nextElementSibling : false;
      }
    }

    function removeAnyLeftOverElementsFromPreviousUpdate(currentEl, component) {
      var nextElementFromOldLoop = currentEl.nextElementSibling && currentEl.nextElementSibling.__x_for_key !== undefined ? currentEl.nextElementSibling : false;

      while (nextElementFromOldLoop) {
        let nextElementFromOldLoopImmutable = nextElementFromOldLoop;
        let nextSibling = nextElementFromOldLoop.nextElementSibling;
        transitionOut(nextElementFromOldLoop, () => {
          nextElementFromOldLoopImmutable.remove();
        }, () => {}, component);
        nextElementFromOldLoop = nextSibling && nextSibling.__x_for_key !== undefined ? nextSibling : false;
      }
    }

    function handleAttributeBindingDirective(component, el, attrName, expression, extraVars, attrType, modifiers) {
      var value = component.evaluateReturnExpression(el, expression, extraVars);

      if (attrName === 'value') {
        if (Alpine.ignoreFocusedForValueBinding && document.activeElement.isSameNode(el)) return; // If nested model key is undefined, set the default value to empty string.

        if (value === undefined && String(expression).match(/\./)) {
          value = '';
        }

        if (el.type === 'radio') {
          // Set radio value from x-bind:value, if no "value" attribute exists.
          // If there are any initial state values, radio will have a correct
          // "checked" value since x-bind:value is processed before x-model.
          if (el.attributes.value === undefined && attrType === 'bind') {
            el.value = value;
          } else if (attrType !== 'bind') {
            el.checked = checkedAttrLooseCompare(el.value, value);
          }
        } else if (el.type === 'checkbox') {
          // If we are explicitly binding a string to the :value, set the string,
          // If the value is a boolean, leave it alone, it will be set to "on"
          // automatically.
          if (typeof value !== 'boolean' && ![null, undefined].includes(value) && attrType === 'bind') {
            el.value = String(value);
          } else if (attrType !== 'bind') {
            if (Array.isArray(value)) {
              // I'm purposely not using Array.includes here because it's
              // strict, and because of Numeric/String mis-casting, I
              // want the "includes" to be "fuzzy".
              el.checked = value.some(val => checkedAttrLooseCompare(val, el.value));
            } else {
              el.checked = !!value;
            }
          }
        } else if (el.tagName === 'SELECT') {
          updateSelect(el, value);
        } else {
          if (el.value === value) return;
          el.value = value;
        }
      } else if (attrName === 'class') {
        if (Array.isArray(value)) {
          const originalClasses = el.__x_original_classes || [];
          el.setAttribute('class', arrayUnique(originalClasses.concat(value)).join(' '));
        } else if (typeof value === 'object') {
          // Sorting the keys / class names by their boolean value will ensure that
          // anything that evaluates to `false` and needs to remove classes is run first.
          const keysSortedByBooleanValue = Object.keys(value).sort((a, b) => value[a] - value[b]);
          keysSortedByBooleanValue.forEach(classNames => {
            if (value[classNames]) {
              convertClassStringToArray(classNames).forEach(className => el.classList.add(className));
            } else {
              convertClassStringToArray(classNames).forEach(className => el.classList.remove(className));
            }
          });
        } else {
          const originalClasses = el.__x_original_classes || [];
          const newClasses = value ? convertClassStringToArray(value) : [];
          el.setAttribute('class', arrayUnique(originalClasses.concat(newClasses)).join(' '));
        }
      } else {
        attrName = modifiers.includes('camel') ? camelCase(attrName) : attrName; // If an attribute's bound value is null, undefined or false, remove the attribute

        if ([null, undefined, false].includes(value)) {
          el.removeAttribute(attrName);
        } else {
          isBooleanAttr(attrName) ? setIfChanged(el, attrName, attrName) : setIfChanged(el, attrName, value);
        }
      }
    }

    function setIfChanged(el, attrName, value) {
      if (el.getAttribute(attrName) != value) {
        el.setAttribute(attrName, value);
      }
    }

    function updateSelect(el, value) {
      const arrayWrappedValue = [].concat(value).map(value => {
        return value + '';
      });
      Array.from(el.options).forEach(option => {
        option.selected = arrayWrappedValue.includes(option.value || option.text);
      });
    }

    function handleTextDirective(el, output, expression) {
      // If nested model key is undefined, set the default value to empty string.
      if (output === undefined && String(expression).match(/\./)) {
        output = '';
      }

      el.textContent = output;
    }

    function handleHtmlDirective(component, el, expression, extraVars) {
      el.innerHTML = component.evaluateReturnExpression(el, expression, extraVars);
    }

    function handleShowDirective(component, el, value, modifiers, initialUpdate = false) {
      const hide = () => {
        el.style.display = 'none';
        el.__x_is_shown = false;
      };

      const show = () => {
        if (el.style.length === 1 && el.style.display === 'none') {
          el.removeAttribute('style');
        } else {
          el.style.removeProperty('display');
        }

        el.__x_is_shown = true;
      };

      if (initialUpdate === true) {
        if (value) {
          show();
        } else {
          hide();
        }

        return;
      }

      const handle = (resolve, reject) => {
        if (value) {
          if (el.style.display === 'none' || el.__x_transition) {
            transitionIn(el, () => {
              show();
            }, reject, component);
          }

          resolve(() => {});
        } else {
          if (el.style.display !== 'none') {
            transitionOut(el, () => {
              resolve(() => {
                hide();
              });
            }, reject, component);
          } else {
            resolve(() => {});
          }
        }
      }; // The working of x-show is a bit complex because we need to
      // wait for any child transitions to finish before hiding
      // some element. Also, this has to be done recursively.
      // If x-show.immediate, foregoe the waiting.


      if (modifiers.includes('immediate')) {
        handle(finish => finish(), () => {});
        return;
      } // x-show is encountered during a DOM tree walk. If an element
      // we encounter is NOT a child of another x-show element we
      // can execute the previous x-show stack (if one exists).


      if (component.showDirectiveLastElement && !component.showDirectiveLastElement.contains(el)) {
        component.executeAndClearRemainingShowDirectiveStack();
      }

      component.showDirectiveStack.push(handle);
      component.showDirectiveLastElement = el;
    }

    function handleIfDirective(component, el, expressionResult, initialUpdate, extraVars) {
      warnIfMalformedTemplate(el, 'x-if');
      const elementHasAlreadyBeenAdded = el.nextElementSibling && el.nextElementSibling.__x_inserted_me === true;

      if (expressionResult && (!elementHasAlreadyBeenAdded || el.__x_transition)) {
        const clone = document.importNode(el.content, true);
        el.parentElement.insertBefore(clone, el.nextElementSibling);
        transitionIn(el.nextElementSibling, () => {}, () => {}, component, initialUpdate);
        component.initializeElements(el.nextElementSibling, extraVars);
        el.nextElementSibling.__x_inserted_me = true;
      } else if (!expressionResult && elementHasAlreadyBeenAdded) {
        transitionOut(el.nextElementSibling, () => {
          el.nextElementSibling.remove();
        }, () => {}, component, initialUpdate);
      }
    }

    function registerListener(component, el, event, modifiers, expression, extraVars = {}) {
      const options = {
        passive: modifiers.includes('passive')
      };

      if (modifiers.includes('camel')) {
        event = camelCase(event);
      }

      let handler, listenerTarget;

      if (modifiers.includes('away')) {
        listenerTarget = document;

        handler = e => {
          // Don't do anything if the click came from the element or within it.
          if (el.contains(e.target)) return; // Don't do anything if this element isn't currently visible.

          if (el.offsetWidth < 1 && el.offsetHeight < 1) return; // Now that we are sure the element is visible, AND the click
          // is from outside it, let's run the expression.

          runListenerHandler(component, expression, e, extraVars);

          if (modifiers.includes('once')) {
            document.removeEventListener(event, handler, options);
          }
        };
      } else {
        listenerTarget = modifiers.includes('window') ? window : modifiers.includes('document') ? document : el;

        handler = e => {
          // Remove this global event handler if the element that declared it
          // has been removed. It's now stale.
          if (listenerTarget === window || listenerTarget === document) {
            if (!document.body.contains(el)) {
              listenerTarget.removeEventListener(event, handler, options);
              return;
            }
          }

          if (isKeyEvent(event)) {
            if (isListeningForASpecificKeyThatHasntBeenPressed(e, modifiers)) {
              return;
            }
          }

          if (modifiers.includes('prevent')) e.preventDefault();
          if (modifiers.includes('stop')) e.stopPropagation(); // If the .self modifier isn't present, or if it is present and
          // the target element matches the element we are registering the
          // event on, run the handler

          if (!modifiers.includes('self') || e.target === el) {
            const returnValue = runListenerHandler(component, expression, e, extraVars);
            returnValue.then(value => {
              if (value === false) {
                e.preventDefault();
              } else {
                if (modifiers.includes('once')) {
                  listenerTarget.removeEventListener(event, handler, options);
                }
              }
            });
          }
        };
      }

      if (modifiers.includes('debounce')) {
        let nextModifier = modifiers[modifiers.indexOf('debounce') + 1] || 'invalid-wait';
        let wait = isNumeric(nextModifier.split('ms')[0]) ? Number(nextModifier.split('ms')[0]) : 250;
        handler = debounce(handler, wait);
      }

      listenerTarget.addEventListener(event, handler, options);
    }

    function runListenerHandler(component, expression, e, extraVars) {
      return component.evaluateCommandExpression(e.target, expression, () => {
        return _objectSpread2(_objectSpread2({}, extraVars()), {}, {
          '$event': e
        });
      });
    }

    function isKeyEvent(event) {
      return ['keydown', 'keyup'].includes(event);
    }

    function isListeningForASpecificKeyThatHasntBeenPressed(e, modifiers) {
      let keyModifiers = modifiers.filter(i => {
        return !['window', 'document', 'prevent', 'stop'].includes(i);
      });

      if (keyModifiers.includes('debounce')) {
        let debounceIndex = keyModifiers.indexOf('debounce');
        keyModifiers.splice(debounceIndex, isNumeric((keyModifiers[debounceIndex + 1] || 'invalid-wait').split('ms')[0]) ? 2 : 1);
      } // If no modifier is specified, we'll call it a press.


      if (keyModifiers.length === 0) return false; // If one is passed, AND it matches the key pressed, we'll call it a press.

      if (keyModifiers.length === 1 && keyModifiers[0] === keyToModifier(e.key)) return false; // The user is listening for key combinations.

      const systemKeyModifiers = ['ctrl', 'shift', 'alt', 'meta', 'cmd', 'super'];
      const selectedSystemKeyModifiers = systemKeyModifiers.filter(modifier => keyModifiers.includes(modifier));
      keyModifiers = keyModifiers.filter(i => !selectedSystemKeyModifiers.includes(i));

      if (selectedSystemKeyModifiers.length > 0) {
        const activelyPressedKeyModifiers = selectedSystemKeyModifiers.filter(modifier => {
          // Alias "cmd" and "super" to "meta"
          if (modifier === 'cmd' || modifier === 'super') modifier = 'meta';
          return e[`${modifier}Key`];
        }); // If all the modifiers selected are pressed, ...

        if (activelyPressedKeyModifiers.length === selectedSystemKeyModifiers.length) {
          // AND the remaining key is pressed as well. It's a press.
          if (keyModifiers[0] === keyToModifier(e.key)) return false;
        }
      } // We'll call it NOT a valid keypress.


      return true;
    }

    function keyToModifier(key) {
      switch (key) {
        case '/':
          return 'slash';

        case ' ':
        case 'Spacebar':
          return 'space';

        default:
          return key && kebabCase(key);
      }
    }

    function registerModelListener(component, el, modifiers, expression, extraVars) {
      // If the element we are binding to is a select, a radio, or checkbox
      // we'll listen for the change event instead of the "input" event.
      var event = el.tagName.toLowerCase() === 'select' || ['checkbox', 'radio'].includes(el.type) || modifiers.includes('lazy') ? 'change' : 'input';
      const listenerExpression = `${expression} = rightSideOfExpression($event, ${expression})`;
      registerListener(component, el, event, modifiers, listenerExpression, () => {
        return _objectSpread2(_objectSpread2({}, extraVars()), {}, {
          rightSideOfExpression: generateModelAssignmentFunction(el, modifiers, expression)
        });
      });
    }

    function generateModelAssignmentFunction(el, modifiers, expression) {
      if (el.type === 'radio') {
        // Radio buttons only work properly when they share a name attribute.
        // People might assume we take care of that for them, because
        // they already set a shared "x-model" attribute.
        if (!el.hasAttribute('name')) el.setAttribute('name', expression);
      }

      return (event, currentValue) => {
        // Check for event.detail due to an issue where IE11 handles other events as a CustomEvent.
        if (event instanceof CustomEvent && event.detail) {
          return event.detail;
        } else if (el.type === 'checkbox') {
          // If the data we are binding to is an array, toggle its value inside the array.
          if (Array.isArray(currentValue)) {
            const newValue = modifiers.includes('number') ? safeParseNumber(event.target.value) : event.target.value;
            return event.target.checked ? currentValue.concat([newValue]) : currentValue.filter(el => !checkedAttrLooseCompare(el, newValue));
          } else {
            return event.target.checked;
          }
        } else if (el.tagName.toLowerCase() === 'select' && el.multiple) {
          return modifiers.includes('number') ? Array.from(event.target.selectedOptions).map(option => {
            const rawValue = option.value || option.text;
            return safeParseNumber(rawValue);
          }) : Array.from(event.target.selectedOptions).map(option => {
            return option.value || option.text;
          });
        } else {
          const rawValue = event.target.value;
          return modifiers.includes('number') ? safeParseNumber(rawValue) : modifiers.includes('trim') ? rawValue.trim() : rawValue;
        }
      };
    }

    function safeParseNumber(rawValue) {
      const number = rawValue ? parseFloat(rawValue) : null;
      return isNumeric(number) ? number : rawValue;
    }

    /**
     * Copyright (C) 2017 salesforce.com, inc.
     */
    const { isArray } = Array;
    const { getPrototypeOf, create: ObjectCreate, defineProperty: ObjectDefineProperty, defineProperties: ObjectDefineProperties, isExtensible, getOwnPropertyDescriptor, getOwnPropertyNames, getOwnPropertySymbols, preventExtensions, hasOwnProperty, } = Object;
    const { push: ArrayPush, concat: ArrayConcat, map: ArrayMap, } = Array.prototype;
    function isUndefined(obj) {
        return obj === undefined;
    }
    function isFunction(obj) {
        return typeof obj === 'function';
    }
    function isObject(obj) {
        return typeof obj === 'object';
    }
    const proxyToValueMap = new WeakMap();
    function registerProxy(proxy, value) {
        proxyToValueMap.set(proxy, value);
    }
    const unwrap = (replicaOrAny) => proxyToValueMap.get(replicaOrAny) || replicaOrAny;

    function wrapValue(membrane, value) {
        return membrane.valueIsObservable(value) ? membrane.getProxy(value) : value;
    }
    /**
     * Unwrap property descriptors will set value on original descriptor
     * We only need to unwrap if value is specified
     * @param descriptor external descrpitor provided to define new property on original value
     */
    function unwrapDescriptor(descriptor) {
        if (hasOwnProperty.call(descriptor, 'value')) {
            descriptor.value = unwrap(descriptor.value);
        }
        return descriptor;
    }
    function lockShadowTarget(membrane, shadowTarget, originalTarget) {
        const targetKeys = ArrayConcat.call(getOwnPropertyNames(originalTarget), getOwnPropertySymbols(originalTarget));
        targetKeys.forEach((key) => {
            let descriptor = getOwnPropertyDescriptor(originalTarget, key);
            // We do not need to wrap the descriptor if configurable
            // Because we can deal with wrapping it when user goes through
            // Get own property descriptor. There is also a chance that this descriptor
            // could change sometime in the future, so we can defer wrapping
            // until we need to
            if (!descriptor.configurable) {
                descriptor = wrapDescriptor(membrane, descriptor, wrapValue);
            }
            ObjectDefineProperty(shadowTarget, key, descriptor);
        });
        preventExtensions(shadowTarget);
    }
    class ReactiveProxyHandler {
        constructor(membrane, value) {
            this.originalTarget = value;
            this.membrane = membrane;
        }
        get(shadowTarget, key) {
            const { originalTarget, membrane } = this;
            const value = originalTarget[key];
            const { valueObserved } = membrane;
            valueObserved(originalTarget, key);
            return membrane.getProxy(value);
        }
        set(shadowTarget, key, value) {
            const { originalTarget, membrane: { valueMutated } } = this;
            const oldValue = originalTarget[key];
            if (oldValue !== value) {
                originalTarget[key] = value;
                valueMutated(originalTarget, key);
            }
            else if (key === 'length' && isArray(originalTarget)) {
                // fix for issue #236: push will add the new index, and by the time length
                // is updated, the internal length is already equal to the new length value
                // therefore, the oldValue is equal to the value. This is the forking logic
                // to support this use case.
                valueMutated(originalTarget, key);
            }
            return true;
        }
        deleteProperty(shadowTarget, key) {
            const { originalTarget, membrane: { valueMutated } } = this;
            delete originalTarget[key];
            valueMutated(originalTarget, key);
            return true;
        }
        apply(shadowTarget, thisArg, argArray) {
            /* No op */
        }
        construct(target, argArray, newTarget) {
            /* No op */
        }
        has(shadowTarget, key) {
            const { originalTarget, membrane: { valueObserved } } = this;
            valueObserved(originalTarget, key);
            return key in originalTarget;
        }
        ownKeys(shadowTarget) {
            const { originalTarget } = this;
            return ArrayConcat.call(getOwnPropertyNames(originalTarget), getOwnPropertySymbols(originalTarget));
        }
        isExtensible(shadowTarget) {
            const shadowIsExtensible = isExtensible(shadowTarget);
            if (!shadowIsExtensible) {
                return shadowIsExtensible;
            }
            const { originalTarget, membrane } = this;
            const targetIsExtensible = isExtensible(originalTarget);
            if (!targetIsExtensible) {
                lockShadowTarget(membrane, shadowTarget, originalTarget);
            }
            return targetIsExtensible;
        }
        setPrototypeOf(shadowTarget, prototype) {
        }
        getPrototypeOf(shadowTarget) {
            const { originalTarget } = this;
            return getPrototypeOf(originalTarget);
        }
        getOwnPropertyDescriptor(shadowTarget, key) {
            const { originalTarget, membrane } = this;
            const { valueObserved } = this.membrane;
            // keys looked up via hasOwnProperty need to be reactive
            valueObserved(originalTarget, key);
            let desc = getOwnPropertyDescriptor(originalTarget, key);
            if (isUndefined(desc)) {
                return desc;
            }
            const shadowDescriptor = getOwnPropertyDescriptor(shadowTarget, key);
            if (!isUndefined(shadowDescriptor)) {
                return shadowDescriptor;
            }
            // Note: by accessing the descriptor, the key is marked as observed
            // but access to the value, setter or getter (if available) cannot observe
            // mutations, just like regular methods, in which case we just do nothing.
            desc = wrapDescriptor(membrane, desc, wrapValue);
            if (!desc.configurable) {
                // If descriptor from original target is not configurable,
                // We must copy the wrapped descriptor over to the shadow target.
                // Otherwise, proxy will throw an invariant error.
                // This is our last chance to lock the value.
                // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Proxy/handler/getOwnPropertyDescriptor#Invariants
                ObjectDefineProperty(shadowTarget, key, desc);
            }
            return desc;
        }
        preventExtensions(shadowTarget) {
            const { originalTarget, membrane } = this;
            lockShadowTarget(membrane, shadowTarget, originalTarget);
            preventExtensions(originalTarget);
            return true;
        }
        defineProperty(shadowTarget, key, descriptor) {
            const { originalTarget, membrane } = this;
            const { valueMutated } = membrane;
            const { configurable } = descriptor;
            // We have to check for value in descriptor
            // because Object.freeze(proxy) calls this method
            // with only { configurable: false, writeable: false }
            // Additionally, method will only be called with writeable:false
            // if the descriptor has a value, as opposed to getter/setter
            // So we can just check if writable is present and then see if
            // value is present. This eliminates getter and setter descriptors
            if (hasOwnProperty.call(descriptor, 'writable') && !hasOwnProperty.call(descriptor, 'value')) {
                const originalDescriptor = getOwnPropertyDescriptor(originalTarget, key);
                descriptor.value = originalDescriptor.value;
            }
            ObjectDefineProperty(originalTarget, key, unwrapDescriptor(descriptor));
            if (configurable === false) {
                ObjectDefineProperty(shadowTarget, key, wrapDescriptor(membrane, descriptor, wrapValue));
            }
            valueMutated(originalTarget, key);
            return true;
        }
    }

    function wrapReadOnlyValue(membrane, value) {
        return membrane.valueIsObservable(value) ? membrane.getReadOnlyProxy(value) : value;
    }
    class ReadOnlyHandler {
        constructor(membrane, value) {
            this.originalTarget = value;
            this.membrane = membrane;
        }
        get(shadowTarget, key) {
            const { membrane, originalTarget } = this;
            const value = originalTarget[key];
            const { valueObserved } = membrane;
            valueObserved(originalTarget, key);
            return membrane.getReadOnlyProxy(value);
        }
        set(shadowTarget, key, value) {
            return false;
        }
        deleteProperty(shadowTarget, key) {
            return false;
        }
        apply(shadowTarget, thisArg, argArray) {
            /* No op */
        }
        construct(target, argArray, newTarget) {
            /* No op */
        }
        has(shadowTarget, key) {
            const { originalTarget, membrane: { valueObserved } } = this;
            valueObserved(originalTarget, key);
            return key in originalTarget;
        }
        ownKeys(shadowTarget) {
            const { originalTarget } = this;
            return ArrayConcat.call(getOwnPropertyNames(originalTarget), getOwnPropertySymbols(originalTarget));
        }
        setPrototypeOf(shadowTarget, prototype) {
        }
        getOwnPropertyDescriptor(shadowTarget, key) {
            const { originalTarget, membrane } = this;
            const { valueObserved } = membrane;
            // keys looked up via hasOwnProperty need to be reactive
            valueObserved(originalTarget, key);
            let desc = getOwnPropertyDescriptor(originalTarget, key);
            if (isUndefined(desc)) {
                return desc;
            }
            const shadowDescriptor = getOwnPropertyDescriptor(shadowTarget, key);
            if (!isUndefined(shadowDescriptor)) {
                return shadowDescriptor;
            }
            // Note: by accessing the descriptor, the key is marked as observed
            // but access to the value or getter (if available) cannot be observed,
            // just like regular methods, in which case we just do nothing.
            desc = wrapDescriptor(membrane, desc, wrapReadOnlyValue);
            if (hasOwnProperty.call(desc, 'set')) {
                desc.set = undefined; // readOnly membrane does not allow setters
            }
            if (!desc.configurable) {
                // If descriptor from original target is not configurable,
                // We must copy the wrapped descriptor over to the shadow target.
                // Otherwise, proxy will throw an invariant error.
                // This is our last chance to lock the value.
                // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Proxy/handler/getOwnPropertyDescriptor#Invariants
                ObjectDefineProperty(shadowTarget, key, desc);
            }
            return desc;
        }
        preventExtensions(shadowTarget) {
            return false;
        }
        defineProperty(shadowTarget, key, descriptor) {
            return false;
        }
    }
    function createShadowTarget(value) {
        let shadowTarget = undefined;
        if (isArray(value)) {
            shadowTarget = [];
        }
        else if (isObject(value)) {
            shadowTarget = {};
        }
        return shadowTarget;
    }
    const ObjectDotPrototype = Object.prototype;
    function defaultValueIsObservable(value) {
        // intentionally checking for null
        if (value === null) {
            return false;
        }
        // treat all non-object types, including undefined, as non-observable values
        if (typeof value !== 'object') {
            return false;
        }
        if (isArray(value)) {
            return true;
        }
        const proto = getPrototypeOf(value);
        return (proto === ObjectDotPrototype || proto === null || getPrototypeOf(proto) === null);
    }
    const defaultValueObserved = (obj, key) => {
        /* do nothing */
    };
    const defaultValueMutated = (obj, key) => {
        /* do nothing */
    };
    const defaultValueDistortion = (value) => value;
    function wrapDescriptor(membrane, descriptor, getValue) {
        const { set, get } = descriptor;
        if (hasOwnProperty.call(descriptor, 'value')) {
            descriptor.value = getValue(membrane, descriptor.value);
        }
        else {
            if (!isUndefined(get)) {
                descriptor.get = function () {
                    // invoking the original getter with the original target
                    return getValue(membrane, get.call(unwrap(this)));
                };
            }
            if (!isUndefined(set)) {
                descriptor.set = function (value) {
                    // At this point we don't have a clear indication of whether
                    // or not a valid mutation will occur, we don't have the key,
                    // and we are not sure why and how they are invoking this setter.
                    // Nevertheless we preserve the original semantics by invoking the
                    // original setter with the original target and the unwrapped value
                    set.call(unwrap(this), membrane.unwrapProxy(value));
                };
            }
        }
        return descriptor;
    }
    class ReactiveMembrane {
        constructor(options) {
            this.valueDistortion = defaultValueDistortion;
            this.valueMutated = defaultValueMutated;
            this.valueObserved = defaultValueObserved;
            this.valueIsObservable = defaultValueIsObservable;
            this.objectGraph = new WeakMap();
            if (!isUndefined(options)) {
                const { valueDistortion, valueMutated, valueObserved, valueIsObservable } = options;
                this.valueDistortion = isFunction(valueDistortion) ? valueDistortion : defaultValueDistortion;
                this.valueMutated = isFunction(valueMutated) ? valueMutated : defaultValueMutated;
                this.valueObserved = isFunction(valueObserved) ? valueObserved : defaultValueObserved;
                this.valueIsObservable = isFunction(valueIsObservable) ? valueIsObservable : defaultValueIsObservable;
            }
        }
        getProxy(value) {
            const unwrappedValue = unwrap(value);
            const distorted = this.valueDistortion(unwrappedValue);
            if (this.valueIsObservable(distorted)) {
                const o = this.getReactiveState(unwrappedValue, distorted);
                // when trying to extract the writable version of a readonly
                // we return the readonly.
                return o.readOnly === value ? value : o.reactive;
            }
            return distorted;
        }
        getReadOnlyProxy(value) {
            value = unwrap(value);
            const distorted = this.valueDistortion(value);
            if (this.valueIsObservable(distorted)) {
                return this.getReactiveState(value, distorted).readOnly;
            }
            return distorted;
        }
        unwrapProxy(p) {
            return unwrap(p);
        }
        getReactiveState(value, distortedValue) {
            const { objectGraph, } = this;
            let reactiveState = objectGraph.get(distortedValue);
            if (reactiveState) {
                return reactiveState;
            }
            const membrane = this;
            reactiveState = {
                get reactive() {
                    const reactiveHandler = new ReactiveProxyHandler(membrane, distortedValue);
                    // caching the reactive proxy after the first time it is accessed
                    const proxy = new Proxy(createShadowTarget(distortedValue), reactiveHandler);
                    registerProxy(proxy, value);
                    ObjectDefineProperty(this, 'reactive', { value: proxy });
                    return proxy;
                },
                get readOnly() {
                    const readOnlyHandler = new ReadOnlyHandler(membrane, distortedValue);
                    // caching the readOnly proxy after the first time it is accessed
                    const proxy = new Proxy(createShadowTarget(distortedValue), readOnlyHandler);
                    registerProxy(proxy, value);
                    ObjectDefineProperty(this, 'readOnly', { value: proxy });
                    return proxy;
                }
            };
            objectGraph.set(distortedValue, reactiveState);
            return reactiveState;
        }
    }
    /** version: 0.26.0 */

    function wrap(data, mutationCallback) {

      let membrane = new ReactiveMembrane({
        valueMutated(target, key) {
          mutationCallback(target, key);
        }

      });
      return {
        data: membrane.getProxy(data),
        membrane: membrane
      };
    }
    function unwrap$1(membrane, observable) {
      let unwrappedData = membrane.unwrapProxy(observable);
      let copy = {};
      Object.keys(unwrappedData).forEach(key => {
        if (['$el', '$refs', '$nextTick', '$watch'].includes(key)) return;
        copy[key] = unwrappedData[key];
      });
      return copy;
    }

    class Component {
      constructor(el, componentForClone = null) {
        this.$el = el;
        const dataAttr = this.$el.getAttribute('x-data');
        const dataExpression = dataAttr === '' ? '{}' : dataAttr;
        const initExpression = this.$el.getAttribute('x-init');
        let dataExtras = {
          $el: this.$el
        };
        let canonicalComponentElementReference = componentForClone ? componentForClone.$el : this.$el;
        Object.entries(Alpine.magicProperties).forEach(([name, callback]) => {
          Object.defineProperty(dataExtras, `$${name}`, {
            get: function get() {
              return callback(canonicalComponentElementReference);
            }
          });
        });
        this.unobservedData = componentForClone ? componentForClone.getUnobservedData() : saferEval(el, dataExpression, dataExtras);
        // Construct a Proxy-based observable. This will be used to handle reactivity.

        let {
          membrane,
          data
        } = this.wrapDataInObservable(this.unobservedData);
        this.$data = data;
        this.membrane = membrane; // After making user-supplied data methods reactive, we can now add
        // our magic properties to the original data for access.

        this.unobservedData.$el = this.$el;
        this.unobservedData.$refs = this.getRefsProxy();
        this.nextTickStack = [];

        this.unobservedData.$nextTick = callback => {
          this.nextTickStack.push(callback);
        };

        this.watchers = {};

        this.unobservedData.$watch = (property, callback) => {
          if (!this.watchers[property]) this.watchers[property] = [];
          this.watchers[property].push(callback);
        };
        /* MODERN-ONLY:START */
        // We remove this piece of code from the legacy build.
        // In IE11, we have already defined our helpers at this point.
        // Register custom magic properties.


        Object.entries(Alpine.magicProperties).forEach(([name, callback]) => {
          Object.defineProperty(this.unobservedData, `$${name}`, {
            get: function get() {
              return callback(canonicalComponentElementReference, this.$el);
            }
          });
        });
        /* MODERN-ONLY:END */

        this.showDirectiveStack = [];
        this.showDirectiveLastElement;
        componentForClone || Alpine.onBeforeComponentInitializeds.forEach(callback => callback(this));
        var initReturnedCallback; // If x-init is present AND we aren't cloning (skip x-init on clone)

        if (initExpression && !componentForClone) {
          // We want to allow data manipulation, but not trigger DOM updates just yet.
          // We haven't even initialized the elements with their Alpine bindings. I mean c'mon.
          this.pauseReactivity = true;
          initReturnedCallback = this.evaluateReturnExpression(this.$el, initExpression);
          this.pauseReactivity = false;
        } // Register all our listeners and set all our attribute bindings.
        // If we're cloning a component, the third parameter ensures no duplicate
        // event listeners are registered (the mutation observer will take care of them)


        this.initializeElements(this.$el, () => {}, componentForClone); // Use mutation observer to detect new elements being added within this component at run-time.
        // Alpine's just so darn flexible amirite?

        this.listenForNewElementsToInitialize();

        if (typeof initReturnedCallback === 'function') {
          // Run the callback returned from the "x-init" hook to allow the user to do stuff after
          // Alpine's got it's grubby little paws all over everything.
          initReturnedCallback.call(this.$data);
        }

        componentForClone || setTimeout(() => {
          Alpine.onComponentInitializeds.forEach(callback => callback(this));
        }, 0);
      }

      getUnobservedData() {
        return unwrap$1(this.membrane, this.$data);
      }

      wrapDataInObservable(data) {
        var self = this;
        let updateDom = debounce(function () {
          self.updateElements(self.$el);
        }, 0);
        return wrap(data, (target, key) => {
          if (self.watchers[key]) {
            // If there's a watcher for this specific key, run it.
            self.watchers[key].forEach(callback => callback(target[key]));
          } else if (Array.isArray(target)) {
            // Arrays are special cases, if any of the items change, we consider the array as mutated.
            Object.keys(self.watchers).forEach(fullDotNotationKey => {
              let dotNotationParts = fullDotNotationKey.split('.'); // Ignore length mutations since they would result in duplicate calls.
              // For example, when calling push, we would get a mutation for the item's key
              // and a second mutation for the length property.

              if (key === 'length') return;
              dotNotationParts.reduce((comparisonData, part) => {
                if (Object.is(target, comparisonData[part])) {
                  self.watchers[fullDotNotationKey].forEach(callback => callback(target));
                }

                return comparisonData[part];
              }, self.unobservedData);
            });
          } else {
            // Let's walk through the watchers with "dot-notation" (foo.bar) and see
            // if this mutation fits any of them.
            Object.keys(self.watchers).filter(i => i.includes('.')).forEach(fullDotNotationKey => {
              let dotNotationParts = fullDotNotationKey.split('.'); // If this dot-notation watcher's last "part" doesn't match the current
              // key, then skip it early for performance reasons.

              if (key !== dotNotationParts[dotNotationParts.length - 1]) return; // Now, walk through the dot-notation "parts" recursively to find
              // a match, and call the watcher if one's found.

              dotNotationParts.reduce((comparisonData, part) => {
                if (Object.is(target, comparisonData)) {
                  // Run the watchers.
                  self.watchers[fullDotNotationKey].forEach(callback => callback(target[key]));
                }

                return comparisonData[part];
              }, self.unobservedData);
            });
          } // Don't react to data changes for cases like the `x-created` hook.


          if (self.pauseReactivity) return;
          updateDom();
        });
      }

      walkAndSkipNestedComponents(el, callback, initializeComponentCallback = () => {}) {
        walk(el, el => {
          // We've hit a component.
          if (el.hasAttribute('x-data')) {
            // If it's not the current one.
            if (!el.isSameNode(this.$el)) {
              // Initialize it if it's not.
              if (!el.__x) initializeComponentCallback(el); // Now we'll let that sub-component deal with itself.

              return false;
            }
          }

          return callback(el);
        });
      }

      initializeElements(rootEl, extraVars = () => {}, componentForClone = false) {
        this.walkAndSkipNestedComponents(rootEl, el => {
          // Don't touch spawns from for loop
          if (el.__x_for_key !== undefined) return false; // Don't touch spawns from if directives

          if (el.__x_inserted_me !== undefined) return false;
          this.initializeElement(el, extraVars, componentForClone ? false : true);
        }, el => {
          if (!componentForClone) el.__x = new Component(el);
        });
        this.executeAndClearRemainingShowDirectiveStack();
        this.executeAndClearNextTickStack(rootEl);
      }

      initializeElement(el, extraVars, shouldRegisterListeners = true) {
        // To support class attribute merging, we have to know what the element's
        // original class attribute looked like for reference.
        if (el.hasAttribute('class') && getXAttrs(el, this).length > 0) {
          el.__x_original_classes = convertClassStringToArray(el.getAttribute('class'));
        }

        shouldRegisterListeners && this.registerListeners(el, extraVars);
        this.resolveBoundAttributes(el, true, extraVars);
      }

      updateElements(rootEl, extraVars = () => {}) {
        this.walkAndSkipNestedComponents(rootEl, el => {
          // Don't touch spawns from for loop (and check if the root is actually a for loop in a parent, don't skip it.)
          if (el.__x_for_key !== undefined && !el.isSameNode(this.$el)) return false;
          this.updateElement(el, extraVars);
        }, el => {
          el.__x = new Component(el);
        });
        this.executeAndClearRemainingShowDirectiveStack();
        this.executeAndClearNextTickStack(rootEl);
      }

      executeAndClearNextTickStack(el) {
        // Skip spawns from alpine directives
        if (el === this.$el && this.nextTickStack.length > 0) {
          // We run the tick stack after the next frame to allow any
          // running transitions to pass the initial show stage.
          requestAnimationFrame(() => {
            while (this.nextTickStack.length > 0) {
              this.nextTickStack.shift()();
            }
          });
        }
      }

      executeAndClearRemainingShowDirectiveStack() {
        // The goal here is to start all the x-show transitions
        // and build a nested promise chain so that elements
        // only hide when the children are finished hiding.
        this.showDirectiveStack.reverse().map(handler => {
          return new Promise((resolve, reject) => {
            handler(resolve, reject);
          });
        }).reduce((promiseChain, promise) => {
          return promiseChain.then(() => {
            return promise.then(finishElement => {
              finishElement();
            });
          });
        }, Promise.resolve(() => {})).catch(e => {
          if (e !== TRANSITION_CANCELLED) throw e;
        }); // We've processed the handler stack. let's clear it.

        this.showDirectiveStack = [];
        this.showDirectiveLastElement = undefined;
      }

      updateElement(el, extraVars) {
        this.resolveBoundAttributes(el, false, extraVars);
      }

      registerListeners(el, extraVars) {
        getXAttrs(el, this).forEach(({
          type,
          value,
          modifiers,
          expression
        }) => {
          switch (type) {
            case 'on':
              registerListener(this, el, value, modifiers, expression, extraVars);
              break;

            case 'model':
              registerModelListener(this, el, modifiers, expression, extraVars);
              break;
          }
        });
      }

      resolveBoundAttributes(el, initialUpdate = false, extraVars) {
        let attrs = getXAttrs(el, this);
        attrs.forEach(({
          type,
          value,
          modifiers,
          expression
        }) => {
          switch (type) {
            case 'model':
              handleAttributeBindingDirective(this, el, 'value', expression, extraVars, type, modifiers);
              break;

            case 'bind':
              // The :key binding on an x-for is special, ignore it.
              if (el.tagName.toLowerCase() === 'template' && value === 'key') return;
              handleAttributeBindingDirective(this, el, value, expression, extraVars, type, modifiers);
              break;

            case 'text':
              var output = this.evaluateReturnExpression(el, expression, extraVars);
              handleTextDirective(el, output, expression);
              break;

            case 'html':
              handleHtmlDirective(this, el, expression, extraVars);
              break;

            case 'show':
              var output = this.evaluateReturnExpression(el, expression, extraVars);
              handleShowDirective(this, el, output, modifiers, initialUpdate);
              break;

            case 'if':
              // If this element also has x-for on it, don't process x-if.
              // We will let the "x-for" directive handle the "if"ing.
              if (attrs.some(i => i.type === 'for')) return;
              var output = this.evaluateReturnExpression(el, expression, extraVars);
              handleIfDirective(this, el, output, initialUpdate, extraVars);
              break;

            case 'for':
              handleForDirective(this, el, expression, initialUpdate, extraVars);
              break;

            case 'cloak':
              el.removeAttribute('x-cloak');
              break;
          }
        });
      }

      evaluateReturnExpression(el, expression, extraVars = () => {}) {
        return saferEval(el, expression, this.$data, _objectSpread2(_objectSpread2({}, extraVars()), {}, {
          $dispatch: this.getDispatchFunction(el)
        }));
      }

      evaluateCommandExpression(el, expression, extraVars = () => {}) {
        return saferEvalNoReturn(el, expression, this.$data, _objectSpread2(_objectSpread2({}, extraVars()), {}, {
          $dispatch: this.getDispatchFunction(el)
        }));
      }

      getDispatchFunction(el) {
        return (event, detail = {}) => {
          el.dispatchEvent(new CustomEvent(event, {
            detail,
            bubbles: true
          }));
        };
      }

      listenForNewElementsToInitialize() {
        const targetNode = this.$el;
        const observerOptions = {
          childList: true,
          attributes: true,
          subtree: true
        };
        const observer = new MutationObserver(mutations => {
          for (let i = 0; i < mutations.length; i++) {
            // Filter out mutations triggered from child components.
            const closestParentComponent = mutations[i].target.closest('[x-data]');
            if (!(closestParentComponent && closestParentComponent.isSameNode(this.$el))) continue;

            if (mutations[i].type === 'attributes' && mutations[i].attributeName === 'x-data') {
              const xAttr = mutations[i].target.getAttribute('x-data') || '{}';
              const rawData = saferEval(this.$el, xAttr, {
                $el: this.$el
              });
              Object.keys(rawData).forEach(key => {
                if (this.$data[key] !== rawData[key]) {
                  this.$data[key] = rawData[key];
                }
              });
            }

            if (mutations[i].addedNodes.length > 0) {
              mutations[i].addedNodes.forEach(node => {
                if (node.nodeType !== 1 || node.__x_inserted_me) return;

                if (node.matches('[x-data]') && !node.__x) {
                  node.__x = new Component(node);
                  return;
                }

                this.initializeElements(node);
              });
            }
          }
        });
        observer.observe(targetNode, observerOptions);
      }

      getRefsProxy() {
        var self = this;
        var refObj = {};
        // One of the goals of this is to not hold elements in memory, but rather re-evaluate
        // the DOM when the system needs something from it. This way, the framework is flexible and
        // friendly to outside DOM changes from libraries like Vue/Livewire.
        // For this reason, I'm using an "on-demand" proxy to fake a "$refs" object.

        return new Proxy(refObj, {
          get(object, property) {
            if (property === '$isAlpineProxy') return true;
            var ref; // We can't just query the DOM because it's hard to filter out refs in
            // nested components.

            self.walkAndSkipNestedComponents(self.$el, el => {
              if (el.hasAttribute('x-ref') && el.getAttribute('x-ref') === property) {
                ref = el;
              }
            });
            return ref;
          }

        });
      }

    }

    const Alpine = {
      version: "2.8.2",
      pauseMutationObserver: false,
      magicProperties: {},
      onComponentInitializeds: [],
      onBeforeComponentInitializeds: [],
      ignoreFocusedForValueBinding: false,
      start: async function start() {
        if (!isTesting()) {
          await domReady();
        }

        this.discoverComponents(el => {
          this.initializeComponent(el);
        }); // It's easier and more performant to just support Turbolinks than listen
        // to MutationObserver mutations at the document level.

        document.addEventListener("turbolinks:load", () => {
          this.discoverUninitializedComponents(el => {
            this.initializeComponent(el);
          });
        });
        this.listenForNewUninitializedComponentsAtRunTime();
      },
      discoverComponents: function discoverComponents(callback) {
        const rootEls = document.querySelectorAll('[x-data]');
        rootEls.forEach(rootEl => {
          callback(rootEl);
        });
      },
      discoverUninitializedComponents: function discoverUninitializedComponents(callback, el = null) {
        const rootEls = (el || document).querySelectorAll('[x-data]');
        Array.from(rootEls).filter(el => el.__x === undefined).forEach(rootEl => {
          callback(rootEl);
        });
      },
      listenForNewUninitializedComponentsAtRunTime: function listenForNewUninitializedComponentsAtRunTime() {
        const targetNode = document.querySelector('body');
        const observerOptions = {
          childList: true,
          attributes: true,
          subtree: true
        };
        const observer = new MutationObserver(mutations => {
          if (this.pauseMutationObserver) return;

          for (let i = 0; i < mutations.length; i++) {
            if (mutations[i].addedNodes.length > 0) {
              mutations[i].addedNodes.forEach(node => {
                // Discard non-element nodes (like line-breaks)
                if (node.nodeType !== 1) return; // Discard any changes happening within an existing component.
                // They will take care of themselves.

                if (node.parentElement && node.parentElement.closest('[x-data]')) return;
                this.discoverUninitializedComponents(el => {
                  this.initializeComponent(el);
                }, node.parentElement);
              });
            }
          }
        });
        observer.observe(targetNode, observerOptions);
      },
      initializeComponent: function initializeComponent(el) {
        if (!el.__x) {
          // Wrap in a try/catch so that we don't prevent other components
          // from initializing when one component contains an error.
          try {
            el.__x = new Component(el);
          } catch (error) {
            setTimeout(() => {
              throw error;
            }, 0);
          }
        }
      },
      clone: function clone(component, newEl) {
        if (!newEl.__x) {
          newEl.__x = new Component(newEl, component);
        }
      },
      addMagicProperty: function addMagicProperty(name, callback) {
        this.magicProperties[name] = callback;
      },
      onComponentInitialized: function onComponentInitialized(callback) {
        this.onComponentInitializeds.push(callback);
      },
      onBeforeComponentInitialized: function onBeforeComponentInitialized(callback) {
        this.onBeforeComponentInitializeds.push(callback);
      }
    };

    if (!isTesting()) {
      window.Alpine = Alpine;

      if (window.deferLoadingAlpine) {
        window.deferLoadingAlpine(function () {
          window.Alpine.start();
        });
      } else {
        window.Alpine.start();
      }
    }

    return Alpine;

  })));

  /**!
   * Sortable 1.13.0
   * @author	RubaXa   <trash@rubaxa.org>
   * @author	owenm    <owen23355@gmail.com>
   * @license MIT
   */
  function _typeof(obj) {
    if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
      _typeof = function (obj) {
        return typeof obj;
      };
    } else {
      _typeof = function (obj) {
        return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
      };
    }

    return _typeof(obj);
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _extends() {
    _extends = Object.assign || function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };

    return _extends.apply(this, arguments);
  }

  function _objectSpread(target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i] != null ? arguments[i] : {};
      var ownKeys = Object.keys(source);

      if (typeof Object.getOwnPropertySymbols === 'function') {
        ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) {
          return Object.getOwnPropertyDescriptor(source, sym).enumerable;
        }));
      }

      ownKeys.forEach(function (key) {
        _defineProperty(target, key, source[key]);
      });
    }

    return target;
  }

  function _objectWithoutPropertiesLoose(source, excluded) {
    if (source == null) return {};
    var target = {};
    var sourceKeys = Object.keys(source);
    var key, i;

    for (i = 0; i < sourceKeys.length; i++) {
      key = sourceKeys[i];
      if (excluded.indexOf(key) >= 0) continue;
      target[key] = source[key];
    }

    return target;
  }

  function _objectWithoutProperties(source, excluded) {
    if (source == null) return {};

    var target = _objectWithoutPropertiesLoose(source, excluded);

    var key, i;

    if (Object.getOwnPropertySymbols) {
      var sourceSymbolKeys = Object.getOwnPropertySymbols(source);

      for (i = 0; i < sourceSymbolKeys.length; i++) {
        key = sourceSymbolKeys[i];
        if (excluded.indexOf(key) >= 0) continue;
        if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue;
        target[key] = source[key];
      }
    }

    return target;
  }

  var version = "1.13.0";

  function userAgent(pattern) {
    if (typeof window !== 'undefined' && window.navigator) {
      return !!
      /*@__PURE__*/
      navigator.userAgent.match(pattern);
    }
  }

  var IE11OrLess = userAgent(/(?:Trident.*rv[ :]?11\.|msie|iemobile|Windows Phone)/i);
  var Edge = userAgent(/Edge/i);
  var FireFox = userAgent(/firefox/i);
  var Safari = userAgent(/safari/i) && !userAgent(/chrome/i) && !userAgent(/android/i);
  var IOS = userAgent(/iP(ad|od|hone)/i);
  var ChromeForAndroid = userAgent(/chrome/i) && userAgent(/android/i);

  var captureMode = {
    capture: false,
    passive: false
  };

  function on(el, event, fn) {
    el.addEventListener(event, fn, !IE11OrLess && captureMode);
  }

  function off(el, event, fn) {
    el.removeEventListener(event, fn, !IE11OrLess && captureMode);
  }

  function matches(
  /**HTMLElement*/
  el,
  /**String*/
  selector) {
    if (!selector) return;
    selector[0] === '>' && (selector = selector.substring(1));

    if (el) {
      try {
        if (el.matches) {
          return el.matches(selector);
        } else if (el.msMatchesSelector) {
          return el.msMatchesSelector(selector);
        } else if (el.webkitMatchesSelector) {
          return el.webkitMatchesSelector(selector);
        }
      } catch (_) {
        return false;
      }
    }

    return false;
  }

  function getParentOrHost(el) {
    return el.host && el !== document && el.host.nodeType ? el.host : el.parentNode;
  }

  function closest(
  /**HTMLElement*/
  el,
  /**String*/
  selector,
  /**HTMLElement*/
  ctx, includeCTX) {
    if (el) {
      ctx = ctx || document;

      do {
        if (selector != null && (selector[0] === '>' ? el.parentNode === ctx && matches(el, selector) : matches(el, selector)) || includeCTX && el === ctx) {
          return el;
        }

        if (el === ctx) break;
        /* jshint boss:true */
      } while (el = getParentOrHost(el));
    }

    return null;
  }

  var R_SPACE = /\s+/g;

  function toggleClass(el, name, state) {
    if (el && name) {
      if (el.classList) {
        el.classList[state ? 'add' : 'remove'](name);
      } else {
        var className = (' ' + el.className + ' ').replace(R_SPACE, ' ').replace(' ' + name + ' ', ' ');
        el.className = (className + (state ? ' ' + name : '')).replace(R_SPACE, ' ');
      }
    }
  }

  function css(el, prop, val) {
    var style = el && el.style;

    if (style) {
      if (val === void 0) {
        if (document.defaultView && document.defaultView.getComputedStyle) {
          val = document.defaultView.getComputedStyle(el, '');
        } else if (el.currentStyle) {
          val = el.currentStyle;
        }

        return prop === void 0 ? val : val[prop];
      } else {
        if (!(prop in style) && prop.indexOf('webkit') === -1) {
          prop = '-webkit-' + prop;
        }

        style[prop] = val + (typeof val === 'string' ? '' : 'px');
      }
    }
  }

  function matrix(el, selfOnly) {
    var appliedTransforms = '';

    if (typeof el === 'string') {
      appliedTransforms = el;
    } else {
      do {
        var transform = css(el, 'transform');

        if (transform && transform !== 'none') {
          appliedTransforms = transform + ' ' + appliedTransforms;
        }
        /* jshint boss:true */

      } while (!selfOnly && (el = el.parentNode));
    }

    var matrixFn = window.DOMMatrix || window.WebKitCSSMatrix || window.CSSMatrix || window.MSCSSMatrix;
    /*jshint -W056 */

    return matrixFn && new matrixFn(appliedTransforms);
  }

  function find(ctx, tagName, iterator) {
    if (ctx) {
      var list = ctx.getElementsByTagName(tagName),
          i = 0,
          n = list.length;

      if (iterator) {
        for (; i < n; i++) {
          iterator(list[i], i);
        }
      }

      return list;
    }

    return [];
  }

  function getWindowScrollingElement() {
    var scrollingElement = document.scrollingElement;

    if (scrollingElement) {
      return scrollingElement;
    } else {
      return document.documentElement;
    }
  }
  /**
   * Returns the "bounding client rect" of given element
   * @param  {HTMLElement} el                       The element whose boundingClientRect is wanted
   * @param  {[Boolean]} relativeToContainingBlock  Whether the rect should be relative to the containing block of (including) the container
   * @param  {[Boolean]} relativeToNonStaticParent  Whether the rect should be relative to the relative parent of (including) the contaienr
   * @param  {[Boolean]} undoScale                  Whether the container's scale() should be undone
   * @param  {[HTMLElement]} container              The parent the element will be placed in
   * @return {Object}                               The boundingClientRect of el, with specified adjustments
   */


  function getRect(el, relativeToContainingBlock, relativeToNonStaticParent, undoScale, container) {
    if (!el.getBoundingClientRect && el !== window) return;
    var elRect, top, left, bottom, right, height, width;

    if (el !== window && el.parentNode && el !== getWindowScrollingElement()) {
      elRect = el.getBoundingClientRect();
      top = elRect.top;
      left = elRect.left;
      bottom = elRect.bottom;
      right = elRect.right;
      height = elRect.height;
      width = elRect.width;
    } else {
      top = 0;
      left = 0;
      bottom = window.innerHeight;
      right = window.innerWidth;
      height = window.innerHeight;
      width = window.innerWidth;
    }

    if ((relativeToContainingBlock || relativeToNonStaticParent) && el !== window) {
      // Adjust for translate()
      container = container || el.parentNode; // solves #1123 (see: https://stackoverflow.com/a/37953806/6088312)
      // Not needed on <= IE11

      if (!IE11OrLess) {
        do {
          if (container && container.getBoundingClientRect && (css(container, 'transform') !== 'none' || relativeToNonStaticParent && css(container, 'position') !== 'static')) {
            var containerRect = container.getBoundingClientRect(); // Set relative to edges of padding box of container

            top -= containerRect.top + parseInt(css(container, 'border-top-width'));
            left -= containerRect.left + parseInt(css(container, 'border-left-width'));
            bottom = top + elRect.height;
            right = left + elRect.width;
            break;
          }
          /* jshint boss:true */

        } while (container = container.parentNode);
      }
    }

    if (undoScale && el !== window) {
      // Adjust for scale()
      var elMatrix = matrix(container || el),
          scaleX = elMatrix && elMatrix.a,
          scaleY = elMatrix && elMatrix.d;

      if (elMatrix) {
        top /= scaleY;
        left /= scaleX;
        width /= scaleX;
        height /= scaleY;
        bottom = top + height;
        right = left + width;
      }
    }

    return {
      top: top,
      left: left,
      bottom: bottom,
      right: right,
      width: width,
      height: height
    };
  }
  /**
   * Checks if a side of an element is scrolled past a side of its parents
   * @param  {HTMLElement}  el           The element who's side being scrolled out of view is in question
   * @param  {String}       elSide       Side of the element in question ('top', 'left', 'right', 'bottom')
   * @param  {String}       parentSide   Side of the parent in question ('top', 'left', 'right', 'bottom')
   * @return {HTMLElement}               The parent scroll element that the el's side is scrolled past, or null if there is no such element
   */


  function isScrolledPast(el, elSide, parentSide) {
    var parent = getParentAutoScrollElement(el, true),
        elSideVal = getRect(el)[elSide];
    /* jshint boss:true */

    while (parent) {
      var parentSideVal = getRect(parent)[parentSide],
          visible = void 0;

      if (parentSide === 'top' || parentSide === 'left') {
        visible = elSideVal >= parentSideVal;
      } else {
        visible = elSideVal <= parentSideVal;
      }

      if (!visible) return parent;
      if (parent === getWindowScrollingElement()) break;
      parent = getParentAutoScrollElement(parent, false);
    }

    return false;
  }
  /**
   * Gets nth child of el, ignoring hidden children, sortable's elements (does not ignore clone if it's visible)
   * and non-draggable elements
   * @param  {HTMLElement} el       The parent element
   * @param  {Number} childNum      The index of the child
   * @param  {Object} options       Parent Sortable's options
   * @return {HTMLElement}          The child at index childNum, or null if not found
   */


  function getChild(el, childNum, options) {
    var currentChild = 0,
        i = 0,
        children = el.children;

    while (i < children.length) {
      if (children[i].style.display !== 'none' && children[i] !== Sortable.ghost && children[i] !== Sortable.dragged && closest(children[i], options.draggable, el, false)) {
        if (currentChild === childNum) {
          return children[i];
        }

        currentChild++;
      }

      i++;
    }

    return null;
  }
  /**
   * Gets the last child in the el, ignoring ghostEl or invisible elements (clones)
   * @param  {HTMLElement} el       Parent element
   * @param  {selector} selector    Any other elements that should be ignored
   * @return {HTMLElement}          The last child, ignoring ghostEl
   */


  function lastChild(el, selector) {
    var last = el.lastElementChild;

    while (last && (last === Sortable.ghost || css(last, 'display') === 'none' || selector && !matches(last, selector))) {
      last = last.previousElementSibling;
    }

    return last || null;
  }
  /**
   * Returns the index of an element within its parent for a selected set of
   * elements
   * @param  {HTMLElement} el
   * @param  {selector} selector
   * @return {number}
   */


  function index(el, selector) {
    var index = 0;

    if (!el || !el.parentNode) {
      return -1;
    }
    /* jshint boss:true */


    while (el = el.previousElementSibling) {
      if (el.nodeName.toUpperCase() !== 'TEMPLATE' && el !== Sortable.clone && (!selector || matches(el, selector))) {
        index++;
      }
    }

    return index;
  }
  /**
   * Returns the scroll offset of the given element, added with all the scroll offsets of parent elements.
   * The value is returned in real pixels.
   * @param  {HTMLElement} el
   * @return {Array}             Offsets in the format of [left, top]
   */


  function getRelativeScrollOffset(el) {
    var offsetLeft = 0,
        offsetTop = 0,
        winScroller = getWindowScrollingElement();

    if (el) {
      do {
        var elMatrix = matrix(el),
            scaleX = elMatrix.a,
            scaleY = elMatrix.d;
        offsetLeft += el.scrollLeft * scaleX;
        offsetTop += el.scrollTop * scaleY;
      } while (el !== winScroller && (el = el.parentNode));
    }

    return [offsetLeft, offsetTop];
  }
  /**
   * Returns the index of the object within the given array
   * @param  {Array} arr   Array that may or may not hold the object
   * @param  {Object} obj  An object that has a key-value pair unique to and identical to a key-value pair in the object you want to find
   * @return {Number}      The index of the object in the array, or -1
   */


  function indexOfObject(arr, obj) {
    for (var i in arr) {
      if (!arr.hasOwnProperty(i)) continue;

      for (var key in obj) {
        if (obj.hasOwnProperty(key) && obj[key] === arr[i][key]) return Number(i);
      }
    }

    return -1;
  }

  function getParentAutoScrollElement(el, includeSelf) {
    // skip to window
    if (!el || !el.getBoundingClientRect) return getWindowScrollingElement();
    var elem = el;
    var gotSelf = false;

    do {
      // we don't need to get elem css if it isn't even overflowing in the first place (performance)
      if (elem.clientWidth < elem.scrollWidth || elem.clientHeight < elem.scrollHeight) {
        var elemCSS = css(elem);

        if (elem.clientWidth < elem.scrollWidth && (elemCSS.overflowX == 'auto' || elemCSS.overflowX == 'scroll') || elem.clientHeight < elem.scrollHeight && (elemCSS.overflowY == 'auto' || elemCSS.overflowY == 'scroll')) {
          if (!elem.getBoundingClientRect || elem === document.body) return getWindowScrollingElement();
          if (gotSelf || includeSelf) return elem;
          gotSelf = true;
        }
      }
      /* jshint boss:true */

    } while (elem = elem.parentNode);

    return getWindowScrollingElement();
  }

  function extend(dst, src) {
    if (dst && src) {
      for (var key in src) {
        if (src.hasOwnProperty(key)) {
          dst[key] = src[key];
        }
      }
    }

    return dst;
  }

  function isRectEqual(rect1, rect2) {
    return Math.round(rect1.top) === Math.round(rect2.top) && Math.round(rect1.left) === Math.round(rect2.left) && Math.round(rect1.height) === Math.round(rect2.height) && Math.round(rect1.width) === Math.round(rect2.width);
  }

  var _throttleTimeout;

  function throttle(callback, ms) {
    return function () {
      if (!_throttleTimeout) {
        var args = arguments,
            _this = this;

        if (args.length === 1) {
          callback.call(_this, args[0]);
        } else {
          callback.apply(_this, args);
        }

        _throttleTimeout = setTimeout(function () {
          _throttleTimeout = void 0;
        }, ms);
      }
    };
  }

  function cancelThrottle() {
    clearTimeout(_throttleTimeout);
    _throttleTimeout = void 0;
  }

  function scrollBy(el, x, y) {
    el.scrollLeft += x;
    el.scrollTop += y;
  }

  function clone(el) {
    var Polymer = window.Polymer;
    var $ = window.jQuery || window.Zepto;

    if (Polymer && Polymer.dom) {
      return Polymer.dom(el).cloneNode(true);
    } else if ($) {
      return $(el).clone(true)[0];
    } else {
      return el.cloneNode(true);
    }
  }

  var expando = 'Sortable' + new Date().getTime();

  function AnimationStateManager() {
    var animationStates = [],
        animationCallbackId;
    return {
      captureAnimationState: function captureAnimationState() {
        animationStates = [];
        if (!this.options.animation) return;
        var children = [].slice.call(this.el.children);
        children.forEach(function (child) {
          if (css(child, 'display') === 'none' || child === Sortable.ghost) return;
          animationStates.push({
            target: child,
            rect: getRect(child)
          });

          var fromRect = _objectSpread({}, animationStates[animationStates.length - 1].rect); // If animating: compensate for current animation


          if (child.thisAnimationDuration) {
            var childMatrix = matrix(child, true);

            if (childMatrix) {
              fromRect.top -= childMatrix.f;
              fromRect.left -= childMatrix.e;
            }
          }

          child.fromRect = fromRect;
        });
      },
      addAnimationState: function addAnimationState(state) {
        animationStates.push(state);
      },
      removeAnimationState: function removeAnimationState(target) {
        animationStates.splice(indexOfObject(animationStates, {
          target: target
        }), 1);
      },
      animateAll: function animateAll(callback) {
        var _this = this;

        if (!this.options.animation) {
          clearTimeout(animationCallbackId);
          if (typeof callback === 'function') callback();
          return;
        }

        var animating = false,
            animationTime = 0;
        animationStates.forEach(function (state) {
          var time = 0,
              target = state.target,
              fromRect = target.fromRect,
              toRect = getRect(target),
              prevFromRect = target.prevFromRect,
              prevToRect = target.prevToRect,
              animatingRect = state.rect,
              targetMatrix = matrix(target, true);

          if (targetMatrix) {
            // Compensate for current animation
            toRect.top -= targetMatrix.f;
            toRect.left -= targetMatrix.e;
          }

          target.toRect = toRect;

          if (target.thisAnimationDuration) {
            // Could also check if animatingRect is between fromRect and toRect
            if (isRectEqual(prevFromRect, toRect) && !isRectEqual(fromRect, toRect) && // Make sure animatingRect is on line between toRect & fromRect
            (animatingRect.top - toRect.top) / (animatingRect.left - toRect.left) === (fromRect.top - toRect.top) / (fromRect.left - toRect.left)) {
              // If returning to same place as started from animation and on same axis
              time = calculateRealTime(animatingRect, prevFromRect, prevToRect, _this.options);
            }
          } // if fromRect != toRect: animate


          if (!isRectEqual(toRect, fromRect)) {
            target.prevFromRect = fromRect;
            target.prevToRect = toRect;

            if (!time) {
              time = _this.options.animation;
            }

            _this.animate(target, animatingRect, toRect, time);
          }

          if (time) {
            animating = true;
            animationTime = Math.max(animationTime, time);
            clearTimeout(target.animationResetTimer);
            target.animationResetTimer = setTimeout(function () {
              target.animationTime = 0;
              target.prevFromRect = null;
              target.fromRect = null;
              target.prevToRect = null;
              target.thisAnimationDuration = null;
            }, time);
            target.thisAnimationDuration = time;
          }
        });
        clearTimeout(animationCallbackId);

        if (!animating) {
          if (typeof callback === 'function') callback();
        } else {
          animationCallbackId = setTimeout(function () {
            if (typeof callback === 'function') callback();
          }, animationTime);
        }

        animationStates = [];
      },
      animate: function animate(target, currentRect, toRect, duration) {
        if (duration) {
          css(target, 'transition', '');
          css(target, 'transform', '');
          var elMatrix = matrix(this.el),
              scaleX = elMatrix && elMatrix.a,
              scaleY = elMatrix && elMatrix.d,
              translateX = (currentRect.left - toRect.left) / (scaleX || 1),
              translateY = (currentRect.top - toRect.top) / (scaleY || 1);
          target.animatingX = !!translateX;
          target.animatingY = !!translateY;
          css(target, 'transform', 'translate3d(' + translateX + 'px,' + translateY + 'px,0)');
          this.forRepaintDummy = repaint(target); // repaint

          css(target, 'transition', 'transform ' + duration + 'ms' + (this.options.easing ? ' ' + this.options.easing : ''));
          css(target, 'transform', 'translate3d(0,0,0)');
          typeof target.animated === 'number' && clearTimeout(target.animated);
          target.animated = setTimeout(function () {
            css(target, 'transition', '');
            css(target, 'transform', '');
            target.animated = false;
            target.animatingX = false;
            target.animatingY = false;
          }, duration);
        }
      }
    };
  }

  function repaint(target) {
    return target.offsetWidth;
  }

  function calculateRealTime(animatingRect, fromRect, toRect, options) {
    return Math.sqrt(Math.pow(fromRect.top - animatingRect.top, 2) + Math.pow(fromRect.left - animatingRect.left, 2)) / Math.sqrt(Math.pow(fromRect.top - toRect.top, 2) + Math.pow(fromRect.left - toRect.left, 2)) * options.animation;
  }

  var plugins = [];
  var defaults = {
    initializeByDefault: true
  };
  var PluginManager = {
    mount: function mount(plugin) {
      // Set default static properties
      for (var option in defaults) {
        if (defaults.hasOwnProperty(option) && !(option in plugin)) {
          plugin[option] = defaults[option];
        }
      }

      plugins.forEach(function (p) {
        if (p.pluginName === plugin.pluginName) {
          throw "Sortable: Cannot mount plugin ".concat(plugin.pluginName, " more than once");
        }
      });
      plugins.push(plugin);
    },
    pluginEvent: function pluginEvent(eventName, sortable, evt) {
      var _this = this;

      this.eventCanceled = false;

      evt.cancel = function () {
        _this.eventCanceled = true;
      };

      var eventNameGlobal = eventName + 'Global';
      plugins.forEach(function (plugin) {
        if (!sortable[plugin.pluginName]) return; // Fire global events if it exists in this sortable

        if (sortable[plugin.pluginName][eventNameGlobal]) {
          sortable[plugin.pluginName][eventNameGlobal](_objectSpread({
            sortable: sortable
          }, evt));
        } // Only fire plugin event if plugin is enabled in this sortable,
        // and plugin has event defined


        if (sortable.options[plugin.pluginName] && sortable[plugin.pluginName][eventName]) {
          sortable[plugin.pluginName][eventName](_objectSpread({
            sortable: sortable
          }, evt));
        }
      });
    },
    initializePlugins: function initializePlugins(sortable, el, defaults, options) {
      plugins.forEach(function (plugin) {
        var pluginName = plugin.pluginName;
        if (!sortable.options[pluginName] && !plugin.initializeByDefault) return;
        var initialized = new plugin(sortable, el, sortable.options);
        initialized.sortable = sortable;
        initialized.options = sortable.options;
        sortable[pluginName] = initialized; // Add default options from plugin

        _extends(defaults, initialized.defaults);
      });

      for (var option in sortable.options) {
        if (!sortable.options.hasOwnProperty(option)) continue;
        var modified = this.modifyOption(sortable, option, sortable.options[option]);

        if (typeof modified !== 'undefined') {
          sortable.options[option] = modified;
        }
      }
    },
    getEventProperties: function getEventProperties(name, sortable) {
      var eventProperties = {};
      plugins.forEach(function (plugin) {
        if (typeof plugin.eventProperties !== 'function') return;

        _extends(eventProperties, plugin.eventProperties.call(sortable[plugin.pluginName], name));
      });
      return eventProperties;
    },
    modifyOption: function modifyOption(sortable, name, value) {
      var modifiedValue;
      plugins.forEach(function (plugin) {
        // Plugin must exist on the Sortable
        if (!sortable[plugin.pluginName]) return; // If static option listener exists for this option, call in the context of the Sortable's instance of this plugin

        if (plugin.optionListeners && typeof plugin.optionListeners[name] === 'function') {
          modifiedValue = plugin.optionListeners[name].call(sortable[plugin.pluginName], value);
        }
      });
      return modifiedValue;
    }
  };

  function dispatchEvent(_ref) {
    var sortable = _ref.sortable,
        rootEl = _ref.rootEl,
        name = _ref.name,
        targetEl = _ref.targetEl,
        cloneEl = _ref.cloneEl,
        toEl = _ref.toEl,
        fromEl = _ref.fromEl,
        oldIndex = _ref.oldIndex,
        newIndex = _ref.newIndex,
        oldDraggableIndex = _ref.oldDraggableIndex,
        newDraggableIndex = _ref.newDraggableIndex,
        originalEvent = _ref.originalEvent,
        putSortable = _ref.putSortable,
        extraEventProperties = _ref.extraEventProperties;
    sortable = sortable || rootEl && rootEl[expando];
    if (!sortable) return;
    var evt,
        options = sortable.options,
        onName = 'on' + name.charAt(0).toUpperCase() + name.substr(1); // Support for new CustomEvent feature

    if (window.CustomEvent && !IE11OrLess && !Edge) {
      evt = new CustomEvent(name, {
        bubbles: true,
        cancelable: true
      });
    } else {
      evt = document.createEvent('Event');
      evt.initEvent(name, true, true);
    }

    evt.to = toEl || rootEl;
    evt.from = fromEl || rootEl;
    evt.item = targetEl || rootEl;
    evt.clone = cloneEl;
    evt.oldIndex = oldIndex;
    evt.newIndex = newIndex;
    evt.oldDraggableIndex = oldDraggableIndex;
    evt.newDraggableIndex = newDraggableIndex;
    evt.originalEvent = originalEvent;
    evt.pullMode = putSortable ? putSortable.lastPutMode : undefined;

    var allEventProperties = _objectSpread({}, extraEventProperties, PluginManager.getEventProperties(name, sortable));

    for (var option in allEventProperties) {
      evt[option] = allEventProperties[option];
    }

    if (rootEl) {
      rootEl.dispatchEvent(evt);
    }

    if (options[onName]) {
      options[onName].call(sortable, evt);
    }
  }

  var pluginEvent = function pluginEvent(eventName, sortable) {
    var _ref = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {},
        originalEvent = _ref.evt,
        data = _objectWithoutProperties(_ref, ["evt"]);

    PluginManager.pluginEvent.bind(Sortable)(eventName, sortable, _objectSpread({
      dragEl: dragEl,
      parentEl: parentEl,
      ghostEl: ghostEl,
      rootEl: rootEl,
      nextEl: nextEl,
      lastDownEl: lastDownEl,
      cloneEl: cloneEl,
      cloneHidden: cloneHidden,
      dragStarted: moved,
      putSortable: putSortable,
      activeSortable: Sortable.active,
      originalEvent: originalEvent,
      oldIndex: oldIndex,
      oldDraggableIndex: oldDraggableIndex,
      newIndex: newIndex,
      newDraggableIndex: newDraggableIndex,
      hideGhostForTarget: _hideGhostForTarget,
      unhideGhostForTarget: _unhideGhostForTarget,
      cloneNowHidden: function cloneNowHidden() {
        cloneHidden = true;
      },
      cloneNowShown: function cloneNowShown() {
        cloneHidden = false;
      },
      dispatchSortableEvent: function dispatchSortableEvent(name) {
        _dispatchEvent({
          sortable: sortable,
          name: name,
          originalEvent: originalEvent
        });
      }
    }, data));
  };

  function _dispatchEvent(info) {
    dispatchEvent(_objectSpread({
      putSortable: putSortable,
      cloneEl: cloneEl,
      targetEl: dragEl,
      rootEl: rootEl,
      oldIndex: oldIndex,
      oldDraggableIndex: oldDraggableIndex,
      newIndex: newIndex,
      newDraggableIndex: newDraggableIndex
    }, info));
  }

  var dragEl,
      parentEl,
      ghostEl,
      rootEl,
      nextEl,
      lastDownEl,
      cloneEl,
      cloneHidden,
      oldIndex,
      newIndex,
      oldDraggableIndex,
      newDraggableIndex,
      activeGroup,
      putSortable,
      awaitingDragStarted = false,
      ignoreNextClick = false,
      sortables = [],
      tapEvt,
      touchEvt,
      lastDx,
      lastDy,
      tapDistanceLeft,
      tapDistanceTop,
      moved,
      lastTarget,
      lastDirection,
      pastFirstInvertThresh = false,
      isCircumstantialInvert = false,
      targetMoveDistance,
      // For positioning ghost absolutely
  ghostRelativeParent,
      ghostRelativeParentInitialScroll = [],
      // (left, top)
  _silent = false,
      savedInputChecked = [];
  /** @const */

  var documentExists = typeof document !== 'undefined',
      PositionGhostAbsolutely = IOS,
      CSSFloatProperty = Edge || IE11OrLess ? 'cssFloat' : 'float',
      // This will not pass for IE9, because IE9 DnD only works on anchors
  supportDraggable = documentExists && !ChromeForAndroid && !IOS && 'draggable' in document.createElement('div'),
      supportCssPointerEvents = function () {
    if (!documentExists) return; // false when <= IE11

    if (IE11OrLess) {
      return false;
    }

    var el = document.createElement('x');
    el.style.cssText = 'pointer-events:auto';
    return el.style.pointerEvents === 'auto';
  }(),
      _detectDirection = function _detectDirection(el, options) {
    var elCSS = css(el),
        elWidth = parseInt(elCSS.width) - parseInt(elCSS.paddingLeft) - parseInt(elCSS.paddingRight) - parseInt(elCSS.borderLeftWidth) - parseInt(elCSS.borderRightWidth),
        child1 = getChild(el, 0, options),
        child2 = getChild(el, 1, options),
        firstChildCSS = child1 && css(child1),
        secondChildCSS = child2 && css(child2),
        firstChildWidth = firstChildCSS && parseInt(firstChildCSS.marginLeft) + parseInt(firstChildCSS.marginRight) + getRect(child1).width,
        secondChildWidth = secondChildCSS && parseInt(secondChildCSS.marginLeft) + parseInt(secondChildCSS.marginRight) + getRect(child2).width;

    if (elCSS.display === 'flex') {
      return elCSS.flexDirection === 'column' || elCSS.flexDirection === 'column-reverse' ? 'vertical' : 'horizontal';
    }

    if (elCSS.display === 'grid') {
      return elCSS.gridTemplateColumns.split(' ').length <= 1 ? 'vertical' : 'horizontal';
    }

    if (child1 && firstChildCSS["float"] && firstChildCSS["float"] !== 'none') {
      var touchingSideChild2 = firstChildCSS["float"] === 'left' ? 'left' : 'right';
      return child2 && (secondChildCSS.clear === 'both' || secondChildCSS.clear === touchingSideChild2) ? 'vertical' : 'horizontal';
    }

    return child1 && (firstChildCSS.display === 'block' || firstChildCSS.display === 'flex' || firstChildCSS.display === 'table' || firstChildCSS.display === 'grid' || firstChildWidth >= elWidth && elCSS[CSSFloatProperty] === 'none' || child2 && elCSS[CSSFloatProperty] === 'none' && firstChildWidth + secondChildWidth > elWidth) ? 'vertical' : 'horizontal';
  },
      _dragElInRowColumn = function _dragElInRowColumn(dragRect, targetRect, vertical) {
    var dragElS1Opp = vertical ? dragRect.left : dragRect.top,
        dragElS2Opp = vertical ? dragRect.right : dragRect.bottom,
        dragElOppLength = vertical ? dragRect.width : dragRect.height,
        targetS1Opp = vertical ? targetRect.left : targetRect.top,
        targetS2Opp = vertical ? targetRect.right : targetRect.bottom,
        targetOppLength = vertical ? targetRect.width : targetRect.height;
    return dragElS1Opp === targetS1Opp || dragElS2Opp === targetS2Opp || dragElS1Opp + dragElOppLength / 2 === targetS1Opp + targetOppLength / 2;
  },

  /**
   * Detects first nearest empty sortable to X and Y position using emptyInsertThreshold.
   * @param  {Number} x      X position
   * @param  {Number} y      Y position
   * @return {HTMLElement}   Element of the first found nearest Sortable
   */
  _detectNearestEmptySortable = function _detectNearestEmptySortable(x, y) {
    var ret;
    sortables.some(function (sortable) {
      if (lastChild(sortable)) return;
      var rect = getRect(sortable),
          threshold = sortable[expando].options.emptyInsertThreshold,
          insideHorizontally = x >= rect.left - threshold && x <= rect.right + threshold,
          insideVertically = y >= rect.top - threshold && y <= rect.bottom + threshold;

      if (threshold && insideHorizontally && insideVertically) {
        return ret = sortable;
      }
    });
    return ret;
  },
      _prepareGroup = function _prepareGroup(options) {
    function toFn(value, pull) {
      return function (to, from, dragEl, evt) {
        var sameGroup = to.options.group.name && from.options.group.name && to.options.group.name === from.options.group.name;

        if (value == null && (pull || sameGroup)) {
          // Default pull value
          // Default pull and put value if same group
          return true;
        } else if (value == null || value === false) {
          return false;
        } else if (pull && value === 'clone') {
          return value;
        } else if (typeof value === 'function') {
          return toFn(value(to, from, dragEl, evt), pull)(to, from, dragEl, evt);
        } else {
          var otherGroup = (pull ? to : from).options.group.name;
          return value === true || typeof value === 'string' && value === otherGroup || value.join && value.indexOf(otherGroup) > -1;
        }
      };
    }

    var group = {};
    var originalGroup = options.group;

    if (!originalGroup || _typeof(originalGroup) != 'object') {
      originalGroup = {
        name: originalGroup
      };
    }

    group.name = originalGroup.name;
    group.checkPull = toFn(originalGroup.pull, true);
    group.checkPut = toFn(originalGroup.put);
    group.revertClone = originalGroup.revertClone;
    options.group = group;
  },
      _hideGhostForTarget = function _hideGhostForTarget() {
    if (!supportCssPointerEvents && ghostEl) {
      css(ghostEl, 'display', 'none');
    }
  },
      _unhideGhostForTarget = function _unhideGhostForTarget() {
    if (!supportCssPointerEvents && ghostEl) {
      css(ghostEl, 'display', '');
    }
  }; // #1184 fix - Prevent click event on fallback if dragged but item not changed position


  if (documentExists) {
    document.addEventListener('click', function (evt) {
      if (ignoreNextClick) {
        evt.preventDefault();
        evt.stopPropagation && evt.stopPropagation();
        evt.stopImmediatePropagation && evt.stopImmediatePropagation();
        ignoreNextClick = false;
        return false;
      }
    }, true);
  }

  var nearestEmptyInsertDetectEvent = function nearestEmptyInsertDetectEvent(evt) {
    if (dragEl) {
      evt = evt.touches ? evt.touches[0] : evt;

      var nearest = _detectNearestEmptySortable(evt.clientX, evt.clientY);

      if (nearest) {
        // Create imitation event
        var event = {};

        for (var i in evt) {
          if (evt.hasOwnProperty(i)) {
            event[i] = evt[i];
          }
        }

        event.target = event.rootEl = nearest;
        event.preventDefault = void 0;
        event.stopPropagation = void 0;

        nearest[expando]._onDragOver(event);
      }
    }
  };

  var _checkOutsideTargetEl = function _checkOutsideTargetEl(evt) {
    if (dragEl) {
      dragEl.parentNode[expando]._isOutsideThisEl(evt.target);
    }
  };
  /**
   * @class  Sortable
   * @param  {HTMLElement}  el
   * @param  {Object}       [options]
   */


  function Sortable(el, options) {
    if (!(el && el.nodeType && el.nodeType === 1)) {
      throw "Sortable: `el` must be an HTMLElement, not ".concat({}.toString.call(el));
    }

    this.el = el; // root element

    this.options = options = _extends({}, options); // Export instance

    el[expando] = this;
    var defaults = {
      group: null,
      sort: true,
      disabled: false,
      store: null,
      handle: null,
      draggable: /^[uo]l$/i.test(el.nodeName) ? '>li' : '>*',
      swapThreshold: 1,
      // percentage; 0 <= x <= 1
      invertSwap: false,
      // invert always
      invertedSwapThreshold: null,
      // will be set to same as swapThreshold if default
      removeCloneOnHide: true,
      direction: function direction() {
        return _detectDirection(el, this.options);
      },
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      dragClass: 'sortable-drag',
      ignore: 'a, img',
      filter: null,
      preventOnFilter: true,
      animation: 0,
      easing: null,
      setData: function setData(dataTransfer, dragEl) {
        dataTransfer.setData('Text', dragEl.textContent);
      },
      dropBubble: false,
      dragoverBubble: false,
      dataIdAttr: 'data-id',
      delay: 0,
      delayOnTouchOnly: false,
      touchStartThreshold: (Number.parseInt ? Number : window).parseInt(window.devicePixelRatio, 10) || 1,
      forceFallback: false,
      fallbackClass: 'sortable-fallback',
      fallbackOnBody: false,
      fallbackTolerance: 0,
      fallbackOffset: {
        x: 0,
        y: 0
      },
      supportPointer: Sortable.supportPointer !== false && 'PointerEvent' in window && !Safari,
      emptyInsertThreshold: 5
    };
    PluginManager.initializePlugins(this, el, defaults); // Set default options

    for (var name in defaults) {
      !(name in options) && (options[name] = defaults[name]);
    }

    _prepareGroup(options); // Bind all private methods


    for (var fn in this) {
      if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
        this[fn] = this[fn].bind(this);
      }
    } // Setup drag mode


    this.nativeDraggable = options.forceFallback ? false : supportDraggable;

    if (this.nativeDraggable) {
      // Touch start threshold cannot be greater than the native dragstart threshold
      this.options.touchStartThreshold = 1;
    } // Bind events


    if (options.supportPointer) {
      on(el, 'pointerdown', this._onTapStart);
    } else {
      on(el, 'mousedown', this._onTapStart);
      on(el, 'touchstart', this._onTapStart);
    }

    if (this.nativeDraggable) {
      on(el, 'dragover', this);
      on(el, 'dragenter', this);
    }

    sortables.push(this.el); // Restore sorting

    options.store && options.store.get && this.sort(options.store.get(this) || []); // Add animation state manager

    _extends(this, AnimationStateManager());
  }

  Sortable.prototype =
  /** @lends Sortable.prototype */
  {
    constructor: Sortable,
    _isOutsideThisEl: function _isOutsideThisEl(target) {
      if (!this.el.contains(target) && target !== this.el) {
        lastTarget = null;
      }
    },
    _getDirection: function _getDirection(evt, target) {
      return typeof this.options.direction === 'function' ? this.options.direction.call(this, evt, target, dragEl) : this.options.direction;
    },
    _onTapStart: function _onTapStart(
    /** Event|TouchEvent */
    evt) {
      if (!evt.cancelable) return;

      var _this = this,
          el = this.el,
          options = this.options,
          preventOnFilter = options.preventOnFilter,
          type = evt.type,
          touch = evt.touches && evt.touches[0] || evt.pointerType && evt.pointerType === 'touch' && evt,
          target = (touch || evt).target,
          originalTarget = evt.target.shadowRoot && (evt.path && evt.path[0] || evt.composedPath && evt.composedPath()[0]) || target,
          filter = options.filter;

      _saveInputCheckedState(el); // Don't trigger start event when an element is been dragged, otherwise the evt.oldindex always wrong when set option.group.


      if (dragEl) {
        return;
      }

      if (/mousedown|pointerdown/.test(type) && evt.button !== 0 || options.disabled) {
        return; // only left button and enabled
      } // cancel dnd if original target is content editable


      if (originalTarget.isContentEditable) {
        return;
      } // Safari ignores further event handling after mousedown


      if (!this.nativeDraggable && Safari && target && target.tagName.toUpperCase() === 'SELECT') {
        return;
      }

      target = closest(target, options.draggable, el, false);

      if (target && target.animated) {
        return;
      }

      if (lastDownEl === target) {
        // Ignoring duplicate `down`
        return;
      } // Get the index of the dragged element within its parent


      oldIndex = index(target);
      oldDraggableIndex = index(target, options.draggable); // Check filter

      if (typeof filter === 'function') {
        if (filter.call(this, evt, target, this)) {
          _dispatchEvent({
            sortable: _this,
            rootEl: originalTarget,
            name: 'filter',
            targetEl: target,
            toEl: el,
            fromEl: el
          });

          pluginEvent('filter', _this, {
            evt: evt
          });
          preventOnFilter && evt.cancelable && evt.preventDefault();
          return; // cancel dnd
        }
      } else if (filter) {
        filter = filter.split(',').some(function (criteria) {
          criteria = closest(originalTarget, criteria.trim(), el, false);

          if (criteria) {
            _dispatchEvent({
              sortable: _this,
              rootEl: criteria,
              name: 'filter',
              targetEl: target,
              fromEl: el,
              toEl: el
            });

            pluginEvent('filter', _this, {
              evt: evt
            });
            return true;
          }
        });

        if (filter) {
          preventOnFilter && evt.cancelable && evt.preventDefault();
          return; // cancel dnd
        }
      }

      if (options.handle && !closest(originalTarget, options.handle, el, false)) {
        return;
      } // Prepare `dragstart`


      this._prepareDragStart(evt, touch, target);
    },
    _prepareDragStart: function _prepareDragStart(
    /** Event */
    evt,
    /** Touch */
    touch,
    /** HTMLElement */
    target) {
      var _this = this,
          el = _this.el,
          options = _this.options,
          ownerDocument = el.ownerDocument,
          dragStartFn;

      if (target && !dragEl && target.parentNode === el) {
        var dragRect = getRect(target);
        rootEl = el;
        dragEl = target;
        parentEl = dragEl.parentNode;
        nextEl = dragEl.nextSibling;
        lastDownEl = target;
        activeGroup = options.group;
        Sortable.dragged = dragEl;
        tapEvt = {
          target: dragEl,
          clientX: (touch || evt).clientX,
          clientY: (touch || evt).clientY
        };
        tapDistanceLeft = tapEvt.clientX - dragRect.left;
        tapDistanceTop = tapEvt.clientY - dragRect.top;
        this._lastX = (touch || evt).clientX;
        this._lastY = (touch || evt).clientY;
        dragEl.style['will-change'] = 'all';

        dragStartFn = function dragStartFn() {
          pluginEvent('delayEnded', _this, {
            evt: evt
          });

          if (Sortable.eventCanceled) {
            _this._onDrop();

            return;
          } // Delayed drag has been triggered
          // we can re-enable the events: touchmove/mousemove


          _this._disableDelayedDragEvents();

          if (!FireFox && _this.nativeDraggable) {
            dragEl.draggable = true;
          } // Bind the events: dragstart/dragend


          _this._triggerDragStart(evt, touch); // Drag start event


          _dispatchEvent({
            sortable: _this,
            name: 'choose',
            originalEvent: evt
          }); // Chosen item


          toggleClass(dragEl, options.chosenClass, true);
        }; // Disable "draggable"


        options.ignore.split(',').forEach(function (criteria) {
          find(dragEl, criteria.trim(), _disableDraggable);
        });
        on(ownerDocument, 'dragover', nearestEmptyInsertDetectEvent);
        on(ownerDocument, 'mousemove', nearestEmptyInsertDetectEvent);
        on(ownerDocument, 'touchmove', nearestEmptyInsertDetectEvent);
        on(ownerDocument, 'mouseup', _this._onDrop);
        on(ownerDocument, 'touchend', _this._onDrop);
        on(ownerDocument, 'touchcancel', _this._onDrop); // Make dragEl draggable (must be before delay for FireFox)

        if (FireFox && this.nativeDraggable) {
          this.options.touchStartThreshold = 4;
          dragEl.draggable = true;
        }

        pluginEvent('delayStart', this, {
          evt: evt
        }); // Delay is impossible for native DnD in Edge or IE

        if (options.delay && (!options.delayOnTouchOnly || touch) && (!this.nativeDraggable || !(Edge || IE11OrLess))) {
          if (Sortable.eventCanceled) {
            this._onDrop();

            return;
          } // If the user moves the pointer or let go the click or touch
          // before the delay has been reached:
          // disable the delayed drag


          on(ownerDocument, 'mouseup', _this._disableDelayedDrag);
          on(ownerDocument, 'touchend', _this._disableDelayedDrag);
          on(ownerDocument, 'touchcancel', _this._disableDelayedDrag);
          on(ownerDocument, 'mousemove', _this._delayedDragTouchMoveHandler);
          on(ownerDocument, 'touchmove', _this._delayedDragTouchMoveHandler);
          options.supportPointer && on(ownerDocument, 'pointermove', _this._delayedDragTouchMoveHandler);
          _this._dragStartTimer = setTimeout(dragStartFn, options.delay);
        } else {
          dragStartFn();
        }
      }
    },
    _delayedDragTouchMoveHandler: function _delayedDragTouchMoveHandler(
    /** TouchEvent|PointerEvent **/
    e) {
      var touch = e.touches ? e.touches[0] : e;

      if (Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) >= Math.floor(this.options.touchStartThreshold / (this.nativeDraggable && window.devicePixelRatio || 1))) {
        this._disableDelayedDrag();
      }
    },
    _disableDelayedDrag: function _disableDelayedDrag() {
      dragEl && _disableDraggable(dragEl);
      clearTimeout(this._dragStartTimer);

      this._disableDelayedDragEvents();
    },
    _disableDelayedDragEvents: function _disableDelayedDragEvents() {
      var ownerDocument = this.el.ownerDocument;
      off(ownerDocument, 'mouseup', this._disableDelayedDrag);
      off(ownerDocument, 'touchend', this._disableDelayedDrag);
      off(ownerDocument, 'touchcancel', this._disableDelayedDrag);
      off(ownerDocument, 'mousemove', this._delayedDragTouchMoveHandler);
      off(ownerDocument, 'touchmove', this._delayedDragTouchMoveHandler);
      off(ownerDocument, 'pointermove', this._delayedDragTouchMoveHandler);
    },
    _triggerDragStart: function _triggerDragStart(
    /** Event */
    evt,
    /** Touch */
    touch) {
      touch = touch || evt.pointerType == 'touch' && evt;

      if (!this.nativeDraggable || touch) {
        if (this.options.supportPointer) {
          on(document, 'pointermove', this._onTouchMove);
        } else if (touch) {
          on(document, 'touchmove', this._onTouchMove);
        } else {
          on(document, 'mousemove', this._onTouchMove);
        }
      } else {
        on(dragEl, 'dragend', this);
        on(rootEl, 'dragstart', this._onDragStart);
      }

      try {
        if (document.selection) {
          // Timeout neccessary for IE9
          _nextTick(function () {
            document.selection.empty();
          });
        } else {
          window.getSelection().removeAllRanges();
        }
      } catch (err) {}
    },
    _dragStarted: function _dragStarted(fallback, evt) {

      awaitingDragStarted = false;

      if (rootEl && dragEl) {
        pluginEvent('dragStarted', this, {
          evt: evt
        });

        if (this.nativeDraggable) {
          on(document, 'dragover', _checkOutsideTargetEl);
        }

        var options = this.options; // Apply effect

        !fallback && toggleClass(dragEl, options.dragClass, false);
        toggleClass(dragEl, options.ghostClass, true);
        Sortable.active = this;
        fallback && this._appendGhost(); // Drag start event

        _dispatchEvent({
          sortable: this,
          name: 'start',
          originalEvent: evt
        });
      } else {
        this._nulling();
      }
    },
    _emulateDragOver: function _emulateDragOver() {
      if (touchEvt) {
        this._lastX = touchEvt.clientX;
        this._lastY = touchEvt.clientY;

        _hideGhostForTarget();

        var target = document.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
        var parent = target;

        while (target && target.shadowRoot) {
          target = target.shadowRoot.elementFromPoint(touchEvt.clientX, touchEvt.clientY);
          if (target === parent) break;
          parent = target;
        }

        dragEl.parentNode[expando]._isOutsideThisEl(target);

        if (parent) {
          do {
            if (parent[expando]) {
              var inserted = void 0;
              inserted = parent[expando]._onDragOver({
                clientX: touchEvt.clientX,
                clientY: touchEvt.clientY,
                target: target,
                rootEl: parent
              });

              if (inserted && !this.options.dragoverBubble) {
                break;
              }
            }

            target = parent; // store last element
          }
          /* jshint boss:true */
          while (parent = parent.parentNode);
        }

        _unhideGhostForTarget();
      }
    },
    _onTouchMove: function _onTouchMove(
    /**TouchEvent*/
    evt) {
      if (tapEvt) {
        var options = this.options,
            fallbackTolerance = options.fallbackTolerance,
            fallbackOffset = options.fallbackOffset,
            touch = evt.touches ? evt.touches[0] : evt,
            ghostMatrix = ghostEl && matrix(ghostEl, true),
            scaleX = ghostEl && ghostMatrix && ghostMatrix.a,
            scaleY = ghostEl && ghostMatrix && ghostMatrix.d,
            relativeScrollOffset = PositionGhostAbsolutely && ghostRelativeParent && getRelativeScrollOffset(ghostRelativeParent),
            dx = (touch.clientX - tapEvt.clientX + fallbackOffset.x) / (scaleX || 1) + (relativeScrollOffset ? relativeScrollOffset[0] - ghostRelativeParentInitialScroll[0] : 0) / (scaleX || 1),
            dy = (touch.clientY - tapEvt.clientY + fallbackOffset.y) / (scaleY || 1) + (relativeScrollOffset ? relativeScrollOffset[1] - ghostRelativeParentInitialScroll[1] : 0) / (scaleY || 1); // only set the status to dragging, when we are actually dragging

        if (!Sortable.active && !awaitingDragStarted) {
          if (fallbackTolerance && Math.max(Math.abs(touch.clientX - this._lastX), Math.abs(touch.clientY - this._lastY)) < fallbackTolerance) {
            return;
          }

          this._onDragStart(evt, true);
        }

        if (ghostEl) {
          if (ghostMatrix) {
            ghostMatrix.e += dx - (lastDx || 0);
            ghostMatrix.f += dy - (lastDy || 0);
          } else {
            ghostMatrix = {
              a: 1,
              b: 0,
              c: 0,
              d: 1,
              e: dx,
              f: dy
            };
          }

          var cssMatrix = "matrix(".concat(ghostMatrix.a, ",").concat(ghostMatrix.b, ",").concat(ghostMatrix.c, ",").concat(ghostMatrix.d, ",").concat(ghostMatrix.e, ",").concat(ghostMatrix.f, ")");
          css(ghostEl, 'webkitTransform', cssMatrix);
          css(ghostEl, 'mozTransform', cssMatrix);
          css(ghostEl, 'msTransform', cssMatrix);
          css(ghostEl, 'transform', cssMatrix);
          lastDx = dx;
          lastDy = dy;
          touchEvt = touch;
        }

        evt.cancelable && evt.preventDefault();
      }
    },
    _appendGhost: function _appendGhost() {
      // Bug if using scale(): https://stackoverflow.com/questions/2637058
      // Not being adjusted for
      if (!ghostEl) {
        var container = this.options.fallbackOnBody ? document.body : rootEl,
            rect = getRect(dragEl, true, PositionGhostAbsolutely, true, container),
            options = this.options; // Position absolutely

        if (PositionGhostAbsolutely) {
          // Get relatively positioned parent
          ghostRelativeParent = container;

          while (css(ghostRelativeParent, 'position') === 'static' && css(ghostRelativeParent, 'transform') === 'none' && ghostRelativeParent !== document) {
            ghostRelativeParent = ghostRelativeParent.parentNode;
          }

          if (ghostRelativeParent !== document.body && ghostRelativeParent !== document.documentElement) {
            if (ghostRelativeParent === document) ghostRelativeParent = getWindowScrollingElement();
            rect.top += ghostRelativeParent.scrollTop;
            rect.left += ghostRelativeParent.scrollLeft;
          } else {
            ghostRelativeParent = getWindowScrollingElement();
          }

          ghostRelativeParentInitialScroll = getRelativeScrollOffset(ghostRelativeParent);
        }

        ghostEl = dragEl.cloneNode(true);
        toggleClass(ghostEl, options.ghostClass, false);
        toggleClass(ghostEl, options.fallbackClass, true);
        toggleClass(ghostEl, options.dragClass, true);
        css(ghostEl, 'transition', '');
        css(ghostEl, 'transform', '');
        css(ghostEl, 'box-sizing', 'border-box');
        css(ghostEl, 'margin', 0);
        css(ghostEl, 'top', rect.top);
        css(ghostEl, 'left', rect.left);
        css(ghostEl, 'width', rect.width);
        css(ghostEl, 'height', rect.height);
        css(ghostEl, 'opacity', '0.8');
        css(ghostEl, 'position', PositionGhostAbsolutely ? 'absolute' : 'fixed');
        css(ghostEl, 'zIndex', '100000');
        css(ghostEl, 'pointerEvents', 'none');
        Sortable.ghost = ghostEl;
        container.appendChild(ghostEl); // Set transform-origin

        css(ghostEl, 'transform-origin', tapDistanceLeft / parseInt(ghostEl.style.width) * 100 + '% ' + tapDistanceTop / parseInt(ghostEl.style.height) * 100 + '%');
      }
    },
    _onDragStart: function _onDragStart(
    /**Event*/
    evt,
    /**boolean*/
    fallback) {
      var _this = this;

      var dataTransfer = evt.dataTransfer;
      var options = _this.options;
      pluginEvent('dragStart', this, {
        evt: evt
      });

      if (Sortable.eventCanceled) {
        this._onDrop();

        return;
      }

      pluginEvent('setupClone', this);

      if (!Sortable.eventCanceled) {
        cloneEl = clone(dragEl);
        cloneEl.draggable = false;
        cloneEl.style['will-change'] = '';

        this._hideClone();

        toggleClass(cloneEl, this.options.chosenClass, false);
        Sortable.clone = cloneEl;
      } // #1143: IFrame support workaround


      _this.cloneId = _nextTick(function () {
        pluginEvent('clone', _this);
        if (Sortable.eventCanceled) return;

        if (!_this.options.removeCloneOnHide) {
          rootEl.insertBefore(cloneEl, dragEl);
        }

        _this._hideClone();

        _dispatchEvent({
          sortable: _this,
          name: 'clone'
        });
      });
      !fallback && toggleClass(dragEl, options.dragClass, true); // Set proper drop events

      if (fallback) {
        ignoreNextClick = true;
        _this._loopId = setInterval(_this._emulateDragOver, 50);
      } else {
        // Undo what was set in _prepareDragStart before drag started
        off(document, 'mouseup', _this._onDrop);
        off(document, 'touchend', _this._onDrop);
        off(document, 'touchcancel', _this._onDrop);

        if (dataTransfer) {
          dataTransfer.effectAllowed = 'move';
          options.setData && options.setData.call(_this, dataTransfer, dragEl);
        }

        on(document, 'drop', _this); // #1276 fix:

        css(dragEl, 'transform', 'translateZ(0)');
      }

      awaitingDragStarted = true;
      _this._dragStartId = _nextTick(_this._dragStarted.bind(_this, fallback, evt));
      on(document, 'selectstart', _this);
      moved = true;

      if (Safari) {
        css(document.body, 'user-select', 'none');
      }
    },
    // Returns true - if no further action is needed (either inserted or another condition)
    _onDragOver: function _onDragOver(
    /**Event*/
    evt) {
      var el = this.el,
          target = evt.target,
          dragRect,
          targetRect,
          revert,
          options = this.options,
          group = options.group,
          activeSortable = Sortable.active,
          isOwner = activeGroup === group,
          canSort = options.sort,
          fromSortable = putSortable || activeSortable,
          vertical,
          _this = this,
          completedFired = false;

      if (_silent) return;

      function dragOverEvent(name, extra) {
        pluginEvent(name, _this, _objectSpread({
          evt: evt,
          isOwner: isOwner,
          axis: vertical ? 'vertical' : 'horizontal',
          revert: revert,
          dragRect: dragRect,
          targetRect: targetRect,
          canSort: canSort,
          fromSortable: fromSortable,
          target: target,
          completed: completed,
          onMove: function onMove(target, after) {
            return _onMove(rootEl, el, dragEl, dragRect, target, getRect(target), evt, after);
          },
          changed: changed
        }, extra));
      } // Capture animation state


      function capture() {
        dragOverEvent('dragOverAnimationCapture');

        _this.captureAnimationState();

        if (_this !== fromSortable) {
          fromSortable.captureAnimationState();
        }
      } // Return invocation when dragEl is inserted (or completed)


      function completed(insertion) {
        dragOverEvent('dragOverCompleted', {
          insertion: insertion
        });

        if (insertion) {
          // Clones must be hidden before folding animation to capture dragRectAbsolute properly
          if (isOwner) {
            activeSortable._hideClone();
          } else {
            activeSortable._showClone(_this);
          }

          if (_this !== fromSortable) {
            // Set ghost class to new sortable's ghost class
            toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : activeSortable.options.ghostClass, false);
            toggleClass(dragEl, options.ghostClass, true);
          }

          if (putSortable !== _this && _this !== Sortable.active) {
            putSortable = _this;
          } else if (_this === Sortable.active && putSortable) {
            putSortable = null;
          } // Animation


          if (fromSortable === _this) {
            _this._ignoreWhileAnimating = target;
          }

          _this.animateAll(function () {
            dragOverEvent('dragOverAnimationComplete');
            _this._ignoreWhileAnimating = null;
          });

          if (_this !== fromSortable) {
            fromSortable.animateAll();
            fromSortable._ignoreWhileAnimating = null;
          }
        } // Null lastTarget if it is not inside a previously swapped element


        if (target === dragEl && !dragEl.animated || target === el && !target.animated) {
          lastTarget = null;
        } // no bubbling and not fallback


        if (!options.dragoverBubble && !evt.rootEl && target !== document) {
          dragEl.parentNode[expando]._isOutsideThisEl(evt.target); // Do not detect for empty insert if already inserted


          !insertion && nearestEmptyInsertDetectEvent(evt);
        }

        !options.dragoverBubble && evt.stopPropagation && evt.stopPropagation();
        return completedFired = true;
      } // Call when dragEl has been inserted


      function changed() {
        newIndex = index(dragEl);
        newDraggableIndex = index(dragEl, options.draggable);

        _dispatchEvent({
          sortable: _this,
          name: 'change',
          toEl: el,
          newIndex: newIndex,
          newDraggableIndex: newDraggableIndex,
          originalEvent: evt
        });
      }

      if (evt.preventDefault !== void 0) {
        evt.cancelable && evt.preventDefault();
      }

      target = closest(target, options.draggable, el, true);
      dragOverEvent('dragOver');
      if (Sortable.eventCanceled) return completedFired;

      if (dragEl.contains(evt.target) || target.animated && target.animatingX && target.animatingY || _this._ignoreWhileAnimating === target) {
        return completed(false);
      }

      ignoreNextClick = false;

      if (activeSortable && !options.disabled && (isOwner ? canSort || (revert = !rootEl.contains(dragEl)) // Reverting item into the original list
      : putSortable === this || (this.lastPutMode = activeGroup.checkPull(this, activeSortable, dragEl, evt)) && group.checkPut(this, activeSortable, dragEl, evt))) {
        vertical = this._getDirection(evt, target) === 'vertical';
        dragRect = getRect(dragEl);
        dragOverEvent('dragOverValid');
        if (Sortable.eventCanceled) return completedFired;

        if (revert) {
          parentEl = rootEl; // actualization

          capture();

          this._hideClone();

          dragOverEvent('revert');

          if (!Sortable.eventCanceled) {
            if (nextEl) {
              rootEl.insertBefore(dragEl, nextEl);
            } else {
              rootEl.appendChild(dragEl);
            }
          }

          return completed(true);
        }

        var elLastChild = lastChild(el, options.draggable);

        if (!elLastChild || _ghostIsLast(evt, vertical, this) && !elLastChild.animated) {
          // If already at end of list: Do not insert
          if (elLastChild === dragEl) {
            return completed(false);
          } // assign target only if condition is true


          if (elLastChild && el === evt.target) {
            target = elLastChild;
          }

          if (target) {
            targetRect = getRect(target);
          }

          if (_onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, !!target) !== false) {
            capture();
            el.appendChild(dragEl);
            parentEl = el; // actualization

            changed();
            return completed(true);
          }
        } else if (target.parentNode === el) {
          targetRect = getRect(target);
          var direction = 0,
              targetBeforeFirstSwap,
              differentLevel = dragEl.parentNode !== el,
              differentRowCol = !_dragElInRowColumn(dragEl.animated && dragEl.toRect || dragRect, target.animated && target.toRect || targetRect, vertical),
              side1 = vertical ? 'top' : 'left',
              scrolledPastTop = isScrolledPast(target, 'top', 'top') || isScrolledPast(dragEl, 'top', 'top'),
              scrollBefore = scrolledPastTop ? scrolledPastTop.scrollTop : void 0;

          if (lastTarget !== target) {
            targetBeforeFirstSwap = targetRect[side1];
            pastFirstInvertThresh = false;
            isCircumstantialInvert = !differentRowCol && options.invertSwap || differentLevel;
          }

          direction = _getSwapDirection(evt, target, targetRect, vertical, differentRowCol ? 1 : options.swapThreshold, options.invertedSwapThreshold == null ? options.swapThreshold : options.invertedSwapThreshold, isCircumstantialInvert, lastTarget === target);
          var sibling;

          if (direction !== 0) {
            // Check if target is beside dragEl in respective direction (ignoring hidden elements)
            var dragIndex = index(dragEl);

            do {
              dragIndex -= direction;
              sibling = parentEl.children[dragIndex];
            } while (sibling && (css(sibling, 'display') === 'none' || sibling === ghostEl));
          } // If dragEl is already beside target: Do not insert


          if (direction === 0 || sibling === target) {
            return completed(false);
          }

          lastTarget = target;
          lastDirection = direction;
          var nextSibling = target.nextElementSibling,
              after = false;
          after = direction === 1;

          var moveVector = _onMove(rootEl, el, dragEl, dragRect, target, targetRect, evt, after);

          if (moveVector !== false) {
            if (moveVector === 1 || moveVector === -1) {
              after = moveVector === 1;
            }

            _silent = true;
            setTimeout(_unsilent, 30);
            capture();

            if (after && !nextSibling) {
              el.appendChild(dragEl);
            } else {
              target.parentNode.insertBefore(dragEl, after ? nextSibling : target);
            } // Undo chrome's scroll adjustment (has no effect on other browsers)


            if (scrolledPastTop) {
              scrollBy(scrolledPastTop, 0, scrollBefore - scrolledPastTop.scrollTop);
            }

            parentEl = dragEl.parentNode; // actualization
            // must be done before animation

            if (targetBeforeFirstSwap !== undefined && !isCircumstantialInvert) {
              targetMoveDistance = Math.abs(targetBeforeFirstSwap - getRect(target)[side1]);
            }

            changed();
            return completed(true);
          }
        }

        if (el.contains(dragEl)) {
          return completed(false);
        }
      }

      return false;
    },
    _ignoreWhileAnimating: null,
    _offMoveEvents: function _offMoveEvents() {
      off(document, 'mousemove', this._onTouchMove);
      off(document, 'touchmove', this._onTouchMove);
      off(document, 'pointermove', this._onTouchMove);
      off(document, 'dragover', nearestEmptyInsertDetectEvent);
      off(document, 'mousemove', nearestEmptyInsertDetectEvent);
      off(document, 'touchmove', nearestEmptyInsertDetectEvent);
    },
    _offUpEvents: function _offUpEvents() {
      var ownerDocument = this.el.ownerDocument;
      off(ownerDocument, 'mouseup', this._onDrop);
      off(ownerDocument, 'touchend', this._onDrop);
      off(ownerDocument, 'pointerup', this._onDrop);
      off(ownerDocument, 'touchcancel', this._onDrop);
      off(document, 'selectstart', this);
    },
    _onDrop: function _onDrop(
    /**Event*/
    evt) {
      var el = this.el,
          options = this.options; // Get the index of the dragged element within its parent

      newIndex = index(dragEl);
      newDraggableIndex = index(dragEl, options.draggable);
      pluginEvent('drop', this, {
        evt: evt
      });
      parentEl = dragEl && dragEl.parentNode; // Get again after plugin event

      newIndex = index(dragEl);
      newDraggableIndex = index(dragEl, options.draggable);

      if (Sortable.eventCanceled) {
        this._nulling();

        return;
      }

      awaitingDragStarted = false;
      isCircumstantialInvert = false;
      pastFirstInvertThresh = false;
      clearInterval(this._loopId);
      clearTimeout(this._dragStartTimer);

      _cancelNextTick(this.cloneId);

      _cancelNextTick(this._dragStartId); // Unbind events


      if (this.nativeDraggable) {
        off(document, 'drop', this);
        off(el, 'dragstart', this._onDragStart);
      }

      this._offMoveEvents();

      this._offUpEvents();

      if (Safari) {
        css(document.body, 'user-select', '');
      }

      css(dragEl, 'transform', '');

      if (evt) {
        if (moved) {
          evt.cancelable && evt.preventDefault();
          !options.dropBubble && evt.stopPropagation();
        }

        ghostEl && ghostEl.parentNode && ghostEl.parentNode.removeChild(ghostEl);

        if (rootEl === parentEl || putSortable && putSortable.lastPutMode !== 'clone') {
          // Remove clone(s)
          cloneEl && cloneEl.parentNode && cloneEl.parentNode.removeChild(cloneEl);
        }

        if (dragEl) {
          if (this.nativeDraggable) {
            off(dragEl, 'dragend', this);
          }

          _disableDraggable(dragEl);

          dragEl.style['will-change'] = ''; // Remove classes
          // ghostClass is added in dragStarted

          if (moved && !awaitingDragStarted) {
            toggleClass(dragEl, putSortable ? putSortable.options.ghostClass : this.options.ghostClass, false);
          }

          toggleClass(dragEl, this.options.chosenClass, false); // Drag stop event

          _dispatchEvent({
            sortable: this,
            name: 'unchoose',
            toEl: parentEl,
            newIndex: null,
            newDraggableIndex: null,
            originalEvent: evt
          });

          if (rootEl !== parentEl) {
            if (newIndex >= 0) {
              // Add event
              _dispatchEvent({
                rootEl: parentEl,
                name: 'add',
                toEl: parentEl,
                fromEl: rootEl,
                originalEvent: evt
              }); // Remove event


              _dispatchEvent({
                sortable: this,
                name: 'remove',
                toEl: parentEl,
                originalEvent: evt
              }); // drag from one list and drop into another


              _dispatchEvent({
                rootEl: parentEl,
                name: 'sort',
                toEl: parentEl,
                fromEl: rootEl,
                originalEvent: evt
              });

              _dispatchEvent({
                sortable: this,
                name: 'sort',
                toEl: parentEl,
                originalEvent: evt
              });
            }

            putSortable && putSortable.save();
          } else {
            if (newIndex !== oldIndex) {
              if (newIndex >= 0) {
                // drag & drop within the same list
                _dispatchEvent({
                  sortable: this,
                  name: 'update',
                  toEl: parentEl,
                  originalEvent: evt
                });

                _dispatchEvent({
                  sortable: this,
                  name: 'sort',
                  toEl: parentEl,
                  originalEvent: evt
                });
              }
            }
          }

          if (Sortable.active) {
            /* jshint eqnull:true */
            if (newIndex == null || newIndex === -1) {
              newIndex = oldIndex;
              newDraggableIndex = oldDraggableIndex;
            }

            _dispatchEvent({
              sortable: this,
              name: 'end',
              toEl: parentEl,
              originalEvent: evt
            }); // Save sorting


            this.save();
          }
        }
      }

      this._nulling();
    },
    _nulling: function _nulling() {
      pluginEvent('nulling', this);
      rootEl = dragEl = parentEl = ghostEl = nextEl = cloneEl = lastDownEl = cloneHidden = tapEvt = touchEvt = moved = newIndex = newDraggableIndex = oldIndex = oldDraggableIndex = lastTarget = lastDirection = putSortable = activeGroup = Sortable.dragged = Sortable.ghost = Sortable.clone = Sortable.active = null;
      savedInputChecked.forEach(function (el) {
        el.checked = true;
      });
      savedInputChecked.length = lastDx = lastDy = 0;
    },
    handleEvent: function handleEvent(
    /**Event*/
    evt) {
      switch (evt.type) {
        case 'drop':
        case 'dragend':
          this._onDrop(evt);

          break;

        case 'dragenter':
        case 'dragover':
          if (dragEl) {
            this._onDragOver(evt);

            _globalDragOver(evt);
          }

          break;

        case 'selectstart':
          evt.preventDefault();
          break;
      }
    },

    /**
     * Serializes the item into an array of string.
     * @returns {String[]}
     */
    toArray: function toArray() {
      var order = [],
          el,
          children = this.el.children,
          i = 0,
          n = children.length,
          options = this.options;

      for (; i < n; i++) {
        el = children[i];

        if (closest(el, options.draggable, this.el, false)) {
          order.push(el.getAttribute(options.dataIdAttr) || _generateId(el));
        }
      }

      return order;
    },

    /**
     * Sorts the elements according to the array.
     * @param  {String[]}  order  order of the items
     */
    sort: function sort(order, useAnimation) {
      var items = {},
          rootEl = this.el;
      this.toArray().forEach(function (id, i) {
        var el = rootEl.children[i];

        if (closest(el, this.options.draggable, rootEl, false)) {
          items[id] = el;
        }
      }, this);
      useAnimation && this.captureAnimationState();
      order.forEach(function (id) {
        if (items[id]) {
          rootEl.removeChild(items[id]);
          rootEl.appendChild(items[id]);
        }
      });
      useAnimation && this.animateAll();
    },

    /**
     * Save the current sorting
     */
    save: function save() {
      var store = this.options.store;
      store && store.set && store.set(this);
    },

    /**
     * For each element in the set, get the first element that matches the selector by testing the element itself and traversing up through its ancestors in the DOM tree.
     * @param   {HTMLElement}  el
     * @param   {String}       [selector]  default: `options.draggable`
     * @returns {HTMLElement|null}
     */
    closest: function closest$1(el, selector) {
      return closest(el, selector || this.options.draggable, this.el, false);
    },

    /**
     * Set/get option
     * @param   {string} name
     * @param   {*}      [value]
     * @returns {*}
     */
    option: function option(name, value) {
      var options = this.options;

      if (value === void 0) {
        return options[name];
      } else {
        var modifiedValue = PluginManager.modifyOption(this, name, value);

        if (typeof modifiedValue !== 'undefined') {
          options[name] = modifiedValue;
        } else {
          options[name] = value;
        }

        if (name === 'group') {
          _prepareGroup(options);
        }
      }
    },

    /**
     * Destroy
     */
    destroy: function destroy() {
      pluginEvent('destroy', this);
      var el = this.el;
      el[expando] = null;
      off(el, 'mousedown', this._onTapStart);
      off(el, 'touchstart', this._onTapStart);
      off(el, 'pointerdown', this._onTapStart);

      if (this.nativeDraggable) {
        off(el, 'dragover', this);
        off(el, 'dragenter', this);
      } // Remove draggable attributes


      Array.prototype.forEach.call(el.querySelectorAll('[draggable]'), function (el) {
        el.removeAttribute('draggable');
      });

      this._onDrop();

      this._disableDelayedDragEvents();

      sortables.splice(sortables.indexOf(this.el), 1);
      this.el = el = null;
    },
    _hideClone: function _hideClone() {
      if (!cloneHidden) {
        pluginEvent('hideClone', this);
        if (Sortable.eventCanceled) return;
        css(cloneEl, 'display', 'none');

        if (this.options.removeCloneOnHide && cloneEl.parentNode) {
          cloneEl.parentNode.removeChild(cloneEl);
        }

        cloneHidden = true;
      }
    },
    _showClone: function _showClone(putSortable) {
      if (putSortable.lastPutMode !== 'clone') {
        this._hideClone();

        return;
      }

      if (cloneHidden) {
        pluginEvent('showClone', this);
        if (Sortable.eventCanceled) return; // show clone at dragEl or original position

        if (dragEl.parentNode == rootEl && !this.options.group.revertClone) {
          rootEl.insertBefore(cloneEl, dragEl);
        } else if (nextEl) {
          rootEl.insertBefore(cloneEl, nextEl);
        } else {
          rootEl.appendChild(cloneEl);
        }

        if (this.options.group.revertClone) {
          this.animate(dragEl, cloneEl);
        }

        css(cloneEl, 'display', '');
        cloneHidden = false;
      }
    }
  };

  function _globalDragOver(
  /**Event*/
  evt) {
    if (evt.dataTransfer) {
      evt.dataTransfer.dropEffect = 'move';
    }

    evt.cancelable && evt.preventDefault();
  }

  function _onMove(fromEl, toEl, dragEl, dragRect, targetEl, targetRect, originalEvent, willInsertAfter) {
    var evt,
        sortable = fromEl[expando],
        onMoveFn = sortable.options.onMove,
        retVal; // Support for new CustomEvent feature

    if (window.CustomEvent && !IE11OrLess && !Edge) {
      evt = new CustomEvent('move', {
        bubbles: true,
        cancelable: true
      });
    } else {
      evt = document.createEvent('Event');
      evt.initEvent('move', true, true);
    }

    evt.to = toEl;
    evt.from = fromEl;
    evt.dragged = dragEl;
    evt.draggedRect = dragRect;
    evt.related = targetEl || toEl;
    evt.relatedRect = targetRect || getRect(toEl);
    evt.willInsertAfter = willInsertAfter;
    evt.originalEvent = originalEvent;
    fromEl.dispatchEvent(evt);

    if (onMoveFn) {
      retVal = onMoveFn.call(sortable, evt, originalEvent);
    }

    return retVal;
  }

  function _disableDraggable(el) {
    el.draggable = false;
  }

  function _unsilent() {
    _silent = false;
  }

  function _ghostIsLast(evt, vertical, sortable) {
    var rect = getRect(lastChild(sortable.el, sortable.options.draggable));
    var spacer = 10;
    return vertical ? evt.clientX > rect.right + spacer || evt.clientX <= rect.right && evt.clientY > rect.bottom && evt.clientX >= rect.left : evt.clientX > rect.right && evt.clientY > rect.top || evt.clientX <= rect.right && evt.clientY > rect.bottom + spacer;
  }

  function _getSwapDirection(evt, target, targetRect, vertical, swapThreshold, invertedSwapThreshold, invertSwap, isLastTarget) {
    var mouseOnAxis = vertical ? evt.clientY : evt.clientX,
        targetLength = vertical ? targetRect.height : targetRect.width,
        targetS1 = vertical ? targetRect.top : targetRect.left,
        targetS2 = vertical ? targetRect.bottom : targetRect.right,
        invert = false;

    if (!invertSwap) {
      // Never invert or create dragEl shadow when target movemenet causes mouse to move past the end of regular swapThreshold
      if (isLastTarget && targetMoveDistance < targetLength * swapThreshold) {
        // multiplied only by swapThreshold because mouse will already be inside target by (1 - threshold) * targetLength / 2
        // check if past first invert threshold on side opposite of lastDirection
        if (!pastFirstInvertThresh && (lastDirection === 1 ? mouseOnAxis > targetS1 + targetLength * invertedSwapThreshold / 2 : mouseOnAxis < targetS2 - targetLength * invertedSwapThreshold / 2)) {
          // past first invert threshold, do not restrict inverted threshold to dragEl shadow
          pastFirstInvertThresh = true;
        }

        if (!pastFirstInvertThresh) {
          // dragEl shadow (target move distance shadow)
          if (lastDirection === 1 ? mouseOnAxis < targetS1 + targetMoveDistance // over dragEl shadow
          : mouseOnAxis > targetS2 - targetMoveDistance) {
            return -lastDirection;
          }
        } else {
          invert = true;
        }
      } else {
        // Regular
        if (mouseOnAxis > targetS1 + targetLength * (1 - swapThreshold) / 2 && mouseOnAxis < targetS2 - targetLength * (1 - swapThreshold) / 2) {
          return _getInsertDirection(target);
        }
      }
    }

    invert = invert || invertSwap;

    if (invert) {
      // Invert of regular
      if (mouseOnAxis < targetS1 + targetLength * invertedSwapThreshold / 2 || mouseOnAxis > targetS2 - targetLength * invertedSwapThreshold / 2) {
        return mouseOnAxis > targetS1 + targetLength / 2 ? 1 : -1;
      }
    }

    return 0;
  }
  /**
   * Gets the direction dragEl must be swapped relative to target in order to make it
   * seem that dragEl has been "inserted" into that element's position
   * @param  {HTMLElement} target       The target whose position dragEl is being inserted at
   * @return {Number}                   Direction dragEl must be swapped
   */


  function _getInsertDirection(target) {
    if (index(dragEl) < index(target)) {
      return 1;
    } else {
      return -1;
    }
  }
  /**
   * Generate id
   * @param   {HTMLElement} el
   * @returns {String}
   * @private
   */


  function _generateId(el) {
    var str = el.tagName + el.className + el.src + el.href + el.textContent,
        i = str.length,
        sum = 0;

    while (i--) {
      sum += str.charCodeAt(i);
    }

    return sum.toString(36);
  }

  function _saveInputCheckedState(root) {
    savedInputChecked.length = 0;
    var inputs = root.getElementsByTagName('input');
    var idx = inputs.length;

    while (idx--) {
      var el = inputs[idx];
      el.checked && savedInputChecked.push(el);
    }
  }

  function _nextTick(fn) {
    return setTimeout(fn, 0);
  }

  function _cancelNextTick(id) {
    return clearTimeout(id);
  } // Fixed #973:


  if (documentExists) {
    on(document, 'touchmove', function (evt) {
      if ((Sortable.active || awaitingDragStarted) && evt.cancelable) {
        evt.preventDefault();
      }
    });
  } // Export utils


  Sortable.utils = {
    on: on,
    off: off,
    css: css,
    find: find,
    is: function is(el, selector) {
      return !!closest(el, selector, el, false);
    },
    extend: extend,
    throttle: throttle,
    closest: closest,
    toggleClass: toggleClass,
    clone: clone,
    index: index,
    nextTick: _nextTick,
    cancelNextTick: _cancelNextTick,
    detectDirection: _detectDirection,
    getChild: getChild
  };
  /**
   * Get the Sortable instance of an element
   * @param  {HTMLElement} element The element
   * @return {Sortable|undefined}         The instance of Sortable
   */

  Sortable.get = function (element) {
    return element[expando];
  };
  /**
   * Mount a plugin to Sortable
   * @param  {...SortablePlugin|SortablePlugin[]} plugins       Plugins being mounted
   */


  Sortable.mount = function () {
    for (var _len = arguments.length, plugins = new Array(_len), _key = 0; _key < _len; _key++) {
      plugins[_key] = arguments[_key];
    }

    if (plugins[0].constructor === Array) plugins = plugins[0];
    plugins.forEach(function (plugin) {
      if (!plugin.prototype || !plugin.prototype.constructor) {
        throw "Sortable: Mounted plugin must be a constructor function, not ".concat({}.toString.call(plugin));
      }

      if (plugin.utils) Sortable.utils = _objectSpread({}, Sortable.utils, plugin.utils);
      PluginManager.mount(plugin);
    });
  };
  /**
   * Create sortable instance
   * @param {HTMLElement}  el
   * @param {Object}      [options]
   */


  Sortable.create = function (el, options) {
    return new Sortable(el, options);
  }; // Export


  Sortable.version = version;

  var autoScrolls = [],
      scrollEl,
      scrollRootEl,
      scrolling = false,
      lastAutoScrollX,
      lastAutoScrollY,
      touchEvt$1,
      pointerElemChangedInterval;

  function AutoScrollPlugin() {
    function AutoScroll() {
      this.defaults = {
        scroll: true,
        scrollSensitivity: 30,
        scrollSpeed: 10,
        bubbleScroll: true
      }; // Bind all private methods

      for (var fn in this) {
        if (fn.charAt(0) === '_' && typeof this[fn] === 'function') {
          this[fn] = this[fn].bind(this);
        }
      }
    }

    AutoScroll.prototype = {
      dragStarted: function dragStarted(_ref) {
        var originalEvent = _ref.originalEvent;

        if (this.sortable.nativeDraggable) {
          on(document, 'dragover', this._handleAutoScroll);
        } else {
          if (this.options.supportPointer) {
            on(document, 'pointermove', this._handleFallbackAutoScroll);
          } else if (originalEvent.touches) {
            on(document, 'touchmove', this._handleFallbackAutoScroll);
          } else {
            on(document, 'mousemove', this._handleFallbackAutoScroll);
          }
        }
      },
      dragOverCompleted: function dragOverCompleted(_ref2) {
        var originalEvent = _ref2.originalEvent;

        // For when bubbling is canceled and using fallback (fallback 'touchmove' always reached)
        if (!this.options.dragOverBubble && !originalEvent.rootEl) {
          this._handleAutoScroll(originalEvent);
        }
      },
      drop: function drop() {
        if (this.sortable.nativeDraggable) {
          off(document, 'dragover', this._handleAutoScroll);
        } else {
          off(document, 'pointermove', this._handleFallbackAutoScroll);
          off(document, 'touchmove', this._handleFallbackAutoScroll);
          off(document, 'mousemove', this._handleFallbackAutoScroll);
        }

        clearPointerElemChangedInterval();
        clearAutoScrolls();
        cancelThrottle();
      },
      nulling: function nulling() {
        touchEvt$1 = scrollRootEl = scrollEl = scrolling = pointerElemChangedInterval = lastAutoScrollX = lastAutoScrollY = null;
        autoScrolls.length = 0;
      },
      _handleFallbackAutoScroll: function _handleFallbackAutoScroll(evt) {
        this._handleAutoScroll(evt, true);
      },
      _handleAutoScroll: function _handleAutoScroll(evt, fallback) {
        var _this = this;

        var x = (evt.touches ? evt.touches[0] : evt).clientX,
            y = (evt.touches ? evt.touches[0] : evt).clientY,
            elem = document.elementFromPoint(x, y);
        touchEvt$1 = evt; // IE does not seem to have native autoscroll,
        // Edge's autoscroll seems too conditional,
        // MACOS Safari does not have autoscroll,
        // Firefox and Chrome are good

        if (fallback || Edge || IE11OrLess || Safari) {
          autoScroll(evt, this.options, elem, fallback); // Listener for pointer element change

          var ogElemScroller = getParentAutoScrollElement(elem, true);

          if (scrolling && (!pointerElemChangedInterval || x !== lastAutoScrollX || y !== lastAutoScrollY)) {
            pointerElemChangedInterval && clearPointerElemChangedInterval(); // Detect for pointer elem change, emulating native DnD behaviour

            pointerElemChangedInterval = setInterval(function () {
              var newElem = getParentAutoScrollElement(document.elementFromPoint(x, y), true);

              if (newElem !== ogElemScroller) {
                ogElemScroller = newElem;
                clearAutoScrolls();
              }

              autoScroll(evt, _this.options, newElem, fallback);
            }, 10);
            lastAutoScrollX = x;
            lastAutoScrollY = y;
          }
        } else {
          // if DnD is enabled (and browser has good autoscrolling), first autoscroll will already scroll, so get parent autoscroll of first autoscroll
          if (!this.options.bubbleScroll || getParentAutoScrollElement(elem, true) === getWindowScrollingElement()) {
            clearAutoScrolls();
            return;
          }

          autoScroll(evt, this.options, getParentAutoScrollElement(elem, false), false);
        }
      }
    };
    return _extends(AutoScroll, {
      pluginName: 'scroll',
      initializeByDefault: true
    });
  }

  function clearAutoScrolls() {
    autoScrolls.forEach(function (autoScroll) {
      clearInterval(autoScroll.pid);
    });
    autoScrolls = [];
  }

  function clearPointerElemChangedInterval() {
    clearInterval(pointerElemChangedInterval);
  }

  var autoScroll = throttle(function (evt, options, rootEl, isFallback) {
    // Bug: https://bugzilla.mozilla.org/show_bug.cgi?id=505521
    if (!options.scroll) return;
    var x = (evt.touches ? evt.touches[0] : evt).clientX,
        y = (evt.touches ? evt.touches[0] : evt).clientY,
        sens = options.scrollSensitivity,
        speed = options.scrollSpeed,
        winScroller = getWindowScrollingElement();
    var scrollThisInstance = false,
        scrollCustomFn; // New scroll root, set scrollEl

    if (scrollRootEl !== rootEl) {
      scrollRootEl = rootEl;
      clearAutoScrolls();
      scrollEl = options.scroll;
      scrollCustomFn = options.scrollFn;

      if (scrollEl === true) {
        scrollEl = getParentAutoScrollElement(rootEl, true);
      }
    }

    var layersOut = 0;
    var currentParent = scrollEl;

    do {
      var el = currentParent,
          rect = getRect(el),
          top = rect.top,
          bottom = rect.bottom,
          left = rect.left,
          right = rect.right,
          width = rect.width,
          height = rect.height,
          canScrollX = void 0,
          canScrollY = void 0,
          scrollWidth = el.scrollWidth,
          scrollHeight = el.scrollHeight,
          elCSS = css(el),
          scrollPosX = el.scrollLeft,
          scrollPosY = el.scrollTop;

      if (el === winScroller) {
        canScrollX = width < scrollWidth && (elCSS.overflowX === 'auto' || elCSS.overflowX === 'scroll' || elCSS.overflowX === 'visible');
        canScrollY = height < scrollHeight && (elCSS.overflowY === 'auto' || elCSS.overflowY === 'scroll' || elCSS.overflowY === 'visible');
      } else {
        canScrollX = width < scrollWidth && (elCSS.overflowX === 'auto' || elCSS.overflowX === 'scroll');
        canScrollY = height < scrollHeight && (elCSS.overflowY === 'auto' || elCSS.overflowY === 'scroll');
      }

      var vx = canScrollX && (Math.abs(right - x) <= sens && scrollPosX + width < scrollWidth) - (Math.abs(left - x) <= sens && !!scrollPosX);
      var vy = canScrollY && (Math.abs(bottom - y) <= sens && scrollPosY + height < scrollHeight) - (Math.abs(top - y) <= sens && !!scrollPosY);

      if (!autoScrolls[layersOut]) {
        for (var i = 0; i <= layersOut; i++) {
          if (!autoScrolls[i]) {
            autoScrolls[i] = {};
          }
        }
      }

      if (autoScrolls[layersOut].vx != vx || autoScrolls[layersOut].vy != vy || autoScrolls[layersOut].el !== el) {
        autoScrolls[layersOut].el = el;
        autoScrolls[layersOut].vx = vx;
        autoScrolls[layersOut].vy = vy;
        clearInterval(autoScrolls[layersOut].pid);

        if (vx != 0 || vy != 0) {
          scrollThisInstance = true;
          /* jshint loopfunc:true */

          autoScrolls[layersOut].pid = setInterval(function () {
            // emulate drag over during autoscroll (fallback), emulating native DnD behaviour
            if (isFallback && this.layer === 0) {
              Sortable.active._onTouchMove(touchEvt$1); // To move ghost if it is positioned absolutely

            }

            var scrollOffsetY = autoScrolls[this.layer].vy ? autoScrolls[this.layer].vy * speed : 0;
            var scrollOffsetX = autoScrolls[this.layer].vx ? autoScrolls[this.layer].vx * speed : 0;

            if (typeof scrollCustomFn === 'function') {
              if (scrollCustomFn.call(Sortable.dragged.parentNode[expando], scrollOffsetX, scrollOffsetY, evt, touchEvt$1, autoScrolls[this.layer].el) !== 'continue') {
                return;
              }
            }

            scrollBy(autoScrolls[this.layer].el, scrollOffsetX, scrollOffsetY);
          }.bind({
            layer: layersOut
          }), 24);
        }
      }

      layersOut++;
    } while (options.bubbleScroll && currentParent !== winScroller && (currentParent = getParentAutoScrollElement(currentParent, false)));

    scrolling = scrollThisInstance; // in case another function catches scrolling as false in between when it is not
  }, 30);

  var drop = function drop(_ref) {
    var originalEvent = _ref.originalEvent,
        putSortable = _ref.putSortable,
        dragEl = _ref.dragEl,
        activeSortable = _ref.activeSortable,
        dispatchSortableEvent = _ref.dispatchSortableEvent,
        hideGhostForTarget = _ref.hideGhostForTarget,
        unhideGhostForTarget = _ref.unhideGhostForTarget;
    if (!originalEvent) return;
    var toSortable = putSortable || activeSortable;
    hideGhostForTarget();
    var touch = originalEvent.changedTouches && originalEvent.changedTouches.length ? originalEvent.changedTouches[0] : originalEvent;
    var target = document.elementFromPoint(touch.clientX, touch.clientY);
    unhideGhostForTarget();

    if (toSortable && !toSortable.el.contains(target)) {
      dispatchSortableEvent('spill');
      this.onSpill({
        dragEl: dragEl,
        putSortable: putSortable
      });
    }
  };

  function Revert() {}

  Revert.prototype = {
    startIndex: null,
    dragStart: function dragStart(_ref2) {
      var oldDraggableIndex = _ref2.oldDraggableIndex;
      this.startIndex = oldDraggableIndex;
    },
    onSpill: function onSpill(_ref3) {
      var dragEl = _ref3.dragEl,
          putSortable = _ref3.putSortable;
      this.sortable.captureAnimationState();

      if (putSortable) {
        putSortable.captureAnimationState();
      }

      var nextSibling = getChild(this.sortable.el, this.startIndex, this.options);

      if (nextSibling) {
        this.sortable.el.insertBefore(dragEl, nextSibling);
      } else {
        this.sortable.el.appendChild(dragEl);
      }

      this.sortable.animateAll();

      if (putSortable) {
        putSortable.animateAll();
      }
    },
    drop: drop
  };

  _extends(Revert, {
    pluginName: 'revertOnSpill'
  });

  function Remove() {}

  Remove.prototype = {
    onSpill: function onSpill(_ref4) {
      var dragEl = _ref4.dragEl,
          putSortable = _ref4.putSortable;
      var parentSortable = putSortable || this.sortable;
      parentSortable.captureAnimationState();
      dragEl.parentNode && dragEl.parentNode.removeChild(dragEl);
      parentSortable.animateAll();
    },
    drop: drop
  };

  _extends(Remove, {
    pluginName: 'removeOnSpill'
  });

  Sortable.mount(new AutoScrollPlugin());
  Sortable.mount(Remove, Revert);

  const tabs = function() {
      return {
          tab: null,

          initTabs: function(defaultTab) {
              if (window.location.hash) {
                  this.tab = window.location.hash.substring(1);
              } else {
                  this.tab = defaultTab;
              }
          },

          changeTab: function(tabName) {
              this.tab = tabName;
              window.location.hash = tabName;

              // Trigger size updates on textareas
              const tabEl = document.getElementById('tab-'+this.tab);
              const textareas = tabEl.querySelectorAll("textarea.resize-none");
              for (let i = 0; i < textareas.length; i++) {
                  setTimeout(function() {
                      textareas[i].dispatchEvent(new CustomEvent('update-size', {
                          bubbles: true
                      }));
                  }, 100);
              }
          }

      };
  };

  const assetIndex = function() {
      return {
          deleting: false,
          uploading: false,

          // Fired after uploading an asset
          uploaderOnLoad(evt) {
              // We want to cancel the response handling because we can’t actually use it
              evt.preventDefault();
              evt.stopPropagation();

              // TODO: at this point, we might be able to request another upload?

              // Then manually trigger a refresh of only the asset index
              htmx.trigger('#files-index', 'refresh');

              this.uploading = false;
              return false;
          },

          // Progress bar on upload
          uploaderOnProgress(evt) {
              var percent = evt.detail.loaded/evt.detail.total * 100;
              htmx.find('#file-uploader-progress').style.width = percent+'%';
          }
      }
  };

  const variantBlock = function() {
      return {
          visible: true,
          isDefault: false,
          isEnabled: true,
          isExpanded: true,
          titleText: '',

          initBlock: function(isDefault, isEnabled, fadeIn, titleText) {
              this.isDefault = isDefault;
              this.isEnabled = isEnabled;
              this.isExpanded = this.isEnabled;
              this.titleText = titleText;

              if (fadeIn) {
                  this.visible = false;
              }

              // Enable all delete buttons or disable if there is only block
              const blocks = document.querySelectorAll('.variant-block');
              if (blocks.length > 1) {
                  for (let i = 0; i < blocks.length; i++) {
                      let btn = blocks[i].querySelector('.variant-block-delete-btn');
                      btn.removeAttribute('disabled');
                      btn.classList.remove('opacity-30');
                  }
              } else {
                  let btn = blocks[0].querySelector('.variant-block-delete-btn');
                  btn.setAttribute('disabled', 'disabled');
                  btn.classList.add('opacity-30');
              }

              if (fadeIn) {
                  setTimeout(() => {
                      this.visible = true;
                  }, 200);
              }
          },

          updateTitle: function($event) {
              this.titleText = $event.detail.titleValue;
          },

          setAsDefault: function($event) {
              const vbs = document.querySelectorAll('.variant-block-default-input');
              for (let i = 0; i < vbs.length; i++) {
                  vbs[i].dispatchEvent(new CustomEvent('toggle-off', {
                      bubbles: true
                  }));
              }
              this.isDefault = true;
              this.$refs.defaultInput.value = '1';

              // Make sure the block is enabled
              this.toggleEnabled(true);
          },

          setNotDefault: function($event) {
              this.isDefault = false;
              this.$refs.defaultInput.value = '';
          },

          toggleEnabled: function (val) {
              this.isEnabled = val;
              this.$refs.enabledInput.value = this.isEnabled ? '1' : '';
              this.toggleExpanded(this.isEnabled);
          },

          toggleExpanded: function (val) {
              this.isExpanded = val;
          },

          removeBlock: function($event) {
              let blocks = document.querySelectorAll('.variant-block');

              // Check we have more than one block
              if (blocks.length > 1) {
                  this.visible = false;

                  setTimeout(() => {
                      this.$el.parentNode.removeChild(this.$el);

                      // If there is now only one block, set disable its delete button
                      let blocks = document.querySelectorAll('.variant-block');
                      if (blocks.length === 1) {
                          const btn = blocks[0].querySelector('.variant-block-delete-btn');
                          btn.setAttribute('disabled', 'disabled');
                          btn.classList.add('opacity-30');
                      }

                      // Check if it was default and if so mark the first one remaining as default
                      if (this.isDefault) {
                          blocks[0].dispatchEvent(new CustomEvent('make-default', {
                              bubbles: true
                          }));
                      }

                  }, 250);
              }
          }
      }
  };

  const assetField = function() {
      return {
          open: false,
          fieldId: null,
          uploading: false,

          initField(fieldId) {
              this.fieldId = fieldId;
          },

          onSave(ids) {
              // Close the panel
              this.open = false;

              // Find the input in the parent, update it with the ids and then trigger a refresh
              htmx.find('#field-'+this.fieldId+'-selectedIds').value = ids;
              htmx.trigger('#field-'+this.fieldId, 'refresh');
          },

          trackScroll(evt) {
              // Store the scroll of this field’s index
              Market.assetFields[this.fieldId] = {
                  scroll: htmx.find('#field-'+this.fieldId+'-assets-index-container').scrollTop
              };
          },

          applyScroll(evt) {
              // Check we have a scroll val stored and that the event is coming from
              // the asset element checkbox and not anywhere else
              if (typeof Market.assetFields[this.fieldId] !== 'undefined' && evt.detail.requestConfig.elt.classList.contains("asset-toggle")){
                  htmx.find('#field-'+this.fieldId+'-assets-index-container').scrollTop = Market.assetFields[this.fieldId].scroll;
              }
          },

          // Fired after uploading an asset
          uploaderOnLoad(evt) {
              // We want to cancel the response handling because we can’t actually use it
              evt.preventDefault();
              evt.stopPropagation();

              // Then manually trigger a refresh of only the asset index
              htmx.trigger('#field-'+this.fieldId+'-assets-index', 'refresh');

              this.uploading = false;
              return false;
          },

          // Progress bar on upload
          uploaderOnProgress(evt) {
              var percent = evt.detail.loaded/evt.detail.total * 100;
              htmx.find('#field-'+this.fieldId+'-uploader-progress').style.width = percent+'%';
          }
      }
  };

  const categoryField = function() {
      return {
          open: false,
          fieldId: null,

          initField(fieldId) {
              this.fieldId = fieldId;
          },

          onSave(ids) {
              // Close the panel
              this.open = false;

              // Find the input in the parent, update it with the ids and then trigger a refresh
              htmx.find('#field-'+this.fieldId+'-selectedIds').value = ids;
              htmx.trigger('#field-'+this.fieldId, 'refresh');
          },

          trackScroll(evt) {
              // Store the scroll of this field’s index
              Market.categoryFields[this.fieldId] = {
                  scroll: htmx.find('#field-'+this.fieldId+'-categories-index-container').scrollTop
              };
          },

          applyScroll(evt) {
              // Check we have a scroll val stored and that the event is coming from
              // the category toggle button and not anywhere else
              if (typeof Market.categoryFields[this.fieldId] !== 'undefined' && evt.detail.requestConfig.elt.classList.contains("category-toggle")){
                  htmx.find('#field-'+this.fieldId+'-categories-index-container').scrollTop = Market.categoryFields[this.fieldId].scroll;
              }
          }
      }
  };

  const DP_MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const DP_DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  const datepickerField = function() {
      return {
          showDatepicker: false,
          datepickerValue: '',

          month: '',
          year: '',
          no_of_days: [],
          blankdays: [],
          days: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],

          initDate(value) {
              if (value) {
                  let currDate = new Date(Date.parse(value));
                  this.month = currDate.getMonth();
                  this.year = currDate.getFullYear();
                  this.datepickerValue = currDate.toLocaleDateString();
              } else {
                  let today = new Date();
                  this.month = today.getMonth();
                  this.year = today.getFullYear();
              }
          },

          isToday(date) {
              const today = new Date();
              const d = new Date(this.year, this.month, date);

              return today.toLocaleDateString() === d.toLocaleDateString() ? true : false;
          },

          isSelectedDate(date) {
              const d = new Date(this.year, this.month, date);

              return this.datepickerValue === d.toLocaleDateString() ? true : false;
          },

          getDateValue(date) {
              let selectedDate = new Date(this.year, this.month, date);
              this.datepickerValue = selectedDate.toLocaleDateString();

              this.$refs.date.value = selectedDate.getFullYear() +"-"+ ('0'+ (selectedDate.getMonth()+1)).slice(-2) +"-"+ ('0' + selectedDate.getDate()).slice(-2);

              this.showDatepicker = false;
          },

          clearDateValue() {
              this.datepickerValue = '';
              this.$refs.date.value = '';
              this.showDatepicker = false;
          },

          getNoOfDays() {
              let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();

              // find where to start calendar day of week
              let dayOfWeek = new Date(this.year, this.month).getDay();
              let blankdaysArray = [];
              for ( var i=1; i <= dayOfWeek; i++) {
                  blankdaysArray.push(i);
              }

              let daysArray = [];
              for ( var i=1; i <= daysInMonth; i++) {
                  daysArray.push(i);
              }

              this.blankdays = blankdaysArray;
              this.no_of_days = daysArray;
          },

          next() {
              if (this.month == 11) {
                  this.year++;
                  this.month = 0;
              } else {
                  this.month++;
              }
          },

          prev() {
              if (this.month == 0) {
                  this.year--;
                  this.month = 11;
              } else {
                  this.month--;
              }
          }
      }
  };

  const lightswitchField = function() {
      return {
          on: false,

          initValue: function(val) {
              this.on = val;
          },

          updateValue: function (val) {
              this.on = val;
              this.$refs.input.value = this.on ? '1' : '';
          }
      }
  };

  const skuField = function() {
      return {
          prefix: '',
          skuValue: '',

          initField: function(prefix, value) {
              this.prefix = prefix;
              this.skuValue = value;
          },

          onUpdate: function($event) {
              if (this.skuValue) {
                  this.$refs.input.value = this.prefix + this.skuValue;
              } else {
                  this.$refs.input.value = '';
              }
          }
      }
  };

  const slugField = function() {
      return {
          slug: '',

          initSlug: function(val) {
              this.slug = val;
          },

          updateFromTitle: function() {
              this.formatValue(this.slug);
          },

          formatValue: function (val) {
              // Clean it all up
              val = val
                      // Remove HTML tags
                      .replace(/<(.*?)>/g, '')

                      // Remove inner-word punctuation
                      .replace(/['"‘’“”\[\]\(\)\{\}:]/g, '')

                      // Remove leading and trailing whitespace
                      .trim()

                      // Replace non alpha chars with dashes
                      .replace(/\W+/g, '-')

                      // Make it lowercase
                      .toLowerCase();

              // Set it on the model
              this.slug = val;
          }
      }
  };

  const stockField = function() {
      return {
          hasUnlimitedStock: false,

          initField: function(hasUnlimitedStock) {
              this.hasUnlimitedStock = hasUnlimitedStock;
          }
      }
  };

  const textField = function() {
      return {
          fieldId: null,
          value: null,
          limit: null,

          initField: function(fieldId, value, limit, isMultiline) {
              this.fieldId = fieldId;
              this.value = value;
              this.limit = limit;

              // Initial resize size
              if (isMultiline) {
                  const inputEl = document.getElementById('field-' + this.fieldId);
                  setTimeout(() => {
                      this.resize(inputEl);
                  }, 100);
              }
          },

          resize: function(el) {
              el.style.height = 'auto';
              el.style.height = (el.scrollHeight + 2) + 'px';
          },

          get remaining() {
              return this.limit - this.value.length
          },
      }
  };

  const timepickerField = function() {
      return {
          timepickerValue: '',

          initTime(value) {
              if (value) {
                  this.timepickerValue = value;
              }
          }
      }
  };

  const titleField = function() {
      return {
          titleValue: null,
          allowSlugUpdate: true,

          initField: function(titleVal) {
              this.titleValue = titleVal;
          },

          updateSlug: function(evt) {
              if (this.allowSlugUpdate) {
                  if (evt.target.value.trim() !== '') {
                      const slugInput = document.getElementById('slug');
                      slugInput.value = this.titleValue;
                      slugInput.dispatchEvent(new CustomEvent('update-from-title', {
                          bubbles: true
                      }));
                      this.allowSlugUpdate = false;
                  }
              }
          },

          updateVariantBlock: function(evt) {
              evt.target.closest(".variant-block").dispatchEvent(new CustomEvent('update-from-title', {
                  bubbles: true,
                  detail: {
                      titleValue: this.titleValue
                  }
              }));
          }
      }
  };

  var main = {
      Sortable,
      categoryFields: {},
      assetFields: {},
      tabs,
      assetIndex,
      variantBlock,
      assetField, // CHECK Market.var stuff
      categoryField, // CHECK Market.var stuff
      DP_MONTH_NAMES,
      DP_DAYS,
      datepickerField,
      lightswitchField,
      skuField,
      slugField,
      stockField,
      textField,
      timepickerField,
      titleField,
  };

  return main;

}());
