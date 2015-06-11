(function() {
  var BotAPIActionCreator, BotConstants, BotStatusComponent, BotstreamEventActions, BotstreamStore, Dispatcher, PlaceOrderInput, QuotebotActionCreator, QuotebotEventActions, QuotebotStore, ReactZeroClipboard, RecentAndActiveSwapsComponent, RecentOrActiveSwapComponent, SwapAPIActionCreator, SwapMatcher, SwapPurchaseStepsComponent, SwapTestView, SwapbotChoose, SwapbotComplete, SwapbotPlaceOrder, SwapbotReceivingTransaction, SwapbotWait, SwapsStore, SwapstreamEventActions, UserChoiceStore, UserInputActions, invariant, swapbot;

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

  swapbot.formatters = (function() {
    var SATOSHI, exports;
    exports = {};
    SATOSHI = 100000000;
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
    exports.satoshisToValue = function(amount, currencyPostfix) {
      if (currencyPostfix == null) {
        currencyPostfix = 'BTC';
      }
      return exports.formatCurrency(amount / SATOSHI, currencyPostfix);
    };
    exports.formatCurrency = function(value, currencyPostfix) {
      if (currencyPostfix == null) {
        currencyPostfix = '';
      }
      if ((value == null) || isNaN(value)) {
        return '';
      }
      return window.numeral(value).format('0,0.[00000000]') + ((currencyPostfix != null ? currencyPostfix.length : void 0) ? ' ' + currencyPostfix : '');
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
    exports.subscribeToPusherChanel = function(pusherURL, channelName, callbackFn) {
      var client;
      if (callbackFn == null) {
        callbackFn = channelName;
        channelName = pusherURL;
        pusherURL = window.PUSHER_URL;
      }
      client = new window.Faye.Client("" + pusherURL + "/public");
      client.subscribe("/" + channelName, function(data) {
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
    var buildDesc, buildInAmountFromOutAmount, exports, validateOutAmount;
    exports = {};
    buildDesc = {};
    buildDesc.rate = function(swapConfig) {
      var formatCurrency, inAmount, outAmount;
      outAmount = 1 * swapConfig.rate;
      inAmount = 1;
      formatCurrency = swapbot.formatters.formatCurrency;
      return "This bot will send you " + (formatCurrency(outAmount)) + " " + swapConfig.out + " for every " + (formatCurrency(inAmount)) + " " + swapConfig["in"] + " you deposit.";
    };
    buildDesc.fixed = function(swapConfig) {
      var formatCurrency;
      formatCurrency = swapbot.formatters.formatCurrency;
      return "This bot will send you " + (formatCurrency(swapConfig.out_qty)) + " " + swapConfig.out + " for every " + (formatCurrency(swapConfig.in_qty)) + " " + swapConfig["in"] + " you deposit.";
    };
    buildDesc.fiat = function(swapConfig) {
      var cost, formatCurrency, outAmount;
      formatCurrency = swapbot.formatters.formatCurrency;
      outAmount = 1;
      cost = swapConfig.cost;
      return "This bot will send you " + (formatCurrency(outAmount)) + " " + swapConfig.out + " for every $" + (formatCurrency(swapConfig.cost)) + " USD worth of " + swapConfig["in"] + " you deposit.";
    };
    buildInAmountFromOutAmount = {};
    buildInAmountFromOutAmount.rate = function(outAmount, swapConfig) {
      var inAmount;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      inAmount = outAmount / swapConfig.rate;
      return inAmount;
    };
    buildInAmountFromOutAmount.fixed = function(outAmount, swapConfig) {
      var inAmount;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      inAmount = outAmount / (swapConfig.out_qty / swapConfig.in_qty);
      return inAmount;
    };
    buildInAmountFromOutAmount.fiat = function(outAmount, swapConfig, currentRate) {
      var cost, inAmount, marketBuffer, maxMarketBuffer, maxMarketBufferValue;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      if (currentRate === 0) {
        return 0;
      }
      cost = swapConfig.cost;
      if (swapConfig.divisible) {
        marketBuffer = 0;
      } else {
        marketBuffer = 0.02;
        maxMarketBufferValue = cost * 0.40;
        maxMarketBuffer = maxMarketBufferValue / outAmount;
        if (marketBuffer > maxMarketBuffer) {
          marketBuffer = maxMarketBuffer;
        }
      }
      inAmount = outAmount * cost / currentRate * (1 + marketBuffer);
      return inAmount;
    };
    validateOutAmount = {};
    validateOutAmount.shared = function(outAmount, swapConfig) {
      if (("" + outAmount).length === 0) {
        return null;
      }
      if (isNaN(outAmount)) {
        return 'The amount to purchase does not look like a number.';
      }
      return null;
    };
    validateOutAmount.rate = function(outAmount, swapConfig) {
      var errorMsg;
      errorMsg = validateOutAmount.shared(outAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    validateOutAmount.fixed = function(outAmount, swapConfig) {
      var errorMsg, formatCurrency, ratio;
      errorMsg = validateOutAmount.shared(outAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      ratio = outAmount / swapConfig.out_qty;
      if (ratio !== Math.floor(ratio)) {
        formatCurrency = swapbot.formatters.formatCurrency;
        return "This swap must be purchased at a rate of exactly " + (formatCurrency(swapConfig.out_qty)) + " " + swapConfig.out + " for every " + (formatCurrency(swapConfig.in_qty)) + " " + swapConfig["in"] + ".";
      }
      return null;
    };
    validateOutAmount.fiat = function(outAmount, swapConfig) {
      var errorMsg, formatCurrency;
      errorMsg = validateOutAmount.shared(outAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      if ((swapConfig.min_out != null) && outAmount > 0 && outAmount < swapConfig.min_out) {
        formatCurrency = swapbot.formatters.formatCurrency;
        return "To use this swap, you must purchase at least " + (formatCurrency(swapConfig.min_out)) + " " + swapConfig.out + ".";
      }
      return null;
    };
    exports.exchangeDescription = function(swapConfig) {
      return buildDesc[swapConfig.strategy](swapConfig);
    };
    exports.inAmountFromOutAmount = function(inAmount, swapConfig, currentRate) {
      inAmount = buildInAmountFromOutAmount[swapConfig.strategy](inAmount, swapConfig, currentRate);
      if (inAmount === NaN) {
        inAmount = 0;
      }
      return inAmount;
    };
    exports.validateOutAmount = function(outAmount, swapConfig) {
      var errorMsg;
      errorMsg = validateOutAmount[swapConfig.strategy](outAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.zeroClipboard = (function() {
    var exports;
    exports = {};
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
        var ts;
        ts = this.props.swap.completedAt != null ? this.props.swap.completedAt : this.props.swap.updatedAt;
        this.setState({
          fromNow: moment(ts).fromNow()
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
        }, this.state.fromNow), React.createElement("span", null, swap.message, (swap.isComplete ? React.createElement("a", {
          "href": "/public/" + bot.username + "/swap/" + swap.id,
          "className": "details-link",
          "target": "_blank"
        }, React.createElement("i", {
          "className": "fa fa-arrow-circle-right"
        })) : void 0)), (!swap.isComplete ? React.createElement("div", null, React.createElement("small", null, "Waiting for ", swapbot.formatters.confirmationsProse(bot), " to send ", swap.quantityOut, " ", swap.assetOut)) : void 0))));
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

  PlaceOrderInput = null;

  (function() {
    var getViewState;
    getViewState = function() {
      return {
        userChoices: UserChoiceStore.getUserChoices()
      };
    };
    return PlaceOrderInput = React.createClass({
      displayName: 'PlaceOrderInput',
      getInitialState: function() {
        return $.extend({}, getViewState());
      },
      updateAmount: function(e) {
        var outAmount;
        outAmount = parseFloat($(e.target).val());
        if (outAmount < 0 || isNaN(outAmount)) {
          outAmount = 0;
        }
        UserInputActions.updateOutAmount(outAmount);
      },
      checkEnter: function(e) {
        if (e.keyCode === 13) {
          if (this.props.onOrderInput != null) {
            this.props.onOrderInput();
          }
        }
      },
      render: function() {
        var bot, defaultValue, outAsset, swapConfigIsChosen;
        bot = this.props.bot;
        defaultValue = this.state.userChoices.outAmount;
        outAsset = this.state.userChoices.outAsset;
        swapConfigIsChosen = !(this.state.userChoices.swapConfig == null);
        return React.createElement("div", null, React.createElement("table", {
          "className": "fieldset"
        }, React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
          "htmlFor": "token-available"
        }, outAsset, " available for purchase: ")), React.createElement("td", null, React.createElement("span", {
          "id": "token-available"
        }, swapbot.formatters.formatCurrency(bot.balances[outAsset]), " ", outAsset))), React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
          "htmlFor": "token-amount"
        }, "I would like to purchase: ")), React.createElement("td", null, (swapConfigIsChosen ? React.createElement("div", {
          "className": "chosenInputAmount"
        }, swapbot.formatters.formatCurrency(defaultValue), "\u00a0", outAsset) : React.createElement("div", null, React.createElement("input", {
          "onChange": this.updateAmount,
          "onKeyUp": this.checkEnter,
          "type": "text",
          "id": "token-amount",
          "placeholder": '0',
          "defaultValue": defaultValue
        }), "\u00a0", outAsset))))));
      }
    });
  })();

  ReactZeroClipboard = void 0;

  (function() {
    var addZeroListener, client, eventHandlers, handleZeroClipLoad, propToEvent, readyEventHasHappened, result, waitingForScriptToLoad;
    client = void 0;
    waitingForScriptToLoad = [];
    eventHandlers = {
      copy: [],
      afterCopy: [],
      error: [],
      ready: []
    };
    propToEvent = {
      onCopy: 'copy',
      onAfterCopy: 'afterCopy',
      onError: 'error',
      onReady: 'ready'
    };
    readyEventHasHappened = false;
    handleZeroClipLoad = function(error) {
      var ZeroClipboard, eventName, handleEvent;
      ZeroClipboard = window.ZeroClipboard;
      delete window.ZeroClipboard;
      client = new ZeroClipboard;
      handleEvent = function(eventName) {
        client.on(eventName, function(event) {
          var activeElement;
          if (eventName === 'ready') {
            eventHandlers['ready'].forEach(function(xs) {
              xs[1](event);
            });
            readyEventHasHappened = true;
            return;
          }
          activeElement = ZeroClipboard.activeElement();
          eventHandlers[eventName].some(function(xs) {
            var callback, element;
            element = xs[0];
            callback = xs[1];
            if (element === activeElement) {
              callback(event);
              return true;
            }
          });
        });
      };
      for (eventName in eventHandlers) {
        handleEvent(eventName);
      }
      waitingForScriptToLoad.forEach(function(callback) {
        callback();
      });
    };
    result = function(fnOrValue) {
      if (typeof fnOrValue === 'function') {
        return fnOrValue();
      } else {
        return fnOrValue;
      }
    };
    handleZeroClipLoad(null);
    ReactZeroClipboard = React.createClass({
      ready: function(cb) {
        if (client) {
          setTimeout(cb.bind(this), 1);
        } else {
          waitingForScriptToLoad.push(cb.bind(this));
        }
      },
      componentWillMount: function() {
        if (readyEventHasHappened && this.props.onReady) {
          this.props.onReady();
        }
      },
      componentDidMount: function() {
        this.eventRemovers = [];
        this.ready(function() {
          var remover;
          var el, eventName, prop, remover;
          if (!this.isMounted()) {
            return;
          }
          el = React.findDOMNode(this);
          client.clip(el);
          for (prop in this.props) {
            eventName = propToEvent[prop];
            if (eventName && typeof this.props[prop] === 'function') {
              remover = addZeroListener(eventName, el, this.props[prop]);
              this.eventRemovers.push(remover);
            }
          }
          remover = addZeroListener('copy', el, this.handleCopy);
          this.eventRemovers.push(remover);
        });
      },
      componentWillUnmount: function() {
        if (client) {
          client.unclip(this.getDOMNode());
        }
        this.eventRemovers.forEach(function(fn) {
          fn();
        });
      },
      handleCopy: function() {
        var html, p, richText, text;
        p = this.props;
        text = result(p.getText || p.text);
        html = result(p.getHtml || p.html);
        richText = result(p.getRichText || p.richText);
        client.clearData();
        richText !== null && client.setRichText(richText);
        html !== null && client.setHtml(html);
        text !== null && client.setText(text);
      },
      render: function() {
        return React.Children.only(this.props.children);
      }
    });
    addZeroListener = function(event, el, fn) {
      eventHandlers[event].push([el, fn]);
      return function() {
        var handlers, i;
        handlers = eventHandlers[event];
        i = 0;
        while (i < handlers.length) {
          if (handlers[i][0] === el) {
            handlers.splice(i, 1);
            return;
          }
          i++;
        }
      };
    };
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
            }, swapConfig.out, " ", React.createElement("small", null, "(", swapbot.formatters.formatCurrency(bot.balances[swapConfig.out]), " available)")), React.createElement("p", {
              "className": "exchange-description"
            }, swapbot.swapUtils.exchangeDescription(swapConfig)), React.createElement("div", {
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

  SwapbotPlaceOrder = null;

  (function() {
    var SwapbotSendItem, getViewState;
    getViewState = function() {
      return {
        userChoices: UserChoiceStore.getUserChoices(),
        currentBTCPrice: QuotebotStore.getCurrentPrice()
      };
    };
    SwapbotSendItem = React.createClass({
      displayName: 'SwapbotSendItem',
      getInAmount: function() {
        var inAmount;
        inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, this.props.swapConfig, this.props.currentBTCPrice);
        return inAmount;
      },
      isChooseable: function() {
        if (this.getErrorMessage() != null) {
          return false;
        }
        if (this.getInAmount() > 0) {
          return true;
        }
        return false;
      },
      getErrorMessage: function() {
        return swapbot.swapUtils.validateOutAmount(this.props.outAmount, this.props.swapConfig);
      },
      chooseSwap: function(e) {
        e.preventDefault();
        if (!this.isChooseable()) {
          return;
        }
        UserInputActions.chooseSwapConfigAtRate(this.props.swapConfig, this.props.currentBTCPrice);
      },
      render: function() {
        var errorMsg, inAmount, isChooseable, swapConfig;
        swapConfig = this.props.swapConfig;
        inAmount = this.getInAmount();
        isChooseable = this.isChooseable();
        errorMsg = this.getErrorMessage();
        return React.createElement("li", {
          "className": 'choose-swap' + (isChooseable ? ' chooseable' : ' unchooseable')
        }, React.createElement("a", {
          "className": "choose-swap",
          "onClick": this.chooseSwap,
          "href": "#next-step"
        }, (errorMsg ? React.createElement("div", {
          "className": "item-content error"
        }, errorMsg) : void 0), React.createElement("div", {
          "className": "item-header"
        }, "Send ", React.createElement("span", {
          "id": "token-value-1"
        }, swapbot.formatters.formatCurrency(inAmount)), " ", swapConfig["in"]), React.createElement("p", null, (isChooseable ? React.createElement("small", null, "Click the arrow to choose this swap") : React.createElement("small", null, "Enter an amount above"))), React.createElement("div", {
          "className": "icon-next"
        }), React.createElement("div", {
          "className": "clearfix"
        })));
      }
    });
    return SwapbotPlaceOrder = React.createClass({
      displayName: 'SwapbotPlaceOrder',
      getInitialState: function() {
        return $.extend({}, getViewState());
      },
      _onChange: function() {
        this.setState(getViewState());
      },
      componentDidMount: function() {
        UserChoiceStore.addChangeListener(this._onChange);
        QuotebotStore.addChangeListener(this._onChange);
      },
      componentWillUnmount: function() {
        UserChoiceStore.removeChangeListener(this._onChange);
        QuotebotStore.removeChangeListener(this._onChange);
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
      onOrderInput: function() {
        var matchingSwapConfigs;
        matchingSwapConfigs = this.getMatchingSwapConfigsForOutputAsset();
        if (!matchingSwapConfigs) {
          return;
        }
        if (matchingSwapConfigs.length === 1) {
          UserInputActions.chooseSwapConfigAtRate(matchingSwapConfigs[0], this.state.currentBTCPrice);
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
        }, React.createElement("h2", null, "Place your Order"), React.createElement("div", {
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
        })), React.createElement(PlaceOrderInput, {
          "onOrderInput": this.onOrderInput,
          "bot": bot
        }), React.createElement("div", {
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
                "currentBTCPrice": this.state.currentBTCPrice,
                "swapConfig": matchedSwapConfig,
                "bot": bot
              }));
            }
            return _results;
          }
        }).call(this))), React.createElement("p", {
          "className": "description"
        }, "After receiving one of those token types, this bot will wait for ", React.createElement("b", null, swapbot.formatters.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), ".")));
      }
    });
  })();

  SwapbotReceivingTransaction = null;

  (function() {
    var TransactionInfo, getViewState;
    getViewState = function() {
      var matchedSwaps, swaps, userChoices;
      userChoices = UserChoiceStore.getUserChoices();
      swaps = SwapsStore.getSwaps();
      matchedSwaps = SwapMatcher.buildMatchedSwaps(swaps, userChoices);
      return {
        userChoices: userChoices,
        swaps: swaps,
        matchedSwaps: matchedSwaps,
        anyMatchedSwaps: (matchedSwaps.length > 0 ? true : false),
        addressCopied: false
      };
    };
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
        var ts;
        ts = this.props.swap.completedAt != null ? this.props.swap.completedAt : this.props.swap.updatedAt;
        this.setState({
          fromNow: moment(ts).fromNow()
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
        }, this.state.fromNow), React.createElement("p", null, swap.message), React.createElement("p", null, "This transaction has ", React.createElement("b", null, swap.confirmations, " out of ", bot.confirmationsRequired), " ", swapbot.formatters.confirmationsWord(bot), ".")), React.createElement("div", {
          "className": "item-actions"
        }, React.createElement("div", {
          "className": "icon-next"
        }))), React.createElement("div", {
          "className": "clearfix"
        }));
      }
    });
    return SwapbotReceivingTransaction = React.createClass({
      displayName: 'SwapbotReceivingTransaction',
      copiedTimeoutRef: null,
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
      onAfterCopy: function() {
        this.setState({
          addressCopied: true
        });
        if (this.copiedTimeoutRef != null) {
          clearTimeout(this.copiedTimeoutRef);
        }
        this.copiedTimeoutRef = setTimeout((function(_this) {
          return function() {
            _this.setState({
              addressCopied: false
            });
            return _this.copiedTimeoutRef = null;
          };
        })(this), 2500);
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
        })), React.createElement(PlaceOrderInput, {
          "bot": bot
        }), React.createElement("div", {
          "className": "sendInstructions"
        }, "To begin this swap, send ", React.createElement("strong", null, swapbot.formatters.formatCurrency(this.state.userChoices.inAmount), " ", this.state.userChoices.inAsset), " to ", bot.address, React.createElement(ReactZeroClipboard, {
          "text": bot.address,
          "onAfterCopy": this.onAfterCopy
        }, React.createElement("button", {
          "className": "copyToClipboard" + (this.state.addressCopied ? ' copied' : ''),
          "title": "copy to clipboard"
        }, React.createElement("i", {
          "className": "fa fa-clipboard"
        }), " ", (this.state.addressCopied ? 'Copied' : 'Copy')))), React.createElement("div", {
          "id": "GoBackLink"
        }, React.createElement("a", {
          "id": "go-back",
          "onClick": UserInputActions.goBackOnClick,
          "href": "#go-back",
          "className": "shadow-link"
        }, "Go Back")), (this.state.anyMatchedSwaps ? React.createElement("div", null, React.createElement("h4", {
          "id": "DetectedMultiple"
        }, "We\u2019ve detected one or multiple orders that might be yours, please select the correct one to continue."), React.createElement("div", {
          "className": "not-paid-yet-link",
          "id": "NotPaidYetLink"
        }, React.createElement("a", {
          "id": "not-paid-yet",
          "onClick": UserInputActions.ignoreAllSwapsOnClick,
          "href": "#not-paid-yet",
          "className": "shadow-link"
        }, "I haven\u2019t paid yet")), React.createElement("ul", {
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
        }).call(this))) : React.createElement("div", null, React.createElement("ul", {
          "id": "transaction-wait-list",
          "className": "wide-list"
        }, React.createElement("li", null, React.createElement("div", {
          "className": "status-icon icon-pending"
        }), "Waiting for ", React.createElement("strong", null, swapbot.formatters.formatCurrency(this.state.userChoices.inAmount), " ", this.state.userChoices.inAsset), " to be sent to ", bot.address, (this.state.userChoices.numberOfIgnoredSwaps === 0 ? React.createElement("div", {
          "className": "i-paid-link",
          "id": "IPaidLink"
        }, React.createElement("a", {
          "id": "i-paid",
          "onClick": UserInputActions.showAllTransactionsOnClick,
          "href": "#i-paid",
          "className": "shadow-link"
        }, "I\u2019ve Paid")) : void 0))))), React.createElement("p", {
          "className": "description"
        }, "After receiving one of those token types, this bot will wait for ", React.createElement("b", null, swapbot.formatters.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), ".")));
      }
    });
  })();

  SwapbotWait = null;

  (function() {
    var SingleTransactionInfo, getViewState;
    getViewState = function() {
      var userChoices;
      userChoices = UserChoiceStore.getUserChoices();
      return {
        userChoices: userChoices
      };
    };
    SingleTransactionInfo = React.createClass({
      displayName: 'SingleTransactionInfo',
      intervalTimer: null,
      componentDidMount: function() {},
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
        return React.createElement("div", null, React.createElement("p", null, swap.message, React.createElement("br", null), "This transaction has ", React.createElement("b", null, swapbot.formatters.formatConfirmations(swap.confirmations), " of ", bot.confirmationsRequired), " ", swapbot.formatters.confirmationsWord(bot), " in and ", React.createElement("b", null, swapbot.formatters.formatConfirmations(swap.confirmationsOut)), " ", swapbot.formatters.confirmationsWord(bot), " out.", React.createElement("br", null), React.createElement("a", {
          "id": "not-my-transaction",
          "onClick": this.notMyTransactionClicked,
          "href": "#",
          "className": "shadow-link"
        }, "Not your transaction?")), React.createElement("p", null, "\u00a0"), (userChoices.email.emailErrorMsg ? React.createElement("p", {
          "className": "error"
        }, userChoices.email.emailErrorMsg, "  Please try again.") : null), (userChoices.email.submittedEmail ? React.createElement("p", null, React.createElement("strong", null, "Email address submitted."), "  Please check your email.") : React.createElement("div", null, React.createElement("p", null, "Don\u2019t want to wait here?", React.createElement("br", null), "We can notify you when the transaction is done!"), React.createElement("form", {
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
    return SwapbotWait = React.createClass({
      displayName: 'SwapbotWait',
      getInitialState: function() {
        return getViewState();
      },
      _onChange: function() {
        this.setState(getViewState());
      },
      componentDidMount: function() {
        UserChoiceStore.addChangeListener(this._onChange);
      },
      componentWillUnmount: function() {
        UserChoiceStore.removeChangeListener(this._onChange);
      },
      render: function() {
        var bot, defaultValue, outAsset, swapConfig;
        bot = this.props.bot;
        swapConfig = this.state.userChoices.swapConfig;
        defaultValue = this.state.userChoices.outAmount;
        outAsset = this.state.userChoices.outAsset;
        if (!swapConfig) {
          return null;
        }
        return React.createElement("div", {
          "id": "swapbot-container",
          "className": "section grid-100"
        }, React.createElement("div", {
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
        }), React.createElement("div", {
          "className": "chosenInputAmount"
        }, "Purchasing", ' ' + swapbot.formatters.formatCurrency(defaultValue), "\u00a0", outAsset), (this.state.userChoices.swap != null ? React.createElement(SingleTransactionInfo, {
          "bot": bot,
          "userChoices": this.state.userChoices
        }) : React.createElement("div", null, "No transaction found")), React.createElement("p", {
          "className": "description"
        }, "This bot will wait for ", React.createElement("b", null, swapbot.formatters.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), ".")));
      }
    });
  })();

  SwapbotComplete = null;

  (function() {
    var getViewState;
    getViewState = function() {
      var swaps, userChoices;
      userChoices = UserChoiceStore.getUserChoices();
      swaps = SwapsStore.getSwaps();
      return {
        userChoices: userChoices,
        swaps: swaps
      };
    };
    return SwapbotComplete = React.createClass({
      displayName: 'SwapbotComplete',
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
      notMyTransactionClicked: function(e) {
        e.preventDefault();
        UserInputActions.clearSwap();
      },
      closeClicked: function(e) {
        e.preventDefault();
        UserInputActions.resetSwap();
      },
      render: function() {
        var bot, swap;
        bot = this.props.bot;
        swap = this.state.userChoices.swap;
        if (!swap) {
          return null;
        }
        return React.createElement("div", {
          "id": "swapbot-container",
          "className": "section grid-100"
        }, React.createElement("div", {
          "id": "swap-step-4",
          "className": "content"
        }, React.createElement("h2", null, "Successfully finished"), React.createElement("a", {
          "href": "#close",
          "onClick": this.closeClicked,
          "className": "x-button",
          "id": "swap-step-4-close"
        }), React.createElement("div", {
          "className": "segment-control"
        }, React.createElement("div", {
          "className": "line"
        }), React.createElement("br", null), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot"
        }), React.createElement("div", {
          "className": "dot selected"
        })), React.createElement("div", {
          "className": "icon-success center"
        }), React.createElement("p", null, swap.message, React.createElement("br", null), React.createElement("a", {
          "id": "not-my-transaction",
          "onClick": this.notMyTransactionClicked,
          "href": "#",
          "className": "shadow-link"
        }, "Not your transaction?")), React.createElement("p", null, React.createElement("a", {
          "href": "/public/" + bot.username + "/swap/" + swap.id,
          "className": "details-link",
          "target": "_blank"
        }, "Transaction details ", React.createElement("i", {
          "className": "fa fa-arrow-circle-right"
        })))));
      }
    });
  })();

  SwapPurchaseStepsComponent = null;

  (function() {
    var getViewState;
    getViewState = function() {
      return UserChoiceStore.getUserChoices();
    };
    return SwapPurchaseStepsComponent = React.createClass({
      displayName: 'SwapPurchaseStepsComponent',
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
        }) : null), (this.state.step === 'place' ? React.createElement(SwapbotPlaceOrder, {
          "bot": this.props.bot
        }) : null), (this.state.step === 'receive' ? React.createElement(SwapbotReceivingTransaction, {
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

  window.BotApp = {
    init: function(bot, quotebotCredentials, pusherURL) {
      SwapsStore.init();
      BotstreamStore.init();
      QuotebotStore.init();
      UserChoiceStore.init();
      BotAPIActionCreator.subscribeToBotstream(bot.id);
      SwapAPIActionCreator.subscribeToSwapstream(bot.id);
      QuotebotActionCreator.subscribeToQuotebot(quotebotCredentials.url, quotebotCredentials.apiToken, pusherURL);
      React.render(React.createElement(BotStatusComponent, {
        "bot": bot
      }), document.getElementById('BotStatusComponent'));
      React.render(React.createElement(RecentAndActiveSwapsComponent, {
        "bot": bot
      }), document.getElementById('RecentAndActiveSwapsComponent'));
      return React.render(React.createElement(SwapPurchaseStepsComponent, {
        "bot": bot
      }), document.getElementById('SwapPurchaseStepsComponent'));
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

  QuotebotActionCreator = (function() {
    var exports, subscriberId;
    exports = {};
    subscriberId = null;
    exports.subscribeToQuotebot = function(quotebotURL, apiToken, pusherURL) {
      $.get("" + quotebotURL + "/api/v1/quote/all?apitoken=" + apiToken, (function(_this) {
        return function(quotesJSON) {
          var quote, _i, _len, _ref;
          if (quotesJSON.quotes != null) {
            _ref = quotesJSON.quotes;
            for (_i = 0, _len = _ref.length; _i < _len; _i++) {
              quote = _ref[_i];
              if (quote.source === 'bitcoinAverage' && quote.pair === 'USD:BTC') {
                QuotebotEventActions.addNewQuote(quote);
              }
            }
          }
        };
      })(this));
      subscriberId = swapbot.pusher.subscribeToPusherChanel(pusherURL, "quotebot_quote_bitcoinAverage_USD_BTC", function(quote) {
        QuotebotEventActions.addNewQuote(quote);
      });
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

  QuotebotEventActions = (function() {
    var exports;
    exports = {};
    exports.addNewQuote = function(quote) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_ADD_NEW_QUOTE,
        quote: quote
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
    exports.chooseSwapConfigAtRate = function(chosenSwapConfig, currentRate) {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG,
        swapConfig: chosenSwapConfig,
        currentRate: currentRate
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
    exports.resetSwap = function() {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_USER_RESET_SWAP
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
    exports.showAllTransactionsOnClick = function(e) {
      e.preventDefault();
      exports.showAllTransactions();
    };
    exports.showAllTransactions = function() {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_SHOW_ALL_TRANSACTIONS
      });
    };
    exports.ignoreAllSwapsOnClick = function(e) {
      e.preventDefault();
      exports.ignoreAllSwaps();
    };
    exports.ignoreAllSwaps = function() {
      Dispatcher.dispatch({
        actionType: BotConstants.BOT_IGNORE_ALL_PREVIOUS_SWAPS
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
    exports.BOT_USER_RESET_SWAP = 'BOT_USER_RESET_SWAP';
    exports.BOT_USER_CHOOSE_OUT_AMOUNT = 'BOT_USER_CHOOSE_OUT_AMOUNT';
    exports.BOT_UPDATE_EMAIL_VALUE = 'BOT_UPDATE_EMAIL_VALUE';
    exports.BOT_USER_SUBMIT_EMAIL = 'BOT_USER_SUBMIT_EMAIL';
    exports.BOT_GO_BACK = 'BOT_GO_BACK';
    exports.BOT_SHOW_ALL_TRANSACTIONS = 'BOT_SHOW_ALL_TRANSACTIONS';
    exports.BOT_IGNORE_ALL_PREVIOUS_SWAPS = 'BOT_IGNORE_ALL_PREVIOUS_SWAPS';
    exports.BOT_ADD_NEW_QUOTE = 'BOT_ADD_NEW_QUOTE';
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
      if (eventWrapper.level >= 200) {
        newEvent.message = eventWrapper.message;
      } else {
        newEvent.debugMessage = eventWrapper.message;
      }
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

  QuotebotStore = (function() {
    var addNewQuote, currentQuote, emitChange, eventEmitter, exports;
    exports = {};
    eventEmitter = null;
    currentQuote = null;
    addNewQuote = function(newQuote) {
      currentQuote = newQuote;
      emitChange();
    };
    emitChange = function() {
      eventEmitter.emitEvent('change');
    };
    exports.init = function() {
      eventEmitter = new window.EventEmitter();
      Dispatcher.register(function(action) {
        switch (action.actionType) {
          case BotConstants.BOT_ADD_NEW_QUOTE:
            addNewQuote(action.quote);
        }
      });
    };
    exports.getCurrentQuote = function() {
      return currentQuote;
    };
    exports.getCurrentPrice = function() {
      if (currentQuote == null) {
        return null;
      }
      return currentQuote.last;
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
      if (eventWrapper.event.completedAt != null) {
        newSwap.completedAt = eventWrapper.event.completedAt * 1000;
      }
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
    var MATCH_AUTO, MATCH_SHOW_ALL, changeSwapMatchMode, checkForAutoMatch, clearChosenSwap, clearChosenSwapConfig, emitChange, eventEmitter, exports, goBack, ignoreAllSwaps, initRouter, onQuotebotPriceUpdated, onRouteUpdate, onSwapStoreChanged, refreshMatchedSwaps, resetEmailChoices, resetSwap, resetUserChoices, routeToStepOrEmitChange, router, submitEmail, swapIsComplete, updateChosenOutAsset, updateChosenSwap, updateChosenSwapConfig, updateEmailValue, updateOutAmount, userChoices, _recalculateSwapConfigArtifacts;
    exports = {};
    exports.MATCH_AUTO = MATCH_AUTO = 'AUTO';
    exports.MATCH_SHOW_ALL = MATCH_SHOW_ALL = 'SHOW_ALL';
    userChoices = {
      step: 'choose',
      swapConfig: {},
      inAmount: null,
      inAsset: null,
      outAmount: null,
      outAsset: null,
      lockedInRate: null,
      swap: null,
      swapMatchMode: MATCH_AUTO,
      swapIDsToIgnore: {},
      numberOfIgnoredSwaps: 0,
      numberOfMatchedSwaps: null,
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
      userChoices.lockedInRate = null;
      userChoices.swap = null;
      userChoices.swapMatchMode = MATCH_AUTO;
      userChoices.swapIDsToIgnore = {};
      userChoices.numberOfIgnoredSwaps = 0;
      userChoices.numberOfMatchedSwaps = null;
      userChoices.z = false;
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
        routeToStepOrEmitChange('place');
      }
    };
    updateChosenSwapConfig = function(newChosenSwapConfig, currentRate) {
      var matched, newName;
      newName = newChosenSwapConfig["in"] + ':' + newChosenSwapConfig.out;
      if ((userChoices.swapConfig == null) || userChoices.swapConfig.name !== newName) {
        userChoices.swapConfig = newChosenSwapConfig;
        userChoices.swapConfig.name = newName;
        userChoices.lockedInRate = currentRate;
        _recalculateSwapConfigArtifacts();
        matched = checkForAutoMatch();
        if (matched) {
          return;
        }
        router.setRoute('receive');
      }
    };
    updateOutAmount = function(newOutAmount) {
      if (newOutAmount === userChoices.outAmount) {
        return;
      }
      userChoices.outAmount = newOutAmount;
      _recalculateSwapConfigArtifacts();
      emitChange();
    };
    updateChosenSwap = function(newChosenSwap) {
      if ((userChoices.swap == null) || userChoices.swap.id !== newChosenSwap.id) {
        userChoices.swap = newChosenSwap;
        if (swapIsComplete(newChosenSwap)) {
          routeToStepOrEmitChange('complete');
          return;
        }
        routeToStepOrEmitChange('wait');
      }
    };
    clearChosenSwap = function() {
      if (userChoices.swap != null) {
        userChoices.swap = null;
        resetEmailChoices();
      }
    };
    clearChosenSwapConfig = function() {
      clearChosenSwap();
      userChoices.swapConfig = null;
      emitChange();
    };
    resetSwap = function() {
      resetUserChoices();
      routeToStepOrEmitChange('choose');
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
      console.log("goBack userChoices.step=" + userChoices.step);
      switch (userChoices.step) {
        case 'place':
          resetUserChoices();
          router.setRoute('/choose');
          break;
        case 'receive':
          userChoices.swapConfig = null;
          userChoices.inAmount = null;
          userChoices.inAsset = null;
          userChoices.swapMatchMode = MATCH_AUTO;
          userChoices.swapIDsToIgnore = {};
          userChoices.numberOfIgnoredSwaps = 0;
          userChoices.numberOfMatchedSwaps = null;
          router.setRoute('/place');
          break;
        case 'wait':
          clearChosenSwap();
          router.setRoute('/receive');
      }
    };
    ignoreAllSwaps = function() {
      var swap, _i, _len, _ref;
      _ref = SwapsStore.getSwaps();
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        swap = _ref[_i];
        if (userChoices.swapIDsToIgnore[swap.id] == null) {
          userChoices.swapIDsToIgnore[swap.id] = true;
          ++userChoices.numberOfIgnoredSwaps;
        }
      }
    };
    changeSwapMatchMode = function(newSwapMatchMode) {
      userChoices.swapMatchMode = newSwapMatchMode;
      checkForAutoMatch();
    };
    checkForAutoMatch = function() {
      var matchedSingleSwap, matchedSwaps;
      matchedSwaps = refreshMatchedSwaps();
      if (userChoices.swapMatchMode !== MATCH_AUTO) {
        return false;
      }
      if (userChoices.numberOfMatchedSwaps === 1) {
        matchedSingleSwap = matchedSwaps[0];
        updateChosenSwap(matchedSingleSwap);
        return true;
      }
      return false;
    };
    refreshMatchedSwaps = function() {
      var matchedSwaps;
      userChoices.numberOfMatchedSwaps = 0;
      if ((userChoices.inAsset == null) || !userChoices.inAmount) {
        return null;
      }
      matchedSwaps = SwapMatcher.buildMatchedSwaps(SwapsStore.getSwaps(), userChoices);
      userChoices.numberOfMatchedSwaps = matchedSwaps.length;
      return matchedSwaps;
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
      emitChange();
    };
    emitChange = function() {
      eventEmitter.emitEvent('change');
    };
    _recalculateSwapConfigArtifacts = function() {
      if ((userChoices.outAmount != null) && (userChoices.swapConfig != null)) {
        userChoices.inAmount = swapbot.swapUtils.inAmountFromOutAmount(userChoices.outAmount, userChoices.swapConfig, userChoices.lockedInRate);
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
        case 'place':
        case 'receive':
        case 'wait':
        case 'complete':
          if (userChoices.outAsset === null) {
            valid = false;
          }
          break;
        default:
          console.warn("Unknown route: " + rawNewStep);
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
          break;
        case 'place':
          clearChosenSwapConfig();
      }
      emitChange();
      return true;
    };
    initRouter = function() {
      router = Router({
        '/choose': onRouteUpdate.bind(null, 'choose'),
        '/place': onRouteUpdate.bind(null, 'place'),
        '/receive': onRouteUpdate.bind(null, 'receive'),
        '/wait': onRouteUpdate.bind(null, 'wait'),
        '/complete': onRouteUpdate.bind(null, 'complete')
      });
      router.init(userChoices.step);
    };
    onSwapStoreChanged = function() {
      var matched, swap, _ref;
      if ((_ref = userChoices.swap) != null ? _ref.id : void 0) {
        swap = SwapsStore.getSwapById(userChoices.swap.id);
        userChoices.swap = swap;
        if (swapIsComplete(swap)) {
          routeToStepOrEmitChange('complete');
          return;
        }
        emitChange();
      } else {
        matched = checkForAutoMatch();
        if (matched) {
          return;
        }
      }
    };
    onQuotebotPriceUpdated = function() {
      var price;
      price = QuotebotStore.getCurrentPrice();
    };
    exports.init = function() {
      eventEmitter = new window.EventEmitter();
      Dispatcher.register(function(action) {
        switch (action.actionType) {
          case BotConstants.BOT_USER_CHOOSE_OUT_ASSET:
            updateChosenOutAsset(action.outAsset);
            break;
          case BotConstants.BOT_USER_CHOOSE_SWAP_CONFIG:
            updateChosenSwapConfig(action.swapConfig, action.currentRate);
            break;
          case BotConstants.BOT_USER_CHOOSE_SWAP:
            updateChosenSwap(action.swap);
            break;
          case BotConstants.BOT_USER_CLEAR_SWAP:
            clearChosenSwap();
            changeSwapMatchMode(MATCH_SHOW_ALL);
            routeToStepOrEmitChange('receive');
            break;
          case BotConstants.BOT_USER_RESET_SWAP:
            resetSwap();
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
            break;
          case BotConstants.BOT_IGNORE_ALL_PREVIOUS_SWAPS:
            ignoreAllSwaps();
            changeSwapMatchMode(MATCH_AUTO);
            routeToStepOrEmitChange('receive');
            break;
          case BotConstants.BOT_SHOW_ALL_TRANSACTIONS:
            clearChosenSwap();
            changeSwapMatchMode(MATCH_SHOW_ALL);
            routeToStepOrEmitChange('receive');
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
      if (swap.isComplete) {
        return false;
      }
      if (userChoices.swapIDsToIgnore[swap.id] != null) {
        return false;
      }
      if (userChoices.swapMatchMode === UserChoiceStore.MATCH_SHOW_ALL) {
        return true;
      }
      if (swap.assetIn = userChoices.inAsset && swapbot.formatters.formatCurrency(swap.quantityIn) === swapbot.formatters.formatCurrency(userChoices.inAmount)) {
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
      console.log("matchedSwaps=", matchedSwaps);
      return matchedSwaps;
    };
    return exports;
  })();

}).call(this);
