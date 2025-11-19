<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class News extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'posts';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'content',
        'image',
        'alt',
        'country',
        'keywords',
        'meta_description'
    ];

    /**
     * الحقول المحمية من Mass Assignment لأسباب أمنية
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'author_id',    // المؤلف - يُحدد من المستخدم الحالي فقط
        'is_active',    // حالة التفعيل - يتطلب صلاحيات خاصة
        'is_featured',  // خبر مميز - يتطلب صلاحيات خاصة
        'views',        // عدد المشاهدات - يُحدّث تلقائياً فقط
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'views' => 'integer'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function(string $eventName) {
                $action = match($eventName) {
                    'created' => 'إضافة',
                    'updated' => 'تحديث',
                    'deleted' => 'حذف',
                    default => $eventName
                };
                return "تم {$action} منشور: {$this->title}";
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): MorphMany
    {
        $database = session('database', 'jo');

        return $this->morphMany(Comment::class, 'commentable')
                    ->where('database', $database)
                    ->with(['user', 'reactions' => function($q) use ($database) {
                        $q->where('database', $database);
                    }]);
    }

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class, 'post_keyword', 'post_id', 'keyword_id')
                    ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(File::class, 'post_id');
    }

    public function getKeywordsArrayAttribute()
    {
        return $this->keywords instanceof \Illuminate\Database\Eloquent\Collection
            ? $this->keywords->pluck('keyword')->toArray()
            : [];
    }

    public function getKeywordsStringAttribute()
    {
        return !empty($this->keywords_array)
            ? implode(',', $this->keywords_array)
            : '';
    }
}
