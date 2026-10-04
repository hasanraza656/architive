{{-- Five-step timeline. $steps from Content::process(). --}}
<ol class="steps" data-steps>
    @foreach ($steps as $s)
        <li class="steps__item" data-reveal style="--d: {{ $loop->index * .1 }}s">
            <div class="steps__head">
                <span class="steps__num">{{ $s['n'] }}</span>
                <span class="steps__line" aria-hidden="true"></span>
            </div>
            <h3 class="steps__title">{{ $s['title'] }}</h3>
            <p class="steps__text">{{ $s['text'] }}</p>
        </li>
    @endforeach
</ol>
