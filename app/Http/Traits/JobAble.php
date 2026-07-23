<?php

namespace App\Http\Traits;

use App\Models\Benefit;
use App\Models\BenefitTranslation;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobRole;
use App\Models\JobType;
use App\Models\JobTypeTranslation;
use App\Models\Skill;
use App\Models\SkillTranslation;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Notifications\Website\Candidate\ApplyJobNotification;
use App\Notifications\Website\Candidate\BookmarkJobNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Modules\Location\Entities\Country;

trait JobAble
{
    protected function getJobs($request)
    {
        $filteredJobs = $this->filterJobs($request)->latest();
        $featured_jobs = $this->filterJobs($request)->latest()->where('featured', 1)->publicListing()->deadlineActive()
            ->take(18)->get();
        $jobs = $filteredJobs->publicListing()->paginate(18)->withQueryString();

        return [
            'total_jobs' => $jobs->total(),
            'jobs' => $jobs,
            'featured_jobs' => $featured_jobs,
            'countries' => Country::all(['id', 'name', 'slug']),
            'categories' => JobCategory::all()->sortBy('name'),
            'job_roles' => JobRole::all()->sortBy('name'),
            'max_salary' => \DB::table('jobs')->max('max_salary'),
            'min_salary' => \DB::table('jobs')->max('min_salary'),
            'experiences' => Experience::all(),
            'educations' => Education::all(),
            'job_types' => JobType::all(),
            'skills' => Skill::all()->sortBy('name'),
            'popularTags' => $this->popularTags(),
        ];
    }

    protected function moreJobs($request)
    {
        $jobs = $this->filterJobs($request)->latest()->take(18)->get();

        return $jobs;
    }

    private function filterJobs($request)
    {
        if (auth()->user()) {
            $query = Job::with('company.user', 'category', 'job_type:id')
                ->withCount([
                    'bookmarkJobs',
                    'appliedJobs',
                    'bookmarkJobs as bookmarked' => function ($q) {
                        $q->where('candidate_id', currentCandidate() ? currentCandidate()->id : '');
                    },
                    'appliedJobs as applied' => function ($q) {
                        $q->where('candidate_id', currentCandidate() ? currentCandidate()->id : '');
                    },
                ])
                ->active()
                ->withoutEdited();
        } else {
            $query = Job::with('company.user', 'category', 'job_type:id')
                ->withCount([
                    'bookmarkJobs',
                    'appliedJobs',
                    'bookmarkJobs as bookmarked' => function ($q) {
                        $q->where('candidate_id', '');
                    },
                    'appliedJobs as applied' => function ($q) {
                        $q->where('candidate_id', '');
                    },
                ])
                ->withoutEdited()
                ->active();
        }

        $query = applyCandidateAgeFilter($query);

        // company search
        if ($request->has('company') && $request->company != null) {
            $company = $request->company;
            $query->whereHas('company.user', function ($q) use ($company) {
                $q->where('username', $company);
            });
        }

        // Category filter (multi-select from drawer or legacy single value)
        if ($request->has('categories') && is_array($request->categories)) {
            $categorySlugs = array_filter($request->categories);
            if (! empty($categorySlugs)) {
                $categoryIds = JobCategory::whereIn('slug', $categorySlugs)->pluck('id');
                if ($categoryIds->isNotEmpty()) {
                    $query->whereIn('category_id', $categoryIds);
                }
            }
        } elseif ($request->has('category') && $request->category != null) {
            $category = JobCategory::where('slug', $request->category)->first();
            $query->when($category, function ($query) use ($category) {
                return $query->where('category_id', $category->id);
            });
        }

        // job role filter
        if ($request->has('job_role') && $request->job_role != null) {
            $query->where('role_id', $request->job_role);
        }

        // Salary filter
        if ($request->has('price_min') && $request->price_min != null) {
            $query->where('min_salary', '>=', $request->price_min);
        }
        if ($request->has('price_max') && $request->price_max != null) {
            $query->where('max_salary', '<=', $request->price_max);
        }

        // tags filter
        if ($request->has('tag') && $request->tag != null) {
            $tag = TagTranslation::where('name', $request->tag)->first();

            if ($tag) {
                $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('job_tag.tag_id', $tag->tag_id);
                });
            }
        }

        // id filter for load more
        if ($request->id) {
            $query->where('id', '<', $request->id);
        }

        // Location
        $final_address = '';
        if ($request->has('location') && $request->location != null) {
            $address = $request->location;

            // Replace special characters and split the location by commas or plus signs
            $address_array = preg_split('/[,+\s]+/', $address);
            $address_array = array_map('trim', $address_array); // Trim spaces
            $address_array = array_filter($address_array); // Remove empty elements

            // Prepare the query to match any part of the address
            $query->where(function ($query) use ($address_array) {
                foreach ($address_array as $part) {
                    $query->orWhere('country', 'LIKE', '%'.$part.'%')
                        ->orWhere('address', 'LIKE', '%'.$part.'%');
                }
            });
        }

        // Keyword search
        if ($request->has('keyword') && $request->keyword != null) {
            session(['header_search_role' => 'job']);

            $keyword = $request->get('keyword');
            if (is_array($keyword)) {
                $keyword = $keyword[0];
            }

            $query->where(function ($q) use ($keyword, $final_address) {
                $q->where('title', 'LIKE', "%$keyword%")->orWhere('description', 'LIKE', "%$keyword%");

                // Adding location constraint to keyword search
                if (! empty($final_address)) {
                    $q->orWhere('address', 'LIKE', '%'.$final_address.'%');
                }
            });
        }

        // lat Long
        if ($request->has('lat') && $request->has('long') && $request->lat != null && $request->long != null) {
            session()->forget('selected_country');
            // $query->Where('address', $final_address ? $final_address : '')->orWhere('country', $request->location ? $request->location : '');
        }

        // country
        $query = applyJobCountryScope($query);

        // Sort by ads
        if ($request->has('sort_by') && $request->sort_by != null) {
            switch ($request->sort_by) {
                case 'latest':
                    $query->latest('id');
                    break;
                case 'featured':
                    $query->where('featured', 1)->latest();
                    break;
            }
        }

        // Experience filter
        if ($request->has('experience') && $request->experience != null) {
            $experienceNames = array_filter((array) $request->experience);
            if (! empty($experienceNames)) {
                $experienceIds = Experience::whereIn('name', $experienceNames)->pluck('id');
                if ($experienceIds->isNotEmpty()) {
                    $query->whereIn('experience_id', $experienceIds);
                }
            }
        }

        // Education filter
        if ($request->has('education') && $request->education != null) {
            $educationNames = array_filter((array) $request->education);
            if (! empty($educationNames)) {
                $educationIds = Education::whereIn('name', $educationNames)->pluck('id');
                if ($educationIds->isNotEmpty()) {
                    $query->whereIn('education_id', $educationIds);
                }
            }
        }

        // Work type filter
        if ($request->has('is_remote') && $request->is_remote != null) {
            $query->where('is_remote', 1);
        }

        // Job type filter
        if ($request->has('job_type') && $request->job_type != null) {
            $jobTypeNames = array_filter((array) $request->job_type);
            if (! empty($jobTypeNames)) {
                $jobTypeIds = JobTypeTranslation::whereIn('name', $jobTypeNames)->pluck('job_type_id');
                if ($jobTypeIds->isNotEmpty()) {
                    $query->whereIn('job_type_id', $jobTypeIds);
                }
            }
        }

        // Skills filter
        if ($request->has('skills') && $request->skills != null) {
            $skillNames = array_filter((array) $request->skills);

            if (! empty($skillNames)) {
                $skillIds = Skill::query()
                    ->whereHas('translations', function ($q) use ($skillNames) {
                        $q->whereIn('name', $skillNames);
                    })
                    ->pluck('id');

                if ($skillIds->isNotEmpty()) {
                    $query->whereHas('skills', function ($q) use ($skillIds) {
                        $q->whereIn('skills.id', $skillIds);
                    });
                }
            }
        }

        return $query;
    }

    private function getJobsCategory($request, $slug)
    {
        if (auth()->user()) {
            $query = Job::with('company.user', 'job_type:id')
                ->withCount([
                    'bookmarkJobs',
                    'appliedJobs',
                    'bookmarkJobs as bookmarked' => function ($q) {
                        $q->where('candidate_id', currentCandidate() ? currentCandidate()->id : '');
                    },
                    'appliedJobs as applied' => function ($q) {
                        $q->where('candidate_id', currentCandidate() ? currentCandidate()->id : '');
                    },
                ])
                ->active()
                ->withoutEdited();
        } else {
            $query = Job::with('company.user', 'job_type:id')
                ->withCount([
                    'bookmarkJobs',
                    'appliedJobs',
                    'bookmarkJobs as bookmarked' => function ($q) {
                        $q->where('candidate_id', '');
                    },
                    'appliedJobs as applied' => function ($q) {
                        $q->where('candidate_id', '');
                    },
                ])
                ->withoutEdited()
                ->active();
        }

        $query = applyCandidateAgeFilter($query);

        // company search
        if ($request->has('company') && $request->company != null) {
            $company = $request->company;
            $query->whereHas('company.user', function ($q) use ($company) {
                $q->where('username', $company);
            });
        }

        // Category filter

        $id = JobCategory::where('slug', $slug)->first();
        if ($id == null) {
            abort(404);
        } else {
            $category_id = $id->id;
            $query->where('category_id', $category_id);
        }

        // job role filter
        if ($request->has('job_role') && $request->job_role != null) {
            $query->where('role_id', $request->job_role);
        }

        // Salery filter
        if ($request->has('price_min') && $request->price_min != null) {
            $query->where('min_salary', '>=', $request->price_min);
        }
        if ($request->has('price_max') && $request->price_max != null) {
            $query->where('max_salary', '<=', $request->price_max);
        }

        // tags filter
        if ($request->has('tag') && $request->tag != null) {
            $tag = TagTranslation::where('name', $request->tag)->first();

            if ($tag) {
                $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('job_tag.tag_id', $tag->tag_id);
                });
            }
        }

        // location
        $final_address = '';
        if ($request->has('location') && $request->location != null) {
            $address = $request->location;

            // Replace special characters and split the location by commas or plus signs
            $address_array = preg_split('/[,+\s]+/', $address);
            $address_array = array_map('trim', $address_array); // Trim spaces
            $address_array = array_filter($address_array); // Remove empty elements

            // Prepare the query to match any part of the address
            $query->where(function ($query) use ($address_array) {
                foreach ($address_array as $part) {
                    $query->orWhere('country', 'LIKE', '%'.$part.'%')
                        ->orWhere('address', 'LIKE', '%'.$part.'%');
                }
            });
        }

        $final_address = '';
        // Keyword search
        if ($request->has('keyword') && $request->keyword != null) {
            session(['header_search_role' => 'job']);

            $keyword = $request->get('keyword');
            if (is_array($keyword)) {
                $keyword = $keyword[0];
            }

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%$keyword%")->orWhere('description', 'LIKE', "%$keyword%");

            });

        }

        // lat Long
        // if ($request->has('lat') && $request->has('long') && $request->lat != null && $request->long != null) {
        //     session()->forget('selected_country');
        //     $query->Where('address', $final_address ? $final_address : '')->orWhere('country', $request->location ? $request->location : '');
        // }
        // country
        $query = applyJobCountryScope($query);

        // Sort by ads
        if ($request->has('sort_by') && $request->sort_by != null) {
            switch ($request->sort_by) {
                case 'latest':
                    $query->latest('id');
                    break;
                case 'featured':
                    $query->where('featured', 1)->latest();
                    break;
            }
        }

        // Experience filter
        if ($request->has('experience') && $request->experience != null) {
            $experienceNames = array_filter((array) $request->experience);
            if (! empty($experienceNames)) {
                $experienceIds = Experience::whereIn('name', $experienceNames)->pluck('id');
                if ($experienceIds->isNotEmpty()) {
                    $query->whereIn('experience_id', $experienceIds);
                }
            }
        }

        // Education filter
        if ($request->has('education') && $request->education != null) {
            $educationNames = array_filter((array) $request->education);
            if (! empty($educationNames)) {
                $educationIds = Education::whereIn('name', $educationNames)->pluck('id');
                if ($educationIds->isNotEmpty()) {
                    $query->whereIn('education_id', $educationIds);
                }
            }
        }

        // Work type filter
        if ($request->has('is_remote') && $request->is_remote != null) {
            $query->where('is_remote', 1);
        }

        // Job type filter
        if ($request->has('job_type') && $request->job_type != null) {
            $jobTypeNames = array_filter((array) $request->job_type);
            if (! empty($jobTypeNames)) {
                $jobTypeIds = JobTypeTranslation::whereIn('name', $jobTypeNames)->pluck('job_type_id');
                if ($jobTypeIds->isNotEmpty()) {
                    $query->whereIn('job_type_id', $jobTypeIds);
                }
            }
        }
        $jobs = (clone $query)->publicListing()->latest()->paginate(20)->withQueryString();
        $featured_jobs = (clone $query)->publicListing()->latest()->where('featured', 1)->take(20)->get();

        return [
            'total_jobs' => $jobs->total(),
            'jobs' => $jobs,
            'featured_jobs' => $featured_jobs,
            'countries' => Country::all(['id', 'name', 'slug']),
            'categories' => JobCategory::all()->sortBy('name'),
            'job_roles' => JobRole::all()->sortBy('name'),
            'max_salary' => \DB::table('jobs')->max('max_salary'),
            'min_salary' => \DB::table('jobs')->max('min_salary'),
            'experiences' => Experience::all(),
            'educations' => Education::all(),
            'job_types' => JobType::all(),
            'popularTags' => $this->popularTags(),
        ];
    }

    private function getJobDetails($job)
    {
        $candidateId = (auth('user')->check() && authUser()->role === 'candidate' && currentCandidate())
            ? currentCandidate()->id
            : null;

        $jobRelations = [
            'benefits',
            'education',
            'experience',
            'skills',
            'tags',
            'role',
            'questions',
            'job_type',
            'company.user' => function ($q) {
                return $q->with('contactInfo', 'socialInfo');
            },
        ];

        $job_details = $job
            ->load($jobRelations)
            ->loadCount([
                'bookmarkJobs',
                'appliedJobs',
                'bookmarkJobs as bookmarked' => function ($q) use ($candidateId) {
                    $q->where('candidate_id', $candidateId ?: 0);
                },
                'appliedJobs as applied' => function ($q) use ($candidateId) {
                    $q->where('candidate_id', $candidateId ?: 0);
                },
            ]);

        // Blade only shows 5 related jobs — do not hydrate 12–18 full graphs
        $related_jobs_query = Job::query()
            ->withoutEdited()
            ->active()
            ->select([
                'id', 'title', 'slug', 'company_id', 'category_id', 'job_type_id',
                'country', 'featured', 'highlight', 'created_at', 'deadline', 'status',
            ])
            ->with([
                // name lives on job_type_translations (Translatable) — never select job_types.name
                'job_type',
                'company:id,user_id,logo',
                'company.user:id,name',
            ])
            ->where('id', '!=', $job->id)
            ->where('category_id', $job->category_id);

        $related_jobs_query = applyCandidateAgeFilter($related_jobs_query);

        $setting = loadSetting();
        if ($setting->app_country_type == 'single_base') {
            if ($setting->app_country) {
                $country = Country::where('id', $setting->app_country)->first();
                if ($country) {
                    $related_jobs_query->where('country', 'LIKE', "%{$country->name}%");
                }
            }
        } else {
            $selected_country = session()->get('selected_country');
            if ($selected_country && $selected_country != null) {
                $country = selected_country()->name;
                $related_jobs_query->where('country', 'LIKE', "%{$country}%");
            }
        }

        $related_jobs = $related_jobs_query
            ->latest()
            ->limit(5)
            ->get();

        $resumes = $candidateId ? currentCandidate()->resumes : [];

        return [
            'job' => $job_details,
            'related_jobs' => $related_jobs,
            'resumes' => $resumes,
        ];
    }

    private function getIndeedJobs(Request $request, $limit = null, $page = null)
    {
        return [];
        if (config('templatecookie.default_job_provider') == 'indeed' && config('templatecookie.indeed_id')) {
            $keyword = $request->keyword ?? '';
            // $id = JobCategory::where('slug', $request->category)->first();
            // $category_id = $id->id;
            // $category = $category_id ? JobCategory::whereId($category_id)->first() : '';
            // $role = $request->job_role ? JobRole::whereId($category_id)->first() : '';
            $keywords = $keyword;
            $location = $request->location;

            $q = $keywords;
            $l = $location ?? '';
            $limit = $limit ?? (config('templatecookie.indeed_limit') ?? 10);
            $start = '';
            $end = '';
            $sort = 'date';
            $jt = '';
            $fromage = '';
            $radius = '';
            $data = [
                'publisher' => config('templatecookie.indeed_id'),
                'v' => 2,
                'format' => 'json',
                'q' => $q,
                'l' => $l,
                'jt' => $jt,
                'fromage' => $page ?? $fromage,
                'limit' => $limit,
                'start' => $start,
                'end' => $end,
                'radius' => $radius,
                'sort' => $sort,
                'highlight' => 1,
                'filter' => 1,
                // 'latlong' => 1,
                // 'co' => 'uk',
                // 'co' => 'United Kingdom'
            ];
            $param = http_build_query($data)."\n";
            $url = 'http://api.indeed.com/ads/apisearch?'.$param;

            header('Content-type: application/json');
            $obj = file_get_contents($url);
            $json_decode = json_decode($obj);

            return $json_decode;
        }

        return [];
    }

    private function getIndeedJobsCategory(Request $request, $limit, $page, $slug)
    {
        return [];
        if (config('templatecookie.default_job_provider') == 'indeed' && config('templatecookie.indeed_id')) {
            $keyword = $request->keyword ?? '';
            $id = JobCategory::where('slug', $slug)->first();
            $category_id = $id->id;
            $category = $category_id ? JobCategory::whereId($category_id)->first() : '';
            $role = $request->job_role ? JobRole::whereId($category_id)->first() : '';
            $keywords = $keyword ? $keyword : ($category ? $category->name : ($role ? $role->name : 'job'));
            $location = $request->location;

            $q = $keywords;
            $l = $location ?? '';
            $limit = $limit ?? (config('templatecookie.indeed_limit') ?? 10);
            $start = '';
            $end = '';
            $sort = 'date';
            $jt = '';
            $fromage = '';
            $radius = '';
            $data = [
                'publisher' => config('templatecookie.indeed_id'),
                'v' => 2,
                'format' => 'json',
                'q' => $q,
                'l' => $l,
                'jt' => $jt,
                'fromage' => $page ?? $fromage,
                'limit' => $limit,
                'start' => $start,
                'end' => $end,
                'radius' => $radius,
                'sort' => $sort,
                'highlight' => 1,
                'filter' => 1,
                // 'latlong' => 1,
                // 'co' => 'uk',
                // 'co' => 'United Kingdom'
            ];
            $param = http_build_query($data)."\n";
            $url = 'http://api.indeed.com/ads/apisearch?'.urlencode($param);

            header('Content-type: application/json');
            $obj = file_get_contents($url);
            $json_decode = json_decode($obj);

            return $json_decode;
        }

        return [];
    }

    public function getCareerjetJobs(Request $request, $pagesize = null, $page = null)
    {
        if (config('templatecookie.default_job_provider') == 'careerjet' && config('templatecookie.careerjet_id')) {
            $keyword = $request->keyword ?? '';
            // $id = JobCategory::where('slug', $slug)->first();
            // $category_id = $id->id;
            // $category = $category_id ? JobCategory::whereId($category_id)->first() : '';
            // $role = $request->job_role ? JobRole::whereId($category_id)->first() : '';
            $keywords = $keyword;
            $location = $request->location;

            $page = $page ?? 1;
            $pagesize = $pagesize ?? (config('templatecookie.careerjet_limit') ?? 10);
            $result = $this->search([
                'keywords' => $keywords ?? '',
                'location' => $location ?? '',
                'page' => $page,
                'sort' => 'date',
                'pagesize' => $pagesize,
                'affid' => config('templatecookie.careerjet_id'),
            ]);

            return $result;
        }

        return [];
    }

    public function getCareerjetJobsCategory(Request $request, $pagesize, $page, $slug)
    {
        if (config('templatecookie.default_job_provider') == 'careerjet' && config('templatecookie.careerjet_id')) {
            $keyword = $request->keyword ?? '';
            $id = JobCategory::where('slug', $slug)->first();
            $category_id = $id->id;
            $category = $category_id ? JobCategory::whereId($category_id)->first() : '';
            $role = $request->job_role ? JobRole::whereId($category_id)->first() : '';
            $keywords = $keyword ? $keyword : ($category ? $category->name : ($role ? $role->name : 'job'));
            $location = $request->location;

            $page = $page ?? 1;
            $pagesize = $pagesize ?? (config('templatecookie.careerjet_limit') ?? 10);
            $result = $this->search([
                'keywords' => $keywords ?? '',
                'location' => $location ?? '',
                'page' => $page,
                'sort' => 'date',
                'pagesize' => $pagesize,
                'affid' => config('templatecookie.careerjet_id'),
            ]);

            return $result;
        }

        return [];
    }

    public function call($fname, $args)
    {
        $locale = config('templatecookie.careerjet_default_locale');
        $url = 'http://public.api.careerjet.net/'.$fname.'?locale_code='.$locale;

        if (empty($args['affid'])) {
            return (object) [
                'type' => 'ERROR',
                'error' => "Your Careerjet affiliate ID needs to be supplied. If you don't ".'have one, open a free Careerjet partner account.',
            ];
        }

        foreach ($args as $key => $value) {
            $url .= '&'.$key.'='.urlencode($value);
        }

        if (empty($_SERVER['REMOTE_ADDR'])) {
            return (object) [
                'type' => 'ERROR',
                'error' => 'not running within a http server',
            ];
        }

        $ip =
            $_SERVER['HTTP_CLIENT_IP'] ??
            ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? // when behind cloudflare
                ($_SERVER['HTTP_X_FORWARDED'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['HTTP_FORWARDED'] ?? ($_SERVER['HTTP_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'))))));

        $url .= '&user_ip='.$ip;
        $url .= '&user_agent='.urlencode($_SERVER['HTTP_USER_AGENT']);

        // determine current page
        $current_page_url = '';
        if (! empty($_SERVER['SERVER_NAME']) && ! empty($_SERVER['REQUEST_URI'])) {
            $current_page_url = 'http';
            if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $current_page_url .= 's';
            }
            $current_page_url .= '://';

            if (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
                $current_page_url .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
            } else {
                $current_page_url .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            }
        }

        $version = '3.6';
        $header = 'User-Agent: careerjet-api-client-v'.$version.'-php-v'.phpversion();
        if ($current_page_url) {
            $header .= "\nReferer: ".$current_page_url;
        }

        header('Content-type: application/json');
        $obj = file_get_contents($url);
        $json_decode = json_decode($obj);

        return $json_decode;
    }

    public function search($args)
    {
        $result = $this->call('search', $args);
        if ($result->type == 'ERROR') {
            trigger_error($result->error);
        }

        return $result;
    }

    public function jobTagsInsert($tags, $job)
    {
        if (! $tags) {
            return;
        }

        $tagsArray = [];

        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $tagsArray[] = (int) $tag;
                continue;
            }

            $name = trim((string) $tag);
            if ($name === '') {
                continue;
            }

            $existing = TagTranslation::query()
                ->where('name', $name)
                ->orWhere('name', 'like', '%'.$name.'%')
                ->first();

            if ($existing) {
                $tagsArray[] = (int) $existing->tag_id;
                continue;
            }

            $newTag = Tag::create(['name' => $name]);
            $languages = loadLanguage();
            foreach ($languages as $language) {
                $newTag->translateOrNew($language->code)->name = $name;
            }
            $newTag->save();

            $tagsArray[] = (int) $newTag->id;
        }

        if (! empty($tagsArray)) {
            $job->tags()->attach(array_values(array_unique($tagsArray)));
        }
    }

    public function jobTagsSync($tags, $job)
    {
        if ($tags) {
            $tagsArray = [];

            foreach ($tags as $tag) {
                $tag_exists = TagTranslation::where('tag_id', $tag)->first();

                if ($tag_exists) {
                    $taggable = TagTranslation::where('tag_id', $tag)->orWhere('name', $tag)->first();

                    if (! $taggable) {
                        $new_tag = Tag::create(['name' => $tag]);

                        $languages = loadLanguage();
                        foreach ($languages as $language) {
                            $new_tag->translateOrNew($language->code)->name = $tag;
                        }
                        $new_tag->save();

                        array_push($tagsArray, $new_tag->id);
                    } else {
                        array_push($tagsArray, $tag);
                    }
                }
            }

            $job->tags()->sync($tagsArray);
        }
    }

    public function jobSkillsInsert($skills, $job)
    {
        if ($skills) {
            $skillsArray = [];

            foreach ($skills as $skill) {
                $skill_exists = SkillTranslation::where('skill_id', $skill)->orWhere('name', $skill)->first();

                if (! $skill_exists) {
                    $select_skill = Skill::create(['name' => $skill]);

                    $languages = loadLanguage();
                    foreach ($languages as $language) {
                        $select_skill->translateOrNew($language->code)->name = $skill;
                    }
                    $select_skill->save();

                    array_push($skillsArray, $select_skill->id);
                } else {
                    array_push($skillsArray, $skill_exists->skill_id);
                }
            }

            $job->skills()->attach($skillsArray);
        }
    }

    public function jobSkillsSync($skills, $job)
    {
        if ($skills) {
            $skillsArray = [];

            foreach ($skills as $skill) {
                $skill_exists = SkillTranslation::where('skill_id', $skill)->orWhere('name', $skill)->first();

                if (! $skill_exists) {
                    $select_skill = Skill::create(['name' => $skill]);

                    $languages = loadLanguage();
                    foreach ($languages as $language) {
                        $select_skill->translateOrNew($language->code)->name = $skill;
                    }
                    $select_skill->save();

                    array_push($skillsArray, $select_skill->id);
                } else {
                    array_push($skillsArray, $skill_exists->skill_id);
                }
            }

            $job->skills()->sync($skillsArray);
        }
    }

    public function jobBenefitsInsert($benefits, $job)
    {
        if (! $benefits || ! count($benefits)) {
            return;
        }

        $benefitIds = [];

        foreach ($benefits as $benefit) {
            if (is_numeric($benefit)) {
                $benefitIds[] = (int) $benefit;
                continue;
            }

            $name = trim((string) $benefit);
            if ($name === '') {
                continue;
            }

            $existing = BenefitTranslation::query()
                ->where('name', $name)
                ->orWhere('name', 'like', '%'.$name.'%')
                ->first();

            if ($existing) {
                $benefitIds[] = (int) $existing->benefit_id;
                continue;
            }

            $newBenefit = Benefit::create([]);
            $languages = loadLanguage();
            foreach ($languages as $language) {
                $newBenefit->translateOrNew($language->code)->name = $name;
            }
            $newBenefit->save();

            $benefitIds[] = (int) $newBenefit->id;
        }

        if (! empty($benefitIds)) {
            $job->benefits()->attach(array_values(array_unique($benefitIds)));
        }
    }

    public function jobBenefitsSync($benefits, $job)
    {
        if ($benefits && count($benefits)) {
            $job->benefits()->sync($benefits);
        }
    }

    public function popularTags()
    {
        return Tag::popular()->withCount('tags')->latest('tags_count')->get()->take(10);
    }

    /**
     * Toggle bookmark job
     *
     * @return void
     */
    public function toggleBookmarkJob(Job $job)
    {
        $check = $job->bookmarkJobs()->toggle(currentCandidate());

        if ($check['attached'] == [1]) {
            $user = authUser();
            // make notification to company candidate bookmark job
            Notification::send($job->company->user, new BookmarkJobNotification($user, $job));

            // make notification to candidate for notify
            if (auth()->user()->recent_activities_alert) {
                Notification::send(authUser(), new BookmarkJobNotification($user, $job));
            }
        }

        $check['attached'] == [1] ? ($message = __('job_added_to_favorite_list')) : ($message = __('job_removed_from_favorite_list'));

        flashSuccess($message);

        return back();
    }

    /**
     * Toggle apply job
     *
     * @param  Candidate  $candidate
     * @return void
     */
    public function toggleApplyJob(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'resume_id' => 'required',
                'cover_letter' => 'required',
            ],
            [
                'resume_id.required' => 'Please select resume',
                'cover_letter.required' => 'Please enter cover letter',
            ],
        );

        if ($validator->fails()) {
            flashError($validator->errors()->first());

            return back();
        }

        if (currentCandidate()->profile_complete != 0) {
            flashError(__('complete_your_profile_before_applying_to_jobs_add_your_information_resume_and_profile_picture_for_a_better_chance_of_getting_hired'));

            return redirect()->route('candidate.dashboard');
        }

        $candidate = currentCandidate();
        $job = Job::find($request->id);

        DB::table('applied_jobs')->insert([
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'company_id' => $job->company_id ?: null,
            'agency_id' => $job->agency_id ?: null,
            'cover_letter' => $request->cover_letter,
            'candidate_resume_id' => $request->resume_id,
            'application_group_id' => $job->company->applicationGroups->where('is_deleteable', false)->first()->id ?? 1,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // make notification to candidate and company for notify
        $job->company->user->notify(new ApplyJobNotification(authUser(), $job->company->user, $job));

        if (authUser()->recent_activities_alert) {
            auth('user')
                ->user()
                ->notify(new ApplyJobNotification(authUser(), $job->company->user, $job));
        }

        flashSuccess(__('job_applied_successfully'));

        return back();
    }

    /**
     * job benefit create
     *
     * @return Renderable
     */
    public function jobBenefitCreate(Request $request)
    {
        $benefit = $request->benefit;
        $languages = loadLanguage();
        $benefit_exists = BenefitTranslation::where('name', $benefit)->first();

        if ($benefit_exists) {
            return __('benefit_already_exists');
        }

        $translation = new Benefit;
        $translation->company_id = currentCompany()->id;
        $translation->save();

        foreach ($languages as $language) {
            $translation->translateOrNew($language->code)->name = $benefit;
        }

        $translation->save();

        return $translation;
    }
}
