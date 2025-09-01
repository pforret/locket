<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Tags\HasTags;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $published_at
 * @property string $title
 * @property string $url
 * @property string|null $image
 * @property string|null $screenshot
 * @property string $author
 * @property string $source
 * @property string $summary
 * @property string|null $content
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereScreenshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUrl($value)
 * @mixin \Eloquent
 */
class Document extends Model
{
    use HasTags;

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $fillable = [
        'published_at',
        'title',
        'url',
        'image',
        'screenshot',
        'author',
        'source',
        'summary',
        'content',
    ];
}
