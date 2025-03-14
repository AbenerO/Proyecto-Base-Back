<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AppBaseController;
use App\Models\Permission;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Api\CreateRoleApiRequest;
use App\Http\Requests\Api\UpdateRoleApiRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class RoleApiController
 */
class RoleApiController extends AppbaseController
{

    /**
     * //     * @return array
     * //     */
    //    public static function middleware(): array
    //    {
    //        return [
    //            new Middleware('abilities:ver roles', only: ['index', 'show']),
    //            new Middleware('abilities:crear roles', only: ['store']),
    //            new Middleware('abilities:editar roles', only: ['update']),
    //            new Middleware('abilities:eliminar roles', only: ['destroy']),
    //        ];
    //    }

    /**
     * Display a listing of the Roles.
     * GET|HEAD /roles
     */
    public function index(Request $request): JsonResponse
    {
        $roles = QueryBuilder::for(Role::class)
            ->with([])
            ->allowedFilters(['name', 'guard_name'])
            ->allowedSorts(['name', 'guard_name'])
            ->defaultSort('id') // Ordenar por defecto por fecha descendente
            ->allowedIncludes(['permissions'])
            ->paginate($request->get('per_page', 10));

        return $this->sendResponse($roles->toArray(), 'roles recuperados con éxito.');
    }


    /**
     * Store a newly created Role in storage.
     * POST /roles
     */
    public function store(CreateRoleApiRequest $request): JsonResponse
    {
        try {
            $atributos = $request['data'];
            DB::beginTransaction();

            $role = Role::create($atributos);
            $this->createAuditPermisos($role, $atributos['permisos']);

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $role,
                'message' => 'Role guardado con éxito.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el Role.'
            ], 500);
        }
    }


    /**
     * Display the specified Role.
     * GET|HEAD /roles/{id}
     */
    public function show(Role $role)
    {
        return $this->sendResponse($role->toArray(), 'Role recuperado con éxito.');
    }


    /**
     * Update the specified Role in storage.
     * PUT/PATCH /roles/{id}
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        try {
            $atributos = $request['data'];
            DB::beginTransaction();

            $role->update($atributos);
            $role->save();
            $this->updateAuditPermisos($role, $atributos['permisos']);

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $role,
                'message' => 'Role actualizado con éxito.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el Role.'
            ], 500);
        }
    }

    /**
     * Remove the specified Role from storage.
     * DELETE /roles/{id}
     */
    public function destroy(Role $role): JsonResponse
    {
        $role->delete();
        return $this->sendResponse(null, 'Role eliminado con éxito.');
    }

    /**
     * Get columns of the table
     * GET /roles/columns
     */
    public function getColumnas(): JsonResponse
    {

        $columns = Schema::getColumnListing((new Role)->getTable());

        $columnasSinTimesTamps = array_diff($columns, ['id', 'created_at', 'updated_at', 'deleted_at']);

        $nombreDeTabla = (new Role)->getTable();

        $data = [
            'columns' => array_values($columnasSinTimesTamps),
            'nombreDelModelo' => 'Role',
            'nombreDeTabla' => $nombreDeTabla,
            'ruta' => 'api/' . $nombreDeTabla,
        ];

        return $this->sendResponse($data, 'Columnas de la tabla roles recuperadas con éxito.');
    }

    private function createAuditPermisos($role, $permisos)
    {
        if ($permisos) {
            foreach ($permisos as $permiso_id) {
                $permiso = Permission::find($permiso_id['id']);
                if ($permiso) {
                    $role->givePermissionTo($permiso);
                }
            }
        }
    }

    private function updateAuditPermisos($role, $permisos)
    {
        if ($permisos) {
            $permisos_asignar = [];

            foreach ($permisos as $permiso_id) {
                $permiso = Permission::find($permiso_id['id']);
                if ($permiso) {
                    $permisos_asignar[] = $permiso;
                }
            }

            $role->syncPermissions($permisos_asignar);

        } else {
            $role->syncPermissions([]);
        }
    }

}
