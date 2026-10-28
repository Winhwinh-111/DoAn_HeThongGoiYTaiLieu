@extends('layouts.admin')

@section('title', 'Thống kê Phản hồi & Tìm kiếm')
@section('page_title', 'BÁO CÁO PHẢN HỒI GỢI Ý VÀ XU HƯỚNG TÌM KIẾM')

@section('content')
<div class="row g-4 mb-4">
    <!-- Thống kê đánh giá gợi ý AI bằng Biểu đồ tròn -->
    <div class="col-md-6">
        <div class="card card-custom bg-white p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-pie-chart-fill me-2 text-danger"></i>ĐÁNH GIÁ CHẤT LƯỢNG GỢI Ý</h6>
            <div class="mt-3">
                <p class="mb-2 fs-7">Tổng lượt phản hồi: <strong>{{ $totalFeedback }}</strong></p>
                
                @if($totalFeedback > 0)
                    <div style="max-width: 280px; margin: 15px auto 0 auto;">
                        <canvas id="feedbackPieChart"></canvas>
                    </div>
                @else
                    <div class="alert alert-light text-muted text-center my-4">Chưa có dữ liệu phản hồi từ sinh viên.</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Thống kê từ khóa tìm kiếm nhiều nhất -->
    <div class="col-md-6">
        <div class="card card-custom bg-white p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-search me-2 text-primary"></i>TOP TỪ KHÓA TÌM KIẾM NHIỀU NHẤT</h6>
            <ul class="list-group list-group-flush fs-7 mt-2">
                @forelse($topKeywords as $k)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="bi bi-tag-fill me-2 text-secondary"></i>{{ $k->tu_khoa }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $k->count }} lượt</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Chưa có dữ liệu từ khóa tìm kiếm.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@if($totalFeedback > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('feedbackPieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Phù hợp', 'Chưa phù hợp'],
                datasets: [{
                    data: [{{ $goodFeedback }}, {{ $badFeedback }}],
                    backgroundColor: ['#198754', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>
@endif
@endsection