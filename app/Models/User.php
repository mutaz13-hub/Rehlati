<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravolt\Avatar\Facade;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = ['api'];

    public const MORPH_KEY = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone_number',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'nationality',
        'nationality_category',
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
            'phone_number_verified_at' => 'datetime',
            'password' => 'hashed',
            'nationality_category' => 'string',
        ];
    }

    private const EXPAT_NATIONALITIES = [
        'LB', 'JO', 'EG', 'IQ', 'SA', 'AE', 'KW', 'BH', 'QA', 'OM', 'YE',
        'PS', 'SD', 'TN', 'DZ', 'MA', 'LY', 'MR', 'SO', 'DJ', 'KM',
    ];

    public function getResolvedNationalityCategoryAttribute(): string
    {
        if ($this->nationality_category && in_array($this->nationality_category, ['syrian', 'expat', 'foreigner'], true)) {
            return $this->nationality_category;
        }

        $nationality = strtoupper($this->nationality ?? '');

        if ($nationality === 'SY') {
            return 'syrian';
        }

        if (in_array($nationality, self::EXPAT_NATIONALITIES, true)) {
            return 'expat';
        }

        return 'foreigner';
    }

    /**
     * Get the devices for the user.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Get the social accounts for the user.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function bannedUser(): HasOne
    {
        return $this->hasOne(BannedUser::class);
    }

    public function preferredLocale(): string
    {
        return cache()->get('lang_for_user: '.$this->id, app()->getLocale());
    }

    public static function generateUniqueUsername(): string
    {
        do {
            $username = Str::lower(Str::random(12));
        } while (static::where('username', $username)->exists());

        return $username;
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function getAvatarAttribute(): string
    {
        $path = 'avatars/'.md5($this->name ?? 'unknown').'.png';

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory('avatars');
            Facade::create($this->name)->save(Storage::disk('public')->path($path));
        }

        return Storage::disk('public')->url($path);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
