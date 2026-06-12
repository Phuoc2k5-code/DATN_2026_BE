<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $candidate->full_name }}</title>
    <style>
        /* 1. CONFIG TRANG GIẤY CHUẨN IN ẤN (Mác lề 0 để đổ màu full nền sidebar) */
        @page {
            size: a4 portrait;
            margin: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif; /* Đảm bảo hỗ trợ tiếng Việt trọn vẹn */
            font-size: 11.5px;
            color: #1e293b; /* Tương đương text-slate-800 */
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* 2. ĐỊNH DẠNG LAYOUT BẢNG CHÍNH (Thay thế flex-row chia 2 cột) */
        .main-layout {
            width: 100%;
            border-collapse: collapse;
            height: 297mm; /* Chiều cao cố định chuẩn trang A4 */
        }

        /* 💥 SIDEBAR TRÁI - KHỐI MÀU NỔI BẬT */
        .sidebar-left {
            width: 35%; /* Tương đương với md:w-5/12 */
            background-color: #1e1b4b; /* Tương đương bg-indigo-950 */
            color: #e0e7ff; /* Tương đương text-indigo-100 */
            padding: 30px 20px;
            vertical-align: top;
        }

        /* Avatar cách điệu giả lập hiệu ứng Bo góc & Đổ bóng */
        .avatar-container {
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #fbbf24, #f97316); /* Khối màu gradient amber sang orange */
            border-radius: 12px;
            margin: 0 auto 20px auto;
            text-align: center;
        }

        .avatar-text {
            font-size: 45px;
            font-weight: 900;
            color: #ffffff;
            line-height: 100px; /* Căn giữa chữ cái đại diện */
        }

        .sidebar-name {
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            text-align: center;
            margin: 10px 0 5px 0;
        }

        .sidebar-title-badge {
            display: block;
            text-align: center;
            background-color: #fbbf24; /* Tương đương bg-amber-400 */
            color: #1e1b4b; /* Tương đương text-indigo-950 */
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 6px;
            margin: 0 auto 25px auto;
            width: 80%;
        }

        /* Khối liên hệ mục tiêu nhỏ trong Sidebar */
        .sidebar-section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fbbf24; /* Tương đương text-amber-400 */
            border-bottom: 1px solid #312e81; /* Tương đương border-indigo-800 */
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 10px;
        }

        .sidebar-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .sidebar-list li {
            margin-bottom: 10px;
            font-size: 11px;
            color: #c7d2fe; /* Tương đương text-indigo-200 */
        }

        .sidebar-list li strong {
            color: #ffffff;
        }

        /* 📝 CỘT NỘI DUNG PHẢI - THÔNG TIN CHI TIẾT */
        .content-right {
            width: 65%; /* Tương đương md:w-7/12 */
            background-color: #f8fafc; /* Tương đương bg-slate-50/50 */
            padding: 30px 25px;
            vertical-align: top;
        }

        /* Khung hộp Giới thiệu lồng ghép */
        .about-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            position: relative;
            margin-bottom: 25px;
        }

        .experience-badge {
            position: absolute;
            right: 0;
            top: 0;
            background-color: #ea580c; /* Tương đương bg-orange-600 */
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
            padding: 4px 10px;
            border-radius: 0 11px 0 11px;
        }

        .box-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e1b4b;
            text-transform: uppercase;
            margin: 0 0 8px 0;
        }

        /* Tiêu đề mục chính bên cột phải */
        .main-section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 3px;
            margin-top: 25px;
            margin-bottom: 12px;
        }

        /* Định dạng danh sách dự án */
        .project-border-left {
            border-left: 2px solid #e2e8f0;
            padding-left: 12px;
            margin-left: 5px;
        }

        .project-item {
            margin-bottom: 15px;
        }

        .table-fluid {
            width: 100%;
        }

        .project-name {
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
        }

        .project-duration {
            font-size: 10px;
            font-style: italic;
            text-align: right;
            color: #475569;
        }

        .project-role {
            font-size: 10.5px;
            color: #334155;
            font-style: italic;
            margin: 2px 0 4px 0;
        }

        .project-desc {
            color: #475569;
            font-size: 11px;
            text-align: justify;
            white-space: pre-line;
            margin: 0;
        }

        /* Hệ thống kỹ năng dạng Tag đặc sắc */
        .skills-container {
            margin-top: 8px;
        }

        .skill-tag {
            display: inline-block;
            background-color: #eef2ff; /* Tương đương bg-indigo-50 */
            color: #1e1b4b; /* Tương đương text-indigo-950 */
            border: 1px solid #e0e7ff;
            padding: 4px 8px;
            font-size: 10.5px;
            font-weight: bold;
            border-radius: 8px;
            margin-right: 5px;
            margin-bottom: 8px;
        }

        .level-badge {
            font-size: 9px;
            color: #ea580c; /* Tương đương text-orange-600 */
            background-color: #ffffff;
            border: 1px solid #e0e7ff;
            padding: 1px 4px;
            border-radius: 4px;
            margin-left: 3px;
        }

        .text-justify {
            text-align: justify;
        }
        
        .whitespace-pre-line {
            white-space: pre-line;
        }
    </style>
</head>
<body>

    <table class="main-layout" cellpadding="0" cellspacing="0">
        <tr>
            
            <td class="sidebar-left">
                
                <div class="avatar-container">
                    <div class="avatar-text">
                        {{ $candidate->full_name ? mb_substr($candidate->full_name, 0, 1, 'utf-8') : 'C' }}
                    </div>
                </div>

                <div class="sidebar-name">{{ $candidate->full_name }}</div>
                <div class="sidebar-title-badge">{{ $candidate->title ?? 'Creative Specialist' }}</div>

                <div class="sidebar-section-title">Kết nối</div>
                <ul class="sidebar-list">
                    <li>📅 <strong>Ngày sinh:</strong><br>{{ \Carbon\Carbon::parse($candidate->birthday)->format('d/m/Y') }} ({{ $candidate->gender || 'Nam' }})</li>
                    <li>📞 <strong>Điện thoại:</strong><br>{{ $candidate->phone }}</li>
                    <li>✉️ <strong>Email:</strong><br><span style="font-size: 10px;">{{ $candidate->email }}</span></li>
                    <li>📍 <strong>Địa chỉ:</strong><br>{{ $candidate->address }}</li>
                </ul>

                @php
                    $references = [];
                    if (is_string($candidate->contact_reference)) {
                        $references = json_decode($candidate->contact_reference, true) ?: $candidate->contact_reference;
                    } else {
                        $references = $candidate->contact_reference;
                    }
                @endphp

                @if(!empty($references))
                    <div class="sidebar-section-title">Xác minh</div>
                    <div style="padding-left: 2px;">
                        @if(is_array($references))
                            @if(isset($references[0]) && is_array($references[0]))
                                @foreach($references as $ref)
                                    <div style="margin-bottom: 8px; font-size: 10.5px;">
                                        <strong style="color: #ffffff;">{{ $ref['name'] ?? 'Họ và tên' }}</strong>
                                        @if(isset($ref['relationship'])) <br><span style="color: #fbbf24; font-style: italic;">{{ $ref['relationship'] }}</span> @endif
                                        @if(isset($ref['phone'])) <br><span style="color: #cbd5e1;">SĐT: {{ $ref['phone'] }}</span> @endif
                                    </div>
                                @endforeach
                            @else
                                <div style="font-size: 10.5px;">
                                    <strong style="color: #ffffff;">{{ $references['name'] ?? 'Họ và tên' }}</strong>
                                    @if(isset($references['relationship'])) <br><span style="color: #fbbf24; font-style: italic;">{{ $references['relationship'] }}</span> @endif
                                    @if(isset($references['phone'])) <br><span style="color: #cbd5e1;">SĐT: {{ $references['phone'] }}</span> @endif
                                </div>
                            @endif
                        @elseif(is_string($references))
                            <p style="font-size: 10.5px; color: #c7d2fe; white-space: pre-line; margin:0;">{{ $references }}</p>
                        @endif
                    </div>
                @endif

            </td>

            <td class="content-right">
                
                <div class="about-box">
                    <div class="experience-badge">{{ $candidate->experience_years ?? 0 }} Năm KN</div>
                    <div class="box-title">Về bản thân tôi</div>
                    <p class="text-justify style" style="font-size: 11px; color: #475569; margin: 0 0 8px 0; font-weight: 500;">
                        {{ $candidate->summary }}
                    </p>
                    <div style="border-top: 1px dashed #e2e8f0; margin-top: 8px; padding-top: 6px; font-size: 10.5px; color: #64748b;">
                        <span style="font-weight: bold; color: #1e1b4b;">Định hướng:</span> {{ $candidate->objective }}
                    </div>
                </div>

                <div class="main-section-title">Kinh nghiệm làm việc & Dự án tiêu biểu</div>
                <div class="project-border-left">
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
                                <table class="table-fluid" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td class="project-name">Dự án: {{ $proj['project_name'] ?? ($proj['name'] ?? 'Tên dự án') }}</td>
                                        <td class="project-duration">({{ $proj['duration'] ?? 'Chưa rõ' }})</td>
                                    </tr>
                                </table>
                                
                                @if(isset($proj['role']))
                                    <p class="project-role">Vị trí: {{ $proj['role'] }}</p>
                                @endif

                                @if(isset($proj['description']))
                                    <p class="project-desc">{{ $proj['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa cập nhật thông tin dự án.</p>
                    @endif
                </div>

                <div class="main-section-title">Học vấn học thuật</div>
                <div class="text-justify whitespace-pre-line" style="font-size: 11px; color: #475569; padding-left: 6px; border-left: 2px solid #e2e8f0; margin-left: 5px;">
                    {!! nl2br(e($candidate->education)) !!}
                </div>

                <div class="main-section-title">Kỹ năng đặc sắc</div>
                <div class="skills-container" style="padding-left: 5px;">
                    @if($candidate->skills && count($candidate->skills) > 0)
                        @foreach($candidate->skills as $skill)
                            <span class="skill-tag">
                                ⚡ {{ $skill->name }}
                                <span class="level-badge">
                                    {{ $skill->pivot->level ?? 'Cơ bản' }}
                                </span>
                            </span>
                        @endforeach
                    @else
                        <p style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa cập nhật kỹ năng.</p>
                    @endif
                </div>

            </td>
        </tr>
    </table>

</body>
</html>