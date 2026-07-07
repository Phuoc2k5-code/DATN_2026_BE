<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>CV - {{ $candidate->full_name }}</title>
  <style>
    /* 1. ĐỊNH CẤU TRÚC KHỔ GIẤY A4 CHUẨN IN ẤN */
    @page {
      size: a4 portrait;
      margin: 8mm 15mm 8mm 15mm;
      /* Ép sát lề trên dưới tối đa để đẩy toàn bộ nội dung lên */
    }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 11px;
      color: #334155;
      line-height: 1.3;
      /* Thu hẹp chiều cao dòng toàn bài */
      margin: 0;
      padding: 0;
      background-color: #ffffff;
    }

    /* Utility classes */
    .w-full {
      width: 100%;
    }

    .border-collapse {
      border-collapse: collapse;
    }

    .text-justify {
      text-align: justify;
    }

    .whitespace-pre-line {
      white-space: pre-line;
    }

    .break-words {
      word-wrap: break-word;
    }

    /* 🌸 1. KHỐI ĐẦU TRANG (HEADER) */
    .header-table {
      width: 100%;
      border-collapse: collapse;
      background-color: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
    }

    .header-left {
      padding: 12px 25px;
      /* Giảm padding để thu hẹp độ cao header */
      vertical-align: middle;
    }

    .header-right {
      padding: 12px 25px;
      width: 80px;
      text-align: right;
      vertical-align: middle;
    }

    .candidate-name {
      font-size: 22px;
      color: #1e293b;
      margin: 0 0 2px 0;
      font-weight: normal;
    }

    .candidate-name-bold {
      font-weight: bold;
      color: #312e81;
    }

    .candidate-title {
      font-size: 10px;
      font-weight: bold;
      color: #4f46e5;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 0;
    }

    /* Avatar */
    .avatar-circle {
      width: 70px;
      /* Thu nhỏ nhẹ avatar để tiết kiệm diện tích dọc */
      height: 70px;
      border-radius: 50%;
      background-color: #ffffff;
      border: 1px solid #e2e8f0;
      overflow: hidden;
      display: inline-block;
    }

    .avatar-circle img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    /* 📋 2. THÔNG TIN LIÊN HỆ - THANH NGANG */
    .contact-bar {
      width: 100%;
      border-collapse: collapse;
      background-color: #0f172a;
      color: #cbd5e1;
      margin-bottom: 6px;
      /* Giảm khoảng cách tối đa dưới thanh contact */
    }

    .contact-item {
      width: 25%;
      padding: 5px 10px;
      /* Siết mỏng thanh liên hệ */
      font-size: 10px;
      text-align: center;
      border-right: 1px solid #334155;
      vertical-align: middle;
    }

    .contact-item-last {
      border-right: none;
    }

    .icon-indigo {
      color: #818cf8;
    }

    /* 🔗 KHỐI LIÊN KẾT MẠNG XÃ HỘI */
    .social-bar {
      width: 100%;
      border-collapse: collapse;
      background-color: #f1f5f9;
      margin-bottom: 8px;
      /* Giảm khoảng cách hở */
      border-radius: 4px;
    }

    .social-item {
      padding: 4px 12px;
      /* Thu hẹp ruột thanh liên kết mạng xã hội */
      font-size: 10px;
      color: #475569;
    }

    /* 📑 3. PHẦN THÂN CV (BODY) */
    .body-container {
      padding: 0 5px;
    }

    /* Bố cục Khối giới thiệu */
    .intro-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
    }

    .intro-left {
      vertical-align: top;
      padding-right: 20px;
    }

    .intro-right {
      width: 110px;
      vertical-align: top;
    }

    .experience-box {
      background-color: #f8fafc;
      border: 1px solid #e0e7ff;
      border-radius: 8px;
      padding: 6px;
      text-align: center;
      margin-top: 6px;
    }

    .exp-number {
      font-size: 20px;
      color: #4f46e5;
      font-weight: bold;
      line-height: 1;
      margin-bottom: 2px;
    }

    .exp-label {
      font-size: 8px;
      font-weight: bold;
      color: #64748b;
      text-transform: uppercase;
    }

    /* Tiêu đề phân đoạn chuẩn Elegant - ĐÃ SIẾT CHẶT MARGIN CHỐNG TRỐNG */
    .section-title {
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      color: #1e1b4b;
      border-bottom: 1px solid #cbd5e1;
      padding-bottom: 2px;
      margin-top: 10px;
      /* Thu hẹp lề trên để kéo các mục xê lên sát nhau */
      margin-bottom: 4px;
      /* Giảm lề dưới */
    }

    /* Danh sách dự án */
    .project-timeline {
      border-left: 1px solid #c7d2fe;
      padding-left: 12px;
      margin-left: 5px;
    }

    .project-item {
      margin-bottom: 8px;
    }

    .project-header-table {
      width: 100%;
      border-collapse: collapse;
    }

    .project-title {
      font-size: 11.5px;
      font-weight: bold;
      color: #0f172a;
    }

    .project-duration {
      font-size: 9.5px;
      color: #94a3b8;
      font-style: italic;
      text-align: right;
    }

    .project-role {
      font-size: 10px;
      color: #4f46e5;
      font-weight: bold;
      text-transform: uppercase;
      margin: 1px 0 2px 0;
    }

    .project-desc {
      color: #64748b;
      font-size: 11px;
      margin: 0;
      line-height: 1.25;
    }

    /* 🛠️ NĂNG LỰC CHUYÊN MÔN */
    .skills-table {
      width: 100%;
      border-collapse: collapse;
    }

    .skill-cell {
      width: 50%;
      padding-right: 25px;
      padding-bottom: 5px;
      vertical-align: top;
    }

    .skill-cell-right {
      padding-right: 0;
      padding-left: 25px;
    }

    .skill-info-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2px;
    }

    .skill-name {
      font-weight: bold;
      color: #1e293b;
    }

    .skill-level {
      font-size: 10px;
      color: #4f46e5;
      font-weight: bold;
      font-style: italic;
      text-align: right;
    }

    .progress-bg {
      height: 4px;
      width: 100%;
      background-color: #f1f5f9;
      border-radius: 2px;
    }

    .progress-bar {
      height: 4px;
      background-color: #6366f1;
      border-radius: 2px;
    }

    /* 📞 4. KHỐI NGƯỜI THAM CHIẾU (FOOTER TĨNH) */
    .reference-footer {
      margin-top: 10px;
      background-color: #fafafa;
      padding: 6px 12px;
      border-radius: 6px;
      border: 1px solid #f1f5f9;
    }

    .reference-title {
      font-size: 9.5px;
      font-weight: bold;
      text-transform: uppercase;
      color: #94a3b8;
      margin: 0 0 2px 0;
    }

    .reference-text {
      font-size: 10.5px;
      color: #64748b;
      line-height: 1.3;
    }
  </style>
</head>

<body>

  <!-- 🌸 1. KHỐI ĐẦU TRANG (HEADER) -->
  <table class="header-table" cellpadding="0" cellspacing="0">
    <tr>
      <td class="header-left">
        @php
          $nameParts = explode(' ', $candidate->full_name);
          $lastName = array_pop($nameParts);
          $firstName = implode(' ', $nameParts);
        @endphp
        <h1 class="candidate-name">
          {{ $firstName }} <span class="candidate-name-bold">{{ $lastName }}</span>
        </h1>
        <p class="candidate-title">{{ $candidate->title ?? 'UI/UX Designer & Frontend Developer' }}</p>
      </td>
      <td class="header-right">
        <div class="avatar-circle">
          <img
            src="{{ !empty($candidate->avatar_pdf_path) ? $candidate->avatar_pdf_path : asset($candidate->avatar_url) }}" />
        </div>
      </td>
    </tr>
  </table>

  <!-- 📋 2. THÔNG TIN LIÊN HỆ - THANH NGANG -->
  <table class="contact-bar" cellpadding="0" cellspacing="0">
    <tr>
      <td class="contact-item"><span class="icon-indigo">📅</span> {{ $candidate->birthday }}
        ({{ $candidate->gender || 'Nữ' }})</td>
      <td class="contact-item"><span class="icon-indigo">📞</span> {{ $candidate->phone }}</td>
      <td class="contact-item break-words"><span class="icon-indigo">✉️</span> {{ $candidate->email }}</td>
      <td class="contact-item contact-item-last break-words"><span class="icon-indigo">📍</span>
        {{ $candidate->address }}</td>
    </tr>
  </table>

  <!-- 🔗 KHỐI LIÊN KẾT MẠNG XÃ HỘI -->
  @php
    $socials = [];
    if (isset($candidate->links)) {
      if (is_string($candidate->links)) {
        $socials = json_decode($candidate->links, true) ?: [];
      } elseif (is_array($candidate->links)) {
        $socials = $candidate->links;
      }
    }
  @endphp

  @if(!empty($socials))
    <table class="social-bar" cellpadding="0" cellspacing="0">
      <tr>
        <td class="social-item break-words">
          <span class="icon-indigo" style="font-weight: bold;">🔗 Liên kết mạng xã hội:</span>
          @foreach($socials as $key => $url)
            @if(!empty($url))
              <span style="margin-right: 15px;">
                <strong style="text-transform: uppercase;">{{ $key }}:</strong>
                <span style="color: #4f46e5;">{{ $url }}</span>
              </span>
            @endif
          @endforeach
        </td>
      </tr>
    </table>
  @endif

  <!-- 📑 3. PHẦN THÂN CV (BODY) -->
  <div class="body-container">

    <!-- Khối giới thiệu & Mục tiêu nghề nghiệp -->
    <table class="intro-table" cellpadding="0" cellspacing="0">
  <tr>
    <td class="intro-left">
      <div class="section-title" style="margin-top: 0;">Giới thiệu bản thân</div>
      <div class="text-left whitespace-pre-line" style="margin: 0 0 4px 0; line-height: 1.3;">{{ $candidate->summary }}</div>

      @if($candidate->objective)
        <div class="whitespace-pre-line" style="margin: 4px 0 0 0; line-height: 1.3; text-align: left;"><span style="font-weight: bold; color: #1e293b;">Mục tiêu sự nghiệp:</span> {{ $candidate->objective }}</div>
      @endif
    </td>
    <td class="intro-right">
      <div class="experience-box">
        <div class="exp-number">{{ $candidate->experience_years ?? 0 }}</div>
        <div class="exp-label">Năm Kinh Nghiệm</div>
      </div>
    </td>
  </tr>
</table>

    <!-- 💼 KINH NGHIỆM LÀM VIỆC & DỰ ÁN THỰC CHIẾN -->
    <div class="section-title">Kinh nghiệm & Dự án thực chiến</div>
    <div class="project-timeline">
      @php
        $projects = [];
        if (is_string($candidate->project)) {
          $projects = json_decode($candidate->project, true) ?: [];
        } elseif (is_array($candidate->project)) {
          $projects = $candidate->project;
        }
      @endphp

      @if(count($projects) > 0)
        @foreach($projects as $proj)
          <div class="project-item">
            <table class="project-header-table" cellpadding="0" cellspacing="0">
              <tr>
                <td class="project-title">Dự án: {{ $proj['project_name'] ?? ($proj['name'] ?? 'Tên dự án') }}</td>
                <td class="project-duration">({{ $proj['duration'] ?? 'Chưa rõ' }} tháng)</td>
              </tr>
            </table>
            @if(isset($proj['role']))
              <div class="project-role">Vị trí: {{ $proj['role'] }}</div>
            @endif
            @if(isset($proj['description']))
              <div class="project-desc text-justify whitespace-pre-line">{!! e($proj['description']) !!}</div>
            @endif
          </div>
        @endforeach
      @else
        <p style="font-style: italic; color: #94a3b8; margin: 0;">Chưa có thông tin dự án thực chiến.</p>
      @endif
    </div>

    <!-- 🎓 HỌC VẤN & TRÌNH ĐỘ ĐÀO TẠ (ĐÃ THÊM TEXT-ALIGN: LEFT ĐỂ SỬA LỖI CHỮ "THẠC SĨ") -->
    <div class="section-title">Học vấn & Trình độ đào tạo</div>
    <div class="whitespace-pre-line"
      style="padding-left: 12px; border-left: 1px solid #c7d2fe; margin-left: 5px; color: #475569; line-height: 0.5; text-align: left;">
      {!! nl2br(e($candidate->education)) !!}
    </div>

    <!-- 🛠️ NĂNG LỰC CHUYÊN MÔN -->
<div class="section-title">Năng lực chuyên môn</div>
<table class="skills-table" cellpadding="0" cellspacing="0" style="width: 100%; table-layout: fixed;">
  @if($candidate->skills && count($candidate->skills) > 0)
    @foreach($candidate->skills->chunk(2) as $chunk)
      <tr>
        @foreach($chunk as $index => $skill)
          @php
            $levelStr = $skill->pivot->level ?? 'Cơ bản';
            $width = '40%';
            if (in_array($levelStr, ['Chuyên gia', 'Xuất sắc'])) $width = '100%';
            elseif ($levelStr === 'Thành thạo') $width = '85%';
            elseif ($levelStr === 'Khá') $width = '65%';
          @endphp
          
          <td class="skill-cell" style="width: 48%; vertical-align: top; padding-bottom: 10px; {{ $index === 0 ? 'padding-right: 4%;' : '' }}">
            
            <table cellpadding="0" cellspacing="0" style="width: 100%;">
              <tr>
                <td class="skill-name" style="text-align: left; font-weight: bold;">{{ $skill->name }}</td>
                <td class="skill-level" style="text-align: right; font-style: italic; color: #4338ca;">{{ $levelStr }}</td>
              </tr>
            </table>
            
            <div class="progress-bg" style="background-color: #e2e8f0; height: 6px; border-radius: 3px; margin-top: 4px; width: 100%;">
              <div class="progress-bar" style="width: {{ $width }}; background-color: #4f46e5; height: 100%; border-radius: 3px;"></div>
            </div>

          </td>
        @endforeach
        
        @if($chunk->count() < 2)
          <td style="width: 48%;"></td>
        @endif
      </tr>
    @endforeach
  @else
    <tr>
      <td colspan="2" style="font-style: italic; color: #94a3b8; padding-top: 3px; text-align: left;">Chưa cập nhật danh mục kỹ năng.</td>
    </tr>
  @endif
</table>

    <!-- 📞 4. THÔNG TIN XÁC THỰC (REFERENCE) -->
    @php
      $references = [];
      if (is_string($candidate->contact_reference)) {
        $references = json_decode($candidate->contact_reference, true) ?: $candidate->contact_reference;
      } else {
        $references = $candidate->contact_reference;
      }
    @endphp

    @if(!empty($references))
      <div class="reference-footer">
        <div class="reference-title">Thông tin xác thực (Reference)</div>
        <div class="reference-text">
          @if(is_array($references))
            @if(isset($references[0]) && is_array($references[0]))
              @foreach($references as $ref)
                <div style="margin-bottom: 2px;">
                  <strong style="color: #475569;">{{ $ref['name'] ?? 'Họ và tên' }}</strong>
                  @if(isset($ref['relationship'])) — {{ $ref['relationship'] }} @endif
                  @if(isset($ref['phone'])) (SĐT: {{ $ref['phone'] }}) @endif
                </div>
              @endforeach
            @else
              <div>
                <strong style="color: #475569;">{{ $references['name'] ?? 'Họ và tên' }}</strong>
                @if(isset($references['relationship'])) — {{ $references['relationship'] }} @endif
                @if(isset($references['phone'])) (SĐT: {{ $references['phone'] }}) @endif
              </div>
            @endif
          @elseif(is_string($references))
            <div class="whitespace-pre-line">{{ $references }}</div>
          @endif
        </div>
      </div>
    @endif

  </div>

</body>

</html>