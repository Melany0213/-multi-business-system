<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\AccessScheduler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Business extends Model
{
    use LogsActivity;

    protected $fillable = [
        'account_id',
        'rubro_id',
        'nombre',
        'tipo',
        'estado',
        'moneda',
        'zona_horaria',
        'configuracion',
        'logo_path',
        'color_primario',
        'color_secundario',
        'salario_monto_fijo',
        'salario_porcentaje',
        'salario_base_porcentaje',
        'admin_puede_configurar_salario',
    ];

    protected $casts = [
        'configuracion' => 'array',
        'salario_monto_fijo' => 'decimal:2',
        'salario_porcentaje' => 'decimal:2',
        'admin_puede_configurar_salario' => 'boolean',
    ];

    protected $attributes = [
        'estado' => 'activo',
        'moneda' => 'USD',
        'zona_horaria' => 'UTC',
        'salario_monto_fijo' => 0,
        'salario_porcentaje' => 0,
        'salario_base_porcentaje' => 'venta',
        'admin_puede_configurar_salario' => false,
    ];

    /**
     * Sobre qué puede aplicarse el porcentaje de salario — el dueño elige
     * (ver Business::calcularSalario()).
     */
    public const BASES_PORCENTAJE_SALARIO = [
        'venta' => 'Venta del turno',
        'utilidad' => 'Utilidad del turno',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(Access::class);
    }

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class);
    }

    /**
     * Productos del catálogo de la cuenta que este negocio ofrece, con su
     * propio precio/costo (ver [[modelo-datos-multinegocio]] — catálogo
     * compartido por cuenta, precio/costo y stock por negocio/almacén).
     */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'business_producto')
            ->withPivot('precio', 'costo', 'estado')
            ->withTimestamps();
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(Turno::class);
    }

    public function isActive(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Salario del cajero para un turno con estos totales, según lo que el
     * dueño haya configurado para este negocio — monto fijo y porcentaje se
     * pueden combinar (ej. "$5 fijos + 3% de la venta"); no hay una fórmula
     * única del sistema (ver memoria del proyecto y [[referencia-zeta-pos]]).
     */
    public function calcularSalario(float $totalVenta, float $totalUtilidad): float
    {
        $base = $this->salario_base_porcentaje === 'utilidad' ? $totalUtilidad : $totalVenta;
        $variable = $base * ((float) $this->salario_porcentaje / 100);

        return round((float) $this->salario_monto_fijo + $variable, 2);
    }

    /**
     * ¿Puede este usuario ajustar la configuración de salario del negocio?
     * Siempre el dueño de la cuenta; el administrador solo si el dueño se lo
     * delegó explícitamente (admin_puede_configurar_salario).
     */
    public function puedeConfigurarSalario(User $user, AccessScheduler $scheduler): bool
    {
        if ($user->is_super_admin_sistema || $this->account->owner_user_id === $user->id) {
            return true;
        }

        return $this->admin_puede_configurar_salario
            && $scheduler->hasPermission($user, $this, 'usuarios.gestionar');
    }

    protected function activityAccountId(): ?int
    {
        return $this->account_id;
    }

    protected function activityBusinessId(): ?int
    {
        return $this->id;
    }

    protected function activitySelfReferences(): array
    {
        return ['business_id'];
    }

    protected function activityDescription(string $action): string
    {
        return "Negocio \"{$this->nombre}\" ".$this->activityVerbo($action);
    }

    protected static function booted(): void
    {
        // El dueño de la cuenta siempre tiene acceso a cada negocio que crea,
        // con el rol de mayor privilegio — sin esto, no vería sus propios
        // negocios en "Mis negocios" (el dueño no es un caso especial en el
        // sistema de Accesos, es simplemente el primer Acceso que se crea).
        static::created(function (Business $business) {
            $role = Role::where('name', 'super_admin_negocio')->where('guard_name', 'web')->first();

            if (! $role) {
                return;
            }

            Access::create([
                'user_id' => $business->account->owner_user_id,
                'business_id' => $business->id,
                'role_id' => $role->id,
                'tipo_vigencia' => 'fija',
                'estado' => 'activo',
                'created_by' => $business->account->owner_user_id,
            ]);
        });
    }
}
