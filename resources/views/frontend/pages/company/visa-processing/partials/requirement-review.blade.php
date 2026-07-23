@php
    use App\Support\VisaSubmissionReview;

    $reviewStatus = VisaSubmissionReview::status($req, (string) $step->assignee);
    $reviewReason = VisaSubmissionReview::reason($req);
    $hasSubmission = ($req->type === 'file' && $req->file)
        || ($req->type !== 'file' && $req->answer);
    $modalId = 'reject-doc-'.$case->id.'-'.$req->id;
@endphp

@if(in_array($actorRole, ['employer', 'agency'], true) && $step->assignee === 'seeker' && $hasSubmission && $case->status === 'in_progress')
    <div class="cw-visa-doc-review">
        <div class="cw-visa-doc-review-head">
            <span class="cw-visa-req-status {{ VisaSubmissionReview::chipClass($reviewStatus) }}">
                {{ VisaSubmissionReview::label($reviewStatus) }}
            </span>
            @if($req->type === 'file' && $req->file && Route::has($vpRoutePrefix.'.file.view'))
                <a href="{{ route($vpRoutePrefix.'.file.view', [$case, $req->file->id]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                    View online
                </a>
                <a href="{{ route($vpRoutePrefix.'.file', [$case, $req->file->id]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
            @endif
        </div>

        @if($reviewReason)
            <p class="cw-visa-doc-review-reason mb-2"><strong>Feedback:</strong> {{ $reviewReason }}</p>
        @endif

        @if(Route::has($vpRoutePrefix.'.requirements.review'))
            <div class="cw-visa-doc-review-actions">
                <form method="POST" action="{{ route($vpRoutePrefix.'.requirements.review', [$case, $req]) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="decision" value="approve">
                    <button type="submit" class="btn btn-sm btn-success" @disabled($reviewStatus === 'approved')>Approve</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#{{ $modalId }}">
                    {{ $reviewStatus === 'approved' ? 'Unapprove' : 'Reject' }}
                </button>
            </div>

            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form method="POST" action="{{ route($vpRoutePrefix.'.requirements.review', [$case, $req]) }}" class="modal-content">
                        @csrf
                        <input type="hidden" name="decision" value="reject">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject — {{ $req->label }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Reason for candidate (required)</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Explain what needs to be corrected…">{{ $reviewReason }}</textarea>
                            <p class="small text-muted mt-2 mb-0">The candidate will see this reason and can re-upload on their visa processing page.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Reject &amp; send back</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@elseif($actorRole === 'seeker' && $step->assignee === 'seeker' && $hasSubmission && $reviewStatus)
    <div class="cw-visa-doc-review cw-visa-doc-review--seeker">
        <span class="cw-visa-req-status {{ VisaSubmissionReview::chipClass($reviewStatus) }}">
            {{ VisaSubmissionReview::label($reviewStatus) }}
        </span>
        @if($reviewStatus === 'rejected' && $reviewReason)
            <p class="cw-visa-warn mb-0 mt-2"><strong>Employer feedback:</strong> {{ $reviewReason }}</p>
        @elseif($reviewStatus === 'pending')
            <p class="cw-visa-waiting mb-0 mt-2">Waiting for employer to review this submission.</p>
        @elseif($reviewStatus === 'approved')
            <p class="text-success small mb-0 mt-2">Your employer approved this submission.</p>
        @endif
    </div>
@endif
