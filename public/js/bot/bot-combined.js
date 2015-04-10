(function() {
  var SwapStatus, SwapStatuses, SwapsList, swapEventRenderer, swapEventWatcher, swapbot;

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

  swapEventWatcher = (function() {
    var exports, isActive, shouldProcessSwapEvent;
    exports = {};
    shouldProcessSwapEvent = function(event) {
      if (event.swapId == null) {
        return false;
      }
      switch (event.name) {
        case 'swap.stateChange':
          return false;
      }
      return true;
    };
    isActive = function(event) {
      switch (event.name) {
        case 'unconfirmed.tx':
        case 'swap.confirming':
        case 'swap.failed':
          return true;
      }
      return false;
    };
    exports.botEventMatchesSwap = function(botEvent, swap) {
      var event;
      event = botEvent.event;
      if (event.swapId == null) {
        return false;
      }
      return event.swapId === swap.id;
    };
    exports.applyEventToSwapEventRecordsIfNew = function(botEvent, swapEventRecords) {
      var createdAt, event, existingEventRecord, serial, swapId;
      serial = botEvent.serial;
      event = botEvent.event;
      if (!shouldProcessSwapEvent(event)) {
        return false;
      }
      swapId = event.swapId;
      createdAt = botEvent.createdAt;
      if (swapEventRecords[swapId] == null) {
        swapEventRecords[swapId] = {
          serial: serial,
          date: createdAt,
          event: event,
          active: isActive(event)
        };
        return true;
      } else {
        existingEventRecord = swapEventRecords[swapId];
        if (serial > existingEventRecord.serial) {
          swapEventRecords[swapId] = {
            serial: serial,
            date: createdAt,
            event: event,
            active: isActive(event)
          };
          return true;
        }
      }
      return false;
    };
    return exports;
  })();

  swapEventRenderer = (function() {
    var exports, renderers;
    exports = {};
    renderers = {};
    renderers['unconfirmed.tx'] = function(bot, swap, swapEventRecord) {
      var event;
      event = swapEventRecord.event;
      return React.createElement("li", {
        "className": "pending"
      }, React.createElement("div", {
        "className": "status-icon icon-pending"
      }), event.msg, React.createElement("br", null), React.createElement("small", null, "Waiting for ", swapbot.botUtils.confirmationsProse(bot), " to send ", event.outQty, " ", event.outAsset));
    };
    renderers['swap.confirming'] = function(bot, swap, swapEventRecord) {
      var event;
      event = swapEventRecord.event;
      return React.createElement("li", {
        "className": "pending"
      }, React.createElement("div", {
        "className": "status-icon icon-pending"
      }), event.msg, React.createElement("br", null), React.createElement("small", null, "Received ", event.confirmations, " of ", swapbot.botUtils.confirmationsProse(bot), " to send ", event.outQty, " ", event.outAsset));
    };
    renderers['swap.failed'] = function(bot, swap, swapEventRecord) {
      var event;
      event = swapEventRecord.event;
      return React.createElement("li", {
        "className": "failed"
      }, React.createElement("div", {
        "className": "status-icon icon-failed"
      }), event.msg, React.createElement("br", null), React.createElement("small", null, "Failed to swap to ", event.destination));
    };
    renderers['swap.sent'] = function(bot, swap, swapEventRecord) {
      var event;
      event = swapEventRecord.event;
      return React.createElement("li", {
        "className": "confirmed"
      }, React.createElement("div", {
        "className": "status-icon icon-confirmed"
      }), event.msg);
    };
    exports.renderSwapStatus = function(bot, swap, swapEventRecord) {
      var name;
      if (swapEventRecord != null) {
        name = swapEventRecord.event.name;
        if (renderers[name] != null) {
          return renderers[name](bot, swap, swapEventRecord);
        }
      }
      console.log("renderSwapStatus swap=" + swap.id + " swapEventRecord=", swapEventRecord);
      return React.createElement("li", {
        "className": "pending"
      }, React.createElement("div", {
        "className": "status-icon icon-pending"
      }), "Processing swap from ", swap.address, React.createElement("br", null), React.createElement("small", null, "Waiting for more information"));
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
    return exports;
  })();

  SwapStatus = React.createClass({
    displayName: 'SwapStatus',
    getInitialState: function() {
      return {};
    },
    componentDidMount: function() {},
    render: function() {
      var swapEventRecord;
      swapEventRecord = this.props.swapEventRecord;
      return swapEventRenderer.renderSwapStatus(this.props.bot, this.props.swap, this.props.swapEventRecord);
    }
  });

  SwapsList = React.createClass({
    displayName: 'SwapsList',
    getInitialState: function() {
      return {};
    },
    componentDidMount: function() {},
    render: function() {
      var bot, swap;
      bot = this.props.bot;
      if (bot.swaps) {
        return React.createElement("ul", {
          "id": "swaps-list",
          "className": "wide-list"
        }, (function() {
          var _i, _len, _ref, _results;
          _ref = bot.swaps;
          _results = [];
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            swap = _ref[_i];
            _results.push(React.createElement("li", null, React.createElement("div", null, React.createElement("div", {
              "className": "item-header"
            }, swap.out, " ", React.createElement("small", null, "(", bot.balances[swap.out], " available)")), React.createElement("p", null, "Sends ", swapbot.swapUtils.exchangeDescription(swap), "."), React.createElement("a", {
              "href": bot.id + "/popup",
              "target": "_blank",
              "className": "icon-next"
            }))));
          }
          return _results;
        })());
      } else {
        return React.createElement("p", {
          "className": "description"
        }, "There are no swaps available.");
      }
    }
  });

  SwapStatuses = React.createClass({
    displayName: 'SwapStatuses',
    getInitialState: function() {
      return {
        swaps: null,
        swapEventRecords: {}
      };
    },
    componentDidMount: function() {
      var bot, botId;
      bot = this.props.bot;
      botId = bot.id;
      $.when($.ajax("/api/v1/public/swaps/" + botId), $.ajax("/api/v1/public/botevents/" + botId)).done((function(_this) {
        return function(r2, r3) {
          var botEvent, eventsData, swapsData, _i, _len;
          if (_this.isMounted()) {
            swapsData = r2[0];
            eventsData = r3[0];
            _this.setState({
              swaps: swapsData
            });
            for (_i = 0, _len = eventsData.length; _i < _len; _i++) {
              botEvent = eventsData[_i];
              _this.applyBotEventToSwaps(botEvent);
            }
            _this.subscribeToPusher(bot);
          }
        };
      })(this));
    },
    componentWillUnmount: function() {
      if (this.state.pusherClient) {
        swapbot.pusher.closePusherChanel(this.state.pusherClient);
      }
    },
    subscribeToPusher: function(bot) {
      swapbot.pusher.subscribeToPusherChanel("swapbot_events_" + bot.id, (function(_this) {
        return function(botEvent) {
          return _this.applyBotEventToSwaps(botEvent);
        };
      })(this));
    },
    applyBotEventToSwaps: function(botEvent) {
      var anyFound, applied, newSwapEventRecords, swap, _i, _len, _ref;
      if (this.state.swaps == null) {
        return false;
      }
      newSwapEventRecords = this.state.swapEventRecords;
      anyFound = false;
      _ref = this.state.swaps;
      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
        swap = _ref[_i];
        if (swapEventWatcher.botEventMatchesSwap(botEvent, swap)) {
          applied = swapEventWatcher.applyEventToSwapEventRecordsIfNew(botEvent, newSwapEventRecords);
          if (applied) {
            anyFound = true;
          }
        }
      }
      if (anyFound) {
        this.setState({
          swapEventRecords: newSwapEventRecords
        });
      }
      return anyFound;
    },
    activeSwaps: function(fn) {
      var eventRecord, eventRecords, renderedSwaps, swap;
      eventRecords = this.state.swapEventRecords;
      renderedSwaps = (function() {
        var _i, _len, _ref, _results;
        _ref = this.state.swaps;
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          swap = _ref[_i];
          eventRecord = eventRecords[swap.id];
          if (eventRecord != null ? eventRecord.active : void 0) {
            _results.push(fn(swap, eventRecord));
          } else {
            _results.push(void 0);
          }
        }
        return _results;
      }).call(this);
      return renderedSwaps;
    },
    recentSwaps: function(fn) {
      var eventRecord, eventRecords, renderedSwaps, swap;
      eventRecords = this.state.swapEventRecords;
      renderedSwaps = (function() {
        var _i, _len, _ref, _results;
        _ref = this.state.swaps;
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          swap = _ref[_i];
          eventRecord = eventRecords[swap.id];
          console.log("" + swap.id + " eventRecord=", eventRecord);
          if ((eventRecord != null) && !eventRecord.active) {
            _results.push(fn(swap, eventRecord));
          } else {
            _results.push(void 0);
          }
        }
        return _results;
      }).call(this);
      return renderedSwaps;
    },
    render: function() {
      var anyActiveSwaps, anyRecentSwaps;
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
      }, this.activeSwaps((function(_this) {
        return function(swap, eventRecord) {
          anyActiveSwaps = true;
          return React.createElement(SwapStatus, {
            "key": swap.id,
            "bot": _this.props.bot,
            "swap": swap,
            "swapEventRecord": eventRecord
          });
        };
      })(this))), (!anyActiveSwaps ? React.createElement("div", {
        "className": "description"
      }, "No Active Swaps") : void 0)), React.createElement("div", {
        "className": "clearfix"
      }), React.createElement("div", {
        "id": "recent-swaps",
        "className": "section grid-100"
      }, React.createElement("h3", null, "Recent Swaps"), React.createElement("ul", {
        "className": "swap-list"
      }, this.recentSwaps((function(_this) {
        return function(swap, eventRecord) {
          anyRecentSwaps = true;
          return React.createElement(SwapStatus, {
            "key": swap.id,
            "bot": _this.props.bot,
            "swap": swap,
            "swapEventRecord": eventRecord
          });
        };
      })(this))), (!anyRecentSwaps ? React.createElement("div", {
        "className": "description"
      }, "No Active Swaps") : void 0), React.createElement("div", {
        "style": {
          textAlign: 'center'
        }
      }, React.createElement("button", {
        "className": "button-load-more"
      }, "Load more swaps..."))));
    }
  });

  window.BotApp = {
    init: function(bot) {
      React.render(React.createElement(SwapStatuses, {
        "bot": bot
      }), document.getElementById('SwapStatuses'));
      return React.render(React.createElement(SwapsList, {
        "bot": bot
      }), document.getElementById('SwapsList'));
    }
  };

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
      return "" + swap.out_qty + " " + swap.out + " for " + in_qty + " " + swap["in"];
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
    buildInAmountFromOutAmount.fixed = function(outAmount, swap) {};
    exports.exchangeDescription = function(swap) {
      return buildDesc[swap.strategy](swap);
    };
    exports.inAmountFromOutAmount = function(inAmount, swap) {
      return buildInAmountFromOutAmount[swap.strategy](inAmount, swap);
    };
    return exports;
  })();

}).call(this);
