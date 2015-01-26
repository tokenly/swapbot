<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Tokenly\HmacAuth\Generator;
use \PHPUnit_Framework_Assert as PHPUnit;

class APITestHelper  {

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

    public function setURLBase($url_base) {
        $this->url_base = $url_base;
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    

    public function cleanup() {
        foreach($this->repository->findAll() as $model) {
            $this->repository->delete($model);
        }
        return $this;
    }

    public function testCreate($create_vars) {
        $this->cleanup();

        // call the API
        $response = $this->callAPIWithAuthentication('POST', $this->extendURL($this->url_base, null), $create_vars);
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Response was: ".$response->getContent()."\n\nfor POST ".$this->extendURL($this->url_base, null));
        $response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($response_from_api);


        // load from repository
        $loaded_resource_model = $this->repository->findByUuid($response_from_api['id']);
        PHPUnit::assertNotEmpty($loaded_resource_model);

        // make sure everything returned from the api matches what is in the repository
        $expected_response_from_api = ['id' => $loaded_resource_model['uuid']];
        PHPUnit::assertEquals($expected_response_from_api, $response_from_api);

        return $loaded_resource_model;
    }

    public function testIndex() {
        $this->cleanup();

        // create 2 models
        $created_models = [];
        $created_models[] = $this->newModel();
        $created_models[] = $this->newModel();
        

        // now call the API
        $response = $this->callAPIWithAuthentication('GET', $this->extendURL($this->url_base, null));
        PHPUnit::assertEquals(200, $response->getStatusCode(), "Response was: ".$response->getContent()."\n\nfor GET ".$this->extendURL($this->url_base, null));
        $response_from_api = json_decode($response->getContent(), true);
        PHPUnit::assertNotEmpty($response_from_api);

        // populate the $expected_created_resource
        $expected_api_response = [$created_models[0]->serializeForAPI(), $created_models[1]->serializeForAPI()];
        $expected_created_resource = $this->normalizeExpectedAPIResponse($expected_api_response, $response_from_api);

        // check response
        PHPUnit::assertEquals($expected_created_resource, $response_from_api);

        // return the models
        return $created_models;
    }

    public function testLoad() {
        $created_model = $this->newModel();
        $loaded_model = $this->repository->findByID($created_model['id']);
        PHPUnit::assertNotEmpty($loaded_model);
        PHPUnit::assertEquals((array)$created_model, (array)$loaded_model);
    }

    public function testUpdate($update_attributes) {
        $created_model = $this->newModel();

        // update by ID
        $this->repository->update($created_model, $update_attributes);

        // load from repo again and test
        $loaded_model = $this->repository->findByUuid($created_model['uuid']);
        PHPUnit::assertNotEmpty($loaded_model);
        foreach($update_attributes as $k => $v) {
            PHPUnit::assertEquals($v, $loaded_model[$k]);
        }

        // update by UUID
        $this->repository->updateByUuid($created_model['uuid'], $update_attributes);

        // load from repo again
        $loaded_model = $this->repository->findByUuid($created_model['uuid']);
        PHPUnit::assertNotEmpty($loaded_model);
        foreach($update_attributes as $k => $v) {
            PHPUnit::assertEquals($v, $loaded_model[$k]);
        }

        // clean up
        
    }

    public function testDelete() {
        $created_model = $this->newModel();

        // delete by ID
        PHPUnit::assertTrue($this->repository->delete($created_model));

        // load from repo
        $loaded_model = $this->repository->findByID($created_model['id']);
        PHPUnit::assertEmpty($loaded_model);


        // create another one
        $created_model = $this->newModel();

        // delete by uuid
        $this->repository->deleteByUuid($created_model['uuid']);

        // load from repo
        $loaded_model = $this->repository->findByUuid($created_model['uuid']);
        PHPUnit::assertEmpty($loaded_model);

    }


    public function testFindAll() {
        $created_model = $this->newModel();
        $created_model_2 = $this->newModel();
        $loaded_models = array_values(iterator_to_array($this->repository->findAll()));
        PHPUnit::assertNotEmpty($loaded_models);
        PHPUnit::assertCount(2, $loaded_models);
        PHPUnit::assertEquals((array)$created_model, (array)$loaded_models[0]);
        PHPUnit::assertEquals((array)$created_model_2, (array)$loaded_models[1]);
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////

    public function callAPIWithAuthentication($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null) {
        $request = $this->createAPIRequest($method, $uri, $parameters, $cookies, $files, $server, $content);
        $generator = new Generator();
        $api_token = 'TESTAPITOKEN';
        $secret    = 'TESTAPISECRET';
        $generator->addSignatureToSymfonyRequest($request, $api_token, $secret);
        return $this->app->make('Illuminate\Contracts\Http\Kernel')->handle($request);
    }

    protected function createAPIRequest($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null) {
        // convert a POST to json
        if ($parameters AND $method == 'POST') {
            $content = json_encode($parameters);
            $server['CONTENT_TYPE'] = 'application/json';
            $parameters = [];
        }

        return Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }

    protected function extendURL($base_url, $url_extension) {
        if (!strlen($url_extension)) { return $base_url; }
        return $base_url.(strlen($url_extension) ? '/'.ltrim($url_extension, '/') : '');
    }

    protected function normalizeExpectedAPIResponse($expected_api_response, $response_from_api) {
        return $expected_api_response;
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    
    

    protected function newModel() {
        $model = call_user_func($this->create_model_fn, $this->getUser());
        if (!$model) { throw new Exception("Failed to create model", 1); }
        return $model;
    }

    protected function getUser() {
        return $this->user_helper->getSampleUser();
    }


}
