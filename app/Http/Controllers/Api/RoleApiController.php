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
            ->with('permissions')
            ->allowedFields(['id', 'name', 'guard_name', 'created_at', 'updated_at'])
            ->allowedIncludes(['permissions'])
            ->allowedFilters(['name', 'guard_name'])
            ->allowedSorts(['name', 'guard_name', 'created_at', 'updated_at'])
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $roles,
            'message' => 'Roles recuperados con éxito.'
        ]);
    }


    /**
     * Store a newly created Role in storage.
     * POST /roles
     */
    public function store(Request $request)
    {
        try {
            $atributos = $request->input('data', []);

            if (empty($atributos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos no proporcionados en la solicitud.'
                ], 400);
            }

            DB::beginTransaction();

            $role = Role::create([
                'name' => $atributos['name'] ?? null,
                'guard_name' => $atributos['guard_name'] ?? 'web',
            ]);

            if (!empty($atributos['permissions']) && is_array($atributos['permissions'])) {
                $this->createAuditPermisos($role, $atributos['permissions']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
                'message' => 'Role guardado con éxito.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el Role: ' . $e->getMessage(),
                'trace' => $e->getTrace()
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
    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        try {
            $atributos = $request->input('data', []);

            DB::beginTransaction();

            $role->update([
                'name' => $atributos['name'] ?? $role->name,
                'guard_name' => $atributos['guard_name'] ?? $role->guard_name,
            ]);

            $this->updateAuditPermisos($role, $atributos['permissions'] ?? []);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'), // 🔹 Devuelve el rol con permisos
                'message' => 'Role actualizado con éxito.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el Role: ' . $e->getMessage(),
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

    public function permisos(Role $role)
    {
        return response()->json([
            'data' => $role->permissions
        ]);
    }

    private function createAuditPermisos($role, $permisos)
    {
        if (empty($permisos) || !is_array($permisos)) {
            $role->syncPermissions([]); // Si no hay permisos, limpia los existentes
            return;
        }

        $permisos_asignar = Permission::whereIn('id', $permisos)->pluck('id')->toArray();
        $role->syncPermissions($permisos_asignar);
    }

    private function updateAuditPermisos($role, $permisos)
    {
        if (empty($permisos) || !is_array($permisos)) {
            $role->syncPermissions([]);
            return;
        }

        $permisos_asignar = Permission::whereIn('id', $permisos)->pluck('id')->toArray();
        $role->syncPermissions($permisos_asignar);
    }


}
