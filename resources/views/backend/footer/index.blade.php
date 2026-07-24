@extends('backend.layouts.app')

@section('title', 'Footer CMS')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Footer CMS</h1>
            <a href="{{ url('/') }}" target="_blank" class="btn btn-outline-secondary btn-sm">Preview site</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Footer Colors, Copyright &amp; Badge</strong></div>
        <div class="card-body">
            <form action="{{ route('admin.footer.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Background color</label>
                        <input type="color" name="footer_bg_color" class="form-control form-control-color w-100"
                               value="{{ $cms->footer_bg_color ?? '#2b2f3a' }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Text color</label>
                        <input type="color" name="footer_text_color" class="form-control form-control-color w-100"
                               value="{{ $cms->footer_text_color ?? '#cbd5e1' }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Accent color</label>
                        <input type="color" name="footer_accent_color" class="form-control form-control-color w-100"
                               value="{{ $cms->footer_accent_color ?? '#38bdf8' }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Badge position</label>
                        <select name="footer_badge_position" class="form-control">
                            @foreach (['right' => 'Right', 'left' => 'Left', 'center' => 'Center'] as $val => $label)
                                <option value="{{ $val }}" @selected(($cms->footer_badge_position ?? 'right') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Copyright text</label>
                        <input type="text" name="footer_copyright" class="form-control"
                               value="{{ $cms->footer_copyright ?? 'Copyright © 2012 OGSmanpower.com. All rights reserved.' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Powered by text</label>
                        <input type="text" name="footer_powered_by" class="form-control"
                               value="{{ $cms->footer_powered_by ?? 'OGSmanpower' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">15 Years badge image</label>
                        <input type="file" name="footer_badge_image" class="form-control" accept="image/*">
                        @if (!empty($cms->footer_badge_image))
                            <img src="{{ asset('storage/'.$cms->footer_badge_image) }}" alt="badge" class="mt-2" style="height:60px;">
                        @else
                            <small class="text-muted d-block mt-1">Default: public/icons/15yearslogo.png</small>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="footer_badge_enabled" id="footer_badge_enabled" value="1"
                                   @checked($cms->footer_badge_enabled ?? true)>
                            <label class="form-check-label" for="footer_badge_enabled">Show 15 Years badge</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save footer settings</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Add column / panel</strong></div>
        <div class="card-body">
            <form action="{{ route('admin.footer.panels.store') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Panel title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Jobs By Industry" required>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" class="form-check-input" name="is_active" id="panel_active" value="1" checked>
                        <label class="form-check-label" for="panel_active">Active</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Add panel</button>
                </div>
            </form>
        </div>
    </div>

    @forelse ($panels as $panel)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <form action="{{ route('admin.footer.panels.update', $panel) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                    @csrf
                    @method('PUT')
                    <input type="text" name="title" class="form-control form-control-sm" style="min-width:220px" value="{{ $panel->title }}">
                    <div class="form-check mb-0">
                        <input type="checkbox" class="form-check-input" name="is_active" value="1" id="panel_active_{{ $panel->id }}" @checked($panel->is_active)>
                        <label class="form-check-label" for="panel_active_{{ $panel->id }}">Active</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-success">Update</button>
                </form>
                <form action="{{ route('admin.footer.panels.destroy', $panel) }}" method="POST" onsubmit="return confirm('Delete this panel and all its items?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete panel</button>
                </form>
            </div>
            <div class="card-body">
                @foreach ($panel->items as $item)
                    <div class="border rounded p-2 mb-2">
                        <form action="{{ route('admin.footer.items.update', $item) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-center">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="footer_panel_id" value="{{ $panel->id }}">
                            <div class="col-md-2">
                                <select name="type" class="form-control form-control-sm">
                                    @foreach (['link','heading','text','image'] as $type)
                                        <option value="{{ $type }}" @selected($item->type === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="label" class="form-control form-control-sm" value="{{ $item->label }}" placeholder="Label">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="url" class="form-control form-control-sm" value="{{ $item->url }}" placeholder="URL">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="content" class="form-control form-control-sm" value="{{ $item->content }}" placeholder="Text">
                            </div>
                            <div class="col-md-1">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_active" value="1" @checked($item->is_active)>
                                    <label class="form-check-label">On</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-success">Save</button>
                            </div>
                        </form>
                        <form action="{{ route('admin.footer.items.destroy', $item) }}" method="POST" class="mt-1" onsubmit="return confirm('Delete item?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">Delete item</button>
                        </form>
                    </div>
                @endforeach

                <form action="{{ route('admin.footer.items.store') }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-3">
                    @csrf
                    <input type="hidden" name="footer_panel_id" value="{{ $panel->id }}">
                    <div class="col-md-2">
                        <select name="type" class="form-control form-control-sm">
                            <option value="link">link</option>
                            <option value="heading">heading</option>
                            <option value="text">text</option>
                            <option value="image">image</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="label" class="form-control form-control-sm" placeholder="Label">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="url" class="form-control form-control-sm" placeholder="URL">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="content" class="form-control form-control-sm" placeholder="Text">
                    </div>
                    <div class="col-md-1">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Add item</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No footer panels yet. Add a panel above, or run <code>php artisan db:seed --class=FooterSeeder</code>.</div>
    @endforelse
</div>
@endsection
