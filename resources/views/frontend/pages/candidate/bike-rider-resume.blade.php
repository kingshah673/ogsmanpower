<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page { size: A4 portrait; margin: {{ !empty($compactPdf) ? '6mm' : '10mm' }}; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            /* PDF-supported font */
            margin: 0;
            padding: 0px;
            background-color: {{ !empty($compactPdf) ? '#fff' : '#f8f8f8' }};
            font-size: {{ !empty($compactPdf) ? '9px' : '12px' }};
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: {{ !empty($compactPdf) ? '6px' : '10px' }};
            /* box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); */
            border-radius: 5px;
            border: 2px solid #003366;

        }

        .header table {
            width: 100%;
            border-collapse: collapse;
        }

        .header .logo,
        .header .qr-code {
            width: 80px;
            height: auto;
        }

        .header .logo {
            vertical-align: top;
        }

        .header .qr-code {
            vertical-align: top;
            text-align: right;
        }

        .section {
            margin-bottom: 10px;
        }

        h3 {
            border-bottom: 2px solid #003366;
            padding-bottom: 5px;
            color: #003366;
            margin-top: 15px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 0px;
            text-align: left;
            font-size: 12px;
        }

        .profile-image {
            width: 100px;
            height: auto;
            border-radius: 50%;
            border: 2px solid #003366;
            margin: 10px auto;
            display: block;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
        }

        p {
            font-size: 11px;
        }

        /* @media print {
            body {
                padding: 5px;
            }

            .container {
                max-width: 100%;
                box-shadow: none;
                padding: 10px;
            }
        } */
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <table>
                <tr>
                    <td style="width: 80px;">
                        {{-- <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path($setting->favicon_image_url))) }}" alt="Company Logo" class="logo"> --}}
                        <img src="{{ asset($setting->favicon_image_url) }}" alt="Company Logo" class="logo">

                    </td>
                    <td style="text-align: center;">
                        <h1 style="margin: 0; font-size: 30px; color: #003366;">OGS MANPOWER</h1>
                        <p style="margin: 0; font-size: 12px;">OGS Manpower Lic No. 2978 Pakistan</p>
                    </td>
                    <td style="width: 80px; text-align: right;">
                        {!! $qrCode !!}
                        {{-- <img src="data:image/png;base64, {!! $qrCode !!}" alt="QR Code"> --}}
                    </td>
                </tr>
            </table>
        </div>

        <!-- User Image & Applied For Sections Side by Side -->
        <div class="section">
            <h3>APPLIED FOR BIKE RIDER</h3>
            <table style="width: 100%;">
                <tr>
                    <!-- Left Side: User Image -->
                    <td style="text-align: center;">
                        @if ($candidate->photo)
                            <img src="{{ asset($candidate->photo) }}" alt="User Image" class="profile-image"
                                style="display: block; margin: 0 auto;"> 
                        @endif
                    </td>


                    <!-- Right Side: Expected Location and Salary -->
                    <td style="width: 75%; vertical-align: top;">
                        <table style="width: 100%;">
                            <tr>
                                <th style="text-align: left;">Expected Location</th>
                                <td>{{ $candidate->expected_country->name ?? '' }}</td>

                            </tr>
                            <tr>
                                <th style="text-align: left;">Expected Salary</th>
                                <td>{{ $candidate->expected_salary }}</td>
                            </tr>
                        </table>
                        {!! $candidate->bio !!}

                    </td>
                </tr>
            </table>
        </div>

        <!-- Personal Information & Passport Details -->
        <div class="section">
            <table style="width: 100%;">
                <tr>
                    <!-- Personal Information Section -->
                    <td style="width: 35%; vertical-align: top;">
                        <h3>Personal Information</h3>
                        <table style="width: 100%;">
                            <tr>
                                <th>Full Name</th>
                                <td>{{ $candidate->user->name }}</td>
                            </tr>
                            <tr>
                                <th>Country</th>
                                <td>{{ $candidate->country }}</td>
                            </tr>
                            <tr>
                                <th>Date of Birth</th>
                                <td>{{ $candidate->birth_date }}</td>
                            </tr>
                            <tr>
                                <th>Age</th>
                                <td>{{ \Carbon\Carbon::parse($candidate->birth_date)->age }}</td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td>{{ $candidate->district . ', ' . $candidate->region }}</td>

                            </tr>
                            <tr>
                                <th>Marital Status</th>
                                <td>{{ $candidate->marital_status }}</td>

                            </tr>
                        </table>
                    </td>

                    <!-- Passport Section -->
                    <td style="width: 35%; vertical-align: top;">
                        <h3>Passport Details</h3>
                        <table style="width: 100%;">
                            <tr>
                                <th>Passport Number</th>
                                <td>{{ $candidate->passport_number }}</td>
                            </tr>
                            <tr>
                                <th>Issue Date</th>
                                <td>{{ $candidate->passport_issue_date }}</td>
                            </tr>
                            <tr>
                                <th>Expiry Date</th>
                                <td>{{ $candidate->passport_expiry_date }}</td>
                            </tr>
                            <tr>
                                <th>Place of Issue</th>
                                <td>{{ $candidate->place_of_issue }}</td>
                            </tr>
                            <tr>
                                <th>CNIC Number</th>
                                <td>{{ $candidate->cnic_number }}</td>
                            </tr>
                        </table>
                    </td>

                    <!-- Education Section -->
                    <td style="width: 30%; vertical-align: top;">
                        <h3>Education</h3>
                        <table style="width: 100%;">
                            <tr>
                                <th>Level</th>
                                <th>Degree</th>
                                <th>Year</th>
                            </tr>
                            @foreach ($candidate->educations as $education)
                                <tr>
                                    <td>{{ $education->level }}</td>
                                    <td>{{ $education->degree }}</td>
                                    <td>{{ $education->year }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Experience, Languages, and Skills Sections -->
        <div class="section">
            <table style="width: 100%;">
                <tr>
                    <!-- Experience Section -->
                    <td style="width: 40%; vertical-align: top;">
                        <h3>Experience</h3>
                        <table style="width: 100%;">
                            <tr>
                                <th>Company</th>
                                <th>Designation</th>
                                <th>Period</th>
                            </tr>
                            @foreach ($candidate->experiences as $experience)
                                <tr>
                                    <td>{{ $experience->company }}</td>
                                    <td>{{ $experience->designation }}</td>
                                    <td>
                                        {{ formatTime($experience->start, 'd M Y') }} -
                                        {{ $experience->currently_working ? 'Currently Working' : formatTime($experience->end, 'd M Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>

                    <!-- Languages Section -->
                    <td style="width: 30%; vertical-align: top;">
                        <h3>Languages</h3>
                        <p>
                            @if ($candidate->languages && $candidate->languages->count() > 0)
                                {{ $candidate->languages->pluck('name')->implode(', ') }}
                            @endif
                        </p>
                    </td>

                    <!-- Skills Section -->
                    <td style="width: 30%; vertical-align: top;">
                        <h3>Skills</h3>
                        <p>
                            @if ($candidate->skills && $candidate->skills->count() > 0)
                                {{ $candidate->skills->pluck('name')->implode(', ') }}
                            @endif
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Custom Attributes Section -->
        @if ($candidate->attributes)
            <div class="section">
                <table style="width: 100%;">
                    <tr>
                        @foreach ($candidate->attributes as $at)
                            <th>{{ $at->attribute_name }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($candidate->attributes as $at)
                            <td>{{ $at->attribute_value }}</td>
                        @endforeach
                    </tr>
                </table>
            </div>
        @endif

        <div class="section">
            <table style="width: 100%;">
                <h3>Attachments</h3>

                <tr>
                    <td style="width: 50%; vertical-align: top;">
                        @if ($attachments && $attachments->license_image)
                            {{-- <img src="{{ asset('storage/candidates/' . $attachments->license_image) }}" alt="License"
                                style="width: 200px; height: 125px; margin-right: 20px;"> --}}
                        @endif
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        @if ($attachments && $attachments->passport_image)
                            {{-- <img src="{{ asset('storage/candidates/' . $attachments->passport_image) }}" alt="Passport"
                                style="width: 200px; height: 125px;"> --}}
                        @endif
                    </td>
                </tr>
            </table>
        </div>


        <!-- Footer -->
        <div class="footer">

            <p>Worker Video: <a href="https://youtube.com/shorts/SaF_FaBuB12?feature=share">View Video</a></p>
            <p>© OGS Manpower. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
