{{-- Register role picker modal (required by header Register / Sign up buttons) --}}
<style>
.role-main-title {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    position: relative;
    display: inline-block;
}
.role-main-title::after {
    content: '';
    width: 100%;
    height: 4px;
    background: #c97a08;
    position: absolute;
    left: 0;
    bottom: -8px;
}
.role-box {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 5px 18px rgba(0, 0, 0, .12);
    padding: 22px;
    min-height: 150px;
    margin: 20px;
    cursor: pointer;
    position: relative;
    transition: .3s;
    border: 2px solid transparent;
}
#registerTypeModal .role-box {
    margin: 0;
    min-height: 0;
    height: 100%;
    padding: 16px 14px 14px;
}
#registerTypeModal .role-title {
    font-size: 13px;
    margin-top: 12px;
    margin-bottom: 4px;
    line-height: 1.25;
}
#registerTypeModal .role-subtitle {
    font-size: 12px;
    margin-bottom: 6px;
}
#registerTypeModal .role-list {
    margin-bottom: 10px;
    padding-left: 1.1rem;
}
#registerTypeModal .role-list li {
    font-size: 11px;
    margin-bottom: 4px;
}
#registerTypeModal .role-btn {
    font-size: 13px;
    padding: 4px 10px;
    border: 1px solid #c97a08;
    background: #fff;
    color: #b36b08;
    border-radius: 8px;
}
.role-box:hover {
    transform: translateY(-5px);
    border-color: #c97a08;
}
.role-box.active {
    border-color: #198754;
}
.top-bar {
    height: 12px;
    background: #c97a08;
    border-radius: 12px 12px 0 0;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
}
.role-title {
    font-size: 18px;
    font-weight: 700;
    color: #b36b08;
    margin-top: 15px;
    margin-bottom: 8px;
    font-family: Georgia, serif;
}
.role-subtitle {
    color: #777;
    font-size: 16px;
}
</style>

<div class="modal fade" id="registerTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 bg-white shadow-none">
            <div class="modal-body p-3 p-md-4">
                <div class="text-center mb-3">
                    <h2 class="role-main-title">Who Are You? Choose Your Role</h2>
                    <p class="text-muted mb-0 small">Select how you want to register on OGS Manpower</p>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="role-box" onclick="selectRole(this,'seeker')">
                            <div class="top-bar"></div>
                            <h3 class="role-title">JOB SEEKER</h3>
                            <p class="role-subtitle">Find Your Dream Job</p>
                            <ul class="role-list">
                                <li>Upload your CV and search jobs worldwide.</li>
                                <li>Get daily job alerts via email.</li>
                            </ul>
                            <button type="button" class="role-btn">Get Started</button>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="role-box" onclick="selectRole(this,'employer')">
                            <div class="top-bar"></div>
                            <h3 class="role-title">EMPLOYER</h3>
                            <p class="role-subtitle">Post Jobs & Hire Talent</p>
                            <ul class="role-list">
                                <li>Register free and post job vacancies.</li>
                                <li>Find skilled & experienced talent.</li>
                            </ul>
                            <button type="button" class="role-btn">Get Started</button>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="role-box" onclick="selectRole(this,'agency')">
                            <div class="top-bar"></div>
                            <h3 class="role-title">RECRUITMENT AGENCY</h3>
                            <p class="role-subtitle">Manage Clients & Placements</p>
                            <ul class="role-list">
                                <li>Manage multiple client accounts.</li>
                                <li>Coordinate placements and grow your network.</li>
                            </ul>
                            <button type="button" class="role-btn">Get Started</button>
                        </div>
                    </div>

                    <div class="col-6 col-md-4">
                        <div class="role-box" onclick="selectRole(this,'agent')">
                            <div class="top-bar"></div>
                            <h3 class="role-title">AGENT / FACILITATOR</h3>
                            <p class="role-subtitle">Connect & Earn</p>
                            <ul class="role-list">
                                <li>Work as a headhunter or consultant.</li>
                                <li>Refer candidates and build your network.</li>
                            </ul>
                            <button type="button" class="role-btn">Get Started</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectRole(element, role) {
    document.querySelectorAll('#registerTypeModal .role-box').forEach(function (card) {
        card.classList.remove('active');
    });
    if (element) {
        element.classList.add('active');
    }
    window.location.href = @json(route('register')) + '?type=' + encodeURIComponent(role);
}
</script>
