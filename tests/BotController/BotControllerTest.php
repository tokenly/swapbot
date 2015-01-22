<?php

use Illuminate\Http\RedirectResponse;
use Swapbot\Models\User;
use \PHPUnit_Framework_Assert as PHPUnit;

class BotControllerTest extends TestCase {

    public function testGetBotControllerRequiresUser()
    {
        $response = $this->call('GET', '/bot/edit');
        PHPUnit::assertEquals(302, $response->getStatusCode());
    }

    public function testGetBotControllerForm()
    {
        $user = new User(array('name' => 'Joe'));
        $this->be($user);

        $response = $this->call('GET', '/bot/edit');
        PHPUnit::assertEquals(200, $response->getStatusCode());
        PHPUnit::assertContains('Edit your Swapbot', $response->getContent());
    }

    public function testPostBotForm()
    {
        // most be a user
        $this->setUpDb();
        $user = $this->app->make('UserHelper')->getSampleUser();
        $this->be($user);

        $sample_vars = $this->app->make('BotHelper')->sampleBotVars();

        $test_specs = [
            [
                'vars' => $sample_vars,
                'error' => null,
            ],
            [
                'vars' => array_replace($sample_vars, ['name' => '']),
                'error' => 'The name field is required.',
            ],
            [
                'vars' => array_replace($sample_vars, ['description' => '']),
                'error' => 'The description field is required.',
            ],
            [
                'vars' => array_replace($sample_vars, ['asset_in_1' => '', 'asset_out_1' => '', 'vend_rate_1' => '']),
                'error' => 'at least one asset',
            ],
            [
                'vars' => array_replace($sample_vars, ['asset_in_1' => '']),
                'error' => 'specify an asset to receive for swap #1',
            ],
            [
                'vars' => array_replace($sample_vars, ['asset_out_1' => '']),
                'error' => 'specify an asset to send for swap #1',
            ],
            [
                'vars' => array_replace($sample_vars, ['vend_rate_1' => '']),
                'error' => 'Please specify a valid rate for swap #1',
            ],
            [
                'vars' => array_replace($sample_vars, ['vend_rate_1' => '-0.001']),
                'error' => 'The rate for swap #1 was not valid.',
            ],
            [
                'vars' => array_replace($sample_vars, ['asset_out_1' => 'BTC']),
                'error' => 'should not be the same',
            ],

            [
                'vars' => array_replace($sample_vars, ['asset_in_2' => 'FOOC']),
                'error' => 'specify an asset to send for swap #2',
            ],
            [
                'vars' => array_replace($sample_vars, ['asset_in_2' => 'FOOC']),
                'error' => 'Please specify a valid rate for swap #',
            ],
        ];

        // set previous location
        $response = $this->call('GET', '/bot/edit');

        // run all tests
        foreach($test_specs as $test_spec_offset => $test_spec) {
            // echo "\$test_spec['vars']:\n".json_encode($test_spec['vars'], 192)."\n";
            $response = $this->call('POST', '/bot/edit', $test_spec['vars']);
            
            // get errors
            $errors_string = null;
            if ($response instanceof RedirectResponse) { $errors_string = join(" | ", $response->getSession()->get('errors') ? $response->getSession()->get('errors')->all() : []); }

            if ($test_spec['error']) {
                // check errors
                PHPUnit::assertEquals(302, $response->getStatusCode(), "Received unexpected redirect to ".json_encode($response->headers->get('location'), 192)." with errors: $errors_string");
                PHPUnit::assertContains($test_spec['error'], $errors_string);
            } else {
                // no errors
                if ($response->getStatusCode() == 302) {
                    PHPUnit::assertContains('/bot/show/', $response->headers->get('location'), "Unexpected redirect to ".json_encode($response->headers->get('location'), 192));
                } else {
                    PHPUnit::assertEquals(200, $response->getStatusCode(), "Received errors: $errors_string in test $test_spec_offset.  Redirect was to ".json_encode($response->headers->get('location'), 192));
                }
            }
        }
    }

    public function testBotBelongsToUser() {
        $this->setUpDb();

        // create a sample bot with the standard user
        $new_bot = $this->app->make('BotHelper')->newSampleBot();

        // now create a separate user
        $another_user = $this->app->make('UserHelper')->getSampleUser('user2@tokenly.co');

        $this->be($another_user);
        $response = $this->call('GET', '/bot/show/'.$new_bot['uuid']);

        // should be unauthorized
        PHPUnit::assertEquals(403, $response->getStatusCode());

    }

}
