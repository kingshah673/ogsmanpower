{{-- Site-wide lead generation floating buttons --}}
@php
    $leadAboutConfig = [];
    if (\Illuminate\Support\Facades\Schema::hasTable('about_config')) {
        $leadAboutConfig = \Illuminate\Support\Facades\DB::table('about_config')->pluck('cfg_value', 'cfg_key')->toArray();
    }
    $leadWhatsapp = preg_replace('/[^0-9]/', '', (string) ($leadAboutConfig['whatsapp_number'] ?? '923005352636')) ?: '923005352636';
    $leadEmail = $leadAboutConfig['email_address'] ?? ($setting->email ?? 'info@ogsmanpower.com');
    $leadRegister = $leadAboutConfig['register_url'] ?? route('register');
    if ($leadRegister && !str_starts_with($leadRegister, 'http')) {
        $leadRegister = url('/' . ltrim($leadRegister, '/'));
    }
@endphp

<style>
.ogs-lead-floats {
    position: fixed;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1040;
    display: flex;
    flex-direction: column;
    gap: 0;
    box-shadow: -4px 0 20px rgba(0,0,0,.18);
}
.ogs-lead-btn {
    display: flex;
    align-items: center;
    gap: .65rem;
    padding: .9rem 1.05rem;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    text-decoration: none !important;
    color: #fff !important;
    border: none;
    cursor: pointer;
    font-family: 'DM Sans', system-ui, sans-serif;
    transition: padding .25s ease, filter .2s ease;
    white-space: nowrap;
}
.ogs-lead-btn:hover { filter: brightness(1.06); padding-right: 1.35rem; color: #fff !important; }
.ogs-lead-btn .ogs-lead-icon {
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.ogs-lead-btn .ogs-lead-icon svg { width: 20px; height: 20px; fill: currentColor; }
.ogs-lead-btn .ogs-lead-label {
    max-width: 0;
    overflow: hidden;
    opacity: 0;
    transition: max-width .35s ease, opacity .35s ease;
}
.ogs-lead-btn:hover .ogs-lead-label,
.ogs-lead-btn:focus .ogs-lead-label {
    max-width: 140px;
    opacity: 1;
}
.ogs-lead-btn.wa { background: #25D366; }
.ogs-lead-btn.em { background: #E8B923; color: #1a1a1a !important; }
.ogs-lead-btn.em:hover { color: #1a1a1a !important; }
.ogs-lead-btn.reg { background: #E05C2A; }
@media (max-width: 768px) {
    .ogs-lead-floats {
        top: auto;
        bottom: 0;
        right: 0;
        left: 0;
        transform: none;
        flex-direction: row;
        width: 100%;
        box-shadow: 0 -4px 18px rgba(0,0,0,.2);
    }
    .ogs-lead-btn {
        flex: 1;
        justify-content: center;
        padding: .85rem .4rem;
        border-radius: 0;
    }
    .ogs-lead-btn .ogs-lead-label {
        max-width: none;
        opacity: 1;
        font-size: .62rem;
    }
    .ogs-lead-btn:hover { padding-right: .4rem; }
}
</style>

<nav class="ogs-lead-floats" aria-label="Lead generation">
    <a href="https://wa.me/{{ $leadWhatsapp }}"
       class="ogs-lead-btn wa"
       data-lead-source="floating_whatsapp"
       target="_blank"
       rel="noopener noreferrer">
        <span class="ogs-lead-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M20.52 3.48A11.86 11.86 0 0012.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.15 1.6 5.96L0 24l6.3-1.65a11.9 11.9 0 005.75 1.47h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.44-8.44zM12.06 21.8h-.01a9.86 9.86 0 01-5.02-1.37l-.36-.21-3.74.98 1-3.64-.24-.37a9.86 9.86 0 01-1.51-5.28c0-5.44 4.43-9.87 9.88-9.87 2.64 0 5.12 1.03 6.98 2.9a9.82 9.82 0 012.9 6.98c0 5.44-4.43 9.86-9.88 9.86zm5.42-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35z"/></svg>
        </span>
        <span class="ogs-lead-label">WhatsApp Us</span>
    </a>
    <a href="mailto:{{ $leadEmail }}"
       class="ogs-lead-btn em"
       data-lead-source="floating_email">
        <span class="ogs-lead-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        </span>
        <span class="ogs-lead-label">Email Us</span>
    </a>
    <a href="{{ $leadRegister }}"
       class="ogs-lead-btn reg"
       data-lead-source="floating_register">
        <span class="ogs-lead-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H7v-2h5v2zm5-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        </span>
        <span class="ogs-lead-label">Register Now</span>
    </a>
</nav>

<script>
(function () {
    var trackUrl = @json(route('website.leads.track'));
    var token = @json(csrf_token());

    document.querySelectorAll('.ogs-lead-btn[data-lead-source]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var source = btn.getAttribute('data-lead-source');
            var fallback = btn.getAttribute('href');
            var openBlank = btn.getAttribute('target') === '_blank';

            fetch(trackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ source: source })
            }).then(function (res) { return res.json(); })
              .then(function (data) {
                  var url = (data && data.redirect) ? data.redirect : fallback;
                  if (openBlank) {
                      window.open(url, '_blank', 'noopener');
                  } else if (url.indexOf('mailto:') === 0) {
                      window.location.href = url;
                  } else {
                      window.location.href = url;
                  }
              })
              .catch(function () {
                  if (openBlank) {
                      window.open(fallback, '_blank', 'noopener');
                  } else {
                      window.location.href = fallback;
                  }
              });
        });
    });
})();
</script>
