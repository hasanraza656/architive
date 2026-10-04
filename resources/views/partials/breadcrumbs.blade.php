@if (! empty($crumbs) && count($crumbs) > 1)
    <nav class="crumbs" aria-label="Breadcrumb">
        <ol>
            @foreach ($crumbs as $c)
                <li @if($loop->last) aria-current="page" @endif>
                    @if ($loop->last)
                        <span>{{ $c['label'] }}</span>
                    @else
                        <a href="{{ $c['url'] }}">{{ $c['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
