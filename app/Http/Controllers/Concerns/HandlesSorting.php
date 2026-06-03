<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HandlesSorting
{
    private function sortCol(Request $request, array $allowed, string $default, string $param = 'sort'): string
    {
        $col = $request->string($param)->toString();
        return in_array($col, $allowed) ? $col : $default;
    }

    private function sortDir(Request $request, string $param = 'dir', string $default = 'asc'): string
    {
        $dir = $request->string($param)->toString();
        return in_array($dir, ['asc', 'desc']) ? $dir : $default;
    }
}
