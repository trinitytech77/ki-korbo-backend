<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'language',
        'password',
        'role',
        'status',
        'phone_verified_at',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Role checking helpers
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER->value || $this->hasRole(UserRole::CUSTOMER->value);
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::AGENT->value || $this->hasRole(UserRole::AGENT->value);
    }

    public function isProfessional(): bool
    {
        return $this->role === UserRole::PROFESSIONAL->value || $this->hasRole(UserRole::PROFESSIONAL->value);
    }

    public function isOperations(): bool
    {
        return $this->role === UserRole::OPERATIONS->value || $this->hasRole(UserRole::OPERATIONS->value);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN->value || $this->hasRole(UserRole::ADMIN->value);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPER_ADMIN->value || $this->hasRole(UserRole::SUPER_ADMIN->value);
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin() || $this->isOperations();
    }

    /**
     * Relationship to customer profile.
     */
    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    /**
     * Relationship to agent profile.
     */
    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    /**
     * Tasks created by this customer.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    /**
     * Tasks assigned to this agent.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id');
    }

    /**
     * Documents uploaded by this user.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    /**
     * Payments made by this user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    /**
     * Reviews written by this customer.
     */
    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Reviews received by this agent.
     */
    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'agent_id');
    }
}
