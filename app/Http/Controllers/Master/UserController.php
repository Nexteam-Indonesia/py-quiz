<?php

namespace App\Http\Controllers\Master;

use App\Contract\Master\UserContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Utils\WebResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{

    protected UserContract $service;

    public function __construct(UserContract $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Inertia::render('master/user/index');
    }


public function fetch()
{
    $withCount = [];

    if (request()->has('filter.extend_assigned')) {
        $withCount[] = 'classRooms';
    }

    $filters = ['name'];
    if (request()->has('filter.role')) {
        $filters[] = 'role';
    }

    $data = $this->service->all(
        filters: $filters,
        sorts: ['name'],
        paginate: true,
        relation: ['roles'],
        withCount: $withCount,
        perPage: request()->get('per_page', 10)
    );
    Log::info('User fetch data:', ['data' => $data]);
    return response()->json($data);
}

    public function create()
    {
        return Inertia::render('master/user/form');
    }

    public function store(UserRequest $request)
    {
        $data = $this->service->create($request->validated());
        return WebResponse::response($data, 'master.user.index');
    }

    public function show($id)
    {
        $data = $this->service->find($id, relation: ['roles']);
        return Inertia::render('master/user/form', [
            "user" => $data
        ]);
    }

    public function update(UserRequest $request, $id)
    {
        $payload = $request->validated();

        unset($payload['password_confirmation']);

        $payload['password'] = Hash::make($payload['password']);

        $data = $this->service->update(
            [
                ['id', '=', $id],
            ],
            $payload
        );
        return WebResponse::response($data, 'master.user.index');
    }

    public function destroy($id)
    {
        $data = $this->service->destroy($id);
        return WebResponse::response($data, 'master.user.index');
    }
}
