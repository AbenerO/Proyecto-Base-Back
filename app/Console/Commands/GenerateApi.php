<?php

namespace App\Console\Commands;

use http\Cookie;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class GenerateApi extends Command
{
    protected $signature = 'make:api {table : Nombre de la tabla} {modelo : Nombre del modelo} {connection? : Nombre de la conexión (opcional)}';
    protected $description = 'Generar API incluyendo Modelo, Controlador y Requests';

    private $conexion;
    private $schema;
    private $nombreTabla;
    private $nombreModelo;
    private $variable;
    private $variablePlural;
    private $namespace = 'App\\Models';
    private $controllerNamespace = 'App\\Http\\Controllers\\Api';
    private $requestNamespace = 'App\\Http\\Requests\\Api';



    public function handle()
    {

        $this->conexion = $this->argument('connection') ?? config('database.default'); // Nombre de la conexión
        $this->schema = Schema::connection($this->conexion);    // Esquema de la base de datos
        $this->nombreTabla = $this->argument('table');  // Nombre de la tabla
        $this->nombreModelo = Str::studly($this->argument('modelo')); // Nombre del modelo
        $this->variable = strtolower($this->nombreModelo); // Nombre de la variable en singular (ej: `user`)
        $this->variablePlural = Str::kebab(Str::pluralStudly($this->nombreModelo)); // Nombre de recurso en kebab-case (ej: `users`)




        // Verificar si la tabla existe
        if (!$this->schema->hasTable($this->nombreTabla)) {
            $this->error("La tabla '{$this->nombreTabla}' no existe.");
            return;
        }

        $useSoftDeletes = $this->confirm('¿Requiere SoftDeletes?', false);

        /**
         * Generar contenido de los archivos
         */
        $columns = $this->schema->getColumnListing($this->nombreTabla);
        $fillable = $this->generateFillable($columns);
        $validationRules = $this->generateValidationRules();
        $relationships = $this->generateRelationships();
        $casts = $this->generateCasts();



        $this->generateFiles($fillable, $validationRules, $relationships, $useSoftDeletes, $casts);

        // Añadir la ruta
        $this->addRoute();

    }


    private function generateFiles($fillable, $validationRules, $relationships, $useSoftDeletes, $casts)
    {
        $tableNameM = ucfirst($this->nombreTabla); // Nombre de la tabla en mayúsculas

        $modelo = "{$this->nombreModelo}";
        $controlador = "{$this->nombreModelo}ApiController";
        $createRequest = "Create{$this->nombreModelo}ApiRequest";
        $updateRequest = "Update{$this->nombreModelo}ApiRequest";
        $seedername = "{$this->nombreTabla}TableSeeder";

        // Paths
        $modelPath = app_path("Models/{$modelo}.php");
        $controllerPath = app_path("Http/Controllers/Api/{$controlador}.php");
        $createRequestPath = app_path("Http/Requests/Api/{$createRequest}.php");
        $updateRequestPath = app_path("Http/Requests/Api/{$updateRequest}.php");
        $seederPath = "Database/seeders/{$seedername}.php";

        // Stubs
        $modelStub = File::get(base_path('stubs/api/model.stub'));
        $controllerStub = File::get(base_path('stubs/api/controller.stub'));
        $createRequestStub = File::get(base_path('stubs/api/request-create.stub'));
        $updateRequestStub = File::get(base_path('stubs/api/request-update.stub'));
        $seederStub = File::get(base_path('stubs/api/seeder.stub'));

        $useSoftDeletesCode = $useSoftDeletes ? 'use Illuminate\\Database\\Eloquent\\SoftDeletes;' : '';
        $softDeletesTrait = $useSoftDeletes ? 'use SoftDeletes;' : '';


        // Reemplazar placeholders
        $replacements = [
            '{{ modelNamespace }}' => $this->namespace,
            '{{ requestNamespace }}' => $this->requestNamespace,
            '{{ controllerNamespace }}' => $this->controllerNamespace,
            '{{ controlador }}' => $controlador,
            '{{ model }}' => $this->nombreModelo,
            '{{ variable }}' => $this->variable,
            '{{ variable_plural }}' => $this->variablePlural,
            '{{ tableNameM }}' => $tableNameM,
            '{{ createRequest }}' => $createRequest,
            '{{ updateRequest }}' => $updateRequest,
            '{{ fillable }}' => $fillable,
            '{{ validationRules }}' => $validationRules,
            '{{ relationships }}' => $relationships,
            '{{ tableName }}' => $this->nombreTabla,
            '{{ useSoftDeletes }}' => $useSoftDeletesCode,
            '{{ softDeletesTrait }}' => $softDeletesTrait,
            '{{ casts }}' => $casts,
            '{{ otro }}' => $fillable,
        ];


        // Generar archivos
        File::put($modelPath, str_replace(array_keys($replacements), $replacements, $modelStub));
        File::put($controllerPath, str_replace(array_keys($replacements), $replacements, $controllerStub));
        File::put($createRequestPath, str_replace(array_keys($replacements), $replacements, $createRequestStub));
        File::put($updateRequestPath, str_replace(array_keys($replacements), $replacements, $updateRequestStub));
        File::put($seederPath, str_replace(array_keys($replacements), $replacements, $seederStub));

        // Ejecutar el comando ide-helper:models para agregar anotaciones
        $this->callSilent('ide-helper:models', [
            'model' => ['App\\Models\\' . $modelo],
            '--reset' => true,
            '--write' => true,
            '--no-interaction' => true, // Evita cualquier interacción del usuario
        ]);

        $this->info("📁 Estructura de Archivos:");

        $this->line("  ├── <fg=blue>📂 app</>");
        $this->line("  │   ├── <fg=green>📂 Models</>");
        $this->line("  │   │   └── <fg=yellow>📌 {$modelo}.php</>");
        $this->line("  │   ├── <fg=green>📂 Http</>");
        $this->line("  │   │   ├── <fg=cyan>📂 Controllers</>");
        $this->line("  │   │   │   └── <fg=magenta>📌 {$controlador}.php</>");
        $this->line("  │   │   └── <fg=cyan>📂 Requests</>");
        $this->line("  │   │       ├── <fg=yellow>📌 {$createRequest}.php</>");
        $this->line("  │   │       └── <fg=yellow>📌 {$updateRequest}.php</>");
        $this->line("  ├── <fg=blue>📂 database</>");
        $this->line("  │   └── <fg=green>📂 seeders</>");
        $this->line("  │       └── <fg=magenta>📌 {$this->nombreTabla}TableSeeder.php</>");
        $this->line("  └── <fg=blue>📂 routes</>");
        $this->line("      └── <fg=cyan>📌 api.php</>");

        $this->newLine();


        $this->newLine();
        $url = url("api/{$this->nombreTabla}/getColumnas");
        $this->info("🌐 Ruta para obtener columnas desde el FrontEnd:");
        $this->line("🔗 <fg=blue>{$url}</>");

        $this->newLine();

// Copiar la URL automáticamente en el portapapeles (según el SO)
        if (PHP_OS_FAMILY === 'Darwin') {
            exec("echo '{$url}' | pbcopy"); // MacOS
            $this->info("📋 URL copiada al portapapeles (MacOS)");
        } elseif (PHP_OS_FAMILY === 'Windows') {
            exec("echo {$url} | clip"); // Windows
            $this->info("📋 URL copiada al portapapeles (Windows)");
        } else {
            $this->info("⚠️ Copia manual: Usa Ctrl+C para copiar la URL.");
        }

        $this->newLine();
        $this->info("🚀 ¡Generación de archivos completada con éxito!");


    }

    /**
     * Añadir ruta al archivo de rutas
     *
     * @param string $name
     * @return void
     */
    private function addRoute()
    {
        $routePath = base_path('routes/api.php');
        $resourceName = $this->variablePlural; // Nombre de recurso en kebab-case (ej: `users`)
        $controllerName = "{$this->nombreModelo}ApiController";
        $controllerNamespace = 'App\\Http\\Controllers\\Api\\';

        $rutaParaObtenerColumnas = "Route::get('{$resourceName}/getColumnas', [$controllerNamespace{$controllerName}::class, 'getColumnas'])->name('{$resourceName}.getColumnas');";

        File::append($routePath, PHP_EOL . $rutaParaObtenerColumnas . PHP_EOL);

        $rutaDeRecursos = "Route::apiResource('{$resourceName}', $controllerNamespace{$controllerName}::class);";

        if (!File::exists($routePath)) {
            $this->error("El archivo de rutas 'routes/api.php' no existe.");
            return;
        }

        $content = File::get($routePath);

        // Verificar si la ruta ya existe
        if (strpos($content, $rutaDeRecursos) !== false) {
            $this->warn("La ruta para '{$resourceName}' ya existe en el archivo de rutas.");
            return;
        }

        // Añadir la nueva ruta al final del archivo
        File::append($routePath, PHP_EOL . $rutaDeRecursos . PHP_EOL);

    }


    private function generateFillable(array $columns)
    {
        // Excluir columnas que no son relevantes para $fillable
        $excluded = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $fillable = array_diff($columns, $excluded);

        return '[' . PHP_EOL . '    \'' . implode("',\n    '", $fillable) . '\'' . PHP_EOL . ']';
    }

    private function generateValidationRules(): string
    {
        // Obtener información de las columnas
        $columns = $this->schema->getColumns($this->nombreTabla);
        $indexes = $this->schema->getIndexes($this->nombreTabla);


        $uniqueColumns = array_filter($indexes, function ($index) {
            return $index['unique']; // Filtrar los índices únicos
        });

        $rules = [];

        foreach ($columns as $column) {
            $columnName = $column['name'];


            // Excluir columnas que no son relevantes para las reglas de validación
            if (in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $typeName = $column['type_name'];
            $nullable = $column['nullable'];
            $typeDetails = $column['type']; // Contiene información como nvarchar(255)

            $columnRules = [];

            // Requerido o nullable
            $columnRules[] = $nullable ? 'nullable' : 'required';

            // Validar tipo de dato
            switch ($typeName) {
                case 'nvarchar':
                case 'varchar':
                case 'char':
                case 'text':
                    $columnRules[] = 'string';

                    // Extraer longitud máxima si está disponible (por ejemplo, nvarchar(255))
                    if (preg_match('/\((\d+)\)/', $typeDetails, $matches)) {
                        $maxLength = $matches[1];
                        $columnRules[] = "max:$maxLength";
                    }
                    break;

                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $columnRules[] = 'integer';
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $columnRules[] = 'numeric';
                    break;

                case 'date':
                case 'datetime':
                case 'timestamp':
                    $columnRules[] = 'date';
                    break;

                case 'bit':
                case 'boolean':
                    $columnRules[] = 'boolean';
                    break;

                default:
                    $columnRules[] = 'string'; // Tipo genérico por defecto
            }

            // Validar unicidad
            foreach ($uniqueColumns as $index) {
                if (in_array($columnName, $index['columns'])) {
                    $columnRules[] = "unique:{$this->nombreTabla},{$columnName}";
                    break;
                }
            }

            // Agregar las reglas al array principal
            $rules[$columnName] = implode('|', $columnRules);
        }

        // Formatear las reglas en un array PHP
        $formattedRules = '[' . PHP_EOL;
        foreach ($rules as $field => $rule) {
            $formattedRules .= "    '$field' => '$rule'," . PHP_EOL;
        }
        $formattedRules .= ']';

        return $formattedRules;
    }


    private function generateCasts(): string
    {
        $columns = $this->schema->getColumns($this->nombreTabla);
        $casts = [];

        foreach ($columns as $column) {
            $columnName = $column['name'];
            $typeName = $column['type_name'];

            switch ($typeName) {
                case 'int':
                case 'bigint':
                case 'smallint':
                case 'tinyint':
                    $casts[$columnName] = 'integer';
                    break;

                case 'decimal':
                case 'float':
                case 'double':
                    $casts[$columnName] = 'float';
                    break;

                case 'bit':
                case 'boolean':
                    $casts[$columnName] = 'boolean';
                    break;

                case 'date':
                    $casts[$columnName] = 'date';
                    break;
                case 'datetime':
                    $casts[$columnName] = 'datetime';
                    break;
                case 'timestamp':
                    $casts[$columnName] = 'timestamp';
                    break;

                default:
                    $casts[$columnName] = 'string';
            }
        }

        $formattedCasts = '[' . PHP_EOL;
        foreach ($casts as $field => $cast) {
            $formattedCasts .= "        '$field' => '$cast'," . PHP_EOL;
        }
        $formattedCasts .= '    ]';

        return $formattedCasts;

    }


    private function generateRelationships(): string
    {
        $foreignKeys = $this->schema->getForeignKeys($this->nombreTabla);
        $relationships = [];

        foreach ($foreignKeys as $foreignKey) {
        $columnName = $foreignKey['columns'][0]; // Columna de la tabla actual
        $referencedTable = $foreignKey['foreign_table']; // Tabla referenciada
        $referencedColumn = $foreignKey['foreign_columns'][0]; // Columna referenciada


        // Determinar el tipo de relación
        $relationType = $this->determineRelationshipType($foreignKey, $this->nombreTabla);
        $relatedModel = Str::studly(Str::singular($referencedTable)); // Modelo relacionado
        $functionName = ($relationType === 'hasMany' || $relationType === 'belongsToMany')
        ? Str::camel(Str::plural($referencedTable))
        : Str::camel(Str::singular($referencedTable));

        // Generar el método de relación
        $relationships[] = <<<EOT
        public function {$functionName}()
            {
            return \$this->{$relationType}({$relatedModel}::class,'{$columnName}','{$referencedColumn}');
            }
        EOT;
        }

        return implode("\n\n    ", $relationships);
    }

    private function determineRelationshipType(array $foreignKey, string $nombreTabla): string
    {
        $columnName = $foreignKey['columns'][0];
        $referencedTable = $foreignKey['foreign_table'];
        $referencedColumn = $foreignKey['foreign_columns'][0];

        // belongsTo
        if ($nombreTabla !== $referencedTable) {
            return 'belongsTo';
        }

        // hasMany
        if ($nombreTabla === $referencedTable && $referencedColumn === 'id') {
            return 'hasMany';
        }

        // hasOne (si la clave foránea es única)
        if ($this->isUniqueForeignKey($nombreTabla, $columnName)) {
            return 'hasOne';
        }

        // belongsToMany (tabla pivote)
        if ($this->isPivotTable($nombreTabla)) {
            return 'belongsToMany';
        }

        // Polimórficas
        if ($this->isPolymorphic($foreignKey)) {
            return 'morphTo';
        }

        // Por defecto
        return '';
    }


    private function isPivotTable(string $nombreTabla): bool
    {
        $foreignKeys = $this->schema->getForeignKeys($nombreTabla);

        // Una tabla pivote generalmente tiene exactamente 2 claves foráneas
        return count($foreignKeys) === 2 && $this->schema->getColumnCount($nombreTabla) <= 3;
    }


    private function isUniqueForeignKey(string $nombreTabla, string $nombreColumna): bool
    {
        $indexes = $this->schema->getIndexes($nombreTabla);

        foreach ($indexes as $index) {
            if (in_array($nombreColumna, $index['columns']) && $index['unique']) {
                return true;
            }
        }

        return false;
    }


    private function isPolymorphic(array $columns): bool
    {
        return in_array('morph_id', $columns) && in_array('morph_type', $columns);
    }

//






}
