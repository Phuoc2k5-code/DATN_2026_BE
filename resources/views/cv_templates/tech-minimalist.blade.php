<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $candidate->full_name }}</title>
    <style>
        /* 1. ĐỊNH CẤU TRÚC KHỔ GIẤY A4 TIÊU CHUẨN IN ẤN */
        @page {
            size: a4 portrait;
            margin: 20mm; /* Đảm bảo khoảng cách an toàn với viền giấy */
        }
        
        body {
            font-family: 'DejaVu Sans', monospace; /* Giữ phong cách Monospace của lập trình viên và bảo đảm hiển thị tiếng Việt */
            font-size: 11.5px;
            color: #1e293b; /* Tương đương text-slate-800 */
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* 🛠️ UTILITIES TOÀN CỤC */
        .w-full { width: 100%; }
        .border-collapse { border-collapse: collapse; }
        .text-justify { text-align: justify; }
        .whitespace-pre-line { white-space: pre-line; }
        .break-all { word-break: break-all; }
        .break-words { word-wrap: break-word; }

        /* 🖥️ STYLE PHẦN ĐẦU (HEADER) */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a; /* Đường gạch dưới bản đậm phong cách tối giản */
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .candidate-name {
            font-size: 20px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }

        .candidate-title {
            font-size: 11px;
            font-weight: bold;
            color: #059669; /* Tương đương text-emerald-600 */
            margin: 0 0 4px 0;
        }

        .candidate-exp {
            font-size: 10.5px;
            color: #94a3b8; /* text-slate-400 */
            font-style: italic;
            margin: 0;
        }

        .contact-info-cell {
            text-align: right;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #475569;
            vertical-align: top;
            line-height: 1.6;
        }

        .contact-label {
            font-weight: bold;
            color: #0f172a;
        }

        /* 📝 STYLE KHỐI TIÊU ĐỀ CHỈ MỤC (01, 02, 03...) */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f1f5f9; /* bg-slate-100 */
            color: #0f172a;
            padding: 4px 8px;
            border: 1px solid #cbd5e1; /* border-slate-300 */
            display: inline-block;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .section-content {
            padding-left: 5px;
            color: #475569; /* text-slate-600 */
        }

        /* 💼 STYLE KHỐI KINH NGHIỆM & DỰ ÁN */
        .project-timeline {
            border-left: 2px solid #0f172a; /* Đường biên dọc màu tối cứng cáp */
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
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .project-duration {
            font-size: 10px;
            color: #94a3b8;
            font-style: italic;
            text-align: right;
            vertical-align: baseline;
        }

        .project-role {
            font-size: 10.5px;
            color: #059669; /* text-emerald-600 */
            font-weight: bold;
            margin: 2px 0 4px 0;
        }

        .project-desc {
            color: #64748b; /* text-slate-500 */
            font-size: 11px;
        }

        /* 🛠️ STYLE MA TRẬN KỸ NĂNG CÔNG NGHỆ (THAY THẾ KỸ THUẬT GRID BẰNG TABLE) */
        .skills-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px; /* Tạo khoảng cách gap giữa các ô như bản Web */
            margin-top: 5px;
        }

        .skill-cell {
            width: 50%; /* Chia đôi 2 cột đều nhau */
            padding: 8px;
            border: 1px solid #e2e8f0; /* border-slate-200 */
            background-color: #f8fafc; /* bg-slate-50/50 */
            border-radius: 4px;
            vertical-align: middle;
        }

        .skill-name {
            font-weight: bold;
            color: #0f172a;
            font-size: 11px;
        }

        .skill-badge {
            float: right;
            font-size: 9px;
            background-color: #0f172a; /* bg-slate-900 */
            color: #ffffff;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
        }

        /* 📞 STYLE PHẦN NGƯỜI THAM CHIẾU (FOOTER) */
        .footer-reference {
            margin-top: 35px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .reference-title {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #94a3b8; /* text-slate-400 */
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .reference-item {
            font-size: 11px;
            color: #475569;
            margin-bottom: 4px;
        }

        .reference-name {
            font-weight: bold;
            color: #0f172a;
        }
    </style>
</head>
<body>

    <!-- 🖥️ PHẦN ĐẦU (HEADER) - THAY THẾ FLEXBOX BẰNG TABLE TĨNH -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: top; text-align: left;">
                <h1 class="candidate-name">{{ $candidate->full_name }}</h1>
                <p class="candidate-title">// {{ $candidate->title ?? 'Cập nhật vị trí chuyên môn' }}</p>
                <p class="candidate-exp">Kinh nghiệm tích lũy: {{ $candidate->experience_years ?? 0 }} năm thực chiến</p>
            </td>
            <td class="contact-info-cell">
                <div><span class="contact-label">☎ TEL:</span> {{ $candidate->phone }}</div>
                <div><span class="contact-label">✉ EMAIL:</span> <span class="break-all">{{ $candidate->email }}</span></div>
                <div><span class="contact-label">📍 LOC:</span> <span class="break-words">{{ $candidate->address }}</span></div>
                @php
                    $birthday = $candidate->birthday;
                    if ($birthday && !str_contains($birthday, '/') && !str_contains($birthday, 'T')) {
                        try {
                            $birthday = \Carbon\Carbon::parse($birthday)->format('d/m/Y');
                        } catch (\Exception $e) {}
                    }
                @endphp
                <div><span class="contact-label">📅 BORN:</span> {{ $birthday ?? 'Chưa cập nhật' }} ({{ $candidate->gender ?? 'Nam' }})</div>
            </td>
        </tr>
    </table>

    <!-- 📝 KHỐI 01: TÓM TẮT NĂNG LỰC -->
    <div>
        <div class="section-title">01. Tóm tắt năng lực</div>
        <div class="section-content">
            <p class="text-justify break-words" style="margin: 0 0 6px 0;">{{ $candidate->summary }}</p>
            <p class="text-justify break-words" style="margin: 0; font-style: italic; padding-top: 4px;">
                <strong style="color: #0f172a; font-style: normal;">Mục tiêu chiến lược:</strong> {{ $candidate->objective }}
            </p>
        </div>
    </div>

    <!-- 💼 KHỐI 02: KINH NGHIỆM & DỰ ÁN -->
    <div style="margin-top: 15px;">
        <div class="section-title">02. Kinh nghiệm & Dự án</div>
        <div class="section-content project-timeline">
            @php
                $projects = [];
                if (is_string($candidate->project)) {
                    $projects = json_decode($candidate->project, true) ?: [];
                } elseif (is_array($candidate->project)) {
                    $projects = $candidate->project;
                }
            @endphp

            @if(!empty($projects) && is_array($projects))
                @foreach($projects as $proj)
                    <div class="project-item">
                        <table class="project-header-table" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="project-title">&gt; Dự án: {{ $proj['project_name'] ?? ($proj['name'] ?? 'Tên dự án') }}</td>
                                <td class="project-duration">({{ $proj['duration'] ?? 'Chưa rõ thời gian' }})</td>
                            </tr>
                        </table>
                        @if(isset($proj['role']))
                            <p class="project-role">// Vị trí: {{ $proj['role'] }}</p>
                        @endif
                        @if(isset($proj['description']))
                            <p class="project-desc text-justify whitespace-pre-line break-words" style="margin: 4px 0 0 0;">{{ $proj['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            @elseif(is_string($candidate->project) && !empty($candidate->project))
                <p class="text-justify whitespace-pre-line break-words" style="margin: 0;">{{ $candidate->project }}</p>
            @else
                <p style="font-style: italic; color: #94a3b8; margin: 0;">// Chưa có dữ liệu dự án hệ thống.</p>
            @endif
        </div>
    </div>

    <!-- 🎓 KHỐI 03: HỌC VẤN & ĐÀO TẠ -->
    <div style="margin-top: 15px;">
        <div class="section-title">03. Học vấn & Đào tạo</div>
        <div class="section-content text-justify whitespace-pre-line break-words" style="margin: 0;">
            {{ $candidate->education }}
        </div>
    </div>

    <!-- 🛠️ KHỐI 04: KỸ NĂNG CÔNG NGHỆ (THAY THẾ GRID-COLS-2 BẰNG CHIA ĐÔI DÒNG TABLE) -->
    <div style="margin-top: 15px;">
        <div class="section-title">04. Kỹ năng công nghệ</div>
        <div class="section-content" style="padding-left: 0;">
            @if($candidate->skills && count($candidate->skills) > 0)
                <table class="skills-table" cellpadding="0" cellspacing="0">
                    @foreach($candidate->skills->chunk(2) as $chunk)
                        <tr>
                            @foreach($chunk as $skill)
                                <td class="skill-cell">
                                    <span class="skill-badge">{{ $skill->pivot->level ?? 'Cơ bản' }}</span>
                                    <span class="skill-name break-words">{{ $skill->name }}</span>
                                </td>
                            @endforeach
                            {{-- Nếu mảng lẻ phần tử, thêm một ô trống để đảm bảo cấu trúc bảng không bị méo --}}
                            @if($chunk->count() < 2)
                                <td style="width: 50%; border: none; background: none;"></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="font-style: italic; color: #94a3b8; padding-left: 5px;">// Chưa cập nhật ma trận kỹ năng.</p>
            @endif
        </div>
    </div>

    <!-- 📞 KHỐI NGƯỜI THAM CHIẾU (FOOTER XÁC MINH AN TOÀN) -->
    @php
        $references = [];
        if (is_string($candidate->contact_reference)) {
            $references = json_decode($candidate->contact_reference, true) ?: $candidate->contact_reference;
        } else {
            $references = $candidate->contact_reference;
        }
    @endphp

    @if(!empty($references))
        <div class="footer-reference">
            <div class="reference-title">[References / Người xác minh]</div>
            <div style="padding-left: 5px;">
                @if(is_array($references))
                    @if(isset($references[0]) && is_array($references[0]))
                        @foreach($references as $ref)
                            <div class="reference-item">
                                <span class="reference-name">{{ $ref['name'] ?? 'Họ và tên' }}</span>
                                @if(isset($ref['relationship'])) — {{ $ref['relationship'] }} @endif
                                @if(isset($ref['phone'])) (SĐT: {{ $ref['phone'] }}) @endif
                            </div>
                        @endforeach
                    @else
                        <div class="reference-item">
                            <span class="reference-name">{{ $references['name'] ?? 'Họ và tên' }}</span>
                            @if(isset($references['relationship'])) — {{ $references['relationship'] }} @endif
                            @if(isset($references['phone'])) (SĐT: {{ $references['phone'] }}) @endif
                        </div>
                    @endif
                @elseif(is_string($references))
                    <p class="whitespace-pre-line break-words" style="margin: 0; font-size: 11px;">{{ $references }}</p>
                @endif
            </div>
        </div>
    @endif

</body>
</html>