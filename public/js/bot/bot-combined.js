(function() {
  var BotConstants, Dispatcher, RecentAndActiveSwapsComponent, RecentOrActiveSwapComponent, SwapAPIActionCreator, SwapTestView, SwapsStore, SwapstreamEventActions, invariant, swapbot;

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
      return buildInAmountFromOutAmount[swap.strategy](inAmount, swap);
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
        console.log("rendering RecentOrActiveSwapComponent");
        return React.createElement("li", {
          "className": icon
        }, React.createElement("div", {
          "className": "status-icon icon-" + icon
        }), React.createElement("div", {
          "className": "status-content"
        }, React.createElement("span", null, React.createElement("div", {
          "className": "date"
        }, this.state.fromNow), "Confirming", React.createElement("br", null), React.createElement("small", null, "Waiting for ", swapbot.botUtils.confirmationsProse(bot), " to send ", swap.quantityOut, " ", swap.assetOut))));
      }
    });
    return RecentAndActiveSwapsComponent = React.createClass({
      displayName: 'RecentAndActiveSwapsComponent',
      getInitialState: function() {
        return getViewState();
      },
      _onChange: function() {
        console.log("_onChange");
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
            console.log("calling for active swap");
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
        console.log("this.state.swaps=", this.state.swaps);
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

  window.BotApp = {
    init: function(bot) {
      SwapsStore.init();
      SwapAPIActionCreator.subscribeToSwapEventStream(bot.id);
      return React.render(React.createElement(RecentAndActiveSwapsComponent, {
        "bot": bot
      }), document.getElementById('RecentAndActiveSwapsComponent'));
    }
  };

  SwapAPIActionCreator = (function() {
    var exports, handleSwapstreamEvents, subscriberId;
    exports = {};
    subscriberId = null;
    handleSwapstreamEvents = function(swapstreamEvents) {
      console.log("handleSwapstreamEvents: ", swapstreamEvents);
      SwapstreamEventActions.handleSwapstreamEvents(swapstreamEvents);
    };
    exports.loadSwapsFromAPI = function(botId) {
      $.get("/api/v1/public/swaps/" + botId, function(swapsData) {
        SwapstreamEventActions.addNewSwaps(swapsData);
      });
    };
    exports.subscribeToSwapEventStream = function(botId) {
      subscriberId = swapbot.pusher.subscribeToPusherChanel("swapbot_swapstream_" + botId, function(swapstreamEvent) {
        return handleSwapstreamEvents([swapstreamEvent]);
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

  BotConstants = (function() {
    var exports;
    exports = {};
    exports.BOT_ADD_NEW_SWAPS = 'BOT_ADD_NEW_SWAPS';
    exports.BOT_HANDLE_NEW_SWAPSTREAM_EVENTS = 'BOT_HANDLE_NEW_SWAPSTREAM_EVENTS';
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

  SwapsStore = (function() {
    var addNewSwaps, allMySwaps, allMySwapsById, buildSwapFromSwapEvent, emitChange, eventEmitter, exports, handleSwapstreamEvents, rebuildAllMySwaps;
    exports = {};
    allMySwapsById = {};
    allMySwaps = [];
    eventEmitter = null;
    addNewSwaps = function(newSwaps) {
      var swap, _i, _len;
      console.log("handling newSwaps");
      for (_i = 0, _len = newSwaps.length; _i < _len; _i++) {
        swap = newSwaps[_i];
        allMySwapsById[swap.id] = swap;
      }
      allMySwaps = rebuildAllMySwaps();
      emitChange();
    };
    handleSwapstreamEvents = function(eventWrappers) {
      var anyChanged, event, eventWrapper, swapId, _i, _len;
      anyChanged = false;
      for (_i = 0, _len = eventWrappers.length; _i < _len; _i++) {
        eventWrapper = eventWrappers[_i];
        swapId = eventWrapper.swapUuid;
        event = eventWrapper.event;
        if (allMySwapsById[swapId] != null) {
          allMySwapsById[swapId] = buildSwapFromSwapEvent(eventWrapper);
        } else {
          allMySwapsById[swapId] = buildSwapFromSwapEvent(eventWrapper);
        }
        anyChanged = true;
      }
      if (anyChanged) {
        allMySwaps = rebuildAllMySwaps();
        emitChange();
      }
      console.log("handling new swap event");
    };
    rebuildAllMySwaps = function() {
      var id, newAllMySwaps, swap;
      newAllMySwaps = [];
      for (id in allMySwapsById) {
        swap = allMySwapsById[id];
        newAllMySwaps.push(swap);
      }
      return newAllMySwaps;
    };
    buildSwapFromSwapEvent = function(eventWrapper) {
      var newSwap;
      newSwap = $.extend({}, eventWrapper.event);
      delete newSwap.name;
      newSwap.id = eventWrapper.swapUuid;
      newSwap.serial = eventWrapper.serial;
      newSwap.updatedAt = eventWrapper.createdAt;
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
            break;
          default:
            console.log("unknown action: " + action.actionType);
        }
      });
    };
    exports.getSwaps = function() {
      return allMySwaps;
    };
    exports.addChangeListener = function(callback) {
      eventEmitter.addListener('change', callback);
    };
    exports.removeChangeListener = function(callback) {
      eventEmitter.removeListener('change', callback);
    };
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
    exports.sayHi = function() {
      return console.log("Dispatcher says hi");
    };
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

}).call(this);
