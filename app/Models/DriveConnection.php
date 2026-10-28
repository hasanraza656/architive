<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A connected Google account whose Drive holds order files. The refresh token is stored encrypted (it needs APP_KEY to be read). */
class DriveConnection extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['refresh_token'];

    protected $casts = [
        'refresh_token' => 'encrypted',
        'folder_map' => 'array',
        'is_active' => 'boolean',
        'moving_files' => 'boolean',
        'quota_checked_at' => 'datetime',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class, 'drive_connection_id');
    }

    /** The account that receives new files, or null when Google Drive is not connected. */
    public static function active(): ?self
    {
        return static::where('is_active', true)->where('status', 'connected')->first();
    }

    public function folderUrl(): ?string
    {
        return $this->root_folder_id ? 'https://drive.google.com/drive/folders/' . $this->root_folder_id : null;
    }
}
