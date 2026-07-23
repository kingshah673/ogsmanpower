<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { size: A4 portrait; margin: {{ !empty($compactPdf) ? '6mm' : '10mm' }}; }
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: {{ !empty($compactPdf) ? '9px' : '12px' }};
    line-height: 1.3;
}

.container {
    border: 2px solid #000;
    padding: 5px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td {
    border: 1px solid #000;
    padding: 4px;
    vertical-align: top;
}

.title {
    background: #e6f0fa;
    font-weight: bold;
    color: #0b5394;
}

.label {
    width: 40%;
    font-weight: bold;
}

.value {
    width: 60%;
}

.highlight {
    background: yellow;
    font-weight: bold;
}

.red {
    color: red;
}

img.photo {
    width: 90px;
    height: 110px;
}

img.full {
    width: 100%;
    height: 300px;
    object-fit: contain;
}
</style>
</head>

<body>

<div class="container">

<table>
<tr>

<!-- ================= LEFT COLUMN ================= -->
<td width="65%">

    <!-- HEADER -->
    <table>
        <tr>
            <td rowspan="3" width="100" align="center">
                @if(!empty($candidate->photo))
                    <img src="{{ resumeImageSrc($candidate->photo) }}" class="photo">
                @else
                    N/A
                @endif
            </td>

            <td class="label">Last Name</td>
            <td class="label">First Name</td>
            <td class="label">Middle Name</td>
        </tr>

        <tr>
            <td>{{ $candidate->user->last_name ?? 'N/A' }}</td>
            <td>{{ $candidate->user->first_name ?? 'N/A' }}</td>
            <td>{{ $candidate->user->middle_name ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td colspan="3">
                Position:
                <span class="highlight">
                    {{ $candidate->position ?? 'N/A' }}
                </span>
            </td>
        </tr>

        <tr>
            <td></td>
            <td colspan="3">Contract: {{ $candidate->contract ?? 'N/A' }}</td>
        </tr>

        <tr>
            <td></td>
            <td colspan="3">Asking Salary: {{ $candidate->expected_salary ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- WORK EXPERIENCE -->
    <table>
        <tr><td colspan="2" class="title">Work Experience</td></tr>

        <tr>
            <td class="label">Position</td>
            <td class="value">
                {{ optional($candidate->experiences->first())->designation ?? 'N/A' }}
            </td>
        </tr>

        <tr>
            <td class="label">Location</td>
            <td class="value red">
                {{ optional($candidate->experiences->first())->location ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <!-- PERSONAL INFO -->
    <table>
        <tr><td colspan="2" class="title">Personal Information</td></tr>

        <tr><td class="label">Nationality</td><td>{{ $candidate->country ?? 'N/A' }}</td></tr>
        <tr><td class="label">Religion</td><td>{{ $candidate->religion ?? 'N/A' }}</td></tr>
        <tr><td class="label">Age</td><td>{{ $candidate->birth_date ? \Carbon\Carbon::parse($candidate->birth_date)->age : 'N/A' }}</td></tr>
        <tr><td class="label">Birthday</td><td>{{ $candidate->birth_date ?? 'N/A' }}</td></tr>
        <tr><td class="label">Birthplace</td><td>{{ $candidate->birth_place ?? 'N/A' }}</td></tr>
        <tr><td class="label">Civil Status</td><td>{{ $candidate->marital_status ?? 'N/A' }}</td></tr>
        <tr><td class="label">No. of Kids</td><td>{{ $candidate->kids ?? 'N/A' }}</td></tr>
        <tr><td class="label">Height</td><td>{{ $candidate->height ?? 'N/A' }}</td></tr>
        <tr><td class="label">Weight</td><td>{{ $candidate->weight ?? 'N/A' }}</td></tr>
        <tr><td class="label">Education</td>
            <td>{{ optional($candidate->education)->name ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- CONTACT -->
    <table>
        <tr><td class="label">Contacts</td><td class="red">{{ $candidate->user->phone ?? 'N/A' }}</td></tr>
        <tr><td class="label">Messenger</td><td>{{ $candidate->messenger ?? 'N/A' }}</td></tr>
        <tr><td class="label">Whatsapp</td><td>{{ $candidate->whatsapp ?? 'N/A' }}</td></tr>
    </table>

    <!-- SKILLS -->
    <table>
        <tr><td colspan="2" class="title">Skills Checklist</td></tr>

        <tr><td class="label">Communication</td><td>{{ $candidate->communication ?? 'N/A' }}</td></tr>
        <tr><td class="label">Arabic</td><td>{{ $candidate->arabic ?? 'N/A' }}</td></tr>
        <tr><td class="label">English</td><td>{{ $candidate->english ?? 'N/A' }}</td></tr>
        <tr><td class="label">Cleaning</td><td>{{ $candidate->cleaning ?? 'N/A' }}</td></tr>
        <tr><td class="label">Child Care</td><td>{{ $candidate->child_care ?? 'N/A' }}</td></tr>
        <tr><td class="label">Laundry</td><td>{{ $candidate->laundry ?? 'N/A' }}</td></tr>
        <tr><td class="label">Ironing</td><td>{{ $candidate->ironing ?? 'N/A' }}</td></tr>
        <tr><td class="label">Cooking</td><td>{{ $candidate->cooking ?? 'N/A' }}</td></tr>
        <tr><td class="label">Caregiving</td><td>{{ $candidate->caregiving ?? 'N/A' }}</td></tr>
    </table>

</td>

<!-- ================= RIGHT COLUMN ================= -->
<td width="35%">

    <!-- FULL IMAGE -->
    <div style="text-align:center;">
        @if(!empty($candidate->full_image))
            <img src="{{ resumeImageSrc($candidate->full_image) }}" class="full">
        @else
            N/A
        @endif
    </div>

    <!-- REMARKS -->
    <table>
        <tr><td class="title">Remarks</td></tr>
        <tr>
            <td style="font-size:11px;">
                {{ $candidate->remarks ?? 'N/A' }}
            </td>
        </tr>
    </table>

    <!-- PASSPORT -->
    <table>
        <tr><td colspan="2" class="title">Passport Details</td></tr>

        <tr><td class="label">Number</td><td>{{ $candidate->passport_number ?? 'N/A' }}</td></tr>
        <tr><td class="label">Date Issued</td><td>{{ $candidate->passport_issue_date ?? 'N/A' }}</td></tr>
        <tr><td class="label">Date Expired</td><td>{{ $candidate->passport_expiry_date ?? 'N/A' }}</td></tr>
        <tr><td class="label">Issued At</td><td>{{ $candidate->place_of_issue ?? 'N/A' }}</td></tr>
    </table>

    <!-- ADDRESS -->
    <table>
        <tr><td colspan="2" class="title">Address and Next Kin</td></tr>

        <tr><td class="label">Address</td><td>{{ $candidate->address ?? 'N/A' }}</td></tr>
        <tr><td class="label">Next Kin</td><td>{{ $candidate->next_kin ?? 'N/A' }}</td></tr>
        <tr><td class="label">Mobile</td><td>{{ $candidate->kin_mobile ?? 'N/A' }}</td></tr>
    </table>

</td>

</tr>
</table>

</div>

</body>
</html>