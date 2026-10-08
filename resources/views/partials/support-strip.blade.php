{{-- Simple "how to start" strip: free start, defined project, hourly rate. One button opens the same enquiry form as "Start your project". --}}
@php $options = collect(\App\Support\Content::engagements())->whereIn('key', ['pilot', 'hourly']); @endphp
<div class="support-strip" data-reveal data-spotlight>
    <div>
        <span class="support-strip__kicker">Flexible ways to start</span>
        <h3 class="support-strip__title">A flexible extension <em>of your studio.</em></h3>
        <p class="support-strip__text">Start free on one defined task, or use us by the hour from ${{ config('site.hourly_from') }}.</p>
    </div>
    <ul class="support-strip__chips" aria-label="Ways to work with Architive">
        @foreach ($options as $e)
            <li class="{{ $e['key'] === 'hourly' ? 'is-hourly' : ($e['key'] === 'pilot' ? 'is-free' : '') }}">
                <a href="{{ pu('contact', [], ['service' => 'unsure']) }}" data-topic="{{ $e['title'] }}"><x-icon :name="$e['ic']" />{{ $e['title'] }} @if (! empty($e['badge']))<b>{{ $e['badge'] }}</b>@endif</a>
            </li>
        @endforeach
    </ul>
    <a class="btn-ay btn-ay--sm" href="{{ pu('contact') }}" data-topic="Contact us">Contact us <x-icon name="arrow-right" /></a>
</div>
