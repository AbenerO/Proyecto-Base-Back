<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AppBaseController;
use App\Models\Role;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Api\CreatePermissionApiRequest;
use App\Http\Requests\Api\UpdatePermissionApiRequest;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->allowedFilters(['name', 'subject', 'guard_name'])
            ->allowedSorts(['name', 'subject', 'guard_name'])
            ->defaultSort('id') // Ordenar por defecto por fecha descendente
            ->paginate($request->get('per_page', 10));

        return $this->sendResponse($permissions->toArray(), 'permissions recuperados con éxito.');
    }


    /**
     * Store a newly created Permission in storage.
     * POST /permissions
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Obtener los datos, asegurando que 'data' siempre sea un array
            $attributes = $request->input('data', []);

            // Si 'roles' no está presente, se asigna un array vacío
            $roles = $attributes['roles'] ?? [];

            // Crear el permiso sin depender de 'roles'
            $permission = Permission::create($attributes);

            // Solo asignar roles si hay roles en la solicitud
            if (!empty($roles)) {
                $this->createAuditRoles($permission, $roles);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $permission,
                'message' => 'Permiso creado correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
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
    public function update(Request $request, Permission $permission): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Tomar los datos asegurando que sean un array
            $attributes = $request->input('data', []);

            // Si 'roles' no está presente, asignar un array vacío
            $roles = $attributes['roles'] ?? [];

            $permission->update($attributes);

            // Solo actualizar roles si 'roles' fue enviado
            if (!empty($roles)) {
                $this->updateAuditRoles($permission, $roles);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $permission,
                'message' => 'Permiso actualizado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
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

    private function createAuditRoles($permiso, $roles)
    {
        if ($roles) {
            foreach ($roles as $role_id) {
                $role = Role::find($role_id['id']);
                if ($role) {
                    $role->givePermissionTo($permiso);
                }
            }
        }
    }

    private function updateAuditRoles($permiso, $roles)
    {
        try {
            if (is_array($roles)) {
                // Obtener los IDs de los roles seleccionados
                $rolesIdsSeleccionados = array_column($roles, 'id');

                // Obtener todos los roles que tienen actualmente el permiso
                $rolesConPermiso = Role::whereHas('permissions', function ($query) use ($permiso) {
                    $query->where('name', $permiso->name);
                })->pluck('id')->toArray();
                // Roles para los que se debe asignar el permiso
                $rolesParaAsignar = array_diff($rolesIdsSeleccionados, $rolesConPermiso);

                // Roles para los que se debe desasignar el permiso
                $rolesParaRevocar = array_diff($rolesConPermiso, $rolesIdsSeleccionados);
                // Asignar el permiso a los nuevos roles seleccionados
                foreach ($rolesParaAsignar as $roleId) {
                    $role = Role::find($roleId);
                    if ($role) {
                        $role->givePermissionTo($permiso);
                    }
                }

                // Revocar el permiso de los roles que ya no están seleccionados
                foreach ($rolesParaRevocar as $roleId) {
                    $role = Role::find($roleId);
                    if ($role) {
                        $role->revokePermissionTo($permiso);
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
