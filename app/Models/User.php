<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Commission;
use Modules\Appointment\Models\Planning;
use Modules\Appointment\Models\Setting;
use Modules\Queue\Models\Agent;
use Modules\Queue\Models\Organization;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'specialty_id',
        'name',
        'professional_title',
        'email',
        'phone',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the full displayable name with title.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->professional_title) {
            return "{$this->professional_title} {$this->name}";
        }

        return $this->name;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Check if user is a practitioner.
     */
    public function isPractitioner(): bool
    {
        return $this->hasAnyRole(['professional', 'doctor', 'medecin', 'practitioner']);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin', 'administrator']);
    }

    /**
     * Check if user is an agent.
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if user is a secretary.
     */
    public function isSecretary(): bool
    {
        return $this->hasAnyRole(['secretary', 'secretaire']);
    }

    public function canonicalRole(): string
    {
        return self::canonicalizeRole($this->role);
    }

    public static function canonicalizeRole(?string $role): string
    {
        $normalized = Str::of((string) $role)->lower()->ascii()->replace('-', '_')->replace(' ', '_')->toString();

        return match ($normalized) {
            'doctor', 'dr', 'dentist', 'practitioner', 'medecin' => 'professional',
            'superadmin' => 'super_admin',
            'secretaire' => 'secretary',
            default => $normalized,
        };
    }

    public function roleTokens(): Collection
    {
        $primary = collect([
            self::canonicalizeRole($this->role),
            Str::of((string) $this->role)->lower()->ascii()->replace('-', '_')->replace(' ', '_')->toString(),
        ]);

        $roleCodes = $this->roles()
            ->pluck('code')
            ->map(fn (string $code) => self::canonicalizeRole($code));

        return $primary
            ->merge($roleCodes)
            ->filter()
            ->unique()
            ->values();
    }

    public function hasAnyRole(array|string $roles): bool
    {
        $targets = collect((array) $roles)
            ->map(fn (string $role) => self::canonicalizeRole($role))
            ->unique()
            ->values();

        return $this->roleTokens()->intersect($targets)->isNotEmpty();
    }

    public function hasRole(string $role): bool
    {
        return $this->hasAnyRole([$role]);
    }

    /**
     * Get the primary specialty.
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Get all specialties for this user (many-to-many).
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'practitioner_specialties')
            ->withPivot('is_primary', 'priority_order')
            ->withTimestamps();
    }

    /**
     * Get the primary specialty via pivot.
     */
    public function primarySpecialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    /**
     * Get all rooms assigned to this user.
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'practitioner_rooms')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the primary room.
     */
    public function primaryRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Get the agent profile.
     */
    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    /**
     * Get the organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all plannings (for professionals).
     */
    public function plannings(): HasMany
    {
        return $this->hasMany(Planning::class, 'professional_id');
    }

    /**
     * Get all appointments as professional.
     */
    public function professionalAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'professional_id');
    }

    /**
     * Get all appointments as secretary.
     */
    public function secretaryAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'secretary_id');
    }

    /**
     * Get all commissions.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'professional_id');
    }

    /**
     * Get appointment settings.
     */
    public function appointmentSetting(): HasOne
    {
        return $this->hasOne(Setting::class, 'professional_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withPivot('is_granted')
            ->withTimestamps();
    }

    public function accountingProfile(): HasOne
    {
        return $this->hasOne(PractitionerAccountingProfile::class, 'practitioner_id');
    }

    public function hasRoleCode(string $roleCode): bool
    {
        return $this->roles()->where('code', $roleCode)->exists();
    }

    public function grantRole(string $roleCode): void
    {
        $role = Role::where('code', $roleCode)->firstOrFail();
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function effectivePermissionCodes(): Collection
    {
        $rolePermissionCodes = $this->roles()
            ->with('permissions:id,code')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('code'));

        $directGrants = $this->permissions()
            ->wherePivot('is_granted', true)
            ->pluck('code');

        $directDenials = $this->permissions()
            ->wherePivot('is_granted', false)
            ->pluck('code')
            ->all();

        return $rolePermissionCodes
            ->merge($directGrants)
            ->unique()
            ->reject(fn (string $code) => in_array($code, $directDenials, true))
            ->values();
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->effectivePermissionCodes()->contains($permissionCode);
    }

    /**
     * Check if user can access a specific specialty.
     */
    public function canAccessSpecialty(Specialty|int $specialty): bool
    {
        $specialtyId = $specialty instanceof Specialty ? $specialty->id : $specialty;
        
        // Check direct specialty assignment
        if ($this->specialty_id === $specialtyId) {
            return true;
        }

        // Check many-to-many relationship
        return $this->specialties()->where('specialty_id', $specialtyId)->exists();
    }
}
