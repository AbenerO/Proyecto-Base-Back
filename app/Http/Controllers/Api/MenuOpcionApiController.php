<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AppBaseController;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Api\CreateMenuOpcionApiRequest;
use App\Http\Requests\Api\UpdateMenuOpcionApiRequest;
use App\Models\MenuOpcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class MenuOpcionApiController
 */
class MenuOpcionApiController extends AppbaseController
{

    /**
     * //     * @return array
     * //     */
    //    public static function middleware(): array
    //    {
    //        return [
    //            new Middleware('abilities:ver MenuOpcion', only: ['index', 'show']),
    //            new Middleware('abilities:crear MenuOpcion', only: ['store']),
    //            new Middleware('abilities:editar MenuOpcion', only: ['update']),
    //            new Middleware('abilities:eliminar MenuOpcion', only: ['destroy']),
    //        ];
    //    }

    /**
     * Display a listing of the Menu_opciones.
     * GET|HEAD /menu-opcions
     */
    public function index(Request $request): JsonResponse
    {
        $menuOpcions = QueryBuilder::for(MenuOpcion::class)
            ->with([])
            ->allowedFilters([
                'titulo',
                'titulo_seccion',
                'icono',
                'ruta',
                'orden',
                'action',
                'subject',
                'option_id'
            ])
            ->allowedSorts([
                'titulo',
                'titulo_seccion',
                'icono',
                'ruta',
                'orden',
                'action',
                'subject',
                'option_id'
            ])
            ->defaultSort('-id') // Ordenar por defecto por fecha descendente
                ->Padres()
            ->with('children')
            ->orderBy('orden', 'asc')
            ->paginate($request->get('per_page', 10));

        return $this->sendResponse($menuOpcions->toArray(), 'menu-opcions recuperados con éxito.');
    }


    /**
     * Store a newly created MenuOpcion in storage.
     * POST /menu-opcions
     */
    public function store(CreateMenuOpcionApiRequest $request): JsonResponse
    {
        $input = $request->all();

        MenuOpcion::create($input);

        $opcionesMenu = MenuOpcion::Padres()
            ->with('children')
            ->orderBy('orden', 'asc')
            ->get();

        return $this->sendResponse($opcionesMenu->toArray(), 'MenuOpcion creado con éxito.');
    }


    /**
     * Display the specified MenuOpcion.
     * GET|HEAD /menu-opcions/{id}
     */
    public function show(MenuOpcion $menu_opcion)
    {
        return $this->sendResponse($menu_opcion->toArray(), 'MenuOpcion recuperado con éxito.');
    }


    /**
     * Update the specified MenuOpcion in storage.
     * PUT/PATCH /menu-opcions/{id}
     */
    public function update(UpdateMenuOpcionApiRequest $request, $id): JsonResponse
    {
        $menuopcion = MenuOpcion::findOrFail($id);
        $menuopcion->update($request->validated());

        $opcionesMenu = MenuOpcion::Padres()
            ->with('children')
            ->orderBy('orden', 'asc')
            ->get();

        return $this->sendResponse($opcionesMenu->toArray(), 'MenuOpcion actualizado con éxito.');
    }

    /**
     * Remove the specified MenuOpcion from storage.
     * DELETE /menu-opcions/{id}
     */
    public function destroy(MenuOpcion $menu_opcion): JsonResponse
    {
        $menu_opcion->delete();
        return $this->sendResponse(null, 'MenuOpcion eliminado con éxito.');
    }

    /**
     * Get columns of the table
     * GET /menu-opcions/columns
     */
    public function getColumnas(): JsonResponse
    {

        $columns = Schema::getColumnListing((new MenuOpcion)->getTable());

        $columnasSinTimesTamps = array_diff($columns, ['id', 'created_at', 'updated_at', 'deleted_at']);

        $nombreDeTabla = (new MenuOpcion)->getTable();

        $data = [
            'columns' => array_values($columnasSinTimesTamps),
            'nombreDelModelo' => 'MenuOpcion',
            'nombreDeTabla' => $nombreDeTabla,
            'ruta' => 'api/' . $nombreDeTabla,
        ];

        return $this->sendResponse($data, 'Columnas de la tabla menu_opciones recuperadas con éxito.');
    }

    public function actualizarOrden(Request $request)
    {

        $opciones = $request->opciones;

        foreach ($opciones as $index => $menuOpcion) {

            MenuOpcion::where('id', $menuOpcion['id'])->update(['orden' => $index]);

        }

        $opcionesMenu = MenuOpcion::Padres()
            ->with('children')
            ->orderBy('orden', 'asc')
            ->get();

        return $this->sendResponse($opcionesMenu, 'Orden actualizado con éxito.');


    }

}
