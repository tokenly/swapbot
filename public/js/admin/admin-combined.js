(function() {
  var robohashURLBase, sbAdmin, swapbot;

  sbAdmin = {
    ctrl: {}
  };

  sbAdmin.api = (function() {
    var api, buildFileHash, newNonce, signRequest, signURLParameters;
    api = {};
    buildFileHash = function(file, callbackFn) {
      var reader;
      reader = new FileReader();
      reader.onloadend = function() {
        var binaryFileContents, fileHash;
        binaryFileContents = reader.result;
        fileHash = CryptoJS.SHA256(CryptoJS.enc.Latin1.parse(binaryFileContents)).toString();
        callbackFn(fileHash);
      };
      reader.onerror = function(evt) {
        console.error('error reading file');
      };
      reader.readAsBinaryString(file);
    };
    signRequest = function(xhr, xhrOptions) {
      var credentials, nonce, paramsBody, parser, ref, signature, url;
      credentials = sbAdmin.auth.getCredentials();
      if (!((ref = credentials.apiToken) != null ? ref.length : void 0)) {
        return;
      }
      nonce = newNonce();
      if ((xhrOptions.data != null) && xhrOptions.data !== 'null') {
        if (xhrOptions.data instanceof FormData && (xhrOptions.paramsToSign != null)) {
          paramsBody = window.JSON.stringify(xhrOptions.paramsToSign);
        } else if (typeof xhrOptions.data === 'object') {
          paramsBody = window.JSON.stringify(xhrOptions.data);
        } else {
          paramsBody = xhrOptions.data;
        }
      } else {
        paramsBody = '{}';
      }
      parser = document.createElement('a');
      parser.href = window.location.protocol + '//' + window.location.host + xhrOptions.url;
      url = parser.protocol + '//' + parser.host + parser.pathname;
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
    api.shutdownBot = function(id, botAttributes) {
      return api.send('POST', "bots/shutdown/" + id, botAttributes);
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
    api.getSwapEventStream = function(id, additionalOpts) {
      if (additionalOpts == null) {
        additionalOpts = {};
      }
      return api.send('GET', "swapevents/" + id, null, additionalOpts);
    };
    api.getSwap = function(id) {
      return api.send('GET', "swaps/" + id);
    };
    api.refreshBalances = function(id) {
      return api.send('POST', "balancerefresh/" + id, null, {
        background: true
      });
    };
    api.newUser = function(userAttributes) {
      return api.send('POST', 'users', userAttributes);
    };
    api.updateUser = function(id, userAttributes) {
      return api.send('PUT', "users/" + id, userAttributes);
    };
    api.getAllUsers = function() {
      return api.send('GET', 'users');
    };
    api.getUser = function(id) {
      return api.send('GET', "users/" + id);
    };
    api.newSettings = function(settingAttributes) {
      return api.send('POST', 'settings', settingAttributes);
    };
    api.updateSettings = function(id, settingAttributes) {
      return api.send('PUT', "settings/" + id, settingAttributes);
    };
    api.getAllSettings = function() {
      return api.send('GET', 'settings');
    };
    api.getSettings = function(id) {
      return api.send('GET', "settings/" + id);
    };
    api.getBotPaymentBalances = function(id) {
      return api.send('GET', "payments/" + id + "/balances");
    };
    api.getAllBotPayments = function(id) {
      return api.send('GET', "payments/" + id + "/all");
    };
    api.getAllPlansData = function() {
      return api.send('GET', "plans");
    };
    api.uploadImage = function(files) {
      var additionalOpts, deferred, formData, rawFormData;
      deferred = m.deferred();
      formData = new FormData;
      rawFormData = [];
      if (files.length > 1) {
        console.error('only 1 image may be uploaded');
        return;
      }
      additionalOpts = {
        serialize: function(value) {
          return value;
        }
      };
      formData.append('image', files[0]);
      buildFileHash(files[0], function(fileHash) {
        formData.append('filehash', fileHash);
        additionalOpts.paramsToSign = {
          filehash: fileHash
        };
        return api.send('POST', "images", formData, additionalOpts).then(function(apiResponse) {
          deferred.resolve(apiResponse);
        }, function(errorResponse) {
          deferred.reject(errorResponse);
        });
      });
      return deferred.promise;
    };
    api.getBotsForAllUsers = function() {
      return api.send('GET', 'bots', {
        allusers: ''
      });
    };
    api.getSwapsForAllUsers = function(filters) {
      if (filters == null) {
        filters = null;
      }
      return api.send('GET', 'swaps', filters);
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
      opts.extract = function(xhr, xhrOptions) {
        var code, e, errMsg, json, newError;
        try {
          if (xhr.responseText.length) {
            json = window.JSON.parse(xhr.responseText);
          }
          code = "" + xhr.status;
          if (code.substr(0, 1) !== '2') {
            if (((json != null ? json.errors : void 0) != null) && json.errors.length > 0) {
              newError = new Error();
              newError.errors = json.errors;
              newError.message = json.message;
              throw newError;
            }
            throw new Error('invalid response code: ' + code);
          }
          if (xhr.responseText.length) {
            return xhr.responseText;
          }
          return '""';
        } catch (_error) {
          e = _error;
          if (e.errors != null) {
            throw e;
          }
          console.error("e=", e);
          code = xhr.status;
          errMsg = "Received an invalid response from server (" + code + ")";
          newError = new Error();
          newError.errors = [errMsg];
          throw newError;
        }
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
        window.location.href = '/account/login';
      }
    };
    auth.isLoggedIn = function() {
      var credentials, ref, ref1;
      credentials = auth.getCredentials();
      if (((ref = credentials.apiToken) != null ? ref.length : void 0) > 0 && ((ref1 = credentials.apiSecretKey) != null ? ref1.length : void 0) > 0) {
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

  sbAdmin.botPaymentUtils = (function() {
    var assetPostfix, botPaymentUtils, buildPaymentDetails, buildPaymentStatusAndDetails, buildPocketsBtn, monthOpts, paymentOpts, paymentPrices;
    botPaymentUtils = {};
    paymentPrices = function(allPlansData, btcUSDValue) {
      var id, key, planData, prices, qty, ref;
      prices = {};
      if ((allPlansData != null ? allPlansData.monthly001 : void 0) != null) {
        ref = allPlansData.monthly001.monthlyRates;
        for (id in ref) {
          planData = ref[id];
          key = planData.asset;
          qty = planData.quantity;
          if (planData.fiatAmount != null) {
            qty = planData.fiatAmount / btcUSDValue;
            qty = qty * 1.005;
          }
          prices[key] = qty;
        }
      }
      return prices;
    };
    paymentOpts = function(allPlansData, btcUSDValue) {
      var asset, opts, quantity, ref;
      opts = [
        {
          k: '- Choose One -',
          v: ''
        }
      ];
      ref = paymentPrices(allPlansData, btcUSDValue);
      for (asset in ref) {
        quantity = ref[asset];
        if (quantity != null) {
          opts.push({
            k: sbAdmin.currencyutils.formatValue(quantity, asset) + assetPostfix(quantity, asset, btcUSDValue),
            v: asset
          });
        }
      }
      return opts;
    };
    assetPostfix = function(qty, asset, btcUSDValue) {
      if (asset !== 'BTC') {
        return '';
      }
      return " (" + (sbAdmin.currencyutils.formatFiatCurrency(qty * btcUSDValue)) + ")";
    };
    monthOpts = function() {
      var i, j, monthOptions;
      monthOptions = [
        {
          k: '- Choose One -',
          v: ''
        }
      ];
      for (i = j = 1; j <= 36; i = ++j) {
        monthOptions.push({
          k: i + ' Month' + (i === 1 ? '' : 's'),
          v: i
        });
      }
      return monthOptions;
    };
    buildPocketsBtn = function(address, monthsText, quantity, asset) {
      return sbAdmin.pocketsUtils.buildPaymentButton(address, "Swapbot Payment for " + monthsText, quantity, asset);
    };
    buildPaymentDetails = function(asset, months, allPlansData, btcUSDValue) {
      var monthsText, price, quantity;
      monthsText = months + ' month' + (months > 1 ? 's' : '');
      price = paymentPrices(allPlansData, btcUSDValue)[asset];
      quantity = months * price;
      return [monthsText, quantity];
    };
    buildPaymentStatusAndDetails = function(botEventsProp) {
      var botEvent, botEvents, details, event, isOld, j, len, status;
      status = 'watching';
      details = null;
      botEvents = botEventsProp().slice(0);
      botEvents.reverse();
      for (j = 0, len = botEvents.length; j < len; j++) {
        botEvent = botEvents[j];
        event = botEvent.event;
        if (event.name === 'payment.unconfirmed' || event.name === 'payment.confirmed') {
          isOld = true;
          if (moment(botEvent.createdAt).add(2, 'minutes').isAfter(moment())) {
            isOld = false;
          }
          if (isOld) {
            continue;
          }
          if (event.name === 'payment.unconfirmed') {
            status = 'unconfirmed';
          } else if (event.name === 'payment.confirmed') {
            status = 'confirmed';
          } else {
            continue;
          }
          details = {
            txid: event.txid,
            source: event.source,
            inAsset: event.inAsset,
            inQty: event.inQty
          };
        }
      }
      return [status, details];
    };
    botPaymentUtils.buildFormattedBotDueDateText = function(payments, balances) {
      var dueDate, formattedDate, now;
      dueDate = botPaymentUtils.buildBotDueDate(payments, balances);
      if (dueDate == null) {
        return m('span', {
          "class": "label label-default label-big"
        }, 'Unknown');
      }
      now = moment();
      formattedDate = dueDate.format('MMM D YYYY, h:mm a');
      if (dueDate.isBefore(moment())) {
        return m('span', {
          "class": "label label-danger label-big"
        }, 'Past Due');
      }
      if (dueDate.isBefore(moment().add(1, 'week'))) {
        return [
          '', m('span', {
            "class": "label label-danger label-big"
          }, formattedDate)
        ];
      }
      if (dueDate.isBefore(moment().add(1, 'month'))) {
        return [
          '', m('span', {
            "class": "label label-warning label-big"
          }, formattedDate)
        ];
      }
      if (dueDate.isBefore(moment().add(2, 'months'))) {
        return [
          '', m('span', {
            "class": "label label-primary label-big"
          }, formattedDate)
        ];
      }
      return [
        '', m('span', {
          "class": "label label-success label-big"
        }, formattedDate)
      ];
    };
    botPaymentUtils.buildBotDueDate = function(payments, balances) {
      var dueDate, lastPayment, monthsToAdd, swapbotMonthBalance;
      if ((payments == null) || payments.length === 0) {
        return null;
      }
      lastPayment = null;
      payments.map(function(botPaymentObj) {
        var dateObj;
        if (!botPaymentObj.isCredit && botPaymentObj.asset === 'SWAPBOTMONTH') {
          dateObj = window.moment(botPaymentObj.createdAt);
          if (lastPayment != null) {
            if (dateObj.diff(lastPayment) > 0) {
              return lastPayment = dateObj;
            }
          } else {
            return lastPayment = dateObj;
          }
        }
      });
      if (lastPayment == null) {
        return null;
      }
      swapbotMonthBalance = 0;
      balances.map(function(balanceEntry) {
        var asset, quantity;
        asset = balanceEntry.asset;
        quantity = balanceEntry.val;
        if (asset === 'SWAPBOTMONTH') {
          return swapbotMonthBalance = quantity;
        }
      });
      monthsToAdd = 1 + swapbotMonthBalance;
      dueDate = lastPayment.clone().add(monthsToAdd, 'months');
      return dueDate;
    };
    botPaymentUtils.buildMakePaymentPulldown = function(paymentAssetProp, allPlansDataProp, btcUSDValueProp) {
      var paymentOptions;
      paymentOptions = paymentOpts(allPlansDataProp(), btcUSDValueProp());
      return sbAdmin.form.mFormField("Make a Payment With", {
        type: "select",
        options: paymentOptions,
        id: "payment-options"
      }, paymentAssetProp);
    };
    botPaymentUtils.buildMonthsPaymentPulldown = function(monthsProp) {
      var monthOptions;
      monthOptions = monthOpts();
      return sbAdmin.form.mFormField("For How Many Months", {
        type: "select",
        options: monthOptions,
        id: "payment-months"
      }, monthsProp);
    };
    botPaymentUtils.buildPayHereDisplay = function(paymentAssetProp, monthsProp, addressProp, allPlansDataProp, btcUSDValueProp) {
      var asset, months, monthsText, paymentButton, quantity, ref, totalValue;
      asset = paymentAssetProp();
      months = monthsProp();
      if (!asset || !months) {
        return null;
      }
      ref = buildPaymentDetails(asset, months, allPlansDataProp(), btcUSDValueProp()), monthsText = ref[0], quantity = ref[1];
      paymentButton = buildPocketsBtn(addressProp(), monthsText, quantity, asset);
      totalValue = m('span', {
        "class": 'payment-total'
      }, "" + (sbAdmin.currencyutils.formatValue(quantity, asset)) + (assetPostfix(quantity, asset, btcUSDValueProp())) + " ");
      return sbAdmin.form.mValueDisplay("Your Total", {
        id: "payment-total"
      }, [totalValue, ' ', paymentButton]);
    };
    botPaymentUtils.buildReceivingPayment = function(paymentAssetProp, monthsProp, addressProp, botEventsProp, allPlansDataProp, btcUSDValueProp) {
      var asset, href, months, monthsText, msg, paymentButton, paymentDetails, quantity, ref, ref1, status;
      asset = paymentAssetProp();
      months = monthsProp();
      if (!asset || !months) {
        return null;
      }
      ref = buildPaymentStatusAndDetails(botEventsProp), status = ref[0], paymentDetails = ref[1];
      if (status === 'watching') {
        ref1 = buildPaymentDetails(asset, months, allPlansDataProp(), btcUSDValueProp()), monthsText = ref1[0], quantity = ref1[1];
        paymentButton = buildPocketsBtn(addressProp(), monthsText, quantity, asset);
        msg = ["Watching for payment to " + (addressProp()) + " ", paymentButton];
      }
      if (status === 'unconfirmed' || status === 'confirmed') {
        href = "https://chain.so/tx/BTC/" + paymentDetails.txid;
        msg = [
          "Received " + (status === 'unconfirmed' ? 'an' : 'a') + " ", m('a', {
            href: href,
            target: "_blank"
          }, status), " payment of " + (sbAdmin.currencyutils.formatValue(paymentDetails.inQty, paymentDetails.inAsset)) + " from " + paymentDetails.source + "."
        ];
      }
      return sbAdmin.form.mValueDisplay("Payment Status", {
        id: "payment-status",
        "class": "payment-status-" + status
      }, msg);
    };
    return botPaymentUtils;
  })();

  window.botPaymentUtils = sbAdmin.botPaymentUtils;

  sbAdmin.botutils = (function() {
    var botutils, findSetting, settings;
    botutils = {};
    settings = [
      {
        isGroup: true,
        label: 'Swapbot Blue',
        opts: [
          {
            k: 'Blue (Light Tint)',
            v: {
              start: 'rgba(0,29,62,0.30)',
              end: 'rgba(8,85,135,0.30)'
            }
          }, {
            k: 'Blue (Medium Tint)',
            v: {
              start: 'rgba(0,29,62,0.60)',
              end: 'rgba(8,85,135,0.60)'
            }
          }, {
            k: 'Blue (Heavy Tint)',
            v: {
              start: 'rgba(0,29,62,0.90)',
              end: 'rgba(8,85,135,0.90)'
            }
          }
        ]
      }, {
        isGroup: true,
        label: 'Swapbot Green',
        opts: [
          {
            k: 'Green (Light Tint)',
            v: {
              start: 'rgba(32,142,78,0.30)',
              end: 'rgba(46,204,113,0.30)'
            }
          }, {
            k: 'Green (Medium Tint)',
            v: {
              start: 'rgba(32,142,78,0.60)',
              end: 'rgba(46,204,113,0.60)'
            }
          }, {
            k: 'Green (Heavy Tint)',
            v: {
              start: 'rgba(32,142,78,0.90)',
              end: 'rgba(46,204,113,0.90)'
            }
          }
        ]
      }, {
        isGroup: true,
        label: 'Swapbot Yellow',
        opts: [
          {
            k: 'Yellow (Light Tint)',
            v: {
              start: 'rgba(170,138,10,0.30)',
              end: 'rgba(241,196,15,0.30)'
            }
          }, {
            k: 'Yellow (Medium Tint)',
            v: {
              start: 'rgba(170,138,10,0.60)',
              end: 'rgba(241,196,15,0.60)'
            }
          }, {
            k: 'Yellow (Heavy Tint)',
            v: {
              start: 'rgba(170,138,10,0.90)',
              end: 'rgba(241,196,15,0.90)'
            }
          }
        ]
      }, {
        isGroup: true,
        label: 'Swapbot Red',
        opts: [
          {
            k: 'Red (Light Tint)',
            v: {
              start: 'rgba(191,39,24,0.30)',
              end: 'rgba(231,76,6,0.30)'
            }
          }, {
            k: 'Red (Medium Tint)',
            v: {
              start: 'rgba(191,39,24,0.60)',
              end: 'rgba(231,76,6,0.60)'
            }
          }, {
            k: 'Red (Heavy Tint)',
            v: {
              start: 'rgba(191,39,24,0.90)',
              end: 'rgba(231,76,6,0.90)'
            }
          }
        ]
      }
    ];
    botutils.defaultOverlay = function() {
      return settings[0].v;
    };
    botutils.overlayOpts = function() {
      var j, len, opts, setting;
      opts = [];
      opts = [
        {
          k: '- No Overlay -',
          v: ''
        }
      ];
      for (j = 0, len = settings.length; j < len; j++) {
        setting = settings[j];
        opts.push(setting);
      }
      return opts;
    };
    botutils.overlayDesc = function(value) {
      var desc;
      desc = findSetting(value, settings);
      if (desc) {
        return desc;
      }
      return 'No Overlay';
    };
    findSetting = function(value, settings) {
      var j, len, res, setting;
      for (j = 0, len = settings.length; j < len; j++) {
        setting = settings[j];
        if (setting.isGroup != null) {
          res = findSetting(value, setting.opts);
          if (res) {
            return res;
          }
          continue;
        }
        if (setting.v.start === (value != null ? value.start : void 0) && setting.v.end === (value != null ? value.end : void 0)) {
          return setting.k;
        }
      }
      return null;
    };
    return botutils;
  })();

  sbAdmin.csvutils = (function() {
    var csvutils;
    csvutils = {};
    csvutils.dataToCSVString = function(rows) {
      var csv, j, len, row, rowText;
      csv = '';
      for (j = 0, len = rows.length; j < len; j++) {
        row = rows[j];
        rowText = '"' + row.map(function(text, i) {
          console.log("text=", text);
          return text.replace(/"/g, '""');
        }).join('","') + '"';
        csv += rowText + "\n";
      }
      return csv;
    };
    csvutils.CSVDownloadHref = function(csvString) {
      return "data:application/csv;charset=utf-8," + encodeURIComponent(csvString);
    };
    return csvutils;
  })();

  sbAdmin.currencyutils = (function() {
    var SATOSHI, currencyutils;
    currencyutils = {};
    SATOSHI = 100000000;
    currencyutils.satoshisToValue = function(amount, currencyPostfix) {
      if (currencyPostfix == null) {
        currencyPostfix = 'BTC';
      }
      return currencyutils.formatValue(amount / SATOSHI, currencyPostfix);
    };
    currencyutils.formatValue = function(value, currencyPostfix) {
      if (currencyPostfix == null) {
        currencyPostfix = 'BTC';
      }
      if ((value == null) || isNaN(value)) {
        return '';
      }
      return window.numeral(value).format('0,0.[00000000]') + (currencyPostfix.length ? ' ' + currencyPostfix : '');
    };
    currencyutils.formatFiatCurrency = function(value, currencyPrefix) {
      var formattedCurrencyString, prefix;
      if (currencyPrefix == null) {
        currencyPrefix = '$';
      }
      if ((value == null) || isNaN(value)) {
        return '';
      }
      formattedCurrencyString = window.numeral(value).format('0,0.00');
      prefix = '';
      if (formattedCurrencyString === '0.00') {
        prefix = 'less than ';
        formattedCurrencyString = '0.01';
      }
      return prefix + ((currencyPrefix != null ? currencyPrefix.length : void 0) ? currencyPrefix : '') + formattedCurrencyString;
    };
    return currencyutils;
  })();

  sbAdmin.fileHelper = (function() {
    var dragdrop, fileHelper;
    fileHelper = {};
    fileHelper.mImageDisplay = function(label, attributes, imageDetailsProp, imageStyle) {
      var existingImageDetails, imageDisplayOrNone;
      existingImageDetails = imageDetailsProp();
      if ((existingImageDetails != null) && (existingImageDetails[imageStyle + 'Url'] != null)) {
        imageDisplayOrNone = m('div.imageDisplay', attributes, [
          m('img', {
            src: existingImageDetails[imageStyle + 'Url']
          })
        ]);
      } else {
        imageDisplayOrNone = m('div.imageDisplayEmpty', attributes, [
          m('div', {
            "class": 'imageDisplayEmptyLabel'
          }, ['No Image'])
        ]);
      }
      return m("div", {
        "class": "form-group"
      }, [
        m("label", {
          "for": attributes.id,
          "class": 'control-label'
        }, label), imageDisplayOrNone
      ]);
    };
    dragdrop = function(element, options) {
      var activate, deactivate, update;
      options = options || {};
      activate = function(e) {
        e.preventDefault();
        element.className = 'uploader upload-active';
      };
      deactivate = function() {
        return element.className = 'uploader';
      };
      update = function(e) {
        e.preventDefault();
        if (typeof options.onchange === 'function') {
          options.onchange((e.dataTransfer || e.target).files);
        }
      };
      element.addEventListener('dragover', activate);
      element.addEventListener('dragleave', deactivate);
      element.addEventListener('dragend', deactivate);
      element.addEventListener('drop', deactivate);
      element.addEventListener('drop', update);
      window.addEventListener('blur', deactivate);
    };
    fileHelper.mImageUploadAndDisplay = function(label, attributes, imageIdProp, imageDetailsProp, imageStyle) {
      var existingImageDetails, fileUploadDomEl, fileUploadEl, imageDisplayOrUpload, onChange, onFileChange, removeImgFn, sizeDesc, tryAgainFn;
      onChange = function(files) {
        console.log("onChange!  files=", files);
        imageDetailsProp({
          'uploading': true
        });
        sbAdmin.api.uploadImage(files).then(function(apiResponse) {
          console.log("apiResponse=", apiResponse);
          imageIdProp(apiResponse.id);
          return imageDetailsProp(apiResponse.imageDetails);
        }, function(apiError) {
          console.log("error: ", apiError);
          imageDetailsProp({
            'error': "Unable to upload this file. Please check the filesize."
          });
        });
        m.redraw(true);
      };
      attributes.config = function(element, isInitialized) {
        if (!isInitialized) {
          dragdrop(element, {
            onchange: onChange
          });
        }
      };
      fileUploadDomEl = null;
      attributes.onclick = function(e) {
        if (fileUploadDomEl != null) {
          e.stopPropagation();
          fileUploadDomEl.click();
        }
      };
      sizeDesc = null;
      if (attributes.sizeDesc) {
        sizeDesc = attributes.sizeDesc;
        delete attributes.sizeDesc;
      }
      onFileChange = function(e) {
        var files;
        console.log("onFileChange fileUploadDomEl=", fileUploadDomEl);
        if (fileUploadDomEl != null) {
          files = fileUploadDomEl.files;
          console.log("files=", files);
          onChange(files);
          e.stopPropagation();
        }
      };
      removeImgFn = function(e) {
        imageIdProp(null);
        imageDetailsProp(null);
        e.preventDefault();
      };
      tryAgainFn = function(e) {
        imageIdProp(null);
        imageDetailsProp(null);
        e.preventDefault();
        e.stopPropagation();
      };
      fileUploadEl = m('input', {
        type: 'file',
        onchange: onFileChange,
        style: {
          display: 'none'
        },
        config: function(domEl, isInitialized) {
          fileUploadDomEl = domEl;
        }
      });
      existingImageDetails = imageDetailsProp();
      if ((existingImageDetails != null) && (existingImageDetails[imageStyle + 'Url'] != null)) {
        imageDisplayOrUpload = m('div.imageDisplay', attributes, [
          m('img', {
            src: existingImageDetails[imageStyle + 'Url']
          }), m("a", {
            "class": "remove-link",
            href: '#remove',
            onclick: removeImgFn
          }, [
            m("span", {
              "class": "glyphicon glyphicon-remove-circle",
              title: "Remove Image"
            }, ''), " Remove Image"
          ])
        ]);
      } else if ((existingImageDetails != null) && (existingImageDetails['uploading'] != null)) {
        imageDisplayOrUpload = m('div.uploadingDisplay', attributes, [
          m('span', {
            "class": 'fileUploadingLabel'
          }, ['Uploading Image'])
        ]);
      } else if ((existingImageDetails != null) && (existingImageDetails['error'] != null)) {
        imageDisplayOrUpload = m('div.uploadingDisplay', attributes, [
          m('span', {
            "class": 'error'
          }, existingImageDetails['error']), m('br'), m("a", {
            "class": "clear-error",
            href: '#try-again',
            onclick: tryAgainFn
          }, ['Try Again'])
        ]);
      } else {
        imageDisplayOrUpload = m('div.uploader', attributes, [
          m('span', {
            "class": 'fileUploadLabel'
          }, ['Drop An Image Here or', m('br'), 'Click to Upload (2 MB Max)', sizeDesc ? [m('br'), sizeDesc] : void 0]), fileUploadEl
        ]);
      }
      return m("div", {
        "class": "form-group"
      }, [
        m("label", {
          "for": attributes.id,
          "class": 'control-label'
        }, label), imageDisplayOrUpload
      ]);
    };
    fileHelper.submit = function(apiCallFn, apiCallArgs, errorsProp, fileHelperStatusProp) {
      if (fileHelperStatusProp() === 'submitting') {
        return;
      }
      errorsProp([]);
      fileHelperStatusProp('submitting');
      return apiCallFn.apply(null, apiCallArgs).then(function(apiResponse) {
        fileHelperStatusProp('submitted');
        return apiResponse;
      }, function(error) {
        fileHelperStatusProp('active');
        errorsProp(error.errors);
        return m.deferred().reject(error).promise;
      });
    };
    return fileHelper;
  })();

  sbAdmin.formGroup = (function() {
    var buildGroupProp, buildNewItem, buildRemoveItemFn, groupBuilder;
    groupBuilder = {};
    buildGroupProp = function(config) {
      var emptyItem;
      emptyItem = buildNewItem(config);
      return m.prop([emptyItem]);
    };
    buildNewItem = function(config, defaultValues) {
      var emptyItem, fieldDef, j, len, ref, value;
      if (defaultValues == null) {
        defaultValues = null;
      }
      emptyItem = {};
      ref = config.fields;
      for (j = 0, len = ref.length; j < len; j++) {
        fieldDef = ref[j];
        value = '';
        if (defaultValues != null ? defaultValues[fieldDef.name] : void 0) {
          value = defaultValues[fieldDef.name];
        }
        emptyItem[fieldDef.name] = m.prop(value);
      }
      return emptyItem;
    };
    buildRemoveItemFn = function(number, groupProp) {
      return function(e) {
        var newItems;
        e.preventDefault();
        newItems = groupProp().filter(function(item, index) {
          return index !== number - 1;
        });
        groupProp(newItems);
      };
    };
    groupBuilder.newGroup = function(config) {
      var formGroup, idPrefix, newRowBuilder, numberOfColumns;
      formGroup = {};
      idPrefix = config.id || "group";
      config.displayOnly = config.displayOnly || false;
      numberOfColumns = config.displayOnly ? 12 : 11;
      newRowBuilder = function(number, item) {
        var rowBuilder;
        rowBuilder = {};
        rowBuilder.field = function(labelText, propName, placeholder_or_attributes, overrideColumnWidth) {
          var attrs, el, id, prop;
          if (placeholder_or_attributes == null) {
            placeholder_or_attributes = null;
          }
          if (overrideColumnWidth == null) {
            overrideColumnWidth = null;
          }
          prop = item[propName];
          id = idPrefix + "_" + propName + "_" + number;
          if (typeof placeholder_or_attributes === 'object') {
            attrs = placeholder_or_attributes;
            attrs.id = attrs.id || id;
          } else {
            attrs = {
              id: id
            };
            if (placeholder_or_attributes) {
              attrs.placeholder = placeholder_or_attributes;
            }
          }
          if (labelText === null) {
            el = sbAdmin.form.mInputEl(attrs, prop);
          } else {
            el = sbAdmin.form.mFormField(labelText, attrs, prop);
          }
          return {
            colWidth: overrideColumnWidth,
            el: el
          };
        };
        rowBuilder.value = function(labelText, propName, attributes, overrideColumnWidth) {
          var attrs, el, id, prop;
          if (attributes == null) {
            attributes = null;
          }
          if (overrideColumnWidth == null) {
            overrideColumnWidth = null;
          }
          prop = item[propName];
          id = idPrefix + "_" + propName + "_" + number;
          if (typeof attributes === 'object') {
            attrs = attributes;
            attrs.id = attrs.id || id;
          } else {
            attrs = {
              id: id
            };
          }
          if (labelText === null) {
            el = m("span", {}, prop());
          } else {
            el = sbAdmin.form.mValueDisplay(labelText, attrs, prop());
          }
          return {
            colWidth: overrideColumnWidth,
            el: el
          };
        };
        rowBuilder.header = function(headerText) {
          return m("h4", headerText);
        };
        rowBuilder.row = function(rowBuilderFieldDefs) {
          var colEls, colSizes, overrides, rowBuilderFieldDef, rowBuilderFieldDefsCount;
          rowBuilderFieldDefsCount = rowBuilderFieldDefs.length;
          overrides = (function() {
            var j, len, results;
            results = [];
            for (j = 0, len = rowBuilderFieldDefs.length; j < len; j++) {
              rowBuilderFieldDef = rowBuilderFieldDefs[j];
              results.push(rowBuilderFieldDef.colWidth);
            }
            return results;
          })();
          colSizes = sbAdmin.utils.splitColumnsWithOverrides(rowBuilderFieldDefsCount, numberOfColumns, overrides);
          colEls = rowBuilderFieldDefs.map(function(rowBuilderFieldDef, offset) {
            return m("div", {
              "class": "col-md-" + colSizes[offset]
            }, rowBuilderFieldDef.el);
          });
          if (!config.displayOnly) {
            colEls.push(m("div", {
              "class": "col-md-1"
            }, [
              m("a", {
                "class": "remove-link" + (config.useCompactNumberedLayout != null ? " remove-link-compact" : ""),
                href: '#remove',
                onclick: buildRemoveItemFn(number, formGroup.prop),
                style: number === 1 ? {
                  display: 'none'
                } : ""
              }, [
                m("span", {
                  "class": "glyphicon glyphicon-remove-circle",
                  title: "Remove Item " + number
                }, '')
              ])
            ]));
          }
          return m("div", {
            "class": "item-group" + (config.useCompactNumberedLayout != null ? " form-group" : "")
          }, [
            m("div", {
              "class": "row"
            }, colEls)
          ]);
        };
        return rowBuilder;
      };
      formGroup.prop = buildGroupProp(config);
      formGroup.buildInputs = function() {
        var inputs;
        if (config.buildAllItemRows != null) {
          return config.buildAllItemRows(formGroup.prop());
        }
        inputs = formGroup.prop().map(function(item, offset) {
          var number, row;
          number = offset + 1;
          row = config.buildItemRow(newRowBuilder(number, item), number, item);
          return row;
        });
        inputs.push(m("div", {
          "class": "form-group"
        }, [
          m("a", {
            "class": "",
            href: '#add',
            onclick: formGroup.addItem
          }, [
            m("span", {
              "class": "glyphicon glyphicon-plus"
            }, ''), m("span", {}, " " + (config.addLabel || "Add Another Item"))
          ])
        ]));
        return inputs;
      };
      formGroup.buildValues = function() {
        var values;
        if (config.buildAllItemRows != null) {
          return config.buildAllItemRows(formGroup.prop());
        }
        values = formGroup.prop().map(function(item, offset) {
          var number, row;
          number = offset + 1;
          row = config.buildItemRow(newRowBuilder(number, item), number, item);
          return row;
        });
        return values;
      };
      formGroup.addItem = function(e) {
        var emptyItem;
        e.preventDefault();
        emptyItem = buildNewItem(config);
        formGroup.prop().push(emptyItem);
      };
      formGroup.unserialize = function(itemsData) {
        var itemData, j, len, newItems, rawItemData;
        newItems = [];
        for (j = 0, len = itemsData.length; j < len; j++) {
          rawItemData = itemsData[j];
          if (config.translateFieldToNumberedValues != null) {
            itemData = {};
            itemData[config.translateFieldToNumberedValues] = rawItemData;
          } else {
            itemData = rawItemData;
          }
          newItems.push(buildNewItem(config, itemData));
        }
        if (!itemsData || !itemsData.length) {
          newItems.push(buildNewItem(config));
        }
        formGroup.prop(newItems);
      };
      formGroup.serialize = function() {
        var j, len, prop, ref, serializedData;
        if (config.translateFieldToNumberedValues != null) {
          serializedData = [];
          ref = formGroup.prop();
          for (j = 0, len = ref.length; j < len; j++) {
            prop = ref[j];
            serializedData.push(prop[config.translateFieldToNumberedValues]());
          }
        } else {
          serializedData = formGroup.prop();
        }
        return serializedData;
      };
      return formGroup;
    };
    return groupBuilder;
  })();

  sbAdmin.form = (function() {
    var buildLabelEl, buildOpts, form;
    form = {};
    buildLabelEl = function(label, id) {
      var k, labelText, properties, v;
      if (typeof label === 'object') {
        labelText = label.text;
        properties = {
          "for": id,
          "class": 'control-label'
        };
        for (k in label) {
          v = label[k];
          if (k === 'text') {
            continue;
          }
          properties[k] = v;
        }
      } else {
        labelText = label;
        properties = {
          "for": id,
          "class": 'control-label'
        };
      }
      return m("label", properties, labelText);
    };
    form.mValueDisplay = function(label, attributes, value) {
      var id, inputEl, inputProps;
      inputProps = sbAdmin.utils.clone(attributes);
      if (inputProps["class"] == null) {
        inputProps["class"] = 'form-control-static';
      }
      id = inputProps.id || 'value';
      return m("div", {
        "class": "form-group"
      }, [buildLabelEl(label, id), inputEl = m("div", inputProps, value)]);
    };
    form.mFormField = function(label, attributes, prop) {
      var inputEl;
      inputEl = form.mInputEl(attributes, prop);
      return m("div", {
        "class": "form-group"
      }, [buildLabelEl(label, attributes.id), inputEl]);
    };
    form.mInputEl = function(attributes, prop) {
      var inputEl, inputProps, name, options;
      if (prop == null) {
        prop = null;
      }
      inputProps = sbAdmin.utils.clone(attributes);
      name = inputProps.name || inputProps.id;
      if (prop != null) {
        if (attributes.onchange != null) {
          inputProps.onchange = function(e) {
            attributes.onchange(e);
            return (m.withAttr("value", prop))(e);
          };
        } else {
          inputProps.onchange = m.withAttr("value", prop);
        }
        inputProps.value = prop();
      }
      if (inputProps["class"] == null) {
        inputProps["class"] = 'form-control';
      }
      if (inputProps.name == null) {
        inputProps.name = inputProps.id;
      }
      delete inputProps.prefix;
      delete inputProps.postfix;
      switch (inputProps.type) {
        case 'textarea':
          delete inputProps.type;
          inputProps.rows = inputProps.rows || 3;
          inputEl = m("textarea", inputProps);
          break;
        case 'select':
          delete inputProps.type;
          options = inputProps.options || [
            {
              k: '- None -',
              v: ''
            }
          ];
          inputEl = m("select", inputProps, buildOpts(options));
          break;
        default:
          inputEl = m("input", inputProps);
      }
      if ((attributes.prefix != null) || (attributes.postfix != null)) {
        return m('div', {
          "class": 'input-group'
        }, [
          attributes.prefix != null ? m('div', {
            "class": 'input-group-addon'
          }, attributes.prefix) : null, inputEl, attributes.postfix != null ? m('div', {
            "class": 'input-group-addon'
          }, attributes.postfix) : null
        ]);
      }
      return inputEl;
    };
    buildOpts = function(opts) {
      return opts.map(function(opt) {
        var val;
        if (opt.isGroup != null) {
          return m("optgroup", {
            label: opt.label
          }, buildOpts(opt.opts));
        }
        val = opt.v;
        if ((val != null) && typeof val === 'object') {
          val = window.JSON.stringify(opt.v);
        }
        return m("option", {
          value: val,
          label: opt.k
        }, opt.k);
      });
    };
    form.mSubmitBtn = function(label, className) {
      if (className == null) {
        className = 'btn btn-primary';
      }
      return m("button", {
        type: 'submit',
        "class": className
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
        formStatusProp('submitted');
        return apiResponse;
      }, function(error) {
        formStatusProp('active');
        errorsProp(error.errors);
        return m.deferred().reject(error).promise;
      });
    };
    form.yesNoOptions = function() {
      return [
        {
          k: "Yes",
          v: '1'
        }, {
          k: "No",
          v: '0'
        }
      ];
    };
    return form;
  })();

  sbAdmin.nav = (function() {
    var buildAdminPanelNavLink, buildRightNav, nav;
    nav = {};
    buildRightNav = function(user) {
      var username;
      username = user != null ? user.name : void 0;
      if (username) {
        return m("ul", {
          "class": "nav navbar-nav navbar-right"
        }, [
          m("li", {
            "class": "dropdown"
          }, [
            m("a[href=#]", {
              "class": "dropdown-toggle",
              "data-toggle": "dropdown",
              "role": "button",
              "aria-expanded": "false"
            }, [
              username, m("span", {
                "class": "caret"
              })
            ]), m("ul", {
              "class": "dropdown-menu",
              role: "menu"
            }, [
              m("li", {
                "class": ""
              }, [
                m("a[href='/account/welcome']", {
                  "class": ""
                }, "My Swapbot Account")
              ]), m("li", {
                "class": ""
              }, [
                m("a[href='/account/logout']", {
                  "class": ""
                }, "Logout")
              ])
            ])
          ])
        ]);
      } else {
        return m("ul", {
          "class": "nav navbar-nav navbar-right"
        }, [
          m("li", {
            "class": ""
          }, [
            m("a[href='/account/login']", {
              "class": ""
            }, "Login")
          ])
        ]);
      }
    };
    buildAdminPanelNavLink = function(user) {
      var els, ref, ref1, ref2, ref3;
      els = [];
      if ((ref = user.privileges) != null ? ref.createUser : void 0) {
        els.push(m("li", {
          "class": ""
        }, [
          m("a[href='/admin/users']", {
            "class": "",
            config: m.route
          }, "Manage Users")
        ]));
      }
      if ((ref1 = user.privileges) != null ? ref1.viewBots : void 0) {
        els.push(m("li", {
          "class": ""
        }, [
          m("a[href='/admin/allbots']", {
            "class": "",
            config: m.route
          }, "Show All Bots")
        ]));
      }
      if ((ref2 = user.privileges) != null ? ref2.viewBots : void 0) {
        els.push(m("li", {
          "class": ""
        }, [
          m("a[href='/admin/allswaps']", {
            "class": "",
            config: m.route
          }, "Show All Swaps")
        ]));
      }
      if ((ref3 = user.privileges) != null ? ref3.manageSettings : void 0) {
        els.push(m("li", {
          "class": ""
        }, [
          m("a[href='/admin/settings']", {
            "class": "",
            config: m.route
          }, "Global Settings")
        ]));
      }
      if (els.length > 1) {
        return m("li", {
          "class": "dropdown"
        }, [
          m("a[href=#]", {
            "class": "dropdown-toggle",
            "data-toggle": "dropdown",
            "role": "button",
            "aria-expanded": "false"
          }, [
            'Admin Controls', m("span", {
              "class": "caret"
            })
          ]), m("ul", {
            "class": "dropdown-menu",
            role: "menu"
          }, els)
        ]);
      }
      return els;
    };
    nav.buildNav = function() {
      var user;
      user = sbAdmin.auth.getUser();
      return m("nav", {
        "class": "navbar navbar-default"
      }, [
        m("div", {
          "class": "container-fluid"
        }, [
          m("div", {
            "class": "navbar-header"
          }, [
            m("a[href='/admin/dashboard']", {
              "class": "navbar-brand",
              config: m.route
            }, "Swapbot Admin")
          ]), m("ul", {
            "class": "nav navbar-nav"
          }, [
            m("li", {
              "class": ""
            }, [
              m("a[href='/admin/dashboard']", {
                "class": "",
                config: m.route
              }, "Dashboard")
            ]), m("li", {
              "class": ""
            }, [
              m("a[href='/admin/edit/bot/new']", {
                "class": "",
                config: m.route
              }, "New Bot")
            ]), buildAdminPanelNavLink(user), m("li", {
              "class": ""
            }, [
              m("a[href='https://www.youtube.com/watch?v=MCdFHx3yTfE']", {
                target: "_blank"
              }, [
                m('span', {
                  "class": "glyphicon glyphicon-film"
                }, ''), " Tutorial Video"
              ])
            ])
          ]), buildRightNav(user)
        ])
      ]);
    };
    nav.buildInContainer = function(mEl) {
      return m("div", {
        "class": "container",
        style: {
          marginTop: "0px",
          marginBottom: "24px"
        }
      }, [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12 col-lg-10 col-lg-offset-1"
          }, [mEl])
        ])
      ]);
    };
    return nav;
  })();

  sbAdmin.planutils = (function() {
    var planutils;
    planutils = {};
    planutils.paymentPlanDesc = function(planID, allPlansData) {
      var ref;
      return ((ref = planutils.planData(planID, allPlansData)) != null ? ref.name : void 0) || 'unknown plan ' + planID;
    };
    planutils.planData = function(planID, allPlansData) {
      var plans;
      plans = allPlansData;
      if ((plans != null ? plans[planID] : void 0) != null) {
        return plans[planID];
      }
      return null;
    };
    planutils.allPlanOptions = function(allPlansData) {
      var description, first, k, mrk, mrv, opts, ref, v;
      opts = [];
      for (k in allPlansData) {
        v = allPlansData[k];
        description = '';
        if (v.type === 'monthly') {
          description += ' / ';
          first = true;
          ref = v.monthlyRates;
          for (mrk in ref) {
            mrv = ref[mrk];
            description += first ? '' : ', ';
            description += mrv.description;
            first = false;
          }
        }
        opts.push({
          k: v.name + description,
          v: v.id
        });
      }
      if (opts.length === 0) {
        opts = [
          {
            k: '- No Plans Available -',
            v: ''
          }
        ];
      }
      return opts;
    };
    return planutils;
  })();

  sbAdmin.pocketsUtils = (function() {
    var pocketsImage, pocketsUrl, pocketsUtils;
    pocketsUtils = {};
    pocketsUrl = null;
    pocketsImage = null;
    pocketsUtils.buildPaymentButton = function(address, label, amount, acceptedTokens) {
      var encodedLabel, urlAttributes;
      if (amount == null) {
        amount = null;
      }
      if (acceptedTokens == null) {
        acceptedTokens = 'btc';
      }
      if (!pocketsUrl) {
        return null;
      }
      encodedLabel = encodeURIComponent(label).replace(/[!'()*]/g, escape);
      urlAttributes = "?address=" + address + "&label=" + encodedLabel + "&tokens=" + acceptedTokens;
      if (amount != null) {
        urlAttributes += '&amount=' + swapbot.formatters.formatCurrencyAsNumber(amount);
      }
      return m("a", {
        href: pocketsUrl + urlAttributes,
        "class": "pocketsLink",
        title: "Pay Using Tokenly Pockets",
        target: "_blank"
      }, [
        m('img', {
          src: pocketsImage,
          height: '32px',
          'width': '87px'
        })
      ]);
    };
    pocketsUtils.exists = function() {
      return pocketsUrl != null;
    };
    jQuery(function($) {
      var attempts, maxAttempts, tryToLoadURL;
      maxAttempts = 10;
      attempts = 0;
      tryToLoadURL = function() {
        var timeoutRef;
        ++attempts;
        pocketsUrl = $('.pockets-url').text();
        if (pocketsUrl === '') {
          pocketsUrl = null;
          if (attempts > maxAttempts) {
            return;
          }
          timeoutRef = setTimeout(tryToLoadURL, 250);
          return;
        }
        return pocketsImage = $('.pockets-image').text();
      };
      tryToLoadURL();
    });
    return pocketsUtils;
  })();

  sbAdmin.pusherutils = (function() {
    var pusherutils;
    pusherutils = {};
    pusherutils.subscribeToPusherChanel = function(chanelName, callbackFn, pusherUrl) {
      var client;
      if (pusherUrl == null) {
        pusherUrl = null;
      }
      if (pusherUrl == null) {
        pusherUrl = window.PUSHER_URL;
      }
      client = new window.Faye.Client(pusherUrl + "/public");
      client.subscribe("/" + chanelName, function(data) {
        callbackFn(data);
      });
      return client;
    };
    pusherutils.closePusherChanel = function(client) {
      client.disconnect();
    };
    return pusherutils;
  })();

  sbAdmin.quotebotSubscriber = (function() {
    var changeListenerID, changeListeners, currentQuote, handleQuotebotUpdate, quotebotSubscriber;
    currentQuote = null;
    changeListeners = {};
    changeListenerID = 0;
    quotebotSubscriber = {};
    handleQuotebotUpdate = function(quote) {
      var changeListenerCallback, id;
      currentQuote = quote;
      for (id in changeListeners) {
        changeListenerCallback = changeListeners[id];
        changeListenerCallback(currentQuote.last, currentQuote);
      }
    };
    quotebotSubscriber.initSubscriber = function(quotebotURL, apiToken, quotebotPusherURL) {
      var opts, path, pusherClient;
      path = quotebotURL + "/api/v1/quote/all?apitoken=" + apiToken;
      opts = {
        method: 'GET',
        url: path,
        background: true
      };
      m.request(opts).then(function(quotesJSON) {
        var j, len, quote, ref;
        if (quotesJSON.quotes != null) {
          ref = quotesJSON.quotes;
          for (j = 0, len = ref.length; j < len; j++) {
            quote = ref[j];
            if (quote.source === 'bitcoinAverage' && quote.pair === 'USD:BTC') {
              handleQuotebotUpdate(quote);
            }
          }
        }
      }, function(errorResponse) {
        console.error(errorResponse.errors);
      });
      pusherClient = sbAdmin.pusherutils.subscribeToPusherChanel("quotebot_quote_bitcoinAverage_USD_BTC", handleQuotebotUpdate, quotebotPusherURL);
    };
    quotebotSubscriber.addChangeListener = function(changeListenerCallback) {
      changeListeners[++changeListenerID] = changeListenerCallback;
      if (currentQuote != null) {
        changeListenerCallback(currentQuote.last, currentQuote);
      }
      return changeListenerID;
    };
    quotebotSubscriber.removeChangeListener = function(id) {
      delete changeListeners[id];
    };
    return quotebotSubscriber;
  })();

  robohashURLBase = null;

  sbAdmin.robohashUtils = (function() {
    var getRobohashURLBase, robohashUtils;
    robohashUtils = {};
    getRobohashURLBase = function() {
      if (robohashURLBase == null) {
        robohashURLBase = window.ROBOHASH_URL;
      }
      return robohashURLBase;
    };
    robohashUtils.img = function(hash, className) {
      var attrs;
      if (className == null) {
        className = null;
      }
      if (!hash) {
        return null;
      }
      attrs = {
        src: robohashUtils.robohashURL(hash)
      };
      if (className != null) {
        attrs["class"] = className;
      }
      return m("img", attrs);
    };
    robohashUtils.robohashURL = function(hash) {
      if (!hash) {
        return null;
      }
      return (getRobohashURLBase()) + "/" + hash + ".png?set=set3";
    };
    return robohashUtils;
  })();

  sbAdmin.stateutils = (function() {
    var stateutils;
    stateutils = {};
    stateutils.buildStateSpan = function(stateValue) {
      switch (stateValue) {
        case 'brandnew':
          return m("span", {
            "class": 'no'
          }, stateutils.buildStateLabel(stateValue));
        case 'lowfuel':
          return m("span", {
            "class": 'no'
          }, stateutils.buildStateLabel(stateValue));
        case 'active':
          return m("span", {
            "class": 'yes'
          }, stateutils.buildStateLabel(stateValue));
        default:
          return m("span", {
            "class": 'no'
          }, stateutils.buildStateLabel(stateValue));
      }
    };
    stateutils.buildStateLabel = function(stateValue) {
      switch (stateValue) {
        case 'brandnew':
          return "Waiting for Payment";
        case 'lowfuel':
          return "Low Fuel";
        case 'active':
          return "Active";
        default:
          return "Inactive";
      }
    };
    stateutils.buildStateDetails = function(stateValue, planDetails, paymentAddress, botAddress) {
      var details, initialPaymentsCount;
      details = {
        label: '',
        subtitle: '',
        "class": ''
      };
      switch (stateValue) {
        case 'brandnew':
          initialPaymentsCount = 20;
          details.label = stateutils.buildStateLabel(stateValue);
          details.subtitle = "This is a new swapbot and needs to be paid to be activated.  Please send a monthly payment to " + paymentAddress + ".";
          details["class"] = "panel-warning inactive new";
          break;
        case 'lowfuel':
          details.label = stateutils.buildStateLabel(stateValue);
          details.subtitle = m('div', {}, [
            "This swapbot is low on BTC fuel.  Please send 0.005 BTC to " + botAddress + ".", m('br'), m('div', {
              "class": 'note'
            }, "Note: If you've just added fuel or paid to set up this Swapbot, you can ignore this message.")
          ]);
          details["class"] = "panel-warning inactive lowfuel";
          break;
        case 'active':
          details.label = stateutils.buildStateLabel(stateValue);
          details.subtitle = "This swapbot is up and running.  All is well.";
          details["class"] = "panel-success active";
          break;
        default:
          details.label = stateutils.buildStateLabel(stateValue);
          details.subtitle = "This swapbot is inactive.  Swaps are not being processed.";
          details["class"] = "panel-danger inactive deactivated";
      }
      return details;
    };
    stateutils.buildStateDisplay = function(details) {
      return m("div", {
        "class": "panel " + details["class"]
      }, [
        m("div", {
          "class": 'panel-heading'
        }, [
          m("h3", {
            "class": 'panel-title'
          }, details.label)
        ]), m("div", {
          "class": 'panel-body'
        }, details.subtitle)
      ]);
    };
    return stateutils;
  })();

  sbAdmin.swaputils = (function() {
    var strategyLabelCache, swaputils;
    swaputils = {};
    swaputils.newSwapProp = function(swap) {
      if (swap == null) {
        swap = {};
      }
      return m.prop({
        strategy: m.prop(swap.strategy || 'rate'),
        "in": m.prop(swap["in"] || ''),
        out: m.prop(swap.out || ''),
        rate: m.prop(swap.rate || ''),
        in_qty: m.prop(swap.in_qty || ''),
        out_qty: m.prop(swap.out_qty || ''),
        min: m.prop(swap.min || ''),
        cost: m.prop(swap.cost || ''),
        min_out: m.prop(swap.min_out || ''),
        divisible: m.prop(swap.divisible != null ? (swap.divisible ? '1' : '0') : '0')
      });
    };
    swaputils.allStrategyOptions = function() {
      return [
        {
          k: "By Rate",
          v: 'rate'
        }, {
          k: "By Fixed Amounts",
          v: 'fixed'
        }, {
          k: "By USD Amount paid in BTC",
          v: 'fiat'
        }
      ];
    };
    strategyLabelCache = null;
    swaputils.strategyLabelByValue = function(strategyValue) {
      if (strategyLabelCache === null) {
        strategyLabelCache = {};
        swaputils.allStrategyOptions().map(function(opt) {
          strategyLabelCache[opt.v] = opt.k;
        });
      }
      return strategyLabelCache[strategyValue];
    };
    return swaputils;
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
    utils.splitColumns = function(elementsCount, totalColumns) {
      var baseColSize, colSize, cols, cumRemainder, i, isLast, j, ref, remainder, totalColsUsed;
      baseColSize = Math.floor(totalColumns / elementsCount);
      remainder = totalColumns % elementsCount;
      cumRemainder = 0;
      totalColsUsed = 0;
      cols = [];
      for (i = j = 0, ref = elementsCount; 0 <= ref ? j < ref : j > ref; i = 0 <= ref ? ++j : --j) {
        isLast = i === elementsCount - 1;
        if (isLast) {
          colSize = totalColumns - totalColsUsed;
        } else {
          colSize = baseColSize;
          cumRemainder += remainder;
          if (cumRemainder >= elementsCount) {
            cumRemainder -= elementsCount;
            ++colSize;
          }
          totalColsUsed += colSize;
        }
        cols.push(colSize);
      }
      return cols;
    };
    utils.splitColumnsWithOverrides = function(elementsCount, totalColumns, overrides) {
      var cols, colsToSplit, elsToSplit, i, j, l, len, nextSplitColumnOffset, overrideCol, overrideCols, ref, splitColumns;
      overrideCols = [];
      elsToSplit = elementsCount;
      colsToSplit = totalColumns;
      for (i = j = 0, ref = elementsCount; 0 <= ref ? j < ref : j > ref; i = 0 <= ref ? ++j : --j) {
        if (overrides != null ? overrides[i] : void 0) {
          overrideCols.push(overrides[i]);
          colsToSplit -= overrides[i];
          elsToSplit -= 1;
        } else {
          overrideCols.push(-1);
        }
      }
      splitColumns = utils.splitColumns(elsToSplit, colsToSplit);
      cols = [];
      nextSplitColumnOffset = 0;
      for (l = 0, len = overrideCols.length; l < len; l++) {
        overrideCol = overrideCols[l];
        if (overrideCol === -1) {
          cols.push(splitColumns[nextSplitColumnOffset]);
          ++nextSplitColumnOffset;
        } else {
          cols.push(overrideCol);
        }
      }
      return cols;
    };
    utils.buildBalancesMElement = function(balances) {
      if (balances.length > 0) {
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
            balances.map(function(balance, index) {
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
    return utils;
  })();

  window.utils = sbAdmin.utils;

  (function() {
    var vm;
    sbAdmin.ctrl.allbots = {};
    vm = sbAdmin.ctrl.allbots.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.user = m.prop(sbAdmin.auth.getUser());
        vm.bots = m.prop([]);
        vm.botsRefreshing = m.prop('true');
        vm.refreshBots();
      };
      vm.refreshBotsFn = function(e) {
        e.preventDefault();
        vm.refreshBots();
      };
      vm.refreshBots = function() {
        vm.botsRefreshing('true');
        m.redraw(true);
        sbAdmin.api.getBotsForAllUsers().then(function(botsList) {
          vm.bots(botsList);
          vm.botsRefreshing(false);
        });
      };
      return vm;
    })();
    sbAdmin.ctrl.allbots.controller = function() {
      var removeImgFn;
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
      return;
      return removeImgFn = function(e) {
        imageIdProp(null);
        imageDetailsProp(null);
        e.preventDefault();
      };
    };
    return sbAdmin.ctrl.allbots.view = function() {
      var mEl;
      mEl = m("div", [
        m("h2", "All Swapbots"), m("div", {
          "class": "spacer1"
        }), m("p", {
          "class": "pull-right"
        }, [
          m("a[href='#refresh']", {
            onclick: vm.refreshBotsFn
          }, [
            m("span", {
              "class": "glyphicon glyphicon-refresh",
              title: "Refresh"
            }, ''), ' Refresh'
          ])
        ]), m("p", {
          "class": ""
        }, ["Here is a list of all Swapbots."]), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("table", {
              "class": "striped-table bot-table " + (vm.botsRefreshing() ? 'refreshing' : '')
            }, [
              m('thead', {}, [m('tr', {}, [m('th', {}, 'Bot Name'), m('th', {}, 'Admin Link'), m('th', {}, 'State'), m('th', {}, 'Owner')])]), vm.bots().map(function(bot) {
                var address;
                address = swapbot.addressUtils.publicBotAddress(bot.username, bot.id, window.location);
                return m("tr", {}, [
                  m("td", {}, [
                    bot.hash.length ? m("a[href='" + address + "']", {
                      target: "_blank"
                    }, [
                      m("img", {
                        "class": 'tinyRoboHead',
                        src: "http://robohash.tokenly.com/" + bot.hash + ".png?set=set3"
                      })
                    ]) : m('div', {
                      "class": 'emptyRoboHead'
                    }, ''), m("a[href='" + address + "']", {
                      target: "_blank",
                      "class": ""
                    }, "" + bot.name)
                  ]), m("td", {}, [
                    m("a[href='/admin/view/bot/" + bot.id + "']", {
                      "class": "",
                      config: m.route
                    }, "Admin")
                  ]), m("td", {}, bot.state), m("td", {}, bot.username)
                ]);
              })
            ])
          ])
        ]), m("div", {
          "class": "spacer1"
        })
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var vm;
    sbAdmin.ctrl.allswaps = {};
    vm = sbAdmin.ctrl.allswaps.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.user = m.prop(sbAdmin.auth.getUser());
        vm.swaps = m.prop([]);
        vm.swapsRefreshing = m.prop('true');
        vm.swapFilterState = m.prop('confirming');
        vm.refreshSwaps();
      };
      vm.refreshSwapsFn = function(e) {
        e.preventDefault();
        vm.refreshSwaps();
      };
      vm.refreshSwaps = function() {
        var filters;
        vm.swapsRefreshing('true');
        m.redraw(true);
        filters = {};
        if (vm.swapFilterState().length) {
          filters.state = vm.swapFilterState();
        }
        filters.sort = 'updatedAt';
        sbAdmin.api.getSwapsForAllUsers(filters).then(function(swapslist) {
          vm.swaps(swapslist);
          vm.swapsRefreshing(false);
        });
      };
      vm.changeFilterFn = function(e) {
        e.preventDefault();
        return setTimeout(function() {
          vm.refreshSwaps();
        }, 1);
      };
      vm.exportAsCSV = function(e) {
        var csvHref, csvString, j, len, linkEl, ref, rows, swap;
        rows = [];
        rows.push(['In Qty', 'In Asset', 'Out Qty', 'Out Asset', 'State', 'Updated', 'Bot', 'Owner']);
        ref = vm.swaps();
        for (j = 0, len = ref.length; j < len; j++) {
          swap = ref[j];
          rows.push(["" + swap.receipt.quantityIn, "" + swap.receipt.assetIn, "" + swap.receipt.quantityOut, "" + swap.receipt.assetOut, swap.state, window.moment(swap.updatedAt).format('YYYY-MM-DD HH:mm:ss Z'), swap.botName, swap.botUsername]);
        }
        csvString = sbAdmin.csvutils.dataToCSVString(rows);
        csvHref = sbAdmin.csvutils.CSVDownloadHref(csvString);
        linkEl = e.target;
        linkEl.setAttribute('download', 'export.csv');
        linkEl.setAttribute('href', csvHref);
        linkEl.setAttribute('target', '_blank');
      };
      return vm;
    })();
    sbAdmin.ctrl.allswaps.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.allswaps.view = function() {
      var filterOptions, filterSelectEl, mEl, tableRows;
      filterOptions = [
        {
          k: 'All Swaps',
          v: ''
        }, {
          k: 'Brand New',
          v: 'brandnew'
        }, {
          k: 'Out of Stock',
          v: 'outofstock'
        }, {
          k: 'Ready',
          v: 'ready'
        }, {
          k: 'Confirming',
          v: 'confirming'
        }, {
          k: 'Sent',
          v: 'sent'
        }, {
          k: 'Refunded',
          v: 'refunded'
        }, {
          k: 'Complete',
          v: 'complete'
        }, {
          k: 'Error',
          v: 'error'
        }
      ];
      filterSelectEl = sbAdmin.form.mInputEl({
        type: "select",
        options: filterOptions,
        id: "filter",
        onchange: vm.changeFilterFn
      }, vm.swapFilterState);
      if (vm.swaps().length) {
        tableRows = vm.swaps().map(function(swap) {
          var botAaddress;
          botAaddress = swapbot.addressUtils.publicBotAddress(swap.botUsername, swap.botUuid, window.location);
          return m("tr", {}, [
            m("td", {}, swap.receipt.quantityIn + " " + swap.receipt.assetIn), m("td", {}, swap.receipt.quantityOut + " " + swap.receipt.assetOut), m("td", {}, swap.state), m("td", {}, window.moment(swap.updatedAt).format('MMM D h:mm a')), m("td", {}, [
              m("a[href='/public/" + swap.botUsername + "/swap/" + swap.id + "']", {
                target: "_blank",
                "class": ""
              }, 'Details')
            ]), m("td", {}, [
              m("a[href='/admin/swapevents/" + swap.id + "']", {
                target: "_blank",
                "class": "",
                config: m.route
              }, "Events")
            ]), m("td", {}, [
              m("a[href='" + botAaddress + "']", {
                target: "_blank",
                "class": ""
              }, swap.botName), " | ", m("a[href='/admin/view/bot/" + swap.botUuid + "']", {
                "class": "",
                config: m.route
              }, "Admin")
            ]), m("td", {}, swap.botUsername)
          ]);
        });
      } else {
        tableRows = m("tr", {}, [
          m('td', {
            colspan: 8,
            "class": "not-found"
          }, 'No Swaps Found')
        ]);
      }
      mEl = m("div", [
        m("h2", "All Swaps"), m("div", {
          "class": "spacer1"
        }), m("p", {
          "class": "pull-right"
        }, [
          m("a[href='#refresh']", {
            onclick: vm.refreshSwapsFn
          }, [
            m("span", {
              "class": "glyphicon glyphicon-refresh",
              title: "Refresh"
            }, ''), ' Refresh'
          ])
        ]), m("div", {
          "class": "pull-right filter-select"
        }, [filterSelectEl]), m("p", {
          "class": ""
        }, ["Here is a list of all Swaps."]), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("table", {
              "class": "striped-table swap-table " + (vm.swapsRefreshing() ? 'refreshing' : '')
            }, [m('thead', {}, [m('tr', {}, [m('th', {}, 'In'), m('th', {}, 'Out'), m('th', {}, 'State'), m('th', {}, 'Updated'), m('th', {}, 'Details'), m('th', {}, 'Events'), m('th', {}, 'Bot'), m('th', {}, 'Owner')])]), m('tbody', {}, tableRows)])
          ])
        ]), m("div", {
          "class": "spacer2"
        }), m("a[href='#csvExport']", {
          "class": "btn btn-success",
          onclick: vm.exportAsCSV
        }, "Download as CSV"), m("div", {
          "class": "spacer1"
        })
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var buildBlacklistAddressesGroup, buildDuplicateSwapOffsetsMap, buildIncomeRulesGroup, buildOnSwaptypeChange, duplicateWarning, sharedSwapTypeFormField, swapGroup, swapGroupRenderers, vm;
    sbAdmin.ctrl.botForm = {};
    buildOnSwaptypeChange = function(number, swap) {
      return function(e) {
        var value;
        value = e.srcElement.value;
        if (value === 'fiat') {
          swap["in"]('BTC');
        }
      };
    };
    sharedSwapTypeFormField = function(number, swap) {
      return sbAdmin.form.mFormField("Swap Type", {
        onchange: buildOnSwaptypeChange(number, swap),
        id: "swap_strategy_" + number,
        type: 'select',
        options: sbAdmin.swaputils.allStrategyOptions()
      }, swap.strategy);
    };
    swapGroupRenderers = {};
    swapGroupRenderers.rate = function(number, swap, isDuplicate) {
      return m("div", {
        "class": "asset-group" + (isDuplicate ? ' duplicate-asset-group' : '')
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-3"
          }, [sharedSwapTypeFormField(number, swap)]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField({
              text: "Receives Asset",
              "class": 'control-label receives-label'
            }, {
              id: "swap_in_" + number,
              'placeholder': "BTC"
            }, swap["in"])
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Sends Asset", {
              id: "swap_out_" + number,
              'placeholder': "LTBCOIN"
            }, swap.out)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("At Rate", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_rate_" + number,
              'placeholder': "0.000001"
            }, swap.rate)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Minimum", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_rate_" + number,
              'placeholder': "0.000001"
            }, swap.min)
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
        ]), (isDuplicate ? duplicateWarning() : null)
      ]);
    };
    swapGroupRenderers.fixed = function(number, swap, isDuplicate) {
      return m("div", {
        "class": "asset-group" + (isDuplicate ? ' duplicate-asset-group' : '')
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-3"
          }, [sharedSwapTypeFormField(number, swap)]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField({
              text: "Receives Asset",
              "class": 'control-label receives-label'
            }, {
              id: "swap_in_" + number,
              'placeholder': "BTC"
            }, swap["in"])
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Receives Quantity", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_in_qty_" + number,
              'placeholder': "1"
            }, swap.in_qty)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Sends Asset", {
              id: "swap_out_" + number,
              'placeholder': "LTBCOIN"
            }, swap.out)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Sends Quantity", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_out_qty_" + number,
              'placeholder': "1"
            }, swap.out_qty)
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
        ]), (isDuplicate ? duplicateWarning() : null)
      ]);
    };
    swapGroupRenderers.fiat = function(number, swap, isDuplicate) {
      return m("div", {
        "class": "asset-group" + (isDuplicate ? ' duplicate-asset-group' : '')
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-2"
          }, [sharedSwapTypeFormField(number, swap)]), m("div", {
            "class": "col-md-1"
          }, [
            sbAdmin.form.mValueDisplay({
              text: "Receives",
              "class": 'control-label receives-label'
            }, {
              id: "swap_in_" + number
            }, swap["in"]())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Sends Asset", {
              id: "swap_out_" + number,
              'placeholder': "MYPRODUCT"
            }, swap.out)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("At USD Price", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_cost_" + number,
              'placeholder': "1"
            }, swap.cost)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Minimum", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_min_out_" + number,
              'placeholder': "1"
            }, swap.min_out)
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mFormField("Divisible", {
              type: "select",
              options: sbAdmin.form.yesNoOptions(),
              id: "swap_divisible_" + number
            }, swap.divisible)
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
        ]), (isDuplicate ? duplicateWarning() : null)
      ]);
    };
    swapGroup = function(number, swapProp, isDuplicate) {
      return swapGroupRenderers[swapProp().strategy()](number, swapProp(), isDuplicate);
    };
    duplicateWarning = function() {
      return m("div", {
        "class": "duplicate-warning"
      }, [m('strong', {}, 'Warning:'), " This asset is received by 2 or more swaps. Multiple swaps will be triggered when this asset is received. This is not recommended."]);
    };
    buildIncomeRulesGroup = function() {
      return sbAdmin.formGroup.newGroup({
        id: 'incomerules',
        fields: [
          {
            name: 'asset'
          }, {
            name: 'minThreshold'
          }, {
            name: 'paymentAmount'
          }, {
            name: 'address'
          }
        ],
        addLabel: "Add Another Income Forwarding Rule",
        buildItemRow: function(builder, number, item) {
          return [
            builder.header("Income Forwarding Rule #" + number), builder.row([
              builder.field("Asset Received", 'asset', 'BTC', 3), builder.field("Trigger Threshold", 'minThreshold', {
                type: "number",
                step: "any",
                min: "0",
                placeholder: "1.0"
              }), builder.field("Payment Amount", 'paymentAmount', {
                type: "number",
                step: "any",
                min: "0",
                placeholder: "0.5"
              }), builder.field("Payment Address", 'address', "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 4)
            ])
          ];
        }
      });
    };
    buildBlacklistAddressesGroup = function() {
      return sbAdmin.formGroup.newGroup({
        id: 'blacklist',
        fields: [
          {
            name: 'address'
          }
        ],
        addLabel: " Add Another Blacklist Address",
        buildItemRow: function(builder, number, item) {
          return [builder.row([builder.field(null, 'address', "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 4)])];
        },
        translateFieldToNumberedValues: 'address',
        useCompactNumberedLayout: true
      });
    };
    buildDuplicateSwapOffsetsMap = function(swaps) {
      var duplicateOffsetsMap, offsetByToken;
      duplicateOffsetsMap = {};
      offsetByToken = {};
      swaps().map(function(swap, offset) {
        var inToken;
        inToken = swap()["in"]().toUpperCase();
        if (offsetByToken[inToken] != null) {
          duplicateOffsetsMap[offsetByToken[inToken]] = true;
          return duplicateOffsetsMap[offset] = true;
        } else {
          return offsetByToken[inToken] = offset;
        }
      });
      return duplicateOffsetsMap;
    };
    vm = sbAdmin.ctrl.botForm.vm = (function() {
      var buildBlacklistAddressesPropValue, buildSwapsPropValue;
      buildSwapsPropValue = function(swaps) {
        var j, len, out, swap;
        out = [];
        for (j = 0, len = swaps.length; j < len; j++) {
          swap = swaps[j];
          out.push(sbAdmin.swaputils.newSwapProp(swap));
        }
        if (!out.length) {
          out.push(sbAdmin.swaputils.newSwapProp());
        }
        return out;
      };
      buildBlacklistAddressesPropValue = function(addresses) {
        var address, j, len, out;
        out = [];
        for (j = 0, len = addresses.length; j < len; j++) {
          address = addresses[j];
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
        vm.allPlansData = m.prop(null);
        vm.name = m.prop('');
        vm.description = m.prop('');
        vm.hash = m.prop('');
        vm.paymentPlan = m.prop('monthly001');
        vm.returnFee = m.prop(0.0001);
        vm.confirmationsRequired = m.prop(2);
        vm.refundAfterBlocks = m.prop(3);
        vm.swaps = m.prop([sbAdmin.swaputils.newSwapProp()]);
        vm.incomeRulesGroup = buildIncomeRulesGroup();
        vm.blacklistAddressesGroup = buildBlacklistAddressesGroup();
        vm.backgroundOverlaySettings = m.prop(window.JSON.stringify(sbAdmin.botutils.defaultOverlay()));
        vm.backgroundImageDetails = m.prop('');
        vm.backgroundImageId = m.prop('');
        vm.logoImageDetails = m.prop('');
        vm.logoImageId = m.prop('');
        id = m.route.param('id');
        vm.isNew = id === 'new';
        if (!vm.isNew) {
          sbAdmin.api.getBot(id).then(function(botData) {
            var ref, ref1, ref2, ref3;
            vm.resourceId(botData.id);
            vm.name(botData.name);
            vm.description(botData.description);
            vm.hash(botData.hash);
            vm.paymentPlan(botData.paymentPlan);
            vm.swaps(buildSwapsPropValue(botData.swaps));
            vm.returnFee(botData.returnFee || "0.0001");
            vm.confirmationsRequired(botData.confirmationsRequired || "2");
            vm.refundAfterBlocks(((ref = botData.refundConfig) != null ? ref.refundAfterBlocks : void 0) || "3");
            vm.incomeRulesGroup.unserialize(botData.incomeRules);
            vm.blacklistAddressesGroup.unserialize(botData.blacklistAddresses);
            vm.backgroundOverlaySettings(((ref1 = botData.backgroundOverlaySettings) != null ? ref1.start : void 0) ? window.JSON.stringify(botData.backgroundOverlaySettings) : '');
            vm.backgroundImageDetails(botData.backgroundImageDetails);
            vm.backgroundImageId((ref2 = botData.backgroundImageDetails) != null ? ref2.id : void 0);
            vm.logoImageDetails(botData.logoImageDetails);
            vm.logoImageId((ref3 = botData.logoImageDetails) != null ? ref3.id : void 0);
          }, function(errorResponse) {
            vm.errorMessages(errorResponse.errors);
          });
        }
        sbAdmin.api.getAllPlansData().then(function(apiResponse) {
          vm.allPlansData(apiResponse);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        vm.addSwap = function(e) {
          e.preventDefault();
          vm.swaps().push(sbAdmin.swaputils.newSwapProp());
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
        vm.save = function(e) {
          var apiArgs, apiCall, attributes;
          e.preventDefault();
          attributes = {
            name: vm.name(),
            description: vm.description(),
            hash: vm.hash(),
            paymentPlan: vm.paymentPlan(),
            swaps: vm.swaps(),
            returnFee: vm.returnFee() + "",
            incomeRules: vm.incomeRulesGroup.serialize(),
            blacklistAddresses: vm.blacklistAddressesGroup.serialize(),
            confirmationsRequired: vm.confirmationsRequired() + "",
            refundConfig: {
              refundAfterBlocks: vm.refundAfterBlocks() + ""
            },
            backgroundImageId: vm.backgroundImageId() || '',
            backgroundOverlaySettings: vm.backgroundOverlaySettings() ? window.JSON.parse(vm.backgroundOverlaySettings()) : '',
            logoImageId: vm.logoImageId() || ''
          };
          if (vm.resourceId().length > 0) {
            apiCall = sbAdmin.api.updateBot;
            apiArgs = [vm.resourceId(), attributes];
          } else {
            apiCall = sbAdmin.api.newBot;
            apiArgs = [attributes];
          }
          return sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(function(apiResponse) {
            var botId;
            if (vm.isNew) {
              botId = apiResponse.id;
            } else {
              botId = vm.resourceId();
            }
            m.route("/admin/view/bot/" + botId);
          });
        };
      };
      return vm;
    })();
    sbAdmin.ctrl.botForm.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.botForm.view = function() {
      var duplicateSwapsOffsetsMap, mEl;
      duplicateSwapsOffsetsMap = buildDuplicateSwapOffsetsMap(vm.swaps);
      mEl = m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("div", {
              "class": "row"
            }, [
              m("div", {
                "class": "col-md-10"
              }, [m("h2", vm.resourceId() ? "Edit SwapBot " + (vm.name()) : "Create a New Swapbot")]), m("div", {
                "class": "col-md-2 text-right"
              }, [sbAdmin.robohashUtils.img(vm.hash(), 'mediumRoboHead')])
            ]), m("div", {
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
              }, vm.description), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-8"
                }, [
                  sbAdmin.fileHelper.mImageUploadAndDisplay("Custom Background Image", {
                    id: 'BGImage',
                    sizeDesc: '1440 x 720 Image Recommended'
                  }, vm.backgroundImageId, vm.backgroundImageDetails, 'medium')
                ]), m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.fileHelper.mImageUploadAndDisplay("Custom Logo Image", {
                    id: 'LogoImage',
                    sizeDesc: '100 x 100 Image Recommended'
                  }, vm.logoImageId, vm.logoImageDetails, 'thumb')
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-8"
                }, [
                  sbAdmin.form.mFormField("Background Overlay", {
                    id: "background_overlay",
                    type: 'select',
                    options: sbAdmin.botutils.overlayOpts()
                  }, vm.backgroundOverlaySettings)
                ])
              ]), m("hr"), m("h4", "Settings"), m("div", {
                "class": "spacer1"
              }), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.form.mFormField("Confirmations", {
                    id: 'confirmations_required',
                    'placeholder': "2",
                    type: "number",
                    step: "1",
                    min: "2",
                    max: "6",
                    required: true
                  }, vm.confirmationsRequired)
                ]), m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.form.mFormField("Return Transaction Fee", {
                    id: 'return_fee',
                    'placeholder': "0.0001",
                    type: "number",
                    step: "0.00001",
                    min: "0.00001",
                    max: "0.001",
                    required: true,
                    postfix: 'BTC'
                  }, vm.returnFee)
                ]), m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.form.mFormField("Refund Out of Stock Swaps After", {
                    id: 'refund_after_blocks',
                    'placeholder': "3",
                    type: "number",
                    step: "1",
                    min: "3",
                    max: "72",
                    required: true,
                    postfix: 'blocks'
                  }, vm.refundAfterBlocks)
                ])
              ]), m("h5", "Blacklisted Addresses"), m("p", [m("small", "Blacklisted addresses do not trigger swaps and can be used to load the SwapBot.")]), vm.blacklistAddressesGroup.buildInputs(), m("hr"), m("h4", "Income Forwarding"), m("p", [m("small", "When the bot fills up to a certain amount, you may forward the funds to your own destination address.")]), vm.incomeRulesGroup.buildInputs(), m("hr"), m("h4", "Payment"), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  (vm.isNew ? sbAdmin.form.mFormField("Payment Plan", {
                    id: "payment_plan",
                    type: 'select',
                    options: sbAdmin.planutils.allPlanOptions(vm.allPlansData())
                  }, vm.paymentPlan) : null), (!vm.isNew ? sbAdmin.form.mValueDisplay("Payment Plan", {
                    id: 'payment_plan'
                  }, sbAdmin.planutils.paymentPlanDesc(vm.paymentPlan(), vm.allPlansData())) : null)
                ])
              ]), m("hr"), vm.swaps().map(function(swap, offset) {
                return swapGroup(offset + 1, swap, duplicateSwapsOffsetsMap[offset] != null);
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
                  }, ''), m("span", {}, ' Add Another Swap')
                ])
              ]), m("div", {
                "class": "spacer1"
              }), m("a[href='/admin/dashboard']", {
                "class": "btn btn-default pull-right",
                config: m.route
              }, "Return without Saving"), sbAdmin.form.mSubmitBtn("Save Bot"), m("a[href='/admin/shutdown/bot/" + (vm.resourceId()) + "']", {
                "class": "btn btn-warning ",
                config: m.route,
                style: {
                  'margin-left': '24px'
                }
              }, "Shutdown Bot")
            ])
          ])
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var buildPaymentTypeLabel, curryHandleAccountUpdatesMessage, handleBotEventMessage, updateAllAccountPayments, vm;
    sbAdmin.ctrl.botPaymentsView = {};
    curryHandleAccountUpdatesMessage = function(id) {
      return function(data) {
        updateAllAccountPayments(id);
      };
    };
    updateAllAccountPayments = function(id) {
      sbAdmin.api.getBotPaymentBalances(id).then(function(apiResponse) {
        var asset, paymentBalances, ref, val;
        paymentBalances = [];
        ref = apiResponse.balances;
        for (asset in ref) {
          val = ref[asset];
          paymentBalances.push({
            asset: asset,
            val: val
          });
        }
        vm.paymentBalances(paymentBalances);
      }, function(errorResponse) {
        vm.errorMessages(errorResponse.errors);
      });
      sbAdmin.api.getAllBotPayments(id).then(function(apiResponse) {
        apiResponse.reverse();
        vm.payments(apiResponse);
      }, function(errorResponse) {
        vm.errorMessages(errorResponse.errors);
      });
    };
    buildPaymentTypeLabel = function(isCredit) {
      if (isCredit) {
        return m('span', {
          "class": "label label-success"
        }, "Credit");
      } else {
        return m('span', {
          "class": "label label-warning"
        }, "Debit");
      }
    };
    handleBotEventMessage = function(data) {
      var ref;
      if ((data != null ? (ref = data.event) != null ? ref.msg : void 0 : void 0) || (data != null ? data.message : void 0)) {
        vm.botEvents().unshift(data);
        m.redraw(true);
      }
    };
    vm = sbAdmin.ctrl.botPaymentsView.vm = (function() {
      vm = {};
      vm.init = function() {
        var handleQuotebotUpdate, id;
        vm.errorMessages = m.prop([]);
        vm.resourceId = m.prop('');
        vm.pusherClients = [];
        vm.quotebotSubscriberID = null;
        vm.btcQuote = m.prop(null);
        vm.botEvents = m.prop([]);
        vm.allPlansData = m.prop(null);
        vm.name = m.prop('');
        vm.address = m.prop('');
        vm.paymentAddress = m.prop('');
        vm.paymentPlan = m.prop('');
        vm.state = m.prop('');
        vm.paymentBalances = m.prop('');
        vm.payments = m.prop([]);
        vm.paymentAssetType = m.prop('');
        vm.paymentMonths = m.prop('');
        id = m.route.param('id');
        sbAdmin.api.getBot(id).then(function(botData) {
          vm.resourceId(botData.id);
          vm.name(botData.name);
          vm.address(botData.address);
          vm.paymentAddress(botData.paymentAddress);
          vm.paymentPlan(botData.paymentPlan);
          vm.state(botData.state);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        sbAdmin.api.getAllPlansData().then(function(apiResponse) {
          vm.allPlansData(apiResponse);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        sbAdmin.api.getBotEvents(id).then(function(apiResponse) {
          vm.botEvents(apiResponse);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        handleQuotebotUpdate = function(btcUSDValue) {
          vm.btcQuote(btcUSDValue);
          m.redraw(true);
        };
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_events_" + id, handleBotEventMessage));
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_account_updates_" + id, curryHandleAccountUpdatesMessage(id)));
        vm.quotebotSubscriberID = sbAdmin.quotebotSubscriber.addChangeListener(handleQuotebotUpdate);
        updateAllAccountPayments(id);
      };
      return vm;
    })();
    sbAdmin.ctrl.botPaymentsView.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      this.onunload = function(e) {
        var j, len, pusherClient, ref;
        ref = vm.pusherClients;
        for (j = 0, len = ref.length; j < len; j++) {
          pusherClient = ref[j];
          sbAdmin.pusherutils.closePusherChanel(pusherClient);
        }
        sbAdmin.quotebotSubscriber.removeChangeListener(vm.quotebotSubscriberID);
      };
      vm.init();
    };
    sbAdmin.ctrl.botPaymentsView.view = function() {
      var mEl;
      mEl = m("div", [
        m("h2", "SwapBot " + (vm.name())), m("div", {
          "class": "spacer1"
        }), m("div", {
          "class": "bot-payments-view"
        }, [
          sbAdmin.form.mAlerts(vm.errorMessages), m("h3", "Payment Status"), m("div", {
            "class": "row"
          }, [
            m("div", {
              "class": "col-md-9"
            }, [
              m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Next Payment Due", {
                    id: 'due-date'
                  }, sbAdmin.botPaymentUtils.buildFormattedBotDueDateText(vm.payments(), vm.paymentBalances()))
                ]), m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Payment Plan", {
                    id: 'rate'
                  }, sbAdmin.planutils.paymentPlanDesc(vm.paymentPlan(), vm.allPlansData()))
                ]), m("div", {
                  "class": "col-md-6"
                }, [
                  sbAdmin.form.mValueDisplay("Payment Address", {
                    id: 'paymentAddress'
                  }, vm.paymentAddress())
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-3"
                }, [botPaymentUtils.buildMakePaymentPulldown(vm.paymentAssetType, vm.allPlansData, vm.btcQuote)]), m("div", {
                  "class": "col-md-3"
                }, [botPaymentUtils.buildMonthsPaymentPulldown(vm.paymentMonths)]), m("div", {
                  "class": "col-md-6"
                }, [botPaymentUtils.buildPayHereDisplay(vm.paymentAssetType, vm.paymentMonths, vm.paymentAddress, vm.allPlansData, vm.btcQuote)])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [botPaymentUtils.buildReceivingPayment(vm.paymentAssetType, vm.paymentMonths, vm.paymentAddress, vm.botEvents, vm.allPlansData, vm.btcQuote)])
              ])
            ]), m("div", {
              "class": "col-md-3"
            }, [
              sbAdmin.form.mValueDisplay("Account Balances", {
                id: 'balances'
              }, sbAdmin.utils.buildBalancesMElement(vm.paymentBalances()))
            ])
          ]), m("div", {
            "class": "bot-payments"
          }, [
            m("small", {
              "class": "pull-right"
            }, "newest first"), m("h3", "Payment History"), vm.payments().length === 0 ? m("div", {
              "class": "no-payments"
            }, "No Payments Yet") : null, m("ul", {
              "class": "list-unstyled striped-list bot-list payment-list"
            }, [
              vm.payments().map(function(botPaymentObj) {
                var dateObj;
                dateObj = window.moment(botPaymentObj.createdAt);
                return m("li", {
                  "class": "bot-list-entry payment"
                }, [
                  m("div", {
                    "class": "labelWrapper"
                  }, buildPaymentTypeLabel(botPaymentObj.isCredit)), m("span", {
                    "class": "date",
                    title: dateObj.format('MMMM Do YYYY, h:mm:ss a')
                  }, dateObj.format('MMM D h:mm a')), m("span", {
                    "class": "amount"
                  }, sbAdmin.currencyutils.satoshisToValue(botPaymentObj.amount, botPaymentObj.asset)), m("span", {
                    "class": "msg"
                  }, botPaymentObj.msg)
                ]);
              })
            ])
          ]), m("div", {
            "class": "spacer2"
          }), m("a[href='/admin/view/bot/" + (vm.resourceId()) + "']", {
            "class": "btn btn-default",
            config: m.route
          }, "Return to Bot View")
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
    return sbAdmin.ctrl.botPaymentsView.UnloadEvent;
  })();

  (function() {
    var vm;
    sbAdmin.ctrl.botShutdownForm = {};
    vm = sbAdmin.ctrl.botShutdownForm.vm = (function() {
      vm = {};
      vm.init = function() {
        var id;
        vm.errorMessages = m.prop([]);
        vm.formStatus = m.prop('active');
        vm.resourceId = m.prop('');
        vm.allPlansData = m.prop(null);
        vm.name = m.prop('');
        vm.description = m.prop('');
        vm.hash = m.prop('');
        vm.shutdownAddress = m.prop('');
        id = m.route.param('id');
        sbAdmin.api.getBot(id).then(function(botData) {
          vm.resourceId(botData.id);
          vm.name(botData.name);
          vm.description(botData.description);
          vm.hash(botData.hash);
          vm.shutdownAddress(botData.shutdownAddress != null ? botData.shutdownAddress : '');
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        vm.doShutdown = function(e) {
          var apiArgs, attributes;
          e.preventDefault();
          attributes = {
            shutdownAddress: vm.shutdownAddress()
          };
          apiArgs = [vm.resourceId(), attributes];
          return sbAdmin.form.submit(sbAdmin.api.shutdownBot, apiArgs, vm.errorMessages, vm.formStatus).then(function(apiResponse) {
            var botId;
            botId = vm.resourceId();
            m.route("/admin/view/bot/" + botId);
          });
        };
      };
      return vm;
    })();
    sbAdmin.ctrl.botShutdownForm.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.botShutdownForm.view = function() {
      var mEl;
      mEl = m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("div", {
              "class": "row"
            }, [
              m("div", {
                "class": "col-md-10"
              }, [m("h2", vm.resourceId() ? "Shutdown SwapBot " + (vm.name()) : "")]), m("div", {
                "class": "col-md-2 text-right"
              }, [sbAdmin.robohashUtils.img(vm.hash(), 'mediumRoboHead')])
            ]), m("div", {
              "class": "spacer1"
            }), sbAdmin.form.mForm({
              errors: vm.errorMessages,
              status: vm.formStatus
            }, {
              onsubmit: vm.doShutdown
            }, [
              sbAdmin.form.mAlerts(vm.errorMessages), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  m("div", {
                    "class": "spacer2"
                  }), m("div", {
                    "class": "panel panel-danger"
                  }, [
                    m("div", {
                      "class": 'panel-heading'
                    }, [
                      m("h4", {
                        "class": 'panel-title'
                      }, "Are you sure you want to shutdown this bot?")
                    ]), m("div", {
                      "class": 'panel-body'
                    }, "If you shutdown this bot it will be permanently deactivated and not complete any more new swaps.  Any new swaps will be refunded automatically.  After 6 confirmations, all of the remaining funds will be forwarded to the address entered below.")
                  ])
                ])
              ]), m("div", {
                "class": "spacer1"
              }), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-6"
                }, [
                  sbAdmin.form.mFormField("Refund Address to send all Reminaing Tokens and BTC", {
                    id: 'shutdownAddress',
                    'placeholder': "1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
                    required: true
                  }, vm.shutdownAddress)
                ])
              ]), m("div", {
                "class": "spacer1"
              }), m("a[href='/admin/dashboard']", {
                "class": "btn btn-default pull-right",
                config: m.route
              }, "Return without Saving"), sbAdmin.form.mSubmitBtn("Permanently Shutdown Bot", 'btn btn-danger')
            ])
          ])
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var botPublicAddress, buildBlacklistAddressesGroup, buildIncomeRulesGroup, buildMLevel, curryHandleAccountUpdatesMessage, handleBotBalancesMessage, handleBotEventMessage, serializeSwaps, sharedSwapTypeFormField, swapGroup, swapGroupRenderers, updateBotAccountBalance, vm;
    sbAdmin.ctrl.botView = {};
    swapGroupRenderers = {};
    sharedSwapTypeFormField = function(number, swap) {
      return sbAdmin.form.mValueDisplay("Swap Type", {
        id: "swap_strategy_" + number
      }, sbAdmin.swaputils.strategyLabelByValue(swap.strategy()));
    };
    swapGroupRenderers.rate = function(number, swap) {
      return m("div", {
        "class": "asset-group"
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-3"
          }, [sharedSwapTypeFormField(number, swap)]), m("div", {
            "class": "col-md-3"
          }, [
            sbAdmin.form.mValueDisplay("Receives Asset", {
              id: "swap_in_" + number
            }, swap["in"]())
          ]), m("div", {
            "class": "col-md-3"
          }, [
            sbAdmin.form.mValueDisplay("Sends Asset", {
              id: "swap_out_" + number
            }, swap.out())
          ]), m("div", {
            "class": "col-md-3"
          }, [
            sbAdmin.form.mValueDisplay("Rate", {
              type: "number",
              step: "any",
              min: "0",
              id: "swap_rate_" + number
            }, swap.rate())
          ])
        ])
      ]);
    };
    swapGroupRenderers.fixed = function(number, swap) {
      return m("div", {
        "class": "asset-group"
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-3"
          }, [sharedSwapTypeFormField(number, swap)]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Receives Asset", {
              id: "swap_in_" + number
            }, swap["in"]())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Receives Quantity", {
              id: "swap_in_qty_" + number
            }, swap.in_qty())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Sends Asset", {
              id: "swap_out_" + number
            }, swap.out())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Sends Quantity", {
              id: "swap_out_qty_" + number
            }, swap.out_qty())
          ])
        ])
      ]);
    };
    swapGroupRenderers.fiat = function(number, swap) {
      return m("div", {
        "class": "asset-group"
      }, [
        m("h4", "Swap #" + number), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-3"
          }, [sharedSwapTypeFormField(number, swap)]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Receives", {
              id: "swap_in_" + number
            }, swap["in"]())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Sends Asset", {
              id: "swap_out_" + number
            }, swap.out())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("At USD Price", {
              id: "swap_cost_" + number
            }, '$' + swap.cost())
          ]), m("div", {
            "class": "col-md-1"
          }, [
            sbAdmin.form.mValueDisplay("Minimum", {
              id: "swap_min_out_" + number
            }, swap.min_out())
          ]), m("div", {
            "class": "col-md-2"
          }, [
            sbAdmin.form.mValueDisplay("Divisible", {
              id: "swap_divisible_" + number
            }, swap.divisible() === '1' ? 'YES' : 'NO')
          ])
        ])
      ]);
    };
    swapGroup = function(number, swapProp) {
      return swapGroupRenderers[swapProp().strategy()](number, swapProp());
    };
    serializeSwaps = function(swap) {
      var out;
      out = [];
      out.push(swap);
      return out;
    };
    buildIncomeRulesGroup = function() {
      return sbAdmin.formGroup.newGroup({
        id: 'incomerules',
        fields: [
          {
            name: 'asset'
          }, {
            name: 'minThreshold'
          }, {
            name: 'paymentAmount'
          }, {
            name: 'address'
          }
        ],
        buildItemRow: function(builder, number, item) {
          return [builder.header("Income Forwarding Rule #" + number), builder.row([builder.value("Asset Received", 'asset', {}, 3), builder.value("Trigger Threshold", 'minThreshold', {}), builder.value("Payment Amount", 'paymentAmount', {}), builder.value("Payment Address", 'address', {}, 4)])];
        },
        displayOnly: true
      });
    };
    buildBlacklistAddressesGroup = function() {
      return sbAdmin.formGroup.newGroup({
        id: 'blacklist',
        fields: [
          {
            name: 'address'
          }
        ],
        buildAllItemRows: function(items) {
          var addressList, item, j, len, offset;
          addressList = "";
          for (offset = j = 0, len = items.length; j < len; offset = ++j) {
            item = items[offset];
            addressList += (offset > 0 ? ", " : "") + item.address();
          }
          return m("div", {
            "class": "item-group"
          }, [
            m("div", {
              "class": "row"
            }, m("div", {
              "class": "col-md-12 form-control-static"
            }, addressList))
          ]);
        },
        translateFieldToNumberedValues: 'address',
        useCompactNumberedLayout: true,
        displayOnly: true
      });
    };
    botPublicAddress = function(vm) {
      return swapbot.addressUtils.publicBotAddress(vm.username(), vm.resourceId(), window.location);
    };
    handleBotEventMessage = function(data) {
      var ref;
      if ((data != null ? (ref = data.event) != null ? ref.msg : void 0 : void 0) || (data != null ? data.message : void 0)) {
        vm.botEvents().unshift(data);
        m.redraw(true);
      }
    };
    handleBotBalancesMessage = function(data) {
      if (data != null) {
        vm.updateBalances(data);
        m.redraw(true);
      }
    };
    curryHandleAccountUpdatesMessage = function(id) {
      return function(data) {
        updateBotAccountBalance(id);
      };
    };
    updateBotAccountBalance = function(id) {
      sbAdmin.api.getBotPaymentBalances(id).then(function(apiResponse) {
        var asset, paymentBalances, ref, val;
        paymentBalances = [];
        ref = apiResponse.balances;
        for (asset in ref) {
          val = ref[asset];
          paymentBalances.push({
            asset: asset,
            val: val
          });
        }
        vm.paymentBalances(paymentBalances);
        m.redraw(true);
      }, function(errorResponse) {
        vm.errorMessages(errorResponse.errors);
      });
      sbAdmin.api.getAllBotPayments(id).then(function(apiResponse) {
        apiResponse.reverse();
        vm.payments(apiResponse);
        vm.paymentsSet(true);
        m.redraw(true);
      }, function(errorResponse) {
        vm.errorMessages(errorResponse.errors);
      });
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
    vm = sbAdmin.ctrl.botView.vm = (function() {
      var buildBalancesPropValue, buildSwapsPropValue;
      buildSwapsPropValue = function(swaps) {
        var j, len, out, swap;
        out = [];
        for (j = 0, len = swaps.length; j < len; j++) {
          swap = swaps[j];
          out.push(sbAdmin.swaputils.newSwapProp(swap));
        }
        return out;
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
        return out;
      };
      vm = {};
      vm.updateBalances = function(newBalances) {
        vm.balances(buildBalancesPropValue(newBalances));
      };
      vm.toggleDebugView = function(e) {
        e.preventDefault();
        vm.showDebug = !vm.showDebug;
      };
      vm.init = function() {
        var handleQuotebotUpdate, id;
        vm.pusherClients = [];
        vm.quotebotSubscriberID = null;
        vm.btcQuote = m.prop(null);
        vm.showDebug = false;
        vm.errorMessages = m.prop([]);
        vm.formStatus = m.prop('active');
        vm.resourceId = m.prop('new');
        vm.botEvents = m.prop([]);
        vm.allPlansData = m.prop(null);
        vm.name = m.prop('');
        vm.description = m.prop('');
        vm.hash = m.prop('');
        vm.username = m.prop('');
        vm.address = m.prop('');
        vm.paymentAddress = m.prop('');
        vm.paymentPlan = m.prop('');
        vm.state = m.prop('');
        vm.swaps = m.prop(buildSwapsPropValue([]));
        vm.balances = m.prop(buildBalancesPropValue([]));
        vm.confirmationsRequired = m.prop('');
        vm.returnFee = m.prop('');
        vm.refundAfterBlocks = m.prop('');
        vm.paymentBalances = m.prop('');
        vm.payments = m.prop([]);
        vm.paymentsSet = m.prop(false);
        vm.incomeRulesGroup = buildIncomeRulesGroup();
        vm.blacklistAddressesGroup = buildBlacklistAddressesGroup();
        vm.backgroundImageDetails = m.prop('');
        vm.logoImageDetails = m.prop('');
        vm.backgroundOverlaySettings = m.prop('');
        vm.paymentAssetType = m.prop('');
        vm.paymentMonths = m.prop('');
        id = m.route.param('id');
        sbAdmin.api.getBot(id).then(function(botData) {
          var ref;
          vm.resourceId(botData.id);
          vm.name(botData.name);
          vm.address(botData.address);
          vm.paymentAddress(botData.paymentAddress);
          vm.paymentPlan(botData.paymentPlan);
          vm.state(botData.state);
          vm.description(botData.descriptionHtml);
          vm.hash(botData.hash);
          vm.username(botData.username);
          vm.swaps(buildSwapsPropValue(botData.swaps));
          vm.balances(buildBalancesPropValue(botData.balances));
          vm.confirmationsRequired(botData.confirmationsRequired);
          vm.returnFee(botData.returnFee);
          vm.incomeRulesGroup.unserialize(botData.incomeRules);
          vm.blacklistAddressesGroup.unserialize(botData.blacklistAddresses);
          vm.backgroundImageDetails(botData.backgroundImageDetails);
          vm.logoImageDetails(botData.logoImageDetails);
          vm.backgroundOverlaySettings(botData.backgroundOverlaySettings);
          vm.refundAfterBlocks((ref = botData.refundConfig) != null ? ref.refundAfterBlocks : void 0);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        sbAdmin.api.getBotEvents(id).then(function(apiResponse) {
          vm.botEvents(apiResponse);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        sbAdmin.api.getAllPlansData().then(function(apiResponse) {
          vm.allPlansData(apiResponse);
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
        updateBotAccountBalance(id);
        handleQuotebotUpdate = function(btcUSDValue) {
          vm.btcQuote(btcUSDValue);
          m.redraw(true);
        };
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_events_" + id, handleBotEventMessage));
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_balances_" + id, handleBotBalancesMessage));
        vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_account_updates_" + id, curryHandleAccountUpdatesMessage(id)));
        vm.quotebotSubscriberID = sbAdmin.quotebotSubscriber.addChangeListener(handleQuotebotUpdate);
      };
      return vm;
    })();
    sbAdmin.ctrl.botView.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      this.onunload = function(e) {
        var j, len, pusherClient, ref;
        ref = vm.pusherClients;
        for (j = 0, len = ref.length; j < len; j++) {
          pusherClient = ref[j];
          sbAdmin.pusherutils.closePusherChanel(pusherClient);
        }
        sbAdmin.quotebotSubscriber.removeChangeListener(vm.quotebotSubscriberID);
      };
      vm.init();
    };
    sbAdmin.ctrl.botView.view = function() {
      var botPaymentUtils, mEl;
      botPaymentUtils = sbAdmin.botPaymentUtils;
      mEl = m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-10"
          }, [m("h2", "SwapBot " + (vm.name()))]), m("div", {
            "class": "col-md-2 text-right"
          }, [sbAdmin.robohashUtils.img(vm.hash(), 'mediumRoboHead')])
        ]), m("div", {
          "class": "spacer1"
        }), m("div", {
          "class": "bot-status"
        }, [sbAdmin.stateutils.buildStateDisplay(sbAdmin.stateutils.buildStateDetails(vm.state(), sbAdmin.planutils.planData(vm.paymentPlan(), vm.allPlansData()), vm.paymentAddress(), vm.address()))]), m("div", {
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
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Bot Name", {
                    id: 'name'
                  }, vm.name())
                ]), m("div", {
                  "class": "col-md-6"
                }, [
                  sbAdmin.form.mValueDisplay("Bot Address", {
                    id: 'address'
                  }, vm.address() ? vm.address() : m("span", {
                    "class": 'no'
                  }, "[ none ]"))
                ]), m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Status", {
                    id: 'status'
                  }, sbAdmin.stateutils.buildStateSpan(vm.state()))
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Return Fee", {
                    id: 'return_fee'
                  }, vm.returnFee() + ' BTC')
                ]), m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mValueDisplay("Confirmations", {
                    id: 'confirmations_required'
                  }, vm.confirmationsRequired())
                ]), m("div", {
                  "class": "col-md-6"
                }, [
                  sbAdmin.form.mValueDisplay("Refund Out of Stock Swaps After", {
                    id: 'refund_after_blocks'
                  }, vm.refundAfterBlocks() + " blocks")
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  sbAdmin.form.mValueDisplay("Bot Description", {
                    id: 'description'
                  }, m.trust(vm.description()))
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-7"
                }, [
                  sbAdmin.fileHelper.mImageDisplay("Custom Background Image", {
                    id: 'BGImage'
                  }, vm.backgroundImageDetails, 'medium')
                ]), m("div", {
                  "class": "col-md-5"
                }, [
                  sbAdmin.fileHelper.mImageDisplay("Custom Logo Image", {
                    id: 'LogoImage'
                  }, vm.logoImageDetails, 'thumb')
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-7"
                }, [
                  sbAdmin.form.mValueDisplay("Background Overlay", {
                    id: 'BackgroundOverlay'
                  }, sbAdmin.botutils.overlayDesc(vm.backgroundOverlaySettings()))
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  sbAdmin.form.mValueDisplay("Public Bot Address", {
                    id: 'description'
                  }, [
                    m("a", {
                      href: botPublicAddress(vm)
                    }, botPublicAddress(vm))
                  ])
                ])
              ])
            ]), m("div", {
              "class": "col-md-4"
            }, [
              sbAdmin.form.mValueDisplay("Bot Balances", {
                id: 'balances'
              }, sbAdmin.utils.buildBalancesMElement(vm.balances()))
            ])
          ]), m("hr"), vm.swaps().map(function(swap, offset) {
            return swapGroup(offset + 1, swap);
          }), m("hr"), m("h4", "Blacklisted Addresses"), vm.blacklistAddressesGroup.buildValues(), m("div", {
            "class": "spacer1"
          }), m("hr"), vm.incomeRulesGroup.buildValues(), m("hr"), m("div", {
            "class": "bot-payments"
          }, [
            m("h3", "Payment"), m("div", {
              "class": "row"
            }, [
              m("div", {
                "class": "col-md-9"
              }, [
                m("div", {
                  "class": "row"
                }, [
                  m("div", {
                    "class": "col-md-3"
                  }, [
                    sbAdmin.form.mValueDisplay("Next Payment Due", {
                      id: 'due-date'
                    }, vm.paymentsSet() ? botPaymentUtils.buildFormattedBotDueDateText(vm.payments(), vm.paymentBalances()) : 'loading...')
                  ]), m("div", {
                    "class": "col-md-3"
                  }, [
                    sbAdmin.form.mValueDisplay("Payment Plan", {
                      id: 'rate'
                    }, sbAdmin.planutils.paymentPlanDesc(vm.paymentPlan(), vm.allPlansData()))
                  ]), m("div", {
                    "class": "col-md-6"
                  }, [
                    sbAdmin.form.mValueDisplay("Payment Address", {
                      id: 'paymentAddress'
                    }, vm.paymentAddress())
                  ])
                ]), m("div", {
                  "class": "row"
                }, [
                  m("div", {
                    "class": "col-md-3"
                  }, [botPaymentUtils.buildMakePaymentPulldown(vm.paymentAssetType, vm.allPlansData, vm.btcQuote)]), m("div", {
                    "class": "col-md-3"
                  }, [botPaymentUtils.buildMonthsPaymentPulldown(vm.paymentMonths)]), m("div", {
                    "class": "col-md-6"
                  }, [botPaymentUtils.buildPayHereDisplay(vm.paymentAssetType, vm.paymentMonths, vm.paymentAddress, vm.allPlansData, vm.btcQuote)])
                ]), m("div", {
                  "class": "row"
                }, [
                  m("div", {
                    "class": "col-md-12"
                  }, [botPaymentUtils.buildReceivingPayment(vm.paymentAssetType, vm.paymentMonths, vm.paymentAddress, vm.botEvents, vm.allPlansData, vm.btcQuote)])
                ])
              ]), m("div", {
                "class": "col-md-3"
              }, [
                sbAdmin.form.mValueDisplay("Payment Account Balances", {
                  id: 'balances'
                }, sbAdmin.utils.buildBalancesMElement(vm.paymentBalances()))
              ])
            ])
          ]), m("div", {
            "class": "spacer1"
          }), m("a[href='/admin/payments/bot/" + (vm.resourceId()) + "']", {
            "class": "btn btn-info",
            config: m.route
          }, "View Payment History"), m("div", {
            "class": "spacer1"
          }), m("hr"), m("div", {
            "class": "bot-events"
          }, [
            m("div", {
              "class": "pulse-spinner pull-right"
            }, [
              m("div", {
                "class": "rect1"
              }), m("div", {
                "class": "rect2"
              }), m("div", {
                "class": "rect3"
              }), m("div", {
                "class": "rect4"
              }), m("div", {
                "class": "rect5"
              })
            ]), m("h3", "Events"), vm.botEvents().length === 0 ? m("div", {
              "class": "no-events"
            }, "No Events Yet") : null, m("ul", {
              "class": "list-unstyled striped-list bot-list event-list" + (vm.showDebug ? ' event-list-debug' : '')
            }, [
              vm.botEvents().map(function(botEventObj) {
                var dateObj, ref;
                if (!vm.showDebug && botEventObj.level <= 100) {
                  return;
                }
                dateObj = window.moment(botEventObj.createdAt);
                return m("li", {
                  "class": "bot-list-entry event"
                }, [
                  m("div", {
                    "class": "labelWrapper"
                  }, buildMLevel(botEventObj.level)), m("span", {
                    "class": "date",
                    title: dateObj.format('MMMM Do YYYY, h:mm:ss a')
                  }, dateObj.format('MMM D h:mm a')), m("span", {
                    "class": "msg"
                  }, botEventObj.message || ((ref = botEventObj.event) != null ? ref.msg : void 0))
                ]);
              })
            ]), m("div", {
              "class": "pull-right"
            }, [
              m("a[href='#show-debug']", {
                onclick: vm.toggleDebugView,
                "class": "btn " + (vm.showDebug ? 'btn-warning' : 'btn-default') + " btn-xs",
                style: {
                  "margin-right": "16px"
                }
              }, [vm.showDebug ? "Hide Debug" : "Show Debug"])
            ])
          ]), m("div", {
            "class": "spacer1"
          }), m("hr"), m("div", {
            "class": "spacer2"
          }), m("a[href='/admin/dashboard']", {
            "class": "btn btn-default pull-right",
            config: m.route
          }, "Back to Dashboard"), (vm.username() === sbAdmin.auth.getUser().username ? m("a[href='/admin/edit/bot/" + (vm.resourceId()) + "']", {
            "class": "btn btn-success",
            config: m.route
          }, "Edit This Bot") : null)
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
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
      var botsListEl, mEl;
      if (vm.bots().length) {
        botsListEl = [
          m("p", {
            "class": ""
          }, "Here is a list of your Swapbots:"), m("div", {
            "class": "row"
          }, [
            m("div", {
              "class": "col-md-10 col-lg-8"
            }, [
              m("ul", {
                "class": "list-unstyled striped-list bot-list"
              }, [
                vm.bots().map(function(bot) {
                  return m("li", {}, [
                    m("div", {}, [
                      bot.hash.length ? m("a[href='/admin/view/bot/" + bot.id + "']", {
                        config: m.route
                      }, sbAdmin.robohashUtils.img(bot.hash, 'tinyRoboHead')) : m('div', {
                        "class": 'emptyRoboHead'
                      }, ''), m("a[href='/admin/view/bot/" + bot.id + "']", {
                        "class": "",
                        config: m.route
                      }, "" + bot.name), " ", m("a[href='/admin/edit/bot/" + bot.id + "']", {
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
          ])
        ];
      } else {
        botsListEl = [
          m("p", {
            "class": ""
          }, ["You don't have any swapbots yet.  After you create some swapbots, they will be listed here."]), m("p", {
            "class": ""
          }, [
            "For some help, check out the ", m("a[href='https://www.youtube.com/watch?v=MCdFHx3yTfE']", {
              "class": ""
            }, "Swapbot Admin Tutorial"), "."
          ]), m("div", {
            "class": "spacer1"
          })
        ];
      }
      mEl = m("div", [
        m("h2", "Welcome, " + (vm.user().name)), m("div", {
          "class": "spacer1"
        }), botsListEl, m("div", {
          "class": "spacer1"
        }), m("a[href='/admin/edit/bot/new']", {
          "class": "btn btn-primary",
          config: m.route
        }, "Create a new Swapbot")
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
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
            return m.route('/admin/dashboard');
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
      var mEl;
      mEl = m("div", [
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
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    sbAdmin.ctrl.logout = {};
    sbAdmin.ctrl.logout.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      sbAdmin.auth.logout();
    };
    return sbAdmin.ctrl.logout.view = function() {
      var mEl;
      mEl = m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("h2", "Logged Out"), m("p", "The API credentials have been cleared from your browser."), m("div", {
              "class": "spacer1"
            }), m("a[href='/admin/login']", {
              config: m.route
            }, "Return to Login")
          ])
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var vm;
    sbAdmin.ctrl.settingsForm = {};
    vm = sbAdmin.ctrl.settingsForm.vm = (function() {
      vm = {};
      vm.init = function() {
        var id;
        vm.errorMessages = m.prop([]);
        vm.formStatus = m.prop('active');
        vm.resourceId = m.prop('');
        vm.name = m.prop('');
        vm.value = m.prop('');
        id = m.route.param('id');
        if (id !== 'new') {
          sbAdmin.api.getSettings(id).then(function(settingsData) {
            var v;
            vm.resourceId(settingsData.id);
            vm.name(settingsData.name);
            v = settingsData.value;
            console.log("typeof v=", typeof v);
            if ((v != null) && typeof v === 'object') {
              console.log("stringify");
              v = window.JSON.stringify(v, null, 2);
            }
            console.log("v=", v);
            vm.value(v);
          }, function(errorResponse) {
            vm.errorMessages(errorResponse.errors);
          });
        }
        vm.save = function(e) {
          var apiArgs, apiCall, attributes;
          e.preventDefault();
          attributes = {
            name: vm.name(),
            value: vm.value()
          };
          if (vm.resourceId().length > 0) {
            apiCall = sbAdmin.api.updateSettings;
            apiArgs = [vm.resourceId(), attributes];
          } else {
            apiCall = sbAdmin.api.newSettings;
            apiArgs = [attributes];
          }
          return sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(function() {
            m.route('/admin/settings');
          });
        };
      };
      return vm;
    })();
    sbAdmin.ctrl.settingsForm.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.settingsForm.view = function() {
      var mEl;
      mEl = m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("h2", vm.resourceId() ? "Edit Setting " + (vm.name()) : "Create New Settings"), m("div", {
              "class": "spacer1"
            }), sbAdmin.form.mForm({
              errors: vm.errorMessages,
              status: vm.formStatus
            }, {
              onsubmit: vm.save
            }, [
              sbAdmin.form.mAlerts(vm.errorMessages), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  sbAdmin.form.mFormField("Name", {
                    id: 'name',
                    'placeholder': "Name",
                    required: true
                  }, vm.name)
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-12"
                }, [
                  sbAdmin.form.mFormField("Value", {
                    type: 'textarea',
                    id: 'value',
                    'placeholder': "{}",
                    style: {
                      height: '300px'
                    },
                    required: true
                  }, vm.value)
                ])
              ]), m("div", {
                "class": "spacer1"
              }), sbAdmin.form.mSubmitBtn("Save Settings"), m("a[href='/admin/settings']", {
                "class": "btn btn-default pull-right",
                config: m.route
              }, "Return without Saving")
            ])
          ])
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var vm;
    sbAdmin.ctrl.settingsView = {};
    vm = sbAdmin.ctrl.settingsView.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.settings = m.prop([]);
        sbAdmin.api.getAllSettings().then(function(settingsList) {
          vm.settings(settingsList);
        });
      };
      return vm;
    })();
    sbAdmin.ctrl.settingsView.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.settingsView.view = function() {
      var mEl;
      mEl = m("div", [
        m("h2", "Global Swapbot Settings"), m("div", {
          "class": "spacer1"
        }), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-6 col-lg-4"
          }, [
            m("ul", {
              "class": "list-unstyled striped-list setting-list"
            }, [
              vm.settings().map(function(setting) {
                return m("li", {}, [
                  m("div", {}, [
                    m("a[href='/admin/edit/setting/" + setting.id + "']", {
                      "class": "",
                      config: m.route
                    }, "" + setting.name), " ", m("a[href='/admin/edit/setting/" + setting.id + "']", {
                      "class": "settingsView-edit-link pull-right",
                      config: m.route
                    }, [
                      m("span", {
                        "class": "glyphicon glyphicon-edit",
                        title: "Edit Setting " + setting.name
                      }, ''), " Edit"
                    ])
                  ])
                ]);
              }), vm.settings().length === 0 ? m("li", {}, [m("div", {}, ["No settings found"])]) : void 0
            ])
          ])
        ]), m("div", {
          "class": "spacer1"
        }), m("a[href='/admin/edit/setting/new']", {
          "class": "btn btn-primary",
          config: m.route
        }, "Create a new setting")
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var appendBotEventMessage, buildMLevel, handleBotEventMessage, vm;
    sbAdmin.ctrl.swapEvents = {};
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
    handleBotEventMessage = function(data) {
      console.log("handleBotEventMessage data=", data);
      if (appendBotEventMessage(data)) {
        m.redraw(true);
      }
    };
    appendBotEventMessage = function(data, reverse) {
      var anyAppended, ref;
      if (reverse == null) {
        reverse = true;
      }
      anyAppended = false;
      if ((data != null ? (ref = data.event) != null ? ref.msg : void 0 : void 0) || (data != null ? data.message : void 0)) {
        if (data.swapUuid === vm.swapId) {
          if (reverse) {
            vm.swapEvents().unshift(data);
          } else {
            vm.swapEvents().push(data);
          }
          anyAppended = true;
        }
      }
      return anyAppended;
    };
    vm = sbAdmin.ctrl.swapEvents.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.showDebug = false;
        vm.pusherClients = [];
        vm.errorMessages = m.prop([]);
        vm.user = m.prop(sbAdmin.auth.getUser());
        vm.swapId = m.route.param('id');
        vm.swap = m.prop(null);
        vm.swapEvents = m.prop([]);
        sbAdmin.api.getSwap(vm.swapId).then(function(swapData) {
          var bot_id, swap;
          swap = swapData;
          vm.swap(swap);
          bot_id = swap.botUuid;
          sbAdmin.api.getBotEvents(bot_id).then(function(apiResponse) {
            var data, j, len;
            for (j = 0, len = apiResponse.length; j < len; j++) {
              data = apiResponse[j];
              appendBotEventMessage(data, false);
            }
          }, function(errorResponse) {
            vm.errorMessages(errorResponse.errors);
          });
          vm.pusherClients.push(sbAdmin.pusherutils.subscribeToPusherChanel("swapbot_events_" + bot_id, handleBotEventMessage));
        }, function(errorResponse) {
          vm.errorMessages(errorResponse.errors);
        });
      };
      vm.toggleDebugView = function(e) {
        e.preventDefault();
        vm.showDebug = !vm.showDebug;
      };
      return vm;
    })();
    sbAdmin.ctrl.swapEvents.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      this.onunload = function(e) {
        var j, len, pusherClient, ref, results;
        ref = vm.pusherClients;
        results = [];
        for (j = 0, len = ref.length; j < len; j++) {
          pusherClient = ref[j];
          results.push(sbAdmin.pusherutils.closePusherChanel(pusherClient));
        }
        return results;
      };
      vm.init();
    };
    return sbAdmin.ctrl.swapEvents.view = function() {
      var botAaddress, mEl, swap;
      swap = vm.swap();
      if (!swap) {
        mEl = m("div", [m("h2", "Swap not found"), sbAdmin.form.mAlerts(vm.errorMessages)]);
      } else {
        botAaddress = swapbot.addressUtils.publicBotAddress(swap.botUsername, swap.botUuid, window.location);
        mEl = m("div", [
          m("h4", "Swap Events for swap " + swap.id), m("p", {}, [
            "This swap belongs to the bot ", m("a[href='" + botAaddress + "']", {
              target: "_blank",
              "class": ""
            }, swap.botName)
          ]), m("div", {
            "class": "spacer1"
          }), sbAdmin.form.mAlerts(vm.errorMessages), m("div", {
            "class": "bot-events"
          }, [
            m("div", {
              "class": "pulse-spinner pull-right"
            }, [
              m("div", {
                "class": "rect1"
              }), m("div", {
                "class": "rect2"
              }), m("div", {
                "class": "rect3"
              }), m("div", {
                "class": "rect4"
              }), m("div", {
                "class": "rect5"
              })
            ]), m("h3", "Events"), vm.swapEvents().length === 0 ? m("div", {
              "class": "no-events"
            }, "No Events Yet") : null, m("ul", {
              "class": "list-unstyled striped-list bot-list event-list"
            }, [
              vm.swapEvents().map(function(botEventObj) {
                var dateObj, ref;
                if (!vm.showDebug && botEventObj.level <= 100) {
                  return;
                }
                dateObj = window.moment(botEventObj.createdAt);
                return m("li", {
                  "class": "bot-list-entry event"
                }, [
                  m("div", {
                    "class": "labelWrapper"
                  }, buildMLevel(botEventObj.level)), m("span", {
                    "class": "date",
                    title: dateObj.format('MMMM Do YYYY, h:mm:ss a')
                  }, dateObj.format('MMM D h:mm a')), m("span", {
                    "class": "msg"
                  }, botEventObj.message || ((ref = botEventObj.event) != null ? ref.msg : void 0))
                ]);
              })
            ]), m("div", {
              "class": "pull-right"
            }, [
              m("a[href='#show-debug']", {
                onclick: vm.toggleDebugView,
                "class": "btn " + (vm.showDebug ? 'btn-warning' : 'btn-default') + " btn-xs",
                style: {
                  "margin-right": "16px"
                }
              }, [vm.showDebug ? "Hide Debug" : "Show Debug"])
            ])
          ])
        ]);
      }
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var formatPrivileges, vm;
    sbAdmin.ctrl.userForm = {};
    formatPrivileges = function(privileges) {
      var out, privilege, set;
      out = (function() {
        var results;
        results = [];
        for (privilege in privileges) {
          set = privileges[privilege];
          results.push(privilege);
        }
        return results;
      })();
      if (out.length) {
        return out.join(", ");
      }
      return "No Privileges";
    };
    vm = sbAdmin.ctrl.userForm.vm = (function() {
      vm = {};
      vm.init = function() {
        var id;
        vm.errorMessages = m.prop([]);
        vm.formStatus = m.prop('active');
        vm.resourceId = m.prop('');
        vm.name = m.prop('');
        vm.username = m.prop('');
        vm.email = m.prop('');
        vm.apitoken = m.prop('');
        vm.apisecretkey = m.prop('');
        vm.privileges = m.prop('');
        id = m.route.param('id');
        if (id !== 'new') {
          sbAdmin.api.getUser(id).then(function(userData) {
            vm.resourceId(userData.id);
            vm.name(userData.name);
            vm.username(userData.username);
            vm.email(userData.email);
            vm.apitoken(userData.apitoken);
            vm.apisecretkey(userData.apisecretkey);
            vm.privileges(userData.privileges);
          }, function(errorResponse) {
            vm.errorMessages(errorResponse.errors);
          });
        }
        vm.save = function(e) {
          var apiArgs, apiCall, attributes;
          e.preventDefault();
          attributes = {
            name: vm.name(),
            username: vm.username(),
            email: vm.email()
          };
          if (vm.resourceId().length > 0) {
            apiCall = sbAdmin.api.updateUser;
            apiArgs = [vm.resourceId(), attributes];
          } else {
            apiCall = sbAdmin.api.newUser;
            apiArgs = [attributes];
          }
          return sbAdmin.form.submit(apiCall, apiArgs, vm.errorMessages, vm.formStatus).then(function() {
            m.route('/admin/users');
          });
        };
      };
      return vm;
    })();
    sbAdmin.ctrl.userForm.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.userForm.view = function() {
      var mEl;
      mEl = m("div", [
        m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-12"
          }, [
            m("h2", vm.resourceId() ? "Edit User " + (vm.name()) : "Create a New User"), m("div", {
              "class": "spacer1"
            }), sbAdmin.form.mForm({
              errors: vm.errorMessages,
              status: vm.formStatus
            }, {
              onsubmit: vm.save
            }, [
              sbAdmin.form.mAlerts(vm.errorMessages), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.form.mFormField("Public Name", {
                    id: 'name',
                    'placeholder': "Name",
                    required: true
                  }, vm.name)
                ]), m("div", {
                  "class": "col-md-3"
                }, [
                  sbAdmin.form.mFormField("Username", {
                    id: 'username',
                    'placeholder': "Username",
                    required: true
                  }, vm.username)
                ]), m("div", {
                  "class": "col-md-5"
                }, [
                  sbAdmin.form.mFormField("Email", {
                    type: 'email',
                    id: 'email',
                    'placeholder': "User Email",
                    required: true
                  }, vm.email)
                ])
              ]), m("hr"), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-4"
                }, [
                  sbAdmin.form.mValueDisplay("API Token", {
                    id: "apitoken"
                  }, vm.apitoken())
                ]), m("div", {
                  "class": "col-md-8"
                }, [
                  sbAdmin.form.mValueDisplay("API Secret Key", {
                    id: "apisecretkey"
                  }, vm.apisecretkey())
                ])
              ]), m("div", {
                "class": "row"
              }, [
                m("div", {
                  "class": "col-md-6"
                }, [
                  sbAdmin.form.mValueDisplay("privileges", {
                    id: "apitoken"
                  }, formatPrivileges(vm.privileges()))
                ])
              ]), m("div", {
                "class": "spacer1"
              }), sbAdmin.form.mSubmitBtn("Save User"), m("a[href='/admin/users']", {
                "class": "btn btn-default pull-right",
                config: m.route
              }, "Return without Saving")
            ])
          ])
        ])
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  (function() {
    var vm;
    sbAdmin.ctrl.usersView = {};
    vm = sbAdmin.ctrl.usersView.vm = (function() {
      vm = {};
      vm.init = function() {
        vm.users = m.prop([]);
        sbAdmin.api.getAllUsers().then(function(usersList) {
          vm.users(usersList);
        });
      };
      return vm;
    })();
    sbAdmin.ctrl.usersView.controller = function() {
      sbAdmin.auth.redirectIfNotLoggedIn();
      vm.init();
    };
    return sbAdmin.ctrl.usersView.view = function() {
      var mEl;
      mEl = m("div", [
        m("h2", "API Users"), m("div", {
          "class": "spacer1"
        }), m("div", {
          "class": "row"
        }, [
          m("div", {
            "class": "col-md-6 col-lg-4"
          }, [
            m("ul", {
              "class": "list-unstyled striped-list user-list"
            }, [
              vm.users().map(function(user) {
                return m("li", {}, [
                  m("div", {}, [
                    m("a[href='/admin/edit/user/" + user.id + "']", {
                      "class": "",
                      config: m.route
                    }, "" + user.name), " ", m("a[href='/admin/edit/user/" + user.id + "']", {
                      "class": "usersView-edit-link pull-right",
                      config: m.route
                    }, [
                      m("span", {
                        "class": "glyphicon glyphicon-edit",
                        title: "Edit User " + user.name
                      }, ''), " Edit"
                    ])
                  ])
                ]);
              })
            ])
          ])
        ]), m("div", {
          "class": "spacer1"
        }), m("a[href='/admin/edit/user/new']", {
          "class": "btn btn-primary",
          config: m.route
        }, "Create a new user")
      ]);
      return [sbAdmin.nav.buildNav(), sbAdmin.nav.buildInContainer(mEl)];
    };
  })();

  if (typeof swapbot === "undefined" || swapbot === null) {
    swapbot = {};
  }

  swapbot.addressUtils = (function() {
    var exports;
    exports = {};
    exports.publicBotHrefFromBot = function(bot) {
      var location;
      location = window.location;
      return location.protocol + "//" + location.host + "/public/" + bot.username + "/" + bot.id;
    };
    exports.publicBotAddress = function(username, botId, location) {
      return location.protocol + "//" + location.host + "/public/" + username + "/" + botId;
    };
    return exports;
  })();

  m.route.mode = "pathname";

  m.route(document.getElementById('admin'), "/admin/dashboard", {
    "/admin/login": sbAdmin.ctrl.login,
    "/admin/logout": sbAdmin.ctrl.logout,
    "/admin/dashboard": sbAdmin.ctrl.dashboard,
    "/admin/edit/bot/:id": sbAdmin.ctrl.botForm,
    "/admin/view/bot/:id": sbAdmin.ctrl.botView,
    "/admin/shutdown/bot/:id": sbAdmin.ctrl.botShutdownForm,
    "/admin/payments/bot/:id": sbAdmin.ctrl.botPaymentsView,
    "/admin/users": sbAdmin.ctrl.usersView,
    "/admin/edit/user/:id": sbAdmin.ctrl.userForm,
    "/admin/settings": sbAdmin.ctrl.settingsView,
    "/admin/edit/setting/:id": sbAdmin.ctrl.settingsForm,
    "/admin/allbots": sbAdmin.ctrl.allbots,
    "/admin/allswaps": sbAdmin.ctrl.allswaps,
    "/admin/swapevents/:id": sbAdmin.ctrl.swapEvents
  });

  sbAdmin.quotebotSubscriber.initSubscriber(window.QUOTEBOT_URL, window.QUOTEBOT_API_TOKEN, window.QUOTEBOT_PUSHER_URL);

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

  swapbot.eventMessageUtils = (function() {
    var buildTransactionLinkElement, buildTransactionLinkHref, exports;
    exports = {};
    exports.buildTransactionLinkHref = buildTransactionLinkHref = function(txid) {
      return "https://chain.so/tx/BTC/" + txid;
    };
    exports.buildTransactionLinkElement = buildTransactionLinkElement = function(txid, linkContents) {
      if (linkContents == null) {
        linkContents = null;
      }
      if (txid == null) {
        return null;
      }
      if (linkContents == null) {
        linkContents = txid;
      }
      return React.createElement('a', {
        href: buildTransactionLinkHref(txid),
        target: '_blank',
        className: 'externalLink'
      }, linkContents);
    };
    exports.buildSwapStatusMessageElement = function(swap, bot) {
      switch (swap.state) {
        case 'sent':
        case 'refunded':
          return React.createElement('span', {}, ["Confirming  ", buildTransactionLinkElement(swap.txidOut, (swap.state === 'refunded' ? 'refund' : 'delivery')), " with " + (swapbot.formatters.confirmationsProse(swap.confirmationsOut)) + "."]);
        case 'outofstock':
          return React.createElement('span', {}, ['This swap is out of stock. ', buildTransactionLinkElement(swap.txidIn, ' Receiving tokens '), " and waiting to send " + swap.quantityOut + " " + swap.assetOut + "."]);
      }
      return React.createElement('span', {}, ["Waiting for ", buildTransactionLinkElement(swap.txidIn, swapbot.formatters.confirmationsProse(bot.confirmationsRequired)), " to send " + swap.quantityOut + " " + swap.assetOut + "."]);
    };
    exports.fullSwapSummary = function(swap, bot) {
      return React.createElement('span', {}, ["You deposited " + (swapbot.formatters.formatCurrency(swap.quantityIn)) + " " + swap.assetIn + " and we delivered " + (swapbot.formatters.formatCurrency(swap.quantityOut)) + " " + swap.assetOut + " to " + swap.destination + "."]);
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.formatters = (function() {
    var SATOSHI, exports, isZero;
    exports = {};
    SATOSHI = 100000000;
    exports.formatConfirmations = function(confirmations) {
      if (confirmations == null) {
        return 0;
      }
      return window.numeral(confirmations).format('0');
    };
    exports.confirmationsProse = function(confirmations) {
      return (exports.formatConfirmations(confirmations)) + " " + (exports.confirmationsWord(confirmations));
    };
    exports.confirmationsWord = function(confirmations) {
      return "confirmation" + (confirmations === 1 ? '' : 's');
    };
    exports.satoshisToValue = function(amount, currencyPostfix) {
      if (currencyPostfix == null) {
        currencyPostfix = 'BTC';
      }
      return exports.formatCurrency(amount / SATOSHI, currencyPostfix);
    };
    isZero = function(value) {
      if ((value == null) || value.length === 0 || value === 0) {
        return true;
      }
      return false;
    };
    exports.isZero = isZero;
    exports.isNotZero = function(value) {
      return !isZero(value);
    };
    exports.formatCurrencyWithForcedZero = function(value, currencyPostfix) {
      if (currencyPostfix == null) {
        currencyPostfix = '';
      }
      return exports.formatCurrency((isZero(value) ? 0 : value), currencyPostfix);
    };
    exports.formatCurrency = function(value, currencyPostfix) {
      var decimalText, satoshisPrefix, valueText;
      if (currencyPostfix == null) {
        currencyPostfix = '';
      }
      if ((value == null) || isNaN(value)) {
        return '';
      }
      decimalText = window.numeral(value).format('0,0.[00000000]');
      if (value > 0 && value < 0.0001) {
        satoshisPrefix = window.numeral(value * SATOSHI).format('0') + ' satoshis';
        valueText = satoshisPrefix + " (" + decimalText + ")";
      } else {
        valueText = decimalText;
      }
      return valueText + ((currencyPostfix != null ? currencyPostfix.length : void 0) ? ' ' + currencyPostfix : '');
    };
    exports.formatCurrencyAsNumber = function(value) {
      if ((value == null) || isNaN(value)) {
        return '0';
      }
      return window.numeral(value).format('0.[00000000]');
    };
    exports.formatFiatCurrency = function(value, currencyPrefix) {
      var formattedCurrencyString, prefix;
      if (currencyPrefix == null) {
        currencyPrefix = '$';
      }
      if ((value == null) || isNaN(value)) {
        return '';
      }
      formattedCurrencyString = window.numeral(value).format('0,0.00');
      prefix = '';
      if (formattedCurrencyString === '0.00') {
        prefix = 'less than ';
        formattedCurrencyString = '0.01';
      }
      return prefix + ((currencyPrefix != null ? currencyPrefix.length : void 0) ? currencyPrefix : '') + formattedCurrencyString;
    };
    exports.formatArbitraryPrecisionFiatCurrency = function(value, currencyPrefix, formatString) {
      var formattedCurrencyString, prefix;
      if (currencyPrefix == null) {
        currencyPrefix = '$';
      }
      if (formatString == null) {
        formatString = '0,0.00[000000]';
      }
      if ((value == null) || isNaN(value)) {
        return '';
      }
      formattedCurrencyString = window.numeral(value).format(formatString);
      prefix = '';
      return prefix + ((currencyPrefix != null ? currencyPrefix.length : void 0) ? currencyPrefix : '') + formattedCurrencyString;
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
            var callback, e, j, len, ref, results;
            ref = callbacksQueue[key];
            results = [];
            for (j = 0, len = ref.length; j < len; j++) {
              callback = ref[j];
              try {
                callback();
              } catch (_error) {
                e = _error;
                console.error(e);
              }
              delete callbacksQueue[key];
              clearTimeout(callbackTimeouts[key]);
              results.push(delete callbackTimeouts[key]);
            }
            return results;
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
    exports.subscribeToPusherChanel = function(channelName, dataCallbackFn, onSubscribedFn, pusherURL) {
      var client, subscription;
      if (onSubscribedFn == null) {
        onSubscribedFn = null;
      }
      if (pusherURL == null) {
        pusherURL = null;
      }
      if (pusherURL == null) {
        pusherURL = window.PUSHER_URL;
      }
      client = new window.Faye.Client(pusherURL + "/public");
      subscription = client.subscribe("/" + channelName, function(data) {
        dataCallbackFn(data);
      });
      subscription.then(function() {
        if (onSubscribedFn != null) {
          onSubscribedFn();
        }
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

  swapbot.quoteUtils = (function() {
    var exports;
    exports = {};
    exports.fiatQuoteSuffix = function(swapConfig, amount, asset) {
      var fiatAmount;
      if (swapConfig.strategy !== 'fiat') {
        return '';
      }
      fiatAmount = QuotebotStore.getCurrentPrice() * amount;
      return ' (' + swapbot.formatters.formatFiatCurrency(fiatAmount) + ')';
    };
    return exports;
  })();

  if (swapbot == null) {
    swapbot = {};
  }

  swapbot.swapUtils = (function() {
    var HARD_MINIMUM, SATOSHI, buildChangeMessage, buildDesc, buildInAmountAndBuffer, buildInAmountFromOutAmount, exports, validateInAmount, validateOutAmount;
    exports = {};
    exports.SATOSHI = 100000000;
    SATOSHI = exports.SATOSHI;
    HARD_MINIMUM = 0.00000001;
    buildDesc = {};
    buildDesc.rate = function(swapConfig) {
      var formatCurrency, inAmount, outAmount;
      outAmount = 1 * swapConfig.rate;
      inAmount = 1;
      formatCurrency = swapbot.formatters.formatCurrency;
      return (formatCurrency(outAmount)) + " " + swapConfig.out + " for every " + (formatCurrency(inAmount)) + " " + swapConfig["in"] + " you deposit";
    };
    buildDesc.fixed = function(swapConfig) {
      var formatCurrency;
      formatCurrency = swapbot.formatters.formatCurrency;
      return (formatCurrency(swapConfig.out_qty)) + " " + swapConfig.out + " for every " + (formatCurrency(swapConfig.in_qty)) + " " + swapConfig["in"] + " you deposit";
    };
    buildDesc.fiat = function(swapConfig) {
      var cost, formatCurrency, formatFiatCurrency, outAmount;
      formatCurrency = swapbot.formatters.formatCurrency;
      formatFiatCurrency = swapbot.formatters.formatArbitraryPrecisionFiatCurrency;
      outAmount = 1;
      cost = swapConfig.cost;
      return (formatCurrency(outAmount)) + " " + swapConfig.out + " for every " + (formatFiatCurrency(swapConfig.cost)) + " USD worth of " + swapConfig["in"] + " you deposit";
    };
    buildInAmountFromOutAmount = {};
    buildInAmountFromOutAmount.rate = function(outAmount, swapConfig) {
      var inAmount;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      inAmount = Math.ceil(SATOSHI * outAmount / swapConfig.rate) / SATOSHI;
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
      var buffer, inAmount, ref;
      if ((outAmount == null) || isNaN(outAmount)) {
        return 0;
      }
      if (currentRate === 0) {
        return 0;
      }
      ref = buildInAmountAndBuffer(outAmount, swapConfig, currentRate), inAmount = ref[0], buffer = ref[1];
      return inAmount + buffer;
    };
    buildInAmountAndBuffer = function(outAmount, swapConfig, currentRate) {
      var buffer, cost, inAmount, marketBuffer, maxMarketBuffer, maxMarketBufferValue;
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
      inAmount = outAmount * cost / currentRate;
      buffer = inAmount * marketBuffer;
      return [inAmount, buffer];
    };
    validateOutAmount = {};
    validateOutAmount.shared = function(outAmount, swapConfig, botBalance) {
      if (("" + outAmount).length === 0) {
        return null;
      }
      if (isNaN(outAmount)) {
        return 'The amount to purchase does not look like a number.';
      }
      if ((botBalance == null) || outAmount > botBalance) {
        return "There is not enough " + swapConfig.out + " available to make this purchase.";
      }
      return null;
    };
    validateOutAmount.rate = function(outAmount, swapConfig, botBalance) {
      var errorMsg;
      errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    validateOutAmount.fixed = function(outAmount, swapConfig, botBalance) {
      var errorMsg, formatCurrency, ratio;
      errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance);
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
    validateOutAmount.fiat = function(outAmount, swapConfig, botBalance) {
      var errorMsg, formatCurrency;
      errorMsg = validateOutAmount.shared(outAmount, swapConfig, botBalance);
      if (errorMsg != null) {
        return errorMsg;
      }
      if ((swapConfig.min_out != null) && outAmount > 0 && outAmount < swapConfig.min_out) {
        formatCurrency = swapbot.formatters.formatCurrency;
        return "To use this swap, you must purchase at least " + (formatCurrency(swapConfig.min_out)) + " " + swapConfig.out + ".";
      }
      return null;
    };
    validateInAmount = {};
    validateInAmount.shared = function(inAmount, swapConfig) {
      if (("" + inAmount).length === 0) {
        return null;
      }
      if (isNaN(inAmount)) {
        return 'The amount to send does not look like a number.';
      }
      if (inAmount < HARD_MINIMUM) {
        return 'The amount to send is too small.';
      }
      return null;
    };
    validateInAmount.rate = function(inAmount, swapConfig) {
      var errorMsg, formatCurrency;
      errorMsg = validateInAmount.shared(inAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      if ((swapConfig.min != null) && inAmount < swapConfig.min) {
        formatCurrency = swapbot.formatters.formatCurrency;
        return "This swap must be purchased by sending at least " + (formatCurrency(swapConfig.min)) + " " + swapConfig["in"] + ".";
      }
      return null;
    };
    validateInAmount.fixed = function(inAmount, swapConfig) {
      var errorMsg;
      errorMsg = validateInAmount.shared(inAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    validateInAmount.fiat = function(inAmount, swapConfig) {
      var errorMsg;
      errorMsg = validateInAmount.shared(inAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    buildChangeMessage = {};
    buildChangeMessage.fiat = function(outAmount, swapConfig, currentRate) {
      var assetIn, buffer, inAmount, ref;
      ref = buildInAmountAndBuffer(outAmount, swapConfig, currentRate), inAmount = ref[0], buffer = ref[1];
      if ((buffer != null) && buffer > 0) {
        assetIn = swapConfig["in"];
        return "This includes a buffer of " + (swapbot.formatters.formatCurrency(buffer)) + " " + assetIn + " " + (swapbot.quoteUtils.fiatQuoteSuffix(swapConfig, buffer, assetIn)) + ".";
      }
    };
    exports.buildExchangeDescriptionsForGroup = function(swapConfigGroup) {
      var els, index, j, l, len, len1, mainDesc, otherCount, otherSwapDescriptions, otherTokenEl, otherTokenEls, swapConfig, tokenDescs;
      mainDesc = '';
      otherTokenEls = [];
      for (index = j = 0, len = swapConfigGroup.length; j < len; index = ++j) {
        swapConfig = swapConfigGroup[index];
        if (index === 0) {
          mainDesc = buildDesc[swapConfig.strategy](swapConfig);
        }
        if (index >= 1) {
          otherTokenEls.push(React.createElement('span', {
            key: 'token' + index,
            className: 'tokenType'
          }, swapConfig["in"]));
        }
      }
      if (otherTokenEls.length === 0) {
        return [mainDesc, otherSwapDescriptions];
      }
      tokenDescs = [];
      otherCount = otherTokenEls.length;
      if (otherCount === 1) {
        otherSwapDescriptions = React.createElement('span', null, [otherTokenEls[0], ' is also accepted']);
      } else if (otherCount === 2) {
        otherSwapDescriptions = React.createElement('span', null, [otherTokenEls[0], ' and ', otherTokenEls[1], ' are also accepted']);
      }
      if (otherCount > 2) {
        els = [];
        for (index = l = 0, len1 = otherTokenEls.length; l < len1; index = ++l) {
          otherTokenEl = otherTokenEls[index];
          if (index === otherTokenEls.length - 1) {
            els.push(' and ');
            els.push(otherTokenEl);
          } else if (index >= 1) {
            els.push(', ');
            els.push(otherTokenEl);
          } else {
            els.push(otherTokenEl);
          }
        }
        otherSwapDescriptions = React.createElement('span', null, [els, ' are also accepted']);
      }
      return [mainDesc, otherSwapDescriptions];
    };
    exports.inAmountFromOutAmount = function(outAmount, swapConfig, currentRate) {
      var inAmount;
      inAmount = buildInAmountFromOutAmount[swapConfig.strategy](outAmount, swapConfig, currentRate);
      if (inAmount === NaN) {
        inAmount = 0;
      }
      return inAmount;
    };
    exports.validateOutAmount = function(outAmount, swapConfig, botBalance) {
      var errorMsg;
      errorMsg = validateOutAmount[swapConfig.strategy](outAmount, swapConfig, botBalance);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    exports.validateInAmount = function(inAmount, swapConfig) {
      var errorMsg;
      errorMsg = validateInAmount[swapConfig.strategy](inAmount, swapConfig);
      if (errorMsg != null) {
        return errorMsg;
      }
      return null;
    };
    exports.buildChangeMessage = function(outAmount, swapConfig, currentRate) {
      var name1;
      return typeof buildChangeMessage[name1 = swapConfig.strategy] === "function" ? buildChangeMessage[name1](outAmount, swapConfig, currentRate) : void 0;
    };
    exports.groupSwapConfigs = function(allSwapConfigs) {
      var index, j, k, len, swapConfig, swapConfigGroups, swapConfigGroupsByAssetOut, v;
      swapConfigGroupsByAssetOut = {};
      for (index = j = 0, len = allSwapConfigs.length; j < len; index = ++j) {
        swapConfig = allSwapConfigs[index];
        if (swapConfigGroupsByAssetOut[swapConfig.out] == null) {
          swapConfigGroupsByAssetOut[swapConfig.out] = [];
        }
        swapConfigGroupsByAssetOut[swapConfig.out].push(swapConfig);
      }
      swapConfigGroups = [];
      for (k in swapConfigGroupsByAssetOut) {
        v = swapConfigGroupsByAssetOut[k];
        swapConfigGroups.push(v);
      }
      return swapConfigGroups;
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

}).call(this);
