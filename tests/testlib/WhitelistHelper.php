<?php

use Swapbot\Models\User;
use Swapbot\Repositories\WhitelistRepository;
use Tokenly\TokenGenerator\TokenGenerator;

class WhitelistHelper  {

    function __construct(WhitelistRepository $whitelist_repository) {
        $this->whitelist_repository = $whitelist_repository;
    }

    public function sampleWhitelistVars(User $user=null, $override_vars = []) {
        if ($user === null) { $user = app('UserHelper')->getSampleUser(); }

        $default_vars = [
            'name' => 'My Whitelist',
            'data' => ['1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD','1AGNa15ZQXAZUgFiqJ2i7Z2DPU2J6hW62i'],
        ];
        $default_vars['user_id'] = $user['id'];

        return array_replace_recursive($default_vars, $override_vars);
    }

    public function newSampleWhitelist($user=null, $override_vars = []) {
        return $this->whitelist_repository->create($this->sampleWhitelistVars($user, $override_vars));
    }

}
