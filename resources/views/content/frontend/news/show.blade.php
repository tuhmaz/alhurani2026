  @extends('layouts/layoutFront')

  @php
    $configData = Helper::appClasses();
    use Illuminate\Support\Str;

    // Normalize variables: allow controller to pass either $news or $post
    if (!isset($post) && isset($news)) {
      $post = $news;
    }

    // Ensure database is set
    $database = $database ?? session('database', 'default_database');
    // Get the author from the main database (jo)
    $author = isset($post) ? \App\Models\User::on('jo')->find($post->author_id) : null;
    if (isset($post)) {
      $post->setRelation('author', $author);
    }

    // Social locale map like articles page
    $localeMap = ['sa' => 'ar_SA', 'eg' => 'ar_EG', 'ps' => 'ar_PS', 'jo' => 'ar_JO'];
    $ogLocale = $localeMap[$database] ?? 'ar_JO';

    // Normalize twitter handle like articles page
    $configuredTwitter = trim((string) config('settings.social_twitter'));
    $twitterHandle = $configuredTwitter;
    if (Str::startsWith($configuredTwitter, ['http://twitter.com/', 'https://twitter.com/'])) {
      $twitterHandle = '@' . ltrim(parse_url($configuredTwitter, PHP_URL_PATH), '/');
    }
    if ($twitterHandle && $twitterHandle[0] !== '@') {
      $twitterHandle = '@' . $twitterHandle;
    }

    // Compute main image URL with https
    $rawImage = method_exists($post ?? null, 'imageUrl')
      ? $post->imageUrl()
      : (($post->image ?? null) ? asset('storage/' . ltrim($post->image, '/')) : asset('assets/img/front-pages/icons/articles_default_image.webp'));
    $ogImage = $rawImage;
    $ogImageSecure = preg_replace('#^http://#i', 'https://', $ogImage);

    // Prepare keywords array once
    $__keywordsArray = is_string($post->keywords ?? null) ? array_values(array_filter(array_map('trim', explode(',', $post->keywords)))) : [];

    // Compute related posts by category and keywords; fallback to random
    $relatedQuery = \App\Models\Post::on($database)
      ->where('id', '!=', $post->id ?? 0);
    if (!empty($post->category_id)) {
      $relatedQuery->where('category_id', $post->category_id);
    }
    if (!empty($__keywordsArray)) {
      $relatedQuery->where(function($q) use ($__keywordsArray) {
        foreach ($__keywordsArray as $kw) {
          $q->orWhere('title', 'like', "%$kw%")
            ->orWhere('keywords', 'like', "%$kw%");
        }
      });
    }
    $relatedNews = $relatedQuery->latest()->limit(5)->get();
    if ($relatedNews->isEmpty()) {
      $relatedNews = \App\Models\Post::on($database)->inRandomOrder()->limit(5)->get();
    }
  @endphp

  @section('title', $post->title)
  @section('meta_title', $post->title . ' - ' . ($post->meta_title ?? config('settings.meta_title')))

  @section('meta')
  @php
    $rawMeta = $post->meta_description ?? $post->excerpt ?? $post->body ?? '';
    $plain = strip_tags($rawMeta);
    $decoded = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $decoded = preg_replace('/\x{00A0}|&nbsp;/u', ' ', $decoded);
    $metaDescClean = trim(preg_replace('/\s+/u', ' ', $decoded));
    $keywordsArray = is_string($post->keywords) ? array_values(array_filter(array_map('trim', explode(',', $post->keywords)))) : [];
  @endphp
  <meta name="keywords" content="{{ implode(',', $keywordsArray) }}">
  <meta name="description" content="{{ Str::limit($metaDescClean, 160, '') }}">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="{{ url()->current() }}">

  <!-- Open Graph -->
  <meta property="og:title" content="{{ $post->title }}" />
  <meta property="og:description" content="{{ Str::limit($metaDescClean, 200, '') }}" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:image" content="{{ $ogImage }}" />
  <meta property="og:image:secure_url" content="{{ $ogImageSecure }}" />
  <meta property="og:image:alt" content="{{ $post->title }}" />
  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:locale" content="{{ $ogLocale }}" />
  <meta property="og:site_name" content="{{ config('settings.site_name', 'site_name') }}" />
  <meta property="article:published_time" content="{{ optional($post->created_at)->toIso8601String() }}" />
  <meta property="article:modified_time" content="{{ optional($post->updated_at)->toIso8601String() }}" />
  <meta property="article:section" content="{{ optional($post->category)->name }}" />
  @foreach($keywordsArray as $kw)
    <meta property="article:tag" content="{{ $kw }}" />
  @endforeach

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $post->title }}" />
  <meta name="twitter:description" content="{{ Str::limit($metaDescClean, 200, '') }}" />
  <meta name="twitter:image" content="{{ $ogImageSecure }}" />
  <meta name="twitter:image:alt" content="{{ $post->title }}" />
  @if(!empty($twitterHandle))
    <meta name="twitter:site" content="{{ $twitterHandle }}" />
  @endif
  @if (!empty($author?->twitter_handle))
    <meta name="twitter:creator" content="{{ Str::startsWith($author->twitter_handle, '@') ? $author->twitter_handle : '@'.$author->twitter_handle }}" />
  @endif

  <meta property="article:author" content="{{ $author->name ?? 'Unknown' }}" />
  <link rel="author" href="{{ $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#' }}" />
  @endsection

  @section('content')
  <section class="section-py first-section-pt help-center-header position-relative overflow-hidden" style="background: linear-gradient(226deg, #202c45 0%, #286aad 100%);">
      <!-- Background Pattern -->
      <div class="position-absolute w-100 h-100" style="background: linear-gradient(45deg, rgba(40, 106, 173, 0.1), transparent); top: 0; left: 0;"></div>

      <!-- Animated Shapes -->
      <div class="position-absolute" style="width: 300px; height: 300px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); top: -150px; right: -150px; border-radius: 50%;"></div>
      <div class="position-absolute" style="width: 200px; height: 200px; background: radial-gradient(circle, rgba(40, 106, 173, 0.1) 0%, transparent 70%); bottom: -100px; left: -100px; border-radius: 50%;"></div>

      <div class="container position-relative">
          <div class="row justify-content-center">
              <div class="col-12 col-lg-8 text-center">
                  <!-- Main Title with Animation -->
                  <h1 class="display-6 text-white mb-2" style="text-shadow: 0 2px 4px rgba(0,0,0,0.1);">{{ $post->title }}</h1>
                  <p class="text-center text-white px-4 mb-0" style="font-size: medium;">
                    <span class="me-2"><i class="ti tabler-user"></i> {{ $author->name ?? __('Unknown') }}</span>
                    <span class="me-2"><i class="ti tabler-calendar"></i> {{ $post->created_at->format('d M Y') }}</span>
                    @if($post->category)
                      <span class="me-2"><i class="ti tabler-tag"></i> {{ $post->category->name }}</span>
                    @endif
                  </p>
                  @guest
                  <div class="d-flex justify-content-center gap-3 mt-3">
                      <a href="{{ route('login') }}" class="btn btn-primary btn-lg" style="background: linear-gradient(45deg, #3498db, #2980b9); border: none; box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);">
                          <i class="post-icon ti tabler-user-plus me-2"></i>{{ __('Get Started') }}
                      </a>
                      <a href="#features" class="btn btn-outline-light btn-lg">
                          <i class="post-icon ti tabler-info-circle me-2"></i>{{ __('Learn More') }}
                      </a>
                  </div>
                  @endguest
              </div>
          </div>
      </div>

      <!-- Wave Shape Divider -->
      <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="height: 60px;">
          <svg viewBox="0 0 1200 120" preserveAspectRatio="none" style="width: 100%; height: 60px; transform: rotate(180deg);">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" style="fill: #ffffff;"></path>
          </svg>
      </div>
  </section>
  <div class="container px-4 mt-4">
    <ol class="breadcrumb breadcrumb-style2" aria-label="breadcrumbs">
      <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="post-icon ti tabler-home-check"></i>{{ __('Home') }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('content.frontend.posts.index',['database' => $database ?? session('database', 'default_database')]) }}">{{ __('Posts') }}</a></li>
      <li class="breadcrumb-item active" aria-current="page">{{ $post->title }}</li>
    </ol>
    <div class="progress mt-2" aria-hidden="true">
      <div class="progress-bar" style="width: 100%;"></div>
    </div>
  </div>

  

  <!-- Main Content -->
  <section class="py-5 bg-light">
    <div class="container">
      <div class="row g-4">
        <!-- Main Article -->
        <div class="col-lg-8">
          <article class="card shadow-sm border-0">
            <div class="card-body p-4">
              <!-- Article Header -->
              <header class="mb-4 pb-3 border-bottom">
                <h2 class="h1 mb-3 text-dark">{{ $post->title }}</h2>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                  <span><i class="ti tabler-user me-1"></i>{{ $author->name ?? __('Unknown') }}</span>
                  <span><i class="ti tabler-calendar me-1"></i>{{ $post->created_at->format('d M Y') }}</span>
                  @if($post->category)
                  <span><i class="ti tabler-tag me-1"></i>{{ $post->category->name }}</span>
                  @endif
                </div>
              </header>

              <!-- Article Image -->
              @php
                $processedContent = $post->content ?? '';
                $keywords = is_string($post->keywords) ? array_map('trim', explode(',', $post->keywords)) : [];
                usort($keywords, fn($a, $b) => strlen($b) - strlen($a));
                foreach ($keywords as $keyword) {
                  if (!empty($keyword)) {
                    $link = '<a href="' . route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keyword]) . '" class="keyword-link">' . e($keyword) . '</a>';
                    $processedContent = preg_replace('/\b(' . preg_quote($keyword, '/') . ')\b/ui', $link, $processedContent);
                  }
                }
                $defaultImageUrl = match($database) {
                  'sa' => asset('assets/img/front-pages/icons/articles_saudi_image.jpg'),
                  'eg' => asset('assets/img/front-pages/icons/articles_egypt_image.jpg'),
                  'ps' => asset('assets/img/front-pages/icons/articles_palestine_image.jpg'),
                  default => asset('assets/img/front-pages/icons/articles_default_image.webp'),
                };
                $imageUrl = method_exists($post, 'imageUrl')
                  ? $post->imageUrl()
                  : (($post->image ?? null) ? asset('storage/' . ltrim($post->image, '/')) : $defaultImageUrl);
                $imageCaption = $post->image_caption ?? null;
                $cleanBody = (property_exists($post, 'safe_body_html') && !empty($post->safe_body_html)) ? $post->safe_body_html : $processedContent;
                $cleanBody = preg_replace('/\s*&nbsp;\s*/u', ' ', $cleanBody);
                $titlePattern = '/^\s*<h[12][^>]*>\s*' . preg_quote(strip_tags($post->title ?? ''), '/') . '\s*<\/h[12]>\s*/iu';
                $cleanBody = preg_replace($titlePattern, '', $cleanBody, 1);
              @endphp

              <style>
                .article-image{max-width:100%;height:auto;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
                .article-content img{max-width:100%;height:auto;border-radius:4px;margin:1rem 0;}
                .article-content p{margin-bottom:1rem;line-height:1.7;}
              </style>

              <figure class="text-center mb-4">
                @php
                  $imgPath = ltrim(parse_url($imageUrl, PHP_URL_PATH) ?? '', '/');
                  $src360 = $imgPath ? route('img.fit', ['size' => '360x200', 'path' => $imgPath]) : $imageUrl;
                  $src720 = $imgPath ? route('img.fit', ['size' => '720x400', 'path' => $imgPath]) : $imageUrl;
                  $src960 = $imgPath ? route('img.fit', ['size' => '960x540', 'path' => $imgPath]) : $imageUrl;
                @endphp
                <img
                  src="{{ $src720 }}"
                  srcset="{{ $imgPath ? route('img.fit', ['size' => '360x200', 'path' => $imgPath]) : $imageUrl }} 360w, {{ $src720 }} 720w, {{ $src960 }} 960w"
                  sizes="(max-width: 576px) 360px, (max-width: 768px) 720px, 960px"
                  class="article-image img-fluid"
                  alt="{{ e($post->title) }}"
                  loading="lazy">
                @if(!empty($imageCaption))
                  <figcaption class="text-muted small mt-2">{{ $imageCaption }}</figcaption>
                @endif
              </figure>

              <!-- Article Content -->
              <div class="article-content fs-5 lh-base">
                {!! $cleanBody !!}
              </div>

              <!-- Social Share -->
              <div class="d-flex justify-content-center gap-2 mt-4 pt-3 border-top">
                @php $share = urlencode(url()->current()); $title = urlencode($post->title); @endphp
                <a rel="noopener noreferrer" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ $share }}" class="btn btn-outline-primary btn-sm">
                  <i class="ti tabler-brand-facebook me-1"></i>Facebook
                </a>
                <a rel="noopener noreferrer" target="_blank" href="https://twitter.com/intent/tweet?url={{ $share }}&text={{ $title }}" class="btn btn-outline-info btn-sm">
                  <i class="ti tabler-brand-twitter me-1"></i>Twitter
                </a>
                <a rel="noopener noreferrer" target="_blank" href="https://www.linkedin.com/shareArticle?mini=true&url={{ $share }}&title={{ $title }}" class="btn btn-outline-primary btn-sm">
                  <i class="ti tabler-brand-linkedin me-1"></i>LinkedIn
                </a>
              </div>

              <!-- Tags & Meta -->
              <div class="row mt-4 pt-3 border-top">
                <div class="col-md-6 mb-3">
                  <h5 class="text-primary mb-2"><i class="ti tabler-tag me-1"></i>{{ __('Tags') }}</h5>
                  @php $keywords = is_string($post->keywords) ? array_map('trim', explode(',', $post->keywords)) : []; @endphp
                  @foreach($keywords as $keyword)
                    @continue(empty($keyword))
                    <a href="{{ route('keywords.indexByKeyword', ['database' => $database, 'keywords' => $keyword]) }}" class="badge bg-light text-primary me-1 mb-1 text-decoration-none">{{ $keyword }}</a>
                  @endforeach
                </div>
                <div class="col-md-6 mb-3">
                  <h5 class="text-primary mb-2"><i class="ti tabler-info-circle me-1"></i>{{ __('Meta Description') }}</h5>
                  <p class="text-muted small">{{ Str::limit($post->meta_description ?? $post->excerpt ?? strip_tags($post->body ?? ''), 120) }}</p>
                </div>
              </div>

              <!-- Attachments -->
              @if(!empty($post->attachments) && $post->attachments->count())
              <div class="mt-4 pt-3 border-top">
                <h5 class="text-primary mb-3"><i class="ti tabler-paperclip me-1"></i>{{ __('Attachments') }}</h5>
                <div class="row g-2">
                  @foreach($post->attachments as $file)
                    <div class="col-md-6">
                      <div class="d-flex align-items-center p-2 border rounded bg-light">
                        <i class="ti tabler-file me-2 text-primary"></i>
                        <div class="flex-grow-1">
                          <a href="{{ route('download.page', ['file' => $file->id]) }}" target="_blank" rel="noopener" class="text-decoration-none fw-medium">
                            {{ $file->file_name ?? basename($file->file_path) }}
                          </a>
                          @php
                            $size = (int) ($file->file_size ?? 0);
                            $sizeLabel = $size >= 1048576
                              ? number_format($size / 1048576, 2) . ' MB'
                              : number_format(max($size, 0) / 1024, 1) . ' KB';
                          @endphp
                          <small class="text-muted d-block">{{ $sizeLabel }}</small>
                        </div>
                        @php
                          $extLabel = strtoupper($file->file_type ?? pathinfo($file->file_path, PATHINFO_EXTENSION) ?: 'FILE');
                        @endphp
                        <span class="badge bg-secondary ms-2">{{ $extLabel }}</span>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
              @endif
            </div>
          </article>

          <!-- Comments Section -->
          <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
              <div class="divider divider-primary mb-4">
                <div class="divider-text">{{ __('Comments') }}</div>
              </div>

              @if($post->comments->isNotEmpty())
                @foreach($post->comments as $comment)
                  @php
                    $roleColor = $comment->user->hasRole('Admin') ? 'text-danger' :
                                 ($comment->user->hasRole('Supervisor') ? 'text-warning' : 'text-primary');
                    $avatarBg = $comment->user->hasRole('Admin') ? 'bg-danger' :
                               ($comment->user->hasRole('Supervisor') ? 'bg-warning' : 'bg-primary');
                  @endphp
                  <div class="d-flex mb-4 pb-3 border-bottom" id="comment-{{ $comment->id }}">
                    <div class="flex-shrink-0 me-3">
                      <div class="avatar avatar-sm {{ $avatarBg }} rounded-circle d-flex align-items-center justify-content-center text-white fw-bold">
                        {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <h6 class="mb-0 {{ $roleColor }} fw-bold">{{ $comment->user->name }}</h6>
                          <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                        </div>
                        @if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->hasRole('Admin')))
                          <form action="{{ route('frontend.comments.destroy', ['database' => $database ?? session('database'), 'id' => $comment->id]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1">
                              <i class="ti tabler-trash"></i>
                            </button>
                          </form>
                        @endif
                      </div>
                      <p class="mb-3 comment-body" style="white-space: pre-wrap;">{{ $comment->body }}</p>

                      <!-- Reactions -->
                      <div class="d-flex gap-2 flex-wrap">
                        <form action="{{ route('frontend.reactions.store', ['database' => $database ?? session('database')]) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                          <input type="hidden" name="type" value="like">
                          <button type="submit" class="btn btn-sm btn-outline-primary border-0 p-1">
                            <i class="ti tabler-thumb-up me-1"></i>
                            <span class="badge bg-primary text-white ms-1">{{ $comment->reactions->where('type', 'like')->count() }}</span>
                          </button>
                        </form>
                        <form action="{{ route('frontend.reactions.store', ['database' => $database ?? session('database')]) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                          <input type="hidden" name="type" value="love">
                          <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1">
                            <i class="ti tabler-heart me-1"></i>
                            <span class="badge bg-danger text-white ms-1">{{ $comment->reactions->where('type', 'love')->count() }}</span>
                          </button>
                        </form>
                        <form action="{{ route('frontend.reactions.store', ['database' => $database ?? session('database')]) }}" method="POST" class="d-inline">
                          @csrf
                          <input type="hidden" name="comment_id" value="{{ $comment->id }}">
                          <input type="hidden" name="type" value="laugh">
                          <button type="submit" class="btn btn-sm btn-outline-warning border-0 p-1">
                            <i class="ti tabler-mood-happy me-1"></i>
                            <span class="badge bg-warning text-dark ms-1">{{ $comment->reactions->where('type', 'laugh')->count() }}</span>
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="text-center py-4 text-muted">
                  <i class="ti tabler-message-x display-4 d-block mb-2 opacity-50"></i>
                  <p class="mb-0">{{ __('No comments yet. Be the first to comment!') }}</p>
                </div>
              @endif

              <!-- Add Comment Form -->
              @auth
                <div class="mt-4 pt-3 border-top">
                  <h5 class="mb-3"><i class="ti tabler-plus me-1"></i>{{ __('Add a Comment') }}</h5>
                  <form action="{{ route('frontend.comments.store', ['database' => $database ?? session('database')]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="commentable_id" value="{{ $post->id }}">
                    <input type="hidden" name="commentable_type" value="{{ get_class($post) }}">
                    <div class="mb-3">
                      <textarea id="news-comment-body" class="form-control" name="body" rows="4" required placeholder="{{ __('Write your thoughtful comment here…') }}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="ti tabler-send me-1"></i>{{ __('Submit Comment') }}</button>
                  </form>
                </div>
              @else
                <div class="mt-4 pt-3 border-top text-center">
                  <a href="{{ route('login') }}" class="btn btn-outline-primary"><i class="ti tabler-login me-1"></i>{{ __('Login to comment') }}</a>
                </div>
              @endauth
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          <!-- Related Posts -->
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="ti tabler-article me-1"></i>{{ __('Related Posts') }}</h5>
            </div>
            <div class="card-body p-0">
              <div class="accordion" id="relatedPosts">
                @foreach($relatedNews as $index => $relatedPost)
                  <div class="accordion-item border-0 border-bottom">
                    <div class="accordion-header" id="heading{{ $index }}">
                      <button class="accordion-button collapsed bg-transparent border-0 px-3 py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="false" aria-controls="collapse{{ $index }}">
                        <div class="d-flex align-items-center w-100">
                          <div class="flex-grow-1 text-start">
                            <span class="fw-medium text-dark">{{ Str::limit($relatedPost->title, 40) }}</span>
                          </div>
                        </div>
                      </button>
                    </div>
                    <div id="collapse{{ $index }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $index }}" data-bs-parent="#relatedPosts">
                      <div class="accordion-body px-3 pb-3">
                        @if($relatedPost->image)
                          <img src="{{ asset('storage/' . $relatedPost->image) }}" class="img-fluid rounded mb-2" alt="{{ $relatedPost->title }}" style="max-height: 120px; width: 100%; object-fit: cover;">
                        @endif
                        <p class="text-muted small mb-2">{{ Str::limit(strip_tags($relatedPost->description ?? $relatedPost->body ?? ''), 80) }}</p>
                        <a href="{{ route('content.frontend.posts.show', ['database' => $database ?? session('database', 'default_database'), 'id' => $relatedPost->id]) }}" class="btn btn-primary btn-sm">{{ __('Read more') }}</a>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          @php
            $hasNewsSidebarAd = filled(config('settings.google_ads_desktop_news_2')) || filled(config('settings.google_ads_mobile_news_2'));
          @endphp
          @if($hasNewsSidebarAd)
          <div class="card shadow-sm border-0">
            <div class="card-body text-center">
              <x-adsense.banner desktop-key="google_ads_desktop_news_2" mobile-key="google_ads_mobile_news_2" class="m-0" style="margin:0;" />
            </div>
          </div>
          @endif
        </div>
      </div>
    </div>
  </section>

  <style>
    .bg-gradient-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .keyword-link {
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
    }
    .keyword-link:hover {
      color: #5a67d8;
      text-decoration: underline;
    }
    .comment-body {
      white-space: pre-wrap;
      line-height: 1.6;
    }
    .bg-pattern-dots {
      background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 20px 20px;
    }
  </style>

  @php
    // Prepare helpers for BlogPosting
    $plainContent = trim(strip_tags($post->content ?? ''));
    $wordCount = $plainContent ? str_word_count($plainContent) : null;
    $keywordsArrayForSchema = is_string($post->keywords) ? array_values(array_filter(array_map('trim', explode(',', $post->keywords)))) : [];
    $articleSection = optional($post->category)->name;
    $imageUrl = $post->image ? asset('storage/' . $post->image) : null;

    // Article schema (in Arabic)
    $articleSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'Article',
      'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => request()->url(),
      ],
      'headline' => Str::limit($post->title ?? '', 110, ''),
      'url' => request()->url(),
      'name' => $post->title,
      'alternativeHeadline' => $post->meta_title ?? null,
      'description' => $post->meta_description ?? Str::limit($plainContent, 160, ''),
      'articleBody' => $plainContent,
      'wordCount' => $wordCount,
      'keywords' => $keywordsArrayForSchema,
      'inLanguage' => 'ar',
      'datePublished' => optional($post->created_at)->toIso8601String(),
      'dateModified' => optional($post->updated_at)->toIso8601String(),
      'image' => $imageUrl ? [$imageUrl] : null,
      'articleSection' => $articleSection,
      'author' => [
        '@type' => 'Person',
        'name' => $author->name ?? 'Unknown',
        '@id' => $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#author',
        'url' => $author ? route('front.members.show', ['database' => $database, 'id' => $author->id]) : '#',
      ],
      'publisher' => [
        '@type' => 'Organization',
        'name' => config('settings.site_name'),
        'logo' => [
          '@type' => 'ImageObject',
          'url' => asset('storage/' . config('settings.site_logo')),
        ],
      ],
    ];

    // Breadcrumbs schema with localized names
    $breadcrumbSchema = [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
        [
          '@type' => 'ListItem',
          'position' => 1,
          'name' => __('Home'),
          'item' => url('/'),
        ],
        [
          '@type' => 'ListItem',
          'position' => 2,
          'name' => __('Posts'),
          'item' => route('content.frontend.posts.index', ['database' => $database ?? session('database', 'default_database')]),
        ],
        [
          '@type' => 'ListItem',
          'position' => 3,
          'name' => $post->title,
          'item' => request()->url(),
        ],
      ],
    ];
  @endphp

  <script type="application/ld+json">{!! json_encode(array_filter($articleSchema, fn($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
  <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

  <script>
    (function() {
      const hash = window.location.hash;
      if (hash && hash.startsWith('#comment-')) {
        const el = document.querySelector(hash);
        if (el) {
          try { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
          el.classList.add('comment-highlight');
          setTimeout(() => el.classList.remove('comment-highlight'), 2500);
        }
      }
    })();
  </script>
  <style>
    .comment-highlight { background: #fff7e6; transition: background 0.5s ease; border-radius: 8px; }
  </style>

  @endsection
