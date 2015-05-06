(function() {
  var BotAPIActionCreator, BotConstants, BotStatusComponent, BotstreamEventActions, BotstreamStore, Dispatcher, RecentAndActiveSwapsComponent, RecentOrActiveSwapComponent, SwapAPIActionCreator, SwapInterfaceComponent, SwapMatcher, SwapTestView, SwapbotChoose, SwapbotComplete, SwapbotReceive, SwapbotWait, SwapsStore, SwapstreamEventActions, UserChoiceStore, UserInputActions, invariant, swapbot;

  if (typeof swapbot === "undefined" || swapbot === null) {
    swapbot = {};
  }

  swapbot.addressUtils = (function() {
    var exports;
    exports = {};
    exports.publicBotAddress = function(username, botId, location) {
      return "" + location.protocol + "//" + location.host + "/public/" + username + "/" + botId;
    };
    exports.poupupBotAddress = function(username, botId, location) {
      return exports.publicBotAddress(username, botId, location) + "/popup";
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.botUtils = (function() {
    var exports;
    exports = {};
    exports.formatConfirmations = function(confirmations) {
      if (confirmations == null) {
        return 0;
      }
      return window.numeral(confirmations).format('0');
    };
    exports.confirmationsProse = function(bot) {
      return "" + bot.confirmationsRequired + " " + (exports.confirmationsWord(bot));
    };
    exports.confirmationsWord = function(bot) {
      return "confirmation" + (bot.confirmationsRequired === 1 ? '' : 's');
    };
    exports.getStatusFromBot = function(bot) {
      if (bot.state === 'active') {
        return 'active';
      } else {
        return 'inactive';
      }
    };
    exports.newBotStatusFromEvent = function(oldState, botEvent) {
      var event, state;
      state = oldState;
      event = botEvent.event;
      switch (event.name) {
        case 'bot.stateChange':
          if (event.state === 'active') {
            state = 'active';
          } else {
            state = 'inactive';
          }
      }
      return state;
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.fnUtils = (function() {
    var callbackTimeouts, callbacksQueue, exports;
    exports = {};
    callbacksQueue = {};
    callbackTimeouts = {};
    exports.callOnceWithCallback = function(key, fn, newCallback, timeout) {
      var runFunctionCall;
      if (timeout == null) {
        timeout = 5000;
      }
      if ((callbacksQueue[key] != null) && callbacksQueue[key].length > 0) {
        return callbacksQueue[key].push(newCallback);
      } else {
        callbacksQueue[key] = [];
        callbacksQueue[key].push(newCallback);
        runFunctionCall = function() {
          return fn(function() {
            var callback, e, _i, _len, _ref, _results;
            _ref = callbacksQueue[key];
            _results = [];
            for (_i = 0, _len = _ref.length; _i < _len; _i++) {
              callback = _ref[_i];
              try {
                callback();
              } catch (_error) {
                e = _error;
                console.error(e);
              }
              delete callbacksQueue[key];
              clearTimeout(callbackTimeouts[key]);
              _results.push(delete callbackTimeouts[key]);
            }
            return _results;
          });
        };
        callbackTimeouts[key] = setTimeout(function() {
          return runFunctionCall();
        }, timeout);
        return runFunctionCall();
      }
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.pusher = (function() {
    var exports;
    exports = {};
    exports.subscribeToPusherChanel = function(chanelName, callbackFn) {
      var client;
      client = new window.Faye.Client("" + window.PUSHER_URL + "/public");
      client.subscribe("/" + chanelName, function(data) {
        callbackFn(data);
      });
      return client;
    };
    exports.closePusherChanel = function(client) {
      client.disconnect();
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.swapUtils = (function() {
    var buildDesc, buildInAmountFromOutAmount, exports;
    exports = {};
    buildDesc = {};
    buildDesc.rate = function(swap) {
      var inAmount, outAmount;
      outAmount = 1 * swap.rate;
      inAmount = 1;
      return "" + outAmount + " " + swap.out + " for " + inAmount + " " + swap["in"];
    };
    buildDesc.fixed = function(swap) {
      return "" + swap.out_qty + " " + swap.out + " for " + swap.in_qty + " " + swap["in"];
    };
    buildInAmountFromOutAmount = {};
    buildInAmountFromOutAmount.rate = function(outAmount, swap) {
      var inAmount;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      inAmount = outAmount / swap.rate;
      return inAmount;
    };
    buildInAmountFromOutAmount.fixed = function(outAmount, swap) {
      var inAmount;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      inAmount = outAmount / (swap.out_qty / swap.in_qty);
      return inAmount;
    };
    exports.exchangeDescription = function(swap) {
      return buildDesc[swap.strategy](swap);
    };
    exports.inAmountFromOutAmount = function(inAmount, swap) {
      inAmount = buildInAmountFromOutAmount[swap.strategy](inAmount, swap);
      if (inAmount === NaN) {
        inAmount = 0;
      }
      return inAmount;
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.botEventsService = (function() {
    var exports, loadBotEvents, subscribeToPusher;
    exports = {};
    loadBotEvents = function(bot, onBotEventData) {
      var botId;
      botId = bot.id;
      $.get("/api/v1/public/botevents/" + botId, (function(_this) {
        return function(data) {
          var botEvent, _i, _len;
          data.sort(function(a, b) {
            return a.serial - b.serial;
          });
          for (_i = 0, _len = data.length; _i < _len; _i++) {
            botEvent = data[_i];
            onBotEventData(botEvent);
          }
        };
      })(this));
    };
    subscribeToPusher = function(bot, onBotEventData) {
      return swapbot.pusher.subscribeToPusherChanel("swapbot_events_" + bot.id, function(botEvent) {
        return onBotEventData(botEvent);
      });
    };
    exports.buildEventSubscriberForBot = function(bot) {
      var allEvents, clients, loaded, nextClientId, pusherClient, subscriberExports;
      loaded = false;
      pusherClient = null;
      allEvents = {};
      clients = {};
      nextClientId = 1;
      subscriberExports = {};
      subscriberExports.subscribe = function(clientOnBotEventData) {
        var localOnBotEventData, newClientId, pushAllEventsToClient;
        pushAllEventsToClient = function(clientFn) {
          var k, v;
          for (k in allEvents) {
            v = allEvents[k];
            clientFn(v);
          }
        };
        localOnBotEventData = function(botEvent) {
          var clientFn, clientId;
          if (allEvents[botEvent.serial] != null) {
            return;
          }
          allEvents[botEvent.serial] = botEvent;
          for (clientId in clients) {
            clientFn = clients[clientId];
            clientFn(botEvent);
          }
        };
        newClientId = nextClientId++;
        clients[newClientId] = clientOnBotEventData;
        if (!loaded) {
          loadBotEvents(bot, localOnBotEventData);
          pusherClient = subscribeToPusher(bot, localOnBotEventData);
          loaded = true;
        }
        pushAllEventsToClient(clientOnBotEventData);
        return newClientId;
      };
      subscriberExports.unsubscribe = function(oldClientId) {
        if (clients[oldClientId] != null) {
          delete clients[oldClientId];
        }
      };
      return subscriberExports;
    };
    return exports;
  })();

  BotStatusComponent = null;

  (function() {
    var getViewState;
    getViewState = function() {
      return {
        lastEvent: BotstreamStore.getLastEvent()
      };
    };
    return BotStatusComponent = React.createClass({
      displayName: 'BotStatusComponent',
      getInitialState: function() {
        return getViewState();
      },
      _onChange: function() {
        this.setState(getViewState());
      },
      componentDidMount: function() {
        BotstreamStore.addChangeListener(this._onChange);
      },
      componentWillUnmount: function() {
        BotstreamStore.removeChangeListener(this._onChange);
      },
      render: function() {
        var isActive, lastEvent;
        lastEvent = this.state.lastEvent;
        isActive = lastEvent != null ? lastEvent.isActive : false;
        return React.createElement("div", null, (isActive ? React.createElement("div", null, React.createElement("div", {
          "className": "status-dot bckg-green"
        }), "Active") : React.createElement("div", null, React.createElement("div", {
          "className": "status-dot bckg-red"
        }), "Inactive")), React.createElement("button", {
          "className": "button-question"
        }));
      }
    });
  })();

  RecentAndActiveSwapsComponent = null;

  RecentOrActiveSwapComponent = null;

  (function() {
    var getViewState;
    getViewState = function() {
      return {
        swaps: SwapsStore.getSwaps()
      };
    };
    RecentOrActiveSwapComponent = React.createClass({
      displayName: 'RecentOrActiveSwapComponent',
      getInitialState: function() {
        return {
          fromNow: null
        };
      },
      componentDidMount: function() {
        this.updateNow();
        this.intervalTimer = setInterval((function(_this) {
          return function() {
            return _this.updateNow();
          };
        })(this), 1000);
      },
      updateNow: function() {
        this.setState({
          fromNow: moment(this.props.swap.updatedAt).fromNow()
        });
      },
      componentWillUnmount: function() {
        if (this.intervalTimer != null) {
          clearInterval(this.intervalTimer);
        }
      },
      render: function() {
        var bot, icon, swap;
        swap = this.props.swap;
        bot = this.props.bot;
        icon = 'pending';
        if (swap.isError) {
          icon = 'failed';
        } else if (swap.isComplete) {
          icon = 'confirmed';
        }
        return React.createElement("li", {
          "className": icon
        }, React.createElement("div", {
          "className": "status-icon icon-" + icon
        }), React.createElement("div", {
          "className": "status-content"
        }, React.createElement("span", null, React.createElement("div", {
          "className": "date"
        }, this.state.fromNow), React.createElement("span", null, swap.message), React.createElement("br", null), React.createElement("small", null, "Waiting for ", swapbot.botUtils.confirmationsProse(bot), " to send ", swap.quantityOut, " ", swap.assetOut))));
      }
    });
    return RecentAndActiveSwapsComponent = React.createClass({
      displayName: 'RecentAndActiveSwapsComponent',
      getInitialState: function() {
        return getViewState();
      },
      _onChange: function() {
        return this.setState(getViewState());
      },
      componentDidMount: function() {
        SwapsStore.addChangeListener(this._onChange);
      },
      componentWillUnmount: function() {
        SwapsStore.removeChangeListener(this._onChange);
      },
      activeSwaps: function() {
        var activeSwaps, swap, _i, _len, _ref;
        activeSwaps = [];
        _ref = this.state.swaps;
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          swap = _ref[_i];
          if (!swap.isComplete) {
            activeSwaps.push(swap);
          }
        }
        return activeSwaps;
      },
      recentSwaps: function() {
        var recentSwaps, swap, _i, _len, _ref;
        recentSwaps = [];
        _ref = this.state.swaps;
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          swap = _ref[_i];
          if (swap.isComplete) {
            recentSwaps.push(swap);
          }
        }
        return recentSwaps;
      },
      render: function() {
        var anyActiveSwaps, anyRecentSwaps, swap;
        if (!this.state.swaps) {
          return React.createElement("div", null, "No swaps");
        }
        anyActiveSwaps = false;
        anyRecentSwaps = false;
        return React.createElement("div", null, React.createElement("div", {
          "id": "active-swaps",
          "className": "section grid-100"
        }, React.createElement("h3", null, "Active Swaps"), React.createElement("ul", {
          "className": "swap-list"
        }, (function() {
          var _i, _len, _ref, _results;
          _ref = this.activeSwaps();
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            swap = _ref[_i];
            anyActiveSwaps = true;
            _results.push(React.createElement(RecentOrActiveSwapComponent, {
              "key": swap.id,
              "bot": this.props.bot,
              "swap": swap
            }));
          }
          return _results;
        }).call(this)), (!anyActiveSwaps ? React.createElement("div", {
          "className": "description"
        }, "No Active Swaps") : void 0)), React.createElement("div", {
          "className": "clearfix"
        }), React.createElement("div", {
          "id": "recent-swaps",
          "className": "section grid-100"
        }, React.createElement("h3", null, "Recent Swaps"), React.createElement("ul", {
          "className": "swap-list"
        }, (function() {
          var _i, _len, _ref, _results;
          _ref = this.recentSwaps();
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            swap = _ref[_i];
            anyRecentSwaps = true;
            _results.push(React.createElement(RecentOrActiveSwapComponent, {
              "key": swap.id,
              "bot": this.props.bot,
              "swap": swap
            }));
          }
          return _results;
        }).call(this)), (!anyRecentSwaps ? React.createElement("div", {
          "className": "description"
        }, "No Recent Swaps") : void 0), React.createElement("div", {
          "style": {
            textAlign: 'center'
          }
        }, React.createElement("button", {
          "className": "button-load-more"
        }, "Load more swaps..."))));
      }
    });
  })();

  SwapInterfaceComponent = null;

  (function() {
    var getViewState;
    getViewState = function() {
      return UserChoiceStore.getUserChoices();
    };
    return SwapInterfaceComponent = React.createClass({
      displayName: 'SwapInterfaceComponent',
      getInitialState: function() {
        return $.extend({}, getViewState());
      },
      _onUserChoiceChange: function() {
        return this.setState(getViewState());
      },
      componentDidMount: function() {
        UserChoiceStore.addChangeListener(this._onUserChoiceChange);
      },
      componentWillUnmount: function() {
        UserChoiceStore.removeChangeListener(this._onUserChoiceChange);
      },
      render: function() {
        return React.createElement("div", null, (this.props.bot != null ? React.createElement("div", null, (this.state.step === 'choose' ? React.createElement(SwapbotChoose, {
          "bot": this.props.bot
        }) : null), (this.state.step === 'receive' ? React.createElement(SwapbotReceive, {
          "bot": this.props.bot
        }) : null), (this.state.step === 'wait' ? React.createElement(SwapbotWait, {
          "bot": this.props.bot
        }) : null), (this.state.step === 'complete' ? React.createElement(SwapbotComplete, {
          "bot": this.props.bot
        }) : null)) : React.createElement("div", {
          "className": "loading"
        }, "Loading...")));
      }
    });
  })();

  SwapTestView = null;

  (function() {
    var getViewState;
    getViewState = function() {
      return {
        swaps: SwapsStore.getSwaps()
      };
    };
    SwapTestView = React.createClass({
      displayName: 'SwapTestView',
      getInitialState: function() {
        return getViewState();
      },
      _onChange: function() {
        return this.setState(getViewState());
      },
      componentDidMount: function() {
        SwapsStore.addChangeListener(this._onChange);
      },
      componentWillUnmount: function() {
        SwapsStore.removeChangeListener(this._onChange);
      },
      render: function() {
        var swap;
        return React.createElement("div", null, React.createElement("h2", null, "All Swaps"), React.createElement("ul", null, (function() {
          var _i, _len, _ref, _results;
          _ref = this.state.swaps;
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            swap = _ref[_i];
            _results.push(React.createElement("li", {
              "key": swap.id
            }, swap.address, " (", swap.id, ")"));
          }
          return _results;
        }).call(this)));
      }
    });
  })();

  SwapbotChoose = null;

  (function() {
    return SwapbotChoose = React.createClass({
      displayName: 'SwapbotChoose',
      getInitialState: function() {
        return {};
      },
      componentDidMount: function() {},
      buildChooseOutAsset: function(outAsset) {
        return (function(_this) {
          return function(e) {
            e.preventDefault();
            UserInputActions.chooseOutAsset(outAsset);
          };
        })(this);
      },
      render: function() {
        var bot, index, swapConfig;
        bot = this.props.bot;
        if (!bot) {
          return null;
        }
        return React.createElement("div", {
          "id": "swap-step-1"
        }, React.createElement("div", {
          "className": "section grid-50"
        }, React.createElement("h3", null, "Description"), React.createElement("div", {
          "className": "description"
        }, this.props.bot.description)), React.createElement("div", {
          "className": "section grid-50"
        }, React.createElement("h3", null, "Available Swaps"), React.createElement("div", {
          "id": "SwapsListComponent"
        }, (bot.swaps ? React.createElement("ul", {
          "id": "swaps-list",
          "className": "wide-list"
        }, (function() {
          var _i, _len, _ref, _results;
          _ref = bot.swaps;
          _results = [];
          for (index = _i = 0, _len = _ref.length; _i < _len; index = ++_i) {
            swapConfig = _ref[index];
            _results.push(React.createElement("li", {
              "key": "swapConfig" + index,
              "className": "chooseable swap"
            }, React.createElement("a", {
              "href": "#choose-swap",
              "onClick": this.buildChooseOutAsset(swapConfig.out)
            }, React.createElement("div", null, React.createElement("div", {
              "className": "item-header"
            }, swapConfig.out, " ", React.createElement("small", null, "(", bot.balances[swapConfig.out], " available)")), React.createElement("p", null, "Sends ", swapbot.swapUtils.exchangeDescription(swapConfig), "."), React.createElement("div", {
              "className": "icon-next"
            })))));
          }
          return _results;
        }).call(this)) : React.createElement("p", {
          "className": "description"
        }, "There are no swaps available.")))));
      }
    });
  })();

  SwapbotComplete = React.createClass({
    displayName: 'SwapbotComplete',
    subscriberId: null,
    componentDidMount: function() {
      this.subscriberId = this.props.eventSubscriber.subscribe((function(_this) {
        return function(botEvent) {
          var matchedTxInfo;
          if (botEventWatcher.botEventIsFinal(botEvent)) {
            matchedTxInfo = botEventWatcher.txInfoFromBotEvent(botEvent);
            return _this.setState({
              matchedTxInfo: matchedTxInfo
            });
          }
        };
      })(this));
      return;
    },
    componentWillUnmount: function() {
      if (this.subscriberId != null) {
        this.props.eventSubscriber.unsubscribe(this.subscriberId);
        this.subscriberId = null;
      }
    },
    getInitialState: function() {
      return {
        matchedTxInfo: null,
        success: true
      };
    },
    render: function() {
      var bot, swap, swapDetails;
      bot = this.props.bot;
      swapDetails = this.props.swapDetails;
      swap = swapDetails.swap;
      return React.createElement("div", {
        "id": "swapbot-container",
        "className": "section grid-100"
      }, React.createElement("div", {
        "id": "swap-step-4",
        "className": "content hidden"
      }, React.createElement("h2", null, "Successfully finished"), React.createElement("div", {
        "className": "x-button",
        "id": "swap-step-4-close"
      }), React.createElement("div", {
        "className": "segment-control"
      }, React.createElement("div", {
        "className": "line"
      }), "\x3Cbr\x3E", React.createElement("div", {
        "className": "dot"
      }), React.createElement("div", {
        "className": "dot"
      }), React.createElement("div", {
        "className": "dot"
      }), React.createElement("div", {
        "className": "dot selected"
      })), React.createElement("div", {
        "className": "icon-success center"
      }), React.createElement("p", null, "Exchanged ", React.createElement("b", null, "0.1 XXX"), " for ", React.createElement("b", null, "100,000 XXXX"), " with ", bot.address, "."), React.createElement("p", null, React.createElement("a", {
        "href": "/public/" + bot.username + "/swap/" + swap.id,
        "className": "details-link",
        "target": "_blank"
      }, "Transaction details ", React.createElement("i", {
        "className": "fa fa-arrow-circle-right"
      })))));
    }
  });

  SwapbotReceive = null;

  (function() {
    var SwapbotSendItem, getViewState;
    getViewState = function() {
      return {
        userChoices: UserChoiceStore.getUserChoices()
      };
    };
    SwapbotReceive = React.createClass({
      displayName: 'SwapbotReceive',
      getInitialState: function() {
        return $.extend({}, getViewState());
      },
      getMatchingSwapConfigsForOutputAsset: function() {
        var chosenOutAsset, filteredSwapConfigs, offset, otherSwapConfig, swapConfigs, _i, _len, _ref;
        filteredSwapConfigs = [];
        swapConfigs = (_ref = this.props.bot) != null ? _ref.swaps : void 0;
        chosenOutAsset = this.state.userChoices.outAsset;
        if (swapConfigs) {
          for (offset = _i = 0, _len = swapConfigs.length; _i < _len; offset = ++_i) {
            otherSwapConfig = swapConfigs[offset];
            if (otherSwapConfig.out === chosenOutAsset) {
              filteredSwapConfigs.push(otherSwapConfig);
            }
          }
        }
        return filteredSwapConfigs;
      },
      updateAmount: function(e) {
        var outAmount;
        outAmount = parseFloat($(e.target).val());
        UserInputActions.updateOutAmount(outAmount);
      },
      checkEnter: function(e) {
        var matchingSwapConfigs;
        if (e.keyCode === 13) {
          matchingSwapConfigs = this.getMatchingSwapConfigsForOutputAsset();
          if (!matchingSwapConfigs) {
            return;
          }
          if (matchingSwapConfigs.length === 1) {
            UserInputActions.chooseSwapConfig(matchingSwapConfigs[0]);
          }
        }
      },
      render: function() {
        var bot, defaultValue, matchedSwapConfig, matchingSwapConfigs, offset, outAsset;
        defaultValue = this.state.userChoices.outAmount;
        bot = this.props.bot;
        matchingSwapConfigs = this.getMatchingSwapConfigsForOutputAsset();
        outAsset = this.state.userChoices.outAsset;
        return React.createElement("div", {
          "id": "swapbot-container",
          "className": "section grid-100"
        }, React.createElement("div", {
          "id": "swap-step-2",
          "className": "content"
        }, React.createElement("h2", null, "Receiving transaction"), React.createElement("div", {
          "className": "segment-control"
        }, React.createElement("div", {
          "className": "line"
        }), React.createElement("br", null), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot selected"
        }), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot"
        })), React.createElement("table", {
          "className": "fieldset"
        }, React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
          "htmlFor": "token-available"
        }, outAsset, " available for purchase: ")), React.createElement("td", null, React.createElement("span", {
          "id": "token-available"
        }, bot.balances[outAsset], " ", outAsset))), React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
          "htmlFor": "token-amount"
        }, "I would like to purchase: ")), React.createElement("td", null, React.createElement("input", {
          "onChange": this.updateAmount,
          "onKeyUp": this.checkEnter,
          "type": "text",
          "id": "token-amount",
          "placeholder": '0',
          "defaultValue": defaultValue
        }), "\u00a0", outAsset))), React.createElement("div", {
          "id": "GoBackLink"
        }, React.createElement("a", {
          "id": "go-back",
          "onClick": UserInputActions.goBackOnClick,
          "href": "#go-back",
          "className": "shadow-link"
        }, "Go Back")), React.createElement("ul", {
          "id": "transaction-select-list",
          "className": "wide-list"
        }, ((function() {
          var _i, _len, _results;
          if (matchingSwapConfigs) {
            _results = [];
            for (offset = _i = 0, _len = matchingSwapConfigs.length; _i < _len; offset = ++_i) {
              matchedSwapConfig = matchingSwapConfigs[offset];
              _results.push(React.createElement(SwapbotSendItem, {
                "key": 'swap' + offset,
                "outAmount": this.state.userChoices.outAmount,
                "swap": matchedSwapConfig,
                "bot": bot
              }));
            }
            return _results;
          }
        }).call(this))), React.createElement("p", {
          "className": "description"
        }, "After receiving one of those token types, this bot will wait for ", React.createElement("b", null, swapbot.botUtils.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), ".")));
      }
    });
    return SwapbotSendItem = React.createClass({
      displayName: 'SwapbotSendItem',
      getInAmount: function() {
        var inAmount;
        inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, this.props.swap);
        return inAmount;
      },
      isChooseable: function() {
        if (this.getInAmount() > 0) {
          return true;
        }
        return false;
      },
      chooseSwap: function(e) {
        e.preventDefault();
        if (!this.isChooseable()) {
          return;
        }
        UserInputActions.chooseSwapConfig(this.props.swap);
      },
      render: function() {
        var inAmount, isChooseable, swap;
        swap = this.props.swap;
        inAmount = this.getInAmount();
        isChooseable = this.isChooseable();
        return React.createElement("li", {
          "className": 'choose-swap' + (isChooseable ? ' chooseable' : ' unchooseable')
        }, React.createElement("a", {
          "className": "choose-swap",
          "onClick": this.chooseSwap,
          "href": "#next-step"
        }, React.createElement("div", {
          "className": "item-header"
        }, "Send ", React.createElement("span", {
          "id": "token-value-1"
        }, inAmount), " ", swap["in"]), React.createElement("p", null, (isChooseable ? React.createElement("small", null, "Click the arrow to choose this swap") : React.createElement("small", null, "Enter an amount above"))), React.createElement("div", {
          "className": "icon-next"
        }), React.createElement("div", {
          "className": "clearfix"
        })));
      }
    });
  })();

  SwapbotWait = null;

  (function() {
    var SingleTransactionInfo, TransactionInfo, getViewState;
    getViewState = function() {
      var matchedSwaps, swaps, userChoices;
      userChoices = UserChoiceStore.getUserChoices();
      swaps = SwapsStore.getSwaps();
      matchedSwaps = SwapMatcher.buildMatchedSwaps(swaps, userChoices);
      return {
        userChoices: userChoices,
        swaps: swaps,
        matchedSwaps: matchedSwaps,
        anyMatchedSwaps: (matchedSwaps.length > 0 ? true : false)
      };
    };
    SwapbotWait = React.createClass({
      displayName: 'SwapbotWait',
      getInitialState: function() {
        return getViewState();
      },
      _onChange: function() {
        this.setState(getViewState());
      },
      componentDidMount: function() {
        SwapsStore.addChangeListener(this._onChange);
        UserChoiceStore.addChangeListener(this._onChange);
      },
      componentWillUnmount: function() {
        SwapsStore.removeChangeListener(this._onChange);
        UserChoiceStore.removeChangeListener(this._onChange);
      },
      render: function() {
        var bot, swap, swapConfig;
        bot = this.props.bot;
        swapConfig = this.state.userChoices.swapConfig;
        if (!swapConfig) {
          return null;
        }
        return React.createElement("div", {
          "id": "swapbot-container",
          "className": "section grid-100"
        }, React.createElement("div", {
          "id": "swap-step-2",
          "className": "content"
        }, React.createElement("h2", null, "Receiving transaction"), React.createElement("div", {
          "className": "segment-control"
        }, React.createElement("div", {
          "className": "line"
        }), React.createElement("br", null), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot selected"
        }), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot"
        })), React.createElement("table", {
          "className": "fieldset"
        }, React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
          "htmlFor": "token-available"
        }, swapConfig.out, " available for purchase: ")), React.createElement("td", null, React.createElement("span", {
          "id": "token-available"
        }, bot.balances[swapConfig.out], " ", swapConfig.out))), React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
          "htmlFor": "token-amount"
        }, "I would like to purchase: ")), React.createElement("td", null, React.createElement("input", {
          "disabled": true,
          "type": "text",
          "id": "token-amount",
          "placeholder": '0 ' + swapConfig.out,
          "defaultValue": this.state.userChoices.outAmount
        })))), React.createElement("div", {
          "id": "GoBackLink"
        }, React.createElement("a", {
          "id": "go-back",
          "onClick": UserInputActions.goBackOnClick,
          "href": "#go-back",
          "className": "shadow-link"
        }, "Go Back")), (this.state.userChoices.swap != null ? React.createElement(SingleTransactionInfo, {
          "bot": bot,
          "userChoices": this.state.userChoices
        }) : this.state.anyMatchedSwaps ? React.createElement("ul", {
          "id": "transaction-confirm-list",
          "className": "wide-list"
        }, (function() {
          var _i, _len, _ref, _results;
          _ref = this.state.matchedSwaps;
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            swap = _ref[_i];
            _results.push(React.createElement(TransactionInfo, {
              "key": swap.id,
              "bot": bot,
              "swap": swap
            }));
          }
          return _results;
        }).call(this)) : React.createElement("ul", {
          "id": "transaction-wait-list",
          "className": "wide-list"
        }, React.createElement("li", null, React.createElement("div", {
          "className": "status-icon icon-pending"
        }), "Waiting for ", React.createElement("strong", null, this.state.userChoices.inAmount, " ", this.state.userChoices.inAsset), " to be sent to ", bot.address, ".", React.createElement("br", null)))), React.createElement("p", {
          "className": "description"
        }, "After receiving one of those token types, this bot will wait for ", React.createElement("b", null, swapbot.botUtils.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), ".")));
      }
    });
    TransactionInfo = React.createClass({
      displayName: 'TransactionInfo',
      intervalTimer: null,
      componentDidMount: function() {
        this.updateNow();
        this.intervalTimer = setInterval((function(_this) {
          return function() {
            return _this.updateNow();
          };
        })(this), 1000);
      },
      updateNow: function() {
        this.setState({
          fromNow: moment(this.props.swap.updatedAt).fromNow()
        });
      },
      componentWillUnmount: function() {
        if (this.intervalTimer != null) {
          clearInterval(this.intervalTimer);
        }
      },
      getInitialState: function() {
        return {
          fromNow: ''
        };
      },
      clickedFn: function(e) {
        e.preventDefault();
        UserInputActions.chooseSwap(this.props.swap);
      },
      render: function() {
        var bot, swap;
        swap = this.props.swap;
        bot = this.props.bot;
        return React.createElement("li", {
          "className": "chooseable"
        }, React.createElement("a", {
          "onClick": this.clickedFn,
          "href": "#choose"
        }, React.createElement("div", {
          "className": "item-content"
        }, React.createElement("div", {
          "className": "item-header",
          "title": "{swap.name}"
        }, "Transaction Received"), React.createElement("p", {
          "className": "date"
        }, this.state.fromNow), React.createElement("p", null, swap.message), React.createElement("p", null, "This transaction has ", React.createElement("b", null, swap.confirmations, " out of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), ".")), React.createElement("div", {
          "className": "item-actions"
        }, React.createElement("div", {
          "className": "icon-next"
        }))), React.createElement("div", {
          "className": "clearfix"
        }));
      }
    });
    return SingleTransactionInfo = React.createClass({
      displayName: 'SingleTransactionInfo',
      intervalTimer: null,
      componentDidMount: function() {},
      updateNow: function() {},
      componentWillUnmount: function() {
        if (this.intervalTimer != null) {
          clearInterval(this.intervalTimer);
        }
      },
      getInitialState: function() {
        return {};
      },
      updateEmailValue: function(e) {
        e.preventDefault();
        UserInputActions.updateEmailValue(e.target.value);
      },
      submitEmailFn: function(e) {
        var email;
        e.preventDefault();
        email = this.props.userChoices.email.value;
        if (email.length < 1) {
          return;
        }
        return UserInputActions.submitEmail();
      },
      notMyTransactionClicked: function(e) {
        e.preventDefault();
        UserInputActions.clearSwap();
      },
      render: function() {
        var bot, emailValue, swap, userChoices;
        userChoices = this.props.userChoices;
        swap = userChoices.swap;
        bot = this.props.bot;
        emailValue = userChoices.email.value;
        return React.createElement("div", {
          "id": "swap-step-3",
          "className": "content"
        }, React.createElement("h2", null, "Waiting for confirmations"), React.createElement("div", {
          "className": "segment-control"
        }, React.createElement("div", {
          "className": "line"
        }), React.createElement("br", null), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot selected"
        }), React.createElement("div", {
          "className": "dot"
        })), React.createElement("div", {
          "className": "icon-loading center"
        }), React.createElement("p", null, swap.message, React.createElement("br", null), React.createElement("a", {
          "id": "not-my-transaction",
          "onClick": this.notMyTransactionClicked,
          "href": "#",
          "className": "shadow-link"
        }, "Not your transaction?")), React.createElement("p", null, "This transaction has ", React.createElement("b", null, swapbot.botUtils.formatConfirmations(swap.confirmations), " of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), " in and ", React.createElement("b", null, swapbot.botUtils.formatConfirmations(swap.confirmationsOut), " of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), " out."), (userChoices.email.emailErrorMsg ? React.createElement("p", {
          "className": "error"
        }, userChoices.email.emailErrorMsg, "  Please try again.") : null), (userChoices.email.submittedEmail ? React.createElement("p", null, React.createElement("strong", null, "Email address submitted."), "  Please check your email.") : (React.createElement("p", null, "Don\&t want to wait here?", React.createElement("br", null), "We can notify you when the transaction is done!"), React.createElement("form", {
          "action": "#submit-email",
          "onSubmit": this.submitEmailFn,
          "style": (userChoices.email.submittingEmail ? {
            opacity: 0.2
          } : null)
        }, React.createElement("table", {
          "className": "fieldset fieldset-other"
        }, React.createElement("tbody", null, React.createElement("tr", null, React.createElement("td", null, React.createElement("input", {
          "disabled": (userChoices.email.submittingEmail ? true : false),
          "required": true,
          "type": "email",
          "onChange": this.updateEmailValue,
          "id": "other-address",
          "placeholder": "example@example.com",
          "value": emailValue
        })), React.createElement("td", null, React.createElement("div", {
          "id": "icon-other-next",
          "className": "icon-next",
          "onClick": this.submitEmailFn
        })))))))));
      }
    });
  })();

  window.BotApp = {
    init: function(bot) {
      SwapsStore.init();
      BotstreamStore.init();
      UserChoiceStore.init();
      BotAPIActionCreator.subscribeToBotstream(bot.id);
      SwapAPIActionCreator.subscribeToSwapstream(bot.id);
      React.render(React.createElement(BotStatusComponent, {
        "bot": bot
      }), document.getElementById('BotStatusComponent'));
      React.render(React.createElement(RecentAndActiveSwapsComponent, {
        "bot": bot
      }), document.getElementById('RecentAndActiveSwapsComponent'));
      return React.render(React.createElement(SwapInterfaceComponent, {
        "bot": bot
      }), document.getElementById('SwapInterfaceComponent'));
    }
  };

  BotAPIActionCreator = (function() {
    var exports, handleBotstreamEvents, subscriberId;
    exports = {};
    subscriberId = null;
    handleBotstreamEvents = function(botstreamEvents) {
      BotstreamEventActions.handleBotstreamEvents(botstreamEvents);
    };
    exports.subscribeToBotstream = function(botId) {
      subscriberId = swapbot.pusher.subscribeToPusherChanel("swapbot_botstream_" + botId, function(botstreamEvent) {
        return handleBotstreamEvents([botstreamEvent]);
      });
      $.get("/api/v1/public/boteventstream/" + botId, (function(_this) {
        return function(botstreamEvents) {
          botstreamEvents.sort(function(a, b) {
            return a.serial - b.serial;
          });
          handleBotstreamEvents(botstreamEvents);
        };
      })(this));
    };
    return exports;
  })();

  SwapAPIActionCreator = (function() {
    var exports, handleSwapstreamEvents, subscriberId;
    exports = {};
    subscriberId = null;
    handleSwapstreamEvents = function(swapstreamEvents) {
      SwapstreamEventActions.handleSwapstreamEvents(swapstreamEvents);
    };
    exports.loadSwapsFromAPI = function(botId) {
      $.get("/api/v1/public/swaps/" + botId, function(swapsData) {
        SwapstreamEventActions.addNewSwaps(swapsData);
      });
    };
    exports.subscribeToSwapstream = function(botId) {
      subscriberId = swapbot.pusher.subscribeToPusherChanel("swapbot_swapstream_" + botId, function(swapstreamEvent) {
        handleSwapstreamEvents([swapstreamEvent]);
      });
      $.get("/api/v1/public/swapevents/" + botId, (function(_this) {
        return function(swapstreamEvents) {
          swapstreamEvents.sort(function(a, b) {
            return a.serial - b.serial;
          });
          handleSwapstreamEvents(swapstreamEvents);
        };
      })(this));
    };
    return exports;
  })();

  BotstreamEventActions = (function() {
    var exports;
    exports = {};
    exports.handleBotstreamEvents = function(botstreamEvents) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_HANDLE_NEW_BOTSTREAM_EVENTS,
        botstreamEvents: botstreamEvents
      });
    };
    return exports;
  })();

  SwapstreamEventActions = (function() {
    var exports;
    exports = {};
    exports.addNewSwaps = function(swaps) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_ADD_NEW_SWAPS,
        swaps: swaps
      });
    };
    exports.handleSwapstreamEvents = function(swapstreamEvents) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS,
        swapstreamEvents: swapstreamEvents
      });
    };
    return exports;
  })();

  UserInputActions = (function() {
    var exports;
    exports = {};
    exports.chooseOutAsset = function(chosenOutAsset) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_OUT_ASSET,
        outAsset: chosenOutAsset
      });
    };
    exports.chooseSwapConfig = function(chosenSwapConfig) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG,
        swapConfig: chosenSwapConfig
      });
    };
    exports.updateOutAmount = function(newOutAmount) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_OUT_AMOUNT,
        outAmount: newOutAmount
      });
    };
    exports.chooseSwap = function(swap) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_SWAP,
        swap: swap
      });
    };
    exports.clearSwap = function() {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CLEAR_SWAP
      });
    };
    exports.updateEmailValue = function(email) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_UPDATE_EMAIL_VALUE,
        email: email
      });
    };
    exports.submitEmail = function() {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_SUBMIT_EMAIL
      });
    };
    exports.goBackOnClick = function(e) {
      e.preventDefault();
      exports.goBack();
    };
    exports.goBack = function() {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_GO_BACK
      });
    };
    return exports;
  })();

  BotConstants = (function() {
    var exports;
    exports = {};
    exports.BOT_ADD_NEW_SWAPS = 'BOT_ADD_NEW_SWAPS';
    exports.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS = 'BOT_HANDLE_NEW_SWAPSTREAM_EVENTS';
    exports.BOT_HANDLE_NEW_BOTSTREAM_EVENTS = 'BOT_HANDLE_NEW_BOTSTREAM_EVENTS';
    exports.BOT_USER_CHOOSE_OUT_ASSET = 'BOT_USER_CHOOSE_OUT_ASSET';
    exports.BOT_USER_CHOOSE_SWAP_CONFIG = 'BOT_USER_CHOOSE_SWAP_CONFIG';
    exports.BOT_USER_CHOOSE_SWAP = 'BOT_USER_CHOOSE_SWAP';
    exports.BOT_USER_CLEAR_SWAP = 'BOT_USER_CLEAR_SWAP';
    exports.BOT_USER_CHOOSE_OUT_AMOUNT = 'BOT_USER_CHOOSE_OUT_AMOUNT';
    exports.BOT_UPDATE_EMAIL_VALUE = 'BOT_UPDATE_EMAIL_VALUE';
    exports.BOT_USER_SUBMIT_EMAIL = 'BOT_USER_SUBMIT_EMAIL';
    exports.BOT_GO_BACK = 'BOT_GO_BACK';
    return exports;
  })();

  Dispatcher = (function() {
    var exports, _callbacks, _invokeCallback, _isDispatching, _isHandled, _isPending, _lastID, _pendingPayload, _prefix, _startDispatching, _stopDispatching;
    exports = {};
    _prefix = 'ID_';
    _lastID = 1;
    _callbacks = {};
    _isPending = {};
    _isHandled = {};
    _isDispatching = false;
    _pendingPayload = null;
    exports.sayHi = function() {};
    exports.register = function(callback) {
      var id;
      id = _prefix + _lastID++;
      _callbacks[id] = callback;
      return id;
    };
    exports.unregister = function(id) {
      invariant(_callbacks[id], 'Dispatcher.unregister(...): `%s` does not map to a registered callback.', id);
      delete _callbacks[id];
    };
    exports.waitFor = function(ids) {
      var id, ii;
      invariant(_isDispatching, 'Dispatcher.waitFor(...): Must be invoked while dispatching.');
      ii = 0;
      while (ii < ids.length) {
        id = ids[ii];
        if (_isPending[id]) {
          invariant(_isHandled[id], 'Dispatcher.waitFor(...): Circular dependency detected while ' + 'waiting for `%s`.', id);
          continue;
        }
        invariant(_callbacks[id], 'Dispatcher.waitFor(...): `%s` does not map to a registered callback.', id);
        _invokeCallback(id);
        ii++;
      }
    };
    exports.dispatch = function(payload) {
      var id;
      invariant(!_isDispatching, 'Dispatch.dispatch(...): Cannot dispatch in the middle of a dispatch.');
      _startDispatching(payload);
      try {
        for (id in _callbacks) {
          if (_isPending[id]) {
            continue;
          }
          _invokeCallback(id);
        }
      } finally {
        _stopDispatching();
      }
    };
    exports.isDispatching = function() {
      return _isDispatching;
    };
    _invokeCallback = function(id) {
      _isPending[id] = true;
      _callbacks[id](_pendingPayload);
      _isHandled[id] = true;
    };
    _startDispatching = function(payload) {
      var id;
      for (id in _callbacks) {
        _isPending[id] = false;
        _isHandled[id] = false;
      }
      _pendingPayload = payload;
      _isDispatching = true;
    };
    _stopDispatching = function() {
      _pendingPayload = null;
      _isDispatching = false;
    };
    return exports;
  })();

  invariant = function(condition, format, a, b, c, d, e, f) {
    var argIndex, args, error;
    if (typeof __DEV__ !== "undefined" && __DEV__ !== null) {
      if (format === void 0) {
        throw new Error('invariant requires an error message argument');
      }
    }
    if (!condition) {
      error = void 0;
      if (format === void 0) {
        error = new Error('Minified exception occurred; use the non-minified dev environment ' + 'for the full error message and additional helpful warnings.');
      } else {
        args = [a, b, c, d, e, f];
        argIndex = 0;
        error = new Error('Invariant Violation: ' + format.replace(/%s/g, function() {
          return args[argIndex++];
        }));
      }
      error.framesToPop = 1;
      throw error;
    }
  };

  BotstreamStore = (function() {
    var allMyBotstreamEvents, allMyBotstreamEventsById, buildEventFromStreamstreamEventWrapper, emitChange, eventEmitter, exports, handleBotstreamEvents, rebuildAllMyBotEvents;
    exports = {};
    allMyBotstreamEventsById = {};
    allMyBotstreamEvents = [];
    eventEmitter = null;
    handleBotstreamEvents = function(eventWrappers) {
      var anyChanged, event, eventId, eventWrapper, _i, _len;
      anyChanged = false;
      for (_i = 0, _len = eventWrappers.length; _i < _len; _i++) {
        eventWrapper = eventWrappers[_i];
        eventId = eventWrapper.id;
        event = eventWrapper.event;
        if (allMyBotstreamEventsById[eventId] != null) {
          allMyBotstreamEventsById[eventId] = buildEventFromStreamstreamEventWrapper(eventWrapper);
        } else {
          allMyBotstreamEventsById[eventId] = buildEventFromStreamstreamEventWrapper(eventWrapper);
        }
        anyChanged = true;
      }
      if (anyChanged) {
        allMyBotstreamEvents = rebuildAllMyBotEvents();
        emitChange();
      }
    };
    rebuildAllMyBotEvents = function() {
      var event, id, newAllMyBotstreamEvents;
      newAllMyBotstreamEvents = [];
      for (id in allMyBotstreamEventsById) {
        event = allMyBotstreamEventsById[id];
        newAllMyBotstreamEvents.push(event);
      }
      return newAllMyBotstreamEvents;
    };
    buildEventFromStreamstreamEventWrapper = function(eventWrapper) {
      var newEvent;
      newEvent = $.extend({}, eventWrapper.event);
      delete newEvent.name;
      newEvent.id = eventWrapper.id;
      newEvent.serial = eventWrapper.serial;
      newEvent.updatedAt = eventWrapper.createdAt;
      newEvent.message = eventWrapper.message;
      return newEvent;
    };
    emitChange = function() {
      eventEmitter.emitEvent('change');
    };
    exports.init = function() {
      eventEmitter = new window.EventEmitter();
      Dispatcher.register(function(action) {
        switch (action.actionType) {
          case BotConstants.BOT_HANDLE_NEW_BOTSTREAM_EVENTS:
            handleBotstreamEvents(action.botstreamEvents);
        }
      });
    };
    exports.getEvents = function() {
      return allMyBotstreamEvents;
    };
    exports.getLastEvent = function() {
      if (!allMyBotstreamEvents.length > 1) {
        return null;
      }
      return allMyBotstreamEvents[allMyBotstreamEvents.length - 1];
    };
    exports.addChangeListener = function(callback) {
      eventEmitter.addListener('change', callback);
    };
    exports.removeChangeListener = function(callback) {
      eventEmitter.removeListener('change', callback);
    };
    return exports;
  })();

  SwapsStore = (function() {
    var addNewSwaps, allMySwaps, allMySwapsById, buildSwapFromSwapEvent, emitChange, eventEmitter, exports, handleSwapstreamEvents, rebuildAllMySwaps;
    exports = {};
    allMySwapsById = {};
    allMySwaps = [];
    eventEmitter = null;
    addNewSwaps = function(newSwaps) {
      var swap, _i, _len;
      for (_i = 0, _len = newSwaps.length; _i < _len; _i++) {
        swap = newSwaps[_i];
        allMySwapsById[swap.id] = swap;
      }
      allMySwaps = rebuildAllMySwaps();
      emitChange();
    };
    handleSwapstreamEvents = function(eventWrappers) {
      var anyChanged, event, eventWrapper, newSwap, swapId, _i, _len;
      anyChanged = false;
      for (_i = 0, _len = eventWrappers.length; _i < _len; _i++) {
        eventWrapper = eventWrappers[_i];
        swapId = eventWrapper.swapUuid;
        event = eventWrapper.event;
        if (allMySwapsById[swapId] != null) {
          newSwap = buildSwapFromSwapEvent(eventWrapper);
          $.extend(allMySwapsById[swapId], newSwap);
        } else {
          allMySwapsById[swapId] = buildSwapFromSwapEvent(eventWrapper);
        }
        anyChanged = true;
      }
      if (anyChanged) {
        allMySwaps = rebuildAllMySwaps();
        emitChange();
      }
    };
    rebuildAllMySwaps = function() {
      var id, newAllMySwaps, swap;
      newAllMySwaps = [];
      for (id in allMySwapsById) {
        swap = allMySwapsById[id];
        newAllMySwaps.push(swap);
      }
      newAllMySwaps.sort(function(a, b) {
        return b.serial - a.serial;
      });
      return newAllMySwaps;
    };
    buildSwapFromSwapEvent = function(eventWrapper) {
      var newSwap;
      newSwap = $.extend({}, eventWrapper.event);
      delete newSwap.name;
      newSwap.id = eventWrapper.swapUuid;
      newSwap.serial = eventWrapper.serial;
      newSwap.updatedAt = eventWrapper.createdAt;
      if (eventWrapper.level >= 200) {
        newSwap.message = eventWrapper.message;
      } else {
        newSwap.debugMessage = eventWrapper.message;
      }
      return newSwap;
    };
    emitChange = function() {
      eventEmitter.emitEvent('change');
    };
    exports.init = function() {
      eventEmitter = new window.EventEmitter();
      Dispatcher.register(function(action) {
        switch (action.actionType) {
          case BotConstants.BOT_ADD_NEW_SWAPS:
            addNewSwaps(action.swaps);
            break;
          case BotConstants.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS:
            handleSwapstreamEvents(action.swapstreamEvents);
        }
      });
    };
    exports.getSwaps = function() {
      return allMySwaps;
    };
    exports.getSwapById = function(swapId) {
      if (allMySwapsById[swapId] == null) {
        return null;
      }
      return allMySwapsById[swapId];
    };
    exports.addChangeListener = function(callback) {
      eventEmitter.addListener('change', callback);
    };
    exports.removeChangeListener = function(callback) {
      eventEmitter.removeListener('change', callback);
    };
    return exports;
  })();

  UserChoiceStore = (function() {
    var clearChosenSwap, emitChange, eventEmitter, exports, goBack, initRouter, onRouteUpdate, onSwapStoreChanged, resetEmailChoices, resetUserChoices, routeToStepOrEmitChange, router, submitEmail, swapIsComplete, updateChosenOutAsset, updateChosenSwap, updateChosenSwapConfig, updateEmailValue, updateOutAmount, userChoices, _recalulateUserChoices;
    exports = {};
    userChoices = {
      step: 'choose',
      swapConfig: {},
      inAmount: null,
      inAsset: null,
      outAmount: null,
      outAsset: null,
      swap: null,
      email: {
        value: '',
        submitting: false,
        submitted: false,
        errorMsg: null
      }
    };
    router = null;
    eventEmitter = null;
    resetUserChoices = function() {
      userChoices.swapConfig = null;
      userChoices.inAmount = null;
      userChoices.inAsset = null;
      userChoices.outAmount = null;
      userChoices.outAsset = null;
      userChoices.swap = null;
      resetEmailChoices();
    };
    resetEmailChoices = function() {
      userChoices.email = {
        value: '',
        submitting: false,
        submitted: false,
        errorMsg: null
      };
    };
    updateChosenOutAsset = function(newChosenOutAsset) {
      if (userChoices.outAsset !== newChosenOutAsset) {
        userChoices.outAsset = newChosenOutAsset;
        routeToStepOrEmitChange('receive');
      }
    };
    updateChosenSwapConfig = function(newChosenSwapConfig) {
      var newName;
      newName = newChosenSwapConfig["in"] + ':' + newChosenSwapConfig.out;
      if ((userChoices.swapConfig == null) || userChoices.swapConfig.name !== newName) {
        userChoices.swapConfig = newChosenSwapConfig;
        userChoices.swapConfig.name = newName;
        _recalulateUserChoices();
        router.setRoute('wait');
      }
    };
    updateOutAmount = function(newOutAmount) {
      if (newOutAmount === userChoices.outAmount) {
        return;
      }
      userChoices.outAmount = newOutAmount;
      _recalulateUserChoices();
      emitChange();
    };
    updateChosenSwap = function(newChosenSwap) {
      if ((userChoices.swap == null) || userChoices.swap.id !== newChosenSwap.id) {
        userChoices.swap = newChosenSwap;
        console.log("updateChosenSwap newChosenSwap.isComplete=", newChosenSwap.isComplete);
        if (swapIsComplete(newChosenSwap)) {
          console.log("swapIsComplete = TRUE");
          return;
        }
        emitChange();
      }
    };
    clearChosenSwap = function() {
      if (userChoices.swap != null) {
        userChoices.swap = null;
        emitChange();
        resetEmailChoices();
      }
    };
    updateEmailValue = function(email) {
      if (email !== userChoices.email.value) {
        userChoices.email.value = email;
        emitChange();
      }
    };
    submitEmail = function() {
      var data;
      if (userChoices.email.submittingEmail) {
        return;
      }
      userChoices.email.submittingEmail = true;
      userChoices.email.emailErrorMsg = null;
      data = {
        email: userChoices.email.value,
        swapId: userChoices.swap.id
      };
      $.ajax({
        type: "POST",
        url: '/api/v1/public/customers',
        data: data,
        dataType: 'json',
        success: function(data) {
          if (data.id) {
            userChoices.email.submittedEmail = true;
            userChoices.email.submittingEmail = false;
            emitChange();
          }
        },
        error: function(jqhr, textStatus) {
          var errorMsg;
          data = jqhr.responseText ? $.parseJSON(jqhr.responseText) : null;
          if (data != null ? data.message : void 0) {
            errorMsg = data.message;
          } else {
            errorMsg = "An error occurred while trying to submit this email.";
          }
          console.error("Error: " + textStatus, data);
          userChoices.email.submittedEmail = false;
          userChoices.email.submittingEmail = false;
          userChoices.email.emailErrorMsg = errorMsg;
          emitChange();
        }
      });
      emitChange();
    };
    goBack = function() {
      switch (userChoices.step) {
        case 'receive':
          resetUserChoices();
          router.setRoute('/choose');
          break;
        case 'wait':
          userChoices.swapConfig = null;
          userChoices.inAmount = null;
          userChoices.inAsset = null;
          router.setRoute('/receive');
      }
    };
    swapIsComplete = function(newChosenSwap) {
      if (newChosenSwap.isComplete) {
        return true;
      }
      return false;
    };
    routeToStepOrEmitChange = function(newStep) {
      if (userChoices.step !== newStep) {
        router.setRoute('/' + newStep);
        return;
      }
      return emitChange();
    };
    emitChange = function() {
      eventEmitter.emitEvent('change');
    };
    _recalulateUserChoices = function() {
      if ((userChoices.outAmount != null) && (userChoices.swapConfig != null)) {
        userChoices.inAmount = swapbot.swapUtils.inAmountFromOutAmount(userChoices.outAmount, userChoices.swapConfig);
      }
      if (userChoices.swapConfig) {
        userChoices.outAsset = userChoices.swapConfig.out;
        userChoices.inAsset = userChoices.swapConfig["in"];
      }
    };
    onRouteUpdate = function(rawNewStep) {
      var newStep, valid;
      newStep = rawNewStep;
      valid = true;
      switch (rawNewStep) {
        case 'choose':
          valid = true;
          break;
        case 'receive':
        case 'wait':
        case 'complete':
          if (userChoices.outAsset === null) {
            valid = false;
          }
          break;
        default:
          valid = false;
      }
      if (!valid) {
        resetUserChoices();
        if (rawNewStep !== 'choose') {
          router.setRoute('/choose');
        }
        return false;
      }
      if (newStep === userChoices.step) {
        return false;
      }
      userChoices.step = newStep;
      switch (newStep) {
        case 'choose':
          resetUserChoices();
      }
      emitChange();
      return true;
    };
    initRouter = function() {
      router = Router({
        '/choose': onRouteUpdate.bind(null, 'choose'),
        '/receive': onRouteUpdate.bind(null, 'receive'),
        '/wait': onRouteUpdate.bind(null, 'wait'),
        '/complete': onRouteUpdate.bind(null, 'complete')
      });
      router.init(userChoices.step);
    };
    onSwapStoreChanged = function() {
      var _ref;
      if ((_ref = userChoices.swap) != null ? _ref.id : void 0) {
        userChoices.swap = SwapsStore.getSwapById(userChoices.swap.id);
        emitChange();
      }
    };
    exports.init = function() {
      eventEmitter = new window.EventEmitter();
      Dispatcher.register(function(action) {
        switch (action.actionType) {
          case BotConstants.BOT_USER_CHOOSE_OUT_ASSET:
            updateChosenOutAsset(action.outAsset);
            break;
          case BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG:
            updateChosenSwapConfig(action.swapConfig);
            break;
          case BotConstants.BOT_USER_CHOOSE_SWAP:
            updateChosenSwap(action.swap);
            break;
          case BotConstants.BOT_USER_CLEAR_SWAP:
            clearChosenSwap();
            break;
          case BotConstants.BOT_USER_CHOOSE_OUT_AMOUNT:
            updateOutAmount(action.outAmount);
            break;
          case BotConstants.BOT_UPDATE_EMAIL_VALUE:
            updateEmailValue(action.email);
            break;
          case BotConstants.BOT_USER_SUBMIT_EMAIL:
            submitEmail();
            break;
          case BotConstants.BOT_GO_BACK:
            goBack();
        }
      });
      resetUserChoices();
      initRouter();
      SwapsStore.addChangeListener(function() {
        onSwapStoreChanged();
      });
    };
    exports.getUserChoices = function() {
      return userChoices;
    };
    exports.addChangeListener = function(callback) {
      eventEmitter.addListener('change', callback);
    };
    exports.removeChangeListener = function(callback) {
      eventEmitter.removeListener('change', callback);
    };
    return exports;
  })();

  SwapMatcher = (function() {
    var exports, swapIsMatched;
    exports = {};
    swapIsMatched = function(swap, userChoices) {
      if (swap.assetIn = userChoices.inAsset && swap.quantityIn === userChoices.inAmount) {
        return true;
      }
      return false;
    };
    exports.buildMatchedSwaps = function(swaps, userChoices) {
      var matchedSwaps, swap, _i, _len;
      matchedSwaps = [];
      for (_i = 0, _len = swaps.length; _i < _len; _i++) {
        swap = swaps[_i];
        if (swapIsMatched(swap, userChoices)) {
          matchedSwaps.push(swap);
        }
      }
      return matchedSwaps;
    };
    return exports;
  })();

}).call(this);
