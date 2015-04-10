(function() {
  var SwapbotChoose, SwapbotChooseItem, SwapbotComplete, SwapbotComponent, SwapbotReceive, SwapbotSendItem, SwapbotWait, TransactionInfo, botEventWatcher, swapbot, util;

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

  SwapbotComponent = React.createClass({
    displayName: 'SwapbotComponent',
    getInitialState: function() {
      return {
        bot: null,
        botId: null,
        step: 'choose',
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
      var botId, containerEl;
      containerEl = jQuery(this.getDOMNode()).parent();
      botId = containerEl.data('bot-id');
      this.setState({
        botId: botId
      });
      $.get("/api/v1/public/bot/" + botId, (function(_this) {
        return function(data) {
          if (_this.isMounted()) {
            _this.setState({
              bot: data
            });
          }
        };
      })(this));
      this.state.router.init('/choose');
    },
    render: function() {
      var _ref, _ref1;
      return React.createElement("div", {
        "className": "swapbot-container " + (this.props.showing != null ? '' : 'hidden')
      }, React.createElement("div", {
        "className": "header"
      }, React.createElement("div", {
        "className": "avatar"
      }, React.createElement("img", {
        "src": (((_ref = this.state.bot) != null ? _ref.hash : void 0) ? "http://robohash.org/" + this.state.bot.hash + ".png?set=set3" : '')
      })), React.createElement("div", {
        "className": "status-dot bckg-green"
      }), React.createElement("h1", null, React.createElement("a", {
        "href": "http://raburski.com/swapbot0",
        "target": "_blank"
      }, ((_ref1 = this.state.bot) != null ? _ref1.name : void 0)))), React.createElement("div", {
        "className": "content"
      }, (this.state.bot != null ? React.createElement("div", null, (this.state.step === 'choose' ? React.createElement(SwapbotChoose, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.state.bot
      }) : null), (this.state.step === 'receive' ? React.createElement(SwapbotReceive, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.state.bot
      }) : null), (this.state.step === 'wait' ? React.createElement(SwapbotWait, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.state.bot
      }) : null), (this.state.step === 'complete' ? React.createElement(SwapbotComplete, {
        "swapDetails": this.state.swapDetails,
        "router": this.state.router,
        "bot": this.state.bot
      }) : null)) : React.createElement("div", {
        "className": "loading"
      }, "Loading...")), React.createElement("div", {
        "className": "footer"
      }, "powered by ", React.createElement("a", {
        "href": "http://swapbot.co/",
        "target": "_blank"
      }, "Swapbot"))));
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

  SwapbotChooseItem = React.createClass({
    displayName: 'SwapbotChooseItem',
    clickSwap: function(e) {
      e.preventDefault();
      this.props.chooseSwapFn(this.props.swap);
    },
    render: function() {
      var bot, rateDesc, swap;
      swap = this.props.swap;
      bot = this.props.bot;
      rateDesc = swapbot.swapUtils.exchangeDescription(swap);
      return React.createElement("li", null, React.createElement("a", {
        "onClick": this.clickSwap,
        "href": "#choose"
      }, React.createElement("div", {
        "className": "item-header"
      }, swap.out, " ", React.createElement("small", null, "(", bot.balances[swap.out], " available)")), React.createElement("p", null, "Sends ", rateDesc, "."), React.createElement("div", {
        "className": "icon-next"
      })));
    }
  });

  SwapbotChoose = React.createClass({
    displayName: 'SwapbotChoose',
    chooseSwapFn: function(swap) {
      this.props.swapDetails.swap = swap;
      return this.props.router.setRoute('/receive');
    },
    componentDidMount: function() {
      this.props.swapDetails.swap = null;
      console.log("bot=", this.props.bot);
    },
    render: function() {
      var offset, swap, swaps;
      return React.createElement("div", {
        "id": "swap-step-1",
        "className": "swap-step"
      }, React.createElement("h2", null, "Choose a token to receive"), React.createElement("div", {
        "className": "segment-control"
      }, React.createElement("div", {
        "className": "line"
      }), React.createElement("br", null), React.createElement("div", {
        "className": "dot selected"
      }), React.createElement("div", {
        "className": "dot"
      }), React.createElement("div", {
        "className": "dot"
      }), React.createElement("div", {
        "className": "dot"
      })), (this.props.bot ? React.createElement("div", null, React.createElement("p", {
        "className": "description"
      }, this.props.bot.description, " ", React.createElement("a", {
        "className": "more-link",
        "href": swapbot.addressUtils.publicBotAddress(this.props.bot.username, this.props.bot.id, window.location),
        "target": "_blank"
      }, React.createElement("i", {
        "className": "fa fa-sign-out"
      }))), React.createElement("ul", {
        "className": "wide-list"
      }, ((function() {
        var _i, _len, _results;
        swaps = this.props.bot.swaps;
        if (swaps) {
          _results = [];
          for (offset = _i = 0, _len = swaps.length; _i < _len; offset = ++_i) {
            swap = swaps[offset];
            _results.push(React.createElement(SwapbotChooseItem, {
              "key": 'swap' + offset,
              "bot": this.props.bot,
              "swap": swap,
              "chooseSwapFn": this.chooseSwapFn
            }));
          }
          return _results;
        }
      }).call(this)))) : void 0));
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
        "className": "icon-wallet"
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
        "id": "swap-step-2",
        "className": "swap-step"
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
      }, "After receiving one of those token types, this bot will wait for ", React.createElement("b", null, swapbot.botUtils.confirmationsProse(bot)), " and return tokens ", React.createElement("b", null, "to the same address"), "."));
    }
  });

  util = (function() {
    var exports;
    exports = {};
    exports.sayHi = function(text) {
      return console.log("sayHi: " + text);
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
      return React.createElement("li", null, React.createElement("a", {
        "onClick": this.props.clickedFn,
        "href": "#choose"
      }, React.createElement("div", {
        "className": "item-header",
        "title": "{txInfo.name}"
      }, "Transaction Received"), React.createElement("div", {
        "className": "icon-next"
      }), React.createElement("div", null, React.createElement("p", {
        "className": "date"
      }, this.state.fromNow), React.createElement("p", null, "Received ", React.createElement("b", null, txInfo.inQty, " ", txInfo.inAsset), " from ", React.createElement("br", null), txInfo.address, ".", React.createElement("br", null), "This transaction has ", React.createElement("b", null, txInfo.confirmations, " out of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), "."), React.createElement("p", {
        "className": "msg"
      }, txInfo.msg))));
    }
  });

  SwapbotWait = React.createClass({
    displayName: 'SwapbotWait',
    intervalTimer: null,
    componentDidMount: function() {
      var bot, botId;
      bot = this.props.bot;
      botId = bot.id;
      $.get("/api/v1/public/botevents/" + botId, (function(_this) {
        return function(data) {
          var botEvent, _i, _len;
          if (_this.isMounted()) {
            for (_i = 0, _len = data.length; _i < _len; _i++) {
              botEvent = data[_i];
              if (botEventWatcher.botEventMatchesInAmount(botEvent, _this.props.swapDetails.chosenToken.inAmount, _this.props.swapDetails.chosenToken.inAsset)) {
                _this.handleMatchedBotEvent(botEvent);
              }
            }
          }
        };
      })(this));
      this.state.pusherClient = this.subscribeToPusher(bot);
    },
    componentWillUnmount: function() {
      if (this.state.pusherClient) {
        swapbot.pusher.closePusherChanel(this.state.pusherClient);
      }
    },
    subscribeToPusher: function(bot) {
      swapbot.pusher.subscribeToPusherChanel("swapbot_events_" + bot.id, (function(_this) {
        return function(botEvent) {
          if (botEventWatcher.botEventMatchesInAmount(botEvent, _this.props.swapDetails.chosenToken.inAmount, _this.props.swapDetails.chosenToken.inAsset)) {
            return _this.handleMatchedBotEvent(botEvent);
          }
        };
      })(this));
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
        pusherClient: null,
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
      var bot, swapDetails, swapId, txInfo;
      bot = this.props.bot;
      swapDetails = this.props.swapDetails;
      return React.createElement("div", {
        "id": "swap-step-3",
        "className": "swap-step"
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
      })), (this.state.selectedMatchedTxInfo != null ? React.createElement("p", null, "Received ", React.createElement("b", null, this.state.selectedMatchedTxInfo.inQty, " ", this.state.selectedMatchedTxInfo.inAsset), " from ", React.createElement("br", null), this.state.selectedMatchedTxInfo.address, ".", React.createElement("br", null), React.createElement("a", {
        "id": "not-my-transaction",
        "onClick": this.notMyTransactionClicked,
        "href": "#",
        "className": "shadow-link"
      }, "Not your transaction?")) : this.state.anyMatchedTxs ? React.createElement("div", null, React.createElement("ul", {
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
      }).call(this)), React.createElement("a", {
        "id": "go-back",
        "onClick": this.goBack,
        "href": "#",
        "className": "shadow-link"
      }, "Go Back")) : React.createElement("p", null, "Waiting for ", swapDetails.chosenToken.inAmount, " ", swapDetails.chosenToken.inAsset, " to be sent to", React.createElement("br", null), bot.address, React.createElement("br", null), React.createElement("a", {
        "id": "cancel",
        "onClick": this.goBack,
        "href": "#",
        "className": "shadow-link"
      }, "Go Back"))), React.createElement("div", {
        "className": "pulse-spinner center"
      }, React.createElement("div", {
        "className": "rect1"
      }), React.createElement("div", {
        "className": "rect2"
      }), React.createElement("div", {
        "className": "rect3"
      }), React.createElement("div", {
        "className": "rect4"
      }), React.createElement("div", {
        "className": "rect5"
      })), (this.state.selectedMatchedTxInfo != null ? React.createElement("div", null, React.createElement("p", null, "This transaction has ", React.createElement("b", null, this.state.selectedMatchedTxInfo.confirmations, " out of ", bot.confirmationsRequired), " ", swapbot.botUtils.confirmationsWord(bot), "."), React.createElement("p", {
        "className": "msg"
      }, this.state.selectedMatchedTxInfo.msg)) : React.createElement("p", null, "Waiting for transaction.  This transaction will require ", swapbot.botUtils.confirmationsProse(bot), ".")));
    }
  });

  window.SwapbotApp = {
    init: function() {
      return React.render(React.createElement(SwapbotComponent, {
        "showing": true
      }), document.getElementById('SwapbotPopup'));
    }
  };

  SwapbotComplete = React.createClass({
    displayName: 'SwapbotComplete',
    componentDidMount: function() {},
    componentWillUnmount: function() {},
    getInitialState: function() {
      return {};
    },
    render: function() {
      var bot, swapDetails, txInfo;
      bot = this.props.bot;
      swapDetails = this.props.swapDetails;
      txInfo = swapDetails.txInfo || {};
      return React.createElement("div", {
        "id": "swap-step-4",
        "className": "swap-step"
      }, React.createElement("h2", null, "Successfully finished"), React.createElement("div", {
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
      }), React.createElement("p", null, "Exchanged ", React.createElement("b", null, txInfo.inQty, " ", txInfo.inAsset), " for ", React.createElement("b", null, txInfo.outQty, " ", txInfo.outAsset), " with ", txInfo.address, "."), React.createElement("p", null, React.createElement("a", {
        "href": "#",
        "className": "details-link",
        "target": "_blank"
      }, "Transaction details ", React.createElement("i", {
        "className": "fa fa-arrow-circle-right"
      }))));
    }
  });


  /*
  SwapbotTheRest = React.createClass
      displayName: 'SwapbotTheRest'
  
      getInitialState: ()->
          console.log "this.props",this.props
          return {
              bot: null
              loaded: false
              botId: null
          }
  
      componentDidMount: ()->
  
          containerEl = jQuery(this.getDOMNode()).parent()
          botId = containerEl.data('bot-id')
          this.setState({botId: botId})
          $.get "/api/v1/public/bot/#{botId}", (data)=>
              if this.isMounted()
                  console.log "data",data
                  this.setState({bot: data})
              return
  
  
          console.log "this.state.containerEl=",this.state.containerEl
          console.log " this.state.botId=", this.state.botId
          return
  
      render: ->
          <div className={"swapbot-container " + if this.props.showing? then '' else 'hidden'}>
              <div className="header">
                  <div className="avatar">
                      <img src="http://robohash.org/siemano.png?set=set3" />
                  </div>
                  <div className="status-dot bckg-green"></div>
                  <h1><a href="http://raburski.com/swapbot0" target="_blank">{this.state.bot?.name}</a></h1>
              </div>
              <div className="content">
                  <div id="swap-step-1" className="swap-step">
                      <h2>Choose a token to receive</h2>
                      <div className="segment-control">
                          <div className="line"></div><br/>
                          <div className="dot selected"></div>
                          <div className="dot"></div>
                          <div className="dot"></div>
                          <div className="dot"></div>
                      </div>
                      <p className="description">Short description about LTBCOIN. Short description about LTBCOIN. Short description about LTBCOIN. <a className="more-link" href="#" target="_blank"><i className="fa fa-sign-out"></i></a></p>
                      <ul className="wide-list">
                          <li><a href="#move-to-step-2-for-BTC">
                              <div className="item-header">BTC <small>(7.78973 available)</small></div>
                              <p>Sends 1 BTC for 1,000,000 LTBCOIN or 1,000,000 NOTLTBCOIN.</p>
                              <div className="icon-next"></div>
                          </a></li>
                          <li><a href="#move-to-step-2-for-LTBCOIN">
                              <div className="item-header">LTBCOIN <small>(98778973 available)</small></div>
                              <p>Sends 1 LTBCOIN for each 0.000001 BTC or 1 NOTLTBCOIN.</p>
                              <div className="icon-next"></div>
                          </a></li>
                          <li><a href="#move-to-step-2-for-NOTLTBCOIN">
                              <div className="item-header">NOTLTBCOIN <small>(0 available)</small></div>
                              <p>Sends 1 NOTLTBCOIN for each 1 LTBCOIN or 0.000001 BTC.</p>
                              <div className="icon-denied"></div>
                          </a></li>
                      </ul>
                  </div>
  
                  <div id="swap-step-2" className="swap-step hidden">
                      <h2>Receiving transaction</h2>
                      <div className="segment-control">
                          <div className="line"></div><br/>
                          <div className="dot"></div>
                          <div className="dot selected"></div>
                          <div className="dot"></div>
                          <div className="dot"></div>
                      </div>
                      <table className="fieldset">
                          <tr><td><label htmlFor="token-available">LTBCOIN available for purchase: </label></td>
                          <td><span id="token-available">100,202,020 LTBCOIN</span></td></tr>
  
                          <tr><td><label htmlFor="token-amount">I would like to purchase: </label></td>
                          <td><input type="text" id="token-amount" placeholder="0 LTBCOIN"></td></tr>
                      </table>
                      <ul className="wide-list">
                          <li>
                              <div className="item-header">Send <span id="token-value-1">0</span> BTC to</div>
                              <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys?amount=0.1">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
                              <a href="#open-wallet-url"><div className="icon-wallet"></div></a>
                              <div className="icon-qr"></div>
  
                              <img className="qr-code-image hidden" src="/images/avatars/qrcode.png">
                              <div className="clearfix"></div>
                          </li>
                          <li>
                              <div className="item-header">Send <span id="token-value-2">0</span> NOTLTBCOIN to</div>
                              <p><a href="bitcoin:1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys">1ThEBOtAddr3ssuzhrPVvGFEXeiqESnyys</a></p>
                              <a href="#open-wallet-url"><div className="icon-wallet"></div></a>
                              <div className="icon-qr"></div>
  
                              <img className="qr-code-image hidden" src="/images/avatars/qrcode.png">
                              <div className="clearfix"></div>
                          </li>
                      </ul>
  
                      <p className="description">After receiving one of those token types, this bot will wait for <b>2 confirmations</b> and return tokens <b>to the same address</b>.</p>
                  </div>
  
                  <div id="swap-step-3-other" className="swap-step hidden">
                      <h2>Provide source address</h2>
                      <div className="segment-control">
                          <div className="line"></div><br/>
                          <div className="dot"></div>
                          <div className="dot"></div>
                          <div className="dot selected"></div>
                          <div className="dot"></div>
                      </div>
  
                      <p className="description">Please provide us address you have sent your funds from so we can find your transaction. (or some other warning)</p>
                      <table className="fieldset fieldset-other">
                          <tr><td><input type="text" id="other-address" placeholder="1xxxxxxx..."></td><td><div style={{float:"left"}} id="icon-other-next" className="icon-next"></div></td></tr>
                      </table>
                  </div>
  
                  <div id="swap-step-3" className="swap-step hidden">
                      <h2>Waiting for confirmations</h2>
                      <div className="segment-control">
                          <div className="line"></div><br/>
                          <div className="dot"></div>
                          <div className="dot"></div>
                          <div className="dot selected"></div>
                          <div className="dot"></div>
                      </div>
  
                      <p>
                          Received <b>0.1 BTC</b> from <br/>1MySUperHyPerAddreSSNoTOTak991s.<br/>
                          <a id="not-my-transaction" href="#" className="shadow-link">Not your transaction?</a>
                      </p>
                      <div className="pulse-spinner center">
                          <div className="rect1"></div>
                          <div className="rect2"></div>
                          <div className="rect3"></div>
                          <div className="rect4"></div>
                          <div className="rect5"></div>
                      </div>
  
                      <p>Transaction has <b>0 out of 2</b> required confirmations.</p>
                  </div>
  
                  <div id="swap-step-4" className="swap-step hidden">
                      <h2>Successfully finished</h2>
                      <div className="segment-control">
                          <div className="line"></div><br/>
                          <div className="dot"></div>
                          <div className="dot"></div>
                          <div className="dot"></div>
                          <div className="dot selected"></div>
                      </div>
  
                      <div className="icon-success center"></div>
  
                      <p>Exchanged <b>0.1 BTC</b> for <b>100,000 LTBCOIN</b> with 1MySUperHyPerAddreSSNoTOTak991s.</p>
                      <p><a href="#" className="details-link" target="_blank">Transaction details <i className="fa fa-arrow-circle-right"></i></a></p>
                  </div>
                  <div className="footer">powered by <a href="http://swapbot.co/" target="_blank">Swapbot</a></div>
              </div>
          </div>
   */

}).call(this);
