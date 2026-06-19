<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<title>CV - {{ $candidate->full_name }}</title>
	<style>
		/* 1. ĐỊNH CẤU TRÚC KHỔ GIẤY A4 CHUẨN IN ẤN TRÀN VIỀN */
		@page {
			size: a4 portrait;
			margin: 0;
		}

		*,
		*::before,
		*::after {
			box-sizing: border-box;
		}

		html,
		body {
			height: 100%;
			margin: 0;
			padding: 0;
			font-family: 'DejaVu Sans', sans-serif;
			font-size: 13px;
			color: #1e293b;
			background-color: #ffffff;
			-webkit-print-color-adjust: exact;
			overflow: hidden;
		}

		/* Khung bảng tổng thể - Bắt buộc dùng height: 100% để tràn viền tuyệt đối */
		.main-container-table {
			width: 100%;
			height: 100%;
			border-collapse: collapse;
			table-layout: fixed;
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
		}

		.main-row {
			height: 100%;
		}

		/* 🧭 CỘT TRÁI - SIDEBAR (Đã tối ưu khoảng trống) */
		.sidebar-cell {
			width: 35%;
			height: 100%;
			background-color: #0f172a;
			color: #cbd5e1;
			vertical-align: top;
			padding: 30px 18px;
			position: relative;
		}

		/* 📝 CỘT PHẢI - DIỆN TÍCH CHÍNH (Đón nhận thêm mục tiêu nghề nghiệp) */
		.content-cell {
			width: 65%;
			height: 100%;
			background-color: #f8fafc;
			vertical-align: top;
			padding: 35px 25px;
		}

		.w-full {
			width: 100%;
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

		/* --- STYLE CỘT TRÁI (SIDEBAR) --- */
		.avatar-box {
			text-align: center;
			margin-bottom: 20px;
		}

		.avatar-circle {
			width: 100px;
			height: 100px;
			border-radius: 50%;
			background-color: #0f172a;
			border: 3px solid #38bdf8;
			margin: 0 auto 12px auto;
			overflow: hidden;
		}

		.avatar-circle img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}

		.candidate-name {
			font-size: 20px;
			font-weight: bold;
			color: #ffffff;
			text-transform: uppercase;
			margin: 10px 0 5px 0;
			letter-spacing: 0.5px;
		}

		.candidate-title-badge {
			display: block;
			background-color: #38bdf8;
			color: #0f172a;
			font-size: 11px;
			font-weight: bold;
			text-transform: uppercase;
			padding: 4px 8px;
			border-radius: 6px;
			margin: 0 auto 15px auto;
			width: 90%;
			text-align: center;
		}

		.sidebar-section-title {
			font-size: 12px;
			font-weight: bold;
			text-transform: uppercase;
			color: #38bdf8;
			border-bottom: 1px solid #334155;
			padding-bottom: 5px;
			margin-top: 20px;
			margin-bottom: 10px;
			letter-spacing: 0.5px;
		}

		.contact-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.contact-item {
			margin-bottom: 8px;
			font-size: 12px;
			color: #cbd5e1;
			line-height: 1.4;
		}

		.contact-item strong {
			color: #ffffff;
		}

		/* KHỐI NGƯỜI XÁC NHẬN - ÉP CỐ ĐỊNH XUỐNG DƯỚI CÙNG SIDEBAR */
		.sidebar-bottom-wrapper {
			position: absolute;
			bottom: 100px;
			left: 18px;
			right: 18px;
		}

		/* --- STYLE CỘT PHẢI (MAIN CONTENT) --- */
		.main-section-title {
			font-size: 13px;
			font-weight: bold;
			text-transform: uppercase;
			color: #0f172a;
			border-bottom: 2px solid #cbd5e1;
			padding-bottom: 4px;
			margin-top: 25px;
			margin-bottom: 15px;
		}

		.project-border-left {
			border-left: 2px solid #3b82f6;
			padding-left: 15px;
			margin-left: 5px;
		}

		.project-item {
			margin-bottom: 18px;
		}

		.table-fluid {
			width: 100%;
			border-collapse: collapse;
		}

		.project-name {
			font-weight: bold;
			color: #0f172a;
			font-size: 13px;
		}

		.project-duration {
			font-size: 12px;
			font-style: italic;
			text-align: right;
			color: #64748b;
		}

		.project-role {
			font-size: 12px;
			color: #475569;
			font-style: italic;
			margin: 3px 0 6px 0;
		}

		.project-desc {
			color: #334155;
			font-size: 13px;
			margin: 0;
		}

		.skills-container {
			margin-top: 10px;
		}

		.skill-tag {
			display: inline-block;
			background-color: #e0f2fe;
			color: #0369a1;
			border: 1px solid #bae6fd;
			padding: 4px 10px;
			font-size: 12px;
			font-weight: bold;
			border-radius: 6px;
			margin-right: 6px;
			margin-bottom: 8px;
		}

		.level-badge {
			font-size: 10px;
			color: #0f172a;
			background-color: #ffffff;
			border: 1px solid #bae6fd;
			padding: 1px 4px;
			border-radius: 4px;
			margin-left: 4px;
			font-weight: normal;
		}
	</style>
</head>

<body>

	<table class="main-container-table" cellpadding="0" cellspacing="0">
		<tr class="main-row">
			<!-- CỘT SIDEBAR TRÁI -->
			<td class="sidebar-cell">

				<!-- Khung tròn chứa Avatar ứng viên -->
				<div class="avatar-box">
					<div class="avatar-circle">
						<img
							src="{{ !empty($candidate->avatar_pdf_path) ? $candidate->avatar_pdf_path : asset($candidate->avatar_url) }}" />
					</div>
					<h2 class="candidate-name">{{ $candidate->full_name }}</h2>
					<span class="candidate-title-badge">{{ $candidate->title ?? 'Vị trí công việc' }}</span>
				</div>

				<!-- TÓM TẮT PROFILE -->
				<div class="sidebar-section-title">Tóm tắt profile</div>
				<div class="text-justify" style="font-size: 12px; color: #cbd5e1; line-height: 1.5; margin-bottom: 8px;">
					{{ $candidate->summary }}
				</div>
				<div style="font-size: 11px; font-weight: bold; color: #38bdf8; margin-top: 10px; text-transform: uppercase;">
					Số năm kinh nghiệm: {{ $candidate->experience_years ?? 0 }} Năm
				</div>

				<!-- THÔNG TIN LIÊN HỆ -->
				<div class="sidebar-section-title">Thông tin liên hệ</div>
				<ul class="contact-list">
					<li class="contact-item"><strong>Ngày sinh:</strong>
						{{ \Carbon\Carbon::parse($candidate->birthday)->format('d/m/Y') }} ({{ $candidate->gender || 'Nam' }})</li>
					<li class="contact-item"><strong>Điện thoại:</strong> {{ $candidate->phone }}</li>
					<li class="contact-item break-words"><strong>Email:</strong> {{ $candidate->email }}</li>
					<li class="contact-item"><strong>Địa chỉ:</strong> {{ $candidate->address }}</li>
				</ul>

				<!-- LIÊN KẾT MẢNG $candidate->links -->
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
					<div class="sidebar-section-title">Liên kết</div>
					<ul class="contact-list">
						@foreach($socials as $key => $url)
							@if(!empty($url))
								<li class="contact-item break-words">
									<strong>{{ ucfirst($key) }}:</strong><br>
									<span style="font-size: 11px; color: #38bdf8;">{{ $url }}</span>
								</li>
							@endif
						@endforeach
					</ul>
				@endif

				<!-- NGƯỜI XÁC NHẬN - ÉP XUỐNG DƯỚI CÙNG SIDEBAR -->
				@php
					$references = [];
					if (is_string($candidate->contact_reference)) {
						$references = json_decode($candidate->contact_reference, true) ?: $candidate->contact_reference;
					} else {
						$references = $candidate->contact_reference;
					}
				@endphp

				@if(!empty($references))
					<div class="sidebar-bottom-wrapper">
						<div class="sidebar-section-title" style="margin-top: 0;">Người xác nhận</div>
						<div style="padding-left: 2px;">
							@if(is_array($references))
								@if(isset($references[0]) && is_array($references[0]))
									@foreach($references as $ref)
										<div style="margin-bottom: 8px; font-size: 12px; line-height: 1.4;">
											<strong style="color: #ffffff;">{{ $ref['name'] ?? 'Họ và tên' }}</strong>
											@if(isset($ref['relationship'])) <br><span
											style="color: #38bdf8; font-style: italic;">{{ $ref['relationship'] }}</span> @endif
											@if(isset($ref['phone'])) <br><span style="color: #cbd5e1;">SĐT: {{ $ref['phone'] }}</span> @endif
										</div>
									@endforeach
								@else
									<div style="font-size: 12px; line-height: 1.4;">
										<strong style="color: #ffffff;">{{ $references['name'] ?? 'Họ và tên' }}</strong>
										@if(isset($references['relationship'])) <br><span
										style="color: #38bdf8; font-style: italic;">{{ $references['relationship'] }}</span> @endif
										@if(isset($references['phone'])) <br><span style="color: #cbd5e1;">SĐT: {{ $references['phone'] }}</span>
										@endif
									</div>
								@endif
							@elseif(is_string($references))
								<p style="font-size: 12px; color: #cbd5e1; white-space: pre-line; margin:0; line-height: 1.4;">
									{{ $references }}</p>
							@endif
						</div>
					</div>
				@endif

			</td>

			<!-- CỘT NỘI DUNG PHẢI -->
			<td class="content-cell">

				<!-- 🎯 MỤC TIÊU NGHỀ NGHIỆP ĐÃ ĐƯỢC CHUYỂN QUA ĐÂY RỘNG RÃI -->
				@if($candidate->objective)
					<div class="main-section-title" style="margin-top: 0;">Mục tiêu nghề nghiệp</div>
					<div
						style="font-size: 13px; color: #334155; padding-left: 15px; border-left: 2px solid #3b82f6; margin-left: 5px; text-align: left; line-height: 1.5;"
						class="whitespace-pre-line break-words">
						{{ $candidate->objective }}
					</div>
				@endif

				<!-- KINH NGHIỆM LÀM VIỆC & DỰ ÁN TIÊU BIỂU -->
				<div class="main-section-title" style="{{ $candidate->objective ? '' : 'margin-top: 0;' }}">Kinh nghiệm làm việc
					& Dự án</div>
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
										<td class="project-name">{{ $proj['project_name'] ?? ($proj['name'] ?? 'Tên dự án') }}</td>
										<td class="project-duration">{{ $proj['duration'] ?? '' }}</td>
									</tr>
								</table>

								@if(isset($proj['role']))
									<p class="project-role">Vị trí đảm nhiệm: {{ $proj['role'] }}</p>
								@endif

								@if(isset($proj['description']))
									<p class="project-desc whitespace-pre-line break-words text-justify" style="line-height: 1.4;">
										{{ $proj['description'] }}</p>
								@endif
							</div>
						@endforeach
					@else
						<p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">Chưa cập nhật thông tin dự án.</p>
					@endif
				</div>

				<!-- HỌC VẤN & BẰNG CẤP -->
				<div class="main-section-title">Học vấn & Bằng cấp</div>
				<div
					style="font-size: 13px; color: #334155; padding-left: 15px; border-left: 2px solid #cbd5e1; margin-left: 5px; text-align: left; line-height: 1.0;"
					class="whitespace-pre-line break-words">
					{!! nl2br(e($candidate->education)) !!}
				</div>

				<!-- KỸ NĂNG CHUYÊN MÔN -->
				<div class="main-section-title">Kỹ năng chuyên môn</div>
				<div class="skills-container" style="padding-left: 5px;">
					@if($candidate->skills && count($candidate->skills) > 0)
						@foreach($candidate->skills as $skill)
							<span class="skill-tag">
								{{ $skill->name }}
								<span class="level-badge">
									{{ $skill->pivot->level ?? 'Cơ bản' }}
								</span>
							</span>
						@endforeach
					@else
						<p style="font-size: 13px; color: #94a3b8; font-style: italic; margin: 0;">Chưa cập nhật kỹ năng.</p>
					@endif
				</div>

			</td>
		</tr>
	</table>

</body>

</html>