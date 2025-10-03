@props([
    'desktopKey' => null,
    'mobileKey' => null,
    'class' => '',
    'label' => __('إعلان'),
    'minHeightDesktop' => '120px',
    'minHeightMobile' => '250px',
])

@php
    $detect = new \Detection\MobileDetect();
    $isMobile = $detect->isMobile();

    $keyForDevice = null;
    if ($isMobile) {
        $keyForDevice = $mobileKey ?: $desktopKey;
    } else {
        $keyForDevice = $desktopKey ?: $mobileKey;
    }

    $rawSnippet = $keyForDevice ? trim((string) config("settings.$keyForDevice")) : null;
    $shouldRender = filled($rawSnippet);
    $minHeight = $isMobile ? $minHeightMobile : $minHeightDesktop;
@endphp

@if($shouldRender)
  <div {{ $attributes->class(['adsense-banner', $class])->merge(['role' => 'complementary', 'aria-label' => $label]) }}>
    <span class="adsense-banner__label">{{ $label }}</span>
    <div class="adsense-banner__slot" style="min-height: {{ $minHeight }};">
      {!! $rawSnippet !!}
    </div>
  </div>
@endif
