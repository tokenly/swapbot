(function() {
  (function() {
    window.asyncLoad = function(filename, filetype) {
      var domNode;
      if (filetype === 'js') {
        domNode = document.createElement('script');
        domNode.setAttribute('type', 'text/javascript');
        domNode.setAttribute('src', filename);
      } else if (filetype === 'css') {
        domNode = document.createElement('link');
        domNode.setAttribute('rel', 'stylesheet');
        domNode.setAttribute('type', 'text/css');
        domNode.setAttribute('href', filename);
      }
      if (typeof domNode !== 'undefined') {
        document.getElementsByTagName('head')[0].appendChild(domNode);
      }
    };
  })();

}).call(this);

//# sourceMappingURL=asyncLoad.js.map