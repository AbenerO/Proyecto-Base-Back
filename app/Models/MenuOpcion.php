<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 *
 *
 * @property int $id
 * @property string $titulo
 * @property string $titulo_seccion
 * @property string $icono
 * @property string $ruta
 * @property int|null $orden
 * @property string $action
 * @property string $subject
 * @property int|null $option_id opcion padre
 * @property int|null $created_at
 * @property int|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereIcono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereRuta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereTitulo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereTituloSeccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MenuOpcion whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class MenuOpcion extends Model
{


    use HasFactory;

    protected $table = 'menu_opciones';


    protected $fillable =
        [
    'titulo',
    'titulo_seccion',
    'icono',
    'ruta',
    'orden',
    'action',
    'subject',
    'option_id'
];


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts =
        [
        'id' => 'integer',
        'titulo' => 'string',
        'titulo_seccion' => 'string',
        'icono' => 'string',
        'ruta' => 'string',
        'orden' => 'integer',
        'action' => 'string',
        'subject' => 'string',
        'option_id' => 'integer',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];



    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules =
    [
    'titulo' => 'string|max:255',
    'titulo_seccion' => 'string|max:255',
    'icono' => 'string|max:255',
    'ruta' => 'string|max:255',
    'orden' => 'nullable|integer',
    'action' => 'required|string|max:255',
    'subject' => 'required|string|max:255',
    'option_id' => 'nullable|integer',
];


    /**
     * Custom messages for validation
     *
     * @var array
     */
    public static $messages =[

    ];


    /**
     * Accessor for relationships
     *
     * @var array
     */
    public function children()
    {
     return $this->hasMany(MenuOpcion::class,'option_id','id');
    }

    public function scopePadres()
    {

        return $this->whereNull('option_id');

    }

}
