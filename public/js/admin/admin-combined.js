(function() {
  var sbAdmin;

  sbAdmin = {
    ctrl: {}
  };

  sbAdmin.api = (function() {
    var api, errorHandler, newNonce, signRequest, signURLParameters;
    api = {};
    errorHandler = function(error) {
      console.log("an error occurred", error);
    };
    signRequest = function(xhr, xhrOptions) {
      var credentials, nonce, paramsBody, signature, url, _ref;
      credentials = sbAdmin.auth.getCredentials();
      if (!((_ref = credentials.apiToken) != null ? _ref.length : void 0)) {
        return;
      }
      nonce = newNonce();
      if (xhrOptions.data != null) {
        if (typeof xhrOptions.data === 'object') {
          paramsBody = window.JSON.stringify(xhrOptions.data);
        } else {
          paramsBody = xhrOptions.data;
        }
      } else {
        paramsBody = '{}';
      }
      url = window.location.protocol + '//' + window.location.host + xhrOptions.url;
      signature = signURLParameters(xhrOptions.method, url, paramsBody, nonce, credentials);
      xhr.setRequestHeader('X-Tokenly-Auth-Nonce', nonce);
      xhr.setRequestHeader('X-Tokenly-Auth-Api-Token', credentials.apiToken);
      xhr.setRequestHeader('X-Tokenly-Auth-Signature', signature);
    };
    signURLParameters = function(method, url, paramsBody, nonce, credentials) {
      var hmacMessage, signature;
      hmacMessage = method + "\n" + url + "\n" + paramsBody + "\n" + credentials.apiToken + "\n" + nonce;
      signature = CryptoJS.HmacSHA256(hmacMessage, credentials.apiSecretKey).toString(CryptoJS.enc.Base64);
      return signature;
    };
    newNonce = function() {
      return Math.round(0 + new Date() / 1000);
    };
    api.getSelf = function() {
      return api.send('GET', 'users/me');
    };
    api.newBot = function(botAttributes) {
      return api.send('POST', 'bots', botAttributes);
    };
    api.updateBot = function(id, botAttributes) {
      return api.send('PUT', "bots/" + id, botAttributes);
    };
    api.getAllBots = function() {
      return api.send('GET', 'bots');
    };
    api.getBot = function(id) {
      return api.send('GET', "bots/" + id);
    };
    api.getBotEvents = function(id, additionalOpts) {
      if (additionalOpts == null) {
        additionalOpts = {};
      }
      return api.send('GET', "botevents/" + id, null, additionalOpts);
    };
    api.send = function(method, apiPathSuffix, params, additionalOpts) {
      var k, opts, path, v;
      if (params == null) {
        params = null;
      }
      if (additionalOpts == null) {
        additionalOpts = {};
      }
      path = '/api/v1/' + apiPathSuffix;
      opts = {
        method: method,
        url: path,
        data: params,
        config: signRequest
      };
      for (k in additionalOpts) {
        v = additionalOpts[k];
        opts[k] = v;
      }
      return m.request(opts);
    };
    return api;
  })();

  sbAdmin.auth = (function() {
    var auth;
    auth = {};
    auth.redirectIfNotLoggedIn = function() {
      if (!auth.isLoggedIn()) {
        m.route('/login');
      }
    };
    auth.isLoggedIn = function() {
      var credentials, _ref, _ref1;
      credentials = auth.getCredentials();
      if (((_ref = credentials.apiToken) != null ? _ref.length : void 0) > 0 && ((_ref1 = credentials.apiSecretKey) != null ? _ref1.length : void 0) > 0) {
        return true;
      }
      return false;
    };
    auth.getUser = function() {
      var user;
      user = window.JSON.parse(localStorage.getItem("user"));
      if (!user) {
        return {};
      }
      return user;
    };
    auth.login = function(apiToken, apiSecretKey) {
      window.localStorage.setItem("apiToken", apiToken);
      window.localStorage.setItem("apiSecretKey", apiSecretKey);
      return sbAdmin.api.getSelf().then(function(user) {
        window.localStorage.setItem("user", window.JSON.stringify(user));
        return user;
      });
    };
    auth.logout = function() {
      window.localStorage.removeItem("apiToken");
      window.localStorage.removeItem("apiSecretKey");
      window.localStorage.removeItem("user");
    };
    auth.getCredentials = function() {
      return {
        apiToken: localStorage.getItem("apiToken"),
        apiSecretKey: localStorage.getItem("apiSecretKey")
      };
    };
    return auth;
  })();

  sbAdmin.form = (function() {
    var form;
    form = {};
    form.mValueDisplay = function(label, attributes, value) {
      var id, inputEl, inputProps;
      inputProps = sbAdmin.utils.clone(attributes);
      if (inputProps["class"] == null) {
        inputProps["class"] = 'form-control-static';
      }
      id = inputProps.id || 'value';
      return m("div", {
        "class": "form-group"
      }, [
        m("label", {
          "for": id,
          "class": 'control-label'
        }, label), inputEl = m("div", inputProps, value)
      ]);
    };
    form.mFormField = function(label, attributes, prop) {
      var inputEl;
      inputEl = form.mInputEl(attributes, prop);
      return m("div", {
        "class": "form-group"
      }, [
        m("label", {
          "for": attributes.id,
          "class": 'control-label'
        }, label), inputEl
      ]);
    };
    form.mInputEl = function(attributes, prop) {
      var inputEl, inputProps, name;
      inputProps = sbAdmin.utils.clone(attributes);
      name = inputProps.name || inputProps.id;
      inputProps.onchange = m.withAttr("value", prop);
      inputProps.value = prop();
      if (inputProps["class"] == null) {
        inputProps["class"] = 'form-control';
      }
      if (inputProps.name == null) {
        inputProps.name = inputProps.id;
      }
      if (inputProps.type === 'textarea') {
        delete inputProps.type;
        inputProps.rows = inputProps.rows || 3;
        inputEl = m("textarea", inputProps);
      } else {
        inputEl = m("input", inputProps);
      }
      return inputEl;
    };
    form.mSubmitBtn = function(label) {
      return m("button", {
        type: 'submit',
        "class": 'btn btn-primary'
      }, label);
    };
    form.mAlerts = function(errorsProp) {
      if (errorsProp().length === 0) {
        return null;
      }
      return m("div", {
        "class": "alert alert-danger",
        role: "alert"
      }, [
        m("strong", "An error occurred."), m("ul", {
          "class": "list-unstyled"
        }, [
          errorsProp().map(function(errorMsg) {
            return m('li', errorMsg);
          })
        ])
      ]);
    };
    form.mForm = function(props, elAttributes, children) {
      var formAttributes, status;
      formAttributes = sbAdmin.utils.clone(elAttributes);
      if (props.status != null) {
        status = props.status();
      }
      if (status === 'submitting') {
        formAttributes.style = {
          opacity: 0.25
        };
      }
      return m("form", formAttributes, children);
    };
    form.submit = function(apiCallFn, apiCallArgs, errorsProp, formStatusProp) {
      if (formStatusProp() === 'submitting') {
        return;
      }
      errorsProp([]);
      formStatusProp('submitting');
      return apiCallFn.apply(null, apiCallArgs).then(function(apiResponse) {
        console.log("apiResponse=", apiResponse);
        formStatusProp('submitted');
        return apiResponse;
      }, function(error) {
        console.log("error=", error);
        formStatusProp('active');
        errorsProp(error.errors);
        return m.deferred().reject(error).promise;
      });
    };
    return form;
  })();

  sbAdmin.utils = (function() {
    var utils;
    utils = {};
    utils.clone = function(obj) {
      var attr, copy;
      if (null === obj || "object" !== typeof obj) {
        return obj;
      }
      copy = obj.constructor();
      for (attr in obj) {
        if (obj.hasOwnProperty(attr)) {
          copy[attr] = obj[attr];
        }
      }
      return copy;
    };
    utils.isEmpty = function(obj) {
      var key;
      if (obj == null) {
        return true;
      }
      if (obj.length > 0) {
        return false;
      }
      if (obj.length === 0) {
        return true;
      }
      for (key in obj) {
        if (hasOwnProperty.call(obj, key)) {
          return false;
        }
      }
      return true;
    };
    return utils;
  })();

  (function() {
    var swapGroup, vm;
    sbAdmin.ctrl.botForm = {};
    swapGroup = function(number, swapProp) {
      return m("div", {
        "class": "asset-group"
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-4"
          }, [
            sbAdmin.form.mFormField("Receives Asset", {
              id: "swap_in_" + number,
              'placeholder': "BTC"
            }, swapProp()["in"])
          ]), m("div", {
            "class": "col-md-4"
          }, [
            sbAdmin.form.mFormField("Sends Asset", {
              id: "swap_out_" + number,
              'placeholder': "LTBCOIN"
            }, swapProp().out)
          ]), m("div", {
            "class": "col-md-3"
          }, [
            sbAdmin.form.mFormField("Rate", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_rate_" + number,
              'placeholder': "0.99"
            }, swapProp().rate)
          ]), m("div", {
            "class": "col-md-1"
          }, [
            m("a", {
              "class": "remove-link",
              href: '#remove',
              onclick: vm.buildRemoveSwapFn(number),
              style: number === 1 ? {
                display: 'none'
              } : ""
            }, [
              m("span", {
                "class": "glyphicon glyphicon-remove-circle",
                title: "Remove Swap " + number
              }, '')
            ])
          ])
        ])
      ]);
    };
    vm = sbAdmin.ctrl.botForm.vm = (function() {
      var buildBlacklistAddressesPropValue, buildSwapsPropValue, newSwapProp;
      buildSwapsPropValue = function(swaps) {
        var out, swap, _i, _len;
        out = [];
        for (_i = 0, _len = swaps.length; _i < _len; _i++) {
          swap = swaps[_i];
          out.push(newSwapProp(swap));
        }
        if (!out.length) {
          out.push(newSwapProp());
        }
        return out;
      };
      newSwapProp = function(swap) {
        if (swap == null) {
          swap = {};
        }
        return m.prop({
          "in": m.prop(swap["in"] || ''),
          out: m.prop(swap.out || ''),
          rate: m.prop(swap.rate || '')
        });
      };
      buildBlacklistAddressesPropValue = function(addresses) {
        var address, out, _i, _len;
        out = [];
        for (_i = 0, _len = addresses.length; _i < _len; _i++) {
          address = addresses[_i];
          out.push(m.prop(address));
        }
        if (!out.length) {
          out.push(m.prop(''));
        }
        return out;
      };
      vm = {};
      vm.init = function() {
        var id;
        vm.errorMessages = m.prop([]);
        vm.formStatus = m.prop('active');
        vm.resourceId = m.prop('');
        vm.name = m.prop('');
        vm.description = m.prop('');
        vm.swaps = m.prop([newSwapProp()]);
        vm.blacklistAddresses = m.prop([m.prop('')]);
        id = m.route.param('id');
        if (id !== 'new') {
          sbAdmin.api.getBot(id).then(function(botData) {
            vm.resourceId(botData.id);
            vm.name(botData.name);
            vm.description(botData.description);
            vm.swaps(buildSwapsPropValue(botData.swaps));
            vm.blacklistAddresses(buildBlacklistAddressesPropValue(botData.blacklistAddresses));
          }, function(errorResponse) {
            vm.errorMessages(errorResponse.errors);
          });
        }
        vm.addSwap = function(e) {
          e.preventDefault();
          vm.swaps().push(newSwapProp());
        };
        vm.buildRemoveSwapFn = function(number) {
          return function(e) {
            var newSwaps;
            e.preventDefault();
            newSwaps = vm.swaps().filter(function(swap, index) {
              return index !== number - 1;
            });
            vm.swaps(newSwaps);
          };
        };
        vm.addBlacklistAddress = function(e) {
          e.preventDefault();
          vm.blacklistAddresses().push(m.prop(''));
        };
        vm.buildRemoveBlacklistAddress = function(number) {
          return function(e) {
            var newBlacklistAddresses;
            e.preventDefault();
            newBlacklistAddresses = vm.blacklistAddresses().filter(function(blacklistAddress, index) {
              return index !== number - 1;
            });
            vm.blacklistAddresses(newBlacklistAddresses);
          };
        };
        vm.save = function(e) {
          var apiArgs, apiCall, attributes;
          e.preventDefault();
          attributes = {
            name: vm.name(),
            description: vm.description(),
            blacklistAddresses: vm.blacklistAddresses(),
            swaps: vm.swaps()
          };
          if (vm.resourceId().length > 0) {
            apiCall = sbAdmin.api.updateBot;
            apiArgs = [vm.resourceId(), attributes];
          } else {
            apiCall = sbAdmin.api.newBot;
            apiArgs = [attributes];
          }
          return sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(function() {
            console.log("submit complete - routing to dashboard");
            m.route('dashboard');
          });
        };
      };
      return vm;
    })();
    sbAdmin.ctrl.botForm.controller = function() {
      vm.init();
    };
    return sbAdmin.ctrl.botForm.view = function() {
      return m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("h2", vm.resourceId() ? "Edit SwapBot " + (vm.name()) : "Create a New Swapbot"), m("div", {
              "class": "spacer1"
            }), sbAdmin.form.mForm({
              errors: vm.errorMessages,
              status: vm.formStatus
            }, {
              onsubmit: vm.save
            }, [
              sbAdmin.form.mAlerts(vm.errorMessages), sbAdmin.form.mFormField("Bot Name", {
                id: 'name',
                'placeholder': "Bot Name",
                required: true
              }, vm.name), sbAdmin.form.mFormField("Bot Description", {
                type: 'textarea',
                id: 'description',
                'placeholder': "Bot Description",
                required: true
              }, vm.description), m("hr"), m("h4", "Blacklisted Addresses"), m("p", [m("small", "Blacklisted addresses do not trigger swaps and can be used to load the SwapBot.")]), vm.blacklistAddresses().map(function(address, offset) {
                var number;
                number = offset + 1;
                return m("div", {
                  "class": "form-group"
                }, [
                  m("div", {
                    "class": "row"
                  }, [
                    m("div", {
                      "class": "col-md-5"
                    }, [
                      sbAdmin.form.mInputEl({
                        id: "blacklist_address_" + number,
                        'placeholder': "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                      }, address)
                    ]), m("div", {
                      "class": "col-md-1"
                    }, [
                      m("a", {
                        "class": "remove-link remove-link-compact",
                        href: '#remove',
                        onclick: vm.buildRemoveBlacklistAddress(number),
                        style: number === 1 ? {
                          display: 'none'
                        } : ""
                      }, [
                        m("span", {
                          "class": "glyphicon glyphicon-remove-circle",
                          title: "Remove Address " + number
                        }, '')
                      ])
                    ])
                  ])
                ]);
              }), m("div", {
                "class": "form-group"
              }, [
                m("a", {
                  "class": "",
                  href: '#add-address',
                  onclick: vm.addBlacklistAddress
                }, [
                  m("span", {
                    "class": "glyphicon glyphicon-plus"
                  }, ''), m("span", {}, ' Add Another Blacklist Address')
                ])
              ]), m("hr"), vm.swaps().map(function(swap, offset) {
                return swapGroup(offset + 1, swap);
              }), m("div", {
                "class": "form-group"
              }, [
                m("a", {
                  "class": "",
                  href: '#add',
                  onclick: vm.addSwap
                }, [
                  m("span", {
                    "class": "glyphicon glyphicon-plus"
                  }, ''), m("span", {}, ' Add Another Asset')
                ])
              ]), m("div", {
                "class": "spacer1"
              }), sbAdmin.form.mSubmitBtn("Save Bot"), m("a[href='/dashboard']", {
                "class": "btn btn-default pull-right",
                config: m.route
              }, "Return without Saving")
            ])
          ])
        ])
      ]);
    };
  })();

  (function() {
    var buildBalancesMElement, buildMLevel, closePusherChannel, handleBotBalancesMessage, handleBotEventMessage, serializeSwaps, subscribeToPusherChannel, swapGroup, vm;
    sbAdmin.ctrl.botView = {};
    swapGroup = function(number, swapProp) {
      return m("div", {
        "class": "asset-group"
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-4"
          }, [
            sbAdmin.form.mValueDisplay("Receives Asset", {
              id: "swap_in_" + number
            }, swapProp()["in"]())
          ]), m("div", {
            "class": "col-md-4"
          }, [
            sbAdmin.form.mValueDisplay("Sends Asset", {
              id: "swap_out_" + number
            }, swapProp().out())
          ]), m("div", {
            "class": "col-md-4"
          }, [
            sbAdmin.form.mValueDisplay("Rate", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_rate_" + number
            }, swapProp().rate())
          ])
        ])
      ]);
    };
    serializeSwaps = function(swap) {
      var out;
      out = [];
      out.push(swap);
      return out;
    };
    subscribeToPusherChannel = function(channelName, callbackFn) {
      var client;
      client = new window.Faye.Client("" + window.PUSHER_URL + "/public");
      client.subscribe("/" + channelName, function(data) {
        callbackFn(data);
      });
      return client;
    };
    closePusherChannel = function(client) {
      client.disconnect();
    };
    handleBotEventMessage = function(data) {
      var _ref, _ref1;
      console.log("pusher received:", data);
      console.log("msg:", data != null ? (_ref = data.event) != null ? _ref.msg : void 0 : void 0);
      if (data != null ? (_ref1 = data.event) != null ? _ref1.msg : void 0 : void 0) {
        vm.botEvents().unshift(data);
        m.redraw(true);
      }
    };
    handleBotBalancesMessage = function(data) {
      console.log("bot balances:", data);
      if (data != null) {
        vm.updateBalances(data);
        m.redraw(true);
      }
    };
    buildMLevel = function(levelNumber) {
      switch (levelNumber) {
        case 100:
          return m('span', {
            "class": "label label-default debug"
          }, "Debug");
        case 200:
          return m('span', {
            "class": "label label-info info"
          }, "Info");
        case 250:
          return m('span', {
            "class": "label label-primary primary"
          }, "Notice");
        case 300:
          return m('span', {
            "class": "label label-warning warning"
          }, "Warning");
        case 400:
          return m('span', {
            "class": "label label-danger danger"
          }, "Error");
        case 500:
          return m('span', {
            "class": "label label-danger danger"
          }, "Critical");
        case 550:
          return m('span', {
            "class": "label label-danger danger"
          }, "Alert");
        case 600:
          return m('span', {
            "class": "label label-danger danger"
          }, "Emergency");
      }
      return m('span', {
        "class": "label label-danger danger"
      }, "Code " + levelNumber);
    };
    buildBalancesMElement = function(balances) {
      if (vm.balances().length > 0) {
        return m("table", {
          "class": "table table-condensed table-striped"
        }, [
          m("thead", {}, [
            m("tr", {}, [
              m('th', {
                style: {
                  width: '40%'
                }
              }, 'Asset'), m('th', {
                style: {
                  width: '60%'
                }
              }, 'Balance')
            ])
          ]), m("tbody", {}, [
            vm.balances().map(function(balance, index) {
              return m("tr", {}, [m('td', balance.asset), m('td', balance.val)]);
            })
          ])
        ]);
      } else {
        return m("div", {
          "class": "form-group"
        }, "No Balances Found");
      }
    };
    vm = sbAdmin.ctrl.botView.vm = (function() {
      var buildBalancesPropValue, buildSwapsPropValue, newSwapProp;
      buildSwapsPropValue = function(swaps) {
        var out, swap, _i, _len;
        out = [];
        for (_i = 0, _len = swaps.length; _i < _len; _i++) {
          swap = swaps[_i];
          out.push(newSwapProp(swap));
        }
        return out;
      };
      newSwapProp = function(swap) {
        if (swap == null) {
          swap = {};
        }
        return m.prop({
          "in": m.prop(swap["in"] || ''),
          out: m.prop(swap.out || ''),
          rate: m.prop(swap.rate || '')
        });
      };
      buildBalancesPropValue = function(balances) {
        var asset, out, val;
        out = [];
        for (asset in balances) {
          val = balances[asset];
          out.push({
            asset: asset,
            val: val
          });
        }
        console.log("buildBalancesPropValue out=", out);
        return out;
      };
      vm = {};
      vm.updateBalances = function(newBalances) {
        vm.balances(buildBalancesPropValue(newBalances));
      };
      vm.init = function() {
        var id;
        vm.errorMessages = m.prop([]);
        vm.formStatus = m.prop('active');
        vm.resourceId = m.prop('new');
        vm.pusherClient = m.prop(null);
        vm.botEvents = m.prop([]);
        vm.name = m.prop('');
        vm.description = m.prop('');
        vm.address = m.prop('');
        vm.active = m.prop('');
        vm.swaps = m.prop(buildSwapsPropValue([]));
        vm.balances = m.prop(buildBalancesPropValue([]));
        id = m.route.param('id');
        sbAdmin.api.getBot(id).then(function(botData) {
          console.log("botData", botData);
          vm.resourceId(botData.id);
          vm.name(botData.name);
          vm.address(botData.address);
          vm.active(botData.active);
          vm.description(botData.description);
          vm.swaps(buildSwapsPropValue(botData.swaps));
          vm.balances(buildBalancesPropValue(botData.balances));
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        sbAdmin.api.getBotEvents(id).then(function(apiResponse) {
          vm.botEvents(apiResponse);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        vm.pusherClient(subscribeToPusherChannel("swapbot_events_" + id, handleBotEventMessage));
        vm.pusherClient(subscribeToPusherChannel("swapbot_balances_" + id, handleBotBalancesMessage));
        console.log("vm.pusherClient=", vm.pusherClient());
      };
      return vm;
    })();
    sbAdmin.ctrl.botView.controller = function() {
      this.onunload = function(e) {
        console.log("unload bot view vm.pusherClient()=", vm.pusherClient());
        closePusherChannel(vm.pusherClient());
      };
      vm.init();
    };
    sbAdmin.ctrl.botView.view = function() {
      console.log("vm.balances()=", vm.balances());
      return m("div", [
        m("h2", "SwapBot " + (vm.name())), m("div", {
          "class": "spacer1"
        }), m("div", {
          "class": "bot-view"
        }, [
          sbAdmin.form.mAlerts(vm.errorMessages), m("div", {
            "class": "row"
          }, [
            m("div", {
              "class": "col-md-8"
            }, [
              m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.form.mValueDisplay("Bot Name", {
                    id: 'name'
                  }, vm.name())
                ]), m("div", {
                  "class": "col-md-5"
                }, [
                  sbAdmin.form.mValueDisplay("Address", {
                    id: 'address'
                  }, vm.address() ? vm.address() : m("span", {
                    "class": 'no'
                  }, "[ none ]"))
                ]), m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Status", {
                    id: 'status'
                  }, vm.active() ? m("span", {
                    "class": 'yes'
                  }, "Active") : m("span", {
                    "class": 'no'
                  }, "Inactive"))
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  sbAdmin.form.mValueDisplay("Bot Description", {
                    id: 'description'
                  }, vm.description())
                ])
              ])
            ]), m("div", {
              "class": "col-md-4"
            }, [
              sbAdmin.form.mValueDisplay("Balances", {
                id: 'balances'
              }, buildBalancesMElement(vm.balances()))
            ])
          ]), m("hr"), vm.swaps().map(function(swap, offset) {
            return swapGroup(offset + 1, swap);
          }), m("hr"), m("div", {
            "class": "bot-events"
          }, [
            m("h3", "Events"), m("ul", {
              "class": "list-unstyled striped-list event-list"
            }, [
              vm.botEvents().map(function(botEventObj) {
                var dateObj, _ref;
                dateObj = window.moment(botEventObj.createdAt);
                return m("li", {
                  "class": "event"
                }, [
                  m("div", {
                    "class": "labelWrapper"
                  }, buildMLevel(botEventObj.level)), m("span", {
                    "class": "date",
                    title: dateObj.format('MMMM Do YYYY, h:mm:ss a')
                  }, dateObj.format('MMM D h:mm a')), m("span", {
                    "class": "msg"
                  }, (_ref = botEventObj.event) != null ? _ref.msg : void 0)
                ]);
              })
            ])
          ]), m("div", {
            "class": "spacer2"
          }), m("a[href='/edit/bot/" + (vm.resourceId()) + "']", {
            "class": "btn btn-success",
            config: m.route
          }, "Edit This Bot"), m("a[href='/dashboard']", {
            "class": "btn btn-default pull-right",
            config: m.route
          }, "Back to Dashboard")
        ])
      ]);
    };
    return sbAdmin.ctrl.botView.UnloadEvent;
  })();

  (function() {
    var listSwapbots, vm;
    sbAdmin.ctrl.dashboard = {};
    listSwapbots = function() {
      return sbAdmin.api.getBots().then(function(botsList) {
        return m.prop(botsList);
      });
    };
    vm = sbAdmin.ctrl.dashboard.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.user = m.prop(sbAdmin.auth.getUser());
        vm.bots = m.prop([]);
        sbAdmin.api.getAllBots().then(function(botsList) {
          vm.bots(botsList);
        });
      };
      return vm;
    })();
    sbAdmin.ctrl.dashboard.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.dashboard.view = function() {
      return m("div", [
        m("h2", "Welcome, " + (vm.user().name)), m("div", {
          "class": "spacer1"
        }), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-6 col-lg-4"
          }, [
            m("ul", {
              "class": "list-unstyled striped-list bot-list"
            }, [
              vm.bots().map(function(bot) {
                return m("li", {}, [
                  m("div", {}, [
                    m("a[href='/view/bot/" + bot.id + "']", {
                      "class": "",
                      config: m.route
                    }, "Bot " + bot.name), " ", m("a[href='/edit/bot/" + bot.id + "']", {
                      "class": "dashboard-edit-link pull-right",
                      config: m.route
                    }, [
                      m("span", {
                        "class": "glyphicon glyphicon-edit",
                        title: "Edit Swapbot " + bot.name
                      }, ''), " Edit"
                    ])
                  ])
                ]);
              })
            ])
          ])
        ]), m("div", {
          "class": "spacer1"
        }), m("a[href='/edit/bot/new']", {
          "class": "btn btn-primary",
          config: m.route
        }, "Create a new Swapbot"), m("div", {
          "class": "spacer3"
        }), m("div", [
          m("a[href='/logout']", {
            config: m.route
          }, "Logout")
        ])
      ]);
    };
  })();

  (function() {
    var vm;
    sbAdmin.ctrl.login = {};
    vm = sbAdmin.ctrl.login.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.apiToken = m.prop('');
        vm.apiSecretKey = m.prop('');
        vm.errorMessage = m.prop('');
        vm.login = function(e) {
          e.preventDefault();
          vm.errorMessage('');
          sbAdmin.auth.login(vm.apiToken(), vm.apiSecretKey()).then(function() {
            return m.route('/dashboard');
          }, function(error) {
            vm.errorMessage(error.message);
          });
        };
      };
      return vm;
    })();
    sbAdmin.ctrl.login.controller = function() {
      vm.init();
    };
    return sbAdmin.ctrl.login.view = function() {
      return m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("h2", "Please Login to Continue"), m("p", "Enter your API credentials below to save them in your browser."), m("div", {
              "class": "spacer1"
            }), m("form", {
              onsubmit: vm.login
            }, [
              (function() {
                if (vm.errorMessage() === '') {
                  return null;
                }
                return m("div", {
                  "class": "alert alert-danger",
                  role: "alert"
                }, [m("strong", "An error occurred. "), m('span', vm.errorMessage())]);
              })(), m("div", {
                "class": "form-group"
              }, [
                m("label", {
                  "for": 'apiToken'
                }, "API Token"), m("input", {
                  id: 'apiToken',
                  "class": 'form-control',
                  placeholder: "Your API Token",
                  required: true,
                  onchange: m.withAttr("value", vm.apiToken),
                  value: vm.apiToken()
                })
              ]), m("div", {
                "class": "form-group"
              }, [
                m("label", {
                  "for": 'apiSecretKey'
                }, "API Secret Key"), m("input", {
                  type: 'password',
                  id: 'apiSecretKey',
                  "class": 'form-control',
                  placeholder: "Your API Secret Key",
                  required: true,
                  onchange: m.withAttr("value", vm.apiSecretKey),
                  value: vm.apiSecretKey()
                })
              ]), m("div", {
                "class": "spacer1"
              }), m("button", {
                type: 'submit',
                "class": 'btn btn-primary'
              }, "Save Credentials")
            ])
          ])
        ])
      ]);
    };
  })();

  (function() {
    sbAdmin.ctrl.logout = {};
    sbAdmin.ctrl.logout.controller = function() {
      sbAdmin.auth.logout();
    };
    return sbAdmin.ctrl.logout.view = function() {
      return m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("h2", "Logged Out"), m("p", "The API credentials have been cleared from your browser."), m("div", {
              "class": "spacer1"
            }), m("a[href='/login']", {
              config: m.route
            }, "Return to Login")
          ])
        ])
      ]);
    };
  })();

  m.route.mode = "hash";

  m.route(document.getElementById('admin'), "/dashboard", {
    "/login": sbAdmin.ctrl.login,
    "/logout": sbAdmin.ctrl.logout,
    "/dashboard": sbAdmin.ctrl.dashboard,
    "/edit/bot/:id": sbAdmin.ctrl.botForm,
    "/view/bot/:id": sbAdmin.ctrl.botView
  });

}).call(this);