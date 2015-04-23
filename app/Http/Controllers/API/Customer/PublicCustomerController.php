<?php

namespace Swapbot\Http\Controllers\API\Customer;

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Commands\CreateCustomer;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Repositories\CustomerRepository;
use Swapbot\Repositories\SwapRepository;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;
use Exception;

class PublicCustomerController extends APIController {

    use DispatchesCommands;

    protected $protected = false;

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  Request             $request
     * @param  CustomerRepository  $customer_repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function store(Request $request, CustomerRepository $customer_repository, SwapRepository $swap_repository, APIControllerHelper $api_helper)
    {
        $attributes = $request->all();

        // load (and require) the swap by uuid
        $swap = $swap_repository->findByUuid($attributes['swapId']);
        if (!$swap) { throw new HttpResponseException($api_helper->newJsonResponseWithErrors("Unable to find a swap with this id", 422)); }
        $attributes['swapId'] = $swap['id'];

        // create a customer UUID
        $uuid = Uuid::uuid4()->toString();
        $attributes['uuid'] = $uuid;

        try {
            // create the customer
            $this->dispatch(new CreateCustomer($attributes));

        } catch (ValidationException $e) {
            // handle validation errors
            throw new HttpResponseException($api_helper->newJsonResponseWithErrors($e->errors()->all(), 422));
        }

        // load the new customer
        $customer = $customer_repository->findByUuid($uuid);
        if (!$customer) { throw new Exception("Unable to find new customer", 1); }

        // format for API
        return $api_helper->transformResourceForOutput($customer);
    }


}
