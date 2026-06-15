<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<title>CV - {{ $candidate->full_name }}</title>
	<style>
		/* 1. ĐỊNH CẤU TRÚC KHỔ GIẤY A4 CHUẨN IN ẤN KHÔNG CÓ LỀ BODY TRỐNG */
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
			color: #334155;
			background-color: #ffffff;
			-webkit-print-color-adjust: exact;
			overflow: hidden;
		}

		/* Khung bảng tổng thể - Bắt buộc dùng height: 100% và table-layout: fixed để tràn viền */
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

		/* 🧭 CỘT TRÁI - ÉP FULL CHIỀU CAO XUỐNG ĐÁY TRANG */
		.sidebar-cell {
			width: 32%;
			height: 100%;
			background-color: #1e293b;
			color: #cbd5e1;
			vertical-align: top;
			padding: 35px 20px;
			position: relative;
		}

		/* 📝 CỘT PHẢI */
		.content-cell {
			width: 68%;
			height: 100%;
			background-color: #ffffff;
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
			margin-bottom: 25px;
			padding-bottom: 20px;
			border-bottom: 1px solid #334155;
		}

		.avatar-circle {
			width: 90px;
			height: 90px;
			border-radius: 50%;
			background-color: #0f172a;
			border: 3px solid #475569;
			margin: 0 auto 12px auto;
			overflow: hidden;
		}

		.avatar-circle img {
			width: 100%;
			height: 100%;
			display: block;
		}

		.candidate-name {
			font-size: 18px;
			font-weight: bold;
			color: #ffffff;
			text-transform: uppercase;
			margin: 0 0 6px 0;
			letter-spacing: 0.5px;
		}

		.candidate-title {
			font-size: 13px;
			font-weight: bold;
			color: #f59e0b;
			text-transform: uppercase;
			margin: 0;
			letter-spacing: 1px;
		}

		.sidebar-section-title {
			font-size: 13px;
			font-weight: bold;
			text-transform: uppercase;
			color: #f59e0b;
			margin-top: 25px;
			margin-bottom: 12px;
			letter-spacing: 0.5px;
		}

		.contact-list {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.contact-item {
			margin-bottom: 10px;
			font-size: 13px;
			color: #e2e8f0;
		}

		/* Định dạng link trơn tru không gạch chân trên PDF */
		.contact-item a {
			color: #cbd5e1;
			text-decoration: none;
		}

		.skill-badge {
			display: inline-block;
			padding: 6px 10px;
			background-color: #0f172a;
			color: #f1f5f9;
			font-size: 13px;
			border-radius: 4px;
			border: 1px solid #334155;
			margin-right: 6px;
			margin-bottom: 8px;
		}

		.skill-level {
			color: #f59e0b;
			font-size: 12px;
			font-weight: bold;
		}

		/* 💡 KHỐI NGƯỜI XÁC NHẬN - ÉP ĐỂ XUỐNG DƯỚI CÙNG CỦA SIDEBAR */
		.sidebar-bottom-wrapper {
			position: absolute;
			bottom: 100px;
			left: 20px;
			right: 20px;
		}

		.reference-box {
			margin-top: 10px;
			font-size: 13px;
			background-color: #0f172a;
			padding: 10px;
			border-radius: 4px;
			border-left: 3px solid #f59e0b;
		}

		.reference-name {
			font-weight: bold;
			color: #ffffff;
			margin: 0 0 2px 0;
		}

		.reference-rel {
			color: #94a3b8;
			font-style: italic;
			font-size: 12px;
			margin: 0 0 4px 0;
		}

		.reference-phone {
			color: #f59e0b;
			font-weight: bold;
			margin: 0;
		}

		/* --- STYLE CỘT PHẢI (MAIN CONTENT) --- */
		.intro-card {
			background-color: #f8fafc;
			border: 1px solid #e2e8f0;
			border-left: 4px solid #1e293b;
			border-radius: 4px;
			padding: 15px;
			margin-bottom: 25px;
		}

		.intro-card-table {
			width: 100%;
			border-collapse: collapse;
		}

		.intro-card-left {
			vertical-align: top;
			padding-right: 15px;
		}

		.intro-card-right {
			width: 95px;
			vertical-align: middle;
		}

		.exp-badge {
			background-color: #fffbeb;
			border: 1px solid #fde68a;
			border-radius: 6px;
			padding: 10px 4px;
			text-align: center;
		}

		.exp-number {
			font-size: 24px;
			font-weight: bold;
			color: #d97706;
			line-height: 1;
		}

		.exp-label {
			font-size: 10px;
			font-weight: bold;
			color: #475569;
			text-transform: uppercase;
			margin-top: 4px;
		}

		.content-section-title {
			font-size: 14px;
			font-weight: bold;
			text-transform: uppercase;
			color: #1e293b;
			margin-top: 25px;
			margin-bottom: 12px;
			padding-bottom: 5px;
			border-bottom: 2px solid #f1f5f9;
		}

		.title-indicator {
			display: inline-block;
			width: 4px;
			height: 14px;
			margin-right: 6px;
			vertical-align: middle;
			background-color: #1e293b;
		}

		.project-container {
			border-left: 2px solid #e2e8f0;
			padding-left: 15px;
			margin-left: 4px;
		}

		.project-item {
			margin-bottom: 18px;
		}

		.project-header-table {
			width: 100%;
			border-collapse: collapse;
		}

		.project-title {
			font-size: 13px;
			font-weight: bold;
			color: #1e293b;
		}

		.project-duration {
			font-size: 12px;
			color: #94a3b8;
			font-style: italic;
			text-align: right;
		}

		.project-role {
			font-size: 13px;
			color: #d97706;
			font-weight: bold;
			text-transform: uppercase;
			margin: 4px 0 6px 0;
		}

		.project-desc {
			color: #475569;
			font-size: 13px;
			margin: 0;
		}

		.education-box {
			padding-left: 15px;
			border-left: 2px solid #e2e8f0;
			margin-left: 4px;
			bottom: 100px;
			color: #475569;
			font-size: 13px;
			text-align: left;
		}
	</style>
</head>

<body>

	<table class="main-container-table" cellpadding="0" cellspacing="0">
		<tr class="main-row">
			<td class="sidebar-cell">

				<div class="avatar-box">
					<div class="avatar-circle">
						{{-- <img src="{{ $candidate->avatar_pdf_path ?? 'http://localhost:8000/'.$candidate->avatar_url }}" /> --}}
						<img
							src="{{ !empty($candidate->avatar_pdf_path) ? $candidate->avatar_pdf_path : asset($candidate->avatar_url) }}" />
					</div>
					<h2 class="candidate-name">{{ $candidate->full_name }}</h2>
					<p class="candidate-title">{{ $candidate->title ?? 'Lập trình viên' }}</p>
				</div>

				<div class="sidebar-section-title">Thông tin liên hệ</div>
				<ul class="contact-list">
					@php
						$birthday = $candidate->birthday;
						if ($birthday && !str_contains($birthday, '/') && !str_contains($birthday, 'T')) {
							try {
								$birthday = \Carbon\Carbon::parse($birthday)->format('d/m/Y');
							} catch (\Exception $e) {
							}
						}

						// ĐOẠN ĐỌC DỮ LIỆU JSON MẠNG XÃ HỘI (GITHUB, LINKEDIN)
						$socials = [];
						if (isset($candidate->links)) {
							if (is_string($candidate->links)) {
								$socials = json_decode($candidate->links, true) ?: [];
							} elseif (is_array($candidate->links)) {
								$socials = $candidate->links;
							}
						}
					@endphp
					<li class="contact-item">📅 {{ $birthday ?? 'Chưa cập nhật' }} ({{ $candidate->gender ?? 'Nam' }})</li>
					<li class="contact-item">📞 {{ $candidate->phone }}</li>
					<li class="contact-item break-words">✉️ {{ $candidate->email }}</li>
					<li class="contact-item">📍 {{ $candidate->address }}</li>

					@if(!empty($socials['github']))
						<li class="contact-item break-words">💻 <a href="{{ $socials['github'] }}"
								target="_blank">{{ $socials['github'] }}</a></li>
					@endif

					@if(!empty($socials['linkedin']))
						<li class="contact-item break-words">🔗 <a href="{{ $socials['linkedin'] }}"
								target="_blank">{{ $socials['linkedin'] }}</a></li>
					@endif
				</ul>

				<div class="sidebar-section-title">Kỹ năng chuyên môn</div>
				<div style="margin-top: 5px;">
					@if($candidate->skills && count($candidate->skills) > 0)
						@foreach($candidate->skills as $skill)
							<span class="skill-badge">
								{{ $skill->name }} <span class="skill-level">({{ $skill->pivot->level ?? 'Cơ bản' }})</span>
							</span>
						@endforeach
					@else
						<p style="font-style: italic; color: #94a3b8; font-size: 13px;">Chưa cập nhật kỹ năng.</p>
					@endif
				</div>

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
						<div class="sidebar-section-title" style="margin-top: 0; padding-top: 12px; border-top: 1px solid #334155;">
							Người xác nhận</div>
						<div style="margin-top: 5px;">
							@if(is_array($references))
								@if(isset($references[0]) && is_array($references[0]))
									@foreach($references as $ref)
										<div class="reference-box">
											<p class="reference-name">{{ $ref['name'] ?? 'Họ và tên' }}</p>
											@if(isset($ref['relationship']))
											<p class="reference-rel">{{ $ref['relationship'] }}</p> @endif
											@if(isset($ref['phone']))
											<p class="reference-phone">SĐT: {{ $ref['phone'] }}</p> @endif
										</div>
									@endforeach
								@else
									<div class="reference-box">
										<p class="reference-name">{{ $references['name'] ?? 'Họ và tên' }}</p>
										@if(isset($references['relationship']))
										<p class="reference-rel">{{ $references['relationship'] }}</p> @endif
										@if(isset($references['phone']))
										<p class="reference-phone">SĐT: {{ $references['phone'] }}</p> @endif
									</div>
								@endif
							@elseif(is_string($references))
								<p class="whitespace-pre-line" style="font-size: 13px; color: #cbd5e1; padding-left: 5px;">{{ $references }}
								</p>
							@endif
						</div>
					</div>
				@endif

			</td>

			<td class="content-cell">

				<div class="intro-card">
					<table class="intro-card-table" cellpadding="0" cellspacing="0">
						<tr>
							<td class="intro-card-left">
								<h4
									style="font-size: 13px; font-weight: bold; color: #1e293b; text-transform: uppercase; margin: 0 0 6px 0;">
									Giới thiệu & Mục tiêu</h4>
								<p class="text-justify break-words" style="font-size: 13px; color: #475569; margin: 0 0 8px 0;">
									{{ $candidate->summary }}</p>
								<p class="text-justify break-words"
									style="font-size: 13px; color: #64748b; margin: 0; padding-top: 6px; border-top: 1px dashed #e2e8f0;">
									<strong style="color: #334155;">Mục tiêu:</strong> {{ $candidate->objective }}
								</p>
							</td>
							<td class="intro-card-right">
								<div class="exp-badge">
									<div class="exp-number">{{ $candidate->experience_years ?? 0 }}</div>
									<div class="exp-label">Năm kinh<br />nghiệm</div>
								</div>
							</td>
						</tr>
					</table>
				</div>

				<div class="content-section-title">
					<span class="title-indicator"></span>Dự án thực hiện
				</div>

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
								<table class="project-header-table" cellpadding="0" cellspacing="0">
									<tr>
										<td class="project-title">💼 Dự án: {{ $proj['project_name'] ?? ($proj['name'] ?? 'Tên dự án') }}</td>
										<td class="project-duration">⏱️ {{ $proj['duration'] ?? 'Chưa rõ' }}</td>
									</tr>
								</table>
								@if(isset($proj['role']))
									<div class="project-role">Vị trí: {{ $proj['role'] }}</div>
								@endif
								@if(isset($proj['description']))
									<div class="project-desc whitespace-pre-line break-words">{!! e($proj['description']) !!}</div>
								@endif
							</div>
						@endforeach
					@else
						<p style="font-style: italic; color: #94a3b8; font-size: 13px;">Chưa có thông tin dự án.</p>
					@endif
				</div>

				<div class="content-section-title">
					<span class="title-indicator" style="background-color: #f59e0b;"></span>Học vấn & Bằng cấp
				</div>
				<div class="education-box whitespace-pre-line break-words">
					{!! nl2br(e($candidate->education)) !!}
				</div>

			</td>
		</tr>
	</table>

</body>

</html>