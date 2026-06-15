<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CV - {{ $candidate->full_name }}</title>
    <style>
        /* 1. ĐỊNH CẤU TRÚC KHỔ GIẤY A4 TIÊU CHUẨN IN ẤN */
        @page {
            size: a4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        
        body {
            font-family: 'DejaVu Sans', monospace; 
            font-size: 11px; 
            color: #1e293b; 
            line-height: 1.4;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* 🛠️ UTILITIES TOÀN CỤC */
        .w-full { width: 100%; }
        .border-collapse { border-collapse: collapse; }
        .whitespace-pre-line { white-space: pre-line; }
        .break-all { word-break: break-all; }
        .break-words { word-wrap: break-word; }

        /* 🖥️ STYLE PHẦN ĐẦU (HEADER) */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 10px;
            margin-bottom: 14px; 
        }

        .candidate-name {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }

        .candidate-title {
            font-size: 11.5px;
            font-weight: bold;
            color: #059669; 
            margin: 0 0 4px 0;
        }

        .candidate-exp {
            font-size: 10px;
            color: #64748b; 
            font-style: italic;
            margin: 0;
        }

        .contact-info-cell {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #475569;
            vertical-align: top;
            width: 50%;
        }

        .inner-contact-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inner-contact-table td {
            padding: 2px 0;
            font-size: 10.5px;
            vertical-align: top;
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
            background-color: #0f172a; 
            color: #ffffff;
            padding: 3px 10px; 
            display: inline-block;
            margin-top: 12px; 
            margin-bottom: 6px; 
            border-radius: 2px;
        }

        .section-content {
            padding-left: 4px;
            color: #334155; 
            text-align: left;
        }

        /* 💼 STYLE KHỐI KINH NGHIỆM & DỰ ÁN */
        .project-timeline {
            border-left: 2px solid #cbd5e1; 
            padding-left: 12px;
            margin-left: 4px;
        }

        .project-item {
            margin-bottom: 12px; 
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
            font-size: 10px;
            color: #64748b;
            font-style: italic;
            text-align: right;
            vertical-align: baseline;
        }

        .project-role {
            font-size: 10.5px;
            color: #059669; 
            font-weight: bold;
            margin: 2px 0 4px 0;
        }

        .project-desc {
            color: #475569; 
            font-size: 11px;
            text-align: left; 
        }

        /* 🛠️ STYLE MA TRẬN KỸ NĂNG CÔNG NGHỆ */
        .skills-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px; 
            margin-top: 2px;
        }

        .skill-cell {
            width: 33.33%; 
            padding: 5px 8px; 
            border: 1px solid #e2e8f0; 
            background-color: #f8fafc; 
            border-radius: 4px;
            vertical-align: middle;
        }

        .skill-name {
            font-weight: bold;
            color: #1e293b;
            font-size: 10.5px; 
        }

        .skill-badge {
            float: right;
            font-size: 8.5px; 
            background-color: #e2e8f0; 
            color: #334155;
            font-weight: bold;
            padding: 1px 5px;
            border-radius: 3px;
            border: 1px solid #cbd5e1;
        }

        /* 📞 STYLE PHẦN NGƯỜI THAM CHIẾU (FOOTER) */
        .footer-reference {
            margin-top: 18px; 
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
        }

        .reference-title {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b; 
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .reference-item {
            font-size: 11px;
            color: #475569;
            margin-bottom: 3px;
        }

        .reference-name {
            font-weight: bold;
            color: #0f172a;
        }
    </style>
</head>
<body>

    <!-- 🖥️ PHẦN ĐẦU (HEADER) -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <!-- CỘT TRÁI: HỌ TÊN VÀ VỊ TRÍ -->
            <td style="vertical-align: top; text-align: left; width: 50%;">
                <h1 class="candidate-name">{{ $candidate->full_name }}</h1>
                <p class="candidate-title">// {{ $candidate->title ?? 'Cập nhật vị trí chuyên môn' }}</p>
                <p class="candidate-exp">Kinh nghiệm tích lũy: {{ $candidate->experience_years ?? 0 }} năm thực chiến</p>
            </td>
            
            <!-- CỘT PHẢI: THÔNG TIN LIÊN HỆ & LINKS (CHIA ĐỀU THÀNH 2 CỘT NỘI BỘ CHO ĐẸP) -->
            <td class="contact-info-cell">
                @php
                    // Xử lý ngày sinh
                    $birthday = $candidate->birthday;
                    if ($birthday && !str_contains($birthday, '/') && !str_contains($birthday, 'T')) {
                        try {
                            $birthday = \Carbon\Carbon::parse($birthday)->format('d/m/Y');
                        } catch (\Exception $e) {}
                    }

                    // Xử lý danh sách link liên kết xã hội
                    $socials = [];
                    if (isset($candidate->links)) {
                        if (is_string($candidate->links)) {
                            $socials = json_decode($candidate->links, true) ?: [];
                        } elseif (is_array($candidate->links)) {
                            $socials = $candidate->links;
                        }
                    }
                    
                    // Gom nhóm toàn bộ liên hệ cơ bản vào mảng để duyệt layout
                    $basic_contacts = [
                        ['label' => '☎ TEL', 'value' => $candidate->phone, 'class' => ''],
                        ['label' => '✉ EMAIL', 'value' => $candidate->email, 'class' => 'break-all'],
                        ['label' => '📍 LOC', 'value' => $candidate->address, 'class' => 'break-words'],
                        ['label' => '📅 BORN', 'value' => ($birthday ?? 'Chưa cập nhật') . " (" . ($candidate->gender ?? 'Nam') . ")", 'class' => '']
                    ];
                @endphp

                <table class="inner-contact-table">
                    <tr>
                        <!-- Sub-cột 1: Thông tin cơ bản -->
                        <td style="width: 50%; text-align: left; padding-right: 10px; border-right: 1px dashed #e2e8f0;">
                            @foreach($basic_contacts as $contact)
                                <div><span class="contact-label">{{ $contact['label'] }}:</span> <span class="{{ $contact['class'] }}">{{ $contact['value'] }}</span></div>
                            @endforeach
                        </td>
                        <!-- Sub-cột 2: Các đường link liên kết (Socials) -->
                        <td style="width: 50%; text-align: left; padding-left: 10px;">
                            @if(!empty($socials) && is_array($socials))
                                @foreach($socials as $key => $link)
                                    @if(!empty($link))
                                        <div>
                                            <span class="contact-label">🔗 {{ strtoupper($key) }}:</span> 
                                            <span class="break-all">{{ $link }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <div style="color: #94a3b8; font-style: italic;">// Không có link liên kết.</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 📝 KHỐI 01: TÓM TẮT NĂNG LỰC -->
    <div>
        <div class="section-title">01. Tóm tắt năng lực</div>
        <div class="section-content">
            <p class="break-words" style="margin: 0 0 4px 0;">{{ $candidate->summary }}</p>
            <p class="break-words" style="margin: 0; font-style: italic; padding-top: 2px;">
                <strong style="color: #0f172a; font-style: normal;">Mục tiêu chiến lược:</strong> {{ $candidate->objective }}
            </p>
        </div>
    </div>

    <!-- 💼 KHỐI 02: KINH NGHIỆM & DỰ ÁN -->
    <div style="margin-top: 4px;">
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
                            <p class="project-desc whitespace-pre-line break-words" style="margin: 2px 0 0 0;">{{ $proj['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            @elseif(is_string($candidate->project) && !empty($candidate->project))
                <p class="whitespace-pre-line break-words" style="margin: 0;">{{ $candidate->project }}</p>
            @else
                <p style="font-style: italic; color: #94a3b8; margin: 0;">// Chưa có dữ liệu dự án hệ thống.</p>
            @endif
        </div>
    </div>

    <!-- 🎓 KHỐI 03: HỌC VẤN & ĐÀO TẠ (SÁT TIÊU ĐỀ) -->
    <div style="margin-top: 4px;">
        <div class="section-title" style="margin-bottom: 3px;">03. Học vấn & Đào tạo</div>
        <div class="section-content whitespace-pre-line break-words" style="margin-top: 0; padding-top: 0; line-height: 1.3;">
            {{ $candidate->education }}
        </div>
    </div>

    <!-- 🛠️ KHỐI 04: KỸ NĂNG CÔNG NGHỆ (3 CỘT / HÀNG - CARD STYLE ĐẸP MẮT) -->
    <div style="margin-top: 4px;">
        <div class="section-title">04. Kỹ năng công nghệ</div>
        <div class="section-content" style="padding-left: 0;">
            @if($candidate->skills && count($candidate->skills) > 0)
                <table class="skills-table" cellpadding="0" cellspacing="0">
                    @foreach($candidate->skills->chunk(3) as $chunk)
                        <tr>
                            @foreach($chunk as $skill)
                                <td class="skill-cell">
                                    <span class="skill-badge">{{ $skill->pivot->level ?? 'Cơ bản' }}</span>
                                    <span class="skill-name break-words">{{ $skill->name }}</span>
                                </td>
                            @endforeach
                            @if($chunk->count() < 3)
                                @for($i = 0; $i < (3 - $chunk->count()); $i++)
                                    <td style="width: 33.33%; border: none; background: none;"></td>
                                @endfor
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="font-style: italic; color: #94a3b8; padding-left: 5px;">// Chưa cập nhật ma trận kỹ năng.</p>
            @endif
        </div>
    </div>

    <!-- 📞 KHỐI NGƯỜI THAM CHIẾU -->
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
            <div style="padding-left: 4px;">
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