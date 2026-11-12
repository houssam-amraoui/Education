<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Ability;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function isAdministrator(): bool
    {
        return $this->roles()->where('key', Role::ADMINISTRATOR)->exists();
    }

    public function hasAbility(Ability $ability): bool
    {
        return $this->abilityQuery()->where('ability', $ability->value)->exists();
    }

    public function canGrantAbilities(iterable $abilities): bool
    {
        $granted = $this->abilityQuery()->distinct()->pluck('ability')->all();

        foreach ($abilities as $ability) {
            if (! in_array($ability, $granted, true)) {
                return false;
            }
        }

        return true;
    }

    public function abilityQuery(): Builder
    {
        return RoleAbility::whereIn('role_id', $this->roles()->select('roles.id'));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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
}
