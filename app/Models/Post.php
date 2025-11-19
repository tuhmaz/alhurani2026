<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Post extends Model
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
        'is_featured',  // منشور مميز - يتطلب صلاحيات خاصة
        'views',        // عدد المشاهدات - يُحدّث تلقائياً فقط
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'views' => 'integer'
    ];

    /**
     * الخصائص المضافة للنموذج
     */
    protected $appends = ['image_url'];

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

    /**
     * Accessor للحصول على رابط الصورة بشكل صحيح
     *
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        // التحقق من وجود قيمة في حقل image
        if (!$this->image || trim($this->image) === '') {
            return asset('assets/img/illustrations/default_news_image.jpg');
        }

        $image = trim($this->image);

        // إذا كانت الصورة عبارة عن URL كامل (خارجي)
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        // إذا كانت الصورة هي الصورة الافتراضية
        if ($image === 'posts/default_post_image.jpg') {
            return asset('assets/img/illustrations/default_news_image.jpg');
        }

        // إرجاع مسار الصورة المحفوظة باستخدام Storage::url()
        // Storage::url() يقوم تلقائياً بإضافة /storage/ إلى المسار
        return \Illuminate\Support\Facades\Storage::url($image);
    }
}
