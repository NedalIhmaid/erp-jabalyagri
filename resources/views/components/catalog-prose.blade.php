@props(['text' => null])

@php
    // Catalog copy is authored as plain text: a bullet line starts with a
    // bullet glyph or dash, a short line with no sentence-ending punctuation
    // is a sub-heading, everything else is a paragraph.
    $lines = array_values(array_filter(
        array_map('trim', preg_split('/\R/u', trim((string) $text))),
        static fn (string $line): bool => $line !== '',
    ));

    $blocks = [];
    $bullets = [];

    foreach ($lines as $line) {
        if (preg_match('/^[•*\-–—]\s*(.+)$/u', $line, $match)) {
            $bullets[] = $match[1];

            continue;
        }

        if ($bullets !== []) {
            $blocks[] = ['type' => 'list', 'items' => $bullets];
            $bullets = [];
        }

        $isHeading = mb_strlen($line) <= 60
            && ! preg_match('/[.:،؛!?…]$/u', $line);

        $blocks[] = ['type' => $isHeading ? 'heading' : 'paragraph', 'text' => $line];
    }

    if ($bullets !== []) {
        $blocks[] = ['type' => 'list', 'items' => $bullets];
    }
@endphp

@if ($blocks !== [])
    <div {{ $attributes->class(['catalog-prose']) }}>
        @foreach ($blocks as $block)
            @if ($block['type'] === 'heading')
                <h3>{{ $block['text'] }}</h3>
            @elseif ($block['type'] === 'list')
                <ul>
                    @foreach ($block['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @else
                <p>{{ $block['text'] }}</p>
            @endif
        @endforeach
    </div>
@endif
