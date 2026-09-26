<?php

namespace App\Core\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseController
{
    protected $listService;

    protected $createService;

    protected $findByIdService;

    protected $updateService;

    protected $deleteService;

    protected $resourceClass;

    protected ?string $storeRequestClass = null;

    protected function getPerPage(
        Request $request,
        int $default = 100,
        int $max = 1000,
    ): int {
        $perPage = $request->query('per_page', $default);

        return max(1, min($perPage, $max));
    }

    /**
     * Generic index method
     */
    public function index(Request $request)
    {
        $perPage = $this->getPerPage($request);
        $items = $this->listService->execute($perPage);

        return $this->resourceClass::collection($items);
    }

    /**
     * Generic store method
     */
    public function store(Request $request)
    {
        $item = $this->createService->execute($this->validatedData($request));

        return new $this->resourceClass($item);
    }

    /**
     * Generic show method
     */
    public function show($id)
    {
        $item = $this->findByIdService->execute($id);

        return new $this->resourceClass($item);
    }

    /**
     * Generic update method
     */
    public function update($id, FormRequest|Request $request)
    {
        $item = $this->updateService->execute(
            $id,
            $this->validatedData($request),
        );

        return new $this->resourceClass($item);
    }

    /**
     * Generic destroy method
     */
    public function destroy($id)
    {
        $this->deleteService->execute($id);

        return response()->json(
            ['message' => 'Resource deleted successfully'],
            200,
        );
    }

    protected function validatedData(Request $request): array
    {
        if (
            ! $this->storeRequestClass ||
            ! is_subclass_of($this->storeRequestClass, FormRequest::class)
        ) {
            return $request->all();
        }

        /** @var FormRequest $formRequest */
        $formRequest = app($this->storeRequestClass);

        return Validator::make(
            $request->all(),
            $formRequest->rules(),
            method_exists($formRequest, 'messages')
                ? $formRequest->messages()
                : [],
            method_exists($formRequest, 'attributes')
                ? $formRequest->attributes()
                : [],
        )->validate();
    }
}
