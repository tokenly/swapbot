(function() {
  (function($) {
    var MAX_ASSETS, assetGroupsShownCount, bindAssetGroups, init, showAssets;
    console.log("edit bot says hi");
    init = function() {
      bindAssetGroups();
    };
    MAX_ASSETS = 5;
    assetGroupsShownCount = 0;
    bindAssetGroups = function() {
      showAssets(1);
      return $('a[data-add-asset]').on('click', function(e) {
        var newAssetNumber;
        e.preventDefault();
        newAssetNumber = assetGroupsShownCount + 1;
        if (newAssetNumber <= MAX_ASSETS) {
          showAssets(newAssetNumber);
        }
        if (newAssetNumber >= MAX_ASSETS) {
          $(this).hide();
        }
      });
    };
    showAssets = function(maxAssetToShow) {
      $('div[data-asset-group]').each(function() {
        var assetNumber, groupEL;
        groupEL = $(this);
        assetNumber = groupEL.data('asset-group');
        if (assetNumber <= maxAssetToShow) {
          groupEL.show();
        } else {
          groupEL.hide();
        }
      });
      assetGroupsShownCount = maxAssetToShow;
    };
    return init();
  })(jQuery);

}).call(this);
