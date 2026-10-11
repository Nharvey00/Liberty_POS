<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'business_name',
        'tin_number',
        'customer_type',
        'phone',
        'address',
        'default_discount_type',
        'default_discount_percentage',
        'default_discount_ref_name',
        'default_discount_ref_id'
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

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        
        // Handle cross-database concatenation for full names
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $fullName = ($driver === 'sqlite' || $driver === 'pgsql') 
            ? "first_name || ' ' || last_name" 
            : "CONCAT(first_name, ' ', last_name)";

        return $query->where(function ($q) use ($search, $like, $fullName) {
            $q->where(\Illuminate\Support\Facades\DB::raw($fullName), $like, "%{$search}%")
              ->orWhere('first_name', $like, "%{$search}%")
              ->orWhere('last_name', $like, "%{$search}%")
              ->orWhere('business_name', $like, "%{$search}%")
              ->orWhere('phone', $like, "%{$search}%");
        });
    }

    public function creditAccount()
    {
        return $this->hasOne(CreditAccount::class);
    }
}
