@php
    $allReqs = collect($requirements ?? []);
    $byParent = $allReqs->groupBy(fn ($r) => (int) ($r->parent_id ?? 0));
    $roots = $byParent->get(0, collect());
    $orphans = $allReqs->filter(function ($r) use ($allReqs) {
        return $r->parent_id && ! $allReqs->contains(fn ($x) => (int) $x->id === (int) $r->parent_id);
    });
@endphp

@include('frontend.pages.company.visa-processing.partials.requirement-fields-tree', [
    'items' => $roots->merge($orphans)->values(),
    'byParent' => $byParent,
    'depth' => 0,
])
