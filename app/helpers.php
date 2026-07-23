<?php

use App\Models\Advertisement;
use App\Models\Candidate;
use App\Models\Cms;
use App\Models\Company;
use App\Models\agency;
use App\Models\Cookies;
use App\Models\Job;
use App\Models\Profession;
use App\Models\ProfessionTranslation;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Intervention\Image\Facades\Image;
use Laravolt\Avatar\Facade as Avatar;
use Modules\Currency\Entities\Currency;
use Modules\Language\Entities\Language;
use Modules\Location\Entities\Country;
use Modules\Seo\Entities\Seo;
use Stevebauman\Location\Facades\Location;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Torann\GeoIP\Facades\GeoIP;

if (! function_exists('uploadImage')) {
    function uploadImage($file, $destinationPath, $fit = null, $quality = 60)
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = time().'_'.uniqid().'.'.$extension;
        $relativeDir = trim(str_replace('\\', '/', $destinationPath), '/');
        $absoluteDir = public_path(str_replace('/', DIRECTORY_SEPARATOR, $relativeDir));
        $fullPath = $absoluteDir.DIRECTORY_SEPARATOR.$fileName;
        $relativePath = $relativeDir.'/'.$fileName;

        if (! File::isDirectory($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0777, true, true);
        }

        $saved = false;
        $gdAvailable = extension_loaded('gd') || extension_loaded('imagick');

        if ($gdAvailable) {
            try {
                $image = Image::make($file);
                if ($fit) {
                    $image->fit($fit[0], $fit[1]);
                }
                $image->save($fullPath, $quality);
                $saved = is_file($fullPath) && filesize($fullPath) > 0;
            } catch (\Throwable $e) {
                $saved = false;
            }
        }

        if (! $saved) {
            $file->move($absoluteDir, $fileName);
            $saved = is_file($fullPath) && filesize($fullPath) > 0;
        }

        if (! $saved) {
            throw new \RuntimeException('Failed to save uploaded image.');
        }

        return $relativePath;
    }
}

/**
 * image delete
 *
 * @param  string  $image
 * @return void
 */
if (! function_exists('deleteFile')) {
    function deleteFile(?string $image)
    {
        deleteImage($image);
    }
}

/**
 * image delete
 *
 * @param  string  $image
 * @return void
 */
if (! function_exists('deleteImage')) {
    function deleteImage(?string $image)
    {
        if (empty($image) || $image === 'backend/image/default.png') {
            return;
        }

        $candidates = array_unique(array_filter([
            $image,
            public_path($image),
            public_path(str_replace('\\', '/', $image)),
        ]));

        foreach ($candidates as $path) {
            if (is_file($path)) {
                @unlink($path);

                return;
            }
        }
    }
}

if (! function_exists('publicUploadExists')) {
    function publicUploadExists(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        return is_file(public_path(str_replace('\\', '/', $relativePath)));
    }
}

/**
 * @param  UploadedFile  $file
 * @param  null  $folder
 * @param  string  $disk
 * @param  null  $filename
 * @return false|string
 */
if (! function_exists('uploadOne')) {
    function uploadOne(UploadedFile $file, $folder = null, $disk = 'public', $filename = null)
    {
        $name = ! is_null($filename) ? $filename : uniqid('FILE_').dechex(time());

        return $file->storeAs(
            $folder,
            $name.'.'.$file->getClientOriginalExtension(),
            $disk
        );
    }
}

/**
 * @param  null  $path
 * @param  string  $disk
 */
if (! function_exists('deleteOne')) {
    function deleteOne($path = null, $disk = 'public')
    {
        Storage::disk($disk)->delete($path);
    }
}

if (! function_exists('uploadFileToStorage')) {
    function uploadFileToStorage($file, string $path)
    {
        $file_name = $file->hashName();
        Storage::putFileAs($path, $file, $file_name);

        return $path.'/'.$file_name;
    }
}

if (! function_exists('uploadFileToPublic')) {
    function uploadFileToPublic($file, string $path)
    {
        if ($file && $path) {
            $dir      = 'uploads/'.$path;
            $filename = $file->hashName();
            $file->move($dir, $filename);

            return $dir.'/'.$filename;
        }

        return null;
    }
}

// =====================================================
// ===================Env Function====================
// =====================================================
if (! function_exists('envReplace')) {
    function envReplace($name, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $name.'='.env($name),
                $name.'='.$value,
                file_get_contents($path)
            ));
        }

        if (file_exists(App::getCachedConfigPath())) {
            Artisan::call('config:cache');
        }
    }
}
if (! function_exists('replaceAppName')) {
    function replaceAppName($name, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            // Wrap the value in double quotes and replace the line
            $escapedValue = '"'.str_replace('"', '\"', $value).'"';
            file_put_contents($path, preg_replace(
                "/^$name=.*/m",
                "$name=$escapedValue",
                file_get_contents($path)
            ));
        }

        if (file_exists(App::getCachedConfigPath())) {
            Artisan::call('config:clear');
        }
    }
}

if (! function_exists('envUpdate')) {
    function envUpdate($key, $value)
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        $newLine = "$key=$value";

        if (strpos($envContent, "$key=") !== false) {
            $envContent = preg_replace("/$key=.*/", $newLine, $envContent);
        } else {
            $envContent .= "\n".$newLine;
        }

        file_put_contents($envFile, $envContent);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}

if (! function_exists('error')) {
    function error($name, $class = 'is-invalid')
    {
        $errors = session()->get('errors', app(ViewErrorBag::class));

        return $errors->has($name) ? $class : '';
    }
}

if (! function_exists('checkSetConfig')) {
    function checkSetConfig($key, $value)
    {

        if ((config($key) != $value)) {

            setConfig($key, $value);
        }
    }
}

if (! function_exists('setConfig')) {
    function setConfig($key, $value)
    {
        Config::write($key, $value);
        sleep(2);
        if (file_exists(App::getCachedConfigPath())) {
            Artisan::call('config:cache');
        }

        return 'Configuration set successfully!';
    }
}

if (! function_exists('allowLaguageChanage')) {
    function allowLaguageChanage()
    {
        return Setting::first()->language_changing ? true : false;
    }
}

// ========================================================
// ===================Response Function====================
// ========================================================

/**
 * Response success data collection
 *
 * @param  object  $data
 * @param  string  $responseName
 * @return \Illuminate\Http\Response
 */
if (! function_exists('responseData')) {
    function responseData(?object $data, string $responseName = 'data')
    {
        return response()->json([
            'success' => true,
            $responseName => $data,
        ], 200);
    }
}

/**
 * Response success data collection
 *
 * @param  string  $msg
 * @return \Illuminate\Http\Response
 */
if (! function_exists('responseSuccess')) {
    function responseSuccess(string $msg = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $msg,
        ], 200);
    }
}

/**
 * Response error data collection
 *
 * @param  string  $msg
 * @param  int  $code
 * @return \Illuminate\Http\Response
 */
if (! function_exists('responseError')) {
    function responseError(string $msg = 'Something went wrong, please try again', int $code = 404)
    {
        return response()->json([
            'success' => false,
            'message' => $msg,
        ], $code);
    }
}

/**
 * Response success flash message.
 *
 * @param  string  $msg
 * @return \Illuminate\Http\Response
 */
if (! function_exists('flashSuccess')) {
    function flashSuccess(string $msg)
    {
        session()->flash('success', $msg);
    }
}

/**
 * Response error flash message.
 *
 * @param  string  $msg
 * @return \Illuminate\Http\Response
 */
if (! function_exists('flashError')) {
    function flashError(?string $message = null)
    {
        if (! $message) {
            $message = __('something_went_wrong');
        }

        return session()->flash('error', $message);
    }
}

/**
 * Response warning flash message.
 *
 * @param  string  $msg
 * @return \Illuminate\Http\Response
 */
if (! function_exists('flashWarning')) {
    function flashWarning(?string $message = null, bool $custom = false)
    {
        if (! $message) {
            $message = __('something_went_wrong');
        }

        if ($custom) {
            return session()->flash('warning', $message);
        } else {
            return session()->flash('warning', $message);
        }
    }
}

// ========================================================
// ===================Others Function====================
// ========================================================
if (! function_exists('setting')) {
    function setting($fields = null, $append = false)
    {
        if ($fields) {
            $type = gettype($fields);

            if ($type == 'string') {
                $data = $append ? Setting::first($fields) : Setting::value($fields);
            } elseif ($type == 'array') {
                $data = Setting::first($fields);
            }
        } else {
            $data = loadSetting();
        }

        if ($append) {
            $data = $data->makeHidden(['dark_logo_url', 'light_logo_url', 'favicon_image_url', 'app_pwa_icon_url']);
        }
        forgetCache('setting_data');

        return $data;
    }
}

// For pwa_enable start
if (! function_exists('updateManifest')) {
    function updateManifest($setting)
    {
        $manifest = [

            'name' => config('app.name'),
            'short_name' => config('app.name'),
            'start_url' => '/',
            'scope' => '/',
            'background_color' => $setting->frontend_primary_color,
            'description' => config('app.name'),
            'display' => 'fullscreen',
            'theme_color' => $setting->frontend_primary_color,
            'icons' => [
                [
                    'src' => parse_url($setting->app_pwa_icon_url, PHP_URL_PATH) ?: '/logo.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        file_put_contents(public_path('manifest.json'), json_encode($manifest));
    }
}
// For pwa_enable end

if (! function_exists('autoTransLation')) {
    function autoTransLation($lang, $text)
    {
        $tr = new GoogleTranslate($lang);
        $afterTrans = $tr->translate($text);

        return $afterTrans;
    }
}

if (! function_exists('resumeImageLocalPath')) {
    /**
     * Resolve a stored path or asset URL to an absolute filesystem path under public/.
     */
    function resumeImageLocalPath(?string $pathOrUrl): ?string
    {
        if (empty($pathOrUrl)) {
            return null;
        }

        if (preg_match('#^https?://#i', $pathOrUrl)) {
            $path = parse_url($pathOrUrl, PHP_URL_PATH);

            return $path ? public_path(ltrim($path, '/')) : null;
        }

        return public_path(ltrim($pathOrUrl, '/'));
    }
}

if (! function_exists('resumeImageSrc')) {
    /**
     * Image src for resume HTML/PDF — http URLs in browser, base64 in PDF engines.
     */
    function resumeImageSrc(?string $pathOrUrl, ?bool $forPdf = null): ?string
    {
        if (empty($pathOrUrl)) {
            return null;
        }

        if ($forPdf === null) {
            $forPdf = request()->input('action_type') === 'download';
        }

        $localPath = resumeImageLocalPath($pathOrUrl);

        if (! $localPath || ! is_file($localPath)) {
            if (preg_match('#^https?://#i', $pathOrUrl)) {
                return $forPdf ? null : $pathOrUrl;
            }

            return $forPdf ? null : asset(ltrim($pathOrUrl, '/'));
        }

        if ($forPdf) {
            $mime = @mime_content_type($localPath) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($localPath));
        }

        $relative = str_replace('\\', '/', ltrim(str_replace(public_path(), '', $localPath), '/\\'));

        return asset($relative);
    }
}

if (! function_exists('generateResumeQrCode')) {
    /**
     * Generate a resume QR code without requiring the imagick PHP extension.
     *
     * @return array{svg: ?string, png: ?string}
     */
    function generateResumeQrCode(string $url): array
    {
        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->generate($url);

            return ['svg' => $svg, 'png' => null];
        } catch (\Throwable $e) {
            \Log::warning('Resume QR (svg) failed: ' . $e->getMessage());
        }

        try {
            $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(80)->generate($url);

            return ['svg' => null, 'png' => base64_encode($png)];
        } catch (\Throwable $e) {
            \Log::warning('Resume QR (png) failed: ' . $e->getMessage());
        }

        return ['svg' => null, 'png' => null];
    }
}

/**
 * user permission check
 *
 * @param  string  $permission
 * @return bool
 */
if (! function_exists('userCan')) {
    function userCan($permission)
    {
        return auth('admin')->user()->can($permission);
    }
}

if (! function_exists('pdfUpload')) {
    function pdfUpload(?object $file, string $path): string
    {
        $filename = time().'.'.$file->extension();
        $filePath = public_path('uploads/'.$path);
        $file->move($filePath, $filename);

        return $filePath.$filename;
    }
}

if (! function_exists('remainingDays')) {

    function remainingDays($deadline)
    {
        $now = Carbon::now();
        $cDate = Carbon::parse($deadline);

        return $now->diffInDays($cDate);
    }
}

if (! function_exists('jobStatus')) {
    function jobStatus($deadline)
    {
        $now = Carbon::now();
        $cDate = Carbon::parse($deadline);

        if ($now->greaterThanOrEqualTo($cDate)) {
            return 'Expire';
        } else {
            return 'Active';
        }
    }
}

if (! function_exists('socialMediaShareLinks')) {

    function socialMediaShareLinks(string $path, string $provider)
    {
        switch ($provider) {
            case 'facebook':
                $share_link = 'https://www.facebook.com/sharer/sharer.php?u='.$path;
                break;
            case 'twitter':
                $share_link = 'https://twitter.com/intent/tweet?text='.$path;
                break;
            case 'pinterest':
                $share_link = 'http://pinterest.com/pin/create/button/?url='.$path;
                break;
            case 'linkedin':
                $share_link = 'https://www.linkedin.com/shareArticle?mini=true&url='.$path;
                break;
            case 'telegram':
                $share_link = 'https://t.me/share/url?url='.$path;
                break;
            case 'whatsapp':
                $share_link = 'https://api.whatsapp.com/send?text='.$path;
                break;
            case 'linkedin':
                $share_link = 'https://www.linkedin.com/sharing/share-offsite/?url='.$path;
                break;
            case 'mail':
                $share_link = 'mailto:?subject=Share this link&body='.$path;
                break;
            case 'skype':
                $share_link = 'https://web.skype.com/share?url='.$path;
                break;
        }

        return $share_link;
    }
}

if (! function_exists('livejob')) {

    function livejob()
    {
        $jobs = Job::withoutEdited()->openPosition();

        $selected_country = session()->get('selected_country');

        if ($selected_country && $selected_country != null && $selected_country != 'all') {
            $country = selected_country()->name;
            $jobs->where('country', 'LIKE', "%$country%");
        } else {

            $setting = loadSetting();
            if ($setting->app_country_type == 'single_base') {
                if ($setting->app_country) {

                    $country = Country::where('id', $setting->app_country)->first();
                    if ($country) {
                        $jobs->where('country', 'LIKE', "%$country->name%");
                    }
                }
            }
        }

        return $jobs->count();
    }
}

if (! function_exists('companies')) {

    function companies()
    {
        $companies = Company::count();

        return $companies;
    }
}
if (! function_exists('agencies')) {

    function agencies()
    {
        return \App\Models\Agency::count();
    }
}


if (! function_exists('newjob')) {

    function newjob()
    {
        $newjobs = Job::where('status', 'active')->where('created_at', '>=', Carbon::now()->subDays(7)->toDateString())->count();

        return $newjobs;
    }
}

if (! function_exists('candidate')) {
    function candidate()
    {
        $candidates = Candidate::count();

        return $candidates;
    }
}

if (! function_exists('linkActive')) {
    function linkActive($route, $class = 'active')
    {
        return request()->routeIs($route) ? $class : '';
    }
}

if (! function_exists('candidateNotifications')) {
    function candidateNotifications()
    {
        return auth()->user()->notifications()->take(5)->get();
    }
}

if (! function_exists('candidateNotificationsCount')) {

    function candidateNotificationsCount()
    {

        return auth()->user()->notifications()->count();
    }
}

if (! function_exists('candidateUnreadNotifications')) {

    function candidateUnreadNotifications()
    {
        return auth()->user()->unreadNotifications()->count();
    }
}

if (! function_exists('companyNotifications')) {

    function companyNotifications()
    {

        return auth()->user()->notifications()->take(5)->get();
    }
}

if (! function_exists('companyNotificationsCount')) {
    function companyNotificationsCount()
    {
        return auth()->user()->notifications()->count();
    }
}

if (! function_exists('companyUnreadNotifications')) {

    function companyUnreadNotifications()
    {

        return auth()->user()->unreadNotifications()->count();
    }
}

if (! function_exists('agencyNotifications')) {

    function agencyNotifications()
    {

        return auth()->user()->notifications()->take(6)->get();
    }
}

if (! function_exists('agencyNotificationsCount')) {
    function agencyNotificationsCount()
    {
        return auth()->user()->notifications()->count();
    }
}

if (! function_exists('agencyUnreadNotifications')) {

    function agencyUnreadNotifications()
    {

        return auth()->user()->unreadNotifications()->count();
    }
}

if (! function_exists('defaultCurrencySymbol')) {
    function defaultCurrencySymbol()
    {
        return config('templatecookie.app_currency_symbol');
    }
}

if (! function_exists('currencyAmountShort')) {

    function currencyAmountShort($amount, $currencyRate = 1)
    {
        $num = $amount * $currencyRate;

        $units = ['', 'K', 'M', 'B', 'T'];

        for ($i = 0; $num >= 1000; $i++) {
            $num /= 1000;
        }

        return round($num, 0) . $units[$i];
    }
}

/* Currency position
 *
 * @param String $date
 */
if (! function_exists('changeCurrency')) {
    function changeCurrency($amount)
    {
        if (session()->has('current_currency')) {
            $current_currency = session('current_currency');
            $symbol = $current_currency->symbol;
            $position = $current_currency->symbol_position;
        } else {
            $symbol = config('templatecookie.currency_symbol');
            $position = config('templatecookie.currency_symbol_position');
        }

        $converted_amount = round($amount * getCurrencyRate(), 2);

        if ($position == 'left') {
            return $symbol.' '.$converted_amount;
        } else {
            return $converted_amount.' '.$symbol;
        }
    }
}

/**
 * Remove the decimal numbers and shorten
 *
 * @param  number  $amount
 * @return string
 */
if (! function_exists('zeroDecimal')) {
    function zeroDecimal($amount)
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        for ($i = 0; $amount >= 1000; $i++) {
            $amount /= 1000;
        }

        return round($amount, 0).$units[$i];
    }
}

/**
 * Currency exchange
 *
 * @param  $amount
 * @param  $from
 * @param  $to
 * @param  $round
 * @return number
 */
if (! function_exists('currencyExchange')) {
    function currencyExchange($amount, $from = null, $to = null, $round = 2)
    {
        $from = currentCurrencyCode();
        $to = config('templatecookie.currency', 'USD');

        $fromRate = Currency::whereCode($from)->first()->rate;
        $toRate = Currency::whereCode($to)->first()->rate;
        $rate = $toRate / $fromRate;

        return round($amount * $rate);
    }
}

// if (! function_exists('currencyConversion')) {
//     function currencyConversion($amount, $to = 'USD', $round = 2)
//     {
//         $to = $to;

//         $checkCurrency = Currency::where('code', $to)->first();

//         if ($amount && $checkCurrency) {
//             $total = $amount * $checkCurrency->rate;

//             return (int) round($total, $round);
//         }

//         return $amount;

//         // $from = $from ?? config('templatecookie.currency');
//         // $to = $to ?? 'USD';

//         // $checkCurrency = Currency::where('code', $to)->first();
//         // if ($amount && $checkCurrency) {

//         //     $fromRate = Currency::whereCode($from)->first()?->rate ?? 1;
//         //     $toRate = Currency::whereCode($to)->first()?->rate ?? 1;
//         //     $rate = $fromRate / $toRate;
//         //     $result = $amount / $rate;

//         //     return (int) round($amount * $rate, 2);
//         // }

//         // $from = $from ?? config('templatecookie.currency');
//         // $to = $to ?? 'USD';

//         // $checkCurrency = Currency::where('code', $to)->first();
//         // if ($amount && $checkCurrency) {

//         //     $fromRate = Currency::whereCode($from)->first()?->rate ?? 1;
//         //     $toRate = Currency::whereCode($to)->first()?->rate ?? 1;
//         //     $rate = $fromRate / $toRate;
//         //     $result = $amount / $rate;

//         //     return (int) round($amount * $rate, 2);
//         // }
//     }
// }

if (! function_exists('currencyConversion')) {
    function currencyConversion($amount, $to = 'USD', $round = 2)
    {
        $to = $to;

        // Check if the target currency is TL
        if ($to === 'TL') {
            // Assuming 1 USD = 9 TL, replace this value with the actual conversion rate
            $usdToTLRate = 32.27; // For example purposes, replace with actual rate
            $total = $amount * $usdToTLRate;
        } else {
            // Convert to USD or any other currency
            $checkCurrency = Currency::where('code', $to)->first();

            if ($amount && $checkCurrency) {
                $total = $amount * $checkCurrency->rate;
            } else {
                return $amount;
            }
        }

        return (int) round($total, $round);
    }
}

if (! function_exists('usdAmount')) {
    function usdAmount($amount)
    {
        if (session('currency_rate')) {
            return round($amount / session('currency_rate.rate'), 2);
        } else {
            return round($amount * 1 / Currency::whereCode(config('templatecookie.currency'))->first()->rate, 2);
        }
    }
}

if (! function_exists('currencyConvert')) {
    function currencyConvert($amount, $to = 'USD', $round = 2)
    {
        $checkCurrency = Currency::where('code', $to)->first();

        if ($amount && $checkCurrency) {
            $total = $amount * $checkCurrency->rate;

            return round($total, $round);
        }

        return $amount;
    }
}

/**
 * Currency rate store in session
 *
 * @return void
 */
if (! function_exists('currencyRateStore')) {
    function currencyRateStore()
    {
        if (session()->has('currency_rate')) {
            $currency_rate = session('currency_rate');
            $from = config('templatecookie.currency');
            $to = currentCurrencyCode();

            if ($currency_rate['from'] != $from || $currency_rate['to'] != $to) {
                $fromRate = Currency::whereCode($from)->first()->rate;
                $toRate = Currency::whereCode($to)->first()->rate;
                $rate = $fromRate / $toRate;
                session(['currency_rate' => ['from' => $from, 'to' => $to, 'rate' => $rate]]);
            }
        } else {
            $from = config('templatecookie.currency');
            $to = currentCurrencyCode();

            $fromRate = Currency::whereCode($from)->first()->rate;
            $toRate = Currency::whereCode($to)->first()->rate;
            $rate = $fromRate / $toRate;
            session(['currency_rate' => ['from' => $from, 'to' => $to, 'rate' => $rate]]);
        }
    }
}

/**
 * Get currency rate
 *
 * @return number
 */
if (! function_exists('getCurrencyRate')) {
    function getCurrencyRate()
    {
        if (session()->has('currency_rate')) {
            $currency_rate = session('currency_rate');
            $rate = $currency_rate['rate'];

            return $rate;
        } else {
            return 1;
        }
    }
}

/**
 * Get current Currency
 *
 * @return object
 */
if (! function_exists('currentCurrency')) {
    function currentCurrency()
    {
        return session('current_currency') ?? loadSystemCurrency();
    }
}

/**
 * Get current Currency code
 *
 * @return string
 */
if (! function_exists('currentCurrencyCode')) {
    function currentCurrencyCode()
    {
        if (session()->has('current_currency')) {
            $currency = session('current_currency');

            return $currency->code;
        }

        return config('templatecookie.currency');
    }
}

/**
 * Get current Currency symbol
 *
 * @return string
 */
if (! function_exists('currentCurrencySymbol')) {
    function currentCurrencySymbol()
    {
        if (session()->has('current_currency')) {
            $currency = session('current_currency');

            return $currency->symbol;
        }

        return config('templatecookie.currency_symbol');
    }
}

if (! function_exists('currentLanguage')) {

    function currentLanguage()
    {
        return session('current_lang');
    }
}

if (! function_exists('langDirection')) {

    function langDirection()
    {
        return currentLanguage()?->direction ?? Language::where('code', config('templatecookie.default_language'))->value('direction');
    }
}

if (! function_exists('metaData')) {

    function metaData($page)
    {
        $current_language = currentLanguage(); // current session language
        $language_code = $current_language ? $current_language->code : 'en'; // language code or default one
        $page = Seo::where('page_slug', $page)->first(); // get page
        $exist_content = $page ? $page->contents()->where('language_code', $language_code)->first() : null; // get page content orderBy page && language
        $content = '';
        if ($exist_content) {
            $content = $exist_content;
        } else {
            $content = $page->contents()?->where('language_code', 'en')->first() ?? '';
        }

        return $content; // return response
    }
}

if (! function_exists('storePlanInformation')) {

    function storePlanInformation()
    {
        session()->forget('user_plan');

        $user = auth()->user();

        if ($user->role === 'company' && $user->company) {
            session(['user_plan' => $user->company->userPlan]);
        } elseif ($user->role === 'agency' && $user->agency) {
            session(['user_plan' => $user->agency->userPlan]);
        } else {
            session(['user_plan' => null]);
        }
    }
}


if (! function_exists('formatTime')) {

    function formatTime($date, $format = 'F d, Y H:i A')
    {
        if ($date === null || $date === '') {
            return '';
        }

        try {
            return Carbon::parse($date)->format($format);
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }
}

if (! function_exists('formatResumeDate')) {
    /** Clean date for CV output (no time component). */
    function formatResumeDate($date, string $format = 'd M Y'): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $raw = trim((string) $date);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d{4}$/', $raw)) {
            return $raw;
        }

        try {
            return Carbon::parse($raw)->format($format);
        } catch (\Throwable $e) {
            return preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', $raw);
        }
    }
}

if (! function_exists('formatResumeYear')) {
    function formatResumeYear($date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $raw = trim((string) $date);
        if (preg_match('/^\d{4}$/', $raw)) {
            return $raw;
        }

        try {
            return Carbon::parse($raw)->format('Y');
        } catch (\Throwable $e) {
            return $raw;
        }
    }
}

if (! function_exists('resumeAge')) {
    function resumeAge($birthDate): ?int
    {
        if ($birthDate === null || $birthDate === '') {
            return null;
        }

        try {
            return Carbon::parse($birthDate)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (! function_exists('resumeIsRtlLocale')) {
    function resumeIsRtlLocale(?string $code): bool
    {
        return in_array(strtolower((string) $code), ['ar', 'ur', 'fa', 'he', 'ps', 'sd'], true);
    }
}

/**
 * Scripts that DomPDF + DejaVu cannot shape correctly (need mPDF + OTL / special fonts).
 */
if (! function_exists('resumeNeedsMpdfEngine')) {
    function resumeNeedsMpdfEngine(?string $code): bool
    {
        $code = strtolower((string) $code);

        if ($code === '' || $code === 'en') {
            return false;
        }

        if (resumeIsRtlLocale($code)) {
            return true;
        }

        return in_array($code, [
            // Indic / South Asian
            'hi', 'bn', 'ne', 'mr', 'gu', 'pa', 'ta', 'te', 'kn', 'ml', 'si',
            // SE Asian complex
            'th', 'lo', 'my', 'km',
            // CJK
            'zh', 'ja', 'ko',
        ], true);
    }
}

if (! function_exists('bilingualResumeLanguages')) {
    /** @return list<array{code: string, name: string}> */
    function bilingualResumeLanguages(): array
    {
        return [
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'ar', 'name' => 'Arabic'],
            ['code' => 'tr', 'name' => 'Turkish'],
            ['code' => 'de', 'name' => 'German'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'es', 'name' => 'Spanish'],
            ['code' => 'ro', 'name' => 'Romanian'],
            ['code' => 'lt', 'name' => 'Lithuanian'],
            ['code' => 'pl', 'name' => 'Polish'],
            ['code' => 'ur', 'name' => 'Urdu'],
            ['code' => 'hi', 'name' => 'Hindi'],
            ['code' => 'bn', 'name' => 'Bengali'],
            ['code' => 'id', 'name' => 'Indonesian'],
            ['code' => 'ms', 'name' => 'Malay'],
            ['code' => 'it', 'name' => 'Italian'],
            ['code' => 'pt', 'name' => 'Portuguese'],
            ['code' => 'ru', 'name' => 'Russian'],
            ['code' => 'zh', 'name' => 'Chinese'],
            ['code' => 'ja', 'name' => 'Japanese'],
            ['code' => 'ko', 'name' => 'Korean'],
            ['code' => 'nl', 'name' => 'Dutch'],
            ['code' => 'sv', 'name' => 'Swedish'],
            ['code' => 'no', 'name' => 'Norwegian'],
            ['code' => 'da', 'name' => 'Danish'],
            ['code' => 'fi', 'name' => 'Finnish'],
            ['code' => 'el', 'name' => 'Greek'],
            ['code' => 'th', 'name' => 'Thai'],
            ['code' => 'vi', 'name' => 'Vietnamese'],
            ['code' => 'fa', 'name' => 'Persian'],
            ['code' => 'he', 'name' => 'Hebrew'],
        ];
    }
}

if (! function_exists('normalizeBilingualLanguageCode')) {
    function normalizeBilingualLanguageCode(?string $code, ?string $custom = null): string
    {
        $code = strtolower(trim((string) $code));
        if ($code === 'custom') {
            $code = '';
        }

        $raw = strtolower(trim((string) ($custom ?: $code ?: 'en')));
        $raw = preg_replace('/[^a-z-]/', '', $raw) ?: 'en';

        return substr($raw, 0, 10);
    }
}

if (! function_exists('inspireMe')) {

    function inspireMe()
    {
        Artisan::call('inspire');

        return Artisan::output();
    }
}

if (! function_exists('getUnsplashImage')) {
    function getUnsplashImage()
    {
        $url = 'https://source.unsplash.com/random/1920x1280/?nature,landscape,mountains';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $a = curl_exec($ch); // $a will contain all headers
    }
}

if (! function_exists('adminNotifications')) {
    function adminNotifications()
    {
        return auth('admin')->user()->notifications()->take(10)->get();
    }
}

if (! function_exists('adminUnNotifications')) {

    function adminUnNotifications()
    {
        return auth('admin')->user()->unreadNotifications()->count();
    }
}

if (! function_exists('checkMailConfig')) {

    function checkMailConfig()
    {
        $status = config('mail.mailers.smtp.transport') && config('mail.mailers.smtp.host') && config('mail.mailers.smtp.port') && config('mail.mailers.smtp.username') && config('mail.mailers.smtp.password') && config('mail.mailers.smtp.encryption') && config('mail.from.address') && config('mail.from.name');

        ! $status ? flashError(__('mail_not_sent_for_the_reason_of_incomplete_mail_configuration')) : '';

        return $status ? 1 : 0;
    }
}

if (! function_exists('openJobs')) {

    function openJobs()
    {
        return Job::where('status', 'active')->where('deadline', '>=', Carbon::now()->toDateString())->count();
    }
}

if (! function_exists('initialJobStatus')) {
    /**
     * Status for a newly created job.
     * Auto when admin enables it, or when the employer still has plan job quota.
     */
    function initialJobStatus(): string
    {
        if (setting('job_auto_approved')) {
            return 'active';
        }

        if (auth('user')->check()) {
            $user = auth('user')->user();
            if (in_array($user->role, ['company', 'agency'], true)) {
                storePlanInformation();
                $plan = session('user_plan');
                if ($plan && (int) ($plan->job_limit ?? 0) > 0) {
                    return 'active';
                }
            }
        }

        return 'pending';
    }
}

if (! function_exists('activateEligiblePendingJobs')) {
    /**
     * Publish pending jobs when auto-approval rules allow it.
     */
    function activateEligiblePendingJobs(?Company $company = null): int
    {
        if (! $company) {
            return 0;
        }

        storePlanInformation();
        $userPlan = session('user_plan');

        if (! setting('job_auto_approved') && (! $userPlan || (int) ($userPlan->job_limit ?? 0) < 1)) {
            return 0;
        }

        return $company->jobs()->where('status', 'pending')->update(['status' => 'active']);
    }
}

if (! function_exists('applyJobCountryScope')) {
    /**
     * Restrict jobs to the visitor's selected/default country.
     * Falls back to the employer/agency country when the job row has no country set.
     */
    function applyJobCountryScope($query)
    {
        $selected_country = session()->get('selected_country');
        $countryName = null;

        if ($selected_country && $selected_country != null && $selected_country != 'all') {
            $countryName = selected_country()->name;
        } else {
            $setting = loadSetting();
            if ($setting->app_country_type == 'single_base' && $setting->app_country) {
                $countryName = optional(\Modules\Location\Entities\Country::where('id', $setting->app_country)->first())->name;
            }
        }

        if (blank($countryName)) {
            return $query;
        }

        return $query->where(function ($q) use ($countryName) {
            $q->where('country', 'LIKE', "%{$countryName}%")
                ->orWhere(function ($inner) use ($countryName) {
                    $inner->where(function ($blank) {
                        $blank->whereNull('country')->orWhere('country', '');
                    })->where(function ($owner) use ($countryName) {
                        $owner->whereHas('company', function ($company) use ($countryName) {
                            $company->where('country', 'LIKE', "%{$countryName}%");
                        })->orWhereHas('agency', function ($agency) use ($countryName) {
                            $agency->where('country', 'LIKE', "%{$countryName}%");
                        });
                    });
                });
        });
    }
}

if (! function_exists('finalizeJobForListing')) {
    /**
     * Ensure a newly posted job has listing fields needed for /jobs visibility.
     */
    function finalizeJobForListing(\App\Models\Job $job): void
    {
        $updates = [];

        if (blank($job->job_roles)) {
            $updates['job_roles'] = 'public';
        }

        if (blank($job->country)) {
            $job->loadMissing('company', 'agency');

            $country = $job->company?->country
                ?? $job->agency?->country
                ?? $job->ip_country
                ?? optional(\Modules\Location\Entities\Country::find(loadSetting()->app_country))->name;

            if (filled($country)) {
                $updates['country'] = $country;
            }
        }

        if ($updates !== []) {
            $job->update($updates);
        }
    }
}

if (! function_exists('candidateFeaturedPlan')) {
    function candidateFeaturedPlan(): ?\App\Models\CandidatePlan
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('candidate_plans')) {
            return null;
        }

        return \App\Models\CandidatePlan::query()->first();
    }
}

if (! function_exists('seedLocationSessionFromNames')) {
    function seedLocationSessionFromNames(?string $countryName, ?string $stateName = null, ?string $cityName = null): void
    {
        if (config('templatecookie.map_show') || empty($countryName)) {
            return;
        }

        $country = \App\Models\SearchCountry::where('name', $countryName)->first();
        $state = null;
        $city = null;

        if ($stateName && $country) {
            $state = \App\Models\State::where('name', $stateName)
                ->where('country_id', $country->id)
                ->first();
        }

        if ($cityName && $state) {
            $city = \App\Models\City::where('name', $cityName)
                ->where('state_id', $state->id)
                ->first();
        }

        session([
            'selectedCountryId' => $countryName,
            'selectedStateId' => $stateName,
            'selectedCityId' => $cityName,
            'selectedCountryLong' => $country->long ?? null,
            'selectedCountryLat' => $country->lat ?? null,
            'selectedStateLong' => $state->long ?? null,
            'selectedStateLat' => $state->lat ?? null,
            'selectedCityLong' => $city->long ?? null,
            'selectedCityLat' => $city->lat ?? null,
        ]);
    }
}

if (! function_exists('updateMap')) {
    function updateMap($data)
    {
        if (empty(config('templatecookie.map_show'))) {
            session()->put('location', [
                'country' => session('selectedCountryId'),
                'region' => session('selectedStateId'),
                'district' => session('selectedCityId'),
                'lng' => session('selectedCityLong') ?? session('selectedStateLong') ?? session('selectedCountryLong'),
                'lat' => session('selectedCityLat') ?? session('selectedStateLat') ?? session('selectedCountryLat'),
            ]);
        }
        $location = session()->get('location');

        if ($location) {
            $region = array_key_exists('region', $location) ? $location['region'] : '';
            $country = array_key_exists('country', $location) ? $location['country'] : '';
            $district = array_key_exists('district', $location) ? $location['district'] : '';
            $exactLocation = array_key_exists('exact_location', $location) ? $location['exact_location'] : '';

            // Do not wipe dropdown-saved location when the map session has no real data.
            $hasMapData = filled($country) || filled($region) || filled($district) || filled($exactLocation);
            if (! $hasMapData) {
                session()->forget('location');

                return true;
            }

            if ($region == 'undefined') {
                $address = Str::slug($country);
                $region = '';
            } else {
                $address = Str::slug($region.'-'.$country);
            }

            $data->update([
                'address' => $address,
                'neighborhood' => array_key_exists('neighborhood', $location) ? $location['neighborhood'] : '',
                'locality' => array_key_exists('locality', $location) ? $location['locality'] : '',
                'place' => array_key_exists('place', $location) ? $location['place'] : '',
                'district' => $district,
                'postcode' => array_key_exists('postcode', $location) ? $location['postcode'] : '',
                'region' => $region ?? '',
                'country' => $country,
                'long' => array_key_exists('lng', $location) ? $location['lng'] : '',
                'lat' => array_key_exists('lat', $location) ? $location['lat'] : '',
                'exact_location' => $exactLocation,
            ]);
            session()->forget('location');
            session([
                'selectedCountryId' => null,
                'selectedStateId' => null,
                'selectedCityId' => null,
                'selectedCountryLong' => null,
                'selectedCountryLat' => null,
                'selectedStateLong' => null,
                'selectedStateLat' => null,
                'selectedCityLong' => null,
                'selectedCityLat' => null,
            ]);
        }

        return true;
    }
}

if (! function_exists('selected_country')) {
    function selected_country()
    {
        $selected_country = session()->get('selected_country');

        if ($selected_country) {
            $countries = loadAllCountries();
            $country = $countries->where('id', $selected_country)->first() ?? loadCountry();

        } else {
            $country = loadCountry();
        }

        return $country;

        // $selected_country = session()->get('selected_country');
        // $country = Country::find($selected_country) ?? Country::first();

        // return $country;
    }
}

if (! function_exists('get_file_size')) {
    function get_file_size($file)
    {
        if (file_exists($file)) {
            $file_size = File::size($file) / 1024 / 1024;

            return round($file_size, 4).' MB';
        }

        return '0 MB';
    }
}

/**
 * Resolve an uploaded file path stored as a public-relative or storage path.
 */
if (! function_exists('resolve_uploaded_file_path')) {
    function resolve_uploaded_file_path(?string $file): ?string
    {
        if (! is_string($file) || trim($file) === '') {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $file), '/');
        $candidates = array_filter([
            $relative,
            public_path($relative),
            storage_path('app/public/'.$relative),
            storage_path('app/'.$relative),
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}

/**
 * Write large HTML to mPDF without hitting pcre.backtrack_limit.
 *
 * @param  \Mpdf\Mpdf  $mpdf
 */
if (! function_exists('mpdf_write_html_chunked')) {
    function mpdf_write_html_chunked($mpdf, string $html, int $chunkSize = 40000): void
    {
        $current = (int) ini_get('pcre.backtrack_limit');
        if ($current < 10000000) {
            @ini_set('pcre.backtrack_limit', '10000000');
        }

        // Prefer CSS first, then body — reduces regex pressure on huge bilingual templates.
        if (preg_match('/<style\b[^>]*>([\s\S]*?)<\/style>/i', $html, $styleMatch)) {
            $css = '<style>'.$styleMatch[1].'</style>';
            $body = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $html, 1) ?? $html;
            try {
                $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
                $html = $body;
            } catch (\Throwable $e) {
                // Keep full HTML if CSS mode is unavailable.
            }
        }

        $length = strlen($html);
        if ($length <= $chunkSize) {
            $mpdf->WriteHTML($html);

            return;
        }

        $offset = 0;
        while ($offset < $length) {
            $remaining = $length - $offset;
            $size = min($chunkSize, $remaining);
            $chunk = substr($html, $offset, $size);

            if ($size === $chunkSize && $offset + $size < $length) {
                $breakAt = strrpos($chunk, '>');
                if ($breakAt !== false && $breakAt > (int) ($chunkSize * 0.4)) {
                    $chunk = substr($chunk, 0, $breakAt + 1);
                }
            }

            $mpdf->WriteHTML($chunk);
            $offset += strlen($chunk);
        }
    }
}

/**
 * Download a standard (non-bilingual) resume view as a single A4 PDF.
 */
if (! function_exists('download_resume_pdf')) {
    function download_resume_pdf(string $view, array $data, string $filename)
    {
        @ini_set('memory_limit', '256M');
        @set_time_limit(120);

        $data['compactPdf'] = true;
        $data['forPdf'] = true;

        $pdf = \PDF::loadView($view, $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
            ]);

        return $pdf->download($filename);
    }
}

/**
 * Download a (often large) bilingual CV HTML as PDF via mPDF.
 * Raises memory/time limits; enables heavy OTL only for RTL locales.
 */
if (! function_exists('mpdf_download_bilingual_cv')) {
    function mpdf_download_bilingual_cv(string $html, string $downloadName, ?string $locale = null)
    {
        @ini_set('memory_limit', '512M');
        @ini_set('pcre.backtrack_limit', '10000000');
        @set_time_limit(180);

        $needsRtl = function_exists('resumeIsRtlLocale') && resumeIsRtlLocale($locale);
        $needsMpdf = function_exists('resumeNeedsMpdfEngine') && resumeNeedsMpdfEngine($locale);

        $html = preg_replace('/<!--[\s\S]*?-->/', '', $html) ?? $html;
        $html = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html) ?? $html;

        // Latin-script bilingual CVs: DomPDF is faster. Hindi/Arabic/CJK need mPDF.
        if (! $needsMpdf) {
            $pdf = \PDF::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 96,
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                ]);

            return $pdf->download($downloadName);
        }

        $defaultFont = $needsRtl ? 'dejavusans' : 'freesans';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 6,
            'margin_bottom' => 6,
            'margin_left' => 6,
            'margin_right' => 6,
            'default_font' => $defaultFont,
            'useOTL' => 0xFF,
            'useKashida' => $needsRtl ? 75 : 0,
            'simpleTables' => true,
            'packTableData' => true,
        ]);

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->autoVietnamese = false;
        if ($needsRtl) {
            $mpdf->baseScript = 1;
            $mpdf->autoArabic = true;
        }

        // Prefer FreeSans for Devanagari (Hindi) shaping — DejaVu lacks proper conjuncts.
        $locale = strtolower((string) $locale);
        if (in_array($locale, ['hi', 'mr', 'ne'], true)) {
            $html = str_replace(
                "font-family: 'DejaVu Sans', Arial, sans-serif;",
                "font-family: freesans, 'FreeSans', DejaVu Sans, sans-serif;",
                $html
            );
        }

        mpdf_write_html_chunked($mpdf, $html, 50000);

        return $mpdf->Output($downloadName, \Mpdf\Output\Destination::DOWNLOAD);
    }
}

/**
 * Increases or decreases the brightness of a color by a percentage of the current brightness.
 *
 * @param  string  $hexCode  Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
 * @param  float  $adjustPercent  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
 * @return string
 *
 * @author  maliayas
 */
if (! function_exists('adjustBrightness')) {
    function adjustBrightness($hexCode, $adjustPercent)
    {
        $hexCode = ltrim($hexCode, '#');

        if (strlen($hexCode) == 3) {
            $hexCode = $hexCode[0].$hexCode[0].$hexCode[1].$hexCode[1].$hexCode[2].$hexCode[2];
        }

        $hexCode = array_map('hexdec', str_split($hexCode, 2));

        foreach ($hexCode as &$color) {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);

            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }

        return '#'.implode($hexCode);
    }
}

if (! function_exists('current_country_code')) {
    function current_country_code()
    {
        if (selected_country()) {
            $country_code = selected_country()->sortname;
        } else {
            $setting = loadSetting();

            if ($setting->app_country_type != 'multiple_base') {
                $country_code = Country::find($setting->app_country)->sortname;
            } else {
                return '';
            }
        }

        return $country_code;
    }
}

if (! function_exists('phone_country_iso')) {
    /**
     * Resolve a 2-letter ISO country code for intl-tel-input defaults.
     */
    function phone_country_iso(?string $countryName = null): string
    {
        $fallback = strtolower((string) (current_country_code() ?: 'pk'));

        if (! $countryName) {
            return strlen($fallback) === 2 ? $fallback : 'pk';
        }

        static $map = null;

        if ($map === null) {
            $map = [];
            $path = base_path('resources/seed-data/search_countries.json');

            if (file_exists($path)) {
                $rows = json_decode(file_get_contents($path), true) ?: [];

                foreach ($rows as $row) {
                    if (! empty($row['name']) && ! empty($row['iso2'])) {
                        $map[strtolower($row['name'])] = strtolower($row['iso2']);
                    }
                }
            }
        }

        $iso = $map[strtolower($countryName)] ?? $fallback;

        return strlen($iso) === 2 ? $iso : 'pk';
    }
}

if (! function_exists('cw_json_array')) {
    /**
     * Normalize a DB JSON column or Eloquent array-cast value to a plain array.
     */
    function cw_json_array(mixed $value, array $default = []): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : $default;
        }

        return $default;
    }
}

if (! function_exists('cw_profession_labels')) {
    /** @return list<string> */
    function cw_profession_labels(mixed $stored): array
    {
        $labels = [];

        foreach (cw_json_array($stored) as $value) {
            if (is_numeric($value)) {
                $profession = Profession::find((int) $value);
                $labels[] = $profession ? (string) $profession->name : (string) $value;
            } else {
                $text = trim((string) $value);
                if ($text !== '') {
                    $labels[] = $text;
                }
            }
        }

        return array_values(array_unique($labels));
    }
}

if (! function_exists('cw_industry_labels')) {
    /** @return list<string> */
    function cw_industry_labels(mixed $stored): array
    {
        $labels = [];

        foreach (cw_json_array($stored) as $value) {
            if (is_numeric($value)) {
                $industry = \App\Models\IndustryType::find((int) $value);
                $labels[] = $industry ? (string) $industry->name : (string) $value;
            } else {
                $text = trim((string) $value);
                if ($text !== '') {
                    $labels[] = $text;
                }
            }
        }

        return array_values(array_unique($labels));
    }
}

if (! function_exists('cw_normalize_multi_input')) {
    /**
     * Read a multi-value request field (Select2) with JSON payload fallback.
     *
     * @return list<int|string>
     */
    function cw_normalize_multi_input(\Illuminate\Http\Request $request, string $arrayKey, string $payloadKey): array
    {
        $values = $request->input($arrayKey, []);

        if (! is_array($values)) {
            $values = ($values !== null && $values !== '') ? [$values] : [];
        }

        if ($values === [] && $request->filled($payloadKey)) {
            $values = cw_json_array($request->input($payloadKey));
        }

        return array_values(array_filter($values, static fn ($v) => $v !== null && $v !== ''));
    }
}

if (! function_exists('cw_resolve_profession_ids')) {
    /** @param  array<int|string>  $values */
    function cw_resolve_profession_ids(array $values): array
    {
        $ids = [];
        $locale = app()->getLocale();

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
                continue;
            }

            $name = trim((string) $value);
            if ($name === '') {
                continue;
            }

            $translation = ProfessionTranslation::query()
                ->where(function ($q) use ($name) {
                    $q->where('name', $name)->orWhere('name', 'LIKE', '%'.$name.'%');
                })
                ->first();

            if ($translation) {
                $ids[] = (int) $translation->profession_id;
                continue;
            }

            try {
                $profession = Profession::create(['name' => $name]);
                foreach (loadLanguage() as $language) {
                    $profession->translateOrNew($language->code)->name = $name;
                }
                $profession->save();
                $ids[] = (int) $profession->id;
            } catch (\Throwable $e) {
                \Log::warning('cw_resolve_profession_ids: '.$e->getMessage());
            }
        }

        return array_values(array_unique($ids));
    }
}

if (! function_exists('cw_resolve_industry_ids')) {
    /** @param  array<int|string>  $values */
    function cw_resolve_industry_ids(array $values): array
    {
        $ids = [];
        $locale = app()->getLocale();

        foreach ($values as $value) {
            if (is_numeric($value)) {
                $ids[] = (int) $value;
                continue;
            }

            $name = trim((string) $value);
            if ($name === '') {
                continue;
            }

            $translation = \App\Models\IndustryTypeTranslation::query()
                ->where('locale', $locale)
                ->where(function ($q) use ($name) {
                    $q->where('name', $name)->orWhere('name', 'LIKE', '%'.$name.'%');
                })
                ->first();

            if ($translation) {
                $ids[] = (int) $translation->industry_type_id;
                continue;
            }

            try {
                $industry = \App\Models\IndustryType::create();
                $industry->translateOrNew($locale)->name = $name;
                $industry->save();
                $ids[] = (int) $industry->id;
            } catch (\Throwable $e) {
                \Log::warning('cw_resolve_industry_ids: '.$e->getMessage());
            }
        }

        return array_values(array_unique($ids));
    }
}

if (! function_exists('default_phone_country_iso')) {
    /**
     * Best default ISO2 for phone inputs: profile → session/site country → GeoIP → pk.
     */
    function default_phone_country_iso(): string
    {
        $countryName = null;

        if (auth()->check()) {
            $user = auth()->user();
            $countryName = optional($user->candidate)->country
                ?? optional($user->company)->country
                ?? optional($user->agency)->country;
        }

        if ($countryName) {
            return phone_country_iso($countryName);
        }

        if ($selected = selected_country()) {
            return phone_country_iso($selected->name);
        }

        try {
            $ip = request()->ip();
            if ($ip && ! in_array($ip, ['127.0.0.1', '::1'], true)) {
                $geo = GeoIP::getLocation($ip);
                $iso = strtolower((string) ($geo->iso_code ?? ''));
                if (strlen($iso) === 2) {
                    return $iso;
                }
            }
        } catch (\Throwable $e) {
            // fall through to phone_country_iso default
        }

        return phone_country_iso(null);
    }
}

if (! function_exists('default_destination_country_name')) {
    /**
     * Default destination for nominating workers / visa: GeoIP only.
     * Do not use selected_country() — that is the site job-filter country and often wrong
     * (e.g. first/random CMS location). On localhost with no GeoIP, leave empty so the user picks.
     */
    function default_destination_country_name(): ?string
    {
        try {
            $ip = request()->ip();
            if ($ip && ! in_array($ip, ['127.0.0.1', '::1'], true)) {
                $geo = GeoIP::getLocation($ip);
                $iso = strtoupper((string) ($geo->iso_code ?? ''));
                if (strlen($iso) === 2) {
                    $byIso = \App\Models\SearchCountry::query()
                        ->whereRaw('UPPER(short_name) = ?', [$iso])
                        ->value('name');
                    if ($byIso) {
                        return $byIso;
                    }
                }
                $geoName = trim((string) ($geo->country ?? ''));
                if ($geoName !== '') {
                    $byName = \App\Models\SearchCountry::query()
                        ->whereRaw('LOWER(name) = ?', [strtolower($geoName)])
                        ->value('name');
                    if ($byName) {
                        return $byName;
                    }
                }
            }
        } catch (\Throwable $e) {
            // no reliable IP location
        }

        return null;
    }
}

if (! function_exists('user_home_route')) {
    /**
     * Role-aware dashboard/home URL for frontend (portal) users.
     */
    function user_home_route($user = null): string
    {
        $user = $user ?: auth('user')->user();

        if (! $user) {
            return route('website.home');
        }

        if ($user->role === 'company') {
            $redirect = \App\Services\Company\CompanyDocumentVerificationService::redirectIfBlocked($user->company);
            if ($redirect) {
                return $redirect->getTargetUrl();
            }
        }

        return match ($user->role) {
            'candidate' => route('candidate.dashboard'),
            'company'   => route('company.dashboard'),
            'agency'    => route('agency.dashboard'),
            'agent'     => route('agent.dashboard'),
            default     => route('website.home'),
        };
    }
}

if (! function_exists('user_post_auth_route')) {
    /**
     * Where to send a user immediately after register/login (OTP gate when required).
     */
    function user_post_auth_route($user = null): string
    {
        $user = $user ?: auth('user')->user();

        if (! $user) {
            return route('website.home');
        }

        // OTP before document upload / dashboard for portal roles that require it.
        if (in_array($user->role, ['company', 'agent', 'agency'], true) && ! $user->is_otp_verified) {
            return route('otp.verify');
        }

        return user_home_route($user);
    }
}

/**
 * Set ip wise country, currency and language
 *
 * @return void
 */
if (! function_exists('setLocationCurrency')) {
    function setLocationCurrency()
    {
        $ip = request()->ip();
        // $ip = '103.102.27.0'; // Bangladesh
        // $ip = '105.179.161.212'; // Mauritius
        // $ip = '197.246.60.160'; // Egypt
        // $ip = '107.29.65.61'; // United States"
        // $ip = '46.39.160.0'; // Czech Republic
        // $ip = "94.112.58.11"; // Czechia

        if ($ip && $ip != '127.0.0.1') {
            $geo = GeoIP::getLocation($ip);

            // Set the currency
            if (! session()->has('current_currency')) {
                $currency = Modules\Currency\Entities\Currency::where('code', $geo->currency)->first() ?? Modules\Currency\Entities\Currency::where('code', config('templatecookie.currency'))->first();

                if ($currency) {
                    session(['current_currency' => $currency]);
                } else {
                    session(['current_currency' => Modules\Currency\Entities\Currency::first()]);
                }
            }

            // Set the language
            if (! session()->has('current_lang')) {
                $path = base_path('resources/backend/dummy-data/country_currency_language.json');
                $country_language_currency = json_decode(file_get_contents($path), true);
                $key = array_search($geo->iso_code, array_column($country_language_currency, 'code'));
                $country_language_currency = $country_language_currency[$key];
                $lang_code = $country_language_currency['language']['code'];
                $language = Language::where('code', $lang_code)->first();

                if ($language) {
                    session(['current_lang' => $language]);
                } else {
                    session(['current_lang' => Language::where('code', config('templatecookie.default_language'))->first()]);
                }
            }

            // Set the country
            // $selected_country = session('country_code');
            $selected_country = session('selected_country');

            if (! session()->has('selected_country')) {
                // if (!session()->has('country_code')) {
                if ($selected_country != 'all') {
                    if ($ip) {
                        $current_user_data = Location::get($ip);
                    }
                    if ($current_user_data) {
                        $user_country = $current_user_data->countryName;
                        if ($user_country) {
                            $database_country = Country::where('name', $user_country)->where('status', 1)->first();
                            if ($database_country) {
                                // $selected_country = session()->get('country_code');
                                $selected_country = session()->get('selected_country');
                                if (! $selected_country) {
                                    // session()->put('country_code', $database_country->sortname);
                                    session()->put('selected_country', $database_country->id);

                                    return true;
                                }
                            }
                        }
                    }
                }
            } else {
                // $selected_country = session('country_code');
                $selected_country = session('selected_country');
            }
        }
    }
}

/**
 * @param  string  $date
 *                        Date format
 */
if (! function_exists('getLanguageByCode')) {
    function getLanguageByCode($code)
    {
        $languages = loadLanguage();

        return $languages->where('code', $code)->value('name');
    }
}

/**
 * @param  string  $date
 *                        Date format
 */
if (! function_exists('getLanguageByCodeInLookUp')) {
    function getLanguageByCodeInLookUp($code, $languages)
    {
        $language = $languages->where('code', $code)->first();

        return $language ? $language->name : '';
    }
}

/**
 * @param  string  $date
 *                        Date format
 */
if (! function_exists('currentLangCode')) {
    function currentLangCode()
    {

        if (session('current_lang')) {
            return session('current_lang')->code;
        } else {
            return loadSystemLanguageCode();
        }
    }
}

/**
 * @param  string  $date
 *                        Date format
 */
if (! function_exists('dateFormat')) {
    function dateFormat($date, $format = 'F Y')
    {
        return \Carbon\Carbon::createFromFormat($format, $date)->toDateTimeString();
    }
}

/* Currency position
    *
    * @param String $date
    */
if (! function_exists('currencyPosition')) {
    function currencyPosition($amount, $applyCurrencyRate = false, $current_currency = null)
    {
        if (! $current_currency) {
            $current_currency = currentCurrency();
        }
        $symbol = $current_currency->symbol;
        $position = $current_currency->symbol_position;

        if ($applyCurrencyRate) {
            $amount = $amount / getCurrencyRate();
            $amount = round($amount, 2);
        }

        if ($position == 'left') {
            return $symbol.' '.$amount;
        } else {
            return $amount.' '.$symbol;
        }

        return $amount;
    }
}

/**
 * Authenticate candidate
 */
if (! function_exists('currentCandidate')) {
    function currentCandidate()
    {
        return authUser()->candidate;
    }
}

/**
 * Limit a Job query to age-eligible listings for the logged-in seeker.
 * Guests and non-candidate roles are unchanged.
 *
 * @param  \Illuminate\Database\Eloquent\Builder|\App\Models\Job  $query
 * @return \Illuminate\Database\Eloquent\Builder|\App\Models\Job
 */
if (! function_exists('applyCandidateAgeFilter')) {
    function applyCandidateAgeFilter($query)
    {
        $user = authUser();
        if (! $user && auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
        }

        if (! $user || ($user->role ?? null) !== 'candidate') {
            return $query;
        }

        $candidate = $user->candidate ?? null;
        $age = $candidate?->resolvedAge();

        return $query->matchingCandidateAge($age);
    }
}

/**
 * Authenticate candidate
 */
if (! function_exists('currentCompany')) {
    function currentCompany()
    {
        return authUser()->company;
    }
}
if (! function_exists('currentAgency')) {
    function currentAgency()
    {
        return authUser()->agency;
    }
}

/**
 * Authenticate user
 */
if (! function_exists('authUser')) {
    function authUser()
    {
        $guard = auth('user');
        $user = $guard->user();

        if ($user) {
            return $user;
        }

        // Session still holds a user id but the record is gone — clear stale login.
        if ($guard->id()) {
            $guard->logout();
        }

        return null;
    }
}

/* Get format number for currency
    *
    * @param String $path
    */
if (! function_exists('getFormattedNumber')) {
    function getFormattedNumber(
        $value,
        $currencyCode = 'USD',
        $locale = 'en_US',
        $style = NumberFormatter::DECIMAL,
        $precision = 0,
        $groupingUsed = true,
    ) {
        if (session()->has('current_lang')) {
            $locale = currentLanguage()->code.'_us' ?? $locale;
        }

        $currencyCode = currentCurrencyCode();
        $formatter = new NumberFormatter($locale, $style);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        $formatter->setAttribute(NumberFormatter::GROUPING_USED, $groupingUsed);
        if ($style == NumberFormatter::CURRENCY) {
            $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currencyCode);
        }

        return $formatter->format($value / getCurrencyRate());
    }
}

/**
 * Checks jobs status like highlighted or featured
 *
 * @param  string  $date
 * @return bool
 */
if (! function_exists('isFuture')) {
    function isFuture($date = null): bool
    {

        if ($date) {
            return Carbon::parse($date)->isFuture();
        }

        return false;
    }
}

if (! function_exists('getEmailTemplateFormatFlagsByType')) {
    function getEmailTemplateFormatFlagsByType($type)
    {
        return \App\Http\Controllers\Admin\EmailTemplateController::getFormatterByType($type) ?? [];
    }
}

if (! function_exists('getFormattedTextByType')) {
    function getFormattedTextByType($type, $data = null)
    {
        return \App\Http\Controllers\Admin\EmailTemplateController::getFormattedTextByType($type, $data);
    }
}

/**
 * get formatted mail template
 *
 * @param  string  $type
 * @param  mixed  $data
 * @return string formatted mail content
 */
if (! function_exists('getFormattedMail')) {
    function getFormattedMail($type, $data)
    {
        return \App\Http\Controllers\Admin\EmailTemplatesController::formatMessage($type, $data);
    }
}

/**
 * get list of available format flags
 *
 * @param  string  $type
 * @return array list of available format flags
 */
if (! function_exists('getFormatFlagsByType')) {
    function getFormatFlagsByType($type)
    {
        return \App\Http\Controllers\Admin\EmailTemplatesController::getFormatByType($type)['search'] ?? [];
    }
}

/**
 * Create avatar image
 *
 * @param  string  $name
 * @param  string  $path
 * @return bool
 */

//  if (! function_exists('createAvatar')) {
//     function createAvatar($name = null, $path = 'uploads/images'): string
//     {
//         if (! File::exists($path)) {
//             File::makeDirectory($path, $mode = 0777, true, true);
//         }

//         $name = $name ? $name.'_'.time().'_'.uniqid() : time().'_'.uniqid();
//         Avatar::create($name)->setDimension(250, 250)->save("{$path}/{$name}.png", 100);

//         return "{$path}/{$name}.png";
//     }
// }

if (! function_exists('createAvatar')) {
    function createAvatar($name, $path, $setDimension = null, $quality = 60): string
    {
        $mainAvatar = Avatar::create($name);

        if (! File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $name = $name ? $name.'_'.time().'_'.uniqid() : time().'_'.uniqid();

        if ($setDimension) {
            $mainAvatar->setDimension($setDimension[0], $setDimension[1]);
        }

        // Avatar::create($name)->setDimension($setDimension[0], $setDimension[1])->save("{$path}/{$name}.jpg", $quality);
        $mainAvatar->save($path.'/'.$name.'jpg', $quality);
        // Avatar::create($name)->setDimension($setDimension[0], $setDimension[1])->save("{$path}/{$name}.jpg", $quality);

        // dd("{$path}/{$name}.jpg", $quality);

        // return "{$path}/{$name}.jpg";
        return $path.'/'.$name.'jpg';
    }
}

if (! function_exists('generateReference')) {
    function generateReference(?string $transactionPrefix = null)
    {
        if ($transactionPrefix) {
            return $transactionPrefix.'_'.uniqid(time());
        }

        return 'flw_'.uniqid(time());
    }
}

/**
 * URL match helper
 *
 * @param  string  $current_url
 * @param  string  $value_url
 * @return bool
 */
if (! function_exists('urlMatch')) {
    function urlMatch(?string $current_url = null, ?string $value_url = null)
    {
        if ($current_url == $value_url) {
            return true;
        }

        return false;
    }
}

if (! function_exists('currentLocation')) {
    function currentLocation()
    {
        $ip = request()->ip();
        // $ip = '103.102.27.0'; // Bangladesh
        // $ip = '105.179.161.212'; // Mauritius
        // $ip = '110.33.122.75'; // AUD
        // $ip = '5.132.255.255'; // SA
        // $ip = '107.29.65.61'; // United States"
        // $ip = '46.39.160.0'; // Czech Republic
        // $ip = "94.112.58.11"; // Czechia

        $current_user_data = Location::get($ip);

        if ($ip && $current_user_data && $current_user_data->countryName) {
            return $current_user_data->countryName;
        }

        return null;
    }
}

if (! function_exists('loadCms')) {
    function loadCms()
    {
        return Cache::remember('Cms', now()->addDays(2), function () {
            return Cms::first();
        });
    }
}

if (! function_exists('loadCurrency')) {
    function loadCurrency()
    {
        return Cache::remember('Currency', now()->addDays(2), function () {
            return Currency::first();
        });
    }
}

if (! function_exists('loadSetting')) {
    function loadSetting()
    {
        return Cache::remember('setting', now()->addDays(2), function () {
            return Setting::first();
        });
    }
}

if (! function_exists('loadLanguage')) {
    function loadLanguage()
    {
        return Cache::remember('languages', now()->addDays(2), function () {
            return Language::all();
        });
    }
}

if (! function_exists('loadCookies')) {
    function loadCookies()
    {
        return Cache::remember('cookies', now()->addDays(30), function () {
            return Cookies::first();
        });
    }
}

if (! function_exists('loadDefaultLanguage')) {
    function loadDefaultLanguage()
    {
        return Cache::remember('default_language', now()->addDays(30), function () {
            return Language::where('code', config('templatecookie.default_language'))->first();
        });
    }
}

if (! function_exists('loadCountry')) {
    function loadCountry()
    {
        return Cache::remember('country', now()->addDays(30), function () {
            return Country::first();
        });
    }
}

if (! function_exists('loadAllCountries')) {
    function loadAllCountries()
    {
        return Cache::remember('countries', now()->addDays(30), function () {
            return Country::all();
        });
    }
}

if (! function_exists('loadActiveCountries')) {
    function loadActiveCountries()
    {
        return Cache::remember('active_countries', now()->addDays(30), function () {
            return Country::select('id', 'name', 'slug', 'icon')->active()->get();
        });
    }
}

if (! function_exists('loadAllCurrencies')) {
    function loadAllCurrencies()
    {
        return Cache::remember('currencies', now()->addDays(30), function () {
            return Currency::all();
        });
    }
}

/**
 * Get the system currency
 *
 * @return object
 */
if (! function_exists('loadSystemCurrency')) {
    function loadSystemCurrency()
    {
        return Cache::remember('systemCurrency', now()->addDays(30), function () {
            // return Modules\Currency\Entities\Currency::where('code', config('jobpilot.currency'))->first();
            return Modules\Currency\Entities\Currency::where('code', config('templatecookie.currency'))->first();
        });
    }
}

/**
 * Get the system currency
 *
 * @return object
 */
if (! function_exists('loadSystemLanguageCode')) {
    function loadSystemLanguageCode()
    {
        return Cache::remember('systemLanguageCode', now()->addDays(30), function () {
            return Language::where('code', config('templatecookie.default_language'))->value('code');
        });
    }
}

/**
 * Getting cache information
 *
 * @return string
 */
if (! function_exists('getCache')) {
    function getCache($name)
    {
        return Cache::get($name);
    }
}

/**
 * Getting cache information
 *
 * @return string
 */
if (! function_exists('forgetCache')) {
    function forgetCache($name)
    {
        Cache::forget($name);
        loadSetting();

        return true;
    }
}

/**
 * @param  $fullName
 * @return string
 */
if (! function_exists('maskFullName')) {
    function maskFullName($fullName)
    {
        $nameParts = explode(' ', $fullName); // Split the full name into parts
        $maskedName = '';

        foreach ($nameParts as $part) {
            $initial = substr($part, 0, 1); // Get the first letter
            $rest = substr($part, 1); // Get the rest of the letters
            $maskedName .= $initial.str_repeat('*', strlen($rest)).' '; // Replace the rest with asterisks
        }

        return rtrim($maskedName); // Remove trailing space and return the masked name
    }
}

if (! function_exists('advertisementCode')) {
    function advertisementCode($page_slug)
    {

        $ads = loadAdvertisements();
        $code = '';
        $ad = $ads->where('page_slug', $page_slug)->where('status', 1)->first();
        if ($ad) {
            $code = $ad->ad_code;
        }

        return $code;
    }
}

if (! function_exists('advertisement_status')) {
    function advertisement_status($page_slug)
    {
        $ad_status = 0;
        $ad_status = Advertisement::where('page_slug', '=', $page_slug)->value('status');

        return $ad_status;
    }
}

if (! function_exists('loadAdvertisements')) {
    function loadAdvertisements()
    {
        return Cache::remember('advertisements', now()->addDays(30), function () {
            return Advertisement::all();
        });
    }
}

if (! function_exists('registrationTypeMeta')) {
    /**
     * Map ?type= registration query values to labels and portal roles.
     * Specialty signups reuse seeker / employer / agency / agent / broker portals
     * until dedicated modules ship; specialty key is stored in users.signup_type.
     *
     * @return array{label: string, role: string, type: string, headline: string, description: string, profile: string}
     */
    function registrationTypeMeta(?string $type): array
    {
        $type = strtolower(trim((string) $type));

        return match ($type) {
            'seeker', 'candidate', 'job_seeker' => [
                'label' => __('seeker'),
                'role' => 'candidate',
                'type' => 'seeker',
                'profile' => 'candidate',
                'headline' => __('job_seeker'),
                'description' => __('job_seeker_description'),
            ],
            'employer', 'company' => [
                'label' => __('employer'),
                'role' => 'company',
                'type' => 'employer',
                'profile' => 'company',
                'headline' => __('employer'),
                'description' => __('employer_company_description'),
            ],
            'agency' => [
                'label' => __('recruitment_agency'),
                'role' => 'agency',
                'type' => 'agency',
                'profile' => 'agency',
                'headline' => __('recruitment_agency'),
                'description' => __('recruitment_agency_description'),
            ],
            'agent' => [
                'label' => 'Agent / Facilitator',
                'role' => 'agent',
                'type' => 'agent',
                'profile' => 'agent',
                'headline' => 'Agent / Facilitator',
                'description' => __('recruitment_agent_description'),
            ],
            'broker', 'middleman', 'demand_partner' => [
                'label' => 'Broker / Middleman',
                'role' => 'broker',
                'type' => 'broker',
                'profile' => 'broker',
                'headline' => 'Broker / Middleman',
                'description' => 'Create demand requests and route them to Recruitment Agencies.',
            ],
            'labour_supply' => [
                'label' => 'Labour Supply Office',
                'role' => 'company',
                'type' => 'labour_supply',
                'profile' => 'company',
                'headline' => 'Labour Supply Office',
                'description' => 'Register your labour supply office and connect workforce to employers worldwide.',
            ],
            'hr_referral', 'hr' => [
                'label' => 'HR Referral Partner',
                'role' => 'agent',
                'type' => 'hr_referral',
                'profile' => 'agent',
                'headline' => 'HR Referral Partner',
                'description' => 'Refer candidates to employers and agencies and grow your referral network.',
            ],
            'domestic_office', 'domestic_worker_office' => [
                'label' => 'Domestic Worker Office',
                'role' => 'agency',
                'type' => 'domestic_office',
                'profile' => 'agency',
                'headline' => 'Domestic Worker Office',
                'description' => 'Register your domestic worker office and manage placements for household roles.',
            ],
            'domestic_worker', 'selected_domestic', 'nominated_worker' => [
                'label' => 'Selected Domestic Worker',
                'role' => 'candidate',
                'type' => 'domestic_worker',
                'profile' => 'candidate',
                'headline' => 'Selected Domestic Worker',
                'description' => 'Create your worker profile, upload documents, and get matched to household opportunities.',
            ],
            'university', 'education_institution' => [
                'label' => 'University / College / School',
                'role' => 'company',
                'type' => 'university',
                'profile' => 'company',
                'headline' => 'University / College / School',
                'description' => 'Register your institution and connect students with overseas education and career pathways.',
            ],
            'abroad_student', 'abroad_edu_student' => [
                'label' => 'Abroad Edu Student',
                'role' => 'candidate',
                'type' => 'abroad_student',
                'profile' => 'candidate',
                'headline' => 'Abroad Edu Student',
                'description' => 'Build your student profile and explore study-abroad and career support opportunities.',
            ],
            'eu_permit_specialist', 'eu_work_permit_specialist' => [
                'label' => 'EU Work Permit Specialist',
                'role' => 'agency',
                'type' => 'eu_permit_specialist',
                'profile' => 'agency',
                'headline' => 'EU Work Permit Specialist',
                'description' => 'Register as an EU work-permit specialist and support employers and seekers with permit cases.',
            ],
            'work_permit_seeker' => [
                'label' => 'Work Permit Seeker',
                'role' => 'candidate',
                'type' => 'work_permit_seeker',
                'profile' => 'candidate',
                'headline' => 'Work Permit Seeker',
                'description' => 'Create your seeker profile and get guidance toward EU and overseas work-permit opportunities.',
            ],
            default => [
                'label' => '',
                'role' => '',
                'type' => '',
                'profile' => '',
                'headline' => __('create_account'),
                'description' => __('footer_description'),
            ],
        };
    }
}

if (! function_exists('registrationPortalRoles')) {
    /**
     * Portal roles that receive company-document or agency-license forms.
     *
     * @return array{company_docs: string[], agency_license: string[]}
     */
    function registrationFormRequirements(?string $type): array
    {
        $type = strtolower(trim((string) $type));

        return [
            'company_docs' => in_array($type, ['employer', 'company'], true),
            'agency_license' => $type === 'agency',
        ];
    }
}
