<?php


function normalize_updated_date($actual_array, $expected_array) {
    if (isset($actual_array['updated_at'])) { $actual_array['updated_at'] = $expected_array['updated_at']; }

    return $actual_array;
}