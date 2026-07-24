<style>
    .profile_section {
        display: flex;
        flex-direction: column;
        align-items: center;
        /* Center items horizontally */
        justify-content: center;
        /* Center items vertically */
        padding: 10px;
    }

    .profile_section p {
        color: #fff;
        margin: 5px 0;
        /* Adjust spacing between lines */
        text-align: center;
        /* Center text inside the container */
        white-space: nowrap;
        /* Prevent line breaks */
        overflow: hidden;
        /* Hide overflow text */
        text-overflow: ellipsis;
        /* Add ellipsis (...) if text overflows */
        max-width: 200px;
        /* Set a maximum width for the text */
    }

    .profile_section h6 {
        color: #fff;
    }

    .avatar {
        width: 120px;
        height: 120px;
        background-color: #fff;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        margin-bottom: 10px;
        /* Space between avatar and text */
    }


    /* .avatar img {
         width: 80px;
         height: 80px;
     } */


    .profile-image {
        width: 100px;
        /* Reduced from 120px */
        height: auto;
        border-radius: 50%;
        /* border: 2px solid #147ce4; */
        margin: 10px auto;
        display: block;
    }

    .progress {
        height: 15px;
        background-color: #f3f3f3;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 5px;
    }

    .progress-bar {
        height: 100% !important;
        background-color: #00cc44 !important;
        /* Green color for completed part */
        width: {{ $completionPercentage }}% !important;
    }

    h4 {
        margin-bottom: 5px;
    }
</style>
<aside id="sidebar" class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <img src="{{ $setting->favicon_image_url }}" alt="{{ __('logo') }}" class="elevation-3">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-nav-wrapper">
            @if (!auth()->user()->hasRole('superadmin'))
                <div class="profile_section">

                    <div class="avatar">
                        <img src="{{ asset(auth()->user()->image) }}" alt="image" class="profile-image">
                    </div>

                    <div>
                        <p class="">{{ auth()->user()->name }}</p>
                        <p class="">
                            <i class="fas fa-envelope"></i> {{ auth()->user()->email }}
                        </p>
                        <p class="">
                            <i class="fas fa-phone"></i> {{ auth()->user()->whatsapp }}
                        </p>

                    </div>

                </div>
            @endif
            <!-- Sidebar Menu -->
            <nav class="sidebar-main-nav mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <x-admin.sidebar-list :linkActive="Route::is('admin.dashboard') ? true : false" route="admin.dashboard" parameter=""
                        path="admin.dashboard" plus_icon="" icon="fas fa-tachometer-alt">
                        {{ __('dashboard') }}
                    </x-admin.sidebar-list>
                    <!-- ======= Order ======== -->
                    @if (userCan('order.view'))
                        <li class="nav-header">
                            {{ __('order') }}
                        </li>
                        <x-admin.sidebar-list :linkActive="request()->routeIs('order.index') ? true : false" route="order.index" icon="fas fa-money-bill"
                            path="order.index" plus_icon="">
                            {{ __('order') }}
                        </x-admin.sidebar-list>
                    @endif
                    <!-- ======= Company ======== -->
                    @if (userCan('company.view'))
                        <x-admin.sidebar-list :linkActive="Request::is('admin/company*') ? true : false" route="company.index" path="company.create"
                            plus_icon="fa fa-plus-circle" icon="fas fa-building">
                            {{ __('company') }}
                        </x-admin.sidebar-list>
                    @endif
                    <!-- ======== Candidate ====== -->
                    @if (userCan('candidate.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('candidate*') ? true : false" route="candidate.index" path="candidate.create"
                            plus_icon="fa fa-plus-circle" icon="fas fa-user">
                            {{ __('candidate') }}
                        </x-admin.sidebar-list>
                    @endif
                    @if (auth()->user()->hasRole('superadmin'))
                        <x-admin.sidebar-list :linkActive="Route::is('candidate-status*') ? true : false" route="candidate-status" path="candidate-status"
                            plus_icon="fa fa-plus-circle" icon="fas fa-user">
                            {{ __('Candidate Status') }}
                        </x-admin.sidebar-list>
                        <x-admin.sidebar-list :linkActive="Route::is('edit.plan*') ? true : false" route="edit.plan" path="edit.plan"
                            plus_icon="fa fa-plus-circle" icon="fas fa-credit-card">
                            {{ __('Candidate Plan') }}
                        </x-admin.sidebar-list>

                        <x-admin.sidebar-list :linkActive="Route::is('contracts.edit*') ? true : false" route="contracts.edit" path="contracts.edit"
                            plus_icon="fa fa-plus-circle" icon="fas fa-users">
                            {{ __('Hr Contract') }}
                        </x-admin.sidebar-list>

                        <x-admin.sidebar-list :linkActive="Route::is('agent*') ? true : false" route="agent.index" path="agent.create"
                            plus_icon="fa fa-plus-circle" icon="fas fa-users">
                            {{ __('Hr Solutons') }}
                        </x-admin.sidebar-list>
                    @endif

                    @if (userCan('job.view') ||
                            userCan('job_category.view') ||
                            userCan('job_role.view') ||
                            userCan('plan.view') ||
                            userCan('industry_types.view') ||
                            userCan('professions.view'))
                        <li class="nav-header text-uppercase">{{ __('manage_jobs') }}</li>
                    @endif


                    @if (auth()->user()->hasRole('superadmin'))
                        <x-admin.sidebar-list :linkActive="Route::is('admin.hire.requests') ? true : false" route="admin.hire.requests" path="admin.hire.requests"
                            plus_icon="fa fa-plus-circle" icon="fas fa-user">
                            {{ __('Hiring Requests') }}
                        </x-admin.sidebar-list>
                    @endif
                    <!-- ======= Job ======== -->
                    @if (userCan('job.view'))
                        <x-admin.sidebar-list :linkActive="Request::is('admin/job/*') ||
                        Request::is('admin/job') ||
                        request()->routeIs('admin.job.edited.*')
                            ? true
                            : false" route="job.index" path="job.create"
                            plus_icon="fa fa-plus-circle" icon="fas fa-briefcase">
                            {{ __('jobs') }}
                        </x-admin.sidebar-list>
                    @endif
                    <!-- ======= Applied Job ======== -->
                    @if (auth()->user()->hasRole('superadmin'))

                        @if (userCan('job.view'))
                            <x-admin.sidebar-list :linkActive="Request::is('admin/applied/jobs/*') ||
                            Request::is('admin/applied/jobs') ||
                            request()->routeIs('admin.applied.jobs*')
                                ? true
                                : false" route="applied.jobs" path="job.create"
                                plus_icon="" icon="fas fa-check-circle">
                                {{ __('applied_jobs') }}
                            </x-admin.sidebar-list>
                        @endif
                    @endif
                    <!-- ======= Job Category ======== -->
                    @if (userCan('job_category.view'))
                        <x-admin.sidebar-list :linkActive="Request::is('admin/jobCategory*') ? true : false" route="jobCategory.index" path="jobCategory.index"
                            plus_icon="" icon="fas fa-th">
                            {{ __('job_category') }}
                        </x-admin.sidebar-list>
                    @endif
                    <!-- ======= Job Role ======== -->
                    @if (userCan('job_role.view'))
                        <x-admin.sidebar-list :linkActive="Request::is('admin/jobRole*') ? true : false" route="jobRole.index" path="jobRole.index"
                            plus_icon="" icon="fas fa-user-tie">
                            {{ __('job_role') }}
                        </x-admin.sidebar-list>
                    @endif
                    @if (!auth()->user()->hasRole('superadmin'))
                        <x-admin.sidebar-list :linkActive="Route::is('contract.form*') ? true : false" route="contract.form" path="contract.form"
                            plus_icon="fa fa-plus-circle" icon="fas fa-users">
                            {{ __('Contract') }}
                        </x-admin.sidebar-list>
                    @endif
                    @if (!auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('agent'))
                        @if (userCan('job.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('my.job*') ? true : false" route="my.job" path="my.job"
                        plus_icon="fa fa-plus-circle" icon="fas fa-users">
                        {{ __('my_jobs') }}
                    </x-admin.sidebar-list>
                        @endif
                        <x-admin.sidebar-list :linkActive="Route::is('job.applyJob*') ? true : false" route="job.applyJob" path="job.applyJob"
                            plus_icon="fa fa-plus-circle" icon="fas fa-users">
                            {{ __('Apply Job') }}
                        </x-admin.sidebar-list>
                    @endif
                    @if (Module::collections()->has('Plan') && userCan('plan.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('module.plan.index') ||
                        Route::is('module.plan.create') ||
                        Route::is('module.plan.edit')
                            ? true
                            : false" route="module.plan.index" path="module.plan.create"
                            plus_icon="fa fa-plus-circle" icon="fas fa-credit-card">
                            {{ __('price_plan') }}
                        </x-admin.sidebar-list>
                    @endif

                    @if (userCan('industry_types.view') || userCan('professions.view'))
                        <x-admin.sidebar-dropdown :linkActive="Request::is('admin/industryType*') ||
                        Request::is('admin/profession*') ||
                        Request::is('skill.*') ||
                        Request::routeIs('tags.*') ||
                        request()->routeIs('benefit.*') ||
                        request()->routeIs('admin.candidate.language.*') ||
                        Request::is('admin/skill*') ||
                        Request::is('admin/organizationType*')
                            ? true
                            : false" :subLinkActive="Request::is('admin/industryType*') ||
                        Request::is('admin/profession*') ||
                        request()->routeIs('benefit.*') ||
                        request()->routeIs('tags.*') ||
                        request()->routeIs('admin.candidate.language.*') ||
                        Request::is('admin/skill*') ||
                        Request::is('admin/organizationType*')
                            ? true
                            : false" icon="fas fa-users-cog">
                            @slot('title')
                                {{ __('attributes') }}
                            @endslot
                            @if (userCan('industry_types.view'))
                            @endif
                            <ul class="nav nav-treeview">
                                <!-- ======= Job Title ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/professione*') ? true : false" route="profession.index"
                                    path="profession.index" plus_icon="" icon="fas fa-briefcase">
                                    {{ __('job_title') }}
                                </x-admin.sidebar-list>
                                <!-- ======= Industrytype ======== -->
                                @if (userCan('industry_types.view'))
                                    <x-admin.sidebar-list :linkActive="Request::is('admin/industryType*') ? true : false" route="industryType.index"
                                        path="industryType.index" plus_icon="" icon="fas fa-industry">
                                        {{ __('industry_type') }}
                                    </x-admin.sidebar-list>
                                @endif


                                <!-- ======= professions ======== -->
                                @if (userCan('professions.view'))
                                    <x-admin.sidebar-list :linkActive="Request::is('admin/profession*') ? true : false" route="profession.index"
                                        path="profession.index" plus_icon="" icon="fas fa-id-card">
                                        {{ __('professions') }}
                                    </x-admin.sidebar-list>
                                @endif

                                <!-- ======= skills ======== -->
                                @if (userCan('skills.view'))
                                    <x-admin.sidebar-list :linkActive="request()->routeIs('skill.*') ? true : false" route="skill.index" path="skill.index"
                                        plus_icon="" icon="fas fa-cog">
                                        {{ __('skills') }}
                                    </x-admin.sidebar-list>
                                @endif
                                <!-- ======= tags ======== -->
                                @if (userCan('tags.view'))
                                    <x-admin.sidebar-list :linkActive="request()->routeIs('tags.*') ? true : false" route="tags.index" path="tags.index"
                                        plus_icon="" icon="fas fa-tags">
                                        {{ __('tags') }}
                                    </x-admin.sidebar-list>
                                @endif
                                <!-- ======= benefits ======== -->
                                @if (userCan('benefits.view'))
                                    <x-admin.sidebar-list :linkActive="request()->routeIs('benefit.*') ? true : false" route="benefit.index"
                                        path="benefit.index" plus_icon="" icon="fas fa-bullseye">
                                        {{ __('benefits') }}
                                    </x-admin.sidebar-list>
                                @endif
                                <!-- ======= candidate language ======== -->
                                @if (userCan('candidate-language.view'))
                                    <x-admin.sidebar-list :linkActive="request()->routeIs('admin.candidate.language.*') ? true : false" route="admin.candidate.language.index"
                                        path="admin.candidate.language.index" plus_icon="" icon="fas fa-language">
                                        {{ __('language') }}
                                    </x-admin.sidebar-list>
                                @endif

                                <!-- ======= organization_type ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/organizationType*') ? true : false" route="organizationType.index"
                                    path="organizationType.index" plus_icon="" icon="fas fa-industry">
                                    {{ __('organization_type') }}
                                </x-admin.sidebar-list>

                                <!-- ======= salary_type ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/salaryType*') ? true : false" route="salaryType.index"
                                    path="salaryType.index" plus_icon="" icon="fas fa-money-bill">
                                    {{ __('salary_type') }}
                                </x-admin.sidebar-list>

                                <!-- ======= education ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/education*') ? true : false" route="education.index"
                                    path="education.index" plus_icon="" icon="fas fa-school">
                                    {{ __('education') }}
                                </x-admin.sidebar-list>

                                <!-- ======= experience ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/experience*') ? true : false" route="experience.index"
                                    path="experience.index" plus_icon="" icon="fas fa-briefcase">
                                    {{ __('experience') }}
                                </x-admin.sidebar-list>

                                <!-- ======= team_size ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/teamSize*') ? true : false" route="teamSize.index" path="teamSize.index"
                                    plus_icon="" icon="fas fa-users">
                                    {{ __('team_size') }}
                                </x-admin.sidebar-list>

                                <!-- ======= job_type ======== -->
                                <x-admin.sidebar-list :linkActive="Request::is('admin/jobType*') ? true : false" route="jobType.index" path="jobType.index"
                                    plus_icon="" icon="fas fa-tasks">
                                    {{ __('job_type') }}
                                </x-admin.sidebar-list>
                            </ul>
                        </x-admin.sidebar-dropdown>
                    @endif

                    @if (userCan('post.view') ||
                            userCan('country.view') ||
                            userCan('state.view') ||
                            userCan('city.view') ||
                            userCan('newsletter.view') ||
                            userCan('newsletter.sendmail') ||
                            userCan('contact.view') ||
                            userCan('testimonial.view') ||
                            userCan('admin.view'))
                        <li class="nav-header text-uppercase">{{ __('others') }}</li>
                    @endif
                    <!-- ======== Blog ====== -->
                    @if (Module::collections()->has('Blog'))
                        @if (userCan('post.view'))
                            <x-admin.sidebar-list :linkActive="Route::is('module.blog.*') || Route::is('module.category.*') ? true : false" route="module.blog.index" parameter=""
                                path="module.blog.create" plus_icon="fa fa-plus-circle" icon="fas fa-blog">
                                {{ __('blog') }}
                            </x-admin.sidebar-list>
                        @endif
                    @endif

                    <!--=========  Locations ========= -->
                    @if (Module::collections()->has('Location'))
                        @if (userCan('post.view'))
                            <x-admin.sidebar-list :linkActive="Route::is('module.country.*') ? true : false" route="module.country.index" parameter=""
                                path="module.country.create" plus_icon="fa fa-plus-circle" icon="fas fa-map-marker">
                                {{ __('country') }}
                            </x-admin.sidebar-list>
                        @endif
                    @endif

                    <!--=========== News Letter ========= -->
                    {{-- @if (Module::collections()->has('Newsletter'))
                        @if (userCan('newsletter.view') || userCan('newsletter.sendmail'))
                            <x-admin.sidebar-dropdown :linkActive="Route::is('module.newsletter*') ? true : false" :subLinkActive="Route::is('module.newsletter*') ? true : false"
                                icon="nav-icon fas fa-envelope">
                                @slot('title')
                                    {{ __('newsletter') }}
                                @endslot
                                @if (userCan('newsletter.view'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('module.newsletter.index') ? true : false" route="module.newsletter.index"
                                            path="module.newsletter.index" plus_icon="" icon="fas fa-circle">
                                            {{ __('email_list') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                                @if (userCan('newsletter.sendmail'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('module.newsletter.send_mail') ? true : false" route="module.newsletter.send_mail"
                                            path="module.newsletter.send_mail" plus_icon="" icon="fas fa-circle">
                                            {{ __('send_mail') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                            </x-admin.sidebar-dropdown>
                        @endif
                    @endif --}}

                    @if (Module::collections()->has('Testimonial') && userCan('testimonial.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('module.testimonial.index') ||
                        Route::is('module.testimonial.create') ||
                        Route::is('module.testimonial.edit')
                            ? true
                            : false" route="module.testimonial.index"
                            path="module.testimonial.create" plus_icon="fa fa-plus-circle" icon="fas fa-star">
                            {{ __('testimonial') }}
                        </x-admin.sidebar-list>
                    @endif
                    @if (userCan('faq.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('module.faq.index') ||
                        Route::is('module.faq.create') ||
                        Route::is('module.faq.edit') ||
                        Route::is('module.faq.category.index') ||
                        Route::is('module.faq.category.create') ||
                        Route::is('module.faq.category.edit')
                            ? true
                            : false" route="module.faq.index" path="module.faq.create"
                            plus_icon="fa fa-plus-circle" icon="fas fa-question-circle">
                            {{ __('faq') }}
                        </x-admin.sidebar-list>
                    @endif

                    <!--=========== User Role And Permission ========= -->
                    @if (userCan('admin.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('user.*') ? true : false" route="user.index" path="user.create"
                            plus_icon="fa fa-plus-circle" parameter="" icon="fas fa-users">
                            {{ __('user_role_manage') }}
                        </x-admin.sidebar-list>
                    @endif
                    <!--=========== User Role And OTP method ========= -->
                    @if (userCan('admin.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('user.*') ? true : false" route="roles.otp-methods.index" path="roles.otp-methods.index"
                            plus_icon="" parameter="" icon="fas fa-user-lock">
                            Role & OTP Method
                        </x-admin.sidebar-list>
                    @endif
                    <!--=========== Custom Email Module ========= -->
                    @if (Module::collections()->has('Newsletter'))
                        @if (userCan('newsletter.view') || userCan('newsletter.sendmail'))
                            <x-admin.sidebar-dropdown :linkActive="Route::is('module.newsletter*') ? true : false" :subLinkActive="Route::is('module.newsletter*') ? true : false"
                                icon="nav-icon fas fa-envelope">
                                @slot('title')
                                    {{ __('email') }}
                                @endslot
                                @if (userCan('newsletter.view'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('module.newsletter.index') ? true : false" route="module.newsletter.index"
                                            path="module.newsletter.index" plus_icon="" icon="fas fa-circle">
                                            {{ __('email_list') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                                @if (userCan('newsletter.sendmail'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('module.newsletter.send_mail') ? true : false" route="module.newsletter.send_mail"
                                            path="module.newsletter.send_mail" plus_icon="" icon="fas fa-circle">
                                            {{ __('send_mail') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                                @if (userCan('newsletter.sendmail'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('module.newsletter.send_mail') ? true : false" route="module.newsletter.send_mail"
                                            path="module.newsletter.send_mail" plus_icon="" icon="fas fa-circle">
                                            {{ __('email_templates') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                            </x-admin.sidebar-dropdown>
                        @endif
                    @endif

                    <!--=========== Custom Whatsapp Module ========= -->
                    @if (Module::collections()->has('Newsletter'))
                        @if (userCan('newsletter.view') || userCan('newsletter.sendmail'))
                            <x-admin.sidebar-dropdown :linkActive="Route::is('module.newsletter*') ? true : false" :subLinkActive="Route::is('module.newsletter*') ? true : false"
                                icon="nav-icon fas fa-phone">
                                @slot('title')
                                    {{ __('whatsapp') }}
                                @endslot
                                @if (userCan('newsletter.view'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('whatsapp.candidate') ? true : false" route="whatsapp.candidate"
                                            path="whatsapp.candidate" plus_icon="" icon="fas fa-circle">
                                            {{ __('send_whatsapp') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                                @if (userCan('newsletter.sendmail'))
                                    <ul class="nav nav-treeview">
                                        <x-admin.sidebar-list :linkActive="Route::is('module.newsletter.send_mail') ? true : false" route="module.newsletter.send_mail"
                                            path="module.newsletter.send_mail" plus_icon="" icon="fas fa-circle">
                                            {{ __('whatsapp_templates') }}
                                        </x-admin.sidebar-list>
                                    </ul>
                                @endif
                            </x-admin.sidebar-dropdown>
                        @endif
                    @endif
                </ul>



            </nav>
            <!-- Sidebar Menu -->
            <nav class="mt-2 nav-footer pt-3 border-top border-secondary">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" role="menu"
                    data-accordion="false">
                    <li class="nav-item">
                        <a target="_blank" href="/" class="nav-link bg-primary text-light">
                            <i class="nav-icon fas fa-globe"></i>
                            <p>{{ __('visit_website') }}</p>
                        </a>
                    </li>
                    @if (userCan('setting.view'))
                        <x-admin.sidebar-list :linkActive="Route::is('admin.footer.*') ? true : false" route="admin.footer.index" path="admin.footer.index"
                            plus_icon="" icon="fas fa-shoe-prints">
                            Footer CMS
                        </x-admin.sidebar-list>
                        <x-admin.sidebar-list :linkActive="request()->is('admin/about*') ? true : false" route="admin.about.dashboard" path="admin.about.dashboard"
                            plus_icon="" icon="fas fa-info-circle">
                            About CMS
                        </x-admin.sidebar-list>
                        <x-admin.sidebar-list :linkActive="request()->is('admin/settings/*') ? true : false" route="settings.general" path="settings.general"
                            plus_icon="" icon="fas fa-cog">
                            {{ __('settings') }}
                        </x-admin.sidebar-list>
                    @endif
                    <li class="nav-item">
                        <a href="javascript:void(0" class="nav-link"
                            onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>{{ __('log_out') }} </p>
                        </a>
                        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST"
                            class="d-none invisible">
                            @csrf
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <!-- /.sidebar -->
</aside>
