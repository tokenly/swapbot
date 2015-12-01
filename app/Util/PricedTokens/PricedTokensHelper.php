<?php

namespace Swapbot\Util\PricedTokens;

use Exception;
use Swapbot\Swap\Settings\Facade\Settings;

/*
* PricedTokensHelper
*/
class PricedTokensHelper {

    public function getAllPricedTokensList() {
        return array_values($this->getAllPricedTokens());
    }
    public function getAllPricedTokens() {
        if (!isset($this->all_priced_tokens)) {
            $priced_tokens = Settings::get('pricedTokens');
            $this->all_priced_tokens = $this->normalizePricedTokensResponse($priced_tokens);
        }

        return $this->all_priced_tokens;
    }

    public function tokenSymbol($token) {
        $all_priced_tokens = $this->getAllPricedTokens();
        if (isset($all_priced_tokens[$token])) {
            return $all_priced_tokens[$token];
        }

        return null;
    }

    public function isPriceableToken($token) {
        $all_priced_tokens = $this->getAllPricedTokens();
        return isset($all_priced_tokens[$token]);
    }

    // ------------------------------------------------------------------------
    
    protected function normalizePricedTokensResponse($raw_priced_tokens) {
        $priced_tokens = [];
        foreach($raw_priced_tokens as $raw_priced_token) {
            if (!isset($raw_priced_token['symbol'])) {
                $raw_priced_token['symbol'] = $raw_priced_token['token'];
            }
            $priced_tokens[$raw_priced_token['token']] = $raw_priced_token;
        }

        ksort($priced_tokens);

        return $priced_tokens;
    }


}

