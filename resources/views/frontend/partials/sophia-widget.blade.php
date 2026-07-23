<style>
/* ===================================================== */
/* OGS AI CHAT WIDGET */
/* COMPLETE ULTRA RESPONSIVE CSS */
/* FACEBOOK + LINKEDIN STYLE */
/* ===================================================== */

#ogs-ai-host-widget{

    position:fixed;

    right:20px;

    bottom:20px;

    z-index:999999;
}

/*
|--------------------------------------------------------------------------
| AVATAR
|--------------------------------------------------------------------------
*/

#ogs-ai-host-avatar{

    position:relative;

    width:70px;

    height:70px;

    border-radius:50%;

    overflow:hidden;

    background:#fff;

    cursor:pointer;

    box-shadow:0 10px 30px rgba(0,0,0,.2);

    transition:.3s ease;
}

#ogs-ai-host-avatar:hover{

    transform:translateY(-4px);
}

#ogs-ai-host-avatar img{

    width:100%;

    height:100%;

    object-fit:cover;
}

/*
|--------------------------------------------------------------------------
| ONLINE PULSE
|--------------------------------------------------------------------------
*/

.ai-pulse{

    position:absolute;

    width:14px;

    height:14px;

    border-radius:50%;

    background:#00c853;

    border:2px solid #fff;

    right:5px;

    bottom:5px;

    animation:pulse 1.5s infinite;
}

@keyframes pulse{

    0%{

        transform:scale(1);

        opacity:1;
    }

    100%{

        transform:scale(2);

        opacity:0;
    }
}

/*
|--------------------------------------------------------------------------
| UNREAD BADGE
|--------------------------------------------------------------------------
*/

#aiUnreadCount{

    position:absolute;

    top:-3px;

    right:-3px;

    min-width:22px;

    height:22px;

    padding:0 6px;

    border-radius:20px;

    background:#ff3040;

    color:#fff;

    font-size:11px;

    font-weight:700;

    display:none;

    align-items:center;

    justify-content:center;

    box-shadow:0 4px 12px rgba(0,0,0,.2);
}

/*
|--------------------------------------------------------------------------
| CHAT BOX
|--------------------------------------------------------------------------
*/

#ogs-ai-host-chat{

    position:fixed;

    right:20px;

    bottom:100px;

    width:380px;

    height:650px;

    max-width:calc(100vw - 40px);

    max-height:calc(100vh - 120px);

    background:#fff;

    border-radius:20px;

    overflow:hidden;

    display:none;

    flex-direction:column;

    border:1px solid #e4e6eb;

    box-shadow:0 20px 60px rgba(0,0,0,.2);

    animation:chatOpen .25s ease;
}

@keyframes chatOpen{

    from{

        opacity:0;

        transform:translateY(20px) scale(.95);
    }

    to{

        opacity:1;

        transform:translateY(0) scale(1);
    }
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.ogs-ai-header{

    height:72px;

    min-height:72px;

    background:#fff;

    border-bottom:1px solid #eee;

    padding:0 16px;

    display:flex;

    align-items:center;

    justify-content:space-between;
}

.ai-header-left{

    display:flex;

    align-items:center;

    gap:12px;
}

.mini-avatar{

    width:42px;

    height:42px;

    border-radius:50%;

    object-fit:cover;
}

.ai-header-info h6{

    margin:0;

    font-size:15px;

    font-weight:700;

    color:#111;
}

.ai-header-info small{

    color:#00c853;

    font-size:12px;
}

.ai-header-actions{

    display:flex;

    align-items:center;

    gap:8px;
}

/*
|--------------------------------------------------------------------------
| HEADER BUTTONS
|--------------------------------------------------------------------------
*/

#clearAIHost,
#clearAIChat,
#sendAIHostMessage,
#attachmentButton,
#ogsMicBtn{
    display:flex;
    align-items:center;
    justify-content:center;
}

.ogs-ai-icon{
    width:18px;
    height:18px;
    display:block;
    flex-shrink:0;
    color:currentColor;
}

#clearAIChat .ogs-ai-icon,
#closeAIHost .ogs-ai-icon{
    width:16px;
    height:16px;
}

#ogsMicBtn .icon-stop{
    display:none;
}

#ogsMicBtn.recording .icon-mic{
    display:none;
}

#ogsMicBtn.recording .icon-stop{
    display:block;
}

#closeAIHost,
#clearAIChat{

    width:34px;

    height:34px;

    border:none;

    border-radius:50%;

    background:#f0f2f5;

    cursor:pointer;

    font-size:18px;

    transition:.2s ease;
}

#closeAIHost:hover,
#clearAIChat:hover{

    background:#dde1e7;
}

/*
|--------------------------------------------------------------------------
| CHAT BODY
|--------------------------------------------------------------------------
*/

#ogs-ai-chat-body{

    flex:1;

    overflow-y:auto;

    padding:16px;

    background:#fff;

    scroll-behavior:smooth;
}

/*
|--------------------------------------------------------------------------
| SCROLLBAR
|--------------------------------------------------------------------------
*/

#ogs-ai-chat-body::-webkit-scrollbar{

    width:6px;
}

#ogs-ai-chat-body::-webkit-scrollbar-thumb{

    background:#d0d7de;

    border-radius:20px;
}

/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/

.ai-message{

    max-width:82%;

    padding:12px 15px;

    border-radius:18px;

    margin-bottom:12px;

    font-size:14px;

    line-height:1.6;

    word-break:break-word;

    animation:messageFade .25s ease;
}

@keyframes messageFade{

    from{

        opacity:0;

        transform:translateY(10px);
    }

    to{

        opacity:1;

        transform:translateY(0);
    }
}

/*
|--------------------------------------------------------------------------
| BOT
|--------------------------------------------------------------------------
*/

.ai-message.bot{

    background:#f0f2f5;

    color:#111;

    border-bottom-left-radius:5px;
}

/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

.ai-message.user{

    margin-left:auto;

    background:linear-gradient(135deg,#0a66c2,#1877f2);

    color:#fff;

    border-bottom-right-radius:5px;
}

/*
|--------------------------------------------------------------------------
| QUICK BUTTONS
|--------------------------------------------------------------------------
*/

.ogs-ai-actions{

    padding:10px;

    display:flex;

    gap:8px;

    overflow-x:auto;

    border-top:1px solid #eee;

    background:#fff;
}

.ogs-ai-actions::-webkit-scrollbar{

    display:none;
}

.quick-ai-question{

    border:none;

    background:#f0f2f5;

    padding:10px 15px;

    border-radius:30px;

    white-space:nowrap;

    cursor:pointer;

    font-size:13px;

    transition:.2s ease;
}

.quick-ai-question:hover{

    background:#1877f2;

    color:#fff;
}

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.ogs-ai-footer{

    padding:10px;

    border-top:1px solid #eee;

    display:flex;

    align-items:center;

    gap:10px;

    background:#fff;
}

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/

#ogs-ai-input{

    flex:1;

    height:46px;

    border:none;

    background:#f0f2f5;

    border-radius:30px;

    padding:0 16px;

    outline:none;

    font-size:14px;
}

#ogs-ai-input:focus{

    background:#e9edf2;
}

/*
|--------------------------------------------------------------------------
| SEND BUTTON
|--------------------------------------------------------------------------
*/

#sendAIHostMessage{

    width:46px;

    height:46px;

    border:none;

    border-radius:50%;

    background:#1877f2;

    color:#fff;

    cursor:pointer;

    font-size:18px;

    transition:.2s ease;
}

#sendAIHostMessage:hover{

    transform:scale(1.05);
}

/*
|--------------------------------------------------------------------------
| TYPING DOTS
|--------------------------------------------------------------------------
*/

.typing-dots{

    display:flex;

    gap:4px;
}

.typing-dots span{

    width:8px;

    height:8px;

    border-radius:50%;

    background:#999;

    animation:typing 1.3s infinite;
}

.typing-dots span:nth-child(2){

    animation-delay:.2s;
}

.typing-dots span:nth-child(3){

    animation-delay:.4s;
}

@keyframes typing{

    0%,80%,100%{

        transform:scale(.8);

        opacity:.5;
    }

    40%{

        transform:scale(1.3);

        opacity:1;
    }
}

/*
|--------------------------------------------------------------------------
| LARGE LAPTOP
|--------------------------------------------------------------------------
*/

@media(max-width:1400px){

    #ogs-ai-host-chat{

        width:360px;

        height:620px;
    }
}

/*
|--------------------------------------------------------------------------
| LAPTOP
|--------------------------------------------------------------------------
*/

@media(max-width:1200px){

    #ogs-ai-host-chat{

        width:340px;

        height:580px;

        right:15px;

        bottom:90px;
    }
}

/*
|--------------------------------------------------------------------------
| TABLET
|--------------------------------------------------------------------------
*/

@media(max-width:992px){

    #ogs-ai-host-widget{

        right:12px;

        bottom:12px;
    }

    #ogs-ai-host-avatar{

        width:60px;

        height:60px;
    }

    #ogs-ai-host-chat{

        width:320px;

        height:540px;

        right:10px;

        bottom:80px;
    }
}

/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media(max-width:768px){

    #ogs-ai-host-widget{

        right:10px;

        bottom:10px;
    }

    #ogs-ai-host-avatar{

        width:56px;

        height:56px;
    }

    #ogs-ai-host-chat{

        position:fixed;

        left:10px;

        right:10px;

        width:auto;

        height:75vh;

        max-height:700px;

        bottom:75px;

        border-radius:18px;
    }

    .ogs-ai-header{

        height:65px;

        min-height:65px;

        padding:0 12px;
    }

    .mini-avatar{

        width:38px;

        height:38px;
    }

    .ai-header-info h6{

        font-size:14px;
    }

    #ogs-ai-chat-body{

        padding:14px;
    }

    .ai-message{

        font-size:13px;

        padding:10px 13px;
    }

    .ogs-ai-footer{

        padding:8px;
    }

    #ogs-ai-input{

        height:42px;

        font-size:13px;
    }

    #sendAIHostMessage{

        width:42px;

        height:42px;
    }

    .quick-ai-question{

        font-size:12px;

        padding:8px 12px;
    }
}

/*
|--------------------------------------------------------------------------
| SMALL MOBILE
|--------------------------------------------------------------------------
*/

@media(max-width:480px){

    #ogs-ai-host-widget{

        right:8px;

        bottom:8px;
    }

    #ogs-ai-host-avatar{

        width:54px;

        height:54px;
    }

    #ogs-ai-host-chat{

        left:0;

        right:0;

        bottom:0;

        width:100%;

        height:100dvh;

        max-height:100dvh;

        border-radius:0;
    }

    .ogs-ai-header{

        padding:0 10px;
    }

    .ogs-ai-actions{

        padding:8px;
    }

    .quick-ai-question{

        padding:7px 11px;

        font-size:11px;
    }

    #ogs-ai-input{

        height:40px;

        padding:0 14px;
    }

    #sendAIHostMessage{

        width:40px;

        height:40px;
    }

    .ai-message{

        max-width:88%;
    }
}
/*
|--------------------------------------------------------------------------
| MESSAGE WRAPPER
|--------------------------------------------------------------------------
*/

.message-wrapper{

    margin-bottom:14px;
}

.user-wrap{

    text-align:right;
}

/*
|--------------------------------------------------------------------------
| MESSAGE TIME
|--------------------------------------------------------------------------
*/

.message-time{

    display:block;

    margin-top:4px;

    font-size:11px;

    color:#888;
}

/*
|--------------------------------------------------------------------------
| ONLINE STATUS
|--------------------------------------------------------------------------
*/

.online-status{

    display:flex;

    align-items:center;

    gap:6px;

    font-size:12px;

    color:#00c853;
}

.online-dot{

    width:8px;

    height:8px;

    border-radius:50%;

    background:#00c853;
}
/*
|--------------------------------------------------------------------------
| ATTACHMENT BUTTON
|--------------------------------------------------------------------------
*/

#attachmentButton{

    width:42px;

    height:42px;

    border-radius:50%;

    background:#f0f2f5;

    display:flex;

    align-items:center;

    justify-content:center;

    cursor:pointer;

    font-size:18px;

    flex-shrink:0;
}

#ogsMicBtn{
    width:42px;
    height:42px;
    border:none;
    border-radius:50%;
    background:#f0f2f5;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    font-size:18px;
    flex-shrink:0;
    transition:.2s;
}

#ogsMicBtn:hover{
    background:#e6e9ef;
}

#ogsMicBtn.recording{
    background:#ff3b30;
    animation:ogsMicPulse 1s infinite;
}

@keyframes ogsMicPulse{
    0%,100%{ box-shadow:0 0 0 0 rgba(255,59,48,.45); }
    50%{ box-shadow:0 0 0 8px rgba(255,59,48,0); }
}

</style>

   <!---- chatboat code start ----->
  

<div id="ogs-ai-host-widget">

    {{-- AVATAR --}}

    <div id="ogs-ai-host-avatar">

    <span id="aiUnreadCount">
        0
    </span>

        <svg class="ogs-ai-launcher-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
             role="img" aria-label="AI Assistant" style="width:100%;height:100%;display:block">
            <defs>
                <linearGradient id="ogsBgL" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#243049"/><stop offset="1" stop-color="#0b1322"/>
                </linearGradient>
                <linearGradient id="ogsSparkL" x1="26" y1="23" x2="40" y2="38" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#4285F4"/><stop offset="1" stop-color="#34A853"/>
                </linearGradient>
            </defs>
            <rect width="64" height="64" fill="url(#ogsBgL)"/>
            <g fill="none" stroke-width="2.6" stroke-linecap="round">
                <path d="M32 9 A23 23 0 0 1 55 32" stroke="#4285F4"/>
                <path d="M55 32 A23 23 0 0 1 32 55" stroke="#EA4335"/>
                <path d="M32 55 A23 23 0 0 1 9 32" stroke="#FBBC05"/>
                <path d="M9 32 A23 23 0 0 1 32 9" stroke="#34A853"/>
            </g>
            <rect x="18" y="20" width="28" height="19" rx="7" fill="#fff"/>
            <path d="M25 39 l0 6 l6 -6 z" fill="#fff"/>
            <path d="M32 24 l1.7 4.2 4.2 1.7 -4.2 1.7 L32 36 l-1.7 -4.2 -4.2 -1.7 4.2 -1.7 z" fill="url(#ogsSparkL)"/>
            <circle cx="26.5" cy="33" r="1.25" fill="#FBBC05"/>
            <circle cx="38" cy="26.5" r="1.25" fill="#EA4335"/>
        </svg>

        <div class="ai-pulse"></div>

    </div>

    {{-- CHATBOX --}}

    <div id="ogs-ai-host-chat">

        {{-- HEADER --}}

        <div class="ogs-ai-header">

            <div class="ai-header-left">

                <svg class="mini-avatar" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"
                     role="img" aria-label="AI Assistant">
                    <defs>
                        <linearGradient id="ogsBgS" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#243049"/><stop offset="1" stop-color="#0b1322"/>
                        </linearGradient>
                        <linearGradient id="ogsSparkS" x1="26" y1="23" x2="40" y2="38" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#4285F4"/><stop offset="1" stop-color="#34A853"/>
                        </linearGradient>
                    </defs>
                    <rect width="64" height="64" fill="url(#ogsBgS)"/>
                    <g fill="none" stroke-width="2.6" stroke-linecap="round">
                        <path d="M32 9 A23 23 0 0 1 55 32" stroke="#4285F4"/>
                        <path d="M55 32 A23 23 0 0 1 32 55" stroke="#EA4335"/>
                        <path d="M32 55 A23 23 0 0 1 9 32" stroke="#FBBC05"/>
                        <path d="M9 32 A23 23 0 0 1 32 9" stroke="#34A853"/>
                    </g>
                    <rect x="18" y="20" width="28" height="19" rx="7" fill="#fff"/>
                    <path d="M25 39 l0 6 l6 -6 z" fill="#fff"/>
                    <path d="M32 24 l1.7 4.2 4.2 1.7 -4.2 1.7 L32 36 l-1.7 -4.2 -4.2 -1.7 4.2 -1.7 z" fill="url(#ogsSparkS)"/>
                    <circle cx="26.5" cy="33" r="1.25" fill="#FBBC05"/>
                    <circle cx="38" cy="26.5" r="1.25" fill="#EA4335"/>
                </svg>

                <div class="ai-header-info">

                    <h6>
                        Sophia AI Assistant
                    </h6>

                    <div class="online-status">

    <span class="online-dot"></span>

    Active now

</div>

                </div>

            </div>

            <div class="ai-header-actions">
                <button id="clearAIChat" title="Start fresh" aria-label="Start fresh">
                    <svg class="ogs-ai-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                </button>
                <button id="closeAIHost" aria-label="Close chat">
                    <svg class="ogs-ai-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>

        </div>

        {{-- BODY --}}

        <div id="ogs-ai-chat-body">
            @php
                $sophiaBootUser = function_exists('authUser') ? authUser() : null;
                $sophiaBootAdmin = auth('admin')->user();
            @endphp

            @if($sophiaBootAdmin)
                <div class="message-wrapper">
                    <div class="ai-message bot">
                        Hi <strong>{{ $sophiaBootAdmin->name }}</strong> — Sophia admin assistant.
                        <br><br>
                        Ask me about candidates, companies, jobs, plans, or all-time stats.
                    </div>
                </div>
            @elseif($sophiaBootUser)
                <div class="message-wrapper">
                    <div class="ai-message bot">
                        Welcome back, <strong>{{ $sophiaBootUser->name }}</strong>!
                        <br><br>
                        You're signed in as a
                        <strong>
                            @switch($sophiaBootUser->role)
                                @case('candidate') Job Seeker @break
                                @case('company') Employer @break
                                @case('agency') Recruitment Agency @break
                                @case('agent') Agent / Facilitator @break
                                @case('broker') Broker / Middleman @break
                                @default {{ ucfirst($sophiaBootUser->role) }}
                            @endswitch
                        </strong>.
                        Ask me anything, or use the shortcuts below.
                    </div>
                </div>
            @else
                <div class="message-wrapper">
                    <div class="ai-message bot">
                        Hi, I'm <strong>Sophia</strong> &mdash; your Career WorkForce assistant.
                        <br><br>
                        To take you to the right place, first tell me <strong>who you are</strong>:
                    </div>
                </div>

                <div class="ai-role-picker" id="aiRolePicker">
                    <div class="ai-role-grid">
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('seeker')"><span class="r-text">Job Seeker</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('employer')"><span class="r-text">Employer</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('agency')"><span class="r-text">Recruitment Agency</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('agent')"><span class="r-text">Agent / Facilitator</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('broker')"><span class="r-text">Broker / Middleman</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('labour_supply')"><span class="r-text">Labour Supply Office</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('hr_referral')"><span class="r-text">HR Referral Partner</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('domestic_office')"><span class="r-text">Domestic Worker Office</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('selected_domestic')"><span class="r-text">Selected Domestic Worker</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('university')"><span class="r-text">University / College / School</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('abroad_student')"><span class="r-text">Abroad Edu Student</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('eu_permit_specialist')"><span class="r-text">EU Work Permit Specialist</span></button>
                        <button type="button" class="ai-role-chip live" onclick="ogsSelectRole('work_permit_seeker')"><span class="r-text">Work Permit Seeker</span></button>
                    </div>
                </div>
            @endif

        </div>

        {{-- QUICK BUTTONS --}}

        <div class="ogs-ai-actions">
            @if(!$sophiaBootUser && !$sophiaBootAdmin)
            <button type="button" class="quick-ai-role" onclick="ogsAskWhoAreYou()">
                Who are you?
            </button>

            <button class="quick-ai-question">
                Find Jobs
            </button>

            <button class="quick-ai-question">
                Visa Help
            </button>

            <button class="quick-ai-question">
                Interview Tips
            </button>
            @endif
        </div>

        {{-- FOOTER --}}

        <div class="ogs-ai-footer">

    {{-- FILE INPUT --}}

    <label
        for="aiAttachment"
        id="attachmentButton"
        title="Attach file"
        aria-label="Attach file"
    >
        <svg class="ogs-ai-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
    </label>

    <input
        type="file"
        id="aiAttachment"
        hidden
    >

    {{-- MIC / VOICE INPUT --}}

    <button
        type="button"
        id="ogsMicBtn"
        title="Speak - any language"
        aria-label="Voice input"
    >
        <svg class="ogs-ai-icon icon-mic" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21h2v-3.08A7 7 0 0 0 19 11h-2z"/></svg>
        <svg class="ogs-ai-icon icon-stop" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
    </button>

    {{-- MESSAGE INPUT --}}

    <input
        type="text"
        id="ogs-ai-input"
        placeholder="Message Sophia..."
    >

    {{-- SEND BUTTON --}}

    <button id="sendAIHostMessage" title="Send message" aria-label="Send message">
        <svg class="ogs-ai-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
    </button>

</div>

    </div>

</div>

<style>
/* WHO-ARE-YOU role picker */
.ai-role-picker{padding:2px 2px 6px;}
.ai-role-group-label{font-size:11px;font-weight:700;color:#6b7280;margin:12px 4px 6px;text-transform:uppercase;letter-spacing:.4px;}
.ai-role-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.ai-role-chip{position:relative;display:flex;align-items:center;justify-content:center;text-align:center;border:1px solid #e6e8f0;background:#fff;border-radius:14px;padding:11px 12px;font-size:12.5px;font-weight:600;color:#1f2937;cursor:pointer;transition:.18s ease;line-height:1.2;}
.ai-role-chip .r-emoji{font-size:16px;flex:0 0 auto;}
.ai-role-chip .r-text{flex:1;}
.ai-role-chip.live{background:linear-gradient(135deg,#f5f8ff,#fdf5ff);}
.ai-role-chip.live:hover{border-color:#6f42c1;box-shadow:0 6px 16px rgba(111,66,193,.18);transform:translateY(-1px);}
.ai-role-chip.soon{opacity:.92;}
.ai-role-chip.soon:hover{border-color:#cbd2e0;background:#fafbfd;}
.ai-role-chip .r-soon{font-size:8.5px;font-weight:700;background:#eef0f5;color:#7a8194;padding:2px 5px;border-radius:6px;flex:0 0 auto;}
@media(max-width:420px){.ai-role-grid{grid-template-columns:1fr;}}
/* header action buttons (refresh + close) */
.ai-header-actions{display:flex;align-items:center;gap:6px;}
/* "Who are you?" / change-role quick button */
.quick-ai-role{border:1px solid #6f42c1;background:#f6f0ff;color:#6f42c1;font-weight:700;border-radius:30px;padding:8px 14px;font-size:12px;cursor:pointer;transition:.18s ease;}
.quick-ai-role:hover{background:#6f42c1;color:#fff;}
.ai-message.bot .sophia-h{font-size:13.5px;font-weight:700;color:#1f2937;margin:10px 0 6px;border-bottom:1px solid #eef0f5;padding-bottom:4px;}
.ai-message.bot .sophia-list{margin:4px 0 10px 18px;padding:0;}
.ai-message.bot .sophia-list li{margin:4px 0;font-size:13px;line-height:1.45;}
.ai-message.bot p{margin:5px 0;font-size:13px;line-height:1.5;}
.quick-ai-link,.quick-ai-action{display:inline-block;border:1px solid #e6e8f0;background:#fff;color:#1f2937;font-weight:600;border-radius:30px;padding:8px 14px;font-size:12px;cursor:pointer;text-decoration:none;transition:.18s ease;}
.quick-ai-link:hover,.quick-ai-action:hover{border-color:#6f42c1;background:#f6f0ff;color:#6f42c1;}
.quick-ai-role-chip{border:1px solid #6f42c1;background:#f6f0ff;color:#6f42c1;font-weight:700;border-radius:30px;padding:8px 14px;font-size:12px;cursor:pointer;}
</style>

<script>
/* ===================================================== */
/* WHO ARE YOU â€” role selection (frontend only for now)  */
/* ===================================================== */

function ogsAppendUserBubble(text){
    $('#ogs-ai-chat-body').append(
        '<div class="message-wrapper">'
        + '<div class="ai-message user">' + text + '</div>'
        + '<small class="message-time">' + getCurrentTime() + '</small>'
        + '</div>'
    );
}

function ogsAppendBotBubble(html){
    $('#ogs-ai-chat-body').append(
        '<div class="message-wrapper">'
        + '<div class="ai-message bot">' + ogsFormatSophiaMessage(html) + '</div>'
        + '<small class="message-time">Seen &middot; ' + getCurrentTime() + '</small>'
        + '</div>'
    );
}

window.ogsSophiaContext = null;
window.ogsPortalAction = null;
window.ogsPortalMode = false;
window.ogsAcceptsDocuments = false;
window.ogsSeekerActive = false;
window.ogsEmployerActive = false;
window.ogsAgentWorkerActive = false;
var ogsSeenAdminReplyIds = {};
var ogsChatInFlight = false;

function ogsFormatSophiaMessage(text){
    if(!text) return '';

    var s = String(text).trim();

    if(/<(h[1-6]|p|ul|li|strong|a)\b/i.test(s) && !/^###?\s/m.test(s) && !/\*\*/.test(s)){
        return s;
    }

    var lines = s.split(/\r?\n/);
    var out = [];
    var inList = false;

    function inlineFmt(line){
        return line
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>');
    }

    lines.forEach(function(line){
        var trimmed = line.trim();

        if(trimmed === ''){
            if(inList){ out.push('</ul>'); inList = false; }
            return;
        }

        var h3 = trimmed.match(/^###\s+(.+)$/);
        var h2 = trimmed.match(/^##\s+(.+)$/);
        var h1 = trimmed.match(/^#\s+(.+)$/);
        var li = trimmed.match(/^[-*•]\s+(.+)$/);

        if(h3){
            if(inList){ out.push('</ul>'); inList = false; }
            out.push('<h4 class="sophia-h">' + inlineFmt(h3[1]) + '</h4>');
        } else if(h2 || h1){
            if(inList){ out.push('</ul>'); inList = false; }
            out.push('<h3 class="sophia-h">' + inlineFmt((h2 || h1)[1]) + '</h3>');
        } else if(li){
            if(!inList){ out.push('<ul class="sophia-list">'); inList = true; }
            out.push('<li>' + inlineFmt(li[1]) + '</li>');
        } else {
            if(inList){ out.push('</ul>'); inList = false; }
            out.push('<p>' + inlineFmt(trimmed) + '</p>');
        }
    });

    if(inList){ out.push('</ul>'); }

    return out.join('');
}

function ogsFormatGreeting(text){
    return ogsFormatSophiaMessage(text);
}

function ogsLoadContext(callback){
    $.getJSON("{{ url('/ai/context') }}", function(ctx){
        window.ogsSophiaContext = ctx || {};
        window.ogsPortalMode = ctx.mode && ctx.mode !== 'guest';
        window.ogsAcceptsDocuments = !!ctx.accepts_documents;
        ogsApplySeekerFlags(ctx);
        ogsApplyEmployerFlags(ctx);
        ogsApplyAgencyFlags(ctx);
        if(ctx && ctx.agent_account_active){ window.ogsAgentAccountActive = true; }
        if(ctx && ctx.broker_active){ window.ogsBrokerActive = true; }
        if(typeof callback === 'function') callback(ctx);
    });
}

function ogsApplySeekerFlags(res){
    if(!res) return;

    if(res.agent_worker_active){
        window.ogsAgentWorkerActive = true;
        window.ogsSeekerActive = false;
        window.ogsAcceptsDocuments = true;
        window.ogsPortalMode = true;
        if(res.document_hint){
            $('#ogs-ai-input').attr('placeholder', res.document_hint);
        }
    } else if(res.agent_worker_active === false){
        window.ogsAgentWorkerActive = false;
    }

    if(res.seeker_active){
        window.ogsSeekerActive = true;
        window.ogsPortalMode = false;
        window.ogsAcceptsDocuments = true;
        window.ogsSophiaContext = window.ogsSophiaContext || {};
        window.ogsSophiaContext.seeker_active = true;
        if(res.seeker_step){
            window.ogsSophiaContext.seeker_step = res.seeker_step;
        }
    } else if(res.seeker_active === false){
        window.ogsSeekerActive = false;
        if(window.ogsSophiaContext){
            window.ogsSophiaContext.seeker_active = false;
        }
    }

    if(res.document_hint){
        $('#ogs-ai-input').attr('placeholder', res.document_hint);
    }
}

function ogsApplyEmployerFlags(res){
    if(!res) return;

    if(res.employer_active){
        window.ogsEmployerActive = true;
        window.ogsSeekerActive = false;
        window.ogsPortalMode = false;
        window.ogsAcceptsDocuments = true;
        window.ogsSophiaContext = window.ogsSophiaContext || {};
        window.ogsSophiaContext.employer_active = true;
        if(res.employer_step){
            window.ogsSophiaContext.employer_step = res.employer_step;
        }
    } else if(res.employer_active === false){
        window.ogsEmployerActive = false;
        if(window.ogsSophiaContext){
            window.ogsSophiaContext.employer_active = false;
        }
    }

    if(res.document_hint){
        $('#ogs-ai-input').attr('placeholder', res.document_hint);
    }
}

function ogsRenderContextActions(actions){
    var $area = $('.ogs-ai-actions');
    if(!$area.length) return;

    var html = '';
    (actions || []).forEach(function(a){
        if(a.type === 'link'){
            html += '<a class="quick-ai-link" href="' + a.url + '">' + a.label + '</a>';
        } else if(a.type === 'action'){
            html += '<button type="button" class="quick-ai-action" data-action="' + a.value + '">' + a.label + '</button>';
        } else if(a.type === 'role'){
            // Role chips only for guests — logged-in users already have an account type
            if(window.ogsPortalMode) return;
            html += '<button type="button" class="quick-ai-role-chip" data-role="' + a.value + '">' + a.label + '</button>';
        } else {
            html += '<button type="button" class="quick-ai-question">' + (a.value || a.label) + '</button>';
        }
    });

    if(html){
        $area.html(html);
    }
}

/** Stable identity key so guest history is never shown to a logged-in seeker/employer/admin. */
function ogsContextIdentity(ctx){
    if(!ctx) return 'guest';
    if(ctx.mode === 'admin') return 'admin:' + (ctx.admin_id || '0');
    if(ctx.mode && ctx.mode !== 'guest') return (ctx.role || 'portal') + ':' + (ctx.user_id || '0');
    return 'guest';
}

function ogsHistoryLooksLikeGuestPicker(){
    var $body = $('#ogs-ai-chat-body');
    if(!$body.length) return false;
    if($body.find('.ai-role-picker').length) return true;
    var html = ($body.html() || '').toLowerCase();
    return html.indexOf('who you are') !== -1 || html.indexOf('who are you') !== -1;
}

/**
 * Apply logged-in / guest context. Logged-in users NEVER see the guest role picker.
 * Stale localStorage from a guest session is discarded when identity changes.
 */
function ogsApplyPortalContext(ctx, forceReset){
    if(!ctx) return;

    var identity = ogsContextIdentity(ctx);
    var storedIdentity = localStorage.getItem('ogs_ai_chat_identity') || '';
    var isLoggedIn = ctx.mode && ctx.mode !== 'guest';
    var identityChanged = storedIdentity !== '' && storedIdentity !== identity;
    var guestHistoryWhileLoggedIn = isLoggedIn && (
        storedIdentity === 'guest'
        || ogsHistoryLooksLikeGuestPicker()
    );

    if(forceReset || identityChanged || guestHistoryWhileLoggedIn){
        localStorage.removeItem('ogs_ai_chat_history_v2');
        forceReset = true;
    }

    if(forceReset || !localStorage.getItem('ogs_ai_chat_history_v2')){
        var greeting = ogsFormatGreeting(ctx.greeting || "Hi, I'm <strong>Sophia</strong>!");
        var body = '<div class="message-wrapper"><div class="ai-message bot">' + greeting + '</div></div>';
        // Role picker only for true guests — never for logged-in seekers/employers/admins
        if(!isLoggedIn && ctx.show_role_picker){
            body += ogsRolePickerMarkup();
        }
        $('#ogs-ai-chat-body').html(body);
        scrollSophiaChat();
        saveSophiaChat();
    } else if(isLoggedIn){
        // Strip any leftover guest picker from restored history
        $('#ogs-ai-chat-body .ai-role-picker').remove();
    }

    localStorage.setItem('ogs_ai_chat_identity', identity);
    window.ogsSophiaContext = ctx;
    window.ogsPortalMode = isLoggedIn;
    window.ogsAcceptsDocuments = !!ctx.accepts_documents;

    ogsRenderContextActions(ctx.actions || []);

    if(ctx.document_hint){
        $('#ogs-ai-input').attr('placeholder', ctx.document_hint);
    } else if(isLoggedIn){
        $('#ogs-ai-input').attr('placeholder', 'Message Sophia...');
    }
}

function ogsPollAdminReplies(){
    $.getJSON("{{ url('/ai/chat/replies') }}/" + sessionId, function(messages){
        if(!messages || !messages.length) return;
        messages.forEach(function(m){
            var id = m.id || (m.ai_reply + m.created_at);
            if(ogsSeenAdminReplyIds[id]) return;
            ogsSeenAdminReplyIds[id] = true;
            ogsAppendBotBubble('<strong>Consultant:</strong><br>' + (m.ai_reply || m.user_message || ''));
            scrollSophiaChat();
            saveSophiaChat();
            $('#aiUnreadCount').text('1').css('display','flex');
        });
    });
}

/* If a seeker onboarding is in progress, cancel it on the backend
   so switching role / "Who are you?" doesn't hijack the next message. */
function ogsCancelSeekerIfActive(){
    if(!window.ogsSeekerActive) return;
    window.ogsSeekerActive = false;

    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('message', 'cancel');

    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false
    });
}

function ogsCancelEmployerIfActive(){
    if(!window.ogsEmployerActive) return;
    window.ogsEmployerActive = false;

    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('message', 'cancel');

    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false
    });
}

function ogsSelectRole(role){

    // Logged-in users already have a role — don't restart guest onboarding
    var ctx = window.ogsSophiaContext;
    if(ctx && ctx.mode && ctx.mode !== 'guest'){
        ogsAppendBotBubble(
            "You're already signed in as a <strong>" + (ctx.label || 'portal user') + "</strong>. "
            + "I know who you are — ask me anything below."
        );
        ogsRenderContextActions(ctx.actions || []);
        scrollSophiaChat();
        saveSophiaChat();
        return;
    }

    if(role !== 'seeker'){
        ogsCancelSeekerIfActive();
    }
    if(role !== 'employer'){
        ogsCancelEmployerIfActive();
    }

    var R = {
        seeker: {
            label: 'Job Seeker',
            guided: true
        },
        employer: {
            label: 'Employer',
            guided: true
        },
        agency: {
            label: 'Recruitment Agency',
            guided: true
        },
        agent: {
            label: 'Agent / Facilitator',
            guided: true
        },
        broker: {
            label: 'Broker / Middleman',
            guided: true
        },
        labour_supply: {
            label: 'Labour Supply Office',
            registerType: 'labour_supply'
        },
        hr_referral: {
            label: 'HR Referral Partner',
            registerType: 'hr_referral'
        },
        domestic_office: {
            label: 'Domestic Worker Office',
            registerType: 'domestic_office'
        },
        selected_domestic: {
            label: 'Selected Domestic Worker',
            registerType: 'domestic_worker'
        },
        nominated_worker: {
            label: 'Selected Domestic Worker',
            registerType: 'domestic_worker'
        },
        university: {
            label: 'University / College / School',
            registerType: 'university'
        },
        abroad_student: {
            label: 'Abroad Edu Student',
            registerType: 'abroad_student'
        },
        eu_permit_specialist: {
            label: 'EU Work Permit Specialist',
            registerType: 'eu_permit_specialist'
        },
        work_permit_seeker: {
            label: 'Work Permit Seeker',
            registerType: 'work_permit_seeker'
        }
    };

    var soon = {};

    var entry = R[role];
    var label = entry ? entry.label : soon[role];
    if(!label) return;

    // Collapse any role picker(s) once a choice is made
    $('.ai-role-picker').slideUp(150);

    ogsAppendUserBubble(label);
    scrollSophiaChat();

    setTimeout(function(){
        if(role === 'seeker'){
            ogsStartSeeker();
        } else if(role === 'employer'){
            ogsStartEmployer();
        } else if(role === 'agency'){
            ogsStartAgency();
        } else if(role === 'agent'){
            ogsStartAgentAccount();
        } else if(role === 'broker'){
            ogsStartBroker();
        } else if(entry && entry.registerType){
            window.location.href = "{{ route('register') }}?type=" + encodeURIComponent(entry.registerType);
        } else if(entry && entry.msg){
            ogsAppendBotBubble(entry.msg);
        }else{
            ogsAppendBotBubble(
                "<strong>" + label + "</strong> is launching soon.<br><br>"
                + "This portal isn't open yet &mdash; but it's on the way. Meanwhile I can still "
                + "help with overseas jobs, visa or recruitment. Just ask me below!"
            );
        }
        scrollSophiaChat();
        saveSophiaChat();
    }, 350);
}

/* Kick off the Job Seeker onboarding flow on the backend */
function ogsStartSeeker(){
    window.ogsSeekerActive = true;
    window.ogsEmployerActive = false;
    showAIThinking();

    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('role', 'seeker');

    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function(res){
            removeAIThinking();
            ogsApplySeekerFlags(res);
            ogsApplyEmployerFlags(res);
            if(res.reply){ appendSophiaReply(res.reply); }
            if(res.actions && res.actions.length){ ogsRenderContextActions(res.actions); }
            saveSophiaChat();
        },
        error: function(){
            removeAIThinking();
            ogsAppendBotBubble('Could not start seeker registration. Please try again.');
        }
    });
}

/* Kick off the Employer onboarding flow on the backend */
function ogsStartEmployer(){
    window.ogsEmployerActive = true;
    window.ogsSeekerActive = false;
    showAIThinking();

    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('role', 'employer');

    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function(res){
            removeAIThinking();
            ogsApplySeekerFlags(res);
            ogsApplyEmployerFlags(res);
            ogsApplyAgencyFlags(res);
            if(res.reply){ appendSophiaReply(res.reply); }
            if(res.actions && res.actions.length){ ogsRenderContextActions(res.actions); }
            saveSophiaChat();
        },
        error: function(){
            removeAIThinking();
            ogsAppendBotBubble('Could not start employer registration. Please try again.');
        }
    });
}

function ogsStartAgency(){
    window.ogsAgencyActive = true;
    window.ogsSeekerActive = false;
    window.ogsEmployerActive = false;
    showAIThinking();
    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('role', 'agency');
    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function(res){
            removeAIThinking();
            ogsApplyAgencyFlags(res);
            ogsApplyEmployerFlags(res);
            if(res.reply){ appendSophiaReply(res.reply); }
            if(res.actions && res.actions.length){ ogsRenderContextActions(res.actions); }
            saveSophiaChat();
        },
        error: function(){
            removeAIThinking();
            ogsAppendBotBubble('Could not start agency registration. Please try again.');
        }
    });
}

function ogsStartAgentAccount(){
    window.ogsAgentAccountActive = true;
    showAIThinking();
    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('role', 'agent');
    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function(res){
            removeAIThinking();
            if(res.agent_account_active){ window.ogsAgentAccountActive = true; window.ogsAcceptsDocuments = false; }
            if(res.reply){ appendSophiaReply(res.reply); }
            if(res.actions && res.actions.length){ ogsRenderContextActions(res.actions); }
            saveSophiaChat();
        },
        error: function(){
            removeAIThinking();
            ogsAppendBotBubble('Could not start Agent / Facilitator registration. Please try again.');
        }
    });
}

function ogsStartBroker(){
    window.ogsBrokerActive = true;
    showAIThinking();
    var fd = new FormData();
    fd.append('_token', "{{ csrf_token() }}");
    fd.append('role', 'broker');
    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: fd,
        processData: false,
        contentType: false,
        success: function(res){
            removeAIThinking();
            if(res.broker_active){ window.ogsBrokerActive = true; }
            if(res.reply){ appendSophiaReply(res.reply); }
            if(res.actions && res.actions.length){ ogsRenderContextActions(res.actions); }
            saveSophiaChat();
        },
        error: function(){
            removeAIThinking();
            ogsAppendBotBubble('Could not start broker registration. Please try again.');
        }
    });
}

function ogsApplyAgencyFlags(res){
    if(!res) return;
    if(res.agency_active){
        window.ogsAgencyActive = true;
        window.ogsAcceptsDocuments = true;
        if(res.document_hint){ window.ogsDocumentHint = res.document_hint; }
    } else if(res.agency_active === false){
        window.ogsAgencyActive = false;
    }
}

/* Markup for the role buttons (reused for the initial step and "change role") */
function ogsRolePickerMarkup(){
    return ''
        + '<div class="ai-role-picker">'
        +   '<div class="ai-role-grid">'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'seeker\')"><span class="r-text">Job Seeker</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'employer\')"><span class="r-text">Employer</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'agency\')"><span class="r-text">Recruitment Agency</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'agent\')"><span class="r-text">Agent / Facilitator</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'broker\')"><span class="r-text">Broker / Middleman</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'labour_supply\')"><span class="r-text">Labour Supply Office</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'hr_referral\')"><span class="r-text">HR Referral Partner</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'domestic_office\')"><span class="r-text">Domestic Worker Office</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'selected_domestic\')"><span class="r-text">Selected Domestic Worker</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'university\')"><span class="r-text">University / College / School</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'abroad_student\')"><span class="r-text">Abroad Edu Student</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'eu_permit_specialist\')"><span class="r-text">EU Work Permit Specialist</span></button>'
        +     '<button type="button" class="ai-role-chip live" onclick="ogsSelectRole(\'work_permit_seeker\')"><span class="r-text">Work Permit Seeker</span></button>'
        +   '</div>'
        + '</div>';
}

/* "Who are you?" — guests only. Logged-in users get a role reminder, not a picker. */
function ogsAskWhoAreYou(){
    ogsCancelSeekerIfActive();
    ogsCancelEmployerIfActive();
    $('.ai-role-picker').slideUp(150);

    var ctx = window.ogsSophiaContext;
    if(ctx && ctx.mode && ctx.mode !== 'guest'){
        var label = ctx.label || 'your account';
        ogsAppendBotBubble(
            "You're already signed in as a <strong>" + label + "</strong>. "
            + "I know who you are — ask me anything, or use the shortcuts below."
        );
        ogsRenderContextActions(ctx.actions || []);
        scrollSophiaChat();
        saveSophiaChat();
        return;
    }

    $('#ogs-ai-chat-body').append(
        '<div class="message-wrapper">'
        + '<div class="ai-message bot">No problem &mdash; tell me <strong>who you are</strong>:</div>'
        + '</div>'
        + ogsRolePickerMarkup()
    );

    scrollSophiaChat();
    saveSophiaChat();
}

/* Clear chat and start fresh (header refresh button) */
function clearAIChat(){
    ogsCancelSeekerIfActive();
    ogsCancelEmployerIfActive();
    localStorage.removeItem('ogs_ai_chat_history_v2');
    window.ogsPortalAction = null;

    ogsLoadContext(function(ctx){
        if(ctx.mode && ctx.mode !== 'guest'){
            ogsApplyPortalContext(ctx, true);
        } else {
            localStorage.setItem('ogs_ai_chat_identity', 'guest');
            $('#ogs-ai-chat-body').html(
                '<div class="message-wrapper">'
                + '<div class="ai-message bot">'
                +   "Hi, I'm <strong>Sophia</strong> — your Career WorkForce assistant."
                +   '<br><br>To take you to the right place, first tell me <strong>who you are</strong>:'
                + '</div>'
                + '</div>'
                + ogsRolePickerMarkup()
            );
            renderSuggestionButtons(['Find Jobs', 'Visa Help', 'Interview Tips']);
            scrollSophiaChat();
            saveSophiaChat();
        }
    });
}
</script>

<script>

let sessionId =
    '{{ session()->getId() }}';

let humanMode = false;

let mediaRecorder;

let audioChunks = [];

let recordedAudioBlob = null;

let aiTypingSpeed = 40;

/* ===================================================== */
/* LOAD CHAT HISTORY */
/* ===================================================== */

$(document).ready(function(){

    // Restore history only temporarily — ogsApplyPortalContext will replace it
    // if the user is logged in and history is still the guest "who are you?" screen.
    if(localStorage.getItem('ogs_ai_chat_history_v2')){
        $('#ogs-ai-chat-body').html(localStorage.getItem('ogs_ai_chat_history_v2'));
        scrollSophiaChat();
    }

    ogsLoadContext(function(ctx){
        ogsApplyPortalContext(ctx, false);
    });

    setInterval(ogsPollAdminReplies, 15000);
});

/* ===================================================== */
/* SAVE CHAT */
/* ===================================================== */

function saveSophiaChat(){

    localStorage.setItem(

        'ogs_ai_chat_history_v2',

        $('#ogs-ai-chat-body').html()
    );
}

/* ===================================================== */
/* SCROLL */
/* ===================================================== */

function scrollSophiaChat(){

    let body =

        document.getElementById(
            'ogs-ai-chat-body'
        );

    if(body){

        body.scrollTop =
            body.scrollHeight;
    }
}

/* ===================================================== */
/* TIME */
/* ===================================================== */

function getCurrentTime(){

    let now = new Date();

    return now.toLocaleTimeString([], {

        hour:'2-digit',

        minute:'2-digit'
    });
}

/* ===================================================== */
/* SOUND */
/* ===================================================== */

function playAIMessageSound(){

    let audio = new Audio(

        'https://notificationsounds.com/storage/sounds/file-sounds-1150-pristine.mp3'
    );

    audio.play();
}

/* ===================================================== */
/* STREAM AI MESSAGE */
/* ===================================================== */

/** HTML / long replies — one shot; short plain text — typewriter stream. */
function appendSophiaReply(text){
    text = (text || '').trim();
    if(!text){ return false; }

    if(/<[^>]+>/.test(text) || text.length > 160){
        ogsAppendBotBubble(text);
        saveSophiaChat();
        playAIMessageSound();
        scrollSophiaChat();
        return true;
    }

    streamAIMessage(text);
    return true;
}

function streamAIMessage(text){

    text = (text || '').trim();
    if(!text){ return; }

    let words = text.split(' ');
    let i = 0;

    let html = `
        <div class="message-wrapper ogs-bot-turn">
            <div class="ai-message bot" id="streamingMessage"></div>
            <small class="message-time">Sophia is typing...</small>
        </div>
    `;

    $('#ogs-ai-chat-body').append(html);
    scrollSophiaChat();

    let interval = setInterval(function(){
        if(i < words.length){
            $('#streamingMessage').append(words[i] + ' ');
            i++;
            scrollSophiaChat();
        } else {
            $('#streamingMessage')
                .html(ogsFormatSophiaMessage(text))
                .removeAttr('id');
            clearInterval(interval);
            $('.ogs-bot-turn').last().find('.message-time')
                .html('Seen &middot; ' + getCurrentTime());
            $('.ogs-bot-turn').last().removeClass('ogs-bot-turn');
            saveSophiaChat();
            playAIMessageSound();
        }
    }, aiTypingSpeed);
}

/* ===================================================== */
/* THINKING */
/* ===================================================== */

function showAIThinking(){
    removeAIThinking();
    $('#ogs-ai-chat-body').append(`
        <div class="message-wrapper ai-thinking-block">
            <div class="ai-message bot">
                <div class="typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    `);
    scrollSophiaChat();
}

function removeAIThinking(){
    $('.ai-thinking-block').remove();
}

/* ===================================================== */
/* SUGGESTIONS */
/* ===================================================== */

function renderSuggestionButtons(buttons){

    let html = '';

    buttons.forEach(function(btn){

        html += `

            <button
                class="quick-ai-question"
            >

                ${btn}

            </button>

        `;
    });

    $('#aiSuggestionArea, .ogs-ai-actions')
    .html(html);
}

/* ===================================================== */
/* DEFAULT SUGGESTIONS */
/* ===================================================== */

renderSuggestionButtons([

    'Find Jobs',

    'Visa Help',

    'Interview Tips'
]);

$(document).on('click', '.quick-ai-action', function(){
    window.ogsPortalAction = $(this).data('action');
    $('#ogs-ai-input').val('');
    sendSophiaAIMessage();
});

$(document).on('click', '.quick-ai-role-chip', function(){
    ogsSelectRole($(this).data('role'));
});

/* ===================================================== */
/* QUICK QUESTIONS */
/* ===================================================== */

$(document).on(

    'click',

    '.quick-ai-question',

    function(){

        $('#ogs-ai-input')

        .val(

            $(this).text()
        );

        sendSophiaAIMessage();
    }
);

/* ===================================================== */
/* OPEN CHAT */
/* ===================================================== */

$('#ogs-ai-host-avatar').on('click', function(){

    if($('#ogs-ai-host-chat').is(':visible')){
        $('#ogs-ai-host-chat').fadeOut(200);
    }else{
        $('#ogs-ai-host-chat').css('display','flex').hide().fadeIn(200);
        $('#aiUnreadCount').hide();
        ogsLoadContext(function(ctx){
            ogsApplyPortalContext(ctx, false);
        });
        ogsPollAdminReplies();
    }

});

/* ===================================================== */
/* CLOSE CHAT */
/* ===================================================== */

$('#closeAIHost').on('click', function(){

    $('#ogs-ai-host-chat')
    .fadeOut(200);

});

/* ===================================================== */
/* MINIMIZE */
/* ===================================================== */

$('#minimizeAIChat').on('click', function(){

    $('#ogs-ai-host-chat')

    .toggleClass('minimized');

});

/* ===================================================== */
/* CLEAR CHAT */
/* ===================================================== */

$('#clearAIChat').on('click', function(){

    // Reset to a fresh chat: clears history and shows the "who are you?" step
    clearAIChat();
});

/* ===================================================== */
/* SEND BUTTON */
/* ===================================================== */

$('#sendAIHostMessage').on('click', function(){

    sendSophiaAIMessage();
});

/* ===================================================== */
/* ENTER KEY */
/* ===================================================== */

$('#ogs-ai-input').keypress(function(e){

    if(e.which == 13){

        sendSophiaAIMessage();
    }

});

/* ===================================================== */
/* ATTACHMENT â€” auto-send during seeker onboarding */
/* ===================================================== */

$('#aiAttachment').on('change', function(){

    var file = this.files[0];

    if(!file) return;

    var canSend = window.ogsSeekerActive
        || window.ogsEmployerActive
        || window.ogsAgencyActive
        || window.ogsAgentWorkerActive
        || (window.ogsPortalMode && window.ogsAcceptsDocuments)
        || (window.ogsSophiaContext && window.ogsSophiaContext.seeker_active)
        || (window.ogsSophiaContext && window.ogsSophiaContext.employer_active)
        || (window.ogsSophiaContext && window.ogsSophiaContext.agency_active);

    if(!canSend) return;

    $('#ogs-ai-chat-body').append(
        '<div class="message-wrapper user-wrap">'
        + '<div class="ai-message user">Attachment: ' + file.name + '</div>'
        + '<small class="message-time">' + getCurrentTime() + '</small>'
        + '</div>'
    );

    scrollSophiaChat();
    saveSophiaChat();

    sendSophiaAIMessage();
});

/* ===================================================== */
/* MIC â€” Real-time speech â†’ message box â†’ smart send     */
/* Primary  : Web Speech API  (Chrome/Edge/Safari)        */
/*            â†’ live text appears as user speaks          */
/* Fallback : MediaRecorder â†’ Whisper (Firefox)           */
/* Intent   : saying "I'm a seeker / job seeker" in any  */
/*            language auto-starts the seeker flow        */
/* ===================================================== */
(function(){

    var micActive   = false;
    var recognition = null;
    var micRec = null, micChunks = [];
    var wsSupported = !!(window.SpeechRecognition || window.webkitSpeechRecognition);

    /* ---- Seeker-intent keyword list (EN + Urdu/Hindi + Arabic) ---- */
    var SEEKER_KW = [
        'seeker','job seeker','seeking job','seeking work','seeking employment',
        'looking for job','looking for work','need job','find job','want job',
        'job search','find work','searching for job','i am seeker','i am a seeker',
        'overseas job','abroad job','foreign job',
        'naukri','rozgar','mulazmat',
        '\u0648\u0638\u064A\u0641\u0629','\u0639\u0645\u0644','\u0623\u0628\u062D\u062B','\u0628\u0627\u062D\u062B'
    ];

    function isSeekerPhrase(text){
        var t = (text || '').toLowerCase();
        return SEEKER_KW.some(function(k){ return t.indexOf(k) > -1; });
    }

    /* ---- UI helpers ---- */
    function setMicUI(on){
        micActive = on;
        var btn = document.getElementById('ogsMicBtn');
        var inp = document.getElementById('ogs-ai-input');
        if(!btn) return;
        btn.classList.toggle('recording', on);
        btn.title = on ? 'Tap stop to send' : 'Speak - any language';
        if(inp) inp.placeholder = on
            ? 'Listening... tap stop to send'
            : 'Message Sophia...';
    }

    /* ---- After transcript is ready: detect intent then send ---- */
    function checkAndSend(text){
        text = (text || '').trim();
        if(!text) return;
        document.getElementById('ogs-ai-input').value = text;
        if(!window.ogsSeekerActive && isSeekerPhrase(text)){
            /* User said they're a job seeker â€” start onboarding flow */
            document.getElementById('ogs-ai-input').value = '';
            ogsStartSeeker();
        } else {
            sendSophiaAIMessage();
        }
    }

    /* ======================================================
       PRIMARY: Web Speech API â€” live real-time transcript
    ====================================================== */
    function startWebSpeech(){
        var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SR();
        recognition.continuous     = true;
        recognition.interimResults = true;
        /* Use last-known session language if available */
        recognition.lang = window.ogsChatLang || navigator.language || 'en-US';

        recognition.onresult = function(e){
            var fin = '', inter = '';
            for(var i = 0; i < e.results.length; i++){
                if(e.results[i].isFinal) fin   += e.results[i][0].transcript;
                else                     inter  += e.results[i][0].transcript;
            }
            document.getElementById('ogs-ai-input').value = fin + inter;
        };

        recognition.onerror = function(e){
            if(micActive) setMicUI(false);
            recognition = null;
            if(e.error === 'not-allowed'){
                ogsAppendBotBubble('Mic access denied &mdash; allow mic in your browser settings.');
            } else if(e.error !== 'no-speech'){
                ogsAppendBotBubble('Voice error (' + e.error + '). Please try again.');
            }
        };

        /* continuous=true: browser may still auto-stop on long silence â€” restart */
        recognition.onend = function(){
            if(micActive){ try{ recognition.start(); }catch(ex){} }
        };

        try{
            recognition.start();
            setMicUI(true);
        }catch(err){
            setMicUI(false);
            ogsAppendBotBubble('Could not start voice. Please try again.');
        }
    }

    function stopWebSpeech(){
        setMicUI(false);
        if(recognition){ try{ recognition.stop(); }catch(e){} recognition = null; }
        var text = (document.getElementById('ogs-ai-input').value || '').trim();
        if(text) checkAndSend(text);
    }

    /* ======================================================
       FALLBACK: MediaRecorder â†’ Whisper (no real-time text)
    ====================================================== */
    function pickMime(){
        var types=['audio/webm;codecs=opus','audio/webm','audio/ogg;codecs=opus','audio/mp4'];
        for(var i=0;i<types.length;i++){
            if(window.MediaRecorder && MediaRecorder.isTypeSupported(types[i])) return types[i];
        }
        return '';
    }

    function startMediaRecorder(){
        if(!navigator.mediaDevices || !window.MediaRecorder){
            ogsAppendBotBubble('Voice input is not supported in this browser. Please type instead.');
            return;
        }
        navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
            var mime = pickMime();
            micChunks = [];
            micRec = mime
                ? new MediaRecorder(stream, {mimeType: mime})
                : new MediaRecorder(stream);
            micRec.ondataavailable = function(e){
                if(e.data && e.data.size > 0) micChunks.push(e.data);
            };
            micRec.onstop = function(){
                stream.getTracks().forEach(function(t){ t.stop(); });
                var blob = new Blob(micChunks, {type: micRec.mimeType || 'audio/webm'});
                sendToWhisper(blob);
            };
            micRec.start();
            setMicUI(true);
        }).catch(function(){
            setMicUI(false);
            ogsAppendBotBubble('Could not access your microphone. Please allow mic access and try again.');
        });
    }

    function stopMediaRecorder(){
        setMicUI(false);
        try{ if(micRec && micRec.state !== 'inactive') micRec.stop(); }catch(e){}
    }

    async function sendToWhisper(blob){
        showAIThinking();
        var ext = blob.type.indexOf('ogg') > -1 ? 'ogg'
                : blob.type.indexOf('mp4') > -1 ? 'mp4' : 'webm';
        var fd = new FormData();
        fd.append('_token', "{{ csrf_token() }}");
        fd.append('audio',  blob, 'voice.' + ext);
        try{
            var res  = await fetch("{{ url('/ai/transcribe') }}", {
                method: 'POST', body: fd, headers: {'Accept': 'application/json'}
            });
            var data = await res.json();
            removeAIThinking();
            if(data && data.text){
                if(data.lang) window.ogsChatLang = data.lang;
                checkAndSend(data.text);
            } else {
                ogsAppendBotBubble((data && data.reply)
                    ? data.reply
                    : "Sorry, I couldn't catch that. Please try again.");
            }
        }catch(err){
            removeAIThinking();
            ogsAppendBotBubble("Voice transcription failed &mdash; please type your message instead.");
        }
    }

    /* ---- Button click ---- */
    $(document).on('click', '#ogsMicBtn', function(){
        if(micActive){
            if(wsSupported) stopWebSpeech(); else stopMediaRecorder();
        } else {
            if(wsSupported) startWebSpeech(); else startMediaRecorder();
        }
    });

})();

/* ===================================================== */
/* VOICE RECORD */
/* ===================================================== */

$('#recordVoiceMessage').on('click', async function(){

    if(

        !$(this).hasClass(
            'recording'
        )

    ){

        const stream =

            await navigator

            .mediaDevices

            .getUserMedia({

                audio:true
            });

        mediaRecorder =
            new MediaRecorder(stream);

        audioChunks = [];

        mediaRecorder.start();

        $(this)

        .addClass('recording')

        .text('Stop');

        mediaRecorder.ondataavailable = e => {

            audioChunks.push(e.data);
        };

        mediaRecorder.onstop = () => {

            recordedAudioBlob =

                new Blob(audioChunks, {

                    type:'audio/webm'
                });

            let audioURL =

                URL.createObjectURL(
                    recordedAudioBlob
                );

            $('#ogs-ai-chat-body').append(`

                <div class="message-wrapper user-wrap">

                    <div class="chat-audio-message">

                        <audio controls>

                            <source
                                src="${audioURL}"
                                type="audio/webm"
                            >

                        </audio>

                    </div>

                </div>

            `);

            scrollSophiaChat();

            saveSophiaChat();
        };

    }else{

        mediaRecorder.stop();

        $(this)

        .removeClass('recording');
    }

});

/* ===================================================== */
/* SEND MESSAGE */
/* ===================================================== */

function sendSophiaAIMessage(){

    if(ogsChatInFlight){
        return;
    }

    let message = $('#ogs-ai-input').val();
    let file = $('#aiAttachment')[0]?.files[0];

    if(!message && !file && !recordedAudioBlob && !window.ogsPortalAction){
        return;
    }

    ogsChatInFlight = true;

    if(message){
        $('#ogs-ai-chat-body').append(
            '<div class="message-wrapper user-wrap ogs-user-turn">'
            + '<div class="ai-message user">' + $('<div>').text(message).html() + '</div>'
            + '<small class="message-time">' + getCurrentTime() + '</small>'
            + '</div>'
        );
        scrollSophiaChat();
        saveSophiaChat();
    }

    $('#ogs-ai-input').val('');

    showAIThinking();

    let formData = new FormData();
    formData.append('_token', "{{ csrf_token() }}");
    formData.append('message', message || '');

    if(file){
        formData.append('attachment', file);
    }

    if(recordedAudioBlob){
        formData.append('voice_message', recordedAudioBlob, 'voice-message.webm');
    }

    if(window.ogsPortalAction){
        formData.append('portal_action', window.ogsPortalAction);
    }

    window.ogsPortalAction = null;

    $.ajax({
        url: "{{ url('/ai/chat') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(res){
            removeAIThinking();
            ogsApplySeekerFlags(res);
            ogsApplyEmployerFlags(res);

            if(res.reply && String(res.reply).trim()){
                appendSophiaReply(res.reply);
            } else {
                ogsAppendBotBubble('Sorry, I could not generate a reply. Please try again.');
                saveSophiaChat();
            }

            if(res.redirect){
                window.ogsSeekerActive = false;
                setTimeout(function(){
                    window.location.href = res.redirect;
                }, 2000);
            }

            if(res.actions && res.actions.length){
                ogsRenderContextActions(res.actions);
            }

            if(res.voice_message){
                $('#ogs-ai-chat-body').append(
                    '<div class="message-wrapper">'
                    + '<div class="chat-audio-message">'
                    + '<audio controls><source src="' + res.voice_message + '" type="audio/webm"></audio>'
                    + '</div></div>'
                );
            }

            scrollSophiaChat();
            recordedAudioBlob = null;
            $('#aiAttachment').val('');
            ogsChatInFlight = false;
        },
        error: function(xhr){
            removeAIThinking();
            ogsChatInFlight = false;
            var serverMsg = (xhr && xhr.responseJSON && xhr.responseJSON.reply)
                ? xhr.responseJSON.reply
                : 'AI server error occurred. Please try again.';
            ogsAppendBotBubble(serverMsg);
            saveSophiaChat();
            scrollSophiaChat();
        }
    });
}

/* ===================================================== */
/* AUTO SAVE */
/* ===================================================== */

setInterval(function(){

    saveSophiaChat();

},5000);

</script>



   <!---- chatboat code end ----->
