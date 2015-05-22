<?php

namespace Arrilot\Api\Skeleton;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

abstract class BaseController extends LaravelController
{
    /**
     * HTTP header status code.
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Fractal Manager instance.
     *
     * @var Manager
     */
    protected $fractal;

    /**
     * Eloquent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model;
     */
    protected $model;

    /**
     * Fractal Transformer instance.
     *
     * @var \League\Fractal\TransformerAbstract
     */
    protected $transformer;

    /**
     * Do we need to unguard the model before create/update?
     *
     * @var boolean
     */
    protected $unguard = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->model = $this->model();
        $this->transformer = $this->transformer();
        $this->fractal = new Manager;

        if (Input::has('include'))
        {
            $this->fractal->parseIncludes(camel_case(Input::get('include')));
        }
    }

    /**
     * Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract protected function model();

    /**
     * Transformer for the current model.
     *
     * @return \League\Fractal\TransformerAbstract
     */
    abstract protected function transformer();

    /**
     * Getter for statusCode.
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Setter for statusCode.
     *
     * @param int $statusCode Value to set
     *
     * @return self
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Respond with a given item.
     *
     * @param $item
     * @param $callback
     *
     * @return mixed
     */
    protected function respondWithItem($item, $callback)
    {
        $resource = new Item($item, $callback);

        $rootScope = $this->prepareRootScope($resource);

        return $this->respondWithArray($rootScope->toArray());
    }

    /**
     * Respond with a given collection.
     *
     * @param $collection
     * @param $callback
     *
     * @return mixed
     */
    protected function respondWithCollection($collection, $callback)
    {
        $resource = new Collection($collection, $callback);

        $rootScope = $this->prepareRootScope($resource);

        return $this->respondWithArray($rootScope->toArray());
    }


    /**
     * Respond with a given array of items.
     *
     * @param array $array
     * @param array $headers
     *
     * @return mixed
     */
    protected function respondWithArray(array $array, array $headers = [])
    {
        return Response::json($array, $this->statusCode, $headers);
    }

    /**
     * Response with the current error.
     *
     * @param $message
     *
     * @return mixed
     */
    protected function respondWithError($message)
    {
        return $this->respondWithArray([
            'error' => [
                'http_code' => $this->statusCode,
                'message'   => $message,
            ]
        ]);
    }

    /**
     * Prepare root scope and adds meta.
     *
     * @param Item $resource
     * @return mixed
     */
    protected function prepareRootScope($resource)
    {
        $availableIncludes = $resource->getTransformer()->getAvailableIncludes();
        $resource->setMetaValue('available_includes', $availableIncludes);

        $defaultIncludes = $resource->getTransformer()->getDefaultIncludes();
        $resource->setMetaValue('default_includes', $defaultIncludes);

        return $this->fractal->createData($resource);
    }

    /**
     * Get the validation rules for create.
     *
     * @return array
     */
    protected function rulesForCreate()
    {
        return [];
    }

    /**
     * Get the validation rules for update.
     *
     * @param $id
     * @return array
     */
    protected function rulesForUpdate($id)
    {
        return [];
    }

    /**
     * Generate a Response with a 403 HTTP header and a given message.
     *
     * @param $message
     *
     * @return  Response
     */
    public function errorForbidden($message = 'Forbidden')
    {
        return $this->setStatusCode(403)->respondWithError($message);
    }

    /**
     * Generate a Response with a 500 HTTP header and a given message.
     *
     * @param $message
     *
     * @return  Response
     */
    public function errorInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }

    /**
     * Generate a Response with a 404 HTTP header and a given message.
     *
     * @param $message
     *
     * @return  Response
     */
    public function errorNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * Generate a Response with a 401 HTTP header and a given message.
     *
     * @param $message
     *
     * @return  Response
     */
    public function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }

    /**
     * Generate a Response with a 400 HTTP header and a given message.
     *
     * @param $message
     *
     * @return  Response
     */
    public function errorWrongArgs($message = 'Wrong Arguments')
    {
        return $this->setStatusCode(400)->respondWithError($message);
    }

    /**
     * Generate a Response with a 501 HTTP header and a given message.
     *
     * @param $message
     *
     * @return  Response
     */
    public function errorNotImplemented($message = 'Not implemented')
    {
        return $this->setStatusCode(501)->respondWithError($message);
    }

    /**
     * Display a listing of the resource.
     * GET /api/{resource}
     *
     * @return Response
     */
    public function index()
    {
        $with  = $this->getEagerLoad();
        $items = $this->model->with($with)->get();

        return $this->respondWithCollection($items, $this->transformer);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}
     *
     * @return Response
     */
    public function store()
    {
        $input = Input::json();
        $data  = $input->get('data');

        $v = Validator::make($data, $this->rulesForCreate());
        if ($v->fails())
        {
            return $this->errorWrongArgs('Validation failed');
        }

        $this->unguardIfNeeded();

        $item = $this->model->create($data);

        return $this->respondWithItem($item, $this->transformer);
    }

    /**
     * Display the specified resource.
     * GET /api/{resource}/{id}
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $with = $this->getEagerLoad();

        $item = $this->findItem($id, $with);
        if (!$item)
        {
            return $this->errorNotFound();
        }

        return $this->respondWithItem($item, $this->transformer);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/{resource}/{id}
     *
     * @param  int $id
     *
     * @return Response
     */
    public function update($id)
    {
        $input = Input::json();
        $data  = $input->get('data');

        if (!$data)
        {
            return $this->errorWrongArgs('Empty data');
        }

        $item = $this->findItem($id);
        if (!$item)
        {
            return $this->errorNotFound();
        }

        $v = Validator::make($data, $this->rulesForUpdate($item->id));
        if ($v->fails())
        {
            return $this->errorWrongArgs('Validation failed');
        }

        $this->unguardIfNeeded();

        $item->fill($data);
        $item->save();

        return $this->respondWithItem($item, $this->transformer);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/{resource}/{id}
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $item = $this->findItem($id);

        if (!$item)
        {
            return $this->errorNotFound();
        }

        $item->delete();

        return Response::json(['message' => 'Deleted']);
    }

    /**
     * Show the form for creating the specified resource.
     *
     * @return Response
     */
    public function create()
    {
        return $this->errorNotImplemented();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        return $this->errorNotImplemented();
    }

    /**
     * Specify relations for eager loading.
     *
     * @return array
     */
    protected function getEagerLoad()
    {
        $include  = camel_case(Input::get('include', ''));
        $includes = explode(',', $include);
        $includes = array_filter($includes);

        return $includes ?: [];
    }

    /**
     * Get item according to mode.
     *
     * @param $id
     * @param array $with
     *
     * @return mixed
     */
    protected function findItem($id, array $with = [])
    {
        if (Input::has('use_as_id'))
        {
            return $this->model->with($with)->where(Input::get('use_as_id'), '=', $id)->first();
        }

        return $this->model->with($with)->find($id);
    }

    /**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard)
        {
            $this->model->unguard();
        }
    }
}