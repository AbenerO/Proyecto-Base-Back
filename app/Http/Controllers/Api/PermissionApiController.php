<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AppBaseController;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Api\CreatePermissionApiRequest;
use App\Http\Requests\Api\UpdatePermissionApiRequest;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class PermissionApiController
 */
class PermissionApiController extends AppbaseController
{

      /**
  //     * @return array
  //     */
  //    public static function middleware(): array
  //    {
  //        return [
  //            new Middleware('abilities:ver permissions', only: ['index', 'show']),
  //            new Middleware('abilities:crear permissions', only: ['store']),
  //            new Middleware('abilities:editar permissions', only: ['update']),
  //            new Middleware('abilities:eliminar permissions', only: ['destroy']),
  //        ];
  //    }

    /**
     * Display a listing of the Permissions.
     * GET|HEAD /permissions
     */
    public function index(Request $request): JsonResponse
    {
        $permissions = QueryBuilder::for(Permission::class)
            ->with([])
            ->allowedFilters([
    'name',
    'subject',
    'guard_name'
])
            ->allowedSorts([
    'name',
    'subject',
    'guard_name'
])
            ->defaultSort('-id') // Ordenar por defecto por fecha descendente
            ->paginate($request->get('per_page', 10));

        return $this->sendResponse($permissions->toArray(), 'permissions recuperados con éxito.');
    }


    /**
     * Store a newly created Permission in storage.
     * POST /permissions
     */
    public function store(CreatePermissionApiRequest $request): JsonResponse
    {
        $input = $request->all();

        $permissions = Permission::create($input);

        return $this->sendResponse($permissions->toArray(), 'Permission creado con éxito.');
    }


    /**
     * Display the specified Permission.
     * GET|HEAD /permissions/{id}
     */
    public function show(Permission $permission)
    {
        return $this->sendResponse($permission->toArray(), 'Permission recuperado con éxito.');
    }



    /**
    * Update the specified Permission in storage.
    * PUT/PATCH /permissions/{id}
    */
    public function update(UpdatePermissionApiRequest $request, $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->update($request->validated());
        return $this->sendResponse($permission, 'Permission actualizado con éxito.');
    }

    /**
    * Remove the specified Permission from storage.
    * DELETE /permissions/{id}
    */
    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();
        return $this->sendResponse(null, 'Permission eliminado con éxito.');
    }

    /**
    * Get columns of the table
    * GET /permissions/columns
    */
    public function getColumnas(): JsonResponse
    {

        $columns = Schema::getColumnListing((new Permission)->getTable());

        $columnasSinTimesTamps = array_diff($columns, ['id', 'created_at', 'updated_at', 'deleted_at']);

        $nombreDeTabla = (new Permission)->getTable();

        $data = [
            'columns' => array_values($columnasSinTimesTamps),
            'nombreDelModelo' => 'Permission',
            'nombreDeTabla' => $nombreDeTabla,
            'ruta' => 'api/'.$nombreDeTabla,
        ];

        return $this->sendResponse($data, 'Columnas de la tabla permissions recuperadas con éxito.');
    }

}
