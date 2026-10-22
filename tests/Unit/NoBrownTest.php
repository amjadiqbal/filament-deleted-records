<?php

declare(strict_types=1);

// Amjad's rule: no brown in any UI. Filament draws badges and modal icons as translucent fills in dark mode, and a
// translucent amber, orange or red fill on a dark background reads as brown. Warm colours stay text, lines and borders.

it('keeps warm colours off badges and modal icons in the source', function (): void {
    $source = collect(glob(__DIR__.'/../../src/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE))
        ->map(fn (string $file): string => (string) file_get_contents($file))
        ->implode("\n");

    expect($source)
        ->not->toMatch("/->badge\\(\\)\\s*->color\\('(warning|danger|primary)'\\)/")
        ->not->toMatch("/getNavigationBadgeColor\\(\\)[^}]*'(warning|danger|primary)'/s")
        ->not->toMatch("/modalIconColor\\('(warning|danger|primary)'\\)/")
        ->not->toMatch('/\b(brown|tan|maroon|sienna|chocolate)\b/i');

    // Every confirmation modal sets its icon colour, so none falls back to a warm translucent fill.
    expect(substr_count($source, '->requiresConfirmation()'))->toBe(substr_count($source, "->modalIconColor('gray')"));
});
