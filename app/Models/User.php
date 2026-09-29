<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
     * Backward-compatible name attribute accessor and mutator.
     */
    protected function name(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
                $this->suffix,
            ]))),
            set: function (?string $value) {
                if (empty($value)) {
                    return [
                        'first_name' => '',
                        'middle_name' => null,
                        'last_name' => '',
                        'suffix' => null,
                    ];
                }
                $suffixes = ['Jr.', 'Jr', 'Sr.', 'Sr', 'II', 'III', 'IV', 'V'];
                $parts = array_values(array_filter(explode(' ', trim($value))));
                $suffix = null;
                if (count($parts) > 1 && in_array(end($parts), $suffixes, true)) {
                    $suffix = array_pop($parts);
                }
                if (count($parts) === 1) {
                    return [
                        'first_name' => $parts[0],
                        'middle_name' => null,
                        'last_name' => '',
                        'suffix' => $suffix,
                    ];
                }
                if (count($parts) === 2) {
                    return [
                        'first_name' => $parts[0],
                        'middle_name' => null,
                        'last_name' => $parts[1],
                        'suffix' => $suffix,
                    ];
                }
                $lastName = array_pop($parts);
                $firstName = array_shift($parts);
                $middleName = implode(' ', $parts);
                return [
                    'first_name' => $firstName,
                    'middle_name' => $middleName ?: null,
                    'last_name' => $lastName,
                    'suffix' => $suffix,
                ];
            }
        );
    }

    /**
     * Check if user is an Owner (Role Level 3).
     */
    public function isOwner(): bool
    {
        return (int)$this->role_id === 3 || $this->role?->role_name === 'Level 3';
    }

    /**
     * Check if user is a Manager or Owner (Role Level 2 or 3).
     */
    public function isManagerOrOwner(): bool
    {
        return in_array((int)$this->role_id, [2, 3]) || in_array($this->role?->role_name, ['Level 2', 'Level 3']);
    }

    /**
     * Check if user is a Cashier (Role Level 1).
     */
    public function isCashier(): bool
    {
        return (int)$this->role_id === 1 || $this->role?->role_name === 'Level 1';
    }

    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}