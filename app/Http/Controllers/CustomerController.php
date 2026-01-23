<?php

namespace App\Http\Controllers;

use App\Actions\Customers\ExportCustomersAction;
use App\Actions\Customers\IndexCustomersAction;
use App\Domain\Customers\CustomerFilterService;
use App\DTOs\Customers\UpdateCustomerData;
use App\Http\Requests\Customers\ExportCustomerRequest;
use App\Http\Requests\Customers\IndexCustomerRequest;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Http\Resources\Customers\CustomerCollection;
use App\Http\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

/**
 * Controller for customer management operations.
 *
 * Handles CRUD operations for customers.
 */
class CustomerController extends Controller
{
    /**
     * Display a listing of the customers with filtering and sorting.
     *
     * Supports pagination, search, advanced filters (branch, customer_type, status), and sorting.
     *
     * @param  \App\Http\Requests\Customers\IndexCustomerRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexCustomerRequest $request): JsonResponse
    {
        $customers = (new IndexCustomersAction(app(CustomerFilterService::class)))->execute($request);

        return (new CustomerCollection($customers))->response();
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \App\Http\Requests\Customers\StoreCustomerRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load('branch');

        return (new CustomerResource($customer))->response();
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \App\Http\Requests\Customers\UpdateCustomerRequest  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $dto = UpdateCustomerData::fromArray($request->validated());
        $customer->update($dto->toArray());

        return (new CustomerResource($customer))->response();
    }

    /**
     * Export customers to Excel.
     *
     * @param  \App\Http\Requests\Customers\ExportCustomerRequest  $request
     * @param  \App\Actions\Customers\ExportCustomersAction  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportCustomerRequest $request, ExportCustomersAction $action): JsonResponse
    {
        return $action->handle($request);
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
