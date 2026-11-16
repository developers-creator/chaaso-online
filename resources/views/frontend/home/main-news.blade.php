@if ($showOnFrontNews->isNotEmpty())
    <section class="co-section co-headline">
        <div class="container co-container">
            <div class="co-lead-stack" id="coLeadStack">
                @foreach ($showOnFrontNews as $news)
                    @php
                        $firstLoop = $loop->first ? 'h1' : 'h2';
                        $featuredImage = $img($news);
                    @endphp
                    <article class="co-lead">
                        <span class="co-tag co-tag-red">मुख्य समाचार</span>
                        <{{ $firstLoop }} class="co-lead-title">
                            <a href="{{ route('frontend.news.show', $news->id) }}">{{ $news->post_title }}</a>
                            </{{ $firstLoop }}>
                            <div class="co-meta co-meta-center">
                                <span class="co-meta-author"><i class="bi bi-person"></i>
                                    {{ $news->author?->name ?? 'चासो संवाददाता' }}</span><span><i
                                        class="bi bi-clock-history"></i>
                                    {{ $date($news) }}</span>
                            </div>
                    </article>
                    @if ($news->show_banner_image == 1)
                        @if ($featuredImage)
                            <a href="{{ route('frontend.news.show', $news->id) }}" class="co-lead-media co-img">
                                <img @unless ($loop->first) loading="lazy" @endunless
                                    src="{{ $featuredImage }}" alt="{{ $news->post_title }}" />
                            </a>
                        @endif
                    @endif
                @endforeach
            </div>

            <!-- Ad slot -->
            @php
                $websiteName = $settings['site_title'] ?? null;
                $below_highlighted_img = $settings['below_highlighted_img'] ?? null;
                $featuredmedia = $below_highlighted_img ? MediaHelper::getImageById($below_highlighted_img) : null;
                if (!empty($featuredmedia) && !empty($featuredmedia->file_name)) {
                    $ad_image_url = MediaHelper::WsrvService($featuredmedia);
                } else {
                    $ad_image_url = null;
                }
                $highlighted_ad_url = $settings['highlighted_ad_url'] ?? null;
            @endphp
            @if ($highlighted_ad_url || $featuredmedia)
                <!-- Ad row -->
                <a href="{{ $highlighted_ad_url }}" class="co-ad-banner">
                    <span class="co-ad-label">विज्ञापन</span>
                    <div class="co-ad-inner"><img class="co-cr-img" src="{{ $ad_image_url }}"
                            alt="{{ $websiteName }}" loading="lazy">
                    </div>
                </a>
            @endif

        </div>
    </section>
@endif
