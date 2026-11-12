<?php

namespace App\Models;

use App\Models\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A client's favourites in one gallery (proofing). Created on their first
 * heart and remembered by a cookie; sent to the photographer with an
 * optional name, email and note.
 */
class FavouriteList extends Model
{
    use UsesUuid;

    /** Most photos one client can pick, and most lists per gallery. */
    public const MAX_PHOTOS = 2000;
    public const MAX_LISTS_PER_COLLECTION = 1000;

    protected $fillable = ['collection_id', 'name', 'email', 'note', 'submitted_at'];

    protected $casts = ['submitted_at' => 'datetime'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'favourite_list_photo')->withPivot('created_at');
    }

    /** "Sam Client", else their email, else "A client". */
    public function clientLabel(): string
    {
        return $this->name ?: ($this->email ?: 'A client');
    }

    /** Filenames for Lightroom: the camera's names where we have them. */
    public function filenames(): array
    {
        return $this->photos()->orderBy('photos.created_at')->orderBy('photos.url')->get()
            ->map(fn (Photo $photo) => $photo->displayFilename())
            ->all();
    }
}
