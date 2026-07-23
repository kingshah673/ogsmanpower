{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.agency.layout.app')

@section('title')
    {{ __('Verification Documents') }}
@endsection

@section('main')
<div class="dashboard-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">

            <div class="col-lg-8">
                <div class="card shadow-lg border-0 rounded-4">

                    {{-- Card Header --}}
                    <div class="card-header bg-white border-0 pt-4 pb-0">
                        <h4 class="fw-semibold text-dark mb-1">
                            {{ __('Submit Verification Document') }}
                        </h4>
                        <p class="text-muted small mb-3">
                            Please upload a valid NID, Driving License, or Passport for account verification.
                        </p>
                        <hr>
                    </div>

                    {{-- Card Body --}}
                    <div class="card-body px-4 pb-4">

                        <form method="POST"
                              action="{{ route('agency.verify.documents.store') }}"
                              enctype="multipart/form-data">
                            @csrf

                            <div class="mb-4">

                                <x-forms.label 
                                    name="Image of your NID / Driving License / Passport"
                                    :required="false" />

                                <input name="document"
                                       type="file"
                                       data-show-errors="true"
                                       data-width="100%"
                                       data-default-file="{{ $agency->getFirstMedia('document') ? $agency->getFirstMedia('document')->getFullUrl() : '' }}"
                                       {{ $agency->document_verified_at ? "disabled='disabled'" : '' }}
                                       class="dropify form-control shadow-sm rounded-3">

                                @error('document')
                                    <span class="text-danger small d-block mt-2">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            {{-- Status Section --}}
                            <div class="mt-3">

                                @if(!$agency->document_verified_at)

                                    @if($agency->getFirstMedia('document'))
                                        <div class="d-flex align-items-center text-danger mb-3">
                                            <div class="me-2">
                                                <svg style="width:24px;height:24px;"
                                                     xmlns="http://www.w3.org/2000/svg"
                                                     fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke-width="1.5"
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="fw-medium">Document Uploaded - Pending Verification</span>
                                        </div>
                                    @endif

                                    <button type="submit"
                                            class="btn btn-primary px-4 py-2 rounded-3 shadow-sm">
                                        Upload Document
                                    </button>

                                @else

                                    <div class="d-flex align-items-center text-success">
                                        <div class="me-2">
                                            <svg style="width:24px;height:24px;"
                                                 xmlns="http://www.w3.org/2000/svg"
                                                 fill="none"
                                                 viewBox="0 0 24 24"
                                                 stroke-width="1.5"
                                                 stroke="currentColor">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <span class="fw-semibold">Document Verified Successfully</span>
                                    </div>

                                @endif

                            </div>

                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Custom Styling --}}
<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
}

.dropify-wrapper {
    border-radius: 12px;
}

input[type=file]{
    position: static;
    opacity: 1;
    width: auto;
    height: auto;
}
</style>

<link rel="stylesheet" href="{{ asset('backend') }}/plugins/dropify/css/dropify.min.css">

@endsection


@section('frontend_scripts')
<script src="{{ asset('backend') }}/plugins/dropify/js/dropify.min.js"></script>

<script>
    $('.dropify').dropify();
</script>

<script>
    $(document).ready(function (){
        $('#nid_front').on('change',function (){
            $('#nid_front_form').submit();
        })
        $('#nid_back').on('change',function (){
            $('#nid_front_form').submit();
        })
        $('#tin').on('change',function (){
            $('#nid_front_form').submit();
        })
    });
</script>
@endsection