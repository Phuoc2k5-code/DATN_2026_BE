<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $candidate->full_name }}</title>
    <style>
        /* 1. ĐỊNH DẠNG TRANG & SỬA LỖI Ô VUÔNG FONT */
        @page {
            size: a4 portrait;
            margin: 12mm 12mm 12mm 12mm;
        }
        
        html, body, div, p, span, table, tr, td, h1, h2, h3, h4, strong, b, i, em, a {
            font-family: 'DejaVu Sans', sans-serif !important;
        }

        body {
            font-size: 12px; 
            color: #0f172a; 
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        strong, b {
            font-weight: bold;
            color: #000000;
        }
        
        i, em {
            font-style: italic;
        }

        .w-full {
            width: 100%;
        }

        /* 2. HEADER SIÊU GỌN - XÓA BỎ BIỂU TƯỢNG LỖI */
        .header {
            border-bottom: 2px solid #1e293b; 
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .full-name {
            font-size: 26px;
            font-weight: bold;
            margin: 0 0 2px 0;
            color: #0f172a;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #475569; 
            margin: 0 0 5px 0;
        }

        /* Bảng thông tin liên hệ trơn - không sợ lỗi ô vuông */
        .contact-table {
            width: 100%;
            font-size: 11px;
            color: #475569;
            margin-top: 5px;
        }

        .contact-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .contact-table a {
            color: #1e293b;
            text-decoration: none;
        }

        /* 3. LAYOUT 2 CỘT KHÔNG BỊ TRÀN GIẤY */
        .main-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .main-layout td {
            vertical-align: top;
        }

        .left-column {
            width: 63%;
            padding-right: 15px;
        }

        .right-column {
            width: 37%;
            border-left: 1px solid #e2e8f0;
            padding-left: 15px;
        }

        .section {
            margin-bottom: 12px;
        }

        .section-title {
            font-size: 13px; 
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1; 
            padding-bottom: 2px;
            margin-bottom: 6px;
        }

        /* FIX LỖI GIÃN CHỮ: Dùng text-align: left tuyệt đối */
        .text-block-left {
            color: #334155;
            white-space: pre-line;
            font-size: 11.5px;
            text-align: left !important; /* Triệt tiêu hoàn toàn justify lỗi */
        }

        .info-row {
            margin-bottom: 5px;
        }

        /* Kinh nghiệm & Dự án */
        .project-container {
            border-left: 2px solid #1e293b; 
            padding-left: 8px;
        }

        .project-item {
            margin-bottom: 8px;
        }

        .project-header-table td {
            vertical-align: top;
            font-size: 12px;
        }

        .project-title {
            font-weight: bold;
            color: #0f172a;
        }

        .project-duration {
            font-size: 11px;
            font-style: italic;
            text-align: right;
            white-space: nowrap;
        }

        .project-role {
            font-size: 11px;
            color: #475569;
            font-style: italic;
            margin: 1px 0;
        }

        .project-desc {
            color: #334155; 
            white-space: pre-line; 
            margin: 0;
            text-align: left; /* Căn trái toàn bộ phần mô tả */
        }

        /* Danh sách kỹ năng bên phải */
        .skills-list {
            margin: 0;
            padding: 0 0 0 12px;
            font-size: 11.5px;
            color: #334155;
        }

        .skills-list li {
            margin-bottom: 4px;
        }

        /* Người tham chiếu */
        .reference-item {
            margin-bottom: 6px;
            font-size: 11.5px;
        }

        .ref-name {
            font-weight: bold;
            color: #1e293b;
            margin: 0;
        }

        .ref-relation {
            color: #b45309; 
            font-style: italic;
            margin: 0;
        }

        .ref-phone {
            color: #64748b; 
            margin: 0;
        }
    </style>
</head>
<body>

    <!-- HEADER SIÊU GỌN -->
    <div class="header">
        <table class="w-full" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align: bottom;">
                    <h1 class="full-name">{{ $candidate->full_name }}</h1>
                </td>
                <td style="text-align: right; vertical-align: bottom; padding-bottom: 3px;">
                    <span class="title">{{ $candidate->title ?? 'Chuyên viên ứng tuyển' }}</span>
                </td>
            </tr>
        </table>

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

        <!-- FIX LỖI Ô VUÔNG: Thay emoji bằng text sạch -->
        <table class="contact-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 35%;">Ngày sinh: {{ \Carbon\Carbon::parse($candidate->birthday)->format('d/m/Y') }} ({{ $candidate->gender || 'Nam' }})</td>
                <td style="width: 30%; text-align: center;">SĐT: {{ $candidate->phone }}</td>
                <td style="width: 35%; text-align: right;">Email: {{ $candidate->email }}</td>
            </tr>
            <tr>
                <td>Địa chỉ: {{ $candidate->address }}</td>
                <td style="text-align: center;">
                    @if(!empty($socials['github'])) GitHub: <a href="{{ $socials['github'] }}" target="_blank">Link</a> @endif
                </td>
                <td style="text-align: right;">
                    @if(!empty($socials['linkedin'])) LinkedIn: <a href="{{ $socials['linkedin'] }}" target="_blank">Link</a> @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- LAYOUT 2 CỘT TỐI ƯU KHÔNG GIAN -->
    <table class="main-layout" cellpadding="0" cellspacing="0">
        <tr>
            <!-- CỘT TRÁI (NỘI DUNG DÀI) -->
            <td class="left-column">
                
                <div class="section">
                    <div class="section-title">Giới thiệu & Mục tiêu nghề nghiệp</div>
                    <div class="text-block-left">
                        <div class="info-row"><strong>Giới thiệu:</strong> <i>{{ $candidate->summary }}</i></div>
                        <div class="info-row"><strong>Mục tiêu:</strong> <i>{{ $candidate->objective }}</i></div>
                        <div style="margin-top: 5px; font-weight: bold; color: #475569; font-size: 11px;">
                            Tổng thời gian tích lũy kinh nghiệm thực chiến: {{ $candidate->experience_years }} năm.
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">Kinh nghiệm làm việc & Dự án tiêu biểu</div>
                    <div class="project-container">
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
                                    <table class="w-full project-header-table" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td class="project-title">{{ $proj['project_name'] ?? ($proj['name'] ?? 'Tên dự án') }}</td>
                                            <td class="project-duration">({{ $proj['duration'] ?? 'Chưa cập nhật' }})</td>
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
                            <p style="font-size: 11.5px; color: #94a3b8; font-style: italic;">Chưa có thông tin dự án.</p>
                        @endif
                    </div>
                </div>

            </td>

            <!-- CỘT PHẢI (DANH MỤC NGẮN) -->
            <td class="right-column">
                
                <div class="section">
                    <div class="section-title">Học Vấn & Bằng Cấp</div>
                    <div class="text-block-left">{!! nl2br(e($candidate->education)) !!}</div>
                </div>

                <div class="section">
                    <div class="section-title">Kỹ năng chuyên môn</div>
                    @if($candidate->skills && count($candidate->skills) > 0)
                        <ul class="skills-list">
                            @foreach($candidate->skills as $skill)
                                <li>
                                    <strong>{{ $skill->name }}</strong>
                                    @if(isset($skill->pivot->level))
                                        <span style="color: #64748b; font-size: 10.5px;">({{ $skill->pivot->level }})</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa cập nhật kỹ năng.</p>
                    @endif
                </div>

                <div class="section">
                    <div class="section-title">Người xác nhận (Reference)</div>
                    <div>
                        @php
                            $references = [];
                            if (is_string($candidate->contact_reference)) {
                                $references = json_decode($candidate->contact_reference, true) ?: $candidate->contact_reference;
                            } else {
                                $references = $candidate->contact_reference;
                            }
                        @endphp

                        @if(is_array($references))
                            @if(isset($references[0]) && is_array($references[0]))
                                @foreach($references as $ref)
                                    <div class="reference-item">
                                        <p class="ref-name">{{ $ref['name'] ?? 'Họ và tên' }}</p>
                                        @if(isset($ref['relationship'])) <p class="ref-relation">{{ $ref['relationship'] }}</p> @endif
                                        @if(isset($ref['phone'])) <p class="ref-phone">SĐT: {{ $ref['phone'] }}</p> @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="reference-item">
                                    <p class="ref-name">{{ $references['name'] ?? 'Họ và tên' }}</p>
                                    @if(isset($references['relationship'])) <p class="ref-relation">{{ $references['relationship'] }}</p> @endif
                                    @if(isset($references['phone'])) <p class="ref-phone">SĐT: {{ $references['phone'] }}</p> @endif
                                </div>
                            @endif
                        @elseif(is_string($references) && !empty($references))
                            <div class="text-block-left" style="font-size: 11.5px;">{!! nl2br(e($references)) !!}</div>
                        @else
                            <p style="font-size: 11px; color: #94a3b8; font-style: italic;">Chưa có thông tin.</p>
                        @endif
                    </div>
                </div>

            </td>
        </tr>
    </table>

</body>
</html>