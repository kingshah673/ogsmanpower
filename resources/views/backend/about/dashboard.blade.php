@extends('backend.layouts.app')
@section('title')
    {{ __('About Us') }}
@endsection

@section('content')

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#F0FBF8;color:#1A2332;}
:root{--green:#2ECC71;--cyan:#00BCD4;--red:#E53935;--orange:#FF6F00;}

/* Layout */
.admin-wrap{display:flex;min-height:100vh;}
.sidebar{width:260px;background:#1A2332;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sb-logo{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.08);font-weight:700;font-size:1.1rem;color:#fff;}
.sb-logo span{color:var(--green);}
.sb-menu{padding:.5rem 0;}
.sb-section{padding:.8rem 1.2rem .3rem;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);}
.sb-link{display:flex;align-items:center;gap:.7rem;padding:.7rem 1.2rem;color:rgba(255,255,255,.65);text-decoration:none;font-size:.83rem;transition:all .25s;cursor:pointer;border:none;background:none;width:100%;text-align:left;}
.sb-link:hover,.sb-link.active{background:rgba(255,255,255,.07);color:#fff;}
.sb-link .si{font-size:1rem;width:20px;}
.main{flex:1;overflow-y:auto;}

/* Topbar */
.topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:1rem 2rem;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.topbar h1{font-size:1.1rem;font-weight:700;}
.topbar-actions{display:flex;gap:.8rem;align-items:center;}
.tb-btn{padding:.5rem 1.1rem;border-radius:50px;font-size:.78rem;font-weight:700;cursor:pointer;text-decoration:none;transition:all .3s;display:flex;align-items:center;gap:.4rem;border:none;font-family:'Plus Jakarta Sans',sans-serif;}
.tb-btn.green{background:linear-gradient(135deg,var(--green),var(--cyan));color:#fff;}
.tb-btn.outline{background:#fff;border:1.5px solid #e5e7eb;color:#1A2332;}
.tb-btn.red{background:var(--red);color:#fff;}

/* Content */
.content{padding:2rem;}
.alert{padding:.9rem 1.2rem;border-radius:10px;margin-bottom:1.5rem;font-size:.85rem;font-weight:600;}
.alert.success{background:rgba(46,204,113,.12);color:#27AE60;border:1px solid rgba(46,204,113,.2);}
.alert.error{background:rgba(229,57,53,.1);color:var(--red);}

/* Tabs */
.tabs{display:flex;gap:.5rem;margin-bottom:2rem;flex-wrap:wrap;background:#fff;padding:.5rem;border-radius:12px;border:1px solid #e5e7eb;}
.tab-btn{padding:.55rem 1.2rem;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .25s;border:none;background:none;color:#6B7280;font-family:'Plus Jakarta Sans',sans-serif;}
.tab-btn.active{background:linear-gradient(135deg,var(--green),var(--cyan));color:#fff;}
.tab-content{display:none;}
.tab-content.active{display:block;}

/* Cards */
.admin-card{background:#fff;border-radius:14px;padding:1.5rem;margin-bottom:1.5rem;border:1px solid #e5e7eb;}
.admin-card h2{font-size:1rem;font-weight:700;margin-bottom:1.2rem;color:#1A2332;display:flex;align-items:center;gap:.5rem;}
.admin-card h2::before{content:'';width:18px;height:3px;background:linear-gradient(90deg,var(--green),var(--cyan));border-radius:2px;display:inline-block;}

/* Form elements */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.fg-full{grid-column:1/-1;}
.form-group{margin-bottom:1rem;}
.form-group label{display:block;font-size:.76rem;font-weight:600;color:#6B7280;margin-bottom:.35rem;}
.form-input{width:100%;background:#F9FAFB;border:1.5px solid #E5E7EB;padding:.65rem .9rem;color:#1A2332;font-family:'Plus Jakarta Sans',sans-serif;font-size:.85rem;outline:none;transition:border-color .3s;border-radius:8px;}
.form-input:focus{border-color:var(--green);background:#fff;}
textarea.form-input{min-height:100px;resize:vertical;}
.save-btn{background:linear-gradient(135deg,var(--green),var(--cyan));color:#fff;border:none;padding:.7rem 1.6rem;font-family:'Plus Jakarta Sans',sans-serif;font-size:.82rem;font-weight:700;cursor:pointer;border-radius:50px;transition:all .3s;}
.save-btn:hover{opacity:.85;}

/* Feature table */
.feat-table{width:100%;border-collapse:collapse;}
.feat-table th{padding:.6rem 1rem;background:#F9FAFB;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6B7280;}
.feat-table td{padding:.7rem 1rem;border-bottom:1px solid #F3F4F6;font-size:.83rem;vertical-align:middle;}
.feat-table tr:hover td{background:#F9FAFB;}
.status-chip{padding:.2rem .7rem;border-radius:50px;font-size:.68rem;font-weight:700;}
.status-chip.on{background:rgba(46,204,113,.12);color:#27AE60;}
.status-chip.off{background:rgba(229,57,53,.1);color:var(--red);}
.action-btn{padding:.3rem .8rem;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;border:none;font-family:'Plus Jakarta Sans',sans-serif;margin-right:.3rem;}
.action-btn.edit{background:#E3F2FD;color:#1565C0;}
.action-btn.del{background:rgba(229,57,53,.1);color:var(--red);}

/* Video grid */
.vid-admin-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;}
.vid-admin-card{background:#F9FAFB;border-radius:10px;border:1px solid #E5E7EB;overflow:hidden;}
.vac-thumb{height:100px;background:linear-gradient(135deg,rgba(46,204,113,.15),rgba(0,188,212,.15));display:flex;align-items:center;justify-content:center;font-size:2rem;}
.vac-info{padding:.8rem;}
.vac-title{font-size:.8rem;font-weight:700;margin-bottom:.3rem;}
.vac-url{font-size:.68rem;color:#6B7280;word-break:break-all;}
.del-vid-btn{margin-top:.5rem;background:rgba(229,57,53,.1);color:var(--red);border:none;padding:.3rem .8rem;border-radius:6px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;width:100%;}
</style>

<div class="admin-wrap">

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sb-logo">CW <span>Admin</span></div>
  <div class="sb-menu">
    <div class="sb-section">About Page</div>
    <button class="sb-link active" onclick="showTab('hero')"><span class="si">🎯</span> Hero Section</button>
    <button class="sb-link" onclick="showTab('story')"><span class="si">📖</span> Our Story</button>
    <button class="sb-link" onclick="showTab('features')"><span class="si">⭐</span> 16 Features</button>
    <button class="sb-link" onclick="showTab('metrics')"><span class="si">📊</span> Metrics</button>
    <button class="sb-link" onclick="showTab('industries')"><span class="si">🏭</span> Industries</button>
    <button class="sb-link" onclick="showTab('ceo')"><span class="si">👔</span> CEO Profile</button>
    <button class="sb-link" onclick="showTab('videos')"><span class="si">🎬</span> Videos</button>
    <button class="sb-link" onclick="showTab('social')"><span class="si">📱</span> Social Links</button>
    <button class="sb-link" onclick="showTab('config')"><span class="si">⚙️</span> Site Config</button>
    <div class="sb-section">Actions</div>
    <a href="/about" target="_blank" class="sb-link"><span class="si">👁</span> Preview Page</a>
    <form method="POST" action="{{ route('admin.about.cache.clear') }}" style="margin:0;">
      @csrf
      <button type="submit" class="sb-link"><span class="si">🔄</span> Clear Cache</button>
    </form>
    <a href="/admin/logout" class="sb-link"><span class="si">🚪</span> Logout</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <h1>About Page Manager — CareerWorkforce.com</h1>
    <div class="topbar-actions">
      <a href="/about" target="_blank" class="tb-btn outline">👁 Preview</a>
      <form method="POST" action="{{ route('admin.about.cache.clear') }}" style="margin:0;">
        @csrf
        <button type="submit" class="tb-btn green">🔄 Publish Changes</button>
      </form>
    </div>
  </div>

  <div class="content">
    @if(session('success'))
    <div class="alert success">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert error">❌ {{ session('error') }}</div>
    @endif
    @if(!empty($about_tables_missing))
    <div class="alert error">
      ❌ About page tables are not installed on this server yet. After deploying the latest code, run:
      <code style="display:block;margin-top:.5rem;">php artisan migrate --force && php artisan db:seed --class=AboutPageSeeder --force</code>
    </div>
    @endif
    @if($errors->any())
    <div class="alert error">❌ {{ $errors->first() }}</div>
    @endif

    <!-- TABS -->
    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('hero')">🎯 Hero</button>
      <button class="tab-btn" onclick="showTab('story')">📖 Story</button>
      <button class="tab-btn" onclick="showTab('features')">⭐ Features</button>
      <button class="tab-btn" onclick="showTab('metrics')">📊 Metrics</button>
      <button class="tab-btn" onclick="showTab('industries')">🏭 Industries</button>
      <button class="tab-btn" onclick="showTab('ceo')">👔 CEO</button>
      <button class="tab-btn" onclick="showTab('videos')">🎬 Videos</button>
      <button class="tab-btn" onclick="showTab('social')">📱 Social</button>
      <button class="tab-btn" onclick="showTab('config')">⚙️ Config</button>
    </div>

    <!-- ── HERO TAB ── -->
    <div class="tab-content active" id="tab-hero">
      <div class="admin-card">
        <h2>Hero Section</h2>
        <form method="POST" action="{{ route('admin.about.hero.update') }}">
          @csrf
          <div class="form-grid">
            <div class="form-group fg-full"><label>Badge Text</label><input type="text" name="badge_text" class="form-input" value="{{ $hero->badge_text ?? '' }}"></div>
            <div class="form-group fg-full"><label>Headline (HTML allowed, use &lt;em&gt; for gradient text)</label><input type="text" name="headline" class="form-input" value="{{ $hero->headline ?? '' }}"></div>
            <div class="form-group fg-full"><label>Subheadline</label><textarea name="subheadline" class="form-input">{{ $hero->subheadline ?? '' }}</textarea></div>
            <div class="form-group"><label>Pill 1</label><input type="text" name="pill_1" class="form-input" value="{{ $hero->pill_1 ?? '' }}"></div>
            <div class="form-group"><label>Pill 2</label><input type="text" name="pill_2" class="form-input" value="{{ $hero->pill_2 ?? '' }}"></div>
            <div class="form-group"><label>Pill 3</label><input type="text" name="pill_3" class="form-input" value="{{ $hero->pill_3 ?? '' }}"></div>
            <div class="form-group"><label>Stat 1 Value</label><input type="text" name="stat_1_val" class="form-input" value="{{ $hero->stat_1_val ?? '' }}"></div>
            <div class="form-group"><label>Stat 1 Label</label><input type="text" name="stat_1_lbl" class="form-input" value="{{ $hero->stat_1_lbl ?? '' }}"></div>
            <div class="form-group"><label>Stat 2 Value</label><input type="text" name="stat_2_val" class="form-input" value="{{ $hero->stat_2_val ?? '' }}"></div>
            <div class="form-group"><label>Stat 2 Label</label><input type="text" name="stat_2_lbl" class="form-input" value="{{ $hero->stat_2_lbl ?? '' }}"></div>
            <div class="form-group"><label>Stat 3 Value</label><input type="text" name="stat_3_val" class="form-input" value="{{ $hero->stat_3_val ?? '' }}"></div>
            <div class="form-group"><label>Stat 3 Label</label><input type="text" name="stat_3_lbl" class="form-input" value="{{ $hero->stat_3_lbl ?? '' }}"></div>
          </div>
          <button type="submit" class="save-btn">💾 Save Hero</button>
        </form>
      </div>
    </div>

    <!-- ── FEATURES TAB ── -->
    <div class="tab-content" id="tab-features">
      <div class="admin-card">
        <h2>Feature Cards — 16 Key Points</h2>
        <table class="feat-table">
          <thead><tr><th>#</th><th>Icon</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            @foreach($features as $f)
            <tr>
              <td>{{ $f->sort_order }}</td>
              <td style="font-size:1.5rem;">{{ $f->icon_emoji }}</td>
              <td><strong>{{ $f->title }}</strong><br><span style="font-size:.73rem;color:#6B7280;">{{ Str::limit($f->teaser, 60) }}</span></td>
              <td><span class="status-chip {{ $f->is_active ? 'on' : 'off' }}">{{ $f->is_active ? 'Active' : 'Hidden' }}</span></td>
              <td>
                <button class="action-btn edit" onclick="openEditFeature({{ json_encode($f) }})">✏️ Edit</button>
                <form method="POST" action="{{ route('admin.about.features.destroy', $f->id) }}" style="display:inline;" onsubmit="return confirm('Delete this feature?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="action-btn del">🗑 Delete</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Add New Feature -->
      <div class="admin-card">
        <h2>Add New Feature Card</h2>
        <form method="POST" action="{{ route('admin.about.features.store') }}">
          @csrf
          <div class="form-grid">
            <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="17"></div>
            <div class="form-group"><label>Icon Emoji</label><input type="text" name="icon_emoji" class="form-input" placeholder="🎯"></div>
            <div class="form-group"><label>Icon Background Color</label><input type="color" name="icon_bg_color" class="form-input" value="#E8F5E9"></div>
            <div class="form-group"><label>CTA Button Text</label><input type="text" name="cta_text" class="form-input" value="Register Now →"></div>
            <div class="form-group fg-full"><label>Title</label><input type="text" name="title" class="form-input" placeholder="Feature title..."></div>
            <div class="form-group fg-full"><label>Teaser (shown on card)</label><input type="text" name="teaser" class="form-input" placeholder="Short description for the card..."></div>
            <div class="form-group fg-full"><label>Modal Body (HTML allowed)</label><textarea name="modal_body" class="form-input" rows="6" placeholder="&lt;p&gt;Full description...&lt;/p&gt;&lt;h4&gt;Section&lt;/h4&gt;&lt;ul&gt;&lt;li&gt;Point&lt;/li&gt;&lt;/ul&gt;"></textarea></div>
            <div class="form-group fg-full"><label>Badge Tags (comma-separated)</label><input type="text" name="badge_tags" class="form-input" placeholder="Tag1,Tag2,Tag3"></div>
          </div>
          <button type="submit" class="save-btn">➕ Add Feature</button>
        </form>
      </div>
    </div>

    <!-- ── CEO TAB ── -->
    <div class="tab-content" id="tab-ceo">
      <div class="admin-card">
        <h2>CEO Profile</h2>
        <form method="POST" action="{{ route('admin.about.ceo.update') }}" enctype="multipart/form-data">
          @csrf
          <div class="form-grid">
            <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-input" value="{{ $ceo->name ?? '' }}"></div>
            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-input" value="{{ $ceo->title ?? '' }}"></div>
            <div class="form-group"><label>Location</label><input type="text" name="location" class="form-input" value="{{ $ceo->location ?? '' }}"></div>
            <div class="form-group"><label>Years Experience</label><input type="text" name="experience" class="form-input" value="{{ $ceo->experience ?? '25+' }}"></div>
            <div class="form-group"><label>Tags (comma-separated)</label><input type="text" name="tags" class="form-input" value="{{ $ceo->tags ?? '' }}"></div>
            <div class="form-group"><label>Profile Photo</label><input type="file" name="photo" class="form-input" accept="image/*"></div>
            <div class="form-group fg-full"><label>CEO Quote (for public page)</label><textarea name="quote" class="form-input" rows="3">{{ $ceo->quote ?? '' }}</textarea></div>
            <div class="form-group fg-full"><label>Biography</label><textarea name="bio" class="form-input" rows="4">{{ $ceo->bio ?? '' }}</textarea></div>
            <div class="form-group fg-full"><label>Credentials (one per line)</label><textarea name="creds" class="form-input" rows="6">{{ isset($ceo->creds) ? implode("\n", json_decode($ceo->creds, true) ?? []) : '' }}</textarea></div>
          </div>
          <button type="submit" class="save-btn">💾 Save CEO Profile</button>
        </form>
      </div>
    </div>

    <!-- ── VIDEOS TAB ── -->
    <div class="tab-content" id="tab-videos">
      <div class="admin-card">
        <h2>Existing Videos</h2>
        <div class="vid-admin-grid">
          @foreach($videos as $v)
          <div class="vid-admin-card">
            <div class="vac-thumb">{{ $v->video_type === 'youtube' ? '▶️' : '🎬' }}</div>
            <div class="vac-info">
              <div class="vac-title">{{ $v->title }}</div>
              <div class="vac-url">{{ $v->video_url }}</div>
              <form method="POST" action="{{ route('admin.about.videos.destroy', $v->id) }}" onsubmit="return confirm('Remove video?')">
                @csrf @method('DELETE')
                <button type="submit" class="del-vid-btn">🗑 Remove</button>
              </form>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      <div class="admin-card">
        <h2>Add New Video</h2>
        <form method="POST" action="{{ route('admin.about.videos.store') }}" enctype="multipart/form-data">
          @csrf
          <div class="form-grid">
            <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" class="form-input" value="5"></div>
            <div class="form-group"><label>Duration (e.g. 3:45)</label><input type="text" name="duration" class="form-input" placeholder="3:45"></div>
            <div class="form-group fg-full"><label>Title</label><input type="text" name="title" class="form-input" placeholder="Video title"></div>
            <div class="form-group fg-full"><label>Description</label><input type="text" name="description" class="form-input" placeholder="Short description"></div>
            <div class="form-group"><label>Video Type</label>
              <select name="video_type" class="form-input" onchange="toggleVideoInput(this.value)">
                <option value="youtube">YouTube Embed</option>
                <option value="vimeo">Vimeo Embed</option>
                <option value="upload">Upload MP4</option>
              </select>
            </div>
            <div class="form-group" id="url-field"><label>Video Embed URL</label><input type="text" name="video_url" class="form-input" placeholder="https://www.youtube.com/embed/VIDEO_ID"></div>
            <div class="form-group" id="file-field" style="display:none;"><label>Upload Video File (MP4)</label><input type="file" name="video_file" class="form-input" accept="video/mp4"></div>
            <div class="form-group"><label>Thumbnail URL (optional)</label><input type="text" name="thumbnail" class="form-input" placeholder="https://img.youtube.com/vi/VIDEO_ID/hqdefault.jpg"></div>
          </div>
          <button type="submit" class="save-btn">➕ Add Video</button>
        </form>
      </div>
    </div>

    <!-- ── METRICS TAB ── -->
    <div class="tab-content" id="tab-metrics">
      <div class="admin-card">
        <h2>Metrics / Stats Bar</h2>
        <form method="POST" action="{{ route('admin.about.metrics.update') }}">
          @csrf
          @foreach($metrics as $m)
          <div class="form-grid" style="border-bottom:1px solid #F3F4F6;padding-bottom:.8rem;margin-bottom:.8rem;">
            <div class="form-group"><label>Value (e.g. 15+)</label><input type="text" name="metrics[{{ $m->id }}][value]" class="form-input" value="{{ $m->value }}"></div>
            <div class="form-group"><label>Label</label><input type="text" name="metrics[{{ $m->id }}][label]" class="form-input" value="{{ $m->label }}"></div>
            <div class="form-group"><label>Icon Emoji</label><input type="text" name="metrics[{{ $m->id }}][icon]" class="form-input" value="{{ $m->icon }}"></div>
            <div class="form-group"><label>Active</label><select name="metrics[{{ $m->id }}][is_active]" class="form-input"><option value="1" {{ $m->is_active ? 'selected' : '' }}>Yes</option><option value="0" {{ !$m->is_active ? 'selected' : '' }}>No</option></select></div>
          </div>
          @endforeach
          <button type="submit" class="save-btn">💾 Save Metrics</button>
        </form>
      </div>
    </div>

    <!-- ── SOCIAL TAB ── -->
    <div class="tab-content" id="tab-social">
      <div class="admin-card">
        <h2>Social Media Links</h2>
        <form method="POST" action="{{ route('admin.about.social.update') }}">
          @csrf
          @foreach($config->all() as $key => $val){{-- just placeholder for social form --}}@endforeach
          <p style="font-size:.8rem;color:#6B7280;margin-bottom:1rem;">Update the URL for each social platform. Set to # to hide a platform.</p>
          @foreach(DB::table('about_social_links')->orderBy('sort_order')->get() as $sl)
          <div class="form-grid" style="border-bottom:1px solid #F3F4F6;padding-bottom:.7rem;margin-bottom:.7rem;align-items:center;">
            <div class="form-group" style="margin:0;display:flex;align-items:center;gap:.7rem;">
              <span style="font-size:1.4rem;">{{ $sl->icon }}</span>
              <strong style="font-size:.85rem;">{{ $sl->platform }}</strong>
            </div>
            <div class="form-group" style="margin:0;grid-column:span 1;">
              <input type="text" name="links[{{ $sl->id }}][url]" class="form-input" value="{{ $sl->url }}" placeholder="https://...">
            </div>
            <div class="form-group" style="margin:0;">
              <select name="links[{{ $sl->id }}][is_active]" class="form-input">
                <option value="1" {{ $sl->is_active ? 'selected' : '' }}>Active</option>
                <option value="0" {{ !$sl->is_active ? 'selected' : '' }}>Hidden</option>
              </select>
            </div>
          </div>
          @endforeach
          <button type="submit" class="save-btn">💾 Save Social Links</button>
        </form>
      </div>
    </div>

    <!-- ── CONFIG TAB ── -->
    <div class="tab-content" id="tab-config">
      <div class="admin-card">
        <h2>Site Configuration</h2>
        <form method="POST" action="{{ route('admin.about.config.update') }}">
          @csrf
          <div class="form-grid">
            <div class="form-group"><label>WhatsApp Number (no +)</label><input type="text" name="config[whatsapp_number]" class="form-input" value="{{ $config['whatsapp_number'] ?? '' }}"></div>
            <div class="form-group"><label>Email Address</label><input type="email" name="config[email_address]" class="form-input" value="{{ $config['email_address'] ?? '' }}"></div>
            <div class="form-group"><label>Register URL</label><input type="text" name="config[register_url]" class="form-input" value="{{ $config['register_url'] ?? '/register' }}"></div>
            <div class="form-group"><label>Google Analytics ID</label><input type="text" name="config[google_analytics_id]" class="form-input" value="{{ $config['google_analytics_id'] ?? '' }}" placeholder="G-XXXXXXXXXX"></div>
            <div class="form-group fg-full"><label>Footer Copyright Text</label><input type="text" name="config[footer_copyright]" class="form-input" value="{{ $config['footer_copyright'] ?? '' }}"></div>

            <div class="form-group fg-full"><hr style="border:none;border-top:1px solid #E5E7EB;margin:.5rem 0;"><strong>About page section titles</strong></div>
            <div class="form-group"><label>Features label</label><input type="text" name="config[features_label]" class="form-input" value="{{ $config['features_label'] ?? 'Why Choose OGS' }}"></div>
            <div class="form-group"><label>Features title</label><input type="text" name="config[features_title]" class="form-input" value="{{ $config['features_title'] ?? 'Why Employers Trust OGS' }}"></div>
            <div class="form-group fg-full"><label>Features intro</label><input type="text" name="config[features_intro]" class="form-input" value="{{ $config['features_intro'] ?? 'Click any card below to explore our strengths in detail.' }}"></div>
            <div class="form-group"><label>Journey title</label><input type="text" name="config[journey_title]" class="form-input" value="{{ $config['journey_title'] ?? 'OGS Journey' }}"></div>
            <div class="form-group"><label>Global presence title</label><input type="text" name="config[global_title]" class="form-input" value="{{ $config['global_title'] ?? 'Our Global Presence' }}"></div>
            <div class="form-group"><label>Portal title</label><input type="text" name="config[portal_title]" class="form-input" value="{{ $config['portal_title'] ?? 'Our Candidate Portal' }}"></div>
            <div class="form-group"><label>Portal subtitle</label><input type="text" name="config[portal_subtitle]" class="form-input" value="{{ $config['portal_subtitle'] ?? 'Find Pre-Screened Candidates Instantly' }}"></div>
            <div class="form-group fg-full"><label>Portal bullets (one per line)</label><textarea name="config[portal_bullets]" class="form-input" rows="3">{{ $config['portal_bullets'] ?? "Search & Filter Profiles\nVerified CVs\nVideo Interviews" }}</textarea></div>
            <div class="form-group"><label>Portal button text</label><input type="text" name="config[portal_btn_text]" class="form-input" value="{{ $config['portal_btn_text'] ?? 'Explore Candidates' }}"></div>
            <div class="form-group"><label>Portal button URL</label><input type="text" name="config[portal_btn_url]" class="form-input" value="{{ $config['portal_btn_url'] ?? '/candidates' }}"></div>
            <div class="form-group"><label>Industries title</label><input type="text" name="config[industries_title]" class="form-input" value="{{ $config['industries_title'] ?? 'Industries We Serve' }}"></div>
            <div class="form-group"><label>Connect title</label><input type="text" name="config[connect_title]" class="form-input" value="{{ $config['connect_title'] ?? 'Connect With OGS Manpower' }}"></div>
            <div class="form-group fg-full"><label>Connect subtitle</label><input type="text" name="config[connect_subtitle]" class="form-input" value="{{ $config['connect_subtitle'] ?? 'Follow OGS across our channels for updates, opportunities, and community news.' }}"></div>
            <div class="form-group"><label>Join CTA title</label><input type="text" name="config[join_title]" class="form-input" value="{{ $config['join_title'] ?? 'Join OGS Manpower' }}"></div>
            <div class="form-group fg-full"><label>Join CTA text</label><input type="text" name="config[join_text]" class="form-input" value="{{ $config['join_text'] ?? 'Create your account as a job seeker or employer and get started in minutes.' }}"></div>
          </div>
          <button type="submit" class="save-btn">💾 Save Configuration</button>
        </form>
      </div>
    </div>

    <!-- ── STORY TAB ── -->
    <div class="tab-content" id="tab-story">
      <div class="admin-card">
        <h2>Our Story Section</h2>
        <form method="POST" action="{{ route('admin.about.story.update') }}">
          @csrf
          <div class="form-grid">
            <div class="form-group"><label>Section Label</label><input type="text" name="section_label" class="form-input" value="{{ $story->section_label ?? '' }}" placeholder="Our Story"></div>
            <div class="form-group"><label>License Text</label><input type="text" name="license_text" class="form-input" value="{{ $story->license_text ?? '' }}" placeholder="License No. 2978/RWP"></div>
            <div class="form-group fg-full"><label>Headline</label><input type="text" name="headline" class="form-input" value="{{ $story->headline ?? '' }}"></div>
            <div class="form-group fg-full"><label>Quote</label><textarea name="quote" class="form-input" rows="2">{{ $story->quote ?? '' }}</textarea></div>
            <div class="form-group fg-full"><label>Body Paragraph 1</label><textarea name="body_1" class="form-input" rows="4">{{ $story->body_1 ?? '' }}</textarea></div>
            <div class="form-group fg-full"><label>Body Paragraph 2</label><textarea name="body_2" class="form-input" rows="4">{{ $story->body_2 ?? '' }}</textarea></div>
            <div class="form-group fg-full"><label>Body Paragraph 3</label><textarea name="body_3" class="form-input" rows="4">{{ $story->body_3 ?? '' }}</textarea></div>
            <div class="form-group fg-full"><label>Mission block (one line per bullet, shown under “Our mission is to”)</label><textarea name="mission" class="form-input" rows="5" placeholder="Build long-term partnerships with employers&#10;Deliver reliable and skilled manpower solutions">{{ $story->mission ?? '' }}</textarea></div>
            <div class="form-group"><label>Card 1 Number</label><input type="text" name="card_1_num" class="form-input" value="{{ $story->card_1_num ?? '' }}"></div>
            <div class="form-group"><label>Card 1 Label</label><input type="text" name="card_1_lbl" class="form-input" value="{{ $story->card_1_lbl ?? '' }}"></div>
            <div class="form-group fg-full"><label>Card 1 Description</label><input type="text" name="card_1_desc" class="form-input" value="{{ $story->card_1_desc ?? '' }}"></div>
            <div class="form-group"><label>Card 2 Number</label><input type="text" name="card_2_num" class="form-input" value="{{ $story->card_2_num ?? '' }}"></div>
            <div class="form-group"><label>Card 2 Label</label><input type="text" name="card_2_lbl" class="form-input" value="{{ $story->card_2_lbl ?? '' }}"></div>
            <div class="form-group fg-full"><label>Card 2 Description</label><input type="text" name="card_2_desc" class="form-input" value="{{ $story->card_2_desc ?? '' }}"></div>
          </div>
          <button type="submit" class="save-btn">💾 Save Story</button>
        </form>
      </div>
    </div>

    <!-- ── INDUSTRIES TAB ── -->
    <div class="tab-content" id="tab-industries">
      <div class="admin-card">
        <h2>Industries</h2>
        <p style="font-size:.8rem;color:#6B7280;margin-bottom:1rem;">Upload an icon image for each industry, edit the name, then click Save. Set Active to No to hide it on the site.</p>
        @forelse(($industries ?? collect()) as $ind)
          @php
            $iconVal = (string) ($ind->icon ?? '');
            $iconIsImage = $iconVal !== '' && (str_contains($iconVal, '/') || preg_match('/\.(png|jpe?g|webp|svg)$/i', $iconVal));
            $iconSrc = $iconIsImage
                ? (str_starts_with($iconVal, 'http') ? $iconVal : asset(ltrim($iconVal, '/')))
                : null;
          @endphp
          <form method="POST" action="{{ route('admin.about.industries.update', $ind->id) }}" enctype="multipart/form-data" class="form-grid" style="border-bottom:1px solid #F3F4F6;padding-bottom:.8rem;margin-bottom:.8rem;align-items:end;">
            @csrf
            @method('PUT')
            <div class="form-group">
              <label>Icon</label>
              <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.4rem;">
                @if ($iconSrc)
                  <img src="{{ $iconSrc }}" alt="{{ $ind->name }}" style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:#F3F4F6;">
                @elseif ($iconVal !== '')
                  <span style="font-size:1.8rem;line-height:1;">{{ $iconVal }}</span>
                @else
                  <span style="font-size:.75rem;color:#9CA3AF;">No icon</span>
                @endif
              </div>
              <input type="file" name="icon_file" class="form-input" accept="image/png,image/jpeg,image/webp,image/svg+xml">
              <small style="color:#6B7280;">Upload a PNG/JPG to replace the current icon.</small>
            </div>
            <div class="form-group"><label>Name</label><input type="text" name="name" class="form-input" value="{{ $ind->name }}" required></div>
            <div class="form-group"><label>Description</label><input type="text" name="description" class="form-input" value="{{ $ind->description }}"></div>
            <div class="form-group"><label>Active</label>
              <select name="is_active" class="form-input">
                <option value="1" {{ $ind->is_active ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$ind->is_active ? 'selected' : '' }}>No</option>
              </select>
            </div>
            <div class="form-group"><button type="submit" class="save-btn" style="padding:.55rem 1.2rem;">Save</button></div>
          </form>
        @empty
          <p style="color:#6B7280;">No industries found. Run <code>php artisan db:seed --class=AboutPageSeeder</code>.</p>
        @endforelse
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /admin-wrap -->

<!-- Edit Feature Modal -->
<div id="editFeatureModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#fff;border-radius:16px;max-width:700px;width:100%;max-height:90vh;overflow-y:auto;padding:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h3 style="font-size:1.1rem;font-weight:700;">Edit Feature Card</h3>
      <button onclick="document.getElementById('editFeatureModal').style.display='none'" style="background:#F3F4F6;border:none;border-radius:50%;width:32px;height:32px;cursor:pointer;font-size:1rem;">✕</button>
    </div>
    <form method="POST" id="editFeatureForm">
      @csrf @method('PUT')
      <div class="form-grid">
        <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" id="ef_order" class="form-input"></div>
        <div class="form-group"><label>Icon Emoji</label><input type="text" name="icon_emoji" id="ef_icon" class="form-input"></div>
        <div class="form-group"><label>Icon BG Color</label><input type="color" name="icon_bg_color" id="ef_color" class="form-input"></div>
        <div class="form-group"><label>Active</label><select name="is_active" id="ef_active" class="form-input"><option value="1">Yes</option><option value="0">No</option></select></div>
        <div class="form-group fg-full"><label>Title</label><input type="text" name="title" id="ef_title" class="form-input"></div>
        <div class="form-group fg-full"><label>Teaser</label><input type="text" name="teaser" id="ef_teaser" class="form-input"></div>
        <div class="form-group fg-full"><label>Modal Body (HTML)</label><textarea name="modal_body" id="ef_body" class="form-input" rows="8"></textarea></div>
        <div class="form-group fg-full"><label>Badge Tags</label><input type="text" name="badge_tags" id="ef_tags" class="form-input"></div>
        <div class="form-group"><label>CTA Text</label><input type="text" name="cta_text" id="ef_cta" class="form-input"></div>
      </div>
      <button type="submit" class="save-btn">💾 Save Changes</button>
    </form>
  </div>
</div>

<script>
function showTab(name){
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-'+name)?.classList.add('active');
  document.querySelectorAll('.tab-btn').forEach(function(b){
    b.classList.toggle('active', (b.getAttribute('onclick')||'').indexOf("'"+name+"'") !== -1);
  });
  document.querySelectorAll('.sb-link').forEach(function(l){
    var onclick = l.getAttribute('onclick') || '';
    l.classList.toggle('active', onclick.indexOf("'"+name+"'") !== -1);
  });
}
function openEditFeature(f){
  document.getElementById('editFeatureForm').action='/admin/about/features/'+f.id;
  document.getElementById('ef_order').value=f.sort_order;
  document.getElementById('ef_icon').value=f.icon_emoji;
  document.getElementById('ef_color').value=f.icon_bg_color||'#E8F5E9';
  document.getElementById('ef_active').value=f.is_active;
  document.getElementById('ef_title').value=f.title;
  document.getElementById('ef_teaser').value=f.teaser;
  document.getElementById('ef_body').value=f.modal_body;
  document.getElementById('ef_tags').value=f.badge_tags||'';
  document.getElementById('ef_cta').value=f.cta_text||'Register Now →';
  document.getElementById('editFeatureModal').style.display='flex';
}
function toggleVideoInput(type){
  document.getElementById('url-field').style.display=type==='upload'?'none':'block';
  document.getElementById('file-field').style.display=type==='upload'?'block':'none';
}
</script>
@endsection