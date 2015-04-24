(function() {
  var BotStatusComponent, RecentAndActiveSwapsComponent, SingleTransactionInfo, SwapInterfaceComponent, SwapStatusComponent, SwapbotChoose, SwapbotReceive, SwapbotSendItem, SwapbotWait, TransactionInfo, botEventWatcher, swapEventRenderer, swapEventWatcher, swapbot;

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

  BotStatusComponent = React.createClass({
    displayName: 'BotStatusComponent',
    getInitialState: function() {
      return {};
    },
    componentDidMount: function() {
      this.props.eventSubscriber.subscribe((function(_this) {
        return function(botEvent) {
          var newState;
          newState = swapbot.botUtils.newBotStatusFromEvent(_this.state.botStatus, botEvent);
          return _this.setState({
            botStatus: newState
          });
        };
      })(this));
    },
    render: function() {
      return React.createElement("div", null, (this.state.botStatus === 'active' ? React.createElement("div", null, React.createElement("div", {
        "className": "status-dot bckg-green"
      }), "Active") : React.createElement("div", null, React.createElement("div", {
        "className": "status-dot bckg-red"
      }), "Inactive")), React.createElement("button", {
        "className": "button-question"
      }));
    }
  });

  SwapStatusComponent = React.createClass({
    displayName: 'SwapStatusComponent',
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

  RecentAndActiveSwapsComponent = React.createClass({
    displayName: 'RecentAndActiveSwapsComponent',
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
      $.when($.ajax("/api/v1/public/swaps/" + botId)).done((function(_this) {
        return function(r2) {
          var swapsData;
          if (_this.isMounted()) {
            swapsData = r2[0];
            _this.setState({
              swaps: swapsData
            });
            _this.props.eventSubscriber.subscribe(function(botEvent) {
              return _this.applyBotEventToSwaps(botEvent);
            });
          }
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

  SwapInterfaceComponent = React.createClass({
    displayName: 'SwapInterfaceComponent',
    getInitialState: function() {
      return {
        step: null,
        router: this.buildRouter(),
        swapDetails: {
          swap: null,
          chosenToken: {
            inAsset: null,
            inAmount: null,
            outAmount: null
          },
          txInfo: null
        }
      };
    },
    componentDidMount: function() {
      this.props.chosenSwapProvider.registerOnSwapChange((function(_this) {
        return function(newSwap) {
          var swapDetails;
          swapDetails = _this.state.swapDetails;
          swapDetails.swap = newSwap;
          _this.setState({
            step: 'receive',
            swapDetails: swapDetails
          });
        };
      })(this));
      this.state.router.init('/choose');
    },
    render: function() {
      return React.createElement("div", null, (this.props.bot != null ? React.createElement("div", null, (this.state.step === 'choose' ? React.createElement(SwapbotChoose, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.props.bot
      }) : null), (this.state.step === 'receive' ? React.createElement(SwapbotReceive, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.props.bot
      }) : null), (this.state.step === 'wait' ? React.createElement(SwapbotWait, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.props.bot,
        "eventSubscriber": this.props.eventSubscriber
      }) : null), (this.state.step === 'complete' ? React.createElement(SwapbotComplete, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.props.bot
      }) : null)) : React.createElement("div", {
        "className": "loading"
      }, "Loading...")));
    },
    route: function(stateUpdates) {
      var valid;
      valid = true;
      switch (stateUpdates.step) {
        case 'choose':
          valid = true;
          break;
        case 'receive':
        case 'wait':
        case 'complete':
          if (this.state.swapDetails.swap == null) {
            valid = false;
          }
          if (stateUpdates.step === 'complete') {
            if (this.state.swapDetails.txInfo == null) {
              valid = false;
            }
          }
          break;
        default:
          valid = false;
      }
      if (!valid) {
        this.state.router.setRoute('/choose');
        return;
      }
      this.setState(stateUpdates);
    },
    buildRouter: function() {
      var route, router;
      route = this.route;
      router = Router({
        '/choose': route.bind(this, {
          step: 'choose'
        }),
        '/receive': route.bind(this, {
          step: 'receive'
        }),
        '/wait': route.bind(this, {
          step: 'wait'
        }),
        '/complete': route.bind(this, {
          step: 'complete'
        })
      });
      return router;
    }
  });

  SwapbotChoose = React.createClass({
    displayName: 'SwapbotChoose',
    componentDidMount: function() {
      this.props.swapDetails.swap = null;
      console.log("bot=", this.props.bot);
    },
    buildChooseSwap: function(swap) {
      return (function(_this) {
        return function(e) {
          e.preventDefault();
          _this.props.swapDetails.swap = swap;
          _this.props.router.setRoute('/receive');
        };
      })(this);
    },
    render: function() {
      var bot, index, swap;
      bot = this.props.bot;
      console.log("bot.swaps=", bot.swaps);
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
          swap = _ref[index];
          _results.push(React.createElement("li", {
            "key": "swap" + index,
            "className": "swap"
          }, React.createElement("div", null, React.createElement("div", {
            "className": "item-header"
          }, swap.out, " ", React.createElement("small", null, "(", bot.balances[swap.out], " available)")), React.createElement("p", null, "Sends ", swapbot.swapUtils.exchangeDescription(swap), "."), React.createElement("a", {
            "href": "#choose-swap",
            "onClick": this.buildChooseSwap(swap),
            "className": "icon-next"
          }))));
        }
        return _results;
      }).call(this)) : React.createElement("p", {
        "className": "description"
      }, "There are no swaps available.")))));
    }
  });

  SwapbotSendItem = React.createClass({
    displayName: 'SwapbotSendItem',
    chooseToken: function(e) {
      var asset, swap;
      e.preventDefault();
      swap = this.props.swap;
      asset = swap["in"];
      this.props.assetWasChosen(this.props.outAmount, swap);
    },
    render: function() {
      var address, inAmount, swap;
      swap = this.props.swap;
      inAmount = swapbot.swapUtils.inAmountFromOutAmount(this.props.outAmount, swap);
      address = this.props.bot.address;
      return React.createElement("li", null, React.createElement("div", {
        "className": "item-header"
      }, "Send ", React.createElement("span", {
        "id": "token-value-1"
      }, inAmount), " ", swap["in"], " to"), React.createElement("p", null, React.createElement("a", {
        "href": "bitcoin:" + address + "?amount=" + inAmount,
        "target": "_blank"
      }, address)), React.createElement("a", {
        "onClick": this.chooseToken,
        "href": "#next-step"
      }, React.createElement("div", {
        "className": "icon-next"
      })), React.createElement("div", {
        "className": "icon-qr"
      }), React.createElement("img", {
        "className": "qr-code-image hidden",
        "src": "/images/avatars/qrcode.png"
      }), React.createElement("div", {
        "className": "clearfix"
      }));
    }
  });

  SwapbotReceive = React.createClass({
    displayName: 'SwapbotReceive',
    getInitialState: function() {
      return {
        outAmount: this.props.swapDetails.chosenToken.outAmount != null ? this.props.swapDetails.chosenToken.outAmount : 0,
        matchingSwaps: this.getMatchingSwapsForOutputAsset()
      };
    },
    getMatchingSwapsForOutputAsset: function() {
      var filteredSwaps, offset, otherSwap, swap, swaps, _i, _len, _ref;
      filteredSwaps = [];
      swaps = (_ref = this.props.bot) != null ? _ref.swaps : void 0;
      swap = this.props.swapDetails.swap;
      if (swaps) {
        for (offset = _i = 0, _len = swaps.length; _i < _len; offset = ++_i) {
          otherSwap = swaps[offset];
          if (otherSwap.out === swap.out) {
            filteredSwaps.push(otherSwap);
          }
        }
      }
      return filteredSwaps;
    },
    assetWasChosen: function(outAmount, swap) {
      var inAmount;
      console.log("assetWasChosen");
      inAmount = swapbot.swapUtils.inAmountFromOutAmount(outAmount, swap);
      this.props.swapDetails.chosenToken = {
        inAsset: swap["in"],
        inAmount: inAmount,
        outAmount: outAmount,
        outAsset: swap.out
      };
      this.props.router.setRoute('/wait');
    },
    updateAmounts: function(e) {
      var outAmount;
      outAmount = parseFloat($(e.target).val());
      this.setState({
        outAmount: outAmount
      });
      return this.props.swapDetails.chosenToken.outAmount = outAmount;
    },
    checkEnter: function(e) {
      var swaps;
      if (e.keyCode === 13) {
        swaps = this.state.matchingSwaps;
        if (!swaps) {
          return;
        }
        if (swaps.length === 1) {
          this.assetWasChosen(this.state.outAmount, swaps[0]);
        }
      }
    },
    render: function() {
      var bot, offset, otherSwap, swap;
      swap = this.props.swapDetails.swap;
      bot = this.props.bot;
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
      }, swap.out, " available for purchase: ")), React.createElement("td", null, React.createElement("span", {
        "id": "token-available"
      }, bot.balances[swap.out], " ", swap.out))), React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
        "htmlFor": "token-amount"
      }, "I would like to purchase: ")), React.createElement("td", null, React.createElement("input", {
        "onChange": this.updateAmounts,
        "onKeyUp": this.checkEnter,
        "type": "text",
        "id": "token-amount",
        "placeholder": '0 ' + swap.out,
        "defaultValue": this.props.swapDetails.chosenToken.outAmount
      })))), React.createElement("ul", {
        "id": "transaction-select-list",
        "className": "wide-list"
      }, ((function() {
        var _i, _len, _ref, _results;
        if (this.state.matchingSwaps) {
          _ref = this.state.matchingSwaps;
          _results = [];
          for (offset = _i = 0, _len = _ref.length; _i < _len; offset = ++_i) {
            otherSwap = _ref[offset];
            _results.push(React.createElement(SwapbotSendItem, {
              "key": 'swap' + offset,
              "swap": otherSwap,
              "bot": bot,
              "outAmount": this.state.outAmount,
              "assetWasChosen": this.assetWasChosen
            }));
          }
          return _results;
        }
      }).call(this))), React.createElement("p", {
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
        fromNow: moment(this.props.txInfo.createdAt).fromNow()
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
    render: function() {
      var bot, txInfo;
      txInfo = this.props.txInfo;
      bot = this.props.bot;
      return React.createElement("li", null, React.createElement("div", {
        "className": "item-content"
      }, React.createElement("a", {
        "onClick": this.props.clickedFn,
        "href": "#choose"
      }, React.createElement("div", {
        "className": "item-header",
        "title": "{txInfo.name}"
      }, "Transaction Received"), React.createElement("p", {
        "className": "date"
      }, this.state.fromNow), React.createElement("p", null, "Received ", React.createElement("b", null, txInfo.inQty, " ", txInfo.inAsset), " from ", txInfo.address, "."), React.createElement("p", null, txInfo.msg), React.createElement("p", null, "This transaction has ", React.createElement("b", null, txInfo.confirmations, " out of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), "."))), React.createElement("div", {
        "className": "item-actions"
      }, React.createElement("a", {
        "onClick": this.props.clickedFn,
        "href": "#choose"
      }, React.createElement("div", {
        "className": "icon-next"
      }))), React.createElement("div", {
        "className": "clearfix"
      }));
    }
  });

  SingleTransactionInfo = React.createClass({
    displayName: 'SingleTransactionInfo',
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
        fromNow: moment(this.props.txInfo.createdAt).fromNow()
      });
    },
    componentWillUnmount: function() {
      if (this.intervalTimer != null) {
        clearInterval(this.intervalTimer);
      }
    },
    getInitialState: function() {
      return {
        fromNow: '',
        emailValue: '',
        submittingEmail: false,
        submittedEmail: false,
        emailErrorMsg: null
      };
    },
    updateEmailValue: function(e) {
      e.preventDefault();
      this.setState({
        emailValue: e.target.value
      });
    },
    submitEmailFn: function(e) {
      var data, email;
      e.preventDefault();
      if (this.state.submittingEmail) {
        return;
      }
      email = this.state.emailValue;
      this.setState({
        submittingEmail: true,
        emailErrorMsg: null
      });
      data = {
        email: email,
        swapId: this.props.txInfo.swapId
      };
      $.ajax({
        type: "POST",
        url: '/api/v1/public/customers',
        data: data,
        dataType: 'json',
        success: (function(_this) {
          return function(data) {
            if (data.id) {
              _this.setState({
                submittedEmail: true,
                submittingEmail: false
              });
            }
          };
        })(this),
        error: (function(_this) {
          return function(jqhr, textStatus) {
            var errorMsg;
            data = jqhr.responseText ? $.parseJSON(jqhr.responseText) : null;
            if (data != null ? data.message : void 0) {
              errorMsg = data.message;
            } else {
              errorMsg = "An error occurred while trying to submit this email.";
            }
            console.error("Error: " + textStatus, data);
            _this.setState({
              submittedEmail: false,
              submittingEmail: false,
              emailErrorMsg: errorMsg
            });
          };
        })(this)
      });
    },
    render: function() {
      var bot, emailValue, txInfo;
      txInfo = this.props.txInfo;
      bot = this.props.bot;
      emailValue = this.state.emailValue;
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
      }), React.createElement("p", null, "Received ", React.createElement("b", null, txInfo.inQty, " ", txInfo.inAsset), " from ", txInfo.address, ".", React.createElement("br", null), React.createElement("a", {
        "id": "not-my-transaction",
        "onClick": this.props.notMyTransactionClicked,
        "href": "#",
        "className": "shadow-link"
      }, "Not your transaction?")), React.createElement("p", null, "This transaction has ", React.createElement("b", null, txInfo.confirmations, " out of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), "."), (this.state.emailErrorMsg ? React.createElement("p", {
        "className": "error"
      }, this.state.emailErrorMsg, "  Please try again.") : null), (this.state.submittedEmail ? React.createElement("p", null, React.createElement("strong", null, "Email address submitted."), "  Please check your email.") : (React.createElement("p", null, "Don\&t want to wait here?", React.createElement("br", null), "We can notify you when the transaction is done!"), React.createElement("form", {
        "action": "#submit-email",
        "onSubmit": this.submitEmailFn,
        "style": (this.state.submittingEmail ? {
          opacity: 0.2
        } : null)
      }, React.createElement("table", {
        "className": "fieldset fieldset-other"
      }, React.createElement("tbody", null, React.createElement("tr", null, React.createElement("td", null, React.createElement("input", {
        "disabled": (this.state.submittingEmail ? true : false),
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

  SwapbotWait = React.createClass({
    displayName: 'SwapbotWait',
    subscriberId: null,
    componentDidMount: function() {
      this.subscriberId = this.props.eventSubscriber.subscribe((function(_this) {
        return function(botEvent) {
          if (_this.isMounted()) {
            if (botEventWatcher.botEventMatchesInAmount(botEvent, _this.props.swapDetails.chosenToken.inAmount, _this.props.swapDetails.chosenToken.inAsset)) {
              return _this.handleMatchedBotEvent(botEvent);
            }
          }
        };
      })(this));
    },
    componentWillUnmount: function() {
      if (this.subscriberId != null) {
        this.props.eventSubscriber.unsubscribe(this.subscriberId);
        this.subscriberId = null;
      }
    },
    handleMatchedBotEvent: function(botEvent) {
      var matchedTxInfo, matchedTxs, selectedMatchedTxInfo, swapId;
      matchedTxInfo = botEventWatcher.txInfoFromBotEvent(botEvent);
      swapId = matchedTxInfo.swapId;
      matchedTxs = this.state.matchedTxs;
      if (matchedTxs[swapId] != null) {
        if (matchedTxs[swapId].serial >= botEvent.serial) {
          return;
        }
      }
      selectedMatchedTxInfo = this.state.selectedMatchedTxInfo;
      if ((selectedMatchedTxInfo != null) && selectedMatchedTxInfo.swapId === swapId) {
        selectedMatchedTxInfo = matchedTxInfo;
      }
      matchedTxs[swapId] = matchedTxInfo;
      this.setState({
        selectedMatchedTxInfo: selectedMatchedTxInfo,
        matchedTxs: matchedTxs,
        anyMatchedTxs: true
      });
    },
    selectMatchedTx: function(matchedTxInfo) {
      if (matchedTxInfo.status === 'swap.sent') {
        this.props.swapDetails.txInfo = matchedTxInfo;
        return this.props.router.setRoute('/complete');
      } else {
        return this.setState({
          selectedMatchedTxInfo: matchedTxInfo
        });
      }
    },
    getInitialState: function() {
      return {
        botEvents: [],
        selectedMatchedTxInfo: null,
        matchedTxs: {},
        anyMatchedTxs: false
      };
    },
    goBack: function(e) {
      e.preventDefault();
      this.props.router.setRoute('/receive');
    },
    notMyTransactionClicked: function(e) {
      this.setState({
        selectedMatchedTxInfo: null
      });
      e.preventDefault();
    },
    buildChooseSwapClicked: function(txInfo) {
      return (function(_this) {
        return function(e) {
          e.preventDefault();
          _this.selectMatchedTx(txInfo);
        };
      })(this);
    },
    render: function() {
      var bot, swap, swapDetails, swapId, txInfo;
      bot = this.props.bot;
      swapDetails = this.props.swapDetails;
      swap = swapDetails.swap;
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
      }, swap.out, " available for purchase: ")), React.createElement("td", null, React.createElement("span", {
        "id": "token-available"
      }, bot.balances[swap.out], " ", swap.out))), React.createElement("tr", null, React.createElement("td", null, React.createElement("label", {
        "htmlFor": "token-amount"
      }, "I would like to purchase: ")), React.createElement("td", null, React.createElement("input", {
        "disabled": true,
        "type": "text",
        "id": "token-amount",
        "placeholder": '0 ' + swap.out,
        "defaultValue": this.props.swapDetails.chosenToken.outAmount
      })))), (this.state.selectedMatchedTxInfo != null ? React.createElement(SingleTransactionInfo, {
        "bot": bot,
        "txInfo": this.state.selectedMatchedTxInfo,
        "notMyTransactionClicked": this.notMyTransactionClicked
      }) : this.state.anyMatchedTxs ? React.createElement("ul", {
        "id": "transaction-confirm-list",
        "className": "wide-list"
      }, (function() {
        var _ref, _results;
        _ref = this.state.matchedTxs;
        _results = [];
        for (swapId in _ref) {
          txInfo = _ref[swapId];
          _results.push(React.createElement(TransactionInfo, {
            "bot": bot,
            "txInfo": txInfo,
            "clickedFn": this.buildChooseSwapClicked(txInfo)
          }));
        }
        return _results;
      }).call(this)) : React.createElement("ul", {
        "id": "transaction-wait-list",
        "className": "wide-list"
      }, React.createElement("li", null, React.createElement("div", {
        "className": "status-icon icon-pending"
      }), "Waiting for ", React.createElement("strong", null, swapDetails.chosenToken.inAmount, " ", swapDetails.chosenToken.inAsset), " to be sent to ", bot.address, "."))), React.createElement("p", {
        "className": "description"
      }, "After receiving one of those token types, this bot will wait for ", React.createElement("b", null, swapbot.botUtils.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), ".")));
    }
  });

  botEventWatcher = (function() {
    var exports;
    exports = {};
    exports.botEventMatchesInAmount = function(botEvent, inAmount, inAsset) {
      var event;
      event = botEvent.event;
      switch (event.name) {
        case 'unconfirmed.tx':
        case 'swap.confirming':
        case 'swap.confirmed':
        case 'swap.sent':
          if (event.inQty === inAmount && event.inAsset === inAsset) {
            return true;
          }
      }
      return false;
    };
    exports.confirmationsFromEvent = function(botEvent) {
      var event;
      event = botEvent.event;
      switch (event.name) {
        case 'unconfirmed.tx':
          return 0;
        case 'swap.confirming':
        case 'swap.confirmed':
        case 'swap.sent':
          return event.confirmations;
      }
      console.warn("unknown event " + event.name);
      return event.confirmations;
    };
    exports.txInfoFromBotEvent = function(botEvent) {
      var event, txInfo;
      event = botEvent.event;
      txInfo = {
        name: event.name,
        msg: event.msg,
        address: event.destination,
        swapId: event.swapId,
        inAsset: event.inAsset,
        inQty: event.inQty,
        outAsset: event.outAsset,
        outQty: event.outQty,
        serial: botEvent.serial,
        createdAt: botEvent.createdAt,
        confirmations: exports.confirmationsFromEvent(botEvent),
        status: event.name
      };
      return txInfo;
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

  window.BotApp = {
    init: function(bot) {
      var chosenSwapProvider, eventSubscriber;
      eventSubscriber = swapbot.botEventsService.buildEventSubscriberForBot(bot);
      chosenSwapProvider = {
        swap: null,
        registerOnSwapChange: function(fn) {
          chosenSwapProvider._callbacks.push(fn);
        },
        setSwap: function(swap) {
          var fn, _i, _len, _ref;
          chosenSwapProvider.swap = swap;
          _ref = chosenSwapProvider._callbacks;
          for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            fn = _ref[_i];
            fn(swap);
          }
        },
        _callbacks: []
      };
      React.render(React.createElement(BotStatusComponent, {
        "eventSubscriber": eventSubscriber,
        "bot": bot
      }), document.getElementById('BotStatusComponent'));
      React.render(React.createElement(SwapInterfaceComponent, {
        "eventSubscriber": eventSubscriber,
        "bot": bot,
        "chosenSwapProvider": chosenSwapProvider
      }), document.getElementById('SwapInterfaceComponent'));
      return React.render(React.createElement(RecentAndActiveSwapsComponent, {
        "eventSubscriber": eventSubscriber,
        "bot": bot
      }), document.getElementById('RecentAndActiveSwapsComponent'));
    }
  };

}).call(this);
