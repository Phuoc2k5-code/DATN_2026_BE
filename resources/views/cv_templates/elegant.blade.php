<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $candidate->full_name }}</title>
    <style>
        /* 1. ĐỊNH CẤU TRÚC KHỔ GIẤY A4 CHUẨN IN ẤN */
        @page {
            size: a4 portrait;
            margin: 20mm 15mm 20mm 15mm; /* Chừa lề thanh lịch đúng phong cách Elegant */
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Hỗ trợ hiển thị tiếng Việt trọn vẹn, không lỗi font */
            font-size: 11px;
            color: #334155; /* Tương đương text-slate-700 */
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Utility classes thay thế Tailwind */
        .w-full { width: 100%; }
        .border-collapse { border-collapse: collapse; }
        .text-justify { text-align: justify; }
        .whitespace-pre-line { white-space: pre-line; }
        
        /* 🌸 1. KHỐI ĐẦU TRANG (HEADER) */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f8fafc; /* Tương đương bg-slate-50 */
            border-bottom: 1px solid #e2e8f0; /* Tương đương border-slate-200 */
            margin-bottom: 0px;
        }
        
        .header-left {
            padding: 25px;
            vertical-align: middle;
        }
        
        .header-right {
            padding: 25px;
            width: 90px;
            text-align: right;
            vertical-align: middle;
        }

        .candidate-name {
            font-size: 22px;
            color: #1e293b; /* Tương đương text-slate-800 */
            margin: 0 0 4px 0;
            font-weight: normal;
        }

        .candidate-name-bold {
            font-weight: bold;
            color: #312e81; /* Tương đương text-indigo-900 */
        }

        .candidate-title {
            font-size: 10px;
            font-weight: bold;
            color: #4f46e5; /* Tương đương text-indigo-600 */
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        /* Avatar bo tròn nghệ thuật */
        .avatar-circle {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .avatar-text {
            font-size: 22px;
            color: #6366f1; /* Tương đương text-indigo-500 */
            line-height: 75px;
            font-weight: normal;
        }

        /* 📋 2. THÔNG TIN LIÊN HỆ - THANH NGANG GỌN GÀNG */
        .contact-bar {
            width: 100%;
            border-collapse: collapse;
            background-color: #0f172a; /* Tương đương bg-slate-900 */
            color: #cbd5e1; /* Tương đương text-slate-300 */
            margin-bottom: 25px;
        }

        .contact-item {
            width: 25%; /* Thay thế hoàn hảo cho grid-cols-4 */
            padding: 8px 10px;
            font-size: 10px;
            text-align: center;
            border-right: 1px solid #334155;
        }

        .contact-item-last {
            border-right: none;
        }

        .icon-indigo {
            color: #818cf8; /* Tương đương text-indigo-400 */
        }

        /* 📑 3. PHẦN THÂN CV (BODY) */
        .body-container {
            padding: 0 5px;
        }

        /* Bố cục chia Khối giới thiệu & Số năm kinh nghiệm */
        .intro-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
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
            padding: 12px;
            text-align: center;
        }

        .exp-number {
            font-size: 24px;
            color: #4f46e5;
            font-weight: normal;
            line-height: 1;
            margin-bottom: 4px;
        }

        .exp-label {
            font-size: 8.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Tiêu đề phân đoạn chuẩn Elegant */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e1b4b; /* Tương đương text-indigo-900 */
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 6px;
            margin-top: 20px;
            margin-bottom: 12px;
        }

        /* Danh sách dự án */
        .project-timeline {
            border-left: 1px solid #c7d2fe; /* Tương đương border-indigo-200 */
            padding-left: 12px;
            margin-left: 5px;
        }

        .project-item {
            margin-bottom: 18px;
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
            margin: 2px 0 4px 0;
        }

        .project-desc {
            color: #64748b;
            font-size: 11px;
            margin: 0;
        }

        /* 🛠️ NĂNG LỰC CHUYÊN MÔN (Hệ thống thanh đo Level đặc thù) */
        .skills-table {
            width: 100%;
            border-collapse: collapse;
        }

        .skill-cell {
            width: 50%; /* Chia đôi 2 cột kỹ năng tương đương sm:grid-cols-2 */
            padding-right: 25px;
            padding-bottom: 10px;
            vertical-align: top;
        }

        .skill-cell-right {
            padding-right: 0;
            padding-left: 25px;
        }

        .skill-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .skill-name {
            font-weight: bold;
            color: #1e293b;
        }

        .skill-level {
            font-size: 10px;
            color: #4f46e5;
            font-weight: bold;
            text-style: italic;
            text-align: right;
        }

        /* Giả lập thanh Progress Bar bằng Div tĩnh lồng nhau */
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
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            background-color: #fafafa;
            padding: 12px;
            border-radius: 6px;
        }

        .reference-title {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 6px 0;
        }

        .reference-text {
            font-size: 10.5px;
            color: #64748b;
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
        <div class="avatar-text">
            @if(!empty($candidate->avatar_url))
                {{-- Nếu có ảnh, hiển thị thẻ img chuẩn Laravel Blade --}}
                <img src="{{ asset($candidate->avatar_url) }}" alt="Avatar" />
            @else
                {{-- Nếu không có ảnh, lấy chữ cái đầu tiên của Tên để làm avatar chữ --}}
                <span>{{ $lastName ? mb_substr($lastName, 0, 1, 'utf-8') : 'CV' }}</span>
            @endif
        </div>
    </div>
</td>
        </tr>
    </table>

    <!-- 📋 2. THÔNG TIN LIÊN HỆ - THANH NGANG GỌN GÀNG -->
    <table class="contact-bar" cellpadding="0" cellspacing="0">
        <tr>
            <td class="contact-item"><span class="icon-indigo">📅</span> {{ $candidate->birthday }} ({{ $candidate->gender || 'Nữ' }})</td>
            <td class="contact-item"><span class="icon-indigo">📞</span> {{ $candidate->phone }}</td>
            <td class="contact-item"><span class="icon-indigo">✉️</span> {{ $candidate->email }}</td>
            <td class="contact-item contact-item-last"><span class="icon-indigo">📍</span> {{ $candidate->address }}</td>
        </tr>
    </table>

    <!-- 📑 3. PHẦN THÂN CV (BODY) -->
    <div class="body-container">
        
        <!-- Khối giới thiệu lồng ghép Năm kinh nghiệm -->
        <table class="intro-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="intro-left">
                    <div class="section-title" style="margin-top: 0;">Giới thiệu bản thân</div>
                    <p class="text-justify" style="margin: 0 0 8px 0;">{{ $candidate->summary }}</p>
                    <p class="text-justify" style="margin: 0;"><span style="font-weight: bold; color: #1e293b;">Mục tiêu sự nghiệp:</span> {{ $candidate->objective }}</p>
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
                                <td class="project-duration">({{ $proj['duration'] ?? 'Chưa rõ' }})</td>
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
                <p style="font-style: italic; color: #94a3b8;">Chưa có thông tin dự án thực chiến.</p>
            @endif
        </div>

        <!-- 🎓 HỌC VẤN & TRÌNH ĐỘ ĐÀO TẠ -->
        <div class="section-title">Học vấn & Trình độ đào tạo</div>
        <div class="text-justify whitespace-pre-line" style="padding-left: 12px; border-left: 1px solid #c7d2fe; margin-left: 5px; color: #64748b;">
            {!! nl2br(e($candidate->education)) !!}
        </div>

        <!-- 🛠️ NĂNG LỰC CHUYÊN MÔN (Hệ thống thanh đo Progress bar tĩnh) -->
        <div class="section-title">Năng lực chuyên môn</div>
        <table class="skills-table" cellpadding="0" cellspacing="0">
            @if($candidate->skills && count($candidate->skills) > 0)
                @foreach($candidate->skills->chunk(2) as $chunk)
                    <tr>
                        @foreach($chunk as $index => $skill)
                            @php
                                $levelStr = $skill->pivot->level ?? 'Cơ bản';
                                // Chuyển đổi level chữ từ DB sang phần trăm chiều rộng tĩnh cho PDF
                                $width = '40%';
                                if (in_array($levelStr, ['Chuyên gia', 'Xuất sắc'])) $width = '100%';
                                elseif ($levelStr === 'Thành thạo') $width = '85%';
                                elseif ($levelStr === 'Khá') $width = '65%';
                            @endphp
                            <td class="skill-cell {{ $index === 1 ? 'skill-cell-right' : '' }}">
                                <table class="skill-info-table" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td class="skill-name">{{ $skill->name }}</td>
                                        <td class="skill-level">{{ $levelStr }}</td>
                                    </tr>
                                </table>
                                <div class="progress-bg">
                                    <div class="progress-bar" style="width: {{ $width }};"></div>
                                </div>
                            </td>
                        @endforeach
                        {{-- Điền nốt ô trống nếu mảng lẻ --}}
                        @if($chunk->count() < 2)
                            <td class="skill-cell skill-cell-right"></td>
                        @endif
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="2" style="font-style: italic; color: #94a3b8;">Chưa cập nhật danh mục kỹ năng.</td>
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
                                <div style="margin-bottom: 4px;">
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