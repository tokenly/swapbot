<?php

namespace Swapbot\Http\Requests\Bot\Transformers;

class BotTransformer {

    public function santizeAttributes($attributes, $rules) {
        $out = [];
        foreach (array_keys($rules) as $field_name) {
            $out[$field_name] = isset($attributes[$field_name]) ? $attributes[$field_name] : null;
        }

        // santize swaps
        if (isset($attributes['swaps'])) {
            $out['swaps'] = $this->sanitizeSwaps($attributes['swaps']);
        }

        return $out;
    }

    protected function sanitizeSwaps($swaps) {
        $swaps_out = [];

        if ($swaps) {
            foreach(array_values($swaps) as $offset => $swap) {
                $swaps_out[] = $this->sanitizeSwap($swap);
            }
        }

        return $swaps_out;
    }

    protected function sanitizeSwap($swap) {

        return [
            'in'   => isset($swap['in']) ? $swap['in'] : null,
            'out'  => isset($swap['out']) ? $swap['out'] : null,
            'rate' => isset($swap['rate']) ? $swap['rate'] : null,
        ];

    }
}
