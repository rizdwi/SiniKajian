<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

#[Fillable(['name', 'email', 'password', 'provider', 'provider_id', 'avatar', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    protected $fillable = ['name', 'email', 'password', 'provider', 'provider_id', 'avatar'];
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return ($this->role ?? '') === 'admin' || strtolower($this->email ?? '') === 'rizkidwisandy@gmail.com';
    }

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

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedKajians(): BelongsToMany
    {
        return $this->belongsToMany(Kajian::class, 'bookmarks', 'user_id', 'kajian_id')->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function submittedKajians(): HasMany
    {
        return $this->hasMany(Kajian::class, 'user_id');
    }

    public function majelisFollows(): HasMany
    {
        return $this->hasMany(MajelisFollow::class);
    }
}
