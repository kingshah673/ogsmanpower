@foreach($items as $req)
    <div class="cw-visa-field mb-3 {{ $depth > 0 ? 'ps-4 border-start' : '' }}">
        <label class="form-label cw-visa-field-label">
            @if($depth > 0)<span class="text-muted">↳ </span>@endif
            {{ $req->label }}
            @if($req->is_required)<span class="text-danger">*</span>@endif
            <span class="cw-visa-field-type">{{ ucfirst($req->type) }}</span>
        </label>

        @if($req->type === 'file')
            @if($req->file)
                <div class="cw-visa-saved-file mb-2">
                    <span class="cw-visa-req-status is-done">Saved</span>
                    <span>{{ $req->file->original_name }} ({{ number_format($req->file->size / 1024, 1) }} KB)</span>
                </div>
            @endif
            <label class="cw-visa-file-zone" for="visa-file-{{ $req->id }}">
                <input type="file"
                    id="visa-file-{{ $req->id }}"
                    name="files[{{ $req->id }}]"
                    class="cw-visa-file-input"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                    data-filename-target="visa-file-name-{{ $req->id }}"
                    @if($req->is_required && empty($req->file)) required @endif>
                <span class="cw-visa-file-zone-body">
                    <strong>Click to upload file</strong>
                    <span class="cw-visa-file-zone-hint" id="visa-file-name-{{ $req->id }}">
                        {{ $req->file ? 'Choose a new file to replace the saved upload' : 'PDF, Word, or image' }}
                    </span>
                </span>
            </label>
        @elseif($req->type === 'date')
            <input type="date" name="answers[{{ $req->id }}]" class="form-control" value="{{ $req->answer?->value }}" @if($req->is_required) required @endif>
        @elseif($req->type === 'checkbox')
            <div class="form-check cw-visa-checkbox-field">
                <input type="checkbox" name="answers[{{ $req->id }}]" value="1" class="form-check-input" id="visa-check-{{ $req->id }}" @checked($req->answer?->value === '1') @if($req->is_required) required @endif>
                <label class="form-check-label" for="visa-check-{{ $req->id }}">I confirm this is complete</label>
            </div>
        @else
            <input type="text" name="answers[{{ $req->id }}]" class="form-control" value="{{ $req->answer?->value }}" placeholder="Enter {{ strtolower($req->label) }}" @if($req->is_required) required @endif>
        @endif

        @php $children = $byParent->get((int) $req->id, collect()); @endphp
        @if($children->isNotEmpty())
            @include('frontend.pages.company.visa-processing.partials.requirement-fields-tree', [
                'items' => $children,
                'byParent' => $byParent,
                'depth' => $depth + 1,
            ])
        @endif
    </div>
@endforeach
