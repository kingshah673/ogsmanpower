{{-- For testing environment --}}
@if (config('templatecookie.testing_mode'))
    @php
        $headerCountries = Modules\Location\Entities\Country::select('id', 'name', 'slug', 'icon')->active()->get();
        $headerCurrencies = Modules\Currency\Entities\Currency::all();
        $languages = loadLanguage();
        $defaultLanguage = Modules\Language\Entities\Language::where(
            'code',
            config('templatecookie.default_language'),
        )->first();
    @endphp
@endif
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .custom-button {
        width: 100%;
        margin-bottom: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 8px 2rem;
        height: 45px;
        font-size: 16px;
        line-height: 1.2;
        border: none;
        border-radius: 50px;
        white-space: normal;
        overflow: hidden;
        position: relative;
    }

    .custom-button::after {
        content: '➔';
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background-color: white;
        border-radius: 50%;
        height: 30px;
        width: 30px;
        font-size: 1.35rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .custom-button:hover {
        cursor: pointer;

    }

    .left-column .custom-button {
        border-top-right-radius: 50px;
        border-bottom-right-radius: 50px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        padding-left: 2.5rem;
    }

    .right-column .custom-button {
        border-top-left-radius: 50px;
        border-bottom-left-radius: 50px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        padding-right: 2.5rem;
    }

    .left-column .custom-button::after {
        right: 6px;
    }

    .right-column .custom-button::after {
        left: 6px;
        transform: translateY(-50%) rotate(180deg);
    }

    /* Button-specific arrow colors */
    .btn-primary.custom-button::after {
        color: #0d6efd;
    }

    .btn-warning.custom-button::after {
        color: #ffc107;
    }

    .btn-danger.custom-button::after {
        color: #dc3545;
    }

    .btn-success.custom-button::after {
        color: #198754;
    }

    @media (max-width: 576px) {
        .custom-button {
            font-size: 10px;
            height: 35px;
            padding: 8px 1.5rem;
        }

        .custom-button::after {
            height: 25px;
            width: 25px;
            font-size: 0.8rem;
        }

        .left-column .custom-button {

            padding-left: 2px;
        }

        .right-column .custom-button {
            padding-right: 2px;
        }
    }

    @media (max-width: 987px) {

        /* Mobile Screen */
        .brand-logo img {
            max-width: 280px !important;
        }
    }

    .n-header--bottom h4 {
        font-size: 2rem;
        /* Adjust font size for smaller screens */
        font-weight: bold;
        margin-bottom: 10px;
    }

    .n-header--bottom span {
        font-size: 1rem;
        /* Adjust font size for smaller screens */
        color: #6c757d;
    }

    .button-style {
        background-color: white !important;
        color: #007bff !important;
        border: 1px solid #007bff !important;
        border-color: #000000 !important;
        border-width: 2px !important;
        padding: 10px 20px !important;
        height: 50px !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        font-size: 1rem !important;
        box-sizing: border-box !important;
    }

    .button-style:hover {
        background-color: #007bff !important;
        /* Blue background on hover */
        color: white !important;
        /* White text on hover */
    }

    .button-style:active,
    .button-style:focus {
        background-color: #007bff !important;
        color: white !important;
    }

    .btn-primary {
        background: #4471ad !important;
    }

    .header-style {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }




    /* Search Bar Styling */
    .search-form {
        display: none;
        /* margin-top: 10px; */
        align-items: center;
        /* Align items vertically in the center */
    }

    .search-form.active {
        display: flex;
        /* Flexbox ensures the input and button are in one line */
        /* gap: 10px; */
        /* Adds spacing between the input and button */
    }

    .brand-logo {
        max-width: 75px !important;
    }


    @media (min-width: 1440px) {
        .heroHeading {
            font-size: 4.5rem !important;
        }
    }

    @media (min-width: 1024px) and (max-width: 1439px) {
        .heroHeading {
            font-size: 4rem !important;
        }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        .heroHeading {
            font-size: 3rem !important;
        }
    }



    @media (min-width: 768px) {
        .n-header--bottom {
            padding: 130px !important;
        }

        .n-header--bottom h4 {
            font-size: 2.5rem;
        }

        .n-header--bottom span {
            font-size: 1.25rem;
        }

        .selectIndCount {
            font-size: 1.1rem;
            margin-bottom: 3rem;
        }


    }

    /* For screens 320px and smaller */
    @media (max-width: 320px) {
        .headerButton {
            padding: 35px 8px;
            /* Padding for small screens */
        }
    }

    /* For screens between 321px and 375px */
    @media (min-width: 321px) {
        .headerButton {
            padding: 35px 36px;
            /* Padding for medium screens */
        }
    }

    @media (min-width: 375px) {
        .headerButton {
            padding: 35px 36px;
            /* Padding for medium screens */
        }
    }

    /* For screens 376px and larger */
    @media (min-width: 376px) {
        .headerButton {
            padding: 35px 63px;
            /* Padding for larger screens */
        }
    }

    @media (max-width: 550px) {
        .header-style {
            justify-content: flex-end;
        }

        .header-style>div:first-child {
            flex-grow: 1;
        }
    }


    @media (max-width: 550px) {
        .mobile-btn {
            padding: 0 !important;
        }
    }

    @media (max-width: 767px) {
        .search-btn-mobile {
            align-self: flex-start !important;
            padding: 5px 16px 8px !important;
        }
    }

    .tab-bar {
        background-color: #f8f9fa;
    }

    .tab-bar .btn {
        font-weight: bold;
        border-radius: 0;
        /* Remove button border radius to make them connected */
    }

    .tab-bar .blue {
        background-color: #004085;
        color: white;
    }

    .tab-bar .light-blue {
        background-color: #568bc3;
        color: white;
    }

    .tab-bar .red {
        background-color: #d9534f;
        color: white;
    }

    .tab-bar .green {
        background-color: #5cb85c;
        color: white;
    }

    .tab-bar .yellow {
        background-color: #ffc107;
        color: white;
    }

    @media (min-width: 1024px) {
        .carouselItemText h1 {
            font-size: 3.5rem;
        }

        .carouselItemText p {
            font-size: 1.5rem;
        }

        .carouselItemText {
            font-size: 1.5rem;
        }


    }

    @media (max-width: 768px) {

        .carouselItemText a {
            padding: 10px 10px;
            font-size: 12px;
        }
    }
</style>


<header class="header rt-fixed-top">
    <script>
        function changeSearchSelections() {
            var job_search_url = "{{ route('website.job') }}";
            var candidate_search_url = "{{ route('website.candidate') }}";
            var company_search_url = "{{ route('website.company') }}";
            var search_selection = $("#headerSearchs").val();

            if (search_selection == 'job') {
                $(".header-search-form").attr('action', job_search_url);
            } else if (search_selection == 'candidate') {
                $(".header-search-form").attr('action', candidate_search_url);
            } else if (search_selection == 'company') {
                $(".header-search-form").attr('action', company_search_url);
            }
        }
    </script>
    {{-- Hero Section Start --}}

    <div class="n-header" id="heroHomeSection" style="display: none;">
        <div class="n-header--top relative">
            @auth('user')
                @if (!authUser()->status)
                    <div class="alert alert-danger" role="alert">
                        <div class="container tw-px-0">
                            <div class="rt-ml-13">
                                {{ __('your_account_is_not_active_please_wait_until_the_account_is_activated_by_admin') }}
                            </div>
                        </div>
                    </div>
                @endif
            @endauth
            <div class=" tw-px-0 ">
                <div class="header-style">

                    <div class="">
                        <a href="{{ route('website.home') }}" class="brand-logo menu-item mx-2">
                            <img src="{{ $setting->light_logo_url }}" alt="logo" class="img-fluid"
                                style="max-width: 300px;">
                        </a>
                        @if (!auth()->user())
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm  d-md-none"
                                style="padding: 10px 10px 10px;line-height: 8px;font-size: 10px;">
                                {{ __('sign_in') }}
                            </a>
                            <a href="javascript:void(0)" class="btn btn-primary btn-sm"
                                    style="padding:10px; font-size:12px;"
                                        data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                                        {{ __('sign_up') }}
                                        </a>
                            
                        @endif


                        <a class="btn btn-outline-primary btn-sm d-md-none" id="mobileSearchDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0px 0px 0px;">
                            <i class="bi bi-search"></i> <!-- Use a Bootstrap Icon -->
                        </a>
                        <div class="dropdown-menu p-3  d-md-none" aria-labelledby="mobileSearchDropdown">
                            <form id="searchForm" method="GET">
                                <div class="mb-2">
                                    <label for="searchType" class="form-label">{{ __('Search Type') }}</label>
                                    <select name="type" id="searchType" class="">
                                        <option value="job" data-action="{{ route('website.filterJobs') }}">
                                            {{ __('Job') }}</option>
                                        <option value="job_seeker"
                                            data-action="{{ route('website.filterJobSeeker') }}">
                                            {{ __('Job Seeker') }}</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="searchQuery" class="form-label">{{ __('Search') }}</label>
                                    <input type="text" name="keyword" id="searchQuery" class="form-control"
                                        placeholder="{{ __('Type to search...') }}">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">{{ __('Search') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="n-header--top__left main-menu">
                        {{-- <div class="mbl-top d-flex align-items-center justify-content-between container position-relative d-lg-none">
                            <div class="d-flex align-items-center">
                                <a href="{{ route('website.home') }}" class="brand-logo" >
                                    <img src="{{ $setting->light_logo_url }}" alt="logo">
                                </a>
                            </div>

                            <div class="">
                                <div class="d-flex align-items-center ">
                                    <div class="search-icon d-lg-none tw-text-white">
                                        <svg id="mblSearchIcon" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M20.9999 21L16.6499 16.65" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="mblTogglesearch bg-primary-500 rounded">
                                        <form action="{{ route('website.job') }}" method="GET" id="search-form"
                                            class="shadow px-md-5 py-md-3 p-3 !tw-bg-white rounded w-sm-75 w-100">
                                            <div class="form-item">
                                                <input name="keyword" class="search-input w-100" type="text"
                                                    placeholder="{{ __('job_title_keyword') }}"
                                                    value="{{ request('keyword') }}" id="mobile_search_input">
                                            </div>
                                        </form>
                                    </div>
                                    @auth('user')
                                        <ul
                                            class="custom-border list-unstyled d-flex align-items-center justify-content-end">
                                            @if (auth()->user()->role == 'company')
                                                <x-website.company.notifications-component />
                                            @endif

                                            @if (auth()->user()->role == 'candidate')
                                                <x-website.candidate.notifications-component />
                                            @endif

                                            @company
                                                <li class="relative">
                                                    <a href="{{ route('user.dashboard') }} " class="candidate-profile p-0">
                                                        <img src="{{ auth()->check() ? auth()->user()?->company?->logo_url : '' }}"
                                                            alt="company logo">
                                                    </a>
                                                </li>
                                            @else
                                                <li class="relative">
                                                    <a href="{{ route('user.dashboard') }} " class="candidate-profile p-0">
                                                        <img src="{{ auth()->check() ? auth()->user()?->candidate?->photo : '' }}"
                                                            alt="user logo">
                                                    </a>
                                                </li>
                                            @endcompany

                                            @if (!request()->is('email/verify'))
                                                @if (auth()->user()->role !== 'company' && auth()->user()->role !== 'candidate')
                                                    <li>
                                                        <a href="{{ route('company.job.create') }}">
                                                            <button class="btn btn-primary">
                                                                {{ __('post_job') }}
                                                            </button>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif

                                            @if (request()->is('email/verify'))
                                                <li>
                                                    <a href="{{ route('logout') }}"
                                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                        <button class="btn btn-primary">
                                                            {{ __('log_out') }}
                                                        </button>
                                                    </a>
                                                </li>
                                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                    class="d-none">
                                                    @csrf
                                                </form>
                                            @endif
                                        </ul>
                                    @endauth

                                    @guest
                                        <ul class="list-unstyled">
                                            <li>
                                                <a href="{{ route('company.job.create') }}"
                                                    class="btn btn-primary text-white"
                                                    style="padding:12px 24px !important;">{{ __('post_job') }}
                                                </a>
                                            </li>
                                        </ul>
                                    @endguest
                                </div>
                            </div>
                        </div> --}}
                        @if (auth('user')->check())
                            @if (authUser()->role == 'company')
                                <div class="container">

                                    <ul class="menu-active-classes">
                                        @if (isset($company_menu_lists))
                                            @foreach ($company_menu_lists as $company_menu_list)
                                                <li class="menu-item">
                                                    @php
                                                        // Check if the URL starts with "http" or "https" to identify external links
                                                        $isExternalLink = Str::startsWith($company_menu_list['url'], [
                                                            'http://',
                                                            'https://',
                                                        ]);
                                                    @endphp
                                                    <a href="{{ $company_menu_list['url'] }}"
                                                        @if ($isExternalLink) target="_blank" @endif
                                                        class="{{ urlMatch(url()->current(), url($company_menu_list['url'])) ? 'text-primary active' : '' }}">
                                                        @if ($company_menu_list['title'])
                                                            {{ $company_menu_list['title'] }}
                                                        @else
                                                            @if ($company_menu_list['en_title'])
                                                                {{ $company_menu_list['en_title'] }}
                                                            @endif
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                            @if ($custom_pages->where('show_header', 1)->count() > 0)
                                                <li class="menu-item extra-page d-none d-lg-inline-block">
                                                    <a href="javascript:void(0)" class="dropdown-toggle">
                                                        Extra Pages
                                                    </a>
                                                    <ul class="ll-dropdown-menu">
                                                        @foreach ($custom_pages->where('show_header', 1) as $page)
                                                            <li>
                                                                <a class="!tw-px-5 !tw-py-2"
                                                                    href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endif

                                            @foreach ($custom_pages->where('show_header', 1) as $page)
                                                <li class="d-lg-none">
                                                    <a class=""
                                                        href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                    <div class="tw-mb-post-job">
                                        <a href="{{ route('company.job.create') }}">
                                            <button class="btn btn-primary">
                                                {{ __('post_job') }}
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="container">
                                    <ul class="menu-active-classes ">
                                        @if (isset($candidate_menu_lists))
                                            @foreach ($candidate_menu_lists as $candidate_menu_list)
                                                <li class="menu-item">
                                                    @php
                                                        // Check if the URL starts with "http" or "https" to identify external links
                                                        $isExternalLink = Str::startsWith($candidate_menu_list['url'], [
                                                            'http://',
                                                            'https://',
                                                        ]);
                                                    @endphp
                                                    <a href="{{ $candidate_menu_list['url'] }}"
                                                        @if ($isExternalLink) target="_blank" @endif
                                                        class="{{ urlMatch(url()->current(), url($candidate_menu_list['url'])) ? 'text-primary active' : '' }}">
                                                        @if ($candidate_menu_list['title'])
                                                            {{ $candidate_menu_list['title'] }}
                                                        @else
                                                            @if ($candidate_menu_list['en_title'])
                                                                {{ $candidate_menu_list['en_title'] }}
                                                            @endif
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                            <li class="menu-item extra-page d-none d-lg-inline-block">
                                                <a href="javascript:void(0)" class="dropdown-toggle">
                                                    Extra Pages
                                                </a>
                                                <ul class="ll-dropdown-menu">
                                                    @foreach ($custom_pages->where('show_header', 1) as $page)
                                                        <li>
                                                            <a class="!tw-px-5 !tw-py-2"
                                                                href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                            @foreach ($custom_pages->where('show_header', 1) as $page)
                                                <li class="d-lg-none">
                                                    <a class=""
                                                        href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        @else
                            <div style="display:flex;justify-content:flex-start !important"
                                class="justify-content-start">
                                <!-- <div>
                                    <a href="{{ route('website.home') }}" class="brand-logo mr-4 menu-item mx-2" >
                                        <img src="{{ $setting->dark_logo_url }}" alt="logo" style="max-width: 330px !important;">
                                    </a>
                                </div> -->

                                <div class="container d-flex align-items-center  ml-4">
                                    <!-- Logo -->
                                    <!-- Menu -->
                                    <ul class="menu-active-classes d-flex align-items-center">
                                        @if (isset($public_menu_lists))
                                            @foreach ($public_menu_lists as $public_menu_list)
                                                <li class="menu-item mx-2">
                                                    @php
                                                        // Check if the URL starts with "http" or "https" to identify external links
                                                        $isExternalLink = Str::startsWith($public_menu_list['url'], [
                                                            'http://',
                                                            'https://',
                                                        ]);
                                                    @endphp
                                                    <a href="{{ $public_menu_list['url'] }}"
                                                        @if ($isExternalLink) target="_blank" @endif
                                                        class="{{ urlMatch(url()->current(), url($public_menu_list['url'])) ? 'text-primary active' : '' }}">
                                                        @if ($public_menu_list['title'])
                                                            {{ $public_menu_list['title'] }}
                                                        @elseif ($public_menu_list['en_title'])
                                                            {{ $public_menu_list['en_title'] }}
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach

                                            @if ($custom_pages->where('show_header', 1)->count() > 0)
                                                <li class="menu-item extra-page d-none d-lg-inline-block">
                                                    <a href="javascript:void(0)" class="dropdown-toggle">
                                                        {{ __('extra_pages') }}
                                                    </a>
                                                    <ul class="ll-dropdown-menu">
                                                        @foreach ($custom_pages->where('show_header', 1) as $page)
                                                            <li>
                                                                <a class="!tw-px-5 !tw-py-2"
                                                                    href="{{ route('showCustomPage', $page->slug) }}">
                                                                    {{ $page->title }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endif

                                            @foreach ($custom_pages->where('show_header', 1) as $page)
                                                <li class="d-lg-none">
                                                    <a class="{{ urlMatch(url()->current(), url($public_menu_list['url'])) ? 'text-primary active' : '' }}"
                                                        href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            </div>

                        @endif

                        <div class="xs:tw-hidden tw-mt-6 mbl-bottom">
                            <div class="container">
                                @if ($cms_setting?->footer_phone_no)
                                    <div class="contact-info">
                                        <a class="text-gray-900" href="tel:{{ $cms_setting?->footer_phone_no }}">
                                            <x-svg.telephone2-icon />
                                            {{ $cms_setting?->footer_phone_no }}
                                        </a>
                                    </div>
                                @endif
                                @if ($setting->app_country_type === 'multiple_base')
                                    <form action="{{ route('website.job') }}" method="GET" id="search-form">
                                        <div class="tw-flex tw-w-full">
                                            @php
                                                $selected_country = session('selected_country');
                                            @endphp
                                            <div class="dropdown dropup tw-w-full">
                                                <button
                                                    class="btn tw-flex tw-justify-between tw-w-full tw-px-0 dropdown-toggle"
                                                    type="button" id="" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <div>
                                                        @if ($selected_country && selected_country())
                                                            <i class="flag-icon {{ selected_country()->icon }}"></i>
                                                            {{ selected_country()->name }}
                                                        @else
                                                            {{ __('all_country') }}
                                                        @endif
                                                    </div>
                                                </button>

                                                <ul class="dropdown-menu mx-height-400 overflow-auto tw-p-2"
                                                    aria-labelledby="dropdownMenuButton1">
                                                    <li>
                                                        <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                            href="{{ route('website.set.country') }}">
                                                            <svg width="26" height="26" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 6h16M4 10h16M4 14h16M4 18h16">
                                                                </path>
                                                            </svg>
                                                            <span class="marginleft">
                                                                {{ __('all_country') }}
                                                            </span>
                                                        </a>
                                                    </li>

                                                    @foreach ($headerCountries as $country)
                                                        <li id="lang-dropdown-item">
                                                            <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                                href="{{ route('website.set.country', ['country' => $country->id]) }}">
                                                                <i class="flag-icon {{ $country->icon }}"></i>
                                                                {{ $country->name }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                                @if (count($headerCurrencies) && $setting->currency_switcher)
                                    @php
                                        $currency_count = count($headerCurrencies) && count($headerCurrencies) > 1;
                                        $current_currency_code = currentCurrencyCode();
                                    @endphp
                                    <div class="dropdown dropup">
                                        <button
                                            class="btn tw-flex tw-w-full tw-justify-between tw-px-0 {{ count($headerCurrencies) ? 'dropdown-toggle' : '' }}"
                                            type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            {{ $current_currency_code }}
                                        </button>
                                        @if ($currency_count)
                                            <ul class="dropdown-menu tw-p-2" aria-labelledby="dropdownMenuButton1">
                                                @foreach ($headerCurrencies as $currency)
                                                    @if ($currency->code != $current_currency_code)
                                                        <li id="lang-dropdown-item">
                                                            <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                                href="{{ route('changeCurrency', $currency->code) }}">
                                                                {{ $currency->code }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class=" d-flex align-items-center">
                        {{-- @if ($cms_setting?->footer_phone_no)
                            <div class="contact-info xs:tw-inline-flex tw-hidden">
                                <a class="text-gray-900" href="tel:{{ $cms_setting?->footer_phone_no }}">
                                    <x-svg.telephone2-icon />
                                    {{ $cms_setting?->footer_phone_no }}
                                </a>
                            </div>
                        @endif --}}
                        @if ($setting->language_changing)
                            <div class="dropdown">
                                @php
                                    $language_count = count($languages) && count($languages) > 1;
                                    $language_count2 = count($languages);
                                    $current_language = currentLanguage() ? currentLanguage() : loadDefaultLanguage();
                                @endphp
                                <button class="btn mobile-btn d-flex {{ $language_count ? 'dropdown-toggle' : '' }} "
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i
                                        class="flag-icon lh-base {{ isset($current_language->icon) && $current_language->icon ? $current_language->icon : '' }}"></i>
                                    <div class="d-none d-md-block ">
                                        {{ isset($current_language->name) && $current_language->name ? $current_language->name : '' }}
                                    </div>
                                </button>
                                @if ($language_count)
                                    <ul class="dropdown-menu mx-height-300 overflow-auto tw-p-2"
                                        aria-labelledby="dropdownMenuButton1">

                                        @foreach ($languages as $lang)
                                            @if (isset($current_language->code) && $current_language->code != $lang->code)
                                                <li id="lang-dropdown-item">
                                                    <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                        href="{{ route('changeLanguage', $lang->code) }}">
                                                        <i
                                                            class="flag-icon {{ isset($lang->icon) && $lang->icon ? $lang->icon : '' }} tw-me-2.5"></i>
                                                        {{ $lang->name }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        @auth('user')
                            <div>

                                @if (auth()->user()->role == 'company')
                                    {{-- <x-website.company.notifications-component /> --}}
                                @endif
                                @if (auth()->user()->role == 'candidate')
                                    {{-- <x-website.candidate.notifications-component /> --}}
                                @endif

                                {{-- <x-website.company.message-component /> --}}

                                <div class="dropdown dropstart">
                                    <a href="javascript:void(0)" class="candidate-profile position-relative"
                                        id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                        @company
                                            <img src="{{ auth()->user()?->company?->logo_url ?? asset('images/default-company.png') }}" alt="logo">
                                        @else
                                            <img src="{{ auth()->check() ? auth()->user()?->candidate?->photo : ''}}" alt="photo">
                                            @if (auth()->user()->candidate && auth()->user()->candidate->status == 'available')
                                                <span class="available-alert-header">
                                                    <svg class="circle" width="14" height="14" viewBox="0 0 14 14"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <circle cx="7" cy="7" r="6" fill="#2ecc71"
                                                            stroke="white" stroke-width="2">
                                                        </circle>
                                                    </svg>
                                                </span>
                                            @endif
                                        @endcompany
                                    </a>
                                    @candidate
                                    <ul class="custom-border dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('candidate.dashboard') ? 'active' : '' }}"
                                                href="{{ route('candidate.dashboard') }}">
                                                <i class="ph-stack"></i>
                                                {{ __('dashboard') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('candidate.setting') ? 'active' : '' }}"
                                                href="{{ route('candidate.setting') }}">
                                                <i class="ph-gear"></i>
                                                {{ __('settings') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logout') }}"
                                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <i class="ph-sign-out"></i>
                                                {{ __('log_out') }}
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                @else
                                    <ul class="dropdown-menu custom-border" aria-labelledby="dropdownMenuButton1">
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('company.dashboard') ? 'active' : '' }}"
                                                href="{{ route('company.dashboard') }}">
                                                <i class="ph-stack"></i>
                                                {{ __('dashboard') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('company.myjob') ? 'active' : '' }}"
                                                href="{{ route('company.myjob') }}">
                                                <i class="ph-suitcase-simple"></i>
                                                {{ __('my_jobs') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('company.plan') ? 'active' : '' }}"
                                                href="{{ route('company.plan') }}">
                                                <i class="ph-notebook"></i>
                                                {{ __('plans_billing') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('company.setting') ? 'active' : '' }}"
                                                href="{{ route('company.setting') }}">
                                                <i class="ph-gear"></i>
                                                {{ __('settings') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logout') }}"
                                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <i class="ph-sign-out"></i>
                                                {{ __('log_out') }}
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                    @endcandidate
                                </div>
                                {{-- @if (!request()->is('email/verify'))
                            @company
                            <li class="tw-hidden sm:tw-block">

                                <a href="{{ route('company.job.create') }}">
                                    <button class="btn btn-light">
                                        {{ __('post_job') }}
                                    </button>
                                </a>
                            </li>
                            @endcompany
                            @endif --}}
                                @if (request()->is('email/verify'))
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <button class="btn btn-primary">
                                                {{ __('log_out') }}
                                            </button>
                                        </a>
                                    </li>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        class="d-none">
                                        @csrf
                                    </form>
                                @endif
                            </div>
                        @endauth
                        {{-- @if (count($headerCurrencies) && $setting->currency_switcher)
                            @php
                                $currency_count = count($headerCurrencies) && count($headerCurrencies) > 1;
                                $current_currency_code = currentCurrencyCode();
                            @endphp
                            <div class="dropdown xs:tw-inline-flex tw-hidden">
                                <button class="btn {{ count($headerCurrencies) ? 'dropdown-toggle' : '' }}"
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    {{ $current_currency_code }}
                                </button>
                                @if ($currency_count)
                                    <ul class="dropdown-menu mx-height-300 overflow-auto tw-p-2"
                                        aria-labelledby="dropdownMenuButton1">
                                        @foreach ($headerCurrencies as $currency)
                                            @if ($currency->code != $current_currency_code)
                                                <li id="lang-dropdown-item">
                                                    <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                        href="{{ route('changeCurrency', $currency->code) }}">
                                                        {{ $currency->code }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        @if ($setting->app_country_type === 'multiple_base')
                            <form action="{{ route('website.job') }}" method="GET" id="search-form"
                                class="mx-width-300 xs:tw-inline-flex tw-hidden">
                                <div class="d-flex">
                                    @php
                                        $selected_country = session('selected_country');
                                    @endphp
                                    <div class="">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle" type="button" id=""
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                @if ($selected_country && selected_country())
                                                    <i class="flag-icon {{ selected_country()->icon }}"></i>
                                                    {{ selected_country()->name }}
                                                @else
                                                    {{ __('all_country') }}
                                                @endif
                                            </button>

                                            <ul class="dropdown-menu mx-height-300 overflow-auto tw-p-2"
                                                aria-labelledby="dropdownMenuButton1">
                                                <li>
                                                    <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                        href="{{ route('website.set.country') }}">
                                                        <svg width="26" height="26" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16">
                                                            </path>
                                                        </svg>
                                                        <span class="marginleft">
                                                            {{ __('all_country') }}
                                                        </span>
                                                    </a>
                                                </li>
                                                @foreach ($headerCountries as $country)
                                                    <li id="lang-dropdown-item">
                                                        <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                            href="{{ route('website.set.country', ['country' => $country->id]) }}">
                                                            <i class="flag-icon {{ $country->icon }}"></i>
                                                            {{ $country->name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        @endif --}}
                        @if (!auth()->user())
                            <ul class="d-none d-lg-flex align-items-center gap-1 mt-4 list-unstyled">
                                <!-- Buttons visible only on large screens -->
                                <li>
                                    <a href="{{ route('login') }}"
                                        class="btn btn-primary d-inline-block">{{ __('login') }}</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" class="btn btn-primary btn-sm"
                                    style="padding:10px; font-size:12px;"
                                        data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                                        {{ __('register') }}
                                        </a>
                                    
                                </li>
                                <li>
                                    <a href="{{ route('company.job.create') }}"
                                        class="btn btn-primary d-inline-block">{{ __('post_job') }}</a>
                                </li>
                            </ul>

                            <div class="d-none d-md-block d-lg-none">


                                <a href="{{ route('login') }}" class="btn btn-primary btn-sm"
                                    style="padding: 10px 10px 10px; line-height: 8px; font-size: 10px;">
                                    {{ __('login') }}
                                </a>
                                <a href="javascript:void(0)" class="btn btn-primary btn-sm"
                                    style="padding:10px; font-size:12px;"
                                        data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                                        {{ __('register') }}
                                        </a>

                                <a href="{{ route('company.job.create') }}" class="btn btn-primary btn-sm"
                                    style="padding: 10px 10px 10px; line-height: 8px; font-size: 10px;">
                                    {{ __('post_job') }}
                                </a>

                            </div>
                        @endif


                    </div>


                    <div class="mobile-menu">
                        <div class="menu-click tw-pe-3">
                            <button class="effect1">
                                <span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Header top -->
        <div class="d-none d-md-block p-0">
            <div class="tab-bar d-flex">
                <a href="{{ route('register', ['type' => 'employer']) }}" class="btn light-blue w-100">{{ __('employer') }}</a>
                <a href="{{ route('register', ['type' => 'seeker']) }}" class="btn green w-100">{{ __('seeker') }}</a>
                <a href="{{ route('website.about') }}" class="btn red w-100" aria-current="true"> {{ __('About_company') }}</a>
                <a href="{{ route('website.privacyPolicy') }}" class="btn green w-100" aria-current="true"> {{ __('privacy_company') }}</a>
                <a href="https://ogsgroupofficial.com/" class="btn light-blue w-100"  aria-current="true"> {{ __('ogs_group') }}</a>
                <a href="{{ route('website.contact') }}" class="btn blue w-100" aria-current="true">{{ __('contact_company') }}</a>
            </div>
            <div class="p-0"
                style=" box-shadow: inset 0 -1px #06080f;  background-size: cover; background-position: center;">

                <div id="carouselExampleIndicators" class="carousel slide p-0" data-bs-ride="carousel">
                    <div class="carousel-indicators d-none">
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0"
                            class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"
                            aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"
                            aria-label="Slide 3"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="3"
                            aria-label="Slide 4"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="4"
                            aria-label="Slide 5"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="5"
                            aria-label="Slide 6"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="6"
                            aria-label="Slide 7"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="7"
                            aria-label="Slide 8"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="8"
                            aria-label="Slide 9"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="9"
                            aria-label="Slide 10"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="10"
                            aria-label="Slide 11"></button>

                        <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="11"
                            aria-label="Slide 12"></button>
                    </div>


                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">
                                        <!-- Left Section -->
                                        <div class="col-md-6 mt-3">

                                            <h1>{{ __('register_as') }} <strong
                                                    class="text-primary">{{ __('employer/company') }}</strong></h1>
                                            <p> {{ __('employer_company_description') }}</p>

                                            <a href="#" class="btn btn-primary ">{{ __('sign_up_now') }}</a>



                                        </div>

                                        <!-- Right Section -->
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images/herosection/Employer.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>
                                <!-- Bottom Navigation Bar -->
                            </div>
                        </div>




                        <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        <!-- Left Section -->

                                        <!-- Left Section -->
                                        <div class="col-md-6">
                                            <h1>{{ __('register_as') }} <br /><strong class="text-primary">
                                                    {{ __('job_seeker') }}</strong></h1> 
                                            <p>{{ __('job_seeker_description') }}</p>
                                            <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        <!-- Right Section -->
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Seeker.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>

                                    </div>

                                    <!-- Bottom Navigation Bar -->
                                </div>
                            </div>
                        </div>

                       <!-- <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        
                                      <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('recruitment_agency') }}</strong>
                                            </h1>
                                            <p> {{ __('recruitment_agency_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <!-- <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Recruitmentcompany.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div>-->
                        <!-- 

                      <!--   <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        <!-- Left Section -->
                                       <!--  <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('recuritment_agents') }}</strong>
                                            </h1>
                                            <p>{{ __('recruitment_agent_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        <!-- Right Section -->
                                       <!--  <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Recruitmentagents.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Navigation Bar -->
                           <!--  </div>

                        </div>-->

                       <!--  <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('labour_supply_office') }}</strong>
                                            </h1>
                                            <p> {{ __('labour_supply_office_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                       
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Labour Supply Office.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div> -->

                       <!--  <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('nominated_worker_selected') }}</strong></h1>
                                            <p>{{ __('nominated_worker_selected_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Nominatedworker.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div> -->


                       <!--  <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                       
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('domestic_worker_office') }}</strong>
                                            </h1>
                                            <p> {{ __('domestic_worker_office_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Domesticworkeroffice.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                               
                            </div>

                        </div>-->


                       <!--  <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                       
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('selected_domestic_worker') }}</strong>
                                            </h1>
                                            <p>{{ __('selected_domestic_worker_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\SelectedWorker.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div>-->

                        <!-- <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong
                                                    class="text-primary">{{ __('university/college/school') }}</strong>
                                            </h1>
                                            <p> {{ __('university/college/school_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\University.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div>-->


                       <!--  <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('abroad_education_student') }}</strong>
                                            </h1>
                                            <p> {{ __('abroad_education_student_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\Abroadedu.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div>-->


                       <!--  <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                        
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong class="text-primary">
                                                    {{ __('eu_work_permit_specialist') }}</strong></h1>
                                            <p> {{ __('work_permit_specialist_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                        
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\WorkPermitspecilist.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div>-->


                        <!-- <div class="carousel-item ">
                            <div
                                style="box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
                                <div class="container p-0">
                                    <div class="row d-flex align-items-center carouselItemText">

                                      
                                        <div class="col-md-6">
                                            <h1> {{ __('login_as') }} <strong
                                                    class="text-primary">{{ __('eu_work_permit_seeker') }}</strong>
                                            </h1>
                                            <p>{{ __('work_permit_seeker_description') }}</p>
   <a href="{{ route('register') }}" class="btn btn-primary "> {{ __('sign_up_now') }}</a>
                                        </div>

                                       
                                        <div class="col-md-6 d-flex justify-content-end">
                                            <img src="{{ asset('images\herosection\WorkPermitsseeker.png.png') }}"
                                                alt="Employer" class="img-fluid">
                                        </div>
                                    </div>
                                </div>

                                
                            </div>

                        </div>--> 
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>

                <!-- Bottom Navigation Bar -->
            </div>

          <!--  <div class="tab-bar d-flex p-0 ">
                <a href="#" class="btn light-blue w-100"data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="6" aria-current="true"> {{ __('domestic_worker_office') }}</a>
                <a href="#" class="btn yellow w-100" data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="7" aria-current="true"> {{ __('selected__worker') }}</a>
                <a href="#" class="btn red w-100" data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="8" aria-current="true"> {{ __('university/college') }}</a>
                <a href="#" class="btn green w-100" data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="9" aria-current="true"> {{ __('abroad_edu_student') }}</a>
                <a href="#" class="btn light-blue w-100" data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="10" aria-current="true">{{ __('work_permit_specialist') }}</a>
                <a href="#" class="btn blue w-100" data-bs-target="#carouselExampleIndicators"
                    data-bs-slide-to="11" aria-current="true"> {{ __('work_permit_seeker') }}</a>
            </div>-->

        </div >


        <div class="d-block d-sm-none "
            style=" box-shadow: inset 0 -1px #e4e5e8; background-image: url('images/background.png'); background-size: cover; background-position: center;">
            <div class="text-center mt-0 ">
                <!--{{-- <h1 class="mb-3">World <span class="text-success fs-1">#01</span> Website to Hunt Work Force</h1> --}}
                <p style="font-size: 17px;">{{ __('worlds_first_complete_hr_website_to' ) }} <span class="text-danger">{{ __('ogs_manpower') }}</span>
                   </p>--><br/>
                 
                <div class="row mt-0">
                    <div class="col-6 d-flex flex-column left-column">
                        <a href="{{ route('register', ['type' => 'employer']) }}" class="btn-success custom-button w-100">{{ __('employer') }}</a>
                        <!--<a href="{{ route('register') }}"class=" btn-warning custom-button w-100">{{ __('recruitment_agency') }}</a>-->
                        <a href="{{ route('website.about') }}" class="btn-warning custom-button w-100" aria-current="true"> {{ __('About_company') }}</a>
                        <!--<a href="{{ route('register') }}" class=" btn-danger  custom-button w-100">{{ __('labour_supply_office') }}</a>-->
                        <a href="https://ogsgroupofficial.com/" class="btn-success custom-button w-100"  aria-current="true"> {{ __('ogs_group') }}</a>
                        <!--<a href="{{ route('register') }}" class=" btn-danger  custom-button w-100">{{ __('domestic_worker_office') }}</a>
                        <a href="{{ route('register') }}" class=" btn-warning custom-button w-100">{{ __('university/college/school') }}</a>
                        <a href="{{ route('register') }}"class=" btn-primary custom-button w-100">{{ __('eu_work_permit_specialist') }} </a>-->
                    </div>
                    <div class="col-6 d-flex flex-column right-column">
                        <a href="{{ route('register', ['type' => 'seeker']) }}" class="btn-primary custom-button w-100">{{ __('seeker') }}</a>
                        <a href="{{ route('website.privacyPolicy') }}" class="btn-success custom-button w-100" aria-current="true"> {{ __('privacy_company') }}</a>
                        <!--<a href="{{ route('register') }}" class=" btn-warning custom-button w-100">{{ __('recuritment_agents') }}</a>-->
                        <!--<a href="{{ route('register') }}" class=" btn-danger custom-button w-100">{{ __('nominated_worker') }}</a>-->
                        <a href="{{ route('website.contact') }}" class="btn-danger custom-button w-100" aria-current="true">{{ __('contact_company') }}</a>
                        <!--<a href="{{ route('register') }}" class=" btn-danger custom-button w-100">{{ __('selected_domestic_worker') }}</a>
                        <a href="{{ route('register') }}" class="btn-warning custom-button w-100">{{ __('abroad_edu_student') }}</a>
                        <a href="{{ route('register') }}" class=" btn-success custom-button w-100">{{ __('work_permit_seeker') }}</a>-->
                    </div>
                </div>
            </div>


            <!-- Bottom Navigation Bar -->
        </div>
        <div class="rt-mobile-menu-overlay"></div>
        <div class="sidebar-overlay"></div>
    </div>
    {{-- Hero Section End --}}

    {{-- Other Section Start --}}
    <div class="n-header" id="heroOtherSection" style="display: none;">
        <div class="n-header--top relative">
            @auth('user')
                @if (!authUser()->status)
                    <div class="alert alert-danger" role="alert">
                        <div class="container tw-px-0">
                            <div class="rt-ml-13">
                                {{ __('your_account_is_not_active_please_wait_until_the_account_is_activated_by_admin') }}
                            </div>
                        </div>
                    </div>
                @endif
            @endauth
            <div class=" tw-px-0 ">
                <div class="header-style">
<!--header-other-page-->
                    <div>
                        <a href="{{ route('website.home') }}" class="brand-logo menu-item mx-2">
                            <img src="{{ $setting->light_logo_url }}" alt="logo" class="img-fluid"
                                style="max-width: 300px;">
                        </a>

                        @if (!auth()->user())
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm  d-md-none"
                                style="padding: 10px 10px 10px;line-height: 8px;font-size: 10px;">
                                {{ __('sign_in') }}
                            </a>
                            <a href="javascript:void(0)" class="btn btn-primary btn-sm"
                                    style="padding:10px; font-size:12px;"
                                        data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                                        {{ __('sign_up') }}
                                        </a>
                            
                        @endif


                        <a class="btn btn-outline-primary btn-sm d-md-none" id="mobileSearchDropdown1"
                            data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0px 0px 0px;">
                            <i class="bi bi-search"></i> <!-- Use a Bootstrap Icon -->
                        </a>
                        <div class="dropdown-menu p-3  d-md-none" aria-labelledby="mobileSearchDropdown1">
                            <form id="searchForm" method="GET">
                                <div class="mb-2">
                                    <label for="searchType" class="form-label">{{ __('Search Type') }}</label>
                                    <select name="type" id="searchType1" class="">
                                        <option value="job" data-action="{{ route('website.filterJobs') }}">
                                            {{ __('Job') }}</option>
                                        <option value="job_seeker"
                                            data-action="{{ route('website.filterJobSeeker') }}">
                                            {{ __('Job Seeker') }}</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label for="searchQuery" class="form-label">{{ __('Search') }}</label>
                                    <input type="text" name="keyword" id="searchQuery" class="form-control"
                                        placeholder="{{ __('Type to search...') }}">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">{{ __('Search') }}</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="n-header--top__left main-menu">
                        {{-- <div class="mbl-top d-flex align-items-center justify-content-between container position-relative d-lg-none">
                            <div class="d-flex align-items-center">
                                <a href="{{ route('website.home') }}" class="brand-logo" >
                                    <img src="{{ $setting->dark_logo_url }}" alt="logo">
                                </a>
                            </div>

                            <div class="">
                                <div class="d-flex align-items-center ">
                                    <div class="search-icon d-lg-none tw-text-white">
                                        <svg id="mblSearchIcon" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                            <path d="M20.9999 21L16.6499 16.65" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="mblTogglesearch bg-primary-500 rounded">
                                        <form action="{{ route('website.job') }}" method="GET" id="search-form"
                                            class="shadow px-md-5 py-md-3 p-3 !tw-bg-white rounded w-sm-75 w-100">
                                            <div class="form-item">
                                                <input name="keyword" class="search-input w-100" type="text"
                                                    placeholder="{{ __('job_title_keyword') }}"
                                                    value="{{ request('keyword') }}" id="mobile_search_input">
                                            </div>
                                        </form>
                                    </div>
                                    @auth('user')
                                        <ul
                                            class="custom-border list-unstyled d-flex align-items-center justify-content-end">
                                            @if (auth()->user()->role == 'company')
                                                <x-website.company.notifications-component />
                                            @endif

                                            @if (auth()->user()->role == 'candidate')
                                                <x-website.candidate.notifications-component />
                                            @endif

                                            @company
                                                <li class="relative">
                                                    <a href="{{ route('user.dashboard') }} " class="candidate-profile p-0">
                                                        <img src="{{ auth()->check() ? auth()->user()?->company?->logo_url : '' }}"
                                                            alt="company logo">
                                                    </a>
                                                </li>
                                            @else
                                                <li class="relative">
                                                    <a href="{{ route('user.dashboard') }} " class="candidate-profile p-0">
                                                        <img src="{{ auth()->check() ? auth()->user()?->candidate?->photo : '' }}"
                                                            alt="user logo">
                                                    </a>
                                                </li>
                                            @endcompany

                                            @if (!request()->is('email/verify'))
                                                @if (auth()->user()->role !== 'company' && auth()->user()->role !== 'candidate')
                                                    <li>
                                                        <a href="{{ route('company.job.create') }}">
                                                            <button class="btn btn-primary">
                                                                {{ __('post_job') }}
                                                            </button>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endif

                                            @if (request()->is('email/verify'))
                                                <li>
                                                    <a href="{{ route('logout') }}"
                                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                        <button class="btn btn-primary">
                                                            {{ __('log_out') }}
                                                        </button>
                                                    </a>
                                                </li>
                                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                    class="d-none">
                                                    @csrf
                                                </form>
                                            @endif
                                        </ul>
                                    @endauth

                                    @guest
                                        <ul class="list-unstyled">
                                            <li>
                                                <a href="{{ route('company.job.create') }}"
                                                    class="btn btn-primary text-white"
                                                    style="padding:12px 24px !important;">{{ __('post_job') }}
                                                </a>
                                            </li>
                                        </ul>
                                    @endguest
                                </div>
                            </div>
                        </div> --}}
                        @if (auth('user')->check())
                            @if (authUser()->role == 'company')
                                <div class="container">

                                    <ul class="menu-active-classes">
                                        @if (isset($company_menu_lists))
                                            @foreach ($company_menu_lists as $company_menu_list)
                                                <li class="menu-item">
                                                    @php
                                                        // Check if the URL starts with "http" or "https" to identify external links
                                                        $isExternalLink = Str::startsWith($company_menu_list['url'], [
                                                            'http://',
                                                            'https://',
                                                        ]);
                                                    @endphp
                                                    <a href="{{ $company_menu_list['url'] }}"
                                                        @if ($isExternalLink) target="_blank" @endif
                                                        class="{{ urlMatch(url()->current(), url($company_menu_list['url'])) ? 'text-primary active' : '' }}">
                                                        @if ($company_menu_list['title'])
                                                            {{ $company_menu_list['title'] }}
                                                        @else
                                                            @if ($company_menu_list['en_title'])
                                                                {{ $company_menu_list['en_title'] }}
                                                            @endif
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                            @if ($custom_pages->where('show_header', 1)->count() > 0)
                                                <li class="menu-item extra-page d-none d-lg-inline-block">
                                                    <a href="javascript:void(0)" class="dropdown-toggle">
                                                        Extra Pages
                                                    </a>
                                                    <ul class="ll-dropdown-menu">
                                                        @foreach ($custom_pages->where('show_header', 1) as $page)
                                                            <li>
                                                                <a class="!tw-px-5 !tw-py-2"
                                                                    href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endif

                                            @foreach ($custom_pages->where('show_header', 1) as $page)
                                                <li class="d-lg-none">
                                                    <a class=""
                                                        href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                    <div class="tw-mb-post-job">
                                        <a href="{{ route('company.job.create') }}">
                                            <button class="btn btn-primary">
                                                {{ __('post_job') }}
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="container">
                                    <ul class="menu-active-classes ">
                                        @if (isset($candidate_menu_lists))
                                            @foreach ($candidate_menu_lists as $candidate_menu_list)
                                                <li class="menu-item">
                                                    @php
                                                        // Check if the URL starts with "http" or "https" to identify external links
                                                        $isExternalLink = Str::startsWith($candidate_menu_list['url'], [
                                                            'http://',
                                                            'https://',
                                                        ]);
                                                    @endphp
                                                    <a href="{{ $candidate_menu_list['url'] }}"
                                                        @if ($isExternalLink) target="_blank" @endif
                                                        class="{{ urlMatch(url()->current(), url($candidate_menu_list['url'])) ? 'text-primary active' : '' }}">
                                                        @if ($candidate_menu_list['title'])
                                                            {{ $candidate_menu_list['title'] }}
                                                        @else
                                                            @if ($candidate_menu_list['en_title'])
                                                                {{ $candidate_menu_list['en_title'] }}
                                                            @endif
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                            <li class="menu-item extra-page d-none d-lg-inline-block">
                                                <a href="javascript:void(0)" class="dropdown-toggle">
                                                    Extra Pages
                                                </a>
                                                <ul class="ll-dropdown-menu">
                                                    @foreach ($custom_pages->where('show_header', 1) as $page)
                                                        <li>
                                                            <a class="!tw-px-5 !tw-py-2"
                                                                href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                            @foreach ($custom_pages->where('show_header', 1) as $page)
                                                <li class="d-lg-none">
                                                    <a class=""
                                                        href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        @else
                            <div style="display:flex;justify-content:flex-start !important"
                                class="justify-content-start">
                                <!-- <div>
                                    <a href="{{ route('website.home') }}" class="brand-logo mr-4 menu-item mx-2" >
                                        <img src="{{ $setting->dark_logo_url }}" alt="logo" style="max-width: 330px !important;">
                                    </a>
                                </div> -->

                                <div class="container d-flex align-items-center  ml-4">
                                    <!-- Logo -->
                                    <!-- Menu -->
                                    <ul class="menu-active-classes d-flex align-items-center">
                                        @if (isset($public_menu_lists))
                                            @foreach ($public_menu_lists as $public_menu_list)
                                                <li class="menu-item mx-2">
                                                    @php
                                                        // Check if the URL starts with "http" or "https" to identify external links
                                                        $isExternalLink = Str::startsWith($public_menu_list['url'], [
                                                            'http://',
                                                            'https://',
                                                        ]);
                                                    @endphp
                                                    <a href="{{ $public_menu_list['url'] }}"
                                                        @if ($isExternalLink) target="_blank" @endif
                                                        class="{{ urlMatch(url()->current(), url($public_menu_list['url'])) ? 'text-primary active' : '' }}">
                                                        @if ($public_menu_list['title'])
                                                            {{ $public_menu_list['title'] }}
                                                        @elseif ($public_menu_list['en_title'])
                                                            {{ $public_menu_list['en_title'] }}
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach

                                            @if ($custom_pages->where('show_header', 1)->count() > 0)
                                                <li class="menu-item extra-page d-none d-lg-inline-block">
                                                    <a href="javascript:void(0)" class="dropdown-toggle">
                                                        {{ __('extra_pages') }}
                                                    </a>
                                                    <ul class="ll-dropdown-menu">
                                                        @foreach ($custom_pages->where('show_header', 1) as $page)
                                                            <li>
                                                                <a class="!tw-px-5 !tw-py-2"
                                                                    href="{{ route('showCustomPage', $page->slug) }}">
                                                                    {{ $page->title }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </li>
                                            @endif

                                            @foreach ($custom_pages->where('show_header', 1) as $page)
                                                <li class="d-lg-none">
                                                    <a class="{{ urlMatch(url()->current(), url($public_menu_list['url'])) ? 'text-primary active' : '' }}"
                                                        href="{{ route('showCustomPage', $page->slug) }}">{{ $page->title }}</a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            </div>

                        @endif

                        <div class="xs:tw-hidden tw-mt-6 mbl-bottom">
                            <div class="container">
                                @if ($cms_setting?->footer_phone_no)
                                    <div class="contact-info">
                                        <a class="text-gray-900" href="tel:{{ $cms_setting?->footer_phone_no }}">
                                            <x-svg.telephone2-icon />
                                            {{ $cms_setting?->footer_phone_no }}
                                        </a>
                                    </div>
                                @endif
                                @if ($setting->app_country_type === 'multiple_base')
                                    <form action="{{ route('website.job') }}" method="GET" id="search-form">
                                        <div class="tw-flex tw-w-full">
                                            @php
                                                $selected_country = session('selected_country');
                                            @endphp
                                            <div class="dropdown dropup tw-w-full">
                                                <button
                                                    class="btn tw-flex tw-justify-between tw-w-full tw-px-0 dropdown-toggle"
                                                    type="button" id="" data-bs-toggle="dropdown"
                                                    aria-expanded="false">
                                                    <div>
                                                        @if ($selected_country && selected_country())
                                                            <i class="flag-icon {{ selected_country()->icon }}"></i>
                                                            {{ selected_country()->name }}
                                                        @else
                                                            {{ __('all_country') }}
                                                        @endif
                                                    </div>
                                                </button>

                                                <ul class="dropdown-menu mx-height-400 overflow-auto tw-p-2"
                                                    aria-labelledby="dropdownMenuButton1">
                                                    <li>
                                                        <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                            href="{{ route('website.set.country') }}">
                                                            <svg width="26" height="26" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 6h16M4 10h16M4 14h16M4 18h16">
                                                                </path>
                                                            </svg>
                                                            <span class="marginleft">
                                                                {{ __('all_country') }}
                                                            </span>
                                                        </a>
                                                    </li>

                                                    @foreach ($headerCountries as $country)
                                                        <li id="lang-dropdown-item">
                                                            <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                                href="{{ route('website.set.country', ['country' => $country->id]) }}">
                                                                <i class="flag-icon {{ $country->icon }}"></i>
                                                                {{ $country->name }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                                @if (count($headerCurrencies) && $setting->currency_switcher)
                                    @php
                                        $currency_count = count($headerCurrencies) && count($headerCurrencies) > 1;
                                        $current_currency_code = currentCurrencyCode();
                                    @endphp
                                    <div class="dropdown dropup">
                                        <button
                                            class="btn tw-flex tw-w-full tw-justify-between tw-px-0 {{ count($headerCurrencies) ? 'dropdown-toggle' : '' }}"
                                            type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            {{ $current_currency_code }}
                                        </button>
                                        @if ($currency_count)
                                            <ul class="dropdown-menu tw-p-2" aria-labelledby="dropdownMenuButton1">
                                                @foreach ($headerCurrencies as $currency)
                                                    @if ($currency->code != $current_currency_code)
                                                        <li id="lang-dropdown-item">
                                                            <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                                href="{{ route('changeCurrency', $currency->code) }}">
                                                                {{ $currency->code }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class=" d-flex align-items-center">
                        {{-- @if ($cms_setting?->footer_phone_no)
                            <div class="contact-info xs:tw-inline-flex tw-hidden">
                                <a class="text-gray-900" href="tel:{{ $cms_setting?->footer_phone_no }}">
                                    <x-svg.telephone2-icon />
                                    {{ $cms_setting?->footer_phone_no }}
                                </a>
                            </div>
                        @endif --}}
                        @if ($setting->language_changing)
                            <div class="dropdown">
                                @php
                                    $language_count = count($languages) && count($languages) > 1;
                                    $language_count2 = count($languages);
                                    $current_language = currentLanguage() ? currentLanguage() : loadDefaultLanguage();
                                @endphp
                                <button class="btn mobile-btn d-flex {{ $language_count ? 'dropdown-toggle' : '' }} "
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i
                                        class="flag-icon lh-base {{ isset($current_language->icon) && $current_language->icon ? $current_language->icon : '' }}"></i>
                                    <div class="d-none d-md-block ">
                                        {{ isset($current_language->name) && $current_language->name ? $current_language->name : '' }}
                                    </div>
                                </button>
                                @if ($language_count)
                                    <ul class="dropdown-menu mx-height-300 overflow-auto tw-p-2"
                                        aria-labelledby="dropdownMenuButton1">

                                        @foreach ($languages as $lang)
                                            @if (isset($current_language->code) && $current_language->code != $lang->code)
                                                <li id="lang-dropdown-item">
                                                    <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                        href="{{ route('changeLanguage', $lang->code) }}">
                                                        <i
                                                            class="flag-icon {{ isset($lang->icon) && $lang->icon ? $lang->icon : '' }} tw-me-2.5"></i>
                                                        {{ $lang->name }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        @auth('user')
                        <div>

                            @if (auth()->user()->role == 'company')
                                {{-- <x-website.company.notifications-component /> --}}
                            @endif
                            @if (auth()->user()->role == 'candidate')
                                {{-- <x-website.candidate.notifications-component /> --}}
                            @endif

                            {{-- <x-website.company.message-component /> --}}

                            <div class="dropdown dropstart">
                                <a href="javascript:void(0)" class="candidate-profile position-relative"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    @company
                                        <img src="{{ auth()->user()?->company?->logo_url ?? asset('images/default-company.png') }}" alt="logo">
                                    @else
                                        <img src="{{ auth()->check() ? auth()->user()?->candidate?->photo : '' }}" alt="photo">
                                        @if (auth()->user()->candidate && auth()->user()->candidate->status == 'available')
                                            <span class="available-alert-header">
                                                <svg class="circle" width="14" height="14" viewBox="0 0 14 14"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <circle cx="7" cy="7" r="6" fill="#2ecc71"
                                                        stroke="white" stroke-width="2">
                                                    </circle>
                                                </svg>
                                            </span>
                                        @endif
                                    @endcompany
                                </a>
                                @candidate
                                <ul class="custom-border dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('candidate.dashboard') ? 'active' : '' }}"
                                            href="{{ route('candidate.dashboard') }}">
                                            <i class="ph-stack"></i>
                                            {{ __('dashboard') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('candidate.setting') ? 'active' : '' }}"
                                            href="{{ route('candidate.setting') }}">
                                            <i class="ph-gear"></i>
                                            {{ __('settings') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="ph-sign-out"></i>
                                            {{ __('log_out') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                            class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            @else
                                <ul class="dropdown-menu custom-border" aria-labelledby="dropdownMenuButton1">
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.dashboard') ? 'active' : '' }}"
                                            href="{{ route('company.dashboard') }}">
                                            <i class="ph-stack"></i>
                                            {{ __('dashboard') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.myjob') ? 'active' : '' }}"
                                            href="{{ route('company.myjob') }}">
                                            <i class="ph-suitcase-simple"></i>
                                            {{ __('my_jobs') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.plan') ? 'active' : '' }}"
                                            href="{{ route('company.plan') }}">
                                            <i class="ph-notebook"></i>
                                            {{ __('plans_billing') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ request()->routeIs('company.setting') ? 'active' : '' }}"
                                            href="{{ route('company.setting') }}">
                                            <i class="ph-gear"></i>
                                            {{ __('settings') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="ph-sign-out"></i>
                                            {{ __('log_out') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                            class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                                @endcandidate
                            </div>
                            {{-- @if (!request()->is('email/verify'))
                        @company
                        <li class="tw-hidden sm:tw-block">

                            <a href="{{ route('company.job.create') }}">
                                <button class="btn btn-light">
                                    {{ __('post_job') }}
                                </button>
                            </a>
                        </li>
                        @endcompany
                        @endif --}}
                            @if (request()->is('email/verify'))
                                <li>
                                    <a href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <button class="btn btn-primary">
                                            {{ __('log_out') }}
                                        </button>
                                    </a>
                                </li>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                    class="d-none">
                                    @csrf
                                </form>
                            @endif
                        </div>
                    @endauth
                        {{-- @if (count($headerCurrencies) && $setting->currency_switcher)
                            @php
                                $currency_count = count($headerCurrencies) && count($headerCurrencies) > 1;
                                $current_currency_code = currentCurrencyCode();
                            @endphp
                            <div class="dropdown xs:tw-inline-flex tw-hidden">
                                <button class="btn {{ count($headerCurrencies) ? 'dropdown-toggle' : '' }}"
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    {{ $current_currency_code }}
                                </button>
                                @if ($currency_count)
                                    <ul class="dropdown-menu mx-height-300 overflow-auto tw-p-2"
                                        aria-labelledby="dropdownMenuButton1">
                                        @foreach ($headerCurrencies as $currency)
                                            @if ($currency->code != $current_currency_code)
                                                <li id="lang-dropdown-item">
                                                    <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                        href="{{ route('changeCurrency', $currency->code) }}">
                                                        {{ $currency->code }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif
                        @if ($setting->app_country_type === 'multiple_base')
                            <form action="{{ route('website.job') }}" method="GET" id="search-form"
                                class="mx-width-300 xs:tw-inline-flex tw-hidden">
                                <div class="d-flex">
                                    @php
                                        $selected_country = session('selected_country');
                                    @endphp
                                    <div class="">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle" type="button" id=""
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                @if ($selected_country && selected_country())
                                                    <i class="flag-icon {{ selected_country()->icon }}"></i>
                                                    {{ selected_country()->name }}
                                                @else
                                                    {{ __('all_country') }}
                                                @endif
                                            </button>

                                            <ul class="dropdown-menu mx-height-300 overflow-auto tw-p-2"
                                                aria-labelledby="dropdownMenuButton1">
                                                <li>
                                                    <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                        href="{{ route('website.set.country') }}">
                                                        <svg width="26" height="26" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24"
                                                            xmlns="http://www.w3.org/2000/svg">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16">
                                                            </path>
                                                        </svg>
                                                        <span class="marginleft">
                                                            {{ __('all_country') }}
                                                        </span>
                                                    </a>
                                                </li>
                                                @foreach ($headerCountries as $country)
                                                    <li id="lang-dropdown-item">
                                                        <a class="dropdown-item hover:tw-bg-[#F1F2F4] hover:tw-rounded-[4px]"
                                                            href="{{ route('website.set.country', ['country' => $country->id]) }}">
                                                            <i class="flag-icon {{ $country->icon }}"></i>
                                                            {{ $country->name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        @endif --}}
                        @if (!auth()->user())
                            <ul class="d-none d-lg-flex align-items-center gap-1 mt-4 list-unstyled">
                                <!-- Buttons visible only on large screens -->
                                <li>
                                    <a href="{{ route('login') }}"
                                        class="btn btn-primary d-inline-block">{{ __('login') }}</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" class="btn btn-primary btn-sm"
                                    style="padding:10px; font-size:12px;"
                                        data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                                        {{ __('register') }}
                                        </a>
                                </li>
                                <li>
                                    <a href="{{ route('company.job.create') }}"
                                        class="btn btn-primary d-inline-block">{{ __('post_job') }}</a>
                                </li>
                            </ul>

                            <div class="d-none d-md-block d-lg-none">


                                <a href="{{ route('login') }}" class="btn btn-primary btn-sm"
                                    style="padding: 10px 10px 10px; line-height: 8px; font-size: 10px;">
                                    {{ __('login') }}
                                </a>
                                <a href="javascript:void(0)" class="btn btn-primary btn-sm"
                                    style="padding:10px; font-size:12px;"
                                        data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                                        {{ __('register') }}
                                        </a>

                                <a href="{{ route('company.job.create') }}" class="btn btn-primary btn-sm"
                                    style="padding: 10px 10px 10px; line-height: 8px; font-size: 10px;">
                                    {{ __('post_job') }}
                                </a>

                            </div>
                        @endif


                    </div>


                    <div class="mobile-menu">
                        <div class="menu-click tw-pe-3">
                            <button class="effect1">
                                <span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Header top -->
        <div class="d-none d-md-block p-0">
            <div class="tab-bar d-flex">
                <a href="{{ route('register', ['type' => 'employer']) }}" class="btn light-blue w-100">{{ __('employer') }}</a>
                <a href="{{ route('register', ['type' => 'seeker']) }}" class="btn green w-100">{{ __('seeker') }}</a>
                <a href="{{ route('website.about') }}" class="btn red w-100" aria-current="true"> {{ __('About_company') }}</a>
                <a href="{{ route('website.privacyPolicy') }}" class="btn green w-100" aria-current="true"> {{ __('privacy_company') }}</a>
                <a href="https://ogsgroupofficial.com/" class="btn light-blue w-100"  aria-current="true"> {{ __('ogs_group') }}</a>
                <a href="{{ route('website.contact') }}" class="btn blue w-100" aria-current="true">{{ __('contact_company') }}</a>
            </div>
            </div>

        <div class="rt-mobile-menu-overlay"></div>
        <div class="sidebar-overlay"></div>
    </div>

    {{-- Other Section End --}}
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>

    <script>
        document.getElementById('searchType').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const form = document.getElementById('searchForm');
            form.action = selectedOption.getAttribute('data-action');
        });

        // Set the initial action when the page loads
        window.addEventListener('DOMContentLoaded', function() {
            const searchType = document.getElementById('searchType');
            const form = document.getElementById('searchForm');
            const selectedOption = searchType.options[searchType.selectedIndex];
            form.action = selectedOption.getAttribute('data-action');
        });


        document.getElementById('searchType1').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const form = document.getElementById('searchForm');
            form.action = selectedOption.getAttribute('data-action');
        });

        // Set the initial action when the page loads
        window.addEventListener('DOMContentLoaded', function() {
            const searchType = document.getElementById('searchType1');
            const form = document.getElementById('searchForm');
            const selectedOption = searchType.options[searchType.selectedIndex];
            form.action = selectedOption.getAttribute('data-action');
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const currentRoute = window.location.pathname;
            const heroHomeSection = document.getElementById('heroHomeSection');
            const heroOtherSection = document.getElementById('heroOtherSection');


            if (currentRoute == "/") {
                console.log(heroHomeSection);

                heroHomeSection.style.display = 'block';

            } else {
                heroOtherSection.style.display = 'block';
            }
            console.log(currentRoute);
        })

        function redirectToLogin(userType) {
            let loginRoute;

            // Define the login route based on the selected user type
            switch (userType) {
                case 'candidate':
                    loginRoute = "{{ route('login', ['role' => 'candidate']) }}";
                    break;
                case 'agent':
                    loginRoute = "{{ route('login', ['role' => 'agent']) }}";
                    break;
                case 'employer':
                    loginRoute = "{{ route('login', ['role' => 'company']) }}";
                    break;
                default:
                    loginRoute = "{{ route('login') }}";
            }

            // Redirect to the selected login route
            window.location.href = loginRoute;
        }

        function selectTab(tabName) {
            // Remove 'active' class from all tabs and forms
            document.querySelectorAll('.tab-button').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.search-form').forEach(form => form.classList.remove('active'));

            // Add 'active' class to selected tab and corresponding form
            document.getElementById(`tab-${tabName}`).classList.add('active');
            document.getElementById(`form-${tabName}`).classList.add('active');
        }
    </script>

    @include('frontend.partials.register-type-modal')

</header>
