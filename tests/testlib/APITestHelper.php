<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Swapbot\Models\User;
use Tokenly\HmacAuth\Generator;
use \PHPUnit_Framework_Assert as PHPUnit;

class APITestHelper  {

    protected $override_user = null;
    protected $repository    = null;

    function __construct(Application $app) {
        $this->app = $app;
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // setups

    public function createModelWith($create_model_fn) {
        $this->create_model_fn = $create_model_fn;
        return $this;
    }
    public function useRepository($repository) {
        $this->repository = $repository;
        return $this;
    }

    public function useUserHelper($user_helper) {
        $this->user_helper = $user_helper;
        return $this;
    }

    public function useCleanupFunction($cleanup_fn) {
        $this->cleanup_fn = $cleanup_fn;
        return $this;
    }

    public function setURLBase($url_base) {
        $this->url_base = $url_base;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    

    public function cleanup() {
        if (isset($this->cleanup_fn) AND is_callable($this->cleanup_fn)) {
            call_user_func($this->cleanup_fn, $this->repository);
        } else {
            if ($this->repository) {
                foreach($this->repository->findAll() as $model) {
                    $this->repository->delete($model);
                }
            }
        }
        return $this;
    }

    public function testRequiresUser() {
        $this->cleanup();

        // create a model
        $created_model = $this->newModel();

        return $this->testURLCallRequiresUser($this->extendURL($this->url_base, '/'.$created_model['uuid']));
    }

    public function testURLCallRequiresUser($url) {
        // call the API without a user
        $request = $this->createAPIRequest('GET', $url);
        $response = $this->sendRequest($request);
        PHPUnit::assertEquals(403, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
    }

    public function testCreate($create_vars) {
        $this->cleanup();

        // call the API
        $url = $this->extendURL($this->url_base, null);
        $response = $this->callAPIWithAuthentication('POST', $url, $create_vars);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor POST ".$url);
        $actual_response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($actual_response_from_api);


        // load from repository
        $loaded_resource_model = $this->repository->findByUuid($actual_response_from_api['id']);
        PHPUnit::assertNotEmpty($loaded_resource_model);

        // build expected response from API
        $expected_response_from_api = $loaded_resource_model->serializeForAPI();
        PHPUnit::assertEquals($expected_response_from_api, $actual_response_from_api);

        return $loaded_resource_model;
    }

    public function testIndex($url_extension=null) {
        $this->cleanup();

        // create 2 models
        $created_models = [];
        $created_models[] = $this->newModel();
        $created_models[] = $this->newModel();
        

        // now call the API
        $url = $this->extendURL($this->url_base, $url_extension);
        $response = $this->callAPIWithAuthentication('GET', $url);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        $actual_response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($actual_response_from_api);

        // populate the $expected_created_resource
        $expected_api_response = [$created_models[0]->serializeForAPI(), $created_models[1]->serializeForAPI()];
        $expected_created_resource = $this->normalizeExpectedAPIResponse($expected_api_response, $actual_response_from_api);

        // check response
        PHPUnit::assertEquals($expected_created_resource, $actual_response_from_api);

        // return the models
        return $created_models;
    }

    public function testShow() {
        $this->cleanup();

        // create a model
        $created_model = $this->newModel();

        // call the API
        $url = $this->extendURL($this->url_base, '/'.$created_model['uuid']);
        $response = $this->callAPIWithAuthentication('GET', $url);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);
        $actual_response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($actual_response_from_api);

        // populate the $expected_created_resource
        $expected_api_response = $created_model->serializeForAPI();
        $expected_api_response = $this->normalizeExpectedAPIResponse($expected_api_response, $actual_response_from_api);

        // check response
        PHPUnit::assertEquals($expected_api_response, $actual_response_from_api);

        // return the model
        return $created_model;
    }

    public function testUpdate($update_attributes) {
        $this->cleanup();

        // create a model
        $created_model = $this->newModel();

        // call the API
        $url = $this->extendURL($this->url_base, '/'.$created_model['uuid']);
        $response = $this->callAPIWithAuthentication('PUT', $url, $update_attributes);
        PHPUnit::assertEquals(204, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        // load the model and make sure it was updated
        $reloaded_model = $this->repository->findByUuid($created_model['uuid']);


        // only check the updated attributes
        $expected_model_vars = $update_attributes;
        $actual_model_vars = [];
        foreach(array_keys($update_attributes) as $k) { $actual_model_vars[$k] = $reloaded_model[$k]; }
        PHPUnit::assertEquals($expected_model_vars, $actual_model_vars);

        // return the model
        return $reloaded_model;
    }

    public function testDelete() {
        $this->cleanup();

        // create a model
        $created_model = $this->newModel();

        // call the API
        $url = $this->extendURL($this->url_base, '/'.$created_model['uuid']);
        $response = $this->callAPIWithAuthentication('DELETE', $url);
        PHPUnit::assertEquals(204, $response->getStatusCode(), "Unexpected response code of ".$response->getContent()."\n\nfor GET ".$url);

        // make sure the model was deleted
        $reloaded_model = $this->repository->findByUuid($created_model['uuid']);
        PHPUnit::assertEmpty($reloaded_model);

        // return the delete model
        return $created_model;
    }


    public function be(User $new_user) {
        $this->override_user = $new_user;
        return $this;
    }

    public function getUser() {
        if (isset($this->override_user)) { return $this->override_user; }
        return $this->user_helper->getSampleUser();
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function callAPIWithAuthentication($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null) {
        $request = $this->createAPIRequest($method, $uri, $parameters, $cookies, $files, $server, $content);
        $generator = new Generator();
        $user = $this->getUser();
        $api_token = $user['apitoken'];
        $secret    = $user['apisecretkey'];
        $generator->addSignatureToSymfonyRequest($request, $api_token, $secret);
        return $this->sendRequest($request);
    }

    public function createAPIRequest($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null) {
        // convert a POST to json
        if ($parameters AND in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $content = json_encode($parameters);
            $server['CONTENT_TYPE'] = 'application/json';
            $parameters = [];
        }

        return Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }

    public function sendRequest($request) {
        return $this->app->make('Illuminate\Contracts\Http\Kernel')->handle($request);
    }

    protected function extendURL($base_url, $url_extension) {
        if (!strlen($url_extension)) { return $base_url; }
        return $base_url.(strlen($url_extension) ? '/'.ltrim($url_extension, '/') : '');
    }

    protected function normalizeExpectedAPIResponse($expected_api_response, $actual_response_from_api) {
        return $expected_api_response;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    
    

    protected function newModel() {
        $model = call_user_func($this->create_model_fn, $this->getUser());
        if (!$model) { throw new Exception("Failed to create model", 1); }
        return $model;
    }



}
