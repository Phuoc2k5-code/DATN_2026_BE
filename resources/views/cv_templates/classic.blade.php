<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $candidate->full_name }}</title>
    <style>
        /* 1. KHẮC PHỤC LỖI FONT TIẾNG VIỆT & THIẾT LẬP KHỔ GIẤY CHUẨN ĐỒ ÁN */
        @page {
            size: a4 portrait;
            margin: 20mm 15mm 20mm 15mm; /* Căn lề lọt lòng trang giấy */
        }
        
        body {
            font-family: 'DejaVu Sans', serif; /* Thay Times New Roman bằng DejaVu Sans để hiển thị Tiếng Việt hoàn hảo */
            font-size: 12px;
            color: #0f172a; /* Tương đương text-slate-900 */
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* 2. ĐỊNH DẠNG LAYOUT CHUẨN (Thay cho Flexbox/Grid của Tailwind) */
        .w-full {
            width: 100%;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-justify {
            text-align: justify;
        }

        /* Phần đầu CV (Header) */
        .header {
            border-bottom: 2px solid #1e293b; /* Tương đương border-slate-800 */
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .full-name {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 5px 0;
        }

        .title {
            font-size: 12px;
            font-weight: 500;
            color: #475569; /* Tương đương text-slate-600 */
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 8px 0;
        }

        /* Thanh thông tin liên hệ */
        .contact-bar {
            font-size: 11px;
            color: #334155; /* Tương đương text-slate-700 */
            font-weight: 500;
        }

        .divider {
            color: #cbd5e1; /* Tương đương text-slate-300 */
            padding: 0 5px;
        }

        /* Các khối nội dung (Sections) */
        .section {
            margin-top: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1; /* Tương đương border-slate-300 */
            padding-bottom: 3px;
            margin-bottom: 8px;
        }

        /* Kinh nghiệm & Dự án */
        .project-container {
            border-left: 2px solid #e2e8f0; /* Tương đương border-slate-200 */
            padding-left: 12px;
            margin-top: 8px;
        }

        .project-item {
            margin-bottom: 15px;
        }

        .project-header-table td {
            vertical-align: bottom;
            font-size: 12px;
        }

        .project-title {
            font-weight: bold;
            color: #0f172a;
            max-width: 75%;
        }

        .project-duration {
            font-size: 11px;
            font-style: italic;
            text-align: right;
        }

        .project-role {
            font-size: 11px;
            color: #334155;
            font-style: italic;
            font-weight: 500;
            margin: 2px 0 4px 0;
        }

        .project-desc {
            color: #475569; /* Tương đương text-slate-600 */
            white-space: pre-line; /* Giữ nguyên xuống dòng dấu gạch đầu dòng */
            margin: 0;
            padding-left: 4px;
        }

        /* Học vấn & Giới thiệu */
        .text-block {
            color: #334155;
            white-space: pre-line;
        }

        /* Kỹ năng chuyên môn (Thay Grid 2 cột bằng Table) */
        .skills-table {
            width: 100%;
            margin-left: 10px;
        }

        .skills-table td {
            width: 50%; /* Chia đôi 2 cột như grid-cols-2 */
            padding: 4px 15px 4px 0;
            border-bottom: 1px solid #f1f5f9; /* Tương đương border-slate-100 */
            font-size: 11px;
            color: #334155;
        }

        .bullet {
            color: #94a3b8; /* Tương đương text-slate-400 */
            font-size: 8px;
            vertical-align: middle;
            margin-right: 4px;
        }

        /* Người tham chiếu */
        .reference-item {
            border-left: 2px solid #cbd5e1; /* Tương đương border-slate-300 */
            padding-left: 10px;
            margin-bottom: 10px;
        }

        .ref-name {
            font-weight: bold;
            color: #1e293b;
            font-size: 12px;
            margin: 0;
        }

        .ref-relation {
            color: #b45309; /* Tương đương text-amber-700 */
            font-style: italic;
            font-weight: 500;
            font-size: 11px;
            margin: 1px 0;
        }

        .ref-phone {
            color: #64748b; /* Tương đương text-slate-500 */
            font-size: 11px;
            margin: 0;
        }
    </style>
</head>
<body>

    <div class="header text-center">
        <h1 class="full-name">{{ $candidate->full_name }}</h1>
        <p class="title">{{ $candidate->title ?? 'Chuyên viên ứng tuyển' }}</p>

        <div class="contact-bar">
            <span>📅 {{ \Carbon\Carbon::parse($candidate->birthday)->format('d/m/Y') }} ({{ $candidate->gender || 'Nam' }})</span>
            <span class="divider">|</span>
            <span>📞 {{ $candidate->phone }}</span>
            <span class="divider">|</span>
            <span>✉️ {{ $candidate->email }}</span>
            <span class="divider">|</span>
            <span>📍 {{ $candidate->address }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Giới thiệu & Mục tiêu nghề nghiệp</div>
        <div class="text-justify" style="font-size: 11px; color: #334155;">
            <p style="margin: 0 0 5px 0; font-style: italic;">
                <strong style="font-style: normal; color: #0f172a;">Giới thiệu: </strong>{{ $candidate->summary }}
            </p>
            <p style="margin: 0 0 5px 0; font-style: italic;">
                <strong style="font-style: normal; color: #0f172a;">Mục tiêu: </strong>{{ $candidate->objective }}
            </p>
            <p style="margin: 5px 0 0 0; font-weight: bold; color: #475569; font-size: 10px;">
                Tổng thời gian tích lũy kinh nghiệm thực chiến: {{ $candidate->experience_years }} năm.
            </p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Kinh nghiệm làm việc & Dự án tiêu biểu</div>
        <div class="project-container">
            @php
                // Giải mã JSON an toàn từ DB của bảng candidates trường project
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
                        <table class="w-full project-header-table" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="project-title">{{ strtoupper($proj['project_name'] ?? ($proj['name'] ?? 'TÊN DỰ ÁN')) }}</td>
                                <td class="project-duration">({{ $proj['duration'] ?? 'Chưa cập nhật' }})</td>
                            </tr>
                        </table>

                        @if(isset($proj['role']))
                            <p class="project-role">Vị trí: {{ $proj['role'] }}</p>
                        @endif

                        @if(isset($proj['description']))
                            <p class="project-desc text-justify">{{ $proj['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            @else
                <p style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa có thông tin dự án.</p>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Học Vấn & Bằng Cấp</div>
        <div class="text-block text-justify" style="font-size: 11px; padding-left: 4px;">{!! nl2br(e($candidate->education)) !!}</div>
    </div>

    <div class="section">
        <div class="section-title">Kỹ năng chuyên môn</div>
        @if($candidate->skills && count($candidate->skills) > 0)
            <table class="skills-table" cellpadding="0" cellspacing="0">
                @foreach($candidate->skills->chunk(2) as $row)
                    <tr>
                        @foreach($row as $skill)
                            <td>
                                <span class="bullet">●</span> 
                                <strong>{{ $skill->name }}</strong> 
                                @if(isset($skill->pivot->level))
                                    ({{ $skill->pivot->level }})
                                @endif
                            </td>
                        @endforeach
                        @if(count($row) < 2)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @else
            <p style="font-size: 11px; color: #94a3b8; font-style: italic; padding-left: 4px;">Chưa cập nhật kỹ năng.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">🤝 Người xác nhận thông tin (Reference)</div>
        <div style="padding-left: 4px; margin-top: 8px;">
            @php
                $references = [];
                if (is_string($candidate->contact_reference)) {
                    $references = json_decode($candidate->contact_reference, true) ?: $candidate->contact_reference;
                } else {
                    $references = $candidate->contact_reference;
                }
            @endphp

            @if(is_array($references))
                @// Kiểm tra nếu là mảng danh sách nhiều người xác nhận
                @if(isset($references[0]) && is_array($references[0]))
                    @foreach($references as $ref)
                        <div class="reference-item">
                            <p class="ref-name">{{ $ref['name'] ?? 'Họ và tên' }}</p>
                            @if(isset($ref['relationship'])) <p class="ref-relation">Mối quan hệ: {{ $ref['relationship'] }}</p> @endif
                            @if(isset($ref['phone'])) <p class="ref-phone">SĐT: {{ $ref['phone'] }}</p> @endif
                        </div>
                    @endforeach
                @else
                    @// Nếu chỉ là 1 Object đơn lẻ
                    <div class="reference-item">
                        <p class="ref-name">{{ $references['name'] ?? 'Họ và tên' }}</p>
                        @if(isset($references['relationship'])) <p class="ref-relation">Mối quan hệ: {{ $references['relationship'] }}</p> @endif
                        @if(isset($references['phone'])) <p class="ref-phone">SĐT: {{ $references['phone'] }}</p> @endif
                    </div>
                @endif
            @elseif(is_string($references) && !empty($references))
                <p class="text-block" style="font-size: 11px;">{!! nl2br(e($references)) !!}</p>
            @else
                <p style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa có thông tin người xác nhận.</p>
            @endif
        </div>
    </div>

</body>
</html>