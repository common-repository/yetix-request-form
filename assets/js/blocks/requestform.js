/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(1);


/***/ }),
/* 1 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _icons_requestform_jsx__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/* harmony import */ var _components_yetix_template_selector_jsx__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);



(function (wp) {
  var ServerSideRender = wp.serverSideRender;
  var _wp$i18n = wp.i18n,
      __ = _wp$i18n.__,
      _x = _wp$i18n._x;
  var registerBlockType = wp.blocks.registerBlockType;
  var _wp$components = wp.components,
      SelectControl = _wp$components.SelectControl,
      PanelBody = _wp$components.PanelBody,
      ToggleControl = _wp$components.ToggleControl,
      BaseControl = _wp$components.BaseControl,
      RangeControl = _wp$components.RangeControl;
  var _wp$blockEditor = wp.blockEditor,
      URLInputButton = _wp$blockEditor.URLInputButton,
      InspectorControls = _wp$blockEditor.InspectorControls;
  var Fragment = wp.element.Fragment;
  registerBlockType('yetix/requestform', {
    title: __('Request Form', 'yetix-request-form'),
    icon: {
      background: '#F1F9F9',
      src: _icons_requestform_jsx__WEBPACK_IMPORTED_MODULE_0__["default"]
    },
    keywords: [_x('form', 'block_search_keyword', 'yetix-request-form'), _x('ticket', 'block_search_keyword', 'yetix-request-form'), _x('zendesk', 'block_search_keyword', 'yetix-request-form')],
    category: 'yetix_blocks',
    attributes: {
      template: {
        type: 'string',
        "default": 'default'
      },
      hide_form_after_send: {
        type: 'boolean',
        "default": false
      },
      return_type: {
        type: 'string',
        "default": 'display_msg'
      },
      return_url: {
        type: 'string',
        "default": null
      },
      return_timeout: {
        type: 'number',
        "default": 1000
      }
    },
    supports: {
      className: true,
      align: ['left', 'center', 'right', 'wide', 'full']
    },
    edit: function edit(_ref) {
      var attributes = _ref.attributes,
          setAttributes = _ref.setAttributes;
      var return_type = attributes.return_type,
          return_url = attributes.return_url,
          return_timeout = attributes.return_timeout,
          hide_form_after_send = attributes.hide_form_after_send,
          template = attributes.template;
      return /*#__PURE__*/React.createElement(Fragment, null, /*#__PURE__*/React.createElement(InspectorControls, null, /*#__PURE__*/React.createElement(PanelBody, {
        title: __('Request Form Settings', 'yetix-request-form'),
        initialOpen: true
      }, /*#__PURE__*/React.createElement(_components_yetix_template_selector_jsx__WEBPACK_IMPORTED_MODULE_1__["default"], {
        options: {
          label: __('Template', 'yetix-request-form'),
          block_slug: 'requestform'
        },
        template: template,
        setAttributes: setAttributes
      }), /*#__PURE__*/React.createElement(SelectControl, {
        label: __('Return type', 'yetix-request-form'),
        value: return_type,
        options: [{
          label: __('Display result in the form', 'yetix-request-form'),
          value: 'display_msg'
        }, {
          label: __('Redirect to a specific url', 'yetix-request-form'),
          value: 'redirect'
        }],
        onChange: function onChange(value) {
          return setAttributes({
            return_type: value
          });
        }
      })), 'redirect' === return_type && /*#__PURE__*/React.createElement(PanelBody, {
        title: __('Redirect behavior', 'yetix-request-form'),
        initialOpen: false
      }, /*#__PURE__*/React.createElement(BaseControl, {
        label: __('Redirection url', 'yetix-request-form')
      }, /*#__PURE__*/React.createElement(URLInputButton, {
        url: return_url,
        onChange: function onChange(value, post) {
          return setAttributes({
            value: value,
            return_url: value
          });
        }
      })), /*#__PURE__*/React.createElement(RangeControl, {
        label: __('redirection timeout', 'yetix-request-form'),
        step: "500",
        min: "0",
        max: "20000",
        onChange: function onChange(value) {
          return setAttributes({
            return_timeout: value
          });
        },
        value: return_timeout
      })), 'display_msg' === return_type && /*#__PURE__*/React.createElement(PanelBody, {
        title: __('Display behavior', 'yetix-request-form'),
        initialOpen: false
      }, /*#__PURE__*/React.createElement(ToggleControl, {
        label: __('hide form when send', 'yetix-request-form'),
        checked: hide_form_after_send,
        onChange: function onChange() {
          return setAttributes({
            hide_form_after_send: !hide_form_after_send
          });
        }
      }), /*#__PURE__*/React.createElement(RangeControl, {
        label: __('re-enable form timeout', 'yetix-request-form'),
        step: "500",
        min: "0",
        max: "20000",
        onChange: function onChange(value) {
          return setAttributes({
            return_timeout: value
          });
        },
        value: return_timeout
      }))), /*#__PURE__*/React.createElement(ServerSideRender, {
        block: "yetix/requestform",
        attributes: attributes
      }));
    },
    save: function save() {
      return null;
    }
  });
})(window.wp);

/***/ }),
/* 2 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function SvgComponent(props) {
  return /*#__PURE__*/React.createElement("svg", _extends({
    width: 20,
    height: 20,
    viewBox: "0 0 20 20",
    version: "1.1",
    xmlns: "http://www.w3.org/2000/svg",
    xmlnsXlink: "http://www.w3.org/1999/xlink"
  }, props), /*#__PURE__*/React.createElement("title", null, "Request Form"), /*#__PURE__*/React.createElement("defs", null, /*#__PURE__*/React.createElement("linearGradient", {
    x1: "50%",
    y1: "0%",
    x2: "50%",
    y2: "100%",
    id: "linearGradient-1"
  }, /*#__PURE__*/React.createElement("stop", {
    "stop-color": "#007988",
    offset: "0%"
  }), /*#__PURE__*/React.createElement("stop", {
    "stop-color": "#5EBFC5",
    offset: "100%"
  }))), /*#__PURE__*/React.createElement("g", {
    id: "yetix-request",
    stroke: "none",
    "stroke-width": "1",
    fill: "none",
    "fill-rule": "evenodd"
  }, /*#__PURE__*/React.createElement("g", {
    id: "noun_form_1237655",
    fill: "url(#linearGradient-1)",
    "fill-rule": "nonzero"
  }, /*#__PURE__*/React.createElement("g", {
    id: "Group"
  }, /*#__PURE__*/React.createElement("path", {
    d: "M1.70138889,0 C0.768998556,0 0,0.768944556 0,1.70138889 L0,18.3007811 C0,19.2332256 0.768998556,20 1.70138889,20 L18.2986111,20 C19.2310022,20 20,19.2332256 20,18.3007811 L20,1.70138889 C20,0.768944556 19.2310022,0 18.2986111,0 L1.70138889,0 Z M1.70138889,1.11328122 L18.2986111,1.11328122 C18.6346267,1.11328122 18.8888889,1.36527778 18.8888889,1.70138889 L18.8888889,18.3007811 C18.8888889,18.6368922 18.6346267,18.8888889 18.2986111,18.8888889 L1.70138889,18.8888889 C1.36537356,18.8888889 1.11111111,18.6368922 1.11111111,18.3007811 L1.11111111,1.70138889 C1.11111111,1.36527778 1.36537356,1.11328122 1.70138889,1.11328122 Z M9.44444444,3.33333333 C9.13763228,3.33333333 8.88888889,3.58207673 8.88888889,3.88888889 L8.88888889,7.22222222 C8.88888889,7.52903438 9.13763228,7.77777778 9.44444444,7.77777778 L16.1111111,7.77777778 C16.4179233,7.77777778 16.6666667,7.52903438 16.6666667,7.22222222 L16.6666667,3.88888889 C16.6666667,3.58207673 16.4179233,3.33333333 16.1111111,3.33333333 L9.44444444,3.33333333 Z M3.33333333,4.44444444 L3.33333333,5.55555556 L6.66666667,5.55555556 L6.66666667,4.44444444 L3.33333333,4.44444444 Z M10,4.44444444 L15.5555556,4.44444444 L15.5555556,6.66666667 L10,6.66666667 L10,4.44444444 Z M9.44444444,8.88888889 C9.13763228,8.88888889 8.88888889,9.13763228 8.88888889,9.44444444 L8.88888889,12.7777778 C8.88888889,13.0845899 9.13763228,13.3333333 9.44444444,13.3333333 L16.1111111,13.3333333 C16.4179233,13.3333333 16.6666667,13.0845899 16.6666667,12.7777778 L16.6666667,9.44444444 C16.6666667,9.13763228 16.4179233,8.88888889 16.1111111,8.88888889 L9.44444444,8.88888889 Z M3.33333333,10 L3.33333333,11.1111111 L6.66666667,11.1111111 L6.66666667,10 L3.33333333,10 Z M10,10 L15.5555556,10 L15.5555556,12.2222222 L10,12.2222222 L10,10 Z M3.33333333,15.5555556 L3.33333333,16.6666667 L16.55382,16.6666667 L16.55382,15.5555556 L3.33333333,15.5555556 Z",
    id: "Shape"
  })))));
}

/* harmony default export */ __webpack_exports__["default"] = (SvgComponent);

/***/ }),
/* 3 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

var __ = wp.i18n.__;
var _wp$element = wp.element,
    Fragment = _wp$element.Fragment,
    Component = _wp$element.Component;
var _wp$components = wp.components,
    PanelBody = _wp$components.PanelBody,
    SelectControl = _wp$components.SelectControl;

var TemplateSelector = /*#__PURE__*/function (_Component) {
  _inherits(TemplateSelector, _Component);

  var _super = _createSuper(TemplateSelector);

  function TemplateSelector(props) {
    var _this;

    _classCallCheck(this, TemplateSelector);

    _this = _super.call(this, props);
    _this.state = {
      optionsList: [],
      label: __('Template', 'yetix-request-form'),
      block_slug: null,
      restRoute: {
        route: 'yetix/v1/block-templates/',
        method: 'GET'
      }
    };

    if (undefined !== _this.props.options) {
      _this.setOptions(_this.props.options);
    }

    _this.fetchPost();

    return _this;
  }

  _createClass(TemplateSelector, [{
    key: "setOptions",
    value: function setOptions(options) {
      if (undefined !== options.label) {
        this.state.label = options.label;
      }

      if (undefined !== options.restRoute) {
        this.state.restRoute = options.restRoute;
      }

      if (undefined !== options.block_slug) {
        this.state.block_slug = options.block_slug;
      }
    }
  }, {
    key: "fetchPost",
    value: function fetchPost() {
      var block_slug = this.state.block_slug;
      var _this$state$restRoute = this.state.restRoute,
          route = _this$state$restRoute.route,
          method = _this$state$restRoute.method;
      wp.apiFetch({
        path: route + block_slug,
        method: method
      }).then(function (response) {
        var optionsList = [];

        if ("success" === response.status) {
          response.templates.map(function (item) {
            optionsList.push({
              value: item.value,
              label: item.label
            });
          });
        }

        this.setState({
          optionsList: optionsList
        });
      }.bind(this));
    }
  }, {
    key: "render",
    value: function render() {
      var _this$props = this.props,
          setAttributes = _this$props.setAttributes,
          template = _this$props.template;
      var _this$state = this.state,
          label = _this$state.label,
          restRoute = _this$state.restRoute,
          optionsList = _this$state.optionsList;
      return /*#__PURE__*/React.createElement(Fragment, null, 1 < optionsList.length && /*#__PURE__*/React.createElement(SelectControl, {
        label: label,
        value: template,
        onChange: function onChange(template) {
          setAttributes({
            template: template
          });
        },
        options: this.state.optionsList
      }));
    }
  }]);

  return TemplateSelector;
}(Component);

;
/* harmony default export */ __webpack_exports__["default"] = (TemplateSelector);

/***/ })
/******/ ]);